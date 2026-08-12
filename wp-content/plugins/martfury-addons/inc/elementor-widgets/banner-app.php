<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Image_Size;
use Elementor\Utils;
use Elementor\Controls_Stack;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Banner Small widget
 */
class Banner_App extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-banner-app';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Banner App', 'martfury-addons' );
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
		$this->_register_background_controls();
		$this->_register_app_box_controls();
		$this->_register_button_box_controls();
	}

	protected function _register_background_controls() {
		$this->start_controls_section(
			'section_banner',
			[ 'label' => esc_html__( 'Banner', 'martfury-addons' ) ]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'banners_background',
				'label'          => __( 'Background', 'martfury-addons' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .mf-elementor-banner-app',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'image'      => [
						'default' => [
							'url' => 'https://via.placeholder.com/1650x300/f8f8f8?text=1650x300+Image',
						],
					],
					'position'      => [
						'default' => 'center center',
					],
				],
			]
		);
		$this->add_control(
			'divider_1',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);
		$this->add_responsive_control(
			'content_alignment',
			[
				'label'           => esc_html__( 'Content Align', 'martfury-addons' ),
				'type'            => Controls_Manager::CHOOSE,
				'options'         => [
					'row'    => [
						'title' => esc_html__( 'Horizontal', 'martfury-addons' ),
						'icon'  => 'fa fa-ellipsis-h',
					],
					'column' => [
						'title' => esc_html__( 'Vertical', 'martfury-addons' ),
						'icon'  => 'fa fa-ellipsis-v',
					],
				],
				'desktop_default' => 'row',
				'tablet_default'  => 'row',
				'mobile_default'  => 'column',
				'toggle'          => false,
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'flex-direction: {{VALUE}}',
				],
				'required'        => true,
				'device_args'     => [
					Controls_Stack::RESPONSIVE_TABLET => [
						'selectors' => [
							'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'flex-direction: {{VALUE}}',
						],
					],
					Controls_Stack::RESPONSIVE_MOBILE => [
						'selectors' => [
							'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'flex-direction: {{VALUE}}',
						],
					],
				]
			]
		);
		$this->add_responsive_control(
			'content_position',
			[
				'label'           => __( 'Vertical Align', 'martfury-addons' ),
				'type'            => Controls_Manager::SELECT,
				'options'         => [
					''              => __( 'Default', 'martfury-addons' ),
					'flex-start'    => __( 'Start', 'martfury-addons' ),
					'center'        => __( 'Center', 'martfury-addons' ),
					'flex-end'      => __( 'End', 'martfury-addons' ),
					'stretch' 		=> __( 'Stretch', 'martfury-addons' ),
					'baseline'  	=> __( 'Baseline', 'martfury-addons' ),
				],
				'desktop_default' => '',
				'tablet_default'  => 'center',
				'mobile_default'  => 'center',
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'align-items: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'           => __( 'Horizontal Align', 'martfury-addons' ),
				'type'            => Controls_Manager::SELECT,
				'options'         => [
					''              => __( 'Default', 'martfury-addons' ),
					'flex-start'    => __( 'Start', 'martfury-addons' ),
					'center'        => __( 'Center', 'martfury-addons' ),
					'flex-end'      => __( 'End', 'martfury-addons' ),
					'space-between' => __( 'Space Between', 'martfury-addons' ),
					'space-around'  => __( 'Space Around', 'martfury-addons' ),
					'space-evenly'  => __( 'Space Evenly', 'martfury-addons' ),
				],
				'desktop_default' => '',
				'tablet_default'  => 'space-between',
				'mobile_default'  => 'space-between',
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'justify-content: {{VALUE}}',
				],
			]
		);
		$this->end_controls_section(); // End Content

		// Style
		$this->start_controls_section(
			'section_discount_box_style',
			[
				'label' => __( 'Banner', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'banner_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'placeholder' => [
					'top'    => '57',
					'right'  => '',
					'bottom' => '57',
					'left'   => '',
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function _register_app_box_controls() {
		$this->start_controls_section(
			'section_app_box',
			[ 'label' => esc_html__( 'Main Content', 'martfury-addons' ) ]
		);
		$this->start_controls_tabs('app_box_tabs');

		$this->start_controls_tab(
			'image_app_tab',
			[
				'label' => __( 'Image', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'image', [
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => 'https://via.placeholder.com/160/ffffff?text=160x160+Image',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'content_app_tab',
			[
				'label' => __( 'Content', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'This is the title', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$this->add_control(
			'description',
			[
				'label'       => esc_html__( 'Description', 'martfury-addons' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => __( 'This is the description', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your description', 'martfury-addons' ),
				'label_block' => true,
			]
		);
		$this->add_control(
			'form',
			[
				'label'     => esc_html__( 'Mailchimp Form', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->get_contact_form(),
				'separator' => 'before',
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section(); // End Content

		// Style
		$this->start_controls_section(
			'section_content_box_style',
			[
				'label' => __( 'Main Content', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'main_content_width',
			[
				'label'     => esc_html__( 'Width', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'default'   => [],
				'range'     => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 2000,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-content' 	=> 'max-width: {{SIZE}}{{UNIT}}; flex: 0 0 {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_responsive_control(
			'content_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'   => [],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-main-content' => 'padding-left: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_responsive_control(
			'main_content_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'placeholder' => [
					'top'    => '',
					'right'  => '',
					'bottom' => '',
					'left'   => '90',
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('main_content_style_tabs');

		// Title
		$this->start_controls_tab(
			'style_title_tab',
			[
				'label' => __( 'Title', 'martfury-addons' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .title',
			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .title' => 'color: {{VALUE}}',
				],
			]
		);
		$this->add_responsive_control(
			'title_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ ],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_tab();

		// Description
		$this->start_controls_tab(
			'style_desc_tab',
			[
				'label' => __( 'Description', 'martfury-addons' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .desc',
			]
		);
		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .desc' => 'color: {{VALUE}}',
				],
			]
		);
		$this->add_responsive_control(
			'desc_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ ],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-main-content .desc' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'divider_2',
			[
				'label' => esc_html__( 'Form', 'martfury-addons' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);
		$this->add_control(
			'form_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'form_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="text"]' => 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="email"]'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="text"]::placeholder'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="email"]::placeholder'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="text"]:-ms-input-placeholder'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="email"]:-ms-input-placeholder'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="text"]::-ms-input-placeholder'=> 'color: {{VALUE}}',
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="email"]::-ms-input-placeholder'=> 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'form_button_color',
			[
				'label'     => esc_html__( 'Button Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .mc4wp-form .mc4wp-form-fields input[type="submit"]' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function _register_button_box_controls() {
		$this->start_controls_section(
			'section_button_box',
			[ 'label' => esc_html__( 'Buttons', 'martfury-addons' ) ]
		);
		$this->add_responsive_control(
			'button_box_alignment',
			[
				'label'           => esc_html__( 'Content Align', 'martfury-addons' ),
				'type'            => Controls_Manager::CHOOSE,
				'options'         => [
					'row'    => [
						'title' => esc_html__( 'Horizontal', 'martfury-addons' ),
						'icon'  => 'fa fa-ellipsis-h',
					],
					'column' => [
						'title' => esc_html__( 'Vertical', 'martfury-addons' ),
						'icon'  => 'fa fa-ellipsis-v',
					],
				],
				'desktop_default' => 'column',
				'tablet_default'  => 'column',
				'mobile_default'  => 'column',
				'toggle'          => false,
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button' => 'flex-direction: {{VALUE}}',
				],
			]
		);
		$this->add_responsive_control(
			'button_box_vertical_align',
			[
				'label'           => __( 'Vertical Align', 'martfury-addons' ),
				'type'            => Controls_Manager::SELECT,
				'options'         => [
					''              => __( 'Default', 'martfury-addons' ),
					'flex-start'    => __( 'Start', 'martfury-addons' ),
					'center'        => __( 'Center', 'martfury-addons' ),
					'flex-end'      => __( 'End', 'martfury-addons' ),
					'stretch' 		=> __( 'Stretch', 'martfury-addons' ),
					'baseline'  	=> __( 'Baseline', 'martfury-addons' ),
				],
				'desktop_default' => '',
				'tablet_default'  => 'center',
				'mobile_default'  => 'center',
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button' => 'align-items: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'button_box_horizontal_align',
			[
				'label'           => __( 'Horizontal Align', 'martfury-addons' ),
				'type'            => Controls_Manager::SELECT,
				'options'         => [
					''              => __( 'Default', 'martfury-addons' ),
					'flex-start'    => __( 'Start', 'martfury-addons' ),
					'center'        => __( 'Center', 'martfury-addons' ),
					'flex-end'      => __( 'End', 'martfury-addons' ),
					'space-between' => __( 'Space Between', 'martfury-addons' ),
					'space-around'  => __( 'Space Around', 'martfury-addons' ),
					'space-evenly'  => __( 'Space Evenly', 'martfury-addons' ),
				],
				'desktop_default' => '',
				'tablet_default'  => 'space-between',
				'mobile_default'  => 'space-between',
				'selectors'       => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button' => 'justify-content: {{VALUE}}',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'btn_image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'separator' => 'before',
			]
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'btn_image', [
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);
		$repeater->add_control(
			'button_link', [
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
			'btn_settings',
			[
				'label'         => esc_html__( 'Buttons', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'btn_image'   => [
							'url' => 'https://via.placeholder.com/156x52/ffffff?text=156x52+Button'
						],
						'button_link' => [
							'url' => '#'
						]
					],
					[
						'btn_image'   => [
							'url' => 'https://via.placeholder.com/156x52/ffffff?text=156x52+Button'
						],
						'button_link' => [
							'url' => '#'
						]
					]
				],
				'prevent_empty' => false
			]
		);
		$this->end_controls_section(); // End Content

		// Style
		$this->start_controls_section(
			'section_button_box_style',
			[
				'label' => __( 'Buttons', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'placeholder' => [
					'top'    => '',
					'right'  => '80',
					'bottom' => '',
					'left'   => '',
				],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_v_spacing',
			[
				'label'     => esc_html__( 'Item Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
					'size' => 27,
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button a' 		  => 'margin-bottom: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button a:last-child' => 'margin-bottom: 0',
				],
				'condition' => [
					'button_box_alignment' => 'column'
				]
			]
		);
		$this->add_responsive_control(
			'button_h_spacing',
			[
				'label'     => esc_html__( 'Item Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
					'size' => 20,
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button a' 		  => 'margin-right: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mf-elementor-banner-app .banner-button a:last-child' => 'margin-right: 0',
				],
				'condition' => [
					'button_box_alignment' => 'row'
				]
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
			'mf-elementor-banner-app'
		] );


		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<div class="banner-wrapper">
				<div class="banner-content">
					<?php
					if ( $settings['image'] ) {
						$image = Group_Control_Image_Size::get_attachment_image_html( $settings, 'image' );
						echo '<div class="banner-image">' . $image . '</div>';
					}
					?>

					<div class="banner-main-content">
						<h4 class="title"><?php echo $settings['title'] ?></h4>
						<div class="desc"><?php echo $settings['description'] ?></div>
						<?php if ( $settings['form'] ) : ?>
							<div class="form"><?php echo do_shortcode( '[mc4wp_form id="' . esc_attr( $settings['form'] ) . '"]' ); ?></div>
						<?php endif; ?>
					</div>
				</div>

				<?php
				$btn_settings = $settings['btn_settings'];

				if ( ! empty( $btn_settings ) ) {

					echo '<div class="banner-button">';

					foreach ( $btn_settings as $index => $item ) {
						$link_key = 'link_' . $index;
						$app_image = '';

						// Image Size
						$item['btn_image_size'] = $settings['btn_image_size'];
						if ( $settings['btn_image_size'] == 'custom' ) {
							$item['btn_image_size_custom_dimension'] = [
								'width' => $settings['btn_image_custom_dimension']['width'],
								'height' => $settings['btn_image_custom_dimension']['height']
							];
						}

						if ( $item['btn_image'] ) {
							$app_image = Group_Control_Image_Size::get_attachment_image_html( $item, 'btn_image' );
						}

						printf( '%s', $this->get_link_control( $link_key, $item['button_link'], $app_image ) );
					}

					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
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

	/**
	 * Get Contact Form
	 */
	protected function get_contact_form() {
		$mail_forms    = get_posts( array( 'post_type' => 'mc4wp-form', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
		$mail_form_ids = array(
			'' => esc_html__( 'Select Form', 'martfury-addons' ),
		);
		foreach ( $mail_forms as $form ) {
			$mail_form_ids[ $form->ID ] = $form->post_title;
		}

		return $mail_form_ids;
	}
}