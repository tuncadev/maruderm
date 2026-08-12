<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use MartfuryAddons\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Product_Deals_Carousel extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-product-deals-carousel';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Product Deals Carousel', 'martfury-addons' );
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
		$this->register_carousel_settings_controls();

		// Style
		$this->register_heading_style_controls();
		$this->register_products_style_controls();
		$this->register_carousel_style_controls();
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
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register the widget carousel settings controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_carousel_settings_controls() {

		$this->start_controls_section(
			'section_carousel_settings',
			[
				'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ),
			]
		);

		$this->add_responsive_control(
			'navigation',
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


		$this->add_responsive_control(
			'slidesToShow',
			[
				'label'   => esc_html__( 'Slides to Show', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'6' => esc_html__( '6', 'martfury-addons' ),
					'5' => esc_html__( '5', 'martfury-addons' ),
					'4' => esc_html__( '4', 'martfury-addons' ),
					'3' => esc_html__( '3', 'martfury-addons' ),
					'2' => esc_html__( '2', 'martfury-addons' ),
					'1' => esc_html__( '1', 'martfury-addons' ),
				],
				'default' => '5',
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'slidesToScroll',
			[
				'label'   => esc_html__( 'Slides to Scroll', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'5' => esc_html__( '5', 'martfury-addons' ),
					'4' => esc_html__( '4', 'martfury-addons' ),
					'3' => esc_html__( '3', 'martfury-addons' ),
					'6' => esc_html__( '6', 'martfury-addons' ),
					'2' => esc_html__( '2', 'martfury-addons' ),
					'1' => esc_html__( '1', 'martfury-addons' ),
				],
				'default' => '5',
				'frontend_available' => true,
			]
		);

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
					''       => esc_html__( 'Solid', 'martfury-addons' ),
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
	protected function register_carousel_style_controls() {

		$this->start_controls_section(
			'section_style_carousel',
			[
				'label' => esc_html__( 'Carousel', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'carousel_nav_settings' );


		$this->start_controls_tab( 'carousel_arrows', [ 'label' => esc_html__( 'Arrows', 'martfury-addons' ) ] );


		$this->add_control(
			'carousel_arrow_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .slick-arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'carousel_arrow_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day .slick-arrow:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'carousel_arrow_typography',
				'selector' => '{{WRAPPER}} .mf-product-deals-day .slick-arrow',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'carousel_dots', [ 'label' => esc_html__( 'Dots', 'martfury-addons' ) ] );

		$this->add_control(
			'carousel_dots_spacing',
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
					'{{WRAPPER}} .mf-product-deals-day ul.slick-dots' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'carousel_dots_width',
			[
				'label'     => esc_html__( 'Width', 'martfury-addons' ),
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
					'{{WRAPPER}} .mf-product-deals-day ul.slick-dots li button' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'carousel_dots_background',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day ul.slick-dots li button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'carousel_dots_active_background',
			[
				'label'     => esc_html__( 'Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-deals-day ul.slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Render icon box widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$nav        = $settings['navigation'];
		$nav_tablet = empty( $settings['navigation_tablet'] ) ? $nav : $settings['navigation_tablet'];
		$nav_mobile = empty( $settings['navigation_mobile'] ) ? $nav_tablet : $settings['navigation_mobile'];

		$this->add_render_attribute( 'wrapper', 'class', [
			'mf-product-deals-day mf-elementor-product-deals-carousel woocommerce mf-elementor-navigation',
			'navigation-' . $nav,
			'navigation-tablet-' . $nav_tablet,
			'navigation-mobile-' . $nav_mobile,
			'deals-' . $settings['product_type']
		] );


		$now         = strtotime( current_time( 'Y-m-d H:i:s' ) );
		$expire_date = strtotime( '00:00 +1 day', $now );
		if ( $settings['product_type'] == 'week' ) {
			$expire_date = strtotime( '00:00 next monday', $now );
		} elseif ( $settings['product_type'] == 'month' ) {
			$expire_date = strtotime( '00:00 first day of next month', $now );
		}
		$expire            = $expire_date - $now;
		$carousel_settings = [
			'slidesToScroll' 		=> $settings['slidesToScroll'],
			'slidesToScroll_tablet' => ! empty($settings['slidesToScroll_tablet']) ? $settings['slidesToScroll_tablet'] : 3,
			'slidesToScroll_mobile' => ! empty($settings['slidesToScroll_mobile']) ? $settings['slidesToScroll_mobile'] : 2,
			'slidesToShow'   		=> $settings['slidesToShow'],
			'slidesToShow_tablet'   => ! empty($settings['slidesToShow_tablet']) ? $settings['slidesToShow_tablet'] : 3,
			'slidesToShow_mobile'   => ! empty($settings['slidesToShow_mobile']) ? $settings['slidesToShow_mobile'] : 2,
			'infinite'       		=> $settings['infinite'],
			'autoplay'       		=> $settings['autoplay'],
			'autoplay_speed' 		=> $settings['autoplay_speed']
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );

		$settings['columns']    = intval( $settings['slidesToShow'] );
		$settings['pagination'] = 'no';
		$product_deals          = Elementor::get_product_deals( $settings );
		if ( empty( $product_deals ) ) {
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