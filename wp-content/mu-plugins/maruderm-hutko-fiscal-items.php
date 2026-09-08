<?php
/** Send a tax-inclusive, balanced basket to Hutko's documented fiscalization input. */
if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Hutko_Fiscal_Items
{
    public function register(): void
    {
        add_filter('wc_gateway_oplata_payment_params', [$this, 'paymentParams'], 20, 2);
    }

    public function paymentParams(array $params, WC_Order $order): array
    {
        $reservation = json_decode(base64_decode((string) ($params['reservation_data'] ?? ''), true) ?: '', true);
        if (! is_array($reservation)) {
            throw new RuntimeException('Не вдалося підготувати дані товарів для оплати.');
        }
        $rows = [];
        $names = [];
        foreach ($order->get_items('line_item') as $item) {
            $name = trim(wp_strip_all_tags(html_entity_decode($item->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $quantity = (float) $item->get_quantity();
            if ($name === '' || $quantity <= 0 || abs($quantity - round($quantity)) > 0.000001) {
                throw new RuntimeException('Некоректна назва або кількість товару для оплати.');
            }
            $gross = $this->cents((float) $item->get_total() + (float) $item->get_total_tax());
            if ($gross < 0) throw new RuntimeException('Некоректна сума товару для оплати.');
            $rows[] = ['name' => $name, 'quantity' => (int) $quantity, 'cents' => $gross];
            $names[] = $name;
        }
        if ($rows === []) throw new RuntimeException('У замовленні відсутні товари для оплати.');

        $discount = 0;
        foreach ($order->get_items('fee') as $item) {
            $gross = $this->cents((float) $item->get_total() + (float) $item->get_total_tax());
            if ($gross < 0) $discount -= $gross;
            elseif ($gross > 0) $rows[] = ['name' => trim(wp_strip_all_tags($item->get_name())), 'quantity' => 1, 'cents' => $gross];
        }
        // Negative fees are an order discount. Allocate their cents proportionally.
        $remaining = array_sum(array_column($rows, 'cents'));
        if ($discount > $remaining) throw new RuntimeException('Знижка перевищує суму товарів.');
        foreach ($rows as &$row) {
            $amount = $row['cents'];
            $share = $remaining > 0 ? (int) round($discount * $amount / $remaining) : 0;
            $row['cents'] -= $share;
            $discount -= $share;
            $remaining -= $amount;
        }
        unset($row);

        $shipping = $this->cents((float) $order->get_shipping_total() + (float) $order->get_shipping_tax());
        if ($shipping < 0) throw new RuntimeException('Некоректна вартість доставки.');
        if ($shipping > 0) $rows[] = ['name' => 'Доставка', 'quantity' => 1, 'cents' => $shipping];
        $expected = $this->cents((float) $order->get_total());
        $sum = array_sum(array_column($rows, 'cents'));
        if ($sum !== $expected || (int) ($params['amount'] ?? -1) !== $expected) {
            throw new RuntimeException('Сума товарів не відповідає сумі оплати. Оновіть замовлення.');
        }

        $products = [];
        foreach ($rows as $row) {
            // Split identical units only when a one-cent rounding difference requires it.
            $unit = intdiv($row['cents'], $row['quantity']);
            $higher = $row['cents'] % $row['quantity'];
            foreach ([[$row['quantity'] - $higher, $unit], [$higher, $unit + 1]] as [$quantity, $price]) {
                if ($quantity === 0) continue;
                if ($row['name'] === '' || mb_strlen($row['name']) > 1000) throw new RuntimeException('Некоректна назва позиції для оплати.');
                $products[] = ['id' => count($products) + 1, 'name' => $row['name'],
                    'price' => number_format($price / 100, 2, '.', ''),
                    'total_amount' => number_format($price * $quantity / 100, 2, '.', ''), 'quantity' => $quantity];
            }
        }
        $reservation['products'] = $products;
        $params['reservation_data'] = base64_encode(wp_json_encode($reservation));
        $params['order_desc'] = mb_substr(implode('; ', $names), 0, 1024);
        return $params;
    }

    private function cents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}

(new Maruderm_Hutko_Fiscal_Items())->register();
