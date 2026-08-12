<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Utils;
use Elementor\Widget_Base;
use MartfuryAddons\Elementor;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Category_Box extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-category-box';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Category Box', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-image-box';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'martfury' ];
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
			'title_tab',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category Name', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'title_size',
			[
				'label'   => __( 'Title HTML Tag', 'martfury-addons' ),
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

		// Group

		$this->start_controls_tab(
			'links_group_tab',
			[
				'label' => esc_html__( 'Links Group', 'martfury-addons' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'title', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Title', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$repeater->add_control(
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

		$this->add_control(
			'links_group',
			[
				'label'         => '',
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'title' => esc_html__( 'New Arrivals', 'martfury-addons' ),
						'link'  => '#'
					],
					[
						'title' => esc_html__( 'Best Seller', 'martfury-addons' ),
						'link'  => '#'
					]
				],
				'title_field'   => '{{{ title }}}',
				'prevent_empty' => false
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Content', 'martfury-addons' ) ]
		);

		$this->start_controls_tabs( 'tabs_content' );
		$this->start_controls_tab(
			'banner_tab',
			[
				'label' => esc_html__( 'Banner', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'image_type',
			[
				'label'   => esc_html__( 'Image Type', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => esc_html__( 'Upload', 'martfury-addons' ),
					'2' => esc_html__( 'URL', 'martfury-addons' ),
				],
				'default' => '1',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'image',
			[
				'label'     => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => 'https://via.placeholder.com/654x295/f8f8f8?text=654x295+Banner',
				],
				'condition' => [
					'image_type' => '1',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'separator' => 'none',
				'condition' => [
					'image_type' => '1',
				],
			]
		);
		$this->add_control(
			'banner_link', [
				'label'         => esc_html__( 'Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
				'default'       => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				],
				'condition'     => [
					'image_type' => '1',
				],
			]
		);

		$this->add_control(
			'image_url', [
				'label'         => esc_html__( 'Image URL', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'Enter your image url', 'martfury-addons' ),
				'show_external' => false,
				'default'       => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
				'condition'     => [
					'image_type' => '2',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'product_cats_tab',
			[
				'label' => esc_html__( 'Categories', 'martfury-addons' ),
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

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		/**
		 * Tab Style
		 */

		// Heading
		$this->start_controls_section(
			'section_category_box_style',
			[
				'label' => esc_html__( 'Category Box', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'category_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-category-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'category_box_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$this->end_controls_section();

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
					'{{WRAPPER}} .mf-category-box .cat-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .mf-category-box .cat-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'default'   => 'rgb(225,225,225)',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header' => 'border-color: {{VALUE}};',
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
					'{{WRAPPER}} .mf-category-box .cat-header' => 'border-style: {{VALUE}};',
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

		$this->start_controls_tabs( 'heading_style_settings' );

		$this->start_controls_tab( 'heading_title', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );


		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-category-box .cat-header .cat-name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .mf-category-box .cat-header .cat-name, {{WRAPPER}} .mf-category-box .cat-header .cat-name .cat-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header .cat-name .cat-title:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .mf-category-box .cat-header .cat-name .cat-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'links_title', [ 'label' => esc_html__( 'Links', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'show_product_links',
			[
				'label'     => esc_html__( 'Links', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'inline-block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'         => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'inline-block',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header .extra-links' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'product_link_items_spacing',
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
					'{{WRAPPER}} .mf-category-box .cat-header .extra-links li'                   => 'padding-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-category-box .cat-header .extra-links li:first-child'       => 'padding-left: 0',
					'.rtl {{WRAPPER}} .mf-category-box   .cat-header .extra-links li:last-child' => 'padding-left: 0',
					'.rtl {{WRAPPER}} .mf-category-box  .cat-header .extra-links li:first-child' => 'padding-left: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'product_link_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header .extra-links li a, {{WRAPPER}} .mf-category-box .cat-header .extra-links li' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'product_link_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .cat-header .extra-links li a:hover' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'product_link_typography',
				'selector' => '{{WRAPPER}} .mf-category-box .cat-header .extra-links li',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// Content

		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__( 'Content', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);


		$this->add_responsive_control(
			'content_style_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-category-box .sub-categories' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'after',
			]
		);

		$this->add_control(
			'banner_content_heading',
			[
				'label' => esc_html__( 'Banner', 'martfury-addons' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'show_banner_content',
			[
				'label'     => esc_html__( 'Show Banner', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Yes', 'martfury-addons' ),
					'none'  => esc_html__( 'No', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .col-banner' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'categories_content_heading',
			[
				'label'     => esc_html__( 'Category Item', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'category_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'category_border_style',
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
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'category_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'   => [
					'top'    => '1',
					'right'  => '1',
					'bottom' => '1',
					'left'   => '1',
				],
			]
		);
		$this->add_control(
			'category_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'category_hover_border_color',
			[
				'label'     => esc_html__( 'Hover Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item:hover' => 'border-color: {{VALUE}};',
				],
				'separator' => 'after',
			]
		);

		$this->start_controls_tabs( 'category_item_style_settings' );

		$this->start_controls_tab( 'category_item_title', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_control(
			'category_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'category_title_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-item:hover .term-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'category_title_typography',
				'selector' => '{{WRAPPER}} .mf-category-box .sub-categories .term-name',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'category_item_count', [ 'label' => esc_html__( 'Count', 'martfury-addons' ) ] );

		$this->add_control(
			'category_count_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-category-box .sub-categories .term-name .count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'category_count_typography',
				'selector' => '{{WRAPPER}} .mf-category-box .sub-categories .term-name .count',
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

		$classes = [
			'mf-category-box'
		];

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		$header_tabs = [];
		if ( ! empty( $settings['title'] ) ) {
			$header_tabs[] = sprintf( '<%1$s class="cat-name">%2$s</%1$s>',\Elementor\Utils::validate_html_tag( $settings['title_size'] ), $this->get_link_control( 'link', $settings['link'], $settings['title'], 'cat-title' ) );
		}

		$btn_settings = $settings['links_group'];

		$links_group = [];

		if ( ! empty ( $btn_settings ) ) {
			foreach ( $btn_settings as $index => $item ) {
				$link_key = 'link_' . $index;

				$link = $this->get_link_control( $link_key, $item['link'], $item['title'], 'extra-link' );

				$links_group[] = sprintf( '<li>%s</li>', $link );
			}
		}

		$header_tabs[] = ! empty( $links_group ) ? sprintf( '<ul class="extra-links">%s</ul>', implode( ' ', $links_group ) ) : '';

		$image = $banner_html = '';


		if ( $settings['image_type'] == '1' && $settings['image'] ) {
			$image = Group_Control_Image_Size::get_attachment_image_html( $settings );
		}

		if ( $settings['image_type'] == '2' && $settings['image_url'] ) {
			$image = sprintf( '<img alt="" src="%s">', esc_url( $settings['image_url'] ) );
		}

		if ( $image ) {
			$banner_html = $this->get_link_control( 'banner_link', $settings['banner_link'], $image, 'cat-banner' );
		}

		$term_list = [];
		if ( $settings['product_cats'] ) {
			$term_list [] = '<div class="sub-categories">';
			if ( $banner_html ) {
				$term_list[] = sprintf(
					'<div class="col-md-7 col-sm-6 col-xs-12 col-banner">%s</div>',
					$banner_html
				);
			}

			$classes = 'col-mf-5 col-sm-3 col-xs-6';

			$cats = $settings['product_cats'];
			foreach ( $cats as $cat ) {
				$terms = get_terms(
					array(
						'taxonomy' => 'product_cat',
						'slug'     => $cat,
						'number'   => 1,
					)
				);

				if ( ! is_wp_error( $terms ) && $terms ) {
					$category             = $terms[0];
					$term_id              = $category->term_id;
					$thumbnail_id         = absint( get_term_meta( $term_id, 'thumbnail_id', true ) );
					$small_thumbnail_size = apply_filters( 'martfury_category_box_thumbnail_size', 'shop_catalog' );

					$image_html = '';
					if ( $thumbnail_id ) {
						$image_html = martfury_get_image_html( $thumbnail_id, $small_thumbnail_size );
					}

					$count = $category->count;

					$item_text = esc_html__( 'Items', 'martfury-addons' );
					if ( $count <= 1 ) {
						$item_text = esc_html__( 'Item', 'martfury-addons' );
					}

					$count .= ' ' . apply_filters( 'martfury_category_box_items_text', $item_text, $count );

					$term_list[] = sprintf(
						'<div class="%s col-cat"><a class="term-item" href="%s">%s <h3 class="term-name">%s <span class="count">%s</span></h3></a></div>',
						esc_attr( $classes ),
						esc_url( get_term_link( $term_id, 'product_cat' ) ),
						$image_html,
						$category->name,
						$count
					);
				}

			}

			$term_list [] = '</div>';
		}

		$output[] = sprintf(
			'<div class="cat-header">' .
			'%s' .
			'</div>' .
			'%s',
			implode( '', $header_tabs ),
			implode( ' ', $term_list )
		);

		echo sprintf(
			'<div %s>%s</div>',
			$this->get_render_attribute_string( 'wrapper' ),
			implode( '', $output )
		);
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