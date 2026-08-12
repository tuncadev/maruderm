<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Recently_Viewed_Products_Carousel extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-recently-viewed-products-carousel';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Recently Viewed Products Carousel', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-carousel';
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
		$this->section_options();
		$this->section_style();
	}

	protected function section_options(){
		$this->heading_options();
		$this->product_options();
		$this->carousel_options();
	}

	protected function section_style(){
		$this->heading_style();
		$this->carousel_style();
	}

	protected function heading_options(){
		$this->start_controls_section(
			'heading_sections',
			[ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'The Title', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => esc_html__( 'Button Text', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Button', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'button_link', [
				'label'         => esc_html__( 'Button Link', 'martfury-addons' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
				'show_external' => true,
			]
		);

		$this->end_controls_section();
	}

	protected function product_options() {
		$this->start_controls_section(
			'section_product',
			[ 'label' => esc_html__( 'Products', 'martfury-addons' ) ]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Total Products', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 2,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->end_controls_section();
	}

	protected function carousel_options(){
		$this->start_controls_section(
			'section_slider_settings',
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
				'default' => 'dots',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'slidesToShow',
			[
				'label'   => esc_html__( 'Slides to Show', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'5' => esc_html__( '5', 'martfury-addons' ),
					'4' => esc_html__( '4', 'martfury-addons' ),
					'3' => esc_html__( '3', 'martfury-addons' ),
					'6' => esc_html__( '6', 'martfury-addons' ),
					'7' => esc_html__( '7', 'martfury-addons' ),
				],
				'default' => '4',
			]
		);

		$this->add_control(
			'slidesToScroll',
			[
				'label'   => esc_html__( 'Slides to Scroll', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'5' => esc_html__( '5', 'martfury-addons' ),
					'4' => esc_html__( '4', 'martfury-addons' ),
					'3' => esc_html__( '3', 'martfury-addons' ),
					'6' => esc_html__( '6', 'martfury-addons' ),
					'7' => esc_html__( '7', 'martfury-addons' ),
					'2' => esc_html__( '2', 'martfury-addons' ),
					'1' => esc_html__( '1', 'martfury-addons' ),
				],
				'default' => '1',
			]
		);

		$this->add_control(
			'infinite',
			[
				'label'     => esc_html__( 'Infinite Loop', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => ''
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'     => esc_html__( 'Autoplay', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => ''
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'   => esc_html__( 'Autoplay Speed (in ms)', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3000,
				'min'     => 100,
				'step'    => 100,
			]
		);

		$this->add_control(
			'speed',
			[
				'label'       => esc_html__( 'Speed', 'martfury-addons' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 800,
				'min'         => 100,
				'step'        => 100,
				'description' => esc_html__( 'Slide animation speed (in ms)', 'martfury-addons' ),
			]
		);

		$this->end_controls_section();
	}

	protected function heading_style(){
		$this->start_controls_section(
			'heading_style',
			[
				'label' =>esc_html__( 'Heading', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'heading_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel__heading' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_title',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'heading_title_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel__heading-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title',
				'selector' => '{{WRAPPER}} .mf-product-recently-viewed-carousel__heading-title',
			]
		);

		$this->add_control(
			'heading_button',
			[
				'label' => esc_html__( 'Button', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'heading_button_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel__heading-button a, {{WRAPPER}} .mf-product-recently-viewed-carousel__heading-button span' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_button',
				'selector' => '{{WRAPPER}} .mf-product-recently-viewed-carousel__heading-button a, {{WRAPPER}} .mf-product-recently-viewed-carousel__heading-button span',
			]
		);

		$this->add_responsive_control(
			'heading_button_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel__heading-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'    => [],
			]
		);

		$this->end_controls_section();
	}

	protected function carousel_style(){
		$this->start_controls_section(
			'carousel_style',
			[
				'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'carousel_settings' );

		$this->start_controls_tab( 'carousel_arrows_style', [ 'label' => esc_html__( 'Arrows', 'martfury-addons' ) ] );

		$this->add_control(
			'arrows_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-arrow' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'arrows_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-arrow:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'arrows_typography',
				'selector' => '{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-arrow',
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'carousel_dots_style', [ 'label' => esc_html__( 'Dots', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'dots_spacing',
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
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'dots_spacing_items',
			[
				'label'     => esc_html__( 'Spacing Items', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots li' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'dots_width',
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
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots li button' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'dots_background',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots li button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dots_active_background',
			[
				'label'     => esc_html__( 'Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .mf-product-recently-viewed-carousel .slick-dots li:hover button' => 'background-color: {{VALUE}};',
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

		$classes = [
			'mf-product-recently-viewed-carousel woocommerce mf-elementor-navigation',
			'navigation-' . $nav,
			'navigation-tablet-' . $nav_tablet,
			'navigation-mobile-' . $nav_mobile,
		];

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		$carousel_settings = [
			'autoplay'          => $settings['autoplay'],
			'infinite'          => $settings['infinite'],
			'autoplay_speed'    => intval( $settings['autoplay_speed'] ),
			'speed'             => intval( $settings['speed'] ),
			'slidesToShow'      => intval( $settings['slidesToShow'] ),
			'slidesToScroll'    => intval( $settings['slidesToScroll'] ),
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );

		$viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) : [ ];
		$viewed_products = array_reverse( array_filter( array_map( 'absint', $viewed_products ) ) );

		if( empty( $viewed_products ) ) {
			return;
		}

		$output = [ ];

		$per_page   = intval( $settings['per_page'] );
		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'post__in'            => $viewed_products,
			'orderby'             => 'post__in',
		);

		$products = new \WP_Query( $query_args );

		if ( ! $products->have_posts() ) {
			return '';
		}

		global $woocommerce_loop;

		$woocommerce_loop['columns'] = intval( $settings['slidesToShow'] );

		ob_start();

		if ( $products->have_posts() ) :
			woocommerce_product_loop_start();
			while ( $products->have_posts() ) : $products->the_post(); $product = wc_get_product( get_the_ID() );
				?>
				<li class="product">
					<div class="product-inner">
						<div class="mf-product-thumbnail">
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo $product->get_image( 'thumbnail' ); ?></a>
						</div>

						<div class="mf-product-content">
							<span class="price"><?php echo $product->get_price_html(); ?></span>
							<h2 class="woo-loop-product__title">
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo $product->get_title(); ?></a>
							</h2>
							<div class="rating-item"><?php echo wc_get_rating_html( $product->get_average_rating() ); ?></div>
						</div>
					</div>
				</li>
				<?php
			endwhile; // end of the loop.

			woocommerce_product_loop_end();
		endif;

		wp_reset_postdata();

		$output[] = ob_get_clean();
		?>
			<div class="mf-product-recently-viewed-carousel__heading">
				<?php if( $settings['title'] ) : ?>
					<div class="mf-product-recently-viewed-carousel__heading-title"><?php echo wp_kses_post( $settings['title'] ); ?></div>
				<?php endif; ?>

				<?php if( $settings['button_text'] ) : ?>
					<div class="mf-product-recently-viewed-carousel__heading-button"><?php echo $this->get_link_control( 'button', $settings['button_link'], $settings['button_text'], '' ); ?></div>
				<?php endif; ?>
			</div>
			<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
				<?php echo implode( '', $output ); ?>
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