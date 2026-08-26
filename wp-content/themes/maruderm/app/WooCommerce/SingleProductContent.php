<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Catalog\CatalogRepository;

if (!defined('ABSPATH')) {
    exit();
}

/** Resolves product-specific presentation copy from live WooCommerce content. */
final class SingleProductContent
{
    private CatalogRepository $repository;

    public function __construct(?CatalogRepository $repository = null)
    {
        $this->repository = $repository ?? new CatalogRepository();
    }

    public function category(\WC_Product $product): ?\WP_Term
    {
        $categories = $this->repository->topCategories($product);

        return $categories[0] ?? null;
    }

    public function lead(\WC_Product $product): string
    {
        $lead = trim(wp_strip_all_tags($product->get_short_description()));

        return $lead !== '' ? $lead : trim(wp_strip_all_tags($product->get_description()));
    }

    public function fullIngredients(\WC_Product $product): string
    {
        $ingredients = trim(wp_strip_all_tags((string) $product->get_meta(ProductIngredients::META_KEY, true)));

        if ($ingredients !== '') {
            return $ingredients;
        }

        return $this->productDetail(
            $product,
            ['pa_inci', 'inci', 'повний склад', 'склад'],
            ['_maruderm_inci', 'maruderm_inci', '_product_inci', 'product_inci', 'inci']
        ) ?? 'Актуальний склад зазначено на пакованні продукту.';
    }

    public function netWeight(\WC_Product $product): string
    {
        $weight = $product->get_weight();

        if ($weight === '') {
            return 'Не вказано';
        }

        $grams = wc_get_weight((float) $weight, 'g', get_option('woocommerce_weight_unit', 'kg'));

        return $this->formatMeasurement($grams) . ' г';
    }

    public function boxDimensions(\WC_Product $product): string
    {
        $dimensions = [$product->get_length(), $product->get_width(), $product->get_height()];

        if (in_array('', $dimensions, true)) {
            return 'Не вказано';
        }

        $unit = get_option('woocommerce_dimension_unit', 'cm');
        $centimeters = array_map(
            fn (string $dimension): string => $this->formatMeasurement(wc_get_dimension((float) $dimension, 'cm', $unit)),
            $dimensions
        );

        return implode(' × ', $centimeters) . ' см';
    }

    public function origin(\WC_Product $product): string
    {
        return $this->productDetail(
            $product,
            ['pa_country_of_origin', 'country_of_origin', 'країна виробництва'],
            ['_maruderm_origin', 'maruderm_origin', '_country_of_origin', 'country_of_origin']
        ) ?? 'Не вказано';
    }

    public function shelfLife(\WC_Product $product): string
    {
        return $this->productDetail(
            $product,
            ['pa_shelf_life', 'shelf_life', 'термін придатності'],
            ['_maruderm_shelf_life', 'maruderm_shelf_life', '_shelf_life', 'shelf_life']
        ) ?? 'Зазначено на пакованні';
    }

    /** @return string[] */
    public function highlights(\WC_Product $product): array
    {
        $slug = $this->category($product)?->slug ?? '';

        if (str_contains($slug, 'voloss')) {
            return ['Для домашнього ритуалу', 'Догляд за волоссям', 'Зручний формат'];
        }

        if (str_contains($slug, 'tila')) {
            return ['Щоденний комфорт', 'Догляд за тілом', 'Продумана формула'];
        }

        return ['Для щоденного ритуалу', 'Делікатний догляд', 'Продумана формула'];
    }

    /** @return array<int, array{title: string, text: string}> */
    public function benefits(\WC_Product $product): array
    {
        $slug = $this->category($product)?->slug ?? '';

        if (str_contains($slug, 'voloss')) {
            return [
                ['title' => 'Сильніше волосся', 'text' => 'Формула підтримує волосся та допомагає зменшити його випадіння.'],
                ['title' => 'Доглянута шкіра голови', 'text' => 'Активні компоненти доповнюють щоденний догляд за коренями волосся.'],
                ['title' => 'Легкий ритуал', 'text' => 'Зручний формат легко додати до регулярної рутини без змивання.'],
            ];
        }

        return [
            ['title' => 'Делікатна дія', 'text' => 'Збалансована формула працює м’яко та комфортно.'],
            ['title' => 'Щоденний догляд', 'text' => 'Засіб легко поєднується з іншими кроками твоєї рутини.'],
            ['title' => 'Відчутний результат', 'text' => 'Регулярне використання допомагає підтримувати доглянутий вигляд.'],
        ];
    }

