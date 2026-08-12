<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use MartfuryAddons\Elementor;
use Elementor\Group_Control_Border;
use MartfuryAddons\Elementor_AjaxLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Products_Of_Category extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-products-of-category';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Products of Category', 'martfury-addons' );
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
		$this->register_products_controls();
		$this->register_lazy_loading_controls();

		// Style Tab
		$this->register_heading_style_controls();
		$this->register_banners_style_controls();
		$this->register_products_style_controls();
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
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category Name', 'martfury-addons' ),
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
			'c_link', [
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
				'default'     => '',
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
				'label'         => esc_html__( 'Quick Link Items', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'link_text' => esc_html__( 'Best Seller', 'martfury-addons' ),
						'link_url'  => '#',
					],
					[
						'link_text' => esc_html__( 'New Arrivals', 'martfury-addons' ),
						'link_url'  => '#',
					],
					[
						'link_text' => esc_html__( 'On Sales', 'martfury-addons' ),
						'link_url'  => '#',
					],

				],
				'title_field'   => '{{{ link_text }}}',
				'prevent_empty' => false
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'view_all_tab', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

		$this->add_control(
			'view_all_text',
			[
				'label'   => esc_html__( 'View All Text', 'martfury-addons' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'View All', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'view_all_link', [
				'label'         => esc_html__( 'View All Link', 'martfury-addons' ),
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

		$this->add_control(
			'banners_carousel',
			[
				'label'     => esc_html__( 'Enable', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'martfury-addons' ),
				'label_off' => esc_html__( 'No', 'martfury-addons' ),
				'default'   => 'yes',
			]
		);

		$this->start_controls_tabs( 'banners_content_settings' );

		$this->start_controls_tab( 'banner_images_tab', [ 'label' => esc_html__( 'Images', 'martfury-addons' ) ] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'image',
			[
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => 'https://via.placeholder.com/269x639/e7eff1?text=269x639+Banner',
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
					'url'         => '#',
					'is_external' => true,
					'nofollow'    => true,
				],
			]
		);

		$this->add_control(
			'banners',
			[
				'label'         => esc_html__( 'Images', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'image'      => [
							'url' => 'https://via.placeholder.com/269x639/e7eff1?text=269x639+Banner'
						],
						'image_link' => [
							'url' => '#'
						]
					],
					[
						'image'      => [
							'url' => 'https://via.placeholder.com/269x639/e7eff1?text=269x639+Banner+2'
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

		$this->start_controls_tab( 'carousel_settings_tab', [ 'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ) ] );

		$this->add_control(
			'autoplay',
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
			'autoplay_speed',
			[
				'label'   => esc_html__( 'Autoplay Speed', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5000,
			]
		);

		$this->add_control(
			'speed',
			[
				'label'   => esc_html__( 'Speed', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 800,
			]
		);

		$this->add_control(
			'infinite',
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
			'arrows',
			[
				'label'     => esc_html__( 'Arrows', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => 'yes',
				'options'   => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
			]
		);

		$this->add_control(
			'dots',
			[
				'label'     => esc_html__( 'Dots', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => 'yes',
				'options'   => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget products controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_products_controls() {

		$this->start_controls_section(
			'section_product_content',
			[
				'label' => esc_html__( 'Products', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'products',
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
			'product_tags',
			[
				'label'       => esc_html__( 'Product Tags', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Elementor::get_taxonomy( 'product_tag' ),
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
				'default' => 6,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Product Columns', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'3' => esc_html__( '3 Columns', 'martfury-addons' ),
					'4' => esc_html__( '4 Columns', 'martfury-addons' ),
				],
				'default' => '3',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'orderby',
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
					'products' => [ 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$this->add_control(
			'order',
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
					'products' => [ 'top_rated', 'sale', 'featured' ],
				],
			]
		);

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
				'default'   => [],
				'range'     => [
					'px' => [
						'min' => 10,
						'max' => 1000,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .mf-products-of-category-loading .mf-vc-loading' => 'min-height: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .mf-products-of-category .cats-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'heading_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-products-of-category .cats-info',
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

		$this->add_responsive_control(
			'heading_title_spacing',
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
					'{{WRAPPER}} .mf-products-of-category .cats-info .cats-inner__heading' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .cat-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .cat-title:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category .cats-info .cats-inner__heading',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_links_style_tab', [ 'label' => esc_html__( 'Links', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'quick_links',
			[
				'label'     => esc_html__( 'Items Display', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Vertical', 'martfury-addons' ),
					'flex'  => esc_html__( 'Horizontal', 'martfury-addons' ),
					'none'  => esc_html__( 'None', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .extra-links' => 'display: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'heading_link_items_padding',
			[
				'label'     => esc_html__( 'Items Padding', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .extra-links li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};margin-top:0;margin-bottom:0',
				],
			]
		);

		$this->add_control(
			'heading_links_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .extra-links li a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_links_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .extra-links li a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_links_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category .cats-info .extra-links li a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_view_all_style_tab', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'heading_view_all_spacing',
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
					'{{WRAPPER}} .mf-products-of-category .cats-info .footer-link' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .footer-link .link' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .cats-info .footer-link .link:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_view_all_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category .cats-info .footer-link .link',
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
			'section_style_sliders',
			[
				'label' => esc_html__( 'Sliders', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'sliders_arrow_width',
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
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow' => 'width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'sliders_arrow_height',
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
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow' => 'height: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sliders_arrow_typography',
				'selector' => '{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow',
			]
		);

		$this->start_controls_tabs( 'sliders_normal_settings' );


		$this->start_controls_tab( 'sliders_normal', [ 'label' => esc_html__( 'Normal', 'martfury-addons' ) ] );

		$this->add_control(
			'sliders_arrow_background',
			[
				'label'     => esc_html__( 'Arrow Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'sliders_arrow_color',
			[
				'label'     => esc_html__( 'Arrow Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'sliders_hover', [ 'label' => esc_html__( 'Hover', 'martfury-addons' ) ] );

		$this->add_control(
			'sliders_arrow_hover_background',
			[
				'label'     => esc_html__( 'Arrow Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'sliders_arrow_hover_color',
			[
				'label'     => esc_html__( 'Arrow Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-arrow:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'sliders_dots_width',
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
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-dots li button' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sliders_dots_background',
			[
				'label'     => esc_html__( 'Dots Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-dots li button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'sliders_dots_active_background',
			[
				'label'     => esc_html__( 'Dots Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .images-slider .slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register the widget products style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_products_style_controls() {

		$this->start_controls_section(
			'section_style_products',
			[
				'label' => esc_html__( 'Products', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'products_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-products-of-category .products-box',
			]
		);


		$this->add_control(
			'products_item_border_color',
			[
				'label'     => esc_html__( 'Product Item Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-of-category .products-box ul.products li' => 'border-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

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
					'{{WRAPPER}} .mf-products-of-category .mf-vc-loading .mf-vc-loading--wrapper:before' => 'border-color: {{VALUE}} {{VALUE}} {{VALUE}} transparent;',
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
				'mf-products-of-category woocommerce',
				isset($settings['quick_links']) && $settings['quick_links'] != 'none' ? '' : 'no-links-group',
				$settings['banners_carousel'] == 'yes' ? '' : 'no-banners-carousel',
				$settings['lazy_loading'] == 'yes' ? '' : 'no-infinite'
			]
		);

		$carousel_settings = [
			'infinite'       => $settings['infinite'],
			'autoplay'       => $settings['autoplay'],
			'autoplay_speed' => $settings['autoplay_speed'],
			'speed'          => $settings['speed'],
			'arrows'         => $settings['arrows'],
			'dots'           => $settings['dots']
		];
		$settings['quick_links'] = empty($settings['quick_links']) ? 'block' : $settings['quick_links'];
		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );

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
					'title'            => $settings['title'],
					'title_size'       => $settings['title_size'],
					'c_link'           => $settings['c_link'],
					'quick_links'      => $settings['quick_links'],
					'links_group'      => $settings['links_group'],
					'view_all_link'    => $settings['view_all_link'],
					'view_all_text'    => $settings['view_all_text'],
					'banners_carousel' => $settings['banners_carousel'],
					'banners'          => $settings['banners'],
					'products'         => $settings['products'],
					'product_cats'     => $settings['product_cats'],
					'product_tags'     => $settings['product_tags'],
					'columns'          => $settings['columns'],
					'orderby'          => $settings['orderby'],
					'order'            => $settings['order'],
					'per_page'         => $settings['per_page']
				];
				$this->add_render_attribute( 'ajax_wrapper', 'data-settings', wp_json_encode( $ajax_settings ) );
				?>
                <div <?php echo $this->get_render_attribute_string( 'ajax_wrapper' ); ?>>
                    <div class="mf-vc-loading">
                        <div class="mf-vc-loading--wrapper"></div>
                    </div>
                </div>
			<?php else : ?>
				<?php Elementor_AjaxLoader::get_products_of_category( $settings ); ?>
			<?php endif; ?>
        </div>
		<?php
	}


}