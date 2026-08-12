<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use MartfuryAddons\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Products_Brands extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-products-brands';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martfury - Product Brands Grid', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
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
		$this->_register_brands_settings_controls();
		$this->_register_products_settings_controls();
		$this->_register_carousel_settings_controls();
	}

	protected function _register_brands_settings_controls() {
		$this->start_controls_section(
			'section_brand',
			[ 'label' => esc_html__( 'Brands', 'martfury-addons' ) ]
		);
		$this->add_responsive_control(
			'brand_columns',
			[
				'label'        => esc_html__( 'Columns', 'martfury-addons' ),
				'type'         => Controls_Manager::NUMBER,
				'default'      => 2,
				'min'          => 1,
				'max'          => 4,
				'step'         => 1,
				'prefix_class' => 'brands-columns-%s',
			]
		);
		$this->add_control(
			'number',
			[
				'label'   => esc_html__( 'Numbers', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 50,
				'step'    => 1,
			]
		);
		$this->add_control(
			'brand_orderby',
			[
				'label'   => esc_html__( 'Order By', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'name'    => esc_html__( 'Name', 'martfury-addons' ),
					'term_id' => esc_html__( 'ID', 'martfury-addons' ),
					'count'   => esc_html__( 'Count', 'martfury-addons' ),
					'order'   => esc_html__( 'Order', 'martfury-addons' ),
				],
				'default' => 'name',
			]
		);

		$this->add_control(
			'brand_order',
			[
				'label'     => esc_html__( 'Order', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default'   => 'asc',
				'condition' => [
					'brand_orderby' => [ 'name', 'term_id', 'count' ],
				],
			]
		);
		$this->add_control(
			'pagination',
			[
				'label'        => __( 'Pagination', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'martfury-addons' ),
				'label_off'    => __( 'Hide', 'martfury-addons' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->end_controls_section(); // End Settings

		// Brand Style
		$this->start_controls_section(
			'section_brand_style',
			[
				'label' => esc_html__( 'Brands', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'item_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper .brand-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'item_gap',
			[
				'label'     => __( 'Gap', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max'  => 100,
						'min'  => 0,
						'step' => 2,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .product-brands'     => 'margin-left: calc(-{{SIZE}}{{UNIT}}/2); margin-right: calc(-{{SIZE}}{{UNIT}}/2);',
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper' => 'padding-left: calc({{SIZE}}{{UNIT}}/2); padding-right: calc({{SIZE}}{{UNIT}}/2);',
				],
			]
		);

		$this->add_responsive_control(
			'item_bottom_spacing',
			[
				'label'     => __( 'Bottom Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 100,
						'min' => 0,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'brand_item_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper .brand-item' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->start_popover();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'item_border',
				'label'     => __( 'Border', 'martfury-addons' ),
				'selector'  => '{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper .brand-item',
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'item_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item-wrapper .brand-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();

		// Item Header
		$this->add_control(
			'brand_style_header',
			[
				'label'     => __( 'Header', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'brand_style_header_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Line
		$this->add_control(
			'header_line',
			[
				'label'        => __( 'Line', 'martfury-addons' ),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => __( 'Default', 'martfury-addons' ),
				'label_on'     => __( 'Custom', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->start_popover();

		$this->add_control(
			'header_line_width',
			[
				'label'      => esc_html__( 'Width', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 10
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'header_line_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header' => 'border-bottom-color: {{VALUE}}',
				],
			]
		);
		$this->add_control(
			'header_line_style',
			[
				'label'     => esc_html__( 'Style', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'solid'  => _x( 'Solid', 'Border Control', 'martfury-addons' ),
					'double' => _x( 'Double', 'Border Control', 'martfury-addons' ),
					'dotted' => _x( 'Dotted', 'Border Control', 'martfury-addons' ),
					'dashed' => _x( 'Dashed', 'Border Control', 'martfury-addons' ),
					'groove' => _x( 'Groove', 'Border Control', 'martfury-addons' ),
				],
				'default'   => 'solid',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header' => 'border-bottom-style: {{VALUE}}',
				],
			]
		);

		$this->end_popover(); // End Line

		// Logo
		$this->add_control(
			'brand_logo_options',
			[
				'label'        => __( 'Logo', 'martfury-addons' ),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => __( 'Default', 'martfury-addons' ),
				'label_on'     => __( 'Custom', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->start_popover();
		$this->add_control(
			'brand_logo',
			[
				'label'     => esc_html__( 'Logo', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-logo' => 'display: {{VALUE}}',
				],
			]
		);
		$this->add_responsive_control(
			'brand_logo_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-logo' => 'padding-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover(); // End Logo

		// Brand Info
		$this->start_controls_tabs( 'brand_info_tabs_style' );

		$this->start_controls_tab(
			'brand_name_tab_style',
			[
				'label' => __( 'Name', 'martfury-addons' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'brand_name_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info a',
			]
		);
		$this->add_control(
			'brand_name_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info a' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'brand_name_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'brand_count_tab_style',
			[
				'label' => __( 'Count', 'martfury-addons' ),
			]
		);
		$this->add_control(
			'brand_count',
			[
				'label'     => esc_html__( 'Count', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info span' => 'display: {{VALUE}}',
				],
			]
		);
		$this->add_responsive_control(
			'brand_count_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info span' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'brand_count' => 'block',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'brand_count_typography',
				'selector'  => '{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info span',
				'condition' => [
					'brand_count' => 'block',
				],
			]
		);
		$this->add_control(
			'brand_count_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__header .brand-info span' => 'color: {{VALUE}};',
				],
				'condition' => [
					'brand_count' => 'block',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs(); // End Brand Info

		// Item Content
		$this->add_control(
			'brand_style_content',
			[
				'label'     => __( 'Content', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'brand_style_content_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .brand-item__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function _register_products_settings_controls() {
		$this->start_controls_section(
			'section_products',
			[ 'label' => esc_html__( 'Products', 'martfury-addons' ) ]
		);

		$this->add_control(
			'enable_products',
			[
				'label'     => esc_html__( 'Enable', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => __( 'Off', 'martfury-addons' ),
				'label_on'  => __( 'On', 'martfury-addons' ),
				'default'   => 'yes',
				'toggle'    => false,
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Products per brand', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);
		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Order By', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''           => esc_html__( 'Default', 'martfury-addons' ),
					'date'       => esc_html__( 'Date', 'martfury-addons' ),
					'title'      => esc_html__( 'Title', 'martfury-addons' ),
					'menu_order' => esc_html__( 'Menu Order', 'martfury-addons' ),
					'rand'       => esc_html__( 'Random', 'martfury-addons' ),
				],
				'default' => '',
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => esc_html__( 'Order', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => esc_html__( 'Default', 'martfury-addons' ),
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default' => '',
			]
		);
		$this->end_controls_section();

	}

	protected function _register_carousel_settings_controls() {
		// Carousel Settings
		$this->start_controls_section(
			'section_products_carousel_settings',
			[ 'label' => esc_html__( 'Products Carousel', 'martfury-addons' ) ]
		);
		$this->add_control(
			'slidesToShow',
			[
				'label'   => esc_html__( 'Slides to show', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 7,
				'default' => 2,
			]
		);
		$this->add_control(
			'slidesToScroll',
			[
				'label'   => esc_html__( 'Slides to scroll', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 7,
				'default' => 1,
			]
		);
		$this->add_control(
			'navigation',
			[
				'label'     => esc_html__( 'Navigation', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => __( 'Off', 'martfury-addons' ),
				'label_on'  => __( 'On', 'martfury-addons' ),
				'default'   => 'yes',
				'toggle'    => false,
			]
		);

		$this->add_control(
			'infinite',
			[
				'label'     => __( 'Infinite Loop', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => __( 'Off', 'martfury-addons' ),
				'label_on'  => __( 'On', 'martfury-addons' ),
				'default'   => 'yes'
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'     => __( 'Autoplay', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => __( 'Off', 'martfury-addons' ),
				'label_on'  => __( 'On', 'martfury-addons' ),
				'default'   => 'yes'
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'   => __( 'Autoplay Speed (in ms)', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3000,
				'min'     => 100,
				'step'    => 100,
			]
		);

		$this->add_control(
			'speed',
			[
				'label'       => __( 'Speed', 'martfury-addons' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 800,
				'min'         => 100,
				'step'        => 50,
				'description' => esc_html__( 'Slide animation speed (in ms)', 'martfury-addons' ),
			]
		);

		$this->end_controls_section(); // End Carousel Settings

		// Carousel Style
		$this->start_controls_section(
			'section_carousel_style',
			[
				'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'arrows_style_divider',
			[
				'label' => esc_html__( 'Arrows', 'martfury-addons' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		// Arrows
		$this->add_control(
			'arrows_style',
			[
				'label'        => __( 'Options', 'martfury-addons' ),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => __( 'Default', 'martfury-addons' ),
				'label_on'     => __( 'Custom', 'martfury-addons' ),
				'return_value' => 'yes',
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'sliders_arrows_size',
			[
				'label'     => __( 'Size', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 100,
						'min' => 0,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .slick-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'sliders_arrow_width',
			[
				'label'      => esc_html__( 'Width', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .slick-arrow' => 'width: {{SIZE}}{{UNIT}}',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'sliders_arrow_height',
			[
				'label'      => esc_html__( 'Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brands-grid .slick-arrow' => 'height: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'sliders_arrows_offset',
			[
				'label'     => esc_html__( 'Horizontal Offset', 'martfury-addons' ),
				'type'      => Controls_Manager::NUMBER,
				'step'      => 1,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brands-grid .slick-prev-arrow' => 'left: {{VALUE}}px;',
					'{{WRAPPER}} .mf-elementor-brands-grid .slick-next-arrow' => 'right: {{VALUE}}px;',
				],
			]
		);

		$this->end_popover();

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
				'mf-brands-grid mf-elementor-brands-grid',
				$settings['navigation'] === 'yes' ? '' : 'hide-navigation',
			]
		);

		$carousel_settings = [
			'autoplay'       => $settings['autoplay'],
			'infinite'       => $settings['infinite'],
			'autoplay_speed' => intval( $settings['autoplay_speed'] ),
			'speed'          => intval( $settings['speed'] ),
			'slidesToShow'   => intval( $settings['slidesToShow'] ),
			'slidesToScroll' => intval( $settings['slidesToScroll'] ),
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );

		$settings['columns'] = $settings['slidesToShow'];

		$products = self::brands_loop( $settings );

		echo sprintf(
			'<div %s>%s</div>',
			$this->get_render_attribute_string( 'wrapper' ),
			$products
		);
	}

	/**
	 * Brands Loop
	 */
	protected function brands_loop( $settings ) {
		$taxonomy = 'product_brand';

		$term_count    = get_terms( $taxonomy, [ 'fields' => 'count' ] );
		$max_num_pages = ceil( $term_count / $settings['number'] );

		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$paged = get_query_var( 'page' );
		} else {
			$paged = 1;
		}

		$offset = ( ( $paged - 1 ) * $settings['number'] );

		$terms_atts = array(
			'taxonomy'   => $taxonomy,
			'orderby'    => $settings['brand_orderby'],
			'order'      => $settings['brand_order'],
			'number'     => $settings['number'],
			'count'      => true,
			'offset'     => $offset,
			'menu_order' => false
		);

		if ( 'order' === $settings['brand_orderby'] ) {
			$terms_atts['menu_order'] = 'asc';
		}

		$terms = get_terms( $terms_atts );

		if ( is_wp_error( $terms ) && ! $terms ) {
			return;
		}

		$output = [];

		$product_atts = array(
			'columns'  => $settings['slidesToShow'],
			'products' => 'recent',
			'order'    => $settings['order'],
			'orderby'  => $settings['orderby'],
			'per_page' => $settings['per_page'],
		);

		foreach ( $terms as $term ) {
			$product_atts['product_brands'] = array( $term->slug );

			$thumbnail_id = absint( get_term_meta( $term->term_id, 'brand_thumbnail_id', true ) );

			$thumbnail = '';
			if ( $thumbnail_id ) {
				$thumbnail = sprintf(
					'<a href="%s" class="brand-logo">%s</a>',
					esc_url( get_term_link( $term->term_id, 'product_brand' ) ),
					martfury_get_image_html( $thumbnail_id, 'shop_catalog' )
				);
			}

			$count = sprintf( _n( '%s product', '%s products', $term->count, 'martfury-addons' ), number_format_i18n( $term->count ) );

			$product_html = '';
			if ( $settings['enable_products'] === 'yes' ) {
				$product_html = sprintf( '<div class="brand-item__content">%s</div>', Elementor::get_products( $product_atts ) );
			}

			$output[] = sprintf(
				'<div class="brand-item-wrapper">
					<div class="brand-item">
						<div class="brand-item__header">
							%s
							<div class="brand-info">
								<a href="%s">%s</a>
								<span>%s</span>
							</div>
						</div>
						%s
					</div>
				</div>',
				$thumbnail,
				esc_url( get_term_link( $term->term_id, 'product_brand' ) ),
				esc_html( $term->name ),
				$count,
				$product_html
			);
		}

		$load_more = '';
		if ( $max_num_pages > 1 && $settings['pagination'] === 'yes' ) {
			$load_more .= '<div class="navigation-number text-center">';
			$load_more .= get_next_posts_link( '<span class="mf-loading"></span>', $max_num_pages );
			$load_more .= '</div>';
		}

		return sprintf( '<div class="product-brands">%s</div>%s', implode( '', $output ), $load_more );
	}



}