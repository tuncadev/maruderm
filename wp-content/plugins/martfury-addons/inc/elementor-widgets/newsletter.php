<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;
use Elementor\Utils;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Icon Box widget
 */
class Newsletter extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-newsletter';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Newsletter', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-mailchimp';
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
			'section_newsletter',
			[ 'label' => esc_html__( 'Newsletter', 'martfury-addons' ) ]
		);

		$this->start_controls_tabs( 'newsletter_content_settings' );


		$this->start_controls_tab( 'content', [ 'label' => esc_html__( 'Content', 'martfury-addons' ) ] );

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'This is the newsletter heading', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'desc',
			[
				'label'       => esc_html__( 'Description', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'This is the newsletter description. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your description', 'martfury-addons' ),
				'rows'        => 10,
			]
		);

		$this->add_control(
			'text',
			[
				'label'       => esc_html__( 'Text', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Enter your text', 'martfury-addons' ),
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

		$this->add_control(
			'btn',
			[
				'label'     => esc_html__( 'Show Buttons', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => 'yes',
				'options'   => [
					'yes' => esc_html__( 'Yes', 'martfury-addons' ),
					'no'  => esc_html__( 'No', 'martfury-addons' ),
				],
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
			'btn_setting',
			[
				'label'         => esc_html__( 'Buttons', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => [
					[
						'btn_image'   => [
							'url' => 'https://via.placeholder.com/127x43/ffffff?text=127x43+Button'
						],
						'button_link' => [
							'url' => '#'
						]
					],
					[
						'btn_image'   => [
							'url' => 'https://via.placeholder.com/127x43/ffffff?text=127x43+Button'
						],
						'button_link' => [
							'url' => '#'
						]
					]
				],
				'condition'     => [
					'btn' => 'yes',
				],
				'prevent_empty' => false
			]
		);


		$this->end_controls_tab();

		$this->start_controls_tab( 'banner', [ 'label' => esc_html__( 'Banner', 'martfury-addons' ) ] );


		$this->add_control(
			'image',
			[
				'label'   => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => 'https://via.placeholder.com/487x379/ffffff?text=487x379+Banner',
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

			]
		);


		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_newsletter',
			[
				'label' => esc_html__( 'Newsletter', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'newsletter_width',
			[
				'label'     => esc_html__( 'Width (%)', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default' => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'range'     => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-area' => 'width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'newsletter_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f8f8f8',
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'newsletter_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .martfury-newletter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'newsletter_border_style',
			[
				'label'     => esc_html__( 'Border Style', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'dotted' => esc_html__( 'Dotted', 'martfury-addons' ),
					'dashed' => esc_html__( 'Dashed', 'martfury-addons' ),
					''       => esc_html__( 'Solid', 'martfury-addons' ),
					'none'   => esc_html__( 'None', 'martfury-addons' ),
				],
				'default'   => '',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'newsletter_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'newsletter_border_style!' => 'none',
				],
			]
		);
		$this->add_control(
			'newsletter_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'newsletter_border_style!' => 'none',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_banner',
			[
				'label' => esc_html__( 'Banner', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'banner_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .martfury-newletter .form-image' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'banner_position',
			[
				'label'     => esc_html__( 'Banner Position', 'martfury-addons' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'' => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-image' => 'text-align: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
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
				'selectors'  => [
					'{{WRAPPER}} .martfury-newletter .form-area' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_align',
			[
				'label'     => esc_html__( 'Alignment', 'martfury-addons' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					''       => [
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
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-area' => 'text-align: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Title', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
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
					'{{WRAPPER}} .martfury-newletter .form-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .martfury-newletter .form-title',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_description',
			[
				'label' => esc_html__( 'Description', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'description_spacing',
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
					'{{WRAPPER}} .martfury-newletter .form-desc' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-desc' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .martfury-newletter .form-desc',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_form',
			[
				'label' => esc_html__( 'Form', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'form_spacing',
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
					'{{WRAPPER}} .martfury-newletter .btn-area' => 'margin-top: {{SIZE}}{{UNIT}}',
				],

			]
		);

		$this->add_control(
			'form_height',
			[
				'label'     => esc_html__( 'Height', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], .martfury-newletter .mc4wp-form input[type=text], .martfury-newletter .mc4wp-form input[type=submit]' => 'height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .elementor-page .martfury-newletter.has-icon .mc4wp-form-fields input[type="email"], .elementor-page .martfury-newletter.has-icon .mc4wp-form-fields input[type="submit"]' => 'height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}}',
				],

			]
		);

		$this->add_control(
			'icon',
			[
				'label'     => esc_html__( 'Show Icon', 'martfury-addons' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'martfury-addons' ),
				'label_off' => esc_html__( 'Hide', 'martfury-addons' ),
				'default'   => '',
				'separator'	=> 'before',
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter.has-icon .mc4wp-form-fields::before' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'icon_line_height',
			[
				'label'     => esc_html__( 'Line Height', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter.has-icon .mc4wp-form-fields::before'	=> 'line-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->start_controls_tabs( 'form_content_settings' );

		$this->start_controls_tab( 'form_text_box_tab', [ 'label' => esc_html__( 'Text Box', 'martfury-addons' ) ] );

		$this->add_control(
			'text_box_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]' => 'background-color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'text_box_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text],{{WRAPPER}} .martfury-newletter .mc4wp-form ::-webkit-input-placeholder' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_box_typography',
				'selector' => '{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]',
			]
		);

		$this->add_control(
			'text_box_border_type',
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
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'text_box_border_width',
			[
				'label'     => esc_html__( 'Border Width', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'text_box_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'text_box_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'text_box_border_type!' => 'none',
				],
			]
		);

		$this->add_responsive_control(
			'text_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=email], {{WRAPPER}} .martfury-newletter .mc4wp-form input[type=text]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'form_button_tab', [ 'label' => esc_html__( 'Button', 'martfury-addons' ) ] );

		$this->add_control(
			'button_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=submit]' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=submit]' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=submit]',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'button_border',
				'label'     => esc_html__( 'Border', 'martfury-addons' ),
				'selector'  => '{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=submit]',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'martfury-addons' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .mc4wp-form input[type=submit]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_text',
			[
				'label' => esc_html__( 'Text', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'unit' => 'px',
				],
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-text'	=> 'margin-top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .form-text' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .martfury-newletter .form-text',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_buttons',
			[
				'label' => esc_html__( 'Buttons', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'buttons_item_spacing',
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
					'{{WRAPPER}} .martfury-newletter .btn-area a'               => 'margin-right: {{SIZE}}{{UNIT}}; margin-left: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .martfury-newletter .btn-area > a:last-child'  => 'margin-right: 0',
					'{{WRAPPER}} .martfury-newletter .btn-area > a:first-child' => 'margin-left: 0',
				],
			]
		);
		$this->add_responsive_control(
			'button_alignment',
			[
				'label'     => esc_html__( 'Banner Position', 'martfury-addons' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'' => [
						'title' => esc_html__( 'Left', 'martfury-addons' ),
						'icon'  => 'fa fa-align-left',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'martfury-addons' ),
						'icon'  => 'fa fa-align-center',
					],
					'flex-end'   => [
						'title' => esc_html__( 'Right', 'martfury-addons' ),
						'icon'  => 'fa fa-align-right',
					],
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .martfury-newletter .btn-area' => 'justify-content: {{VALUE}};',
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

		$classes = [
			'martfury-newletter',
			! empty( $settings['icon'] ) ? 'has-icon' : '',
		];

		$image_html = '';

		$image = Group_Control_Image_Size::get_attachment_image_html( $settings );

		if ( $image ) {
			$image_html = sprintf( '<div class="form-image col-md-6 col-xs-12 col-sm-12">%s</div>', $image );
		} else {
			$classes[] = 'no-image';
		}

		$this->add_render_attribute( 'wrapper', 'class', $classes );

		$btn_settings = $settings['btn_setting'];

		$output = [];

		if ( ! empty ( $btn_settings ) ) {
			foreach ( $btn_settings as $index => $item ) {
				$link_key = 'link_' . $index;

				if ( $item['button_link']['is_external'] ) {
					$this->add_render_attribute( $link_key, 'target', '_blank' );
				}

				if ( $item['button_link']['nofollow'] ) {
					$this->add_render_attribute( $link_key, 'rel', 'nofollow' );
				}

				$settings['image']      = $item['btn_image'];
				$settings['image_size'] = 'full';
				$btn_image              = Group_Control_Image_Size::get_attachment_image_html( $settings );

				$link = '';
				if ( $item['button_link']['url'] ) {
					$this->add_render_attribute( $link_key, 'href', $item['button_link']['url'] );
					$link = sprintf( '<a %s>%s</a>', $this->get_render_attribute_string( $link_key ), $btn_image );
				} else {
					$link = sprintf( '<span %s>%s</span>', $this->get_render_attribute_string( $link_key ), $btn_image );
				}

				$output[] = sprintf( '%s', $link );
			}
		}

		$btn_html = sprintf( '<div class="btn-area">%s</div>', implode( '', $output ) );

		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <div class="container">
                <div class="row newsletter-row">
					<?php echo $image_html ?>
                    <div class="form-area col-md-6 col-xs-12 col-sm-12 content-<?php echo esc_attr( $settings['content_align'] ) ?>">
						<?php if ( $settings['title'] ) : ?>
                            <h3 class="form-title"><?php echo $settings['title']; ?></h3>
						<?php endif; ?>
						<?php if ( $settings['desc'] ) : ?>
                            <div class="form-desc"><?php echo $settings['desc']; ?></div>
						<?php endif; ?>
						<?php if ( $settings['form'] ) :
							echo do_shortcode( '[mc4wp_form id="' . esc_attr( $settings['form'] ) . '"]' );
						endif; ?>
						<?php if ( $settings['text'] ) : ?>
                            <div class="form-text"><?php echo $settings['text']; ?></div>
						<?php endif; ?>
						<?php echo $btn_html; ?>
                    </div>
                </div>
            </div>
        </div>
		<?php
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