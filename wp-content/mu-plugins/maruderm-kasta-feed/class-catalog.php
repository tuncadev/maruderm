<?php
namespace Maruderm\Kasta;
if (! defined('ABSPATH')) { exit; }

final class Catalog
{
    public static function text($value): string
    {
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $value);
        $value = preg_replace('#</?(p|div|li|h[1-6])\b[^>]*>|<br\s*/?>#i', "\n", $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('#(?:https?://|www\.)\S+#iu', '', $value);
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
        if ($value === null) { throw new \RuntimeException('Invalid UTF-8 content.'); }
        $lines = array_map(static fn ($line) => trim(preg_replace('/[\h]+/u', ' ', $line)), explode("\n", $value));
        return implode("\n", array_filter($lines, static fn ($line) => $line !== ''));
    }

    private function integer($value): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > 2147483647
            || floor((float) $value) !== (float) $value) {
            throw new \RuntimeException('KeyCRM stock, reserve and IDs must be nonnegative integers.');
        }
        return (int) $value;
    }

    private function price($value, string $sku): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $value) || (float) $value <= 0 || (float) $value > 100000000) {
            throw new \RuntimeException("Invalid website regular price: {$sku}.");
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function previous(string $xml): array
    {
        if ($xml === '') { return []; }
        if (str_contains($xml, '<!DOCTYPE') || str_contains($xml, '<!ENTITY')) {
            throw new \RuntimeException('Unsupported previous XML declarations.');
        }
        $doc = new \DOMDocument();
        if (! $doc->loadXML($xml, LIBXML_NONET) || $doc->documentElement->tagName !== 'yml_catalog') {
            throw new \RuntimeException('Invalid previous XML.');
        }
        $previous = [];
        foreach ((new \DOMXPath($doc))->query('/yml_catalog/shop/offers/offer') as $offer) {
            $id = $offer->getAttribute('id');
            if ($id === '' || isset($previous[$id])) { throw new \RuntimeException('Duplicate previous ID.'); }
            $previous[$id] = ['sku' => $offer->getElementsByTagName('article')->item(0)?->textContent,
                'price' => $offer->getElementsByTagName('price')->item(0)?->textContent];
        }
        return $previous;
    }

    public function build(array $source, string $previousXml = ''): array
    {
        $started = strtotime($source['generated_at_utc'] ?? '');
        $completed = strtotime($source['completed_at_utc'] ?? '');
        if (($source['schema_version'] ?? 0) !== 1 || ($source['currency'] ?? '') !== 'UAH'
            || ($source['stock_scope'] ?? '') !== 'keycrm_account_total' || ! $started || ! $completed
            || time() - $started > 900 || $started > $completed || $completed > time()) {
            throw new \RuntimeException('Invalid or stale source snapshot.');
        }
        $stocks = $stockIds = [];
        foreach ($source['stocks'] as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $id = $this->integer($row['id'] ?? null);
            if ($sku === '' || isset($stocks[$sku]) || isset($stockIds[$id]) || $id === 0) {
                throw new \RuntimeException("Duplicate or missing KeyCRM SKU/offer: {$sku}.");
            }
            $quantity = $this->integer($row['quantity'] ?? null);
            $reserve = $this->integer($row['reserve'] ?? null);
            $stocks[$sku] = ['quantity' => $quantity, 'reserve' => $reserve, 'available' => max(0, $quantity - $reserve), 'offer_id' => $id];
            $stockIds[$id] = true;
        }
        $previous = $this->previous($previousXml);
        $overrides = json_decode(file_get_contents(__DIR__ . '/category-overrides.json'), true, 512, JSON_THROW_ON_ERROR);
        $offers = $categories = $seenSku = $seenId = [];
        $report = ['excluded' => [], 'warnings' => [], 'offers' => [], 'source_generated_at_utc' => $source['generated_at_utc']];
        foreach ($source['products'] as $product) {
            $id = (string) ($product['id'] ?? '');
            $sku = trim((string) ($product['sku'] ?? ''));
            if (! preg_match('/^[1-9][0-9]*$/D', $id) || $sku === '' || isset($seenId[$id]) || isset($seenSku[$sku])
                || ($product['language'] ?? '') !== 'uk' || ($product['type'] ?? '') !== 'simple') {
                throw new \RuntimeException("Invalid, translated, duplicate or unsupported product: {$sku}.");
            }
            $seenId[$id] = $seenSku[$sku] = true;
            if (! isset($stocks[$sku])) { throw new \RuntimeException("Missing KeyCRM SKU match: {$sku}."); }
            if (isset($previous[$id]) && $previous[$id]['sku'] !== $sku) { throw new \RuntimeException("SKU changed for offer {$id}."); }
            $stock = $stocks[$sku];
            $price = $product['regular_price'] ?? '';
            if ($price === '' || $price === null) {
                if ($stock['available'] > 0) { throw new \RuntimeException("Missing regular price with available stock: {$sku}."); }
                if (isset($previous[$id])) {
                    $price = $previous[$id]['price'];
                    $report['warnings'][] = "{$sku}: retained prior regular price for sold-out item";
                } else {
                    $report['excluded'][] = ['sku' => $sku, 'reason' => 'no_regular_price_and_zero_stock'];
                    continue;
                }
            }
            $price = $this->price($price, $sku);
            $assigned = $product['categories'];
            if ($assigned === []) { throw new \RuntimeException("Missing category: {$sku}."); }
            $depth = max(array_column($assigned, 'depth'));
            $selected = array_values(array_filter($assigned, static fn ($term) => isset($overrides[$sku])
                ? $term['id'] === $overrides[$sku]['category_id'] : $term['depth'] === $depth));
            if (count($selected) !== 1) { throw new \RuntimeException("Ambiguous category: {$sku}."); }
            $names = $descriptions = [];
            foreach (['ua', 'ru'] as $language) {
                $names[$language] = self::text($product['name_' . $language] ?? '');
                $description = self::text($product['description_' . $language] ?? '');
                if ($names[$language] === '' || $description === '') { throw new \RuntimeException("Missing {$language} content: {$sku}."); }
                if (mb_strlen($description) > 5000) { $report['warnings'][] = "{$sku}: {$language} description shortened to 5000 characters"; }
                $descriptions[$language] = mb_substr($description, 0, 5000);
            }
            if (preg_match('/[ЁёЪъЫыЭэ]/u', $names['ua']) || ! preg_match('/[А-Яа-яІіЇїЄєҐґ]/u', $names['ua'])) {
                throw new \RuntimeException("Primary name must be Ukrainian: {$sku}.");
            }
            $images = array_values(array_unique($product['images'] ?? []));
            if (count($images) < 1 || count($images) > 20) { throw new \RuntimeException("Expected 1–20 images: {$sku}."); }
            $category = $selected[0];
            $categories[$category['id']] = self::text($category['name']);
            $params = $product['params'] ?? [];
            if (isset($product['weight_kg']) && (float) $product['weight_kg'] > 0) {
                $params['Вага в упаковці, кг'] = (string) $product['weight_kg'];
            }
            $dimensions = $product['dimensions_cm'] ?? [];
            if (count($dimensions) === 3 && count(array_filter($dimensions, static fn ($value) => is_numeric($value) && (float) $value > 0)) === 3) {
                $params['Габарити в упаковці, см'] = implode('x', $dimensions);
            }
            $offers[$id] = compact('id', 'sku', 'stock', 'price', 'names', 'descriptions', 'images', 'category', 'params');
            $report['offers'][] = ['id' => $id, 'sku' => $sku, 'price' => $price, 'stock' => $stock, 'images' => count($images)];
        }
        if ($offers === [] || array_diff_key($previous, $offers) !== []) {
            throw new \RuntimeException('Empty feed or previously exported offers disappeared; reconcile before replacing XML.');
        }
        $report += ['source_products' => count($source['products']), 'matched_skus' => count($seenSku),
            'exported' => count($offers), 'stock_units' => array_sum(array_column(array_column($offers, 'stock'), 'available')),
            'categories' => count($categories)];
        return ['offers' => $offers, 'categories' => $categories, 'report' => $report];
    }

    public function xml(array $catalog): string
    {
        $writer = new \XMLWriter();
        $writer->openMemory(); $writer->setIndent(true); $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('yml_catalog'); $writer->writeAttribute('date', gmdate('Y-m-d H:i'));
        $writer->startElement('shop');
        $writer->writeElement('name', 'Maruderm'); $writer->writeElement('url', 'https://www.maruderm.com.ua/');
        $writer->startElement('currencies'); $writer->startElement('currency');
        $writer->writeAttribute('id', 'UAH'); $writer->writeAttribute('rate', '1'); $writer->endElement(); $writer->endElement();
        $writer->startElement('categories');
        foreach ($catalog['categories'] as $id => $name) {
            $writer->startElement('category'); $writer->writeAttribute('id', (string) $id); $writer->text($name); $writer->endElement();
        }
        $writer->endElement(); $writer->startElement('offers');
        foreach ($catalog['offers'] as $offer) {
            $writer->startElement('offer'); $writer->writeAttribute('id', $offer['id']);
            $writer->writeAttribute('available', $offer['stock']['available'] > 0 ? 'true' : 'false');
            $fields = ['currencyId' => 'UAH', 'categoryId' => $offer['category']['id'], 'article' => $offer['sku'],
                'vendor' => 'Maruderm', 'price' => $offer['price'], 'price_old' => $offer['price'],
                'stock_quantity' => $offer['stock']['available'], 'name' => $offer['names']['ua'], 'name_ua' => $offer['names']['ua'],
                'name_ru' => $offer['names']['ru'], 'description_ua' => $offer['descriptions']['ua'], 'description' => $offer['descriptions']['ru']];
            foreach ($fields as $name => $value) { $writer->writeElement($name, (string) $value); }
            foreach ($offer['images'] as $image) { $writer->writeElement('picture', $image); }
            foreach ($offer['params'] as $name => $value) {
                if (self::text($name) === '' || self::text($value) === '') { continue; }
                $writer->startElement('param'); $writer->writeAttribute('name', self::text($name)); $writer->text(self::text($value)); $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement(); $writer->endElement(); $writer->endElement(); $writer->endDocument();
        $xml = $writer->outputMemory();
        $parsed = $this->previous($xml);
        if (count($parsed) !== count($catalog['offers'])) { throw new \RuntimeException('XML readback count mismatch.'); }
        foreach ($catalog['offers'] as $id => $offer) {
            if ($parsed[$id] !== ['sku' => $offer['sku'], 'price' => $offer['price']]) { throw new \RuntimeException('XML identity/price readback mismatch.'); }
        }
        return $xml;
    }
}
