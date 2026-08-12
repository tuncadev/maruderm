<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Group_Control_Image_Size;
use MartfuryAddons\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Post_Grid extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-post-grid';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Posts Grid', 'martfury-addons' );
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

		$this->start_controls_tabs( 'heading_content_settings' );

		$this->start_controls_tab( 'heading_tab', [ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ] );

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Title', 'martfury-addons' ),
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

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'title', [
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Category Name', 'martfury-addons' ),
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
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'links_tab', [ 'label' => esc_html__( 'Links', 'martfury-addons' ) ] );

		$this->add_control(
			'links_group',
			[
				'label'         => esc_html__( 'Links Group', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'title' => esc_html__( 'Category Name 1', 'martfury-addons' ),
						'link'  => '#'
					],
					[
						'title' => esc_html__( 'Category Name 2', 'martfury-addons' ),
						'link'  => '#'
					],
				],
				'title_field'   => '{{{ title }}}',
				'prevent_empty' => false
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'view_all_tab', [ 'label' => esc_html__( 'View All', 'martfury-addons' ) ] );

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
			'view_all_link', [
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

		$this->start_controls_section(
			'section_posts',
			[ 'label' => esc_html__( 'Posts', 'martfury-addons' ) ]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Total Posts', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'martfury-addons' ),
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
			'categories',
			[
				'label'       => esc_html__( 'Categories', 'martfury-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Elementor::get_taxonomy( 'category' ),
				'default'     => '',
				'multiple'    => true,
				'label_block' => true,
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

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'martfury-blog-grid',
				'separator' => 'before',
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
					'{{WRAPPER}} .martfury-latest-post .post-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-latest-post .post-header' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .martfury-latest-post .post-header' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .martfury-latest-post .post-header' => 'border-color: {{VALUE}};',
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
					'{{WRAPPER}} .martfury-latest-post .post-header' => 'border-style: {{VALUE}};',
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
					'{{WRAPPER}} .martfury-latest-post .section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
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
					'{{WRAPPER}} .martfury-latest-post .section-title' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .martfury-latest-post .section-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'links_title', [ 'label' => esc_html__( 'Links', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'show_post_links',
			[
				'label'     => esc_html__( 'Links', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'inline-block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'         => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'inline-block',
				'selectors' => [
					'{{WRAPPER}} .martfury-latest-post .extra-links li:not(.view-all-link)' => 'display: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'post_link_items_spacing',
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
					'{{WRAPPER}} .martfury-latest-post .extra-links li'                  => 'padding-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .martfury-latest-post .extra-links li:first-child'      => 'padding-left: 0',
					'.rtl {{WRAPPER}} .martfury-latest-post .extra-links li:last-child'  => 'padding-left: 0',
					'.rtl {{WRAPPER}} .martfury-latest-post .extra-links li:first-child' => 'padding-left: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'post_link_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .martfury-latest-post .extra-links li a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'post_link_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-latest-post .extra-links li a:hover' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'post_link_typography',
				'selector' => '{{WRAPPER}} .martfury-latest-post .extra-links li a',
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
			'martfury-latest-post blog-layout-grid'
		];

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		$header_tabs = [];
		$content     = [];
		$output      = [];

		if ( $settings['title'] ) {
			$header_tabs[] = sprintf( '<%1$s class="section-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $settings['title'] );
		}

		$tabs = $settings['links_group'];

		$header_tabs[] = '<ul class="extra-links">';
		if ( ! empty ( $tabs ) ) {
			foreach ( $tabs as $index => $item ) {
				$attr = [
					'class' => 'extra-link'
				];

				$link_key = 'link_' . $index;

				if ( isset( $item['title'] ) ) {
					$header_tabs[] = sprintf( '<li>%s</li>', $this->get_link_control( $link_key, $item['link'], $item['title'], $attr ) );
				}
			}
		}
		if ( ! empty( $settings['view_all_text'] ) ) {
			$header_tabs[] = '<li class="view-all-link">' . $this->get_link_control( 'view_all_link', $settings['view_all_link'], $settings['view_all_text'], 'all-link' ) . '</li>';
		}
		$header_tabs[] = '</ul>';

		$query_args = array(
			'posts_per_page'      => $settings['per_page'],
			'post_type'           => 'post',
			'ignore_sticky_posts' => true,
			'orderby'             => $settings['orderby'],
			'order'               => $settings['order'],
		);

		if ( ! empty( $settings['categories'] ) ) {
			$query_args['category_name'] = implode( ',', $settings['categories'] );
		}

		$query = new \WP_Query( $query_args );

		global $mf_post;
		$mf_post['css'] = 'col-md-4 col-sm-6 col-xs-6 post-item-grid';

		if ( $settings['columns'] == '4' ) {
			$mf_post['css'] = 'col-md-3 col-sm-6 col-xs-6 post-item-grid';
		}

		while ( $query->have_posts() ) : $query->the_post();
			global $mf_post_grid_elementor;

			$mf_post_grid_elementor['blog_layout'] = 'grid';

			// Image Size
			if ( $settings['image_size'] != 'custom' ) {
				$mf_post_grid_elementor['image_size'] = $settings['image_size'];
			} else {
				$mf_post_grid_elementor['image_size'] = [
					$settings['image_custom_dimension']['width'],
					$settings['image_custom_dimension']['height'],
				];
			}

			ob_start();
			get_template_part( 'template-parts/content', get_post_format() );
			$content[] = ob_get_clean();

		endwhile;
		wp_reset_postdata();

		$output[] = sprintf( '<div class="post-header">%s</div>', implode( ' ', $header_tabs ) );
		$output[] = sprintf( '<div class="post-list row">%s</div>', implode( ' ', $content ) );

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
	protected function get_link_control( $link_key, $url, $content, $attr = [] ) {
		$attr_default = [
			'href' => $url['url'] ? $url['url'] : '#',
		];

		if ( $url['is_external'] ) {
			$attr_default['target'] = '_blank';
		}

		if ( $url['nofollow'] ) {
			$attr_default['rel'] = 'nofollow';
		}

		$attr = wp_parse_args( $attr, $attr_default );

		$this->add_render_attribute( $link_key, $attr );

		return sprintf( '<a %1$s>%2$s</a>', $this->get_render_attribute_string( $link_key ), $content );
	}
}