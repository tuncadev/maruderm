<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Slides extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-slides';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martfury - Slides', 'martfury-addons' );
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
			'imagesloaded',
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
			'section_slides',
			[
				'label' => esc_html__( 'Slides', 'martfury-addons' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->start_controls_tabs( 'slides_repeater' );

		$repeater->start_controls_tab( 'background', [ 'label' => esc_html__( 'Background', 'martfury-addons' ) ] );

		$repeater->add_control(
			'background_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f4f5f5',
				'selectors' => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-bg' => 'background-color: {{VALUE}}',
				],
			]
		);

		$repeater->add_responsive_control(
			'background_image',
			[
				'label'     => _x( 'Image', 'Background Control', 'martfury-addons' ),
				'type'      => Controls_Manager::MEDIA,
				'selectors' => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-bg' => 'background-image: url({{URL}})',
				],
			]
		);

		$repeater->add_responsive_control(
			'background_size',
			[
				'label'     => _x( 'Background Size', 'Background Control', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					'cover'   => _x( 'Cover', 'Background Control', 'martfury-addons' ),
					'contain' => _x( 'Contain', 'Background Control', 'martfury-addons' ),
					'auto'    => _x( 'Auto', 'Background Control', 'martfury-addons' ),
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-bg' => 'background-size: {{VALUE}}',
				],
			]
		);

		$repeater->add_responsive_control(
			'background_position',
			[
				'label'     => esc_html__( 'Background Position', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''              => esc_html__( 'Default', 'martfury-addons' ),
					'left top'      => esc_html__( 'Left Top', 'martfury-addons' ),
					'left center'   => esc_html__( 'Left Center', 'martfury-addons' ),
					'left bottom'   => esc_html__( 'Left Bottom', 'martfury-addons' ),
					'right top'     => esc_html__( 'Right Top', 'martfury-addons' ),
					'right center'  => esc_html__( 'Right Center', 'martfury-addons' ),
					'right bottom'  => esc_html__( 'Right Bottom', 'martfury-addons' ),
					'center top'    => esc_html__( 'Center Top', 'martfury-addons' ),
					'center center' => esc_html__( 'Center Center', 'martfury-addons' ),
					'center bottom' => esc_html__( 'Center Bottom', 'martfury-addons' ),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-bg' => 'background-position: {{VALUE}};',
				],
			]
		);

		$repeater->add_responsive_control(
			'background_position_custom',
			[
				'label'              => esc_html__( 'Custom Background Position', 'martfury-addons' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => [ '%', 'px' ],
				'allowed_dimensions' => [ 'top', 'left' ],
				'default'            => [],
				'selectors'          => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-bg' => 'background-position: {{LEFT}}{{UNIT}} {{TOP}}{{UNIT}};',
				],
			]
		);

		$repeater->add_control(
			'background_ken_burns',
			[
				'label'      => esc_html__( 'Ken Burns Effect', 'martfury-addons' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => '',
				'separator'  => 'before',
				'conditions' => [
					'terms' => [
						[
							'name'     => 'background_image[url]',
							'operator' => '!=',
							'value'    => '',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'zoom_direction',
			[
				'label'      => esc_html__( 'Zoom Direction', 'martfury-addons' ),
				'type'       => Controls_Manager::SELECT,
				'default'    => 'in',
				'options'    => [
					'in'  => esc_html__( 'In', 'martfury-addons' ),
					'out' => esc_html__( 'Out', 'martfury-addons' ),
				],
				'conditions' => [
					'terms' => [
						[
							'name'     => 'background_ken_burns',
							'operator' => '!=',
							'value'    => '',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'background_overlay',
			[
				'label'      => esc_html__( 'Background Overlay', 'martfury-addons' ),
				'type'       => Controls_Manager::SWITCHER,
				'default'    => '',
				'separator'  => 'before',
				'conditions' => [
					'terms' => [
						[
							'name'     => 'background_image[url]',
							'operator' => '!=',
							'value'    => '',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'background_overlay_color',
			[
				'label'      => esc_html__( 'Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'default'    => 'rgba(0,0,0,0.5)',
				'conditions' => [
					'terms' => [
						[
							'name'  => 'background_overlay',
							'value' => 'yes',
						],
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-background-overlay' => 'background-color: {{VALUE}}',
				],
			]
		);

		$repeater->add_control(
			'background_overlay_blend_mode',
			[
				'label'      => esc_html__( 'Blend Mode', 'martfury-addons' ),
				'type'       => Controls_Manager::SELECT,
				'options'    => [
					''            => esc_html__( 'Normal', 'martfury-addons' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'color-burn'  => 'Color Burn',
					'hue'         => 'Hue',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'exclusion'   => 'Exclusion',
					'luminosity'  => 'Luminosity',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'background_overlay',
							'value' => 'yes',
						],
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-background-overlay' => 'mix-blend-mode: {{VALUE}}',
				],
			]
		);

		$repeater->end_controls_tab();

		$repeater->start_controls_tab( 'content', [ 'label' => esc_html__( 'Content', 'martfury-addons' ) ] );

		$repeater->add_control(
			'subtitle',
			[
				'label'       => esc_html__( 'Subtitle', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'heading',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'martfury-addons' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			]
		);

		$repeater->add_control(
			'button_text',
			[
				'label'   => esc_html__( 'Button Text', 'martfury-addons' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Link', 'martfury-addons' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'martfury-addons' ),
			]
		);

		$repeater->add_control(
			'link_click',
			[
				'label'      => esc_html__( 'Apply Link On', 'martfury-addons' ),
				'type'       => Controls_Manager::SELECT,
				'options'    => [
					'button' => esc_html__( 'Button Only', 'martfury-addons' ),
					'slide'  => esc_html__( 'Whole Slide', 'martfury-addons' ),
				],
				'default'    => 'button',
				'conditions' => [
					'terms' => [
						[
							'name'     => 'link[url]',
							'operator' => '!=',
							'value'    => '',
						],
					],
				],
			]
		);

		$repeater->end_controls_tab();

		$repeater->start_controls_tab( 'price_box', [ 'label' => esc_html__( 'Price', 'martfury-addons' ) ] );
		$repeater->add_control(
			'price_box_type',
			[
				'label'       => esc_html__( 'Type', 'martfury-addons' ),
				'type'        => Controls_Manager::CHOOSE,
				'options'     => [
					'text'  => [
						'title' => esc_html__( 'Text', 'martfury-addons' ),
						'icon'  => 'fa fa-font',
					],
					'image' => [
						'title' => esc_html__( 'Image', 'martfury-addons' ),
						'icon'  => 'fa fa-picture-o',
					],
				],
				'default'     => 'text',
				'toggle'      => false,
				'label_block' => false,
			]
		);

		$repeater->add_control(
			'price_box_text',
			[
				'label'     => esc_html__( 'Price Box Text', 'martfury-addons' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => '',
				'condition' => [
					'price_box_type' => [ 'text' ],
				],
			]
		);
		$repeater->add_control(
			'price_box_image',
			[
				'label'     => esc_html__( 'Price Box Image', 'martfury-addons' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [
					'price_box_type' => [ 'image' ],
				],
			]
		);
		$repeater->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'price_box_image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'condition' => [
					'price_box_type' => [ 'image' ],
				],
			]
		);
		$repeater->end_controls_tab();

		$repeater->start_controls_tab( 'style', [ 'label' => esc_html__( 'Style', 'martfury-addons' ) ] );

		$repeater->add_control(
			'custom_style',
			[
				'label'       => esc_html__( 'Custom', 'martfury-addons' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => esc_html__( 'Set custom style that will only affect this specific slide.', 'martfury-addons' ),
			]
		);

		$repeater->add_control(
			'horizontal_position',
			[
				'label'                => esc_html__( 'Horizontal Position', 'martfury-addons' ),
				'type'                 => Controls_Manager::CHOOSE,
				'label_block'          => false,
				'options'              => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default'              => 'left',
				'selectors'            => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-content' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'margin-right: auto',
					'center' => 'margin: 0 auto',
					'right'  => 'margin-left: auto',
				],
				'conditions'           => [
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
			'vertical_position',
			[
				'label'                => esc_html__( 'Vertical Position', 'martfury-addons' ),
				'type'                 => Controls_Manager::CHOOSE,
				'label_block'          => false,
				'options'              => [
					'top'    => [
						'title' => esc_html__( 'Top', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [
						'title' => esc_html__( 'Middle', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [
						'title' => esc_html__( 'Bottom', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'selectors'            => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner' => 'align-items: {{VALUE}}',
				],
				'selectors_dictionary' => [
					'top'    => 'flex-start',
					'middle' => 'center',
					'bottom' => 'flex-end',
				],
				'conditions'           => [
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
			'text_align',
			[
				'label'       => esc_html__( 'Text Align', 'martfury-addons' ),
				'type'        => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options'     => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'selectors'   => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner' => 'text-align: {{VALUE}}',
				],
				'conditions'  => [
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
			'subtitle_custom_color',
			[
				'label'      => esc_html__( 'Subtitle Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-subtitle' => 'color: {{VALUE}}',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'custom_style',
							'value' => 'yes',
						],
					],
				],
				'separator'  => 'before',
			]
		);

		$repeater->add_control(
			'subtitle_custom_bg_color',
			[
				'label'      => esc_html__( 'Subtitle Background Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-subtitle' => 'background-color: {{VALUE}};padding:5px 12px;display:inline-block;border-radius:3px',
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
			'heading_custom_color',
			[
				'label'      => esc_html__( 'Heading Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-heading' => 'color: {{VALUE}}',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'custom_style',
							'value' => 'yes',
						],
					],
				],
				'separator'  => 'before',
			]
		);

		$repeater->add_control(
			'content_custom_color',
			[
				'label'      => esc_html__( 'Content Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-description' => 'color: {{VALUE}}',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'custom_style',
							'value' => 'yes',
						],
					],
				],
				'separator'  => 'before',
			]
		);

		$repeater->add_control(
			'button_custom_color',
			[
				'label'      => esc_html__( 'Button Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-button' => 'color: {{VALUE}}; border-color: {{VALUE}};',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'custom_style',
							'value' => 'yes',
						],
					],
				],
				'separator'  => 'before',
			]
		);

		$repeater->add_control(
			'button_custom_bg_color',
			[
				'label'      => esc_html__( 'Button Background Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-button' => 'background-color: {{VALUE}};',
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
			'price_box_custom_color',
			[
				'label'      => esc_html__( 'Price Box Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-price-box--text' => 'color: {{VALUE}}; border-color: {{VALUE}};',
				],
				'conditions' => [
					'terms' => [
						[
							'name'  => 'custom_style',
							'value' => 'yes',
						],
					],
				],
				'separator'  => 'before',
			]
		);

		$repeater->add_control(
			'price_box_custom_bg_color',
			[
				'label'      => esc_html__( 'Price Box Background Color', 'martfury-addons' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .mf-slides {{CURRENT_ITEM}} .slick-slide-inner .mf-slide-price-box--text' => 'background-color: {{VALUE}};',
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
			'slides',
			[
				'label'      => esc_html__( 'Slides', 'martfury-addons' ),
				'type'       => Controls_Manager::REPEATER,
				'show_label' => true,
				'fields'     => $repeater->get_controls(),
				'default'    => [
					[
						'subtitle'         => esc_html__( 'Slide 1 Subtitle', 'martfury-addons' ),
						'heading'          => esc_html__( 'Slide 1 Heading', 'martfury-addons' ),
						'description'      => esc_html__( 'Click edit button to change this text. Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'martfury-addons' ),
						'button_text'      => esc_html__( 'Click Here', 'martfury-addons' ),
						'background_color' => '#f4f5f5',
					],
					[
						'subtitle'         => esc_html__( 'Slide 2 Subtitle', 'martfury-addons' ),
						'heading'          => esc_html__( 'Slide 2 Heading', 'martfury-addons' ),
						'description'      => esc_html__( 'Click edit button to change this text. Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'martfury-addons' ),
						'button_text'      => esc_html__( 'Click Here', 'martfury-addons' ),
						'background_color' => '#f4f5f5',
					],
					[
						'subtitle'         => esc_html__( 'Slide 3 Subtitle', 'martfury-addons' ),
						'heading'          => esc_html__( 'Slide 3 Heading', 'martfury-addons' ),
						'description'      => esc_html__( 'Click edit button to change this text. Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'martfury-addons' ),
						'button_text'      => esc_html__( 'Click Here', 'martfury-addons' ),
						'background_color' => '#f4f5f5',
					],
				],
			]
		);

		$this->add_responsive_control(
			'slides_height',
			[
				'label'      => esc_html__( 'Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 100,
						'max' => 1000,
					],
					'vh' => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 400,
				],
				'size_units' => [ 'px', 'vh', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-slide' => 'height: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_slider_options',
			[
				'label' => esc_html__( 'Slider Options', 'martfury-addons' ),
				'type'  => Controls_Manager::SECTION,
			]
		);

		$this->add_responsive_control(
			'navigation',
			[
				'label'   => __( 'Navigation', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'both',
				'options' => [
					'both'   => __( 'Arrows and Dots', 'martfury-addons' ),
					'arrows' => __( 'Arrows', 'martfury-addons' ),
					'dots'   => __( 'Dots', 'martfury-addons' ),
					'none'   => __( 'None', 'martfury-addons' ),
				],
				'label'   => esc_html__( 'Navigation', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'both',
				'options' => [
					'both'   => esc_html__( 'Arrows and Dots', 'martfury-addons' ),
					'arrows' => esc_html__( 'Arrows', 'martfury-addons' ),
					'dots'   => esc_html__( 'Dots', 'martfury-addons' ),
					'none'   => esc_html__( 'None', 'martfury-addons' ),
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'   => esc_html__( 'Pause on Hover', 'martfury-addons' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'   => esc_html__( 'Autoplay', 'martfury-addons' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'     => esc_html__( 'Autoplay Speed', 'martfury-addons' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'condition' => [
					'autoplay' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide-bg' => 'animation-duration: calc({{VALUE}}ms*1.2); transition-duration: calc({{VALUE}}ms)',
				],
			]
		);

		$this->add_control(
			'infinite',
			[
				'label'   => esc_html__( 'Infinite Loop', 'martfury-addons' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'transition',
			[
				'label'   => esc_html__( 'Transition', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => [
					'slide' => esc_html__( 'Slide', 'martfury-addons' ),
					'fade'  => esc_html__( 'Fade', 'martfury-addons' ),
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => esc_html__( 'Transition Speed', 'martfury-addons' ) . ' (ms)',
				'type'    => Controls_Manager::NUMBER,
				'default' => 500,
			]
		);

		$this->add_control(
			'content_animation',
			[
				'label'   => esc_html__( 'Content Animation', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fadeInUp',
				'options' => [
					''            => esc_html__( 'None', 'martfury-addons' ),
					'fadeIn'      => esc_html__( 'Fade In', 'martfury-addons' ),
					'fadeInDown'  => esc_html__( 'Fade In Down', 'martfury-addons' ),
					'fadeInUp'    => esc_html__( 'Fade In Up', 'martfury-addons' ),
					'fadeInRight' => esc_html__( 'Fade In Right', 'martfury-addons' ),
					'fadeInLeft'  => esc_html__( 'Fade In Left', 'martfury-addons' ),
					'zoomIn'      => esc_html__( 'Zoom', 'martfury-addons' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_slides',
			[
				'label' => esc_html__( 'Slides', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'content_max_width',
			[
				'label'          => esc_html__( 'Content Width', 'martfury-addons' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units'     => [ '%', 'px' ],
				'default'        => [
					'size' => '66',
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'selectors'      => [
					'{{WRAPPER}} .mf-slides .mf-slide-content' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slides_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'banner_border',
				'label'     => esc_html__( 'Border', 'martfury-addons' ),
				'selector'  => '{{WRAPPER}} .mf-slides',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'slides_horizontal_position',
			[
				'label'        => esc_html__( 'Horizontal Position', 'martfury-addons' ),
				'type'         => Controls_Manager::CHOOSE,
				'label_block'  => false,
				'default'      => 'center',
				'options'      => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'prefix_class' => 'mf--h-position-',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'slides_vertical_position',
			[
				'label'        => esc_html__( 'Vertical Position', 'martfury-addons' ),
				'type'         => Controls_Manager::CHOOSE,
				'label_block'  => false,
				'default'      => 'middle',
				'options'      => [
					'top'    => [
						'title' => esc_html__( 'Top', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [
						'title' => esc_html__( 'Middle', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [
						'title' => esc_html__( 'Bottom', 'martfury-addons' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'prefix_class' => 'mf--v-position-',
			]
		);

		$this->add_control(
			'slides_text_align',
			[
				'label'       => esc_html__( 'Text Align', 'martfury-addons' ),
				'type'        => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options'     => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'default'     => 'left',
				'selectors'   => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner' => 'text-align: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'slides_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Subtitle
		$this->start_controls_section(
			'section_style_subtitle',
			[
				'label' => esc_html__( 'Subtitle', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'subtitle_spacing',
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
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-subtitle:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-subtitle' => 'color: {{VALUE}}',

				],
			]
		);

		$this->add_control(
			'subtitle_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-subtitle' => 'background-color: {{VALUE}};padding:5px 12px;display:inline-block;border-radius:3px',

				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .mf-slide-subtitle',
			]
		);

		$this->end_controls_section();

		// Title
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
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
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-heading:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-heading' => 'color: {{VALUE}}',

				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .mf-slides .mf-slide-heading',
			]
		);

		$this->end_controls_section();

		// Description
		$this->start_controls_section(
			'section_style_description',
			[
				'label' => esc_html__( 'Description', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'description_spacing',
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
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-description:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-description' => 'color: {{VALUE}}',

				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .mf-slides .mf-slide-description',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Button', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_spacing',
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
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .mf-slides .mf-slide-button',
			]
		);

		$this->add_control(
			'button_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'after',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab( 'normal', [ 'label' => esc_html__( 'Normal', 'martfury-addons' ) ] );

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover', [ 'label' => esc_html__( 'Hover', 'martfury-addons' ) ] );

		$this->add_control(
			'button_hover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .mf-slides .mf-slide-button:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .mf-slides .mf-slide-button:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .mf-slide-button:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .mf-slides .mf-slide-button:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'heading_style_icon',
			[
				'label'     => esc_html__( 'Icon', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_control(
			'icon_button',
			[
				'label'        => esc_html__( 'Show Icon', 'martfury-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			]
		);
		$this->add_control(
			'icon',
			[
				'label'     => esc_html__( 'Icon', 'martfury-addons' ),
				'type'      => Controls_Manager::ICON,
				'default'   => 'fa fa-star',
				'condition' => [
					'icon_button' => 'yes',
				],
			]
		);
		$this->add_control(
			'icon_position',
			[
				'label'     => esc_html__( 'Icon Position', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'left'  => esc_html__( 'Left', 'martfury-addons' ),
					'right' => esc_html__( 'Right', 'martfury-addons' ),
				],
				'default'   => 'left',
				'toggle'    => false,
				'condition' => [
					'icon_button' => 'yes',
				],
			]
		);
		$this->add_responsive_control(
			'icon_spacing',
			[
				'label'     => esc_html__( 'Icon Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
						'min' => 5,
					],
				],
				'default'   => [
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}}  .mf-slides-wrapper.align-icon-left .mf-slide-button i' => 'padding-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mf-slides-wrapper.align-icon-right .mf-slide-button i' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'icon_button' => 'yes',
				],
			]
		);
		$this->add_responsive_control(
			'icon_font_size',
			[
				'label'     => esc_html__( 'Font Size', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [],
				'range'     => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-button i' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'icon_button' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// Price Box
		$this->start_controls_section(
			'section_style_price_box',
			[
				'label' => esc_html__( 'Price Box', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'price_box_width',
			[
				'label'      => esc_html__( 'Width', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 50,
						'max' => 200,
					],
				],
				'default'    => [],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'price_box_height',
			[
				'label'      => esc_html__( 'Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 50,
						'max' => 200,
					],
				],
				'default'    => [],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box' => 'height: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'after'
			]
		);

		$position = [
			'top'  => esc_html__( 'Vertical', 'martfury-addons' ),
			'left' => esc_html__( 'Horizontal', 'martfury-addons' ),
		];

		$this->add_control(
			'heading_price_box',
			[
				'label' => esc_html__( 'Position', 'martfury-addons' ),
				'type'  => Controls_Manager::HEADING,
			]
		);
		foreach ( $position as $key => $label ) {
			$this->add_responsive_control(
				'price_box_' . $key,
				[
					'label'      => $label,
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ '%', 'px' ],
					'range'      => [
						'%'  => [
							'min' => 0,
							'max' => 100,
						],
						'px' => [
							'min' => 0,
							'max' => 1170,
						],
					],
					'default'    => [],
					'selectors'  => [
						'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box' => "$key: {{SIZE}}{{UNIT}};",
					],
				]
			);
		}

		$this->add_control(
			'price_box_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box--text' => 'color: {{VALUE}}',
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'price_box_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box--text' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_box_typography',
				'selector' => '{{WRAPPER}} .mf-slides .slick-slide-inner .mf-slide-price-box--text',
			]
		);

		$this->end_controls_section();

		// Arrows
		$this->start_controls_section(
			'section_style_arrows',
			[
				'label' => esc_html__( 'Arrows', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'arrows_position',
			[
				'label'   => esc_html__( 'Position', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'inside'  => esc_html__( 'Inside Container', 'martfury-addons' ),
					'outside' => esc_html__( 'Outside Container', 'martfury-addons' ),
				],
				'default' => 'inside',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'arrows_size',
			[
				'label'     => esc_html__( 'Arrows Size', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 10,
						'max' => 60,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .arrows-wrapper .slick-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrows_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-prev-arrow' => 'left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mf-slides-wrapper .slick-next-arrow' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrows_width',
			[
				'label'      => esc_html__( 'Width', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-arrow' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrows_height',
			[
				'label'      => esc_html__( 'Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-arrow' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrows_line_height',
			[
				'label'      => esc_html__( 'Line Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-arrow' => 'line-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'arrows_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-arrow' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'arrows_tabs' );

		$this->start_controls_tab( 'arrows_normal', [ 'label' => esc_html__( 'Normal', 'martfury-addons' ) ] );

		$this->add_control(
			'arrows_color',
			[
				'label'     => esc_html__( 'Arrows Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .arrows-wrapper .slick-arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrows_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .arrows-wrapper .slick-arrow' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'arrows_hover', [ 'label' => esc_html__( 'Hover', 'martfury-addons' ) ] );

		$this->add_control(
			'arrows_hover_color',
			[
				'label'     => esc_html__( 'Arrows Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .arrows-wrapper .slick-arrow:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrows_hover_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .arrows-wrapper .slick-arrow:hover' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// Dots
		$this->start_controls_section(
			'section_style_dots',
			[
				'label' => esc_html__( 'Dots', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'dots_position',
			[
				'label'     => esc_html__( 'Position', 'martfury-addons' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-dots' => 'text-align: {{VALUE}};',
				],
				'toggle'    => false,
			]
		);

		$this->add_control(
			'dots_vertical_offset',
			[
				'label'      => esc_html__( 'Vertical Offset', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-dots' => 'bottom:{{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dots_horizontal_offset',
			[
				'label'      => esc_html__( 'Horizontal Offset', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-dots' => 'left: {{SIZE}}{{UNIT}}; right: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'dots_position' => [ 'left', 'right' ],
				],
			]
		);

		$this->add_control(
			'dots_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-dots li'                  => 'padding-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mf-slides .slick-dots li:last-child'       => 'padding-right: 0;',
					'.rtl {{WRAPPER}} .mf-slides .slick-dots li:first-child' => 'padding-right: 0;',
					'.rtl {{WRAPPER}} .mf-slides .slick-dots li:last-child'  => 'padding-right: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'dots_width',
			[
				'label'      => esc_html__( 'Width', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-dots li button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dots_height',
			[
				'label'      => esc_html__( 'Height', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-slides .slick-dots li button' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dots_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-slides .slick-dots li button' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'dots_tabs' );

		$this->start_controls_tab( 'dots_normal', [ 'label' => esc_html__( 'Normal', 'martfury-addons' ) ] );

		$this->add_control(
			'dots_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-dots li button' => 'background-color: {{VALUE}};',
				],

			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'dots_hover', [ 'label' => esc_html__( 'Hover', 'martfury-addons' ) ] );

		$this->add_control(
			'dots_hover_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-dots li button:hover' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'dots_active', [ 'label' => esc_html__( 'Active', 'martfury-addons' ) ] );

		$this->add_control(
			'dots_active_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-slides-wrapper .slick-dots li.slick-active button' => 'background-color: {{VALUE}};'
				],
				'default'   => '',
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

		if ( empty( $settings['slides'] ) ) {
			return;
		}

		$nav        = $settings['navigation'];
		$nav_tablet = empty( $settings['navigation_tablet'] ) ? $nav : $settings['navigation_tablet'];
		$nav_mobile = empty( $settings['navigation_mobile'] ) ? $nav : $settings['navigation_mobile'];

		$classes = [
			'mf-slides-wrapper loading',
			'align-icon-' . $settings['icon_position'],
			'navigation-' . $nav,
			'navigation-tablet-' . $nav_tablet,
			'navigation-mobile-' . $nav_mobile,
		];

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		$this->add_render_attribute( 'button', 'class', [ 'mf-button', 'mf-slide-button' ] );

		$slides      = [];
		$slide_count = 0;

		foreach ( $settings['slides'] as $slide ) {
			$slide_html       = '';
			$btn_attributes   = '';
			$slide_attributes = '';
			$slide_element    = 'div';
			$btn_element      = 'div';
			$slide_url        = $slide['link']['url'];

			if ( ! empty( $slide_url ) ) {
				$this->add_render_attribute( 'slide_link' . $slide_count, 'href', $slide_url );

				if ( $slide['link']['is_external'] ) {
					$this->add_render_attribute( 'slide_link' . $slide_count, 'target', '_blank' );
				}

				if ( 'button' === $slide['link_click'] ) {
					$btn_element    = 'a';
					$btn_attributes = $this->get_render_attribute_string( 'slide_link' . $slide_count );
				} else {
					$slide_element    = 'a';
					$slide_attributes = $this->get_render_attribute_string( 'slide_link' . $slide_count );
				}
			}

			if ( 'yes' === $slide['background_overlay'] ) {
				$slide_html .= '<div class="mf-background-overlay"></div>';
			}

			$price_box_type = $slide['price_box_type'];

			if ( $price_box_type == 'text' ) {
				$price_box_html = $slide['price_box_text'];
			} else {
				$price_box_html = Group_Control_Image_Size::get_attachment_image_html( $slide, 'price_box_image' );
			}

			if ( $price_box_html ) {
				$slide_html .= '<div class="mf-slide-price-box mf-slide-price-box--' . $price_box_type . '">' . $price_box_html . '</div>';
			}

			$slide_html .= '<div class="mf-slide-content">';

			if ( $slide['subtitle'] ) {
				$slide_html .= '<div class="mf-slide-subtitle">' . $slide['subtitle'] . '</div>';
			}

			if ( $slide['heading'] ) {
				$slide_html .= '<div class="mf-slide-heading">' . $slide['heading'] . '</div>';
			}

			if ( $slide['description'] ) {
				$slide_html .= '<div class="mf-slide-description">' . $slide['description'] . '</div>';
			}

			$icon = '';

			if ( $settings['icon_button'] == 'yes' ) {
				$icon = '<i class="' . esc_attr( $settings['icon'] ) . '"></i>';
			}

			if ( $slide['button_text'] ) {
				$slide_html .= '<' . $btn_element . ' ' . $btn_attributes . ' ' . $this->get_render_attribute_string( 'button' ) . '>' . $slide['button_text'] . $icon . '</' . $btn_element . '>';
			}

			$ken_class = '';

			if ( '' != $slide['background_ken_burns'] ) {
				$ken_class = ' mf-ken-' . $slide['zoom_direction'];
			}

			$slide_html .= '</div>';
			$slide_html = '<div class="slick-slide-bg' . $ken_class . '"></div><' . $slide_element . ' ' . $slide_attributes . ' class="slick-slide-inner">' . $slide_html . '</' . $slide_element . '>';
			$slides[]   = '<div class="elementor-repeater-item-' . $slide['_id'] . ' slick-slide">' . $slide_html . '</div>';
			$slide_count ++;
		}

		$is_rtl    = is_rtl();
		$direction = $is_rtl ? 'rtl' : 'ltr';

		$slick_options = [
			'slidesToShow'  => absint( 1 ),
			'autoplaySpeed' => isset($settings['autoplay_speed']) ? absint( $settings['autoplay_speed'] ) : 5000,
			'autoplay'      => ( 'yes' === $settings['autoplay'] ),
			'infinite'      => ( 'yes' === $settings['infinite'] ),
			'pauseOnHover'  => ( 'yes' === $settings['pause_on_hover'] ),
			'speed'         => absint( $settings['transition_speed'] ),
			'arrows'        => true,
			'dots'          => true,
			'rtl'           => $is_rtl,
			'fade'          => 'fade' === $settings['transition']
		];

		$carousel_classes = [
			'mf-slides'
		];

		$this->add_render_attribute(
			'slides', [
				'class'               => $carousel_classes,
				'data-slider_options' => wp_json_encode( $slick_options ),
				'data-animation'      => $settings['content_animation'],
			]
		);

		$this->add_render_attribute( 'wrapper', 'dir', $direction );

		$arrows_container = $settings['arrows_position'] == 'inside' ? 'container' : 'container-fluid';

		echo sprintf(
			'<div %s>
				<div class="arrows-wrapper"><div class="arrows-inner arrows-%s"></div></div>
				<div %s>%s</div>
			</div>',
			$this->get_render_attribute_string( 'wrapper' ),
			esc_attr( $arrows_container ),
			$this->get_render_attribute_string( 'slides' ),
			implode( '', $slides )
		);
	}


}