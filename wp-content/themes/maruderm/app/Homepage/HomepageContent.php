<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

if (!defined('ABSPATH')) {
    exit();
}

final class HomepageContent
{
    public function slides(): array
    {
        return [
            [
                'heading' => 'Special Offer',
                'description' => 'MINI HELICOPTER<br>DRONE 4 CHANNELS<br>SALE <span>40% Off</span>',
                'button_text' => 'Shop Now',
                'url' => '#',
                'image_id' => 5371,
                'background_color' => '#ffffff',
                'background_size' => 'contain',
                'background_position' => '70% 70%',
                'accent_color' => '#f04704',
            ],
            [
                'heading' => 'Limited Edition',
                'description' => 'ILUV AUD MINI<br>ULTRA SLIM POCKET-SIZED<br>SPEAKER JUST <span>$599</span>',
                'button_text' => 'Shop Now',
                'url' => '#',
                'image_id' => 5361,
                'background_color' => '#9dbccf',
                'background_size' => 'contain',
                'background_position' => '75% 75%',
                'accent_color' => '#669900',
            ],
            [
                'heading' => 'Limited Edition',
                'description' => 'ILUV AUD MINI<br>ULTRA SLIM POCKET-SIZED<br>SPEAKER JUST $599',
                'button_text' => 'Shop Now',
                'url' => '#',
                'image_id' => 5381,
                'background_color' => '#1f2937',
                'background_size' => 'cover',
                'background_position' => 'center center',
                'accent_color' => '#ffffff',
                'light' => true,
            ],
        ];
    }

    public function benefits(): array
    {
        return [
            [
                'icon' => 'icon-rocket',
                'title' => 'Безкоштовна доставка',
                'description' => 'Для всіх замовлень понад 1000₴',
            ],
            [
                'icon' => 'icon-sync',
                'title' => 'Повернення протягом 90 днів',
                'description' => 'Якщо з товаром виникли проблеми',
            ],
            [
                'icon' => 'icon-credit-card',
                'title' => 'Безпечний платіж',
                'description' => '100% безпечний платіж',
            ],
            [
                'icon' => 'icon-bubbles',
                'title' => 'Цілодобова підтримка',
                'description' => 'Спеціалізована підтримка',
            ],
        ];
    }

    public function banners(): array
    {
        return [
            [
                'title' => 'Holder<br>& Charger',
                'button_text' => 'Shop Now',
                'url' => '#',
                'image_id' => 5720,
            ],
            [
                'title' => 'iPhone X 128GB<br>Retina Display',
                'button_text' => 'Shop Now',
                'url' => '#',
                'image_id' => 6083,
            ],
        ];
    }

    public function categories(): array
    {
        return [
            ['title' => 'Product name', 'image_id' => 5857, 'url' => '#'],
            ['title' => 'Product name', 'image_id' => 6016, 'url' => '#'],
            ['title' => 'Product name', 'image_id' => 6033, 'url' => '#'],
            ['title' => 'Product name', 'image_id' => 5853, 'url' => '#'],
            ['title' => 'Product name', 'image_id' => 5646, 'url' => '#'],
            ['title' => 'Product name', 'image_id' => 6101, 'url' => '#'],
        ];
    }

    public function deals(): array
    {
        return [
            'title' => 'Пропозиції дня',
            'title_size' => 'h3',
            'ends_in' => 'Закінчується в',
            'view_all_text' => 'Переглянути всі',
            'view_all_link' => ['url' => '#', 'is_external' => false, 'nofollow' => false],
            'product_type' => 'sale',
            'product_cats' => [],
            'product_tags' => [],
            'product_variations' => '',
            'per_page' => 12,
            'orderby' => '',
            'order' => '',
            'pagination' => 'no',
            'slidesToShow' => 5,
            'slidesToScroll' => 5,
            'slidesToShow_tablet' => 3,
            'slidesToScroll_tablet' => 3,
            'slidesToShow_mobile' => 2,
            'slidesToScroll_mobile' => 2,
            'infinite' => 'yes',
            'autoplay' => 'no',
            'autoplay_speed' => 5000,
        ];
    }

    public function productCarousels(): array
    {
        return [
            [
                'title' => 'Best Seller Laptops & Sounds',
                'product_cats' => ['laptops', 'sounds'],
                'links' => ['Laptop Apple', 'Laptop Asus', 'Marshall Speaker', 'JBL Speaker'],
            ],
            [
                'title' => 'Technology Toys Recommended For You',
                'product_cats' => ['technology-toys'],
                'links' => ['Micro', 'Drone/Flycam', 'Microphone', 'iQOS Holder'],
            ],
        ];
    }

    public function productList(): array
    {
        return [
            'title' => 'Good Price Accessories',
            'links' => ['HeadPhone', 'Case', 'USB - Hard Drive', 'TV Box'],
            'product_cats' => [],
        ];
    }
}
