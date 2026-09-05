<?php
/**
 * Email-client-safe rendering of the canonical MARUDERM internal order template.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Order_Email_Renderer
{
    public function render(array $order): string
    {
        $items = '';

        foreach ((array) ($order['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $image = trim((string) ($item['image'] ?? ''));
            $image_cell = $image !== ''
                ? '<td width="76" style="padding:18px 18px 18px 0;border-bottom:1px solid #ece9ee;vertical-align:middle"><img src="' . esc_url($image) . '" alt="" width="76" height="76" style="display:block;width:76px;height:76px;border-radius:14px;background:#faf9f7;object-fit:contain"></td>'
                : '<td width="76" style="padding:18px 18px 18px 0;border-bottom:1px solid #ece9ee;vertical-align:middle"><div style="width:76px;height:76px;border-radius:14px;background:#faf9f7"></div></td>';

            $items .= '<tr>' . $image_cell
                . '<td style="padding:18px 12px 18px 0;border-bottom:1px solid #ece9ee;vertical-align:middle;font-size:13px;line-height:1.45;color:#202023"><strong>'
                . esc_html((string) ($item['name'] ?? 'Товар'))
                . '</strong><br><span style="color:#817b87;font-size:11px">Кількість: '
                . esc_html((string) ($item['quantity'] ?? 1))
                . '</span></td><td style="padding:18px 0;border-bottom:1px solid #ece9ee;vertical-align:middle;text-align:right;white-space:nowrap;font-size:13px;font-weight:600;color:#202023">'
                . esc_html((string) ($item['price'] ?? '')) . '</td></tr>';
        }

        $order_url = esc_url((string) ($order['order_url'] ?? admin_url('admin.php?page=wc-orders')));

        return '<!doctype html><html lang="uk"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:24px 10px;background:#f3f1f6;color:#202023;font-family:Arial,sans-serif">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td align="center">'
            . '<table role="presentation" width="720" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:720px;border:1px solid #e8e4eb;border-radius:24px;background:#ffffff;overflow:hidden">'
            . '<tr><td style="padding:26px 38px;border-bottom:1px solid #ece9ee"><span style="font-size:25px;font-weight:500;letter-spacing:-1px">maru<span style="color:#7547e8">·</span>derm</span><span style="float:right;padding-top:8px;color:#77717e;font-size:11px">Турбота у твоєму ритмі</span></td></tr>'
            . '<tr><td style="padding:42px 38px;background:#e8ddff"><table role="presentation" width="100%"><tr><td width="96" valign="top"><div style="width:76px;height:76px;border:1px solid #cfc2e8;border-radius:24px;background:#f7f2ff;text-align:center;line-height:76px;font-size:28px">+</div></td><td valign="top"><div style="font-size:10px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase">Нове замовлення</div><h1 style="margin:10px 0 14px;font-size:34px;line-height:1.05;font-weight:600;letter-spacing:-1.5px">Нове замовлення потребує уваги.</h1><p style="margin:0;color:#625d69;font-size:14px;line-height:1.6">Надійшло нове замовлення. Дані покупця, склад і спосіб доставки зібрані нижче.</p></td></tr></table></td></tr>'
            . $this->summary_row($order)
            . '<tr><td style="padding:34px 38px"><table role="presentation" width="100%"><tr><td style="padding-bottom:12px;font-size:22px;font-weight:600">У замовленні</td><td style="padding-bottom:12px;text-align:right;color:#817b87;font-size:11px">' . count((array) ($order['items'] ?? [])) . ' позицій</td></tr>' . $items . '</table>'
            . $this->totals((string) ($order['subtotal'] ?? ''), (string) ($order['shipping_total'] ?? ''), (string) ($order['total'] ?? '')) . '</td></tr>'
            . '<tr><td style="padding:0 38px 34px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td width="50%" valign="top" style="padding:20px;background:#f7f5f8;border-radius:16px"><span style="color:#817b87;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Покупець</span><strong style="display:block;margin:11px 0 7px;font-size:13px">' . esc_html((string) ($order['customer_name'] ?? 'Не вказано')) . '</strong><span style="color:#77717e;font-size:11px;line-height:1.5">' . esc_html($this->contact($order)) . '</span></td><td width="12"></td><td width="50%" valign="top" style="padding:20px;background:#f7f5f8;border-radius:16px"><span style="color:#817b87;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase">Доставка й оплата</span><strong style="display:block;margin:11px 0 7px;font-size:13px">' . esc_html((string) ($order['delivery'] ?? 'Не вказано')) . '</strong><span style="color:#77717e;font-size:11px;line-height:1.5">' . esc_html((string) ($order['payment'] ?? 'Не вказано')) . '</span></td></tr></table></td></tr>'
            . '<tr><td align="center" style="padding:28px 38px;background:#f0ebfa"><p style="margin:0 0 18px;color:#625d69;font-size:13px">Перевір наявність товарів і підтвердь замовлення покупцю.</p><a href="' . $order_url . '" style="display:inline-block;padding:14px 24px;border-radius:999px;color:#ffffff;background:#7547e8;font-size:12px;font-weight:600;text-decoration:none">Відкрити замовлення</a></td></tr>'
            . '<tr><td align="center" style="padding:30px 38px;color:#716b77;background:#faf9fb;font-size:11px;line-height:1.6"><strong style="color:#202023">Внутрішнє повідомлення MARUDERM</strong><br>Цей лист надіслано автоматично. Не пересилай його покупцю.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function summary_row(array $order): string
    {
        $cells = [
            ['Замовлення', (string) ($order['order_number'] ?? '')],
            ['Дата', (string) ($order['date'] ?? '')],
            ['Джерело', (string) ($order['source'] ?? '')],
        ];
        $html = '<tr><td><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>';

        foreach ($cells as [$label, $value]) {
            $html .= '<td width="33.33%" style="padding:20px 28px;border-right:1px solid #ece9ee"><span style="display:block;color:#817b87;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase">' . esc_html($label) . '</span><strong style="display:block;margin-top:7px;font-size:13px">' . esc_html($value) . '</strong></td>';
        }

        return $html . '</tr></table></td></tr>';
    }

    private function totals(string $subtotal, string $shipping, string $total): string
    {
        return '<table role="presentation" width="310" align="right" style="margin-top:22px;font-size:12px;color:#6e6874"><tr><td style="padding:5px 0">Товари</td><td align="right">' . esc_html($subtotal) . '</td></tr><tr><td style="padding:5px 0">Доставка</td><td align="right">' . esc_html($shipping) . '</td></tr><tr><td style="padding:13px 0 5px;border-top:1px solid #dcd8df;color:#202023;font-size:16px;font-weight:700">Разом</td><td align="right" style="padding:13px 0 5px;border-top:1px solid #dcd8df;color:#202023;font-size:16px;font-weight:700">' . esc_html($total) . '</td></tr></table>';
    }

    private function contact(array $order): string
    {
        return implode(' · ', array_filter([
            trim((string) ($order['customer_phone'] ?? '')),
            trim((string) ($order['customer_email'] ?? '')),
        ])) ?: 'Контакти не вказані';
    }
}
