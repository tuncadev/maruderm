<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Image Box Carousel widget
 */
class Image_Box_Carousel extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-image-box-carousel';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martfury - Image Box Carousel', 'martfury-addons' );
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
		$this->content_options();
		$this->carousel_options();
	}

	protected function section_style(){
		$this->heading_style();
		$this->content_style();
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
				'default'     => '',
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

	protected function content_options(){
		$this->start_controls_section(
			'content_sections',
			[ 'label' => esc_html__( 'Content', 'martfury-addons' ) ]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->start_controls_tabs( 'content_repeater' );

			$repeater->start_controls_tab( 'content', [ 'label' => esc_html__( 'Content', 'martfury-addons' ) ] );

				$repeater->add_control(
					'image',
					[
						'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
						'type'    => Controls_Manager::MEDIA,
					]
				);

				$repeater->add_control(
					'content_title', [
						'label'       => esc_html__( 'Title', 'martfury-addons' ),
						'type'        => Controls_Manager::TEXTAREA,
						'default'     => '',
						'label_block' => true,
					]
				);

				$repeater->add_control(
					'content_description', [
						'label'       => esc_html__( 'Description', 'martfury-addons' ),
						'type'        => Controls_Manager::TEXTAREA,
						'default'     => '',
						'label_block' => true,
					]
				);

				$repeater->add_control(
					'content_link', [
						'label'         => esc_html__( 'Link', 'martfury-addons' ),
						'type'          => Controls_Manager::URL,
						'placeholder'   => esc_html__( 'https://your-link.com', 'martfury-addons' ),
						'show_external' => true,
					]
				);

				$repeater->add_control(
					'show_title_in_background',
					[
						'label'     => esc_html__( 'Show Title in Background', 'martfury-addons' ),
						'type'      => Controls_Manager::SWITCHER,
						'label_off' => esc_html__( 'Off', 'martfury-addons' ),
						'label_on'  => esc_html__( 'On', 'martfury-addons' ),
						'default'   => ''
					]
				);

			$repeater->end_controls_tab();

			$repeater->start_controls_tab( 'content_style_repeater', [ 'label' => esc_html__( 'Style', 'martfury-addons' ) ] );

				$repeater->add_control(
					'custom_style',
					[
						'label'       => esc_html__( 'Custom', 'martfury-addons' ),
						'type'        => Controls_Manager::SWITCHER,
						'description' => esc_html__( 'Set custom style that will only affect this specific box.', 'martfury-addons' ),
					]
				);

				$repeater->add_control(
					'custom_image_heading',
					[
						'label' => esc_html__( 'Image', 'martfury-addons' ),
						'type' => Controls_Manager::HEADING,
						'separator' => 'before',
						'conditions' => [
							'terms' => [
								[
									'name'  => 'custom_style',
									'value' => 'yes',
								],
							],
						],
					]
				);

				$repeater->add_control(
					'custom_color',
					[
						'label'      => esc_html__( 'Color', 'martfury-addons' ),
						'type'       => Controls_Manager::COLOR,
						'selectors'  => [
							'{{WRAPPER}} .martfury-image-box-carousel {{CURRENT_ITEM}} .martfury-image-box-carousel__image-title' => 'color: {{VALUE}}',
						],
						'conditions' => [
							'terms' => [
								[
									'name'  => 'custom_style',
									'value' => 'yes',
								],
							],
						],
					]
				);

				$repeater->add_control(
					'custom_background_color',
					[
						'label'      => esc_html__( 'Background Color', 'martfury-addons' ),
						'type'       => Controls_Manager::COLOR,
						'selectors'  => [
							'{{WRAPPER}} .martfury-image-box-carousel {{CURRENT_ITEM}} .martfury-image-box-carousel__image' => 'background-color: {{VALUE}}',
						],
						'conditions' => [
							'terms' => [
								[
									'name'  => 'custom_style',
									'value' => 'yes',
								],
							],
						],
					]
				);

			$repeater->end_controls_tab();

		$repeater->end_controls_tabs();

		$this->add_control(
			'content_box',
			[
				'label'         => 'Box',
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'content_title' => esc_html__( 'Box 1', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 2', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 3', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 4', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 5', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 6', 'martfury-addons' ),
					],
					[
						'content_title' => esc_html__( 'Box 7', 'martfury-addons' ),
					],
				],
				'title_field'   => '{{{ content_title }}}',
				'prevent_empty' => false,

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
				'default' => 'none',
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
				'default' => '6',
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
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_title_heading',
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
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_title',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-title',
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
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-button a, {{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-button span' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_button',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-button a, {{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-button span',
			]
		);

		$this->add_responsive_control(
			'heading_button_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__heading-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'    => [],
			]
		);

		$this->end_controls_section();
	}

	protected function content_style(){
		$this->start_controls_section(
			'content_style',
			[
				'label' =>esc_html__( 'Content', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				'default'   => 'full',
				'separator' => 'none',
			]
		);

		$this->add_control(
			'content_title_heading',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'content_title_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__content-link' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_title_link',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__content-link',
			]
		);

		$this->add_responsive_control(
			'content_title_spacing',
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
					'{{WRAPPER}} .martfury-image-box-carousel .martfury-image-box-carousel__content-title' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'content_title_bg',
			[
				'label' => esc_html__( 'Title in Background', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'content_title_bg_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .martfury-image-box-carousel__image-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_title_bg',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel__image-title',
			]
		);

		$this->add_control(
			'content_description',
			[
				'label' => esc_html__( 'Description', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'content_description_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .martfury-image-box-carousel__content-description' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_description_text',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel__content-description',
			]
		);

		$this->add_responsive_control(
			'content_description_spacing',
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
					'{{WRAPPER}} .martfury-image-box-carousel__content-description' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
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
			'arrows_bgcolor',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-image-box-carousel .slick-arrow' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'arrows_hover_bgcolor',
			[
				'label'     => esc_html__( 'Hover Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-image-box-carousel .slick-arrow:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'arrows_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-image-box-carousel .slick-arrow' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'arrows_hover_color',
			[
				'label'     => esc_html__( 'Hover Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-image-box-carousel .slick-arrow:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'arrows_typography',
				'selector' => '{{WRAPPER}} .martfury-image-box-carousel .slick-arrow',
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
					'{{WRAPPER}} .martfury-image-box-carousel .slick-dots' => 'margin-top: {{SIZE}}{{UNIT}}',
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
					'{{WRAPPER}} .martfury-image-box-carousel .slick-dots li button' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
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
					'{{WRAPPER}} .martfury-image-box-carousel .slick-dots li button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
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
					'{{WRAPPER}} .martfury-image-box-carousel .slick-dots li.slick-active button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
					'{{WRAPPER}} .martfury-image-box-carousel .slick-dots li:hover button' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
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
			'martfury-image-box-carousel mf-elementor-navigation',
			'navigation-' . $nav,
			'navigation-tablet-' . $nav_tablet,
			'navigation-mobile-' . $nav_mobile,
		];

		$this->add_render_attribute( 'box', 'class', $classes );

		$carousel_settings = [
			'autoplay'       => $settings['autoplay'],
			'infinite'       => $settings['infinite'],
			'autoplay_speed' => intval( $settings['autoplay_speed'] ),
			'speed'          => intval( $settings['speed'] ),
			'slidesToShow'   => intval( $settings['slidesToShow'] ),
			'slidesToScroll' => intval( $settings['slidesToScroll'] ),
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );
		?>
        <div <?php echo $this->get_render_attribute_string( 'box' ); ?>>
			<div class="martfury-image-box-carousel__heading">
				<?php if( $settings['title'] ) : ?>
					<div class="martfury-image-box-carousel__heading-title"><?php echo wp_kses_post( $settings['title'] ); ?></div>
				<?php endif; ?>

				<?php if( $settings['button_text'] ) : ?>
					<div class="martfury-image-box-carousel__heading-button"><?php echo $this->get_link_control( 'button', $settings['button_link'], $settings['button_text'], '' ); ?></div>
				<?php endif; ?>
				<div class="slick-arrows">
					<span class="icon-chevron-left slick-prev-arrow"></span>
					<span class="icon-chevron-right slick-next-arrow"></span>
				</div>
			</div>

			<div class="martfury-image-box-carousel__wrapper" <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
				<?php foreach( $settings['content_box'] as $box ) : ?>
					<div class="martfury-image-box-carousel__content">
						<div class="martfury-image-box-carousel__image">
							<a href="<?php echo esc_url( $box['content_link']['url'] ); ?>">
								<?php if( $box['image']['url'] ) : ?>
									<img src="<?php echo esc_url( $box['image']['url'] ); ?>" alt="<?php echo esc_attr( $box['content_title'] ); ?>" />
								<?php endif; ?>
								<?php if( $box['show_title_in_background'] && $box['content_title'] ) : ?>
									<div class="martfury-image-box-carousel__image-title"><?php echo wp_kses_post( $box['content_title'] ); ?></div>
								<?php endif; ?>
							</a>
						</div>
						<?php
							if ( $box['content_title'] ) {
								echo '<div class="martfury-image-box-carousel__content-title">';
								echo $this->get_link_control( 'content_title', $box['content_link'], $box['content_title'], 'martfury-image-box-carousel__content-link' );
								echo '</div>';
							}
						?>
						<?php echo $box['content_description'] ? '<div class="martfury-image-box-carousel__content-description">'. $box['content_description'] .'</div>' : ''; ?>
					</div>
				<?php endforeach; ?>
			</div>
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