    /** @return array<int, array{title: string, text: string}> */
    public function ingredients(\WC_Product $product): array
    {
        $haystack = mb_strtolower($this->lead($product) . ' ' . wp_strip_all_tags($product->get_description()));
        $dictionary = [
            'кофеїн' => ['Кофеїн', 'Підтримує тонус шкіри голови та догляд за коренями волосся.'],
            'біотин' => ['Біотин', 'Допомагає підтримувати міцність і доглянутий вигляд волосся.'],
            'амінокислот' => ['Комплекс амінокислот', 'Підтримує структуру волосся та доповнює відновлювальний догляд.'],
            'хвощ' => ['Екстракт хвоща', 'Рослинний компонент для зміцнювальної рутини волосся.'],
            'гіалурон' => ['Гіалуронова кислота', 'Допомагає утримувати вологу та підтримувати комфорт.'],
            'ніацинамід' => ['Ніацинамід', 'Підтримує рівний вигляд і захисний бар’єр шкіри.'],
            'саліцил' => ['Саліцилова кислота', 'Допомагає очищенню пор і контролю надлишку себуму.'],
            'вітамін c' => ['Вітамін C', 'Підтримує сяйво та рівний тон шкіри.'],
        ];
        $ingredients = [];

        foreach ($dictionary as $needle => [$title, $text]) {
            if (str_contains($haystack, $needle)) {
                $ingredients[] = ['title' => $title, 'text' => $text];
            }
        }

        $fallback = [
            ['title' => 'Активний комплекс', 'text' => 'Ключові компоненти формули працюють у збалансованому поєднанні.'],
            ['title' => 'Доглядова основа', 'text' => 'Допомагає підтримувати комфорт під час регулярного використання.'],
            ['title' => 'Продумана формула', 'text' => 'Створена для простого та послідовного домашнього ритуалу.'],
        ];

        return array_slice(array_merge($ingredients, $fallback), 0, 3);
    }

    public function formula(\WC_Product $product): string
    {
        $text = mb_strtolower($this->lead($product));

        if (str_contains($text, 'кофеїн') && str_contains($text, 'біотин')) {
            return 'КОФЕЇН<br>+ БІОТИН';
        }

        return 'АКТИВНИЙ<br>КОМПЛЕКС';
    }

    /** @return array<int, array{title: string, text: string}> */
    public function routine(\WC_Product $product): array
    {
        $slug = $this->category($product)?->slug ?? '';

        if (str_contains($slug, 'voloss')) {
            return [
                ['title' => 'Нанеси', 'text' => 'Розподіли засіб безпосередньо на чисту шкіру голови.'],
                ['title' => 'Розподіли', 'text' => 'Пройдися по проділах, приділяючи увагу кореням волосся.'],
                ['title' => 'Масажуй', 'text' => 'Легко помасажуй шкіру голови кінчиками пальців.'],
                ['title' => 'Залиш', 'text' => 'Не змивай та продовжуй звичне укладання волосся.'],
            ];
        }

        return [
            ['title' => 'Підготуй', 'text' => 'Очисти потрібну ділянку перед нанесенням засобу.'],
            ['title' => 'Нанеси', 'text' => 'Використай невелику кількість і рівномірно розподіли.'],
            ['title' => 'Дай подіяти', 'text' => 'Дотримуйся способу використання, зазначеного на пакованні.'],
            ['title' => 'Продовжуй', 'text' => 'Заверши ритуал наступними кроками свого догляду.'],
        ];
    }

    /** @return \WC_Product[] */
    public function related(\WC_Product $product, int $limit = 4): array
    {
        $category = $this->category($product);
        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$product->get_id()],
            'orderby' => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
            'meta_key' => 'total_sales',
            'tax_query' => $category instanceof \WP_Term ? [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => [$category->term_id],
                'include_children' => true,
            ]] : [],
            'meta_query' => [[
                'key' => '_stock_status',
                'value' => 'instock',
            ]],
        ]);
        $products = array_map('wc_get_product', wp_list_pluck($query->posts, 'ID'));

        return array_values(array_filter($products, static fn ($item): bool => $item instanceof \WC_Product && $item->is_visible()));
    }

    /** @return int[] */
    public function imageIds(\WC_Product $product): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$product->get_image_id()],
            $product->get_gallery_image_ids()
        ))));
    }

    /** @param string[] $attributeNames @param string[] $metaKeys */
    private function productDetail(\WC_Product $product, array $attributeNames, array $metaKeys): ?string
    {
        foreach ($attributeNames as $attributeName) {
            $value = trim(wp_strip_all_tags($product->get_attribute($attributeName)));

            if ($value !== '') {
                return $value;
            }
        }

        foreach ($metaKeys as $metaKey) {
            $value = trim(wp_strip_all_tags((string) $product->get_meta($metaKey, true)));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function formatMeasurement(float $value): string
    {
        $decimals = abs($value - round($value)) < 0.001 ? 0 : 1;

        return number_format_i18n($value, $decimals);
    }
}
