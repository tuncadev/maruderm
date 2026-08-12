<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use MartfuryAddons\Elementor_AjaxLoader;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Banner Small widget
 */
class Brand_Images_Carousel extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'mf-brand-images-carousel';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martury - Brand Images Carousel', 'martfury-addons' );
	}


	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-banner';
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

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Product Brand Images', 'martfury-addons' ),
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
			'view_all_text',
			[
				'label'       => esc_html__( 'View All', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'View All', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'view_all_link', [
				'label'         => esc_html__( 'View All Link', 'martfury-addons' ),
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
		$this->end_controls_section();
		$this->start_controls_section(
			'section_brands',
			[ 'label' => esc_html__( 'Brands', 'martfury-addons' ) ]
		);
		$this->add_control(
			'number',
			[
				'label'       => esc_html__( 'Numbers', 'martfury-addons' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 5,
				'placeholder' => esc_html__( 'Enter your number', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Order By', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'name'       => esc_html__( 'Title', 'martfury-addons' ),
					'count'      => esc_html__( 'Count', 'martfury-addons' ),
					'menu_order' => esc_html__( 'Brand Order', 'martfury-addons' ),
				],
				'default' => 'name',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'order',
			[
				'label'     => esc_html__( 'Order', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default'   => 'asc',
				'toggle'    => false,
				'condition' => [
					'orderby' => [ 'name', 'count' ],
				],
			]
		);


		$this->end_controls_section();

		// Slider Settings
		$this->start_controls_section(
			'section_carousel_settings',
			[ 'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ) ]
		);

		$this->add_responsive_control(
			'navigation',
			[
				'label'   => esc_html__( 'Navigation', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'arrows' => esc_html__( 'Arrows', 'martfury-addons' ),
					'none'   => esc_html__( 'None', 'martfury-addons' ),
				],
				'default' => 'arrows',
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
				'default' => '5',
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
				'default' => '5',
			]
		);

		$this->add_control(
			'infinite',
			[
				'label'     => esc_html__( 'Infinite Loop', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => 'yes'
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'     => esc_html__( 'Autoplay', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'martfury-addons' ),
				'label_on'  => esc_html__( 'On', 'martfury-addons' ),
				'default'   => 'no'
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

		$this->start_controls_section(
			'section_style_brands',
			[
				'label' => esc_html__( 'Brands', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'brands_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [],
				'selectors'  => [
					'{{WRAPPER}} .mf-brand-images-carousel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_brands_heading',
			[
				'label' => esc_html__( 'Heading', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_spacing',
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
					'{{WRAPPER}} .mf-brand-images-carousel .brands-header' => 'padding-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_title',
			[
				'label'     => esc_html__( 'Title', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-brand-images-carousel .brands-header .brand-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title_typography',
				'selector' => '{{WRAPPER}} .mf-brand-images-carousel .brands-header .brand-title',
			]
		);

		$this->add_control(
			'heading_title_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-brand-images-carousel .brands-header' => 'border-bottom-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all',
			[
				'label'     => esc_html__( 'View All', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading_view_all_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-brand-images-carousel .brands-header .all-link' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_view_all_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-brand-images-carousel .brands-header a.all-link:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_view_all_typography',
				'selector' => '{{WRAPPER}} .mf-brand-images-carousel .brands-header .all-link',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_brand_carousel',
			[
				'label' => esc_html__( 'Images Carousel', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'brand_images_spacing',
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
					'{{WRAPPER}} .mf-brand-images-carousel .images-list' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
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
			'elementor-brand-images-carousel mf-brand-images-carousel',
		] );

		$carousel_settings = [
			'autoplay'       => $settings['autoplay'],
			'infinite'       => $settings['infinite'],
			'autoplay_speed' => intval( $settings['autoplay_speed'] ),
			'speed'          => intval( $settings['speed'] ),
			'slidesToShow'   => intval( $settings['slidesToShow'] ),
			'slidesToScroll' => intval( $settings['slidesToScroll'] ),
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );
		$title_html = $settings['title']  ? sprintf( '<%1$s class="brand-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $settings['title'] ) : '';
		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <div class="brands-header">
				<?php echo $title_html; ?>
				<?php if ( ! empty( $settings['view_all_text'] ) ) { ?>
					<?php
					echo Elementor_AjaxLoader::get_link_control( $settings['view_all_link'], $settings['view_all_text'], 'all-link' );
					?>

				<?php } ?>
            </div>
            <div class="images-list">
				<?php
				$terms = get_terms( array(
					'taxonomy' => 'product_brand',
					'number'   => $settings['number'],
					'orderby'  => $settings['orderby'],
					'order'    => $settings['order'],
				) );

				foreach ( $terms as $term ) {
					$thumb_id         = get_term_meta( $term->term_id, 'brand_thumbnail_id', true );
					$shop_catalog_img = wp_get_attachment_image_src( $thumb_id, 'full' );
					$term_link        = get_term_link( $term, 'product_cat' );

					if ( $thumb_id ) {

						echo sprintf( '<div class="image-item">
                                            <a href="%s"><img src="%s" alt="%s"/></a>
                                         </div>',
							esc_url( $term_link ),
							$shop_catalog_img[0],
							esc_html( $term->name )
						);

					}
				}
				?>
            </div>
        </div>
		<?php
	}
}