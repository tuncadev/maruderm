<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;
use Elementor\Widget_Base;
use MartfuryAddons\Elementor;
use MartfuryAddons\Elementor_AjaxLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Product_Tabs_Grid extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-product-tabs-grid';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Product Tabs Grid', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-tabs';
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

		$this->start_controls_section(
			'section_heading',
			[ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ]
		);

		$this->start_controls_tabs( 'tabs_heading' );
		$this->start_controls_tab(
			'tab_title',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Heading Name', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
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

		// Product Tabs

		$this->start_controls_tab(
			'tab_product_tabs',
			[
				'label' => esc_html__( 'Tabs', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'product_tabs_source',
			[
				'label'   => esc_html__( 'Source', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'special_products' => esc_html__( 'Special Products', 'martfury-addons' ),
					'product_cats'     => esc_html__( 'Product Categories', 'martfury-addons' ),
				],
				'default' => 'special_products',
				'toggle'  => false,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'title', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'This is heading', 'martfury-addons' ),
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
				'toggle'    => false,
				'condition' => [
					'tab_products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
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
				'toggle'    => false,
				'condition' => [
					'tab_products' => [ 'recent', 'top_rated', 'sale', 'featured' ],
				],
			]
		);

		$this->add_control(
			'special_products_tabs',
			[
				'label'         => '',
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'title'        => esc_html__( 'New Arrivals', 'martfury-addons' ),
						'tab_products' => 'recent'
					],
					[
						'title'        => esc_html__( 'Best Seller', 'martfury-addons' ),
						'tab_products' => 'best_selling'
					],
					[
						'title'        => esc_html__( 'Sale', 'martfury-addons' ),
						'tab_products' => 'sale'
					]
				],
				'title_field'   => '{{{ title }}}',
				'prevent_empty' => false,
				'condition'     => [
					'product_tabs_source' => 'special_products',
				],
			]
		);

		$product_cats = Elementor::get_taxonomy();
		$repeater     = new \Elementor\Repeater();

		$repeater->add_control(
			'product_cat', [
				'label'       => esc_html__( 'Category Tab', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $product_cats,
				'label_block' => true,
			]
		);

		$this->add_control(
			'product_cats_tabs',
			[
				'label'         => esc_html__( 'Category Tabs', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [],
				'prevent_empty' => false,
				'condition'     => [
					'product_tabs_source' => 'product_cats',
				],
				'title_field'   => '{{{ product_cat }}}',
			]
		);

		$this->end_controls_tab();

		// Link Tab

		$this->start_controls_tab(
			'tab_link',
			[
				'label' => esc_html__( 'View All', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'view_all_text',
			[
				'label'       => esc_html__( 'Text', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'View All', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$this->add_control(
			'all_link', [
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

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_products',
			[ 'label' => esc_html__( 'Products', 'martfury-addons' ) ]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'5' => esc_html__( '5', 'martfury-addons' ),
					'4' => esc_html__( '4', 'martfury-addons' ),
					'3' => esc_html__( '3', 'martfury-addons' ),
					'6' => esc_html__( '6', 'martfury-addons' ),
					'7' => esc_html__( '7', 'martfury-addons' ),
				],
				'default' => '5',
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Total Products', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
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
				'condition'   => [
					'product_tabs_source' => 'special_products',
				],
			]
		);

		$this->add_control(
			'products',
			[
				'label'     => esc_html__( 'Product', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'recent'       => esc_html__( 'Recent', 'martfury-addons' ),
					'featured'     => esc_html__( 'Featured', 'martfury-addons' ),
					'best_selling' => esc_html__( 'Best Selling', 'martfury-addons' ),
					'top_rated'    => esc_html__( 'Top Rated', 'martfury-addons' ),
					'sale'         => esc_html__( 'On Sale', 'martfury-addons' ),
				],
				'default'   => 'recent',
				'toggle'    => false,
				'condition' => [
					'product_tabs_source' => 'product_cats',
				],
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
					'products'            => [ 'recent', 'top_rated', 'sale', 'featured' ],
					'product_tabs_source' => 'product_cats',
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
					'products'            => [ 'recent', 'top_rated', 'sale', 'featured' ],
					'product_tabs_source' => 'product_cats',
				],
			]
		);

		$this->end_controls_section();

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
					'{{WRAPPER}} .mf-products-tabs .mf-products-tabs-loading .mf-vc-loading' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);


		$this->end_controls_section();

		/**
		 * Tab Style
		 */
		// Heading
		$this->start_controls_section(
			'section_heading_style',
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
					'{{WRAPPER}} .mf-products-tabs .tabs-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'heading_space',
			[
				'label'     => esc_html__( 'Bottom Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-content' => 'padding-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);
		$this->add_responsive_control(
			'heading_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
				'default'   => [
					'top'    => '0',
					'right'  => '0',
					'bottom' => '1',
					'left'   => '0',
				],
			]
		);
		$this->add_control(
			'heading_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header' => 'border-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'heading_border_style',
			[
				'label'     => esc_html__( 'Border Style', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'dotted' => esc_html__( 'Dotted', 'martfury-addons' ),
					'dashed' => esc_html__( 'Dashed', 'martfury-addons' ),
					'solid'  => esc_html__( 'Solid', 'martfury-addons' ),
					'none'   => esc_html__( 'None', 'martfury-addons' ),
				],
				'default'   => 'solid',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header' => 'border-style: {{VALUE}};',
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

		$this->start_controls_tabs( 'heading_style_settings' );

		$this->start_controls_tab( 'heading_title', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'title_spacing',
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
					'{{WRAPPER}} .mf-products-tabs .tabs-header h2, {{WRAPPER}} .mf-products-tabs .tabs-header .tabs-cat__heading' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .cat-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .cat-title:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .mf-products-tabs .tabs-header h2, {{WRAPPER}} .mf-products-tabs .tabs-header .tabs-cat__heading',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tabs_title', [ 'label' => esc_html__( 'Product Tabs', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'show_product_tabs',
			[
				'label'     => esc_html__( 'Product Tabs', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'flex' => esc_html__( 'Show', 'martfury-addons' ),
					'none' => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'flex',
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'tabs_item_spacing',
			[
				'label'     => esc_html__( 'Items Spacing', 'martfury-addons' ),
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
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li'                  => 'padding-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li:first-child'      => 'padding-left: 0',
					'{{WRAPPER}} .mf-products-tabs .tabs-header .link'                         => 'padding-left: {{SIZE}}{{UNIT}}',
					'.rtl {{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li:first-child' => 'padding-left: {{SIZE}}{{UNIT}}',
					'.rtl {{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li:last-child'  => 'padding-left: 0',
					'.rtl {{WRAPPER}} .mf-products-tabs .tabs-header .link'                    => 'padding-left: 0;padding-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'product_tab_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li a, {{WRAPPER}} .mf-products-tabs .tabs-header .link' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'product_tab_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li a:hover,
					{{WRAPPER}} .mf-products-tabs .tabs-header .link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'product_tab_active_color',
			[
				'label'     => esc_html__( 'Active Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav li a.active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'product_tab_typography',
				'selector' => '{{WRAPPER}} .mf-products-tabs .tabs-header .tabs-nav, {{WRAPPER}} .mf-products-tabs .tabs-header .link',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

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
					'{{WRAPPER}} .mf-products-tabs .mf-vc-loading .mf-vc-loading--wrapper:before' => 'border-color: {{VALUE}} {{VALUE}} {{VALUE}} transparent;',
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

		$classes = [
			'mf-products-tabs mf-products-tabs-grid woocommerce',
			$settings['lazy_loading'] == 'yes' ? '' : 'no-infinite'
		];

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<?php if ( $settings['lazy_loading'] == 'yes' ) : ?>
				<?php
				// AJAX settings
				$this->add_render_attribute(
					'ajax_wrapper', 'class', [
						'mf-products-tabs-loading'
					]
				);
				$ajax_settings = [
					'title'                 => $settings['title'],
					'title_size'            => $settings['title_size'],
					'link'                  => $settings['link'],
					'product_tabs_source'   => $settings['product_tabs_source'],
					'special_products_tabs' => $settings['special_products_tabs'],
					'product_cats_tabs'     => $settings['product_cats_tabs'],
					'view_all_text'         => $settings['view_all_text'],
					'all_link'              => $settings['all_link'],
					'product_cats'          => $settings['product_cats'],
					'per_page'              => $settings['per_page'],
					'products'              => $settings['products'],
					'orderby'               => $settings['orderby'],
					'order'                 => $settings['order'],
					'columns'               => $settings['columns'],
					'lazy_loading'          => $settings['lazy_loading']
				];
				$this->add_render_attribute( 'ajax_wrapper', 'data-settings', wp_json_encode( $ajax_settings ) );
				?>
				<div <?php echo $this->get_render_attribute_string( 'ajax_wrapper' ); ?>>
					<div class="mf-vc-loading">
						<div class="mf-vc-loading--wrapper"></div>
					</div>
				</div>
			<?php else : ?>
				<?php Elementor_AjaxLoader::get_product_tabs_handler( $settings ); ?>
			<?php endif; ?>
		</div>
		<?php
	}


}