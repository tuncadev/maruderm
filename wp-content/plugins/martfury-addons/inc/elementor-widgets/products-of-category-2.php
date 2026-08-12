<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use MartfuryAddons\Elementor;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use MartfuryAddons\Elementor_AjaxLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Products_Of_Category_2 extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-products-of-category-2';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Products of Category 2', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'martfury' ];
	}

	public function get_script_depends() {
		return [
			'martfury-elementor'
		];
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_controls() {

		// Content Tab

		$this->register_heading_controls();
		$this->register_banners_controls();
		$this->register_product_tabs_controls();
		$this->register_side_products_controls();
		$this->register_lazy_loading_controls();

		// Style Tab

		$this->register_heading_style_controls();
		$this->register_banners_style_controls();
		$this->register_product_tabs_style_controls();
		$this->register_side_products_style_controls();
		$this->register_lazy_loading_style_controls();

	}

	/**
	 * Register the widget heading controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_heading_controls() {

		$this->start_controls_section(
			'section_heading',
			[ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ]
		);

		$this->start_controls_tabs( 'heading_content_settings' );

		$this->start_controls_tab( 'title_tab', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_control(
			'icon_type',
			[
				'label'   => esc_html__( 'Icon Type', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'icons'        => esc_html__( 'Old Icons', 'martfury-addons' ),
					'custom_icons' => esc_html__( 'New Icons', 'martfury-addons' ),
				],
				'default' => 'icons',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'icon',
			[
				'label'     => esc_html__( 'Icon', 'martfury-addons' ),
				'type'      => Controls_Manager::ICON,
				'default'   => 'icon-shirt',
				'condition' => [
					'icon_type' => 'icons',
				],
			]
		);

		$this->add_control(
			'custom_icon',
			[
				'label'     => esc_html__( 'Icon', 'martfury-addons' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
				'condition' => [
					'icon_type' => 'custom_icons',
				],
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category Name', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter the title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'title_size',
			[
				'label'   => __( 'HTML Tag', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'default' => 'h2',
			]
		);


		$this->add_control(
			'link', [
				'label'         => esc_html__( 'Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
				'default'       => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'quick_links_tab', [ 'label' => esc_html__( 'Quick Links', 'martfury-addons' ) ] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'link_text', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category Name', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'link_url', [
				'label'         => esc_html__( 'Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
				'default'       => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);

		$this->add_control(
			'links_group',
			[
				'label'         => esc_html__( 'Links', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'link_text' => esc_html__( 'Sub Category 1', 'martfury-addons' ),
						'link_url'  => [
							'url' => '#',
						]
					],
					[
						'link_text' => esc_html__( 'Sub Category 2', 'martfury-addons' ),
						'link_url'  => [
							'url' => '#',
						]
					],
					[
						'link_text' => esc_html__( 'Sub Category 3', 'martfury-addons' ),
						'link_url'  => [
							'url' => '#',
						]
					],
					[
						'link_text' => esc_html__( 'Sub Category 4', 'martfury-addons' ),
						'link_url'  => [
							'url' => '#',
						]
					]
				],
				'title_field'   => '{{{ link_text }}}',
				'prevent_empty' => false
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget banners controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_banners_controls() {

		$this->start_controls_section(
			'section_banners_content',
			[
				'label' => esc_html__( 'Banners Carousel', 'martfury-addons' ),
			]
		);

		$this->start_controls_tabs( 'banners_content_settings' );

		$this->start_controls_tab( 'banners_tab', [ 'label' => esc_html__( 'Banners', 'martfury-addons' ) ] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'image',
			[
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => 'https://via.placeholder.com/829x241/e7eff1?text=829x241+Banner'
				],
			]
		);

		$repeater->add_control(
			'image_link', [
				'label'         => esc_html__( 'Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
				'default'       => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);

		$this->add_control(
			'banners',
			[
				'label'         => esc_html__( 'Banners', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'image'      => [
							'url' => 'https://via.placeholder.com/829x241/e7eff1?text=829x241+Banner'
						],
						'image_link' => [
							'url' => '#'
						]
					],
					[
						'image'      => [
							'url' => 'https://via.placeholder.com/829x241/e7eff1?text=829x241+Banner+2'
						],
						'image_link' => [
							'url' => '#'
						]
					]
				],
				'prevent_empty' => false
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'banners_carousel_tab', [ 'label' => esc_html__( 'Carousel', 'martfury-addons' ) ] );

		$this->add_control(
			'banners_autoplay',
			[
				'label'   => esc_html__( 'Autoplay', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
			]
		);

		$this->add_control(
			'banners_autoplay_speed',
			[
				'label'   => esc_html__( 'Autoplay Speed', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5000,
			]
		);

		$this->add_control(
			'banners_speed',
			[
				'label'   => esc_html__( 'Speed', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 800,
			]
		);

		$this->add_control(
			'banners_infinite',
			[
				'label'   => esc_html__( 'Infinite Loop', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'yes',
				'options' => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
			]
		);

		$this->add_control(
			'banners_arrows',
			[
				'label'     => esc_html__( 'Arrows', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => 'yes',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget product tabs controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_product_tabs_controls() {

		$this->start_controls_section(
			'section_product_content',
			[
				'label' => esc_html__( 'Product Tabs', 'martfury-addons' ),
			]
		);

		$this->start_controls_tabs( 'products_content_settings' );

		$this->start_controls_tab( 'products_tab', [ 'label' => esc_html__( 'Products', 'martfury-addons' ) ] );

		$this->add_control(
			'product_cats',
			[
				'label'       => esc_html__( 'Product Categories', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Elementor::get_taxonomy(),
				'default'     => '',
				'multiple'    => true,
				'label_block' => true,
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Products per view', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'product_tabs_tab', [ 'label' => esc_html__( 'Tabs', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'show_heading_tab',
			[
				'label'     => esc_html__( 'Heading', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header' => 'display: {{VALUE}}!important',

				],
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'tab_title', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Tab Name', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'tab_products',
			[
				'label'   => esc_html__( 'Products', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'recent'       => esc_html__( 'Recent', 'martfury-addons' ),
					'featured'     => esc_html__( 'Featured', 'martfury-addons' ),
					'best_selling' => esc_html__( 'Best Selling', 'martfury-addons' ),
					'top_rated'    => esc_html__( 'Top Rated', 'martfury-addons' ),
					'sale'         => esc_html__( 'On Sale', 'martfury-addons' ),
				],
				'default' => 'recent',
				'toggle'  => false,
			]
		);

		$repeater->add_control(
			'tab_orderby',
			[
				'label'     => esc_html__( 'Order By', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''           => esc_html__( 'Default', 'martfury-addons' ),
					'date'       => esc_html__( 'Date', 'martfury-addons' ),
					'title'      => esc_html__( 'Title', 'martfury-addons' ),
					'menu_order' => esc_html__( 'Menu Order', 'martfury-addons' ),
					'rand'       => esc_html__( 'Random', 'martfury-addons' ),
				],
				'default'   => '',
				'condition' => [
					'products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$repeater->add_control(
			'tab_order',
			[
				'label'     => esc_html__( 'Order', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''     => esc_html__( 'Default', 'martfury-addons' ),
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default'   => '',
				'condition' => [
					'products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$this->add_control(
			'tabs',
			[
				'label'         => esc_html__( 'Product Tabs', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'tab_title'    => esc_html__( 'New Arrivals', 'martfury-addons' ),
						'tab_products' => 'recent'
					],
					[
						'tab_title'    => esc_html__( 'Best Seller', 'martfury-addons' ),
						'tab_products' => 'best_selling'
					],
					[
						'tab_title'    => esc_html__( 'Sale', 'martfury-addons' ),
						'tab_products' => 'sale'
					]
				],
				'title_field'   => '{{{ tab_title }}}',
				'prevent_empty' => false
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'product_carousel_tab', [ 'label' => esc_html__( 'Carousel', 'martfury-addons' ) ] );

		$this->add_control(
			'products_slides_to_show',
			[
				'label'   => esc_html__( 'Slides To Show', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'4' => esc_html__( '4 Columns', 'martfury-addons' ),
					'3' => esc_html__( '3 Columns', 'martfury-addons' ),
					'5' => esc_html__( '5 Columns', 'martfury-addons' ),
					'6' => esc_html__( '6 Columns', 'martfury-addons' ),
				],
				'default' => '4',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'products_slides_to_scroll',
			[
				'label'   => esc_html__( 'Slides To Scroll', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'4' => esc_html__( '4 Columns', 'martfury-addons' ),
					'3' => esc_html__( '3 Columns', 'martfury-addons' ),
					'5' => esc_html__( '5 Columns', 'martfury-addons' ),
					'6' => esc_html__( '6 Columns', 'martfury-addons' ),
				],
				'default' => '4',
				'toggle'  => false,
			]
		);

		$this->add_responsive_control(
			'products_navigation',
			[
				'label'   => esc_html__( 'Navigation', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'both'   => esc_html__( 'Arrows and Dots', 'martfury-addons' ),
					'arrows' => esc_html__( 'Arrows', 'martfury-addons' ),
					'dots'   => esc_html__( 'Dots', 'martfury-addons' ),
					'none'   => esc_html__( 'None', 'martfury-addons' ),
				],
				'default' => 'arrows',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'products_infinite',
			[
				'label'     => esc_html__( 'Infinite Loop', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => 'yes'
			]
		);

		$this->add_control(
			'products_autoplay',
			[
				'label'     => esc_html__( 'Autoplay', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => 'no'
			]
		);

		$this->add_control(
			'products_autoplay_speed',
			[
				'label'   => esc_html__( 'Autoplay Speed (in ms)', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5000,
				'min'     => 100,
				'step'    => 100,
			]
		);

		$this->add_control(
			'products_speed',
			[
				'label'   => esc_html__( 'Speed (in ms)', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 800,
				'min'     => 100,
				'step'    => 100,
			]
		);


		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget side products controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_side_products_controls() {

		$this->start_controls_section(
			'section_side_product',
			[
				'label' => esc_html__( 'Side Products', 'martfury-addons' ),
			]
		);


		$this->start_controls_tabs( 'side_products_content_settings' );

		$this->start_controls_tab( 'side_products_heading_tab', [ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ] );

		$this->add_control(
			'side_title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Heading Name', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'side_products_tab', [ 'label' => esc_html__( 'Products', 'martfury-addons' ) ] );

		$this->add_control(
			'side_per_page',
			[
				'label'   => esc_html__( 'Total Products', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->add_control(
			'side_products',
			[
				'label'   => esc_html__( 'Products', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'recent'       => esc_html__( 'Recent', 'martfury-addons' ),
					'featured'     => esc_html__( 'Featured', 'martfury-addons' ),
					'best_selling' => esc_html__( 'Best Selling', 'martfury-addons' ),
					'top_rated'    => esc_html__( 'Top Rated', 'martfury-addons' ),
					'sale'         => esc_html__( 'On Sale', 'martfury-addons' ),
				],
				'default' => 'recent',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'side_product_cats',
			[
				'label'       => esc_html__( 'Product Categories', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Elementor::get_taxonomy(),
				'default'     => '',
				'multiple'    => true,
				'label_block' => true,
			]
		);

		$this->add_control(
			'side_product_orderby',
			[
				'label'     => esc_html__( 'Order By', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''           => esc_html__( 'Default', 'martfury-addons' ),
					'date'       => esc_html__( 'Date', 'martfury-addons' ),
					'title'      => esc_html__( 'Title', 'martfury-addons' ),
					'menu_order' => esc_html__( 'Menu Order', 'martfury-addons' ),
					'rand'       => esc_html__( 'Random', 'martfury-addons' ),
				],
				'default'   => '',
				'condition' => [
					'side_products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$this->add_control(
			'side_product_order',
			[
				'label'     => esc_html__( 'Order', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''     => esc_html__( 'Default', 'martfury-addons' ),
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default'   => '',
				'condition' => [
					'side_products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'side_products_view_all_tab', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

		$this->add_control(
			'side_link_text', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'View More', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'side_link_url', [
				'label'         => esc_html__( 'Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
				'default'       => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_lazy_loading_controls() {
		$this->start_controls_section(
			'section_lazy_loading_content',
			[
				'label' => esc_html__( 'Lazy Loading', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'lazy_loading',
			[
				'label'   => esc_html__( 'Enable', 'martfury-addons' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_responsive_control(
			'lazy_loading_height',
			[
				'label'     => esc_html__( 'Height', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 10,
						'max' => 1000,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-of-category-loading .mf-vc-loading' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);


		$this->end_controls_section();
	}

	/**
	 * Register the widget heading style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_heading_style_controls() {

		$this->start_controls_section(
			'section_style_heading',
			[
				'label' => esc_html__( 'Heading', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'heading_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header' => 'background-color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading_border_type',
			[
				'label'     => esc_html__( 'Border Type', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       => esc_html__( 'Solid', 'martfury-addons' ),
					'double' => _x( 'Double', 'Border Control', 'martfury-addons' ),
					'dotted' => _x( 'Dotted', 'Border Control', 'martfury-addons' ),
					'dashed' => _x( 'Dashed', 'Border Control', 'martfury-addons' ),
					'groove' => _x( 'Groove', 'Border Control', 'martfury-addons' ),
					'none'   => _x( 'None', 'Border Control', 'martfury-addons' ),
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'heading_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'heading_style_divider',
			[
				'label' => '',
				'type'  => Controls_Manager::DIVIDER,
			]
		);

		$this->start_controls_tabs( 'heading_title_style_settings' );

		$this->start_controls_tab( 'heading_title_style_tab', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_control(
			'heading_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'heading_title_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header h2, {{WRAPPER}} .mf-products-of-category-2 .cats-header .cats-inner__heading' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_icon_style_tab', [ 'label' => esc_html__( 'Icon', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'heading_icon_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title .mf-icon' => 'padding-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_icon_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title i'            => 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title .mf-icon svg' => 'fill: {{VALUE}}',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_icon_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .cats-header .cat-title .mf-icon',
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_links_style_tab', [ 'label' => esc_html__( 'Links', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'heading_links',
			[
				'label'     => esc_html__( 'Show Links', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'flex' => esc_html__( 'Yes', 'martfury-addons' ),
					'none' => esc_html__( 'No', 'martfury-addons' ),
				],
				'default'   => 'flex',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .extra-links' => 'display: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'heading_links_padding',
			[
				'label'     => esc_html__( 'Padding', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .extra-links li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_links_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .extra-links li a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_links_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .cats-header .extra-links li a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_links_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .cats-header .extra-links li a',
			]
		);


		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

	}

	/**
	 * Register the widget banners style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_banners_style_controls() {

		$this->start_controls_section(
			'section_banners_style',
			[
				'label' => esc_html__( 'Banners Carousel', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'banners_show',
			[
				'label'     => esc_html__( 'Show Banners', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Yes', 'martfury-addons' ),
					'none'  => esc_html__( 'No', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider' => 'display: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'banners_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'banners_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .images-slider',
			]
		);

		$this->add_control(
			'banners_style_divider',
			[
				'label' => '',
				'type'  => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'banners_arrow_width',
			[
				'label'     => esc_html__( 'Arrow Width', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow' => 'width: {{SIZE}}{{UNIT}};    overflow: hidden;',
				],
			]
		);

		$this->add_responsive_control(
			'banners_arrow_height',
			[
				'label'     => esc_html__( 'Arrow Height', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow' => 'height: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'banners_arrow_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow',
			]
		);

		$this->start_controls_tabs( 'banners_normal_settings' );


		$this->start_controls_tab( 'banners_normal', [ 'label' => esc_html__( 'Normal', 'martfury-addons' ) ] );

		$this->add_control(
			'banners_arrow_background',
			[
				'label'     => esc_html__( 'Arrow Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'banners_arrow_color',
			[
				'label'     => esc_html__( 'Arrow Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'banners_hover', [ 'label' => esc_html__( 'Hover', 'martfury-addons' ) ] );

		$this->add_control(
			'banners_arrow_hover_background',
			[
				'label'     => esc_html__( 'Arrow Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'banners_arrow_hover_color',
			[
				'label'     => esc_html__( 'Arrow Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .images-slider .slick-arrow:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget product tabs style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_product_tabs_style_controls() {

		$this->start_controls_section(
			'section_product_tabs_style',
			[
				'label' => esc_html__( 'Product Tabs', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'product_tabs_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-content' => 'padding-top: {{SIZE}}{{UNIT}}'
				],
			]
		);

		$this->add_responsive_control(
			'product_tabs_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'product_tabs_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs',
			]
		);

		$this->add_control(
			'product_tabs_style_divider',
			[
				'label' => '',
				'type'  => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'product_tabs_heading_padding',
			[
				'label'      => esc_html__( 'Heading Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'product_tabs_heading_border_type',
			[
				'label'     => esc_html__( 'Heading Border Type', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       => esc_html__( 'Solid', 'martfury-addons' ),
					'double' => _x( 'Double', 'Border Control', 'martfury-addons' ),
					'dotted' => _x( 'Dotted', 'Border Control', 'martfury-addons' ),
					'dashed' => _x( 'Dashed', 'Border Control', 'martfury-addons' ),
					'groove' => _x( 'Groove', 'Border Control', 'martfury-addons' ),
					'none'   => _x( 'None', 'Border Control', 'martfury-addons' ),
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'product_tabs_heading_border_width',
			[
				'label'     => esc_html__( 'Heading Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'product_tabs_heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'product_tabs_heading_border_color',
			[
				'label'     => esc_html__( 'Heading Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'product_tabs_heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'product_tabs_heading_style_divider',
			[
				'label' => '',
				'type'  => Controls_Manager::DIVIDER,
			]
		);

		$this->start_controls_tabs( 'product_tabs_style_settings' );


		$this->start_controls_tab( 'product_tabs_title_style', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'product_tabs_title_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header .tabs-nav li'                  => 'padding-right: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header .tabs-nav li:last-child'       => 'padding-right: 0',
					'.rtl {{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header .tabs-nav li:last-child'  => 'padding-right: {{SIZE}}{{UNIT}}',
					'.rtl {{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header .tabs-nav li:first-child' => 'padding-right: 0',
				],
			]
		);

		$this->add_control(
			'product_tabs_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header li a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header li a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_title_active_color',
			[
				'label'     => esc_html__( 'Active Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header .tabs-nav li a.active' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'product_tabs_title_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .tabs-header li a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'product_tabs_arrows_style', [ 'label' => esc_html__( 'Arrows', 'martfury-addons' ) ] );

		$this->add_control(
			'product_tabs_arrows_prev_position',
			[
				'label'              => esc_html__( 'Arrow Previous Position', 'martfury-addons' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => [ 'px', 'em', '%' ],
				'allowed_dimensions' => [ 'top', 'right' ],
				'default'            => [
					'top'   => '-54',
					'right' => '35',
				],
				'selectors'          => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-prev-arrow' => 'top: {{TOP}}{{UNIT}};right: {{RIGHT}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_arrows_next_position',
			[
				'label'              => esc_html__( 'Arrow Next Position', 'martfury-addons' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => [ 'px', 'em', '%' ],
				'allowed_dimensions' => [ 'top', 'right' ],
				'default'            => [
					'top'   => '-54',
					'right' => '0',
				],
				'selectors'          => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-next-arrow' => 'top: {{TOP}}{{UNIT}};right: {{RIGHT}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_arrows_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-arrow' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_arrows_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-arrow:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'product_tabs_arrows_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-arrow',
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'product_tabs_dots_style', [ 'label' => esc_html__( 'Dots', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'product_tabs_dots_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-dots' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'product_tabs_dots_width',
			[
				'label'     => esc_html__( 'Dots Width', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-dots li button' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'product_tabs_dots_background',
			[
				'label'     => esc_html__( 'Dots Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-dots li button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'product_tabs_dots_active_background',
			[
				'label'     => esc_html__( 'Dots Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-products-tabs .slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget product tabs style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_side_products_style_controls() {

		$this->start_controls_section(
			'section_side_products_style',
			[
				'label' => esc_html__( 'Side Products', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'side_products_hide_desktop',
			[
				'label'        => esc_html__( 'Hide On Desktop', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => esc_html__( 'Hide', 'martfury-addons' ),
				'label_off'    => esc_html__( 'Show', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'side_products_hide_tablet',
			[
				'label'        => esc_html__( 'Hide On Tablet', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => esc_html__( 'Hide', 'martfury-addons' ),
				'label_off'    => esc_html__( 'Show', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'side_products_hide_mobile',
			[
				'label'        => esc_html__( 'Hide On Mobile', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => esc_html__( 'Hide', 'martfury-addons' ),
				'label_off'    => esc_html__( 'Show', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->add_responsive_control(
			'side_products_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'side_products_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .products-side',
			]
		);

		$this->add_control(
			'side_products_style_divider',
			[
				'label' => '',
				'type'  => Controls_Manager::DIVIDER,
			]
		);


		$this->start_controls_tabs( 'side_products_heading_settings' );


		$this->start_controls_tab( 'side_products_heading_style', [ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'side_products_heading_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'side_products_heading_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'side_products_heading_border_type',
			[
				'label'     => esc_html__( 'Border Type', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       => esc_html__( 'Solid', 'martfury-addons' ),
					'double' => _x( 'Double', 'Border Control', 'martfury-addons' ),
					'dotted' => _x( 'Dotted', 'Border Control', 'martfury-addons' ),
					'dashed' => _x( 'Dashed', 'Border Control', 'martfury-addons' ),
					'groove' => _x( 'Groove', 'Border Control', 'martfury-addons' ),
					'none'   => _x( 'None', 'Border Control', 'martfury-addons' ),
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'side_products_heading_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'product_tabs_heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'side_products_heading_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'product_tabs_heading_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'side_products_heading_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'side_products_title_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .products-side .side-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'side_products_products_style', [ 'label' => esc_html__( 'Products', 'martfury-addons' ) ] );

		$this->add_control(
			'side_products_product_items_spacing',
			[
				'label'     => esc_html__( 'Product Items Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side ul.products li.product' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'side_products_view_all_style', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

		$this->add_control(
			'side_products_view_all_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .link' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'side_products_view_all_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .products-side .link:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'side_products_view_all_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category-2 .products-side .link',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_lazy_loading_style_controls() {

		$this->start_controls_section(
			'section_style_lazy_loading',
			[
				'label' => esc_html__( 'Lazy Loading', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);


		$this->add_control(
			'loading_border_color',
			[
				'label'     => esc_html__( 'Loading Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category-2 .mf-vc-loading .mf-vc-loading--wrapper:before' => 'border-color: {{VALUE}} {{VALUE}} {{VALUE}} transparent;',
				],
				'separator' => 'before',
			]
		);


		$this->end_controls_section();
	}


	/**
	 * Render icon box widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute(
			'wrapper', 'class', [
				'mf-products-of-category-2 woocommerce',
				$settings['lazy_loading'] == 'yes' ? '' : 'no-infinite'
			]
		);

		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<?php if ( $settings['lazy_loading'] == 'yes' ) : ?>
				<?php
				// AJAX settings
				$this->add_render_attribute(
					'ajax_wrapper', 'class', [
						'mf-products-of-category-loading'
					]
				);
				$ajax_settings = [
					'icon_type'                  => $settings['icon_type'],
					'icon'                       => $settings['icon'],
					'custom_icon'                => $settings['custom_icon'],
					'title'                      => $settings['title'],
					'title_size'                 => $settings['title_size'],
					'link'                       => $settings['link'],
					'links_group'                => $settings['links_group'],
					'banners'                    => $settings['banners'],
					'banners_autoplay'           => $settings['banners_autoplay'],
					'banners_autoplay_speed'     => $settings['banners_autoplay_speed'],
					'banners_speed'              => $settings['banners_speed'],
					'banners_infinite'           => $settings['banners_infinite'],
					'banners_arrows'             => $settings['banners_arrows'],
					'product_cats'               => $settings['product_cats'],
					'per_page'                   => $settings['per_page'],
					'show_heading_tab'           => $settings['show_heading_tab'],
					'tabs'                       => $settings['tabs'],
					'products_slides_to_show'    => $settings['products_slides_to_show'],
					'products_slides_to_scroll'  => $settings['products_slides_to_scroll'],
					'products_navigation'        => $settings['products_navigation'],
					'products_navigation_tablet' => isset($settings['products_navigation_tablet']) ? $settings['products_navigation_tablet'] : $settings['products_navigation'],
					'products_navigation_mobile' => isset($settings['products_navigation_mobile']) ?$settings['products_navigation_mobile'] :  $settings['products_navigation'],
					'products_infinite'          => $settings['products_infinite'],
					'products_autoplay'          => $settings['products_autoplay'],
					'products_autoplay_speed'    => $settings['products_autoplay_speed'],
					'products_speed'             => $settings['products_speed'],
					'side_title'                 => $settings['side_title'],
					'side_per_page'              => $settings['side_per_page'],
					'side_products'              => $settings['side_products'],
					'side_product_cats'          => $settings['side_product_cats'],
					'side_product_orderby'       => $settings['side_product_orderby'],
					'side_product_order'         => $settings['side_product_order'],
					'side_link_text'             => $settings['side_link_text'],
					'side_link_url'              => $settings['side_link_url'],
					'side_products_hide_desktop' => $settings['side_products_hide_desktop'],
					'side_products_hide_tablet'  => $settings['side_products_hide_tablet'],
					'side_products_hide_mobile'  => $settings['side_products_hide_mobile'],
					'lazy_loading'               => $settings['lazy_loading']
				];
				$this->add_render_attribute( 'ajax_wrapper', 'data-settings', wp_json_encode( $ajax_settings ) );
				?>
                <div <?php echo $this->get_render_attribute_string( 'ajax_wrapper' ); ?>>
                    <div class="mf-vc-loading">
                        <div class="mf-vc-loading--wrapper"></div>
                    </div>
                </div>
			<?php else : ?>
				<?php Elementor_AjaxLoader::get_products_of_category_2( $settings ); ?>
			<?php endif; ?>
        </div>
		<?php

	}

	/**
	 * Get the link control
	 *
	 * @return string.
	 */
	protected function get_link_control( $link_key, $url, $content, $class_css ) {

		if ( $url['is_external'] ) {
			$this->add_render_attribute( $link_key, 'target', '_blank' );
		}

		if ( $url['nofollow'] ) {
			$this->add_render_attribute( $link_key, 'rel', 'nofollow' );
		}

		$attr = 'span';
		if ( $url['url'] ) {
			$this->add_render_attribute( $link_key, 'href', $url['url'] );
			$attr = 'a';
		}

		return sprintf( '<%1$s class="%4$s" %2$s>%3$s</%1$s>', $attr, $this->get_render_attribute_string( $link_key ), $content, $class_css );
	}


}