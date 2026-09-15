<?php
/** Run: php scripts/kasta/test-plugin.php (isolated temp files and WP API doubles). */
require __DIR__ . '/wp-test-stubs.php';
$temporary = sys_get_temp_dir() . '/maruderm-kasta-test-' . bin2hex(random_bytes(6));
mkdir($temporary . '/public', 0700, true);
define('ABSPATH', $temporary . '/public/');
$_SERVER['DOCUMENT_ROOT'] = ABSPATH;
require dirname(__DIR__, 2) . '/wp-content/mu-plugins/maruderm-kasta-feed.php';

use Maruderm\Kasta\{Settings, Storage, Endpoint, Catalog, Generator, Source, Admin};

final class FixtureSource implements Source
{
    public function __construct(public array $data) {}
    public function collect(): array { return $this->data; }
}

final class KastaPluginTests
{
    private int $checks = 0;
    public function check(bool $condition, string $message): void
    {
        if (! $condition) { throw new RuntimeException($message); }
        $this->checks++;
    }
    public function fails(callable $callback, string $message): void
    {
        try { $callback(); } catch (Throwable $error) { $this->checks++; return; }
        throw new RuntimeException($message);
    }
    public function snapshot(): array
    {
        return ['schema_version' => 1, 'currency' => 'UAH', 'stock_scope' => 'keycrm_account_total',
            'generated_at_utc' => gmdate('c'), 'completed_at_utc' => gmdate('c'),
            'stocks' => [['id' => 1, 'sku' => 'SKU001', 'quantity' => 8, 'reserve' => 3]],
            'products' => [['id' => 123, 'sku' => 'SKU001', 'language' => 'uk', 'type' => 'simple', 'regular_price' => '500',
                'sale_price' => '300', 'name_ua' => 'Крем для обличчя & тіла', 'name_ru' => 'Крем для лица',
                'description_ua' => '<p>Опис</p><script>bad()</script>', 'description_ru' => 'Описание',
                'images' => ['https://wp.maruderm.com.ua/wp-content/uploads/test.png'],
                'categories' => [['id' => 465, 'name' => 'Креми', 'depth' => 1]], 'params' => []]]];
    }
    public function run(string $temporary, ?string $recordedSource = null): void
    {
        $media = new \Maruderm\Kasta\Media();
        $this->check($media->publicUrl('https://maruderm.dev/wp-content/uploads/2026/04/test.webp') === 'https://wp.maruderm.com.ua/wp-content/uploads/2026/04/test.webp', 'Clone upload uses public counterpart');
        foreach (['https://maruderm.dev/private/test.webp', 'https://maruderm.dev.evil.test/wp-content/uploads/test.webp', 'https://wp.maruderm.com.ua/wp-content/uploads/test.webp'] as $imageUrl) {
            $this->check($media->publicUrl($imageUrl) === $imageUrl, 'Only exact local uploads are mapped');
        }
        $this->fails(fn () => $media->validate([['images' => ['https://maruderm.dev/private/test.webp']]]), 'Unmapped local images remain forbidden');
        $settings = new Settings(); $storage = new Storage($temporary . '/private');
        $this->check(! $settings->get()['enabled'], 'Default must be off');
        $settings->save(['enabled' => 1, 'automatic' => 1]);
        $key = $settings->get()['key'];
        $this->check(strlen($key) === 64 && isset($GLOBALS['kasta_events'][Settings::AUTO_HOOK]), 'Settings/key/schedule');
        $endpoint = new Endpoint($settings, $storage);
        $url = '/kasta-feed/' . $key . '/products.xml';
        $this->check($endpoint->access($url, 'GET') === 200 && $endpoint->access($url, 'HEAD') === 200, 'Feed route');
        foreach (['/kasta-feed', '/kasta-feed/', '/kasta-feed/' . $key . '/', '/kasta-feed/' . $key . '/report.json', '/kasta-feed/' . $key . '/../products.xml', '/kasta-feed/' . str_repeat('0', 64) . '/products.xml'] as $path) {
            $this->check($endpoint->access($path, 'GET') === 404, 'Folder/other file/wrong key must be hidden');
        }
        $this->check($endpoint->access('/orangejuice', 'GET') === null && $endpoint->access('/wp-admin/', 'GET') === null, 'Login/admin unaffected');
        $this->check($endpoint->access($url, 'POST') === 405, 'Only GET/HEAD');
        $settings->save(['enabled' => 1], true);
        $this->check($endpoint->access($url, 'GET') === 404, 'Rotated key revoked');
        $settings->save([]);
        $this->check(! isset($GLOBALS['kasta_events'][Settings::AUTO_HOOK]), 'Disable clears schedule');
        $this->check($endpoint->access('/kasta-feed/' . $settings->get()['key'] . '/products.xml', 'GET') === 404, 'Disabled feed hidden');
        $this->fails(fn () => (new Storage(ABSPATH . 'uploads'))->directory(true), 'Public storage must fail');
        symlink($temporary . '/public', $temporary . '/linked');
        $this->fails(fn () => (new Storage($temporary . '/linked'))->directory(true), 'Symlink storage must fail');
        $source = new FixtureSource($this->snapshot());
        $generator = new Generator($settings, $storage, $source);
        $generator->queue(); $generator->queue();
        $this->check(isset($GLOBALS['kasta_events'][Settings::MANUAL_HOOK]), 'Manual event queued while disabled');
        $result = $generator->run();
        $this->check($result['state'] === 'success', 'Generation: ' . ($result['error'] ?? ''));
        $xml = $storage->read(); $doc = new DOMDocument(); $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $this->check($xpath->evaluate('string(//offer/name)') === 'Крем для обличчя & тіла', 'Ukrainian primary name');
        $this->check($xpath->evaluate('string(//offer/price)') === '500.00' && $xpath->evaluate('string(//offer/price_old)') === '500.00', 'Regular prices');
        $this->check($xpath->evaluate('string(//offer/stock_quantity)') === '5', 'Quantity minus reserve');
        $this->check(! str_contains($xml, 'bad()'), 'Script removal');
        $this->check((fileperms($storage->path('products.xml')) & 0777) === 0600 && (fileperms($storage->directory()) & 0777) === 0700, 'Private permissions');
        $held = fopen($storage->path('generation.lock'), 'c'); flock($held, LOCK_EX);
        $this->check($generator->run()['state'] === 'running' && $storage->read() === $xml, 'Concurrent generator blocked');
        flock($held, LOCK_UN); fclose($held);
        $GLOBALS['kasta_media_ok'] = false;
        $this->check($generator->run()['state'] === 'failed' && $storage->read() === $xml, 'Failure preserves valid XML');
        $GLOBALS['kasta_media_ok'] = true;
        $source->data['stocks'][0]['reserve'] = 10; $source->data['products'][0]['regular_price'] = '';
        $this->check($generator->run()['state'] === 'success' && str_contains($storage->read(), '<stock_quantity>0</stock_quantity>'), 'Sold-out retention');
        foreach (['duplicate_stock', 'missing_stock', 'fractional', 'missing_price', 'translated', 'category', 'stale', 'changed_sku'] as $case) {
            $data = $this->snapshot();
            switch ($case) {
                case 'duplicate_stock': $data['stocks'][] = $data['stocks'][0]; break;
                case 'missing_stock': $data['stocks'] = []; break;
                case 'fractional': $data['stocks'][0]['quantity'] = 2.5; break;
                case 'missing_price': $data['products'][0]['regular_price'] = ''; break;
                case 'translated': $data['products'][0]['language'] = 'ru'; break;
                case 'category': $data['products'][0]['categories'][] = ['id' => 466, 'name' => 'Маски', 'depth' => 1]; break;
                case 'stale': $data['generated_at_utc'] = gmdate('c', time() - 901); break;
                case 'changed_sku': $data['stocks'][0]['sku'] = $data['products'][0]['sku'] = 'OTHER'; break;
            }
            $this->fails(fn () => (new Catalog())->build($data, $xml), 'Expected catalog rejection: ' . $case);
        }
        $admin = new Admin($settings, $generator); $admin->menu();
        $this->check($GLOBALS['kasta_submenu'][3] === 'manage_woocommerce', 'WooCommerce capability');
        $_SERVER['REQUEST_METHOD'] = 'POST'; $GLOBALS['kasta_allowed'] = false;
        $before = $GLOBALS['kasta_options'];
        $this->fails(fn () => $admin->save(), 'Unauthorized settings');
        $this->fails(fn () => $admin->generate(), 'Unauthorized generation');
        $this->check($before === $GLOBALS['kasta_options'], 'Unauthorized request changed options');
        $GLOBALS['kasta_allowed'] = true; $GLOBALS['kasta_nonce_valid'] = false;
        $this->fails(fn () => $admin->save(), 'CSRF settings');
        $this->fails(fn () => $admin->generate(), 'CSRF generation');
        $GLOBALS['kasta_nonce_valid'] = true;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->fails(fn () => $admin->save(), 'GET must not save settings');
        $this->fails(fn () => $admin->generate(), 'GET must not queue generation');
        update_option(Settings::STATUS, ['state' => 'failed', 'error' => '<script>alert(1)</script>']);
        ob_start(); $admin->render(); $html = ob_get_clean();
        $this->check(! str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;'), 'Admin escaping');
        $this->check(str_contains($html, 'maruderm_kasta_generate') && str_contains($html, 'maruderm_kasta_save'), 'Settings/manual forms');
        if ($recordedSource !== null) {
            $data = json_decode(file_get_contents($recordedSource), true, 512, JSON_THROW_ON_ERROR);
            // Replay captured data as a fixture, without changing the source file or publishing XML.
            $data['generated_at_utc'] = $data['completed_at_utc'] = gmdate('c');
            $mapper = new Catalog(); $catalog = $mapper->build($data);
            $this->check(count($catalog['offers']) === 76 && $catalog['report']['matched_skus'] === 149, 'Recorded catalog coverage');
            $recorded = new DOMDocument(); $recorded->loadXML($mapper->xml($catalog)); $query = new DOMXPath($recorded);
            $products = array_column($data['products'], null, 'id'); $stocks = array_column($data['stocks'], null, 'sku');
            foreach ($catalog['offers'] as $id => $offer) {
                $product = $products[$id]; $stock = $stocks[$product['sku']]; $selector = '//offer[@id="' . $id . '"]';
                $this->check($query->evaluate('string(' . $selector . '/name)') === $product['name_ua'], 'Recorded Ukrainian name');
                $this->check($query->evaluate('string(' . $selector . '/article)') === $product['sku'], 'Recorded SKU');
                $this->check((float) $query->evaluate('string(' . $selector . '/price)') === (float) $product['regular_price'], 'Recorded regular price');
                $this->check((int) $query->evaluate('string(' . $selector . '/stock_quantity)') === max(0, $stock['quantity'] - $stock['reserve']), 'Recorded KeyCRM stock');
                $images = []; foreach ($query->query($selector . '/picture') as $node) { $images[] = $node->textContent; }
                $this->check($images === $product['images'], 'Recorded images');
            }
        }
        echo "PASS {$this->checks} Kasta plugin checks\n";
    }
}

try { (new KastaPluginTests())->run($temporary, $argv[1] ?? null); }
finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($temporary);
}
