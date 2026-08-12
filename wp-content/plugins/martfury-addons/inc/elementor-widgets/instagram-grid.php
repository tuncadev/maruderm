<?php

namespace MartfuryAddons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Martfury\Addons\Elementor\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Instagram_Grid widget
 */
class Instagram_Grid extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'martfury-instagram-grid';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Instagram Grid', 'martfury-addons' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-instagram-gallery';
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
		$this->section_content();
		$this->section_style();
	}

	/**
	 * Section Content
	 */
	protected function section_content() {
		$this->start_controls_section(
			'section_content',
			[ 'label' => esc_html__( 'Content', 'martfury-addons' ) ]
		);

		$this->add_control(
			'instagram_type',
			[
				'label' => esc_html__( 'Instagram type', 'martfury-addons' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'token' 	=> 'Token',
					'custom' 	=> 'Custom',
				],
				'default' => 'token',
			]
		);

		$this->add_control(
			'access_token',
			[
				'label'       => esc_html__( 'Access Token', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Enter your access token', 'martfury-addons' ),
				'label_block' => true,
				'conditions' => [
					'terms' => [
						[
							'name' => 'instagram_type',
							'value' => 'token',
						],
					],
				],
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'image',
			[
				'label' => esc_html__( 'Choose Image', 'martfury-addons' ),
				'type' => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'link',
			[
				'label' => __( 'Link', 'martfury-addons' ),
				'type' => Controls_Manager::URL,
				'placeholder' => __( 'https://your-link.com', 'martfury-addons' ),
			]
		);

		$repeater->add_control(
			'caption',
			[
				'label' => esc_html__( 'Caption', 'martfury-addons' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'placeholder' => esc_html__( 'Enter your caption', 'martfury-addons' ),
				'rows' => 4,
			]
		);

		$this->add_control(
			'image_list',
			[
				'label'         => esc_html__( 'Image List', 'martfury-addons' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'prevent_empty' => false,
				'conditions' => [
					'terms' => [
						[
							'name' => 'instagram_type',
							'value' => 'custom',
						],
					],
				],
			]
		);

		$this->add_control(
			'limit',
			[
				'label'       => esc_html__( 'Limit', 'martfury-addons' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 9,
				'conditions' => [
					'terms' => [
						[
							'name' => 'instagram_type',
							'value' => 'token',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'martfury-addons' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 10,
				'default' => 5,
				'prefix_class' => 'columns-%s',
			]
		);

		$this->add_control(
			'username',
			[
				'label'       => esc_html__( 'Username', 'martfury-addons' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'username', 'martfury-addons' ),
				'conditions' => [
					'terms' => [
						[
							'name' => 'instagram_type',
							'value' => 'token',
						],
					],
				],
			]
		);


		$this->end_controls_section();
	}

	/**
	 * Section Style
	 */

	protected function section_style() {
		$this->start_controls_section(
			'style_general',
			[
				'label' => __( 'Content', 'martfury-addons' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'item_style',
			[
				'label' => esc_html__( 'Item', 'martfury-addons' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'item_gap',
			[
				'label'      => __( 'Gap', 'martfury-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .martfury-instagram-grid .instagram-wrapper' => 'margin: calc(-{{SIZE}}{{UNIT}}/2);',
					'{{WRAPPER}} .martfury-instagram-grid .instagram-item' => 'padding:calc({{SIZE}}{{UNIT}}/2);',
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

		$this->add_render_attribute(
			'wrapper', 'class', [
				'martfury-instagram-grid',
				'martfury-element-columns',
			]
		);

		$output    = [];

		if ( $settings['instagram_type'] === 'token' ) {
			$instagram = $this->get_instagram_get_photos_by_token( $settings['limit'],$settings['access_token'] );

			$user = apply_filters( 'martfury_get_instagram_user', array() );
			if ( empty( $user ) ) {
				$user = $this->get_instagram_user( $settings['access_token'] );
			}

			if ( is_wp_error( $instagram ) ) {
				return $instagram->get_error_message();
			}

			if ( ! $instagram ) {
				return;
			}

			$count = 1;

			$output[] = sprintf('<ul class="instagram-wrapper">');

			foreach ( $instagram as $data ) {

				if ( $count > intval( $settings['limit'] ) ) {
					break;
				}

				$output[] = '<li class="instagram-item"><a target="_blank" href="' . esc_url( $data['link'] ) . '"><i class="social social_instagram"></i><img src="' . esc_url( $data['images']['thumbnail'] ) . '" loading="lazy" alt="' . esc_attr( $data['caption'] ) . '"></a></li>';

				$count ++;
			}
			$output[] = sprintf('</ul>');
		} else {

			$output[] = sprintf('<ul class="instagram-wrapper">');

			foreach ( $settings['image_list'] as $index => $item ) {
				if ( $item['image']['url'] ) {
					$this->add_link_attributes( 'icon-link', $item['link'] );
					$link = $item['link']['url'] ? $item['link']['url'] : '#';
					$target = $item['link']['is_external'] ? ' target="_blank"' : '';
					$nofollow = $item['link']['nofollow'] ? ' rel="nofollow"' : '';

					$output[] = '<li class="instagram-item">';
						$output[] = '<a href="' . $link . '" ' . $target . $nofollow . $this->get_render_attribute_string( 'icon-link' ) . '>';
							$output[] = '<i class="social social_instagram"></i>';
							$output[] = '<img src="' . esc_url( $item['image']['url'] ) . '" loading="lazy" alt="' . esc_attr( $item['caption'] ) . '">';
						$output[] = '</a>';
					$output[] = '</li>';
				}
			}

			$output[] = sprintf('</ul>');
		}

		echo sprintf(
			'<div %s>%s</div>',
			$this->get_render_attribute_string( 'wrapper' ),
			implode( '', $output )
		);

	}

	/**
	 * Get Instagram images
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit
	 *
	 * @return array|\WP_Error
	 */
	public static function get_instagram_get_photos_by_token( $limit, $access_token ) {
		if ( empty( $access_token ) ) {
			return new \WP_Error( 'instagram_no_access_token', esc_html__( 'No access token', 'martfury-addons' ) );
		}

		$user = self::get_instagram_user( $access_token );
		if ( ! $user || is_wp_error( $user ) ) {
			return $user;
		}

		if ( isset( $user['error'] ) ) {
			return new \WP_Error( 'instagram_no_images', esc_html__( 'Instagram did not return any images. Please check your access token', 'martfury-addons' ) );

		} else {
			$transient_key = 'martfury_instagram_photos_' . sanitize_title_with_dashes( $user['username'] . '__' . $limit );
			$images        = get_transient( $transient_key );
			$images = apply_filters( 'martfury_get_instagram_photos', $images, $access_token );

			if ( false !== $images ) {
				return $images;
			}

			$images = array();
			$next   = false;
			while ( count( $images ) < $limit ) {
				if ( ! $next ) {
					$fetched = self::fetch_instagram_media( $access_token );
				} else {
					$fetched = self::fetch_instagram_media( $next );
				}
				if ( is_wp_error( $fetched ) ) {
					break;
				}

				$images = array_merge( $images, $fetched['images'] );
				$next   = $fetched['paging']['cursors']['after'];
			}

			if ( ! empty( $images ) ) {
				set_transient( $transient_key, $images, 2 * 3600 ); // Cache for 2 hours.
			} else {
				return new \WP_Error( 'instagram_no_images', esc_html__( 'Instagram did not return any images.', 'martfury-addons' ) );
			}
		}
	}

	/**
	 * Fetch photos from Instagram API
	 *
	 * @since 1.0.0
	 *
	 * @param  string $access_token
	 *
	 * @return array
	 */
	public static function fetch_instagram_media( $access_token ) {
		$url = add_query_arg( array(
			'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url',
			'access_token' => $access_token,
		), 'https://graph.instagram.com/me/media' );

		$remote = wp_remote_retrieve_body( wp_remote_get( $url ) );
		$data   = json_decode( $remote, true );

		$images = array();
		if ( isset( $data['error'] ) ) {
			return new \WP_Error( 'instagram_error', $data['error']['message'] );
		} else {
			foreach ( $data['data'] as $media ) {
				if( strtolower( $media['media_type'] ) == 'video'  ) {
					$media_data = array(
						'thumbnail' => $media['thumbnail_url'],
						'original'  => $media['thumbnail_url'],
					);
				} else {
					$media_data =  array(
						'thumbnail' => $media['media_url'],
						'original'  => $media['media_url'],
					);
				}

				$images[] = array(
					'type'    => $media['media_type'],
					'caption' => isset( $media['caption'] ) ? $media['caption'] : $media['id'],
					'link'    => $media['permalink'],
					'images'  => $media_data
				);

			}
		}

		return array(
			'images' => $images,
			'paging' => $data['paging'],
		);
	}

	/**
	 * Get user data
	 *
	 * @since 1.0.0
	 *
	 * @return bool|\WP_Error|array
	 */
	public static function get_instagram_user( $access_token ) {
		if ( empty( $access_token ) ) {
			return new \WP_Error( 'no_access_token', esc_html__( 'No access token', 'martfury-addons' ) );
		}
		$transient_key = 'martfury_instagram_user_' . $access_token;
		$user = get_transient( $transient_key);
		$user = apply_filters( 'martfury_get_instagram_user', $user );
		if ( false === $user ) {
			$url  = add_query_arg( array(
				'fields'       => 'id,username',
				'access_token' => $access_token
			), 'https://graph.instagram.com/me' );
			$data = wp_remote_get( $url );
			$data = wp_remote_retrieve_body( $data );
			if ( ! $data ) {
				return new \WP_Error( 'no_user_data', esc_html__( 'No user data received', 'martfury-addons' ) );
			}
			$user = json_decode( $data, true );
			if ( ! empty( $data ) ) {
				set_transient( $transient_key, $user, 2592000 ); // Cache for one month.
			}
		}
		return $user;
	}
}
