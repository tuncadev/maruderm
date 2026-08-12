<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use MartfuryAddons\Elementor;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Product_Deals_Grid extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-product-deals-grid';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Product Deals Grid', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-post-slider';
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

		// Content
		$this->register_content_settings_controls();

		// Style
		$this->register_heading_style_controls();
		$this->register_products_style_controls();
		$this->register_pagination_style_controls();
	}

	/**
	 * Register the widget content controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_content_settings_controls() {

		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Content', 'martfury-addons' ) ]
		);

		$this->start_controls_tabs( 'deals_content_settings' );

		$this->start_controls_tab( 'content', [ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ] );
		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Deals Of The Day', 'martfury-addons' ),
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
				'default' => 'h3',
			]
		);

		$this->add_control(
			'ends_in',
			[
				'label'       => esc_html__( 'Ends In Text', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Ends In', 'martfury-addons' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'view_all_text',
			[
				'label'   => esc_html__( 'View All Text', 'martfury-addons' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'View All', 'martfury-addons' ),
				'separator' => 'before',
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

		$this->start_controls_tab( 'products', [ 'label' => esc_html__( 'Products', 'martfury-addons' ) ] );
		$this->add_control(
			'product_type',
			[
				'label'   => esc_html__( 'Products', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'day'   => esc_html__( 'Deals of the day', 'martfury-addons' ),
					'week'  => esc_html__( 'Deals of the week', 'martfury-addons' ),
					'month' => esc_html__( 'Deals of the month', 'martfury-addons' ),
					'sale'  => esc_html__( 'On Sale', 'martfury-addons' ),
					'deals' => esc_html__( 'Product Deals', 'martfury-addons' ),
				],
				'default' => 'deals',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'product_variations',
			[
				'label'       => esc_html__( 'Show Product Variations', 'martfury-addons' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
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

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Product Columns', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'5' => esc_html__( '5 Columns', 'martfury-addons' ),
					'4' => esc_html__( '4 Columns', 'martfury-addons' ),
					'3' => esc_html__( '3 Columns', 'martfury-addons' ),
					'6' => esc_html__( '6 Columns', 'martfury-addons' ),
				],
				'default' => '5',
				'toggle'  => false,
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
				'toggle'  => false,
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
				'toggle'  => false,
			]
		);

		$this->add_control(
			'pagination',
			[
				'label'        => esc_html__( 'Pagination', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'martfury-addons' ),
				'label_off'    => esc_html__( 'Hide', 'martfury-addons' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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

		$this->add_control(
			'heading_justify_content',
			[
				'label'       => esc_html__( 'Justify Content', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => false,
				'options'     => [
					'flex-start'   => esc_html__( 'Flex Start', 'martfury-addons' ),
					'flex-end'     => esc_html__( 'Flex End', 'martfury-addons' ),
					'center'       => esc_html__( 'Center', 'martfury-addons' ),
					''             => esc_html__( 'Space Between', 'martfury-addons' ),
					'space-around' => esc_html__( 'Space Around', 'martfury-addons' ),
					'initial'      => esc_html__( 'Initial', 'martfury-addons' ),
					'inherit'      => esc_html__( 'Inherit', 'martfury-addons' ),
				],
				'default'     => '',
				'selectors'   => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'justify-content: {{VALUE}}',
				],
			]
		);


		$this->add_responsive_control(
			'heading_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_border_type',
			[
				'label'     => esc_html__( 'Border Type', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       =>esc_html__( 'Solid', 'martfury-addons' ),
					'double' => _x( 'Double', 'Border Control', 'martfury-addons' ),
					'dotted' => _x( 'Dotted', 'Border Control', 'martfury-addons' ),
					'dashed' => _x( 'Dashed', 'Border Control', 'martfury-addons' ),
					'groove' => _x( 'Groove', 'Border Control', 'martfury-addons' ),
					'none'   => _x( 'None', 'Border Control', 'martfury-addons' ),
				],
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'heading_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .mf-product-deals-day .cat-header' => 'border-color: {{VALUE}};',
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


		$this->add_responsive_control(
			'heading_title_margin',
			[
				'label'     => esc_html__( 'Margin', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-content .cat-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-content .cat-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title_typography',
				'selector' => '{{WRAPPER}} .mf-product-deals-day .header-content .cat-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_countdown_style_tab', [ 'label' => esc_html__( 'Countdown', 'martfury-addons' ) ] );

		$this->add_control(
			'hide_heading_countdown',
			[
				'label'        => esc_html__( 'Hide Countdown', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'martfury-addons' ),
				'label_off'    => esc_html__( 'No', 'martfury-addons' ),
				'default'      => '',
				'return_value' => 'none',
				'selectors'    => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'countdown_padding',
			[
				'label'     => esc_html__( 'Padding', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'countdown_margin',
			[
				'label'     => esc_html__( 'Margin', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'countdown_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'countdown_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown' => 'color: {{VALUE}}',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_links_typography',
				'selector' => '{{WRAPPER}} .mf-product-deals-day .header-countdown',
			]
		);

		$this->add_responsive_control(
			'show_ends_in',
			[
				'label'     => esc_html__( 'Ends In Text', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'inline-block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'         => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'inline-block',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-countdown .ends-text' => 'display: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ends_in_spacing',
			[
				'label'     => esc_html__( 'Ends In Text Spacing', 'martfury-addons' ),
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
					'{{WRAPPER}} .mf-product-deals-day .header-countdown .ends-text' => 'margin-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'heading_view_all_style_tab', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'show_heading_view_all',
			[
				'label'     => esc_html__( 'Show View All', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .cat-header .header-link' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-link a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .header-link a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_view_all_typography',
				'selector' => '{{WRAPPER}} .mf-product-deals-day .header-link a',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->end_controls_section();
	}

	/**
	 * Register the widget heading style controls.
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


		$this->add_responsive_control(
			'products_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-product-deals-day .products-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'products_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-product-deals-day .products-content',
			]
		);


		$this->add_control(
			'show_progress_bar',
			[
				'label'     => esc_html__( 'Show Progress Bar', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .deal-progress' => 'display: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'progress_bar_height',
			[
				'label'     => esc_html__( 'Height', 'martfury-addons' ),
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
					'{{WRAPPER}} .mf-product-deals-day ul.products li.product .deal-progress .progress-bar'   => 'height: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-product-deals-day ul.products li.product .deal-progress .progress-value' => 'height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'progress_bar_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .deal-progress .progress-bar' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'progress_bar_active_bg_color',
			[
				'label'     => esc_html__( 'Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .deal-progress .progress-value' => 'background-color: {{VALUE}};',
				],
			]
		);


		$this->end_controls_section();
	}

	/**
	 * Register the widget carousel style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_pagination_style_controls() {

		$this->start_controls_section(
			'section_style_pagination',
			[
				'label' => esc_html__( 'Pagination', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pagination_text_align',
			[
				'label'   => esc_html__( 'Text Align', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'center' => esc_html__( 'Center', 'martfury-addons' ),
					'left'   => esc_html__( 'Left', 'martfury-addons' ),
					'right'  => esc_html__( 'Right', 'martfury-addons' ),
				],
				'default' => 'center',
			]
		);

		$this->add_control(
			'pagination_spacing',
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
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'pagination_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_active_color',
			[
				'label'     => esc_html__( 'Active Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers.current' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers',
			]
		);

		$this->add_control(
			'pagination_items_spacing',
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
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers'            => 'padding-right: {{SIZE}}{{UNIT}};padding-left: 0;',
					'{{WRAPPER}} .mf-product-deals-day .numeric-navigation .page-numbers:last-child' => 'padding-right: 0;',
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


		$this->add_render_attribute( 'wrapper', 'class', [
			'mf-product-deals-day woocommerce mf-elementor-product-deals-grid',
            'deals-' . $settings['product_type']
		] );

		$now         = strtotime( current_time( 'Y-m-d H:i:s' ) );
		$expire_date = strtotime( '00:00 +1 day', $now );
		if ( $settings['product_type'] == 'week' ) {
			$expire_date = strtotime( '00:00 next monday', $now );
		} elseif ( $settings['product_type'] == 'month' ) {
			$expire_date = strtotime( '00:00 first day of next month', $now );
		}
		$expire      = $expire_date - $now;

		$product_deals =  Elementor::get_product_deals( $settings );
		if( empty( $product_deals ) ) {
		    return;
        }

		$this->add_render_attribute( 'title', 'class', 'cat-title' );
		$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $this->get_render_attribute_string( 'title' ), esc_html( $settings['title'] ) );
		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <div class="cat-header">
                <div class="header-content">
				<?php echo $title_html; ?>
                    <div class="header-countdown">
                        <span class="ends-text"><?php echo esc_html( $settings['ends_in'] ) ?></span>
                        <div class="martfury-countdown" data-expire="<?php echo esc_attr( $expire ); ?>"></div>
                    </div>
                </div>
                <div class="header-link">
					<?php echo $this->get_link_control( 'link', $settings['view_all_link'], $settings['view_all_text'] ); ?>
                </div>
            </div>
            <div class="products-content">
				<?php echo $product_deals; ?>
            </div>
        </div>
		<?php

	}


	/**
	 * Get the link control
	 *
	 * @return string.
	 */
	protected function get_link_control( $link_key, $url, $title ) {

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

		return sprintf( '<%1$s class="box-title" %2$s>%3$s</%1$s>', $attr, $this->get_render_attribute_string( $link_key ), esc_html( $title ) );
	}
}