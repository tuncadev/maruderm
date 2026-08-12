<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Banner Small widget
 */
class Banner_Medium extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-banner-medium';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Banner Medium', 'martfury-addons' );
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
			'section_banner',
			[ 'label' => esc_html__( 'Banner', 'martfury-addons' ) ]
		);
		$this->start_controls_tabs( 'banner_content_settings' );


		$this->start_controls_tab( 'content', [ 'label' => esc_html__( 'Content', 'martfury-addons' ) ] );

		$this->add_control(
			'highlight_title',
			[
				'label'       => esc_html__( 'Highlight Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'This is', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your title', 'martfury-addons' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'highlight_title_position',
			[
				'label'   => esc_html__( 'Position', 'martfury-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''      => esc_html__( 'Left The Title', 'martfury-addons' ),
					'right' => esc_html__( 'Right The Title', 'martfury-addons' ),
					'under' => esc_html__( 'Under The Title', 'martfury-addons' ),
					'above' => esc_html__( 'Above The Title', 'martfury-addons' ),
				],
				'default' => '',

			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( ' the <br> title', 'martfury-addons' ),
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
			'desc',
			[
				'label'       => esc_html__( 'Description', 'martfury-addons' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => __( 'Just <span style="color: #690">$159.99</span>', 'martfury-addons' ),
				'placeholder' => esc_html__( 'Enter your description', 'martfury-addons' ),
				'label_block' => true,
			]
		);


		$this->add_control(
			'button_text', [
				'label'         => esc_html__( 'Button Text', 'martfury-addons' ),
				'type'          => Controls_Manager::TEXT,
				'show_external' => true,
				'default'       => esc_html__( 'Shop Now', 'martfury-addons' ),
			]
		);

		$this->add_control(
			'button_link', [
				'label'         => esc_html__( 'Button URL', 'martfury-addons' ),
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

		$this->start_controls_tab( 'background', [ 'label' => esc_html__( 'Background', 'martfury-addons' ) ] );

		$this->add_control(
			'background_color',
			[
				'label'     => esc_html__( 'Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image',
			[
				'label'     => esc_html__( 'Image', 'martfury-addons' ),
				'type'      => Controls_Manager::MEDIA,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'background-image: url("{{URL}}");',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'height',
			[
				'label'     => esc_html__( 'Height', 'martfury-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'size' => 210,
				],
				'range'     => [
					'px' => [
						'min' => 100,
						'max' => 600,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-content' => 'height: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
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
				'default'    => [
					'top'    => '25',
					'right'  => '30',
					'bottom' => '25',
					'left'   => '30',
				],
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'banner_text_align',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-content' => 'text-align: {{VALUE}}',
				],
				'separator'   => 'before',
			]
		);

		$this->add_responsive_control(
			'banner_bg_position',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'background-position: {{VALUE}};',
				],

			]
		);

		$this->add_responsive_control(
			'banner_bg_position_custom',
			[
				'label'              => esc_html__( 'Custom Background Position', 'martfury-addons' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => [ '%', 'px' ],
				'allowed_dimensions' => [ 'left', 'top' ],
				'default'            => [],
				'selectors'          => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'background-position: {{LEFT}}{{UNIT}} {{TOP}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'banner_bg_size',
			[
				'label'     => esc_html__( 'Background Size', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''        => esc_html__( 'Cover', 'martfury-addons' ),
					'auto'    => esc_html__( 'Auto', 'martfury-addons' ),
					'contain' => esc_html__( 'Contain', 'martfury-addons' ),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'background-size: {{VALUE}};',
				],

			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'banner_border',
				'label'     => esc_html__( 'Border', 'martfury-addons' ),
				'selector'  => '{{WRAPPER}} .mf-elementor-banner-medium .banner-content',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'banner_border_radius',
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
					'{{WRAPPER}} .mf-elementor-banner-medium' => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-featured-image' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_highlight_title',
			[
				'label' => esc_html__( 'Highlight Title', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'highlight_title_spacing',
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
					'{{WRAPPER}} .mf-elementor-banner-medium.highlight-title-above .banner-title .hl-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'highlight_title_position' => 'above',
				],
			]
		);

		$this->add_control(
			'highlight_title_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-title .hl-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'highlight_title_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-medium .banner-title .hl-title',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-title' => 'color: {{VALUE}}',

				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-medium .banner-title',
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
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-desc' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => esc_html__( 'Text Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-desc' => 'color: {{VALUE}}',

				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-medium .banner-desc',
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
			'show_button',
			[
				'label'     => esc_html__( 'Show Button', 'martfury-addons' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'block' => esc_html__( 'Show', 'martfury-addons' ),
					'none'  => esc_html__( 'Hide', 'martfury-addons' ),
				],
				'default'   => 'block',
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button' => 'display: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'martfury-addons' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'border-width: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'after',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button' => 'border-color: {{VALUE}};',
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
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button:hover' => 'color: {{VALUE}};',
				],

			]
		);

		$this->add_control(
			'button_hover_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'martfury-addons' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mf-elementor-banner-medium .banner-button .btn-button:hover' => 'border-color: {{VALUE}};',
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

		$this->add_render_attribute( 'wrapper', 'class', [
			'mf-elementor-banner-medium',
			'highlight-title-' . $settings['highlight_title_position']
		] );

		if ( $settings['button_link']['is_external'] ) {
			$this->add_render_attribute( 'link', 'target', '_blank' );
		}

		if ( $settings['button_link']['nofollow'] ) {
			$this->add_render_attribute( 'link', 'rel', 'nofollow' );
		}

		if ( $settings['button_link']['url'] ) {
			$this->add_render_attribute( 'link', 'href', $settings['button_link']['url'] );
		}

		$title           = $settings['title'];
		$highlight_title = $settings['highlight_title'];
		if ( ! empty( $highlight_title ) ) {
			$highlight_title = sprintf( '<span class="hl-title">%s</span>', $settings['highlight_title'] );
		}

		if ( in_array( $settings['highlight_title_position'], array( 'right', 'under' ) ) ) {
			$title .= $highlight_title;
		} else {
			$title = $highlight_title . $title;
		}
		$title_html = $title ? sprintf( '<%1$s class="banner-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), $title ) : '';
		?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <div class="banner-featured-image"></div>
			<?php if ( $settings['button_link']['url'] ) : ?>
                <a class="link" <?php echo $this->get_render_attribute_string( 'link' ); ?>></a>
			<?php endif; ?>
            <div class="banner-content">
                <?php echo $title_html; ?>
                <div class="banner-desc"><?php echo $settings['desc']; ?></div>
				<?php if ( ! empty( $settings['button_text'] ) ) : ?>
                    <div class="banner-button">
                        <a class="btn-button" <?php echo $this->get_render_attribute_string( 'link' ); ?>><?php echo $settings['button_text']; ?></a>
                    </div>
				<?php endif; ?>
            </div>
        </div>
		<?php
	}


}