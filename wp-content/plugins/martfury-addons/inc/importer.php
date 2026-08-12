<?php
/**
 * Hooks for importer
 *
 * @package Martfury
 */


/**
 * Importer the demo content
 *
 * @since  1.0
 *
 */
function martfury_vc_addons_importer() {
	return array(
		array(
			'name'       => 'WPBakery - Auto Parts',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Full Width',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home Full Width',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Technology',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Organic',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V1',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V1<br> Without AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home Without AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V2',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V2<br> Without AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home Without AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V3',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V3<br> Without AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home Without AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Marketplace V4',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'HomePage 4',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Electronic',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Electronic<br> Without AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home Without AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'WPBakery - Furniture',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/wpbakery/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/widgets.wie',
			'sliders'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/sliders.zip',
			'tab'        => '0',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),

		// Elementor
		array(
			'name'       => 'Elementor - Medical',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/medical/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/medical/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/medical/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/medical/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Kids',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Kids With AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/kids/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home With Ajax',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Auto Parts',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/auto_parts/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Full Width',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/full_width/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home Full Width',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Technology',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/technology/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Organic',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/organic/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V1',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V1 With AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_1/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home With AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V2',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V2 With AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_2/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home With AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V3',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(

					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V3 With AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_3/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home With AJAX',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(

					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Marketplace V4',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/market_4/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage 4',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Electronic',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Electronic With AJAX',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/electronic/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home With Ajax',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Furniture',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/elementor/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/furniture/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'Home',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
		array(
			'name'       => 'Elementor - Christmas',
			'preview'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/christmas/preview.jpg',
			'content'    => 'https://drfuri-demo-images.s3.us-west-1.amazonaws.com/martfury/christmas/demo-content.xml',
			'customizer' => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/christmas/customizer.dat',
			'widgets'    => 'https://drfuri-demo-images.s3-us-west-1.amazonaws.com/martfury/christmas/widgets.wie',
			'tab'        => '1',
			'pages'      => array(
				'front_page' => 'HomePage',
				'blog'       => 'Our Press',
				'shop'       => 'Shop',
				'cart'       => 'shop-cart',
				'checkout'   => 'shop-checkout',
				'my_account' => 'My Account',
			),
			'menus'      => array(
				'primary'         => 'primary-menu',
				'shop_department' => 'shop-by-department',
			),
			'options'    => array(
				'shop_catalog_image_size'   => array(
					'width'  => 480,
					'height' => 480,
					'crop'   => 1,
				),
				'shop_single_image_size'    => array(
					'width'  => 600,
					'height' => 600,
					'crop'   => 1,
				),
				'shop_thumbnail_image_size' => array(
					'width'  => 70,
					'height' => 70,
					'crop'   => 1,
				),
			),
		),
	);
}

add_filter( 'soo_demo_packages', 'martfury_vc_addons_importer', 20 );


/**
 * Prepare product attributes before import demo content
 *
 * @param $file
 */
function martfury_addons_import_product_attributes( $file ) {
	global $wpdb;

	if ( ! class_exists( 'WXR_Parser' ) ) {
		require_once WP_PLUGIN_DIR . '/soo-demo-importer/includes/parsers.php';
	}

	$parser      = new WXR_Parser();
	$import_data = $parser->parse( $file );

	if ( isset( $import_data['posts'] ) ) {
		$posts = $import_data['posts'];

		if ( $posts && sizeof( $posts ) > 0 ) {
			foreach ( $posts as $post ) {
				if ( 'product' === $post['post_type'] ) {
					if ( ! empty( $post['terms'] ) ) {
						foreach ( $post['terms'] as $term ) {
							if ( strstr( $term['domain'], 'pa_' ) ) {
								if ( ! taxonomy_exists( $term['domain'] ) ) {
									$attribute_name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $term['domain'] ) );

									// Create the taxonomy
									if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies() ) ) {
										$attribute = array(
											'attribute_label'   => $attribute_name,
											'attribute_name'    => $attribute_name,
											'attribute_type'    => 'select',
											'attribute_orderby' => 'menu_order',
											'attribute_public'  => 0,
										);
										$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
										delete_transient( 'wc_attribute_taxonomies' );
									}

									// Register the taxonomy now so that the import works!
									register_taxonomy(
										$term['domain'],
										apply_filters( 'woocommerce_taxonomy_objects_' . $term['domain'], array( 'product' ) ),
										apply_filters(
											'woocommerce_taxonomy_args_' . $term['domain'], array(
												'hierarchical' => true,
												'show_ui'      => false,
												'query_var'    => true,
												'rewrite'      => false,
											)
										)
									);
								}
							}
						}
					}
				}
			}
		}
	}
}

add_action( 'soodi_before_import_content', 'martfury_addons_import_product_attributes' );