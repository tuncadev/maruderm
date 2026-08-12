<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Border;
use MartfuryAddons\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Testimonial_Slides_2 extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-testimonial-slides-2';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Martfury - Testimonial Slides 2', 'martfury-addons' );
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
		$this->_register_heading_controls();
		$this->_register_content_controls();
		$this->_register_avatar_controls();
		$this->_register_cite_controls();
		$this->_register_slick_controls();
	}

	protected function _register_heading_controls() {
		$this->start_controls_section(
			'section_heading',
			[ 'label' => esc_html__( 'Heading', 'martfury-addons' ) ]
		);
		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Heading Name', 'martfury-addons' ),
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

		// Heading Style
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
				'placeholder' => [
					'top'    => '35',
					'right'  => '40',
					'bottom' => '35',
					'left'   => '40',
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-heading' => 'background-color: {{VALUE}};',
				],
				'default'   => '',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'banner_border',
				'label'    => esc_html__( 'Border', 'martfury-addons' ),
				'selector' => '{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-heading',
			]
		);

		$this->add_control(
			'title_heading',
			[
				'label'     => esc_html__( 'Title', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-heading .tes-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-heading .tes-title',
			]
		);

		$this->end_controls_section();
	}

	protected function _register_content_controls() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Content', 'martfury-addons' ) ]
		);
		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'separator' => 'none',
			]
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'desc',
			[
				'label'       => esc_html__( 'Description', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'This is the description. Sed elit quam, iaculis sed semper sit amet udin vitae nibh. at magna akal semperFusce commodo molestie', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter the Description', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'image', [
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => 'https://via.placeholder.com/100/',
				],
			]
		);
		$repeater->add_control(
			'name',
			[
				'label'       => esc_html__( 'Name', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Name', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter the Name', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'job',
			[
				'label'       => esc_html__( 'Job', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Job', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter the Job', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$this->add_control(
			'settings',
			[
				'label'         => esc_html__( 'Settings', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'desc'      => esc_html__( 'This is the description. Sed elit quam, iaculis sed semper sit amet udin vitae nibh. at magna akal semperFusce commodo molestie', 'martfury-addons' ),
						'image'     => [
							'url' => 'https://via.placeholder.com/100/'
						],
						'name'      => esc_html__( 'Name #1', 'martfury-addons' ),
						'job'       => esc_html__( 'Job #1', 'martfury-addons' ),
					],
					[
						'desc'      => esc_html__( 'This is the description. Sed elit quam, iaculis sed semper sit amet udin vitae nibh. at magna akal semperFusce commodo molestie', 'martfury-addons' ),
						'image'     => [
							'url' => 'https://via.placeholder.com/100/'
						],
						'name'      => esc_html__( 'Name #2', 'martfury-addons' ),
						'job'       => esc_html__( 'Job 02', 'martfury-addons' ),
					],
					[
						'desc'      => esc_html__( 'This is the description. Sed elit quam, iaculis sed semper sit amet udin vitae nibh. at magna akal semperFusce commodo molestie', 'martfury-addons' ),
						'image'     => [
							'url' => 'https://via.placeholder.com/100/'
						],
						'name'      => esc_html__( 'Name #3', 'martfury-addons' ),
						'job'       => esc_html__( 'Job #3', 'martfury-addons' ),
					]
				],
				'title_field'   => '{{{ name }}}',
				'prevent_empty' => false
			]
		);
		$this->end_controls_section();

		// Content Style
		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__( 'Content', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'placeholder' => [
					'top'    => '44',
					'right'  => '50',
					'bottom' => '20',
					'left'   => '60',
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function _register_desc_controls() {
		$this->start_controls_section(
			'section_desc_style',
			[
				'label' => esc_html__( 'Description', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__content',
			]
		);
		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__content' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'desc_spacing',
			[
				'label'     => esc_html__( 'Bottom Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 500,
						'min' => 0,
					],
				],
				'default'   => [],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function _register_avatar_controls() {
		$this->start_controls_section(
			'section_avatar_style',
			[
				'label' => esc_html__( 'Image', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'avatar_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 300,
						'min' => 0,
					],
				],
				'default'   => [],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .testimonial-thumb' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avatar_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'%' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'default'   => [],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .testimonial-thumb img' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function _register_cite_controls() {
		$this->start_controls_section(
			'section_cite_style',
			[
				'label' => esc_html__( 'Cite', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs('cite_style_tabs');

		$this->start_controls_tab(
			'name_style_tab',
			[
				'label' => __( 'Name', 'martfury-addons' ),
			]
		);
		$this->add_responsive_control(
			'name_spacing',
			[
				'label'     => esc_html__( 'Bottom Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 100,
						'min' => 0,
					],
				],
				'default'   => [],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .name',
			]
		);
		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'job_style_tab',
			[
				'label' => __( 'Job', 'martfury-addons' ),
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'job_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .job',
			]
		);
		$this->add_control(
			'job_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .testimonial-info__footer .job' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	protected function _register_slick_controls() {
		$this->start_controls_section(
			'section_slider_settings',
			[ 'label' => esc_html__( 'Slider Settings', 'martfury-addons' ) ]
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
				'default'   => 'yes'
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

		// Carousel Style
		$this->start_controls_section(
			'section_carousel_style',
			[
				'label' => esc_html__( 'Carousel Settings', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'dots_style_divider',
			[
				'label'     => esc_html__( 'Dots', 'martfury-addons' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_control(
			'dots_align',
			[
				'label'     => esc_html__( 'Align', 'martfury-addons' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left' => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'right'   => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'default'   => '',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'dots_style',
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
			'sliders_dots_gap',
			[
				'label'     => __( 'Gap', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
						'min' => 0,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots' => 'margin-left: -{{SIZE}}{{UNIT}};margin-right: -{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li' => 'padding: 0 {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'sliders_dots_width',
			[
				'label'      => esc_html__( 'Size', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li button'        => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li button:before' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}}',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'sliders_dots_top_spacing',
			[
				'label'      => esc_html__( 'Top Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots'        => 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_responsive_control(
			'sliders_dots_bottom_spacing',
			[
				'label'      => esc_html__( 'Bottom Spacing', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots'        => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_control(
			'sliders_dots_background',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li button:before' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'sliders_dots_active_background',
			[
				'label'     => esc_html__( 'Active Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li.slick-active button:before' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .mf-elementor-testimonial-slides-2 .slick-dots li button:hover:before'        => 'background-color: {{VALUE}};',
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

		$classes = [
			'mf-elementor-testimonial-slides-2',
		];

		$this->add_render_attribute( 'wrapper', [
			'class' => $classes,
		] );


		$title = '';
		if ( $settings['title'] ) {
			$title = sprintf( '<%1$s class="tes-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $settings['title'] );
		}

		$output = [];

		$testimonial_settings = $settings['settings'];

		if ( ! empty( $testimonial_settings ) ) {
			foreach ( $testimonial_settings as $index => $item ) {
				$image    = $name = $desc = $css = $job = '';

				// Image Size
				if ( $settings['image_size'] != 'custom' ) {
					$image_size = $settings['image_size'];
				} else {
					$image_size = [
						$settings['image_custom_dimension']['width'],
						$settings['image_custom_dimension']['height'],
					];
				}

				if ( isset( $item['image'] ) ) {
					if ( $item['image']['id'] ) {
						$image              = wp_get_attachment_image( $item['image']['id'], $image_size );
					} else {
						$css .= ' no-thumbnail';
					}

				} else {
					$css .= ' no-thumbnail';
				}

				if ( isset( $item['name'] ) && $item['name'] ) {
					$name = sprintf( '<div class="name">%s</div>', $item['name'] );
				}

				if ( isset( $item['job'] ) && $item['job'] ) {
					$job = sprintf( '<div class="job">%s</div>', $item['job'] );
				}

				if ( isset( $item['desc'] ) && $item['desc'] ) {
					$desc = sprintf( '<div class="testimonial-info__content">%s</div>', $item['desc'] );
				}

				$output[] = sprintf(
					'<div class="testimonial-info %s">
						%s
						<div class="testimonial-info__footer">
							<div class="testimonial-thumb">%s</div>
							<div class="testimonial-cite">%s%s</div>
						</div>
					</div>',
					esc_attr( $css ),
					$desc,
					$image,
					$name,
					$job
				);
			}
		}

		$carousel_settings = [
			'autoplay'       => $settings['autoplay'],
			'infinite'       => $settings['infinite'],
			'autoplay_speed' => intval( $settings['autoplay_speed'] ),
			'speed'          => intval( $settings['speed'] ),
		];

		$this->add_render_attribute( 'wrapper', 'data-settings', wp_json_encode( $carousel_settings ) );

		echo sprintf(
			'<div %s>
				<div class="testimonial-heading">%s<div class="arrow-wrapper"></div></div>
				<div class="testimonial-list">%s</div>
			</div>',
			$this->get_render_attribute_string( 'wrapper' ),
			$title,
			implode( '', $output )
		);
	}


	/**
	 * Get the link control
	 *
	 * @return string.
	 */
	protected function get_link_control( $link_key, $url, $content, $attr = [ ] ) {
		$attr_default = [ ];
		if ( isset( $url['url'] ) && $url['url'] ) {
			$attr_default['href'] = $url['url'];
		}

		if ( isset( $url['is_external'] ) && $url['is_external'] ) {
			$attr_default['target'] = '_blank';
		}

		if ( isset( $url['nofollow'] ) && $url['nofollow'] ) {
			$attr_default['rel'] = 'nofollow';
		}

		$tag = 'a';

		if ( empty( $attr_default['href'] ) ) {
			$tag = 'span';
		}

		$attr = wp_parse_args( $attr, $attr_default );

		$this->add_render_attribute( $link_key, $attr );

		return sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, $this->get_render_attribute_string( $link_key ), $content );
	}
}