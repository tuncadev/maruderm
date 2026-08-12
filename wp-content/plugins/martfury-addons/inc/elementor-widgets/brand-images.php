<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Banner Small widget
 */
class Brand_Images extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'mf-brand-images';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martury - Brand Images Grid', 'martfury-addons' );
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
			'section_brand_heading',
			[ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
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

		$this->end_controls_section();


		$this->start_controls_section(
			'section_brands',
			[ 'label' => esc_html__( 'Brands', 'martfury-addons' ) ]
		);
		$this->add_control(
			'brand_title',
			[
				'label'     => esc_html__( 'Brand Title', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => 'no',
				'options'   => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
			]
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
				'label'   => esc_html__( 'Order', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'asc'  => esc_html__( 'Ascending', 'martfury-addons' ),
					'desc' => esc_html__( 'Descending', 'martfury-addons' ),
				],
				'default' => 'asc',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'       => esc_html__( 'Columns', 'martfury-addons' ),
				'type'        => Controls_Manager::NUMBER,
				'max'         => 10,
				'default'     => 5,
				'placeholder' => esc_html__( 'Enter your number', 'martfury-addons' ),
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'separator' => 'before',
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_brand_heading',
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
					'{{WRAPPER}} .mf-elementor-brand-images .brand-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'heading_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brand-images .brand-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brand-images .brand-title' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-brand-images .brand-title',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_brand_item',
			[
				'label' => esc_html__( 'Brand Item', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'brand_item_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brand-images .image-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'brand_item_border_style',
			[
				'label'     => esc_html__( 'Border Style', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       => __( 'None', 'elementor' ),
					'solid'  => esc_html__( 'Solid', 'Border Control', 'elementor' ),
					'double' => esc_html__( 'Double', 'Border Control', 'elementor' ),
					'dotted' => esc_html__( 'Dotted', 'Border Control', 'elementor' ),
					'dashed' => esc_html__( 'Dashed', 'Border Control', 'elementor' ),
					'groove' => esc_html__( 'Groove', 'Border Control', 'elementor' ),
				],
				'selectors' => [
					'{{SELECTOR}} .martfury-images-grid.mf-elementor-brand-images .image-item' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'brand_item_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{SELECTOR}} .mf-elementor-brand-images .image-item' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs( 'brand_items_tabs' );

		$this->start_controls_tab( 'brand_items_image', [ 'label' => esc_html__( 'Image', 'martfury-addons' ) ] );

		$this->add_responsive_control(
			'brand_image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-brand-images .image-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'brand_items_title', [ 'label' => esc_html__( 'Title', 'martfury-addons' ) ] );

		$this->add_control(
			'brand_title_spacing',
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
					'{{WRAPPER}} .mf-elementor-brand-images .images-list .b-title' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'brand_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brand-images .images-list .b-title a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'brand_title_hover_color',
			[
				'label'     => esc_html__( 'Hover Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-brand-images .images-list .b-title a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'brand_title_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-brand-images .images-list .b-title a',
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

		$this->add_render_attribute( 'wrapper', 'class', [
			'mf-elementor-brand-images',
			'mf-brand-images',
			'martfury-images-grid'
		] );

		$title_html = $settings['title']  ? sprintf( '<%1$s class="brand-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $settings['title'] ) : '';
		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<?php echo $title_html; ?>
            <div class="images-list columns-<?php echo $settings['columns']; ?>">
				<?php
				$terms = get_terms( array(
					'taxonomy' => 'product_brand',
					'number'   => $settings['number'],
					'orderby'  => $settings['orderby'],
					'order'    => $settings['order'],
				) );

				foreach ( $terms as $term ) {
					$thumb_id          = get_term_meta( $term->term_id, 'brand_thumbnail_id', true );
					$settings['image'] = array( 'id' => $thumb_id, 'url' => '' );
					$btn_image         = Group_Control_Image_Size::get_attachment_image_html( $settings );
					$term_link         = get_term_link( $term, 'product_cat' );

					$title = '';
					if ( $settings['brand_title'] == 'yes' ) {
						$title = sprintf( '<h4 class="b-title"><a href="%s">%s</a></h4>', esc_url( $term_link ), $term->name );
					}

					echo sprintf( '<div class="image-item">
                                            <a href="%s">%s</a>%s
                                         </div>',
						esc_url( $term_link ),
						$btn_image,
						$title
					);

				}
				?>
            </div>
        </div>
		<?php
	}
}