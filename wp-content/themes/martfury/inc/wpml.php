<?php
/**
 * WPML compatibility functions
 */

class Martfury_WPML {
	const HOT_WORDS_DOMAIN = 'Search Hot Words';
	const HOT_WORDS_PREFIX = 'hot_word_';
	const CATALOG_PRODUCT_CAROUSEL_DOMAIN = 'Catalog Layout 1 - Product Carousel';
	const CATALOG_PRODUCT_CAROUSEL_PREFIX = 'catalog_1_product_carousel_';
	const CATALOG_PRODUCT_CAROUSEL_2_DOMAIN = 'Catalog Layout 2 - Product Carousel';
	const CATALOG_PRODUCT_CAROUSEL_2_PREFIX = 'catalog_2_product_carousel_';
	const CATALOG_PRODUCT_CAROUSEL_3_DOMAIN = 'Catalog Layout 3 - Product Carousel';
	const CATALOG_PRODUCT_CAROUSEL_3_PREFIX = 'catalog_3_product_carousel_';
	/**
	 * The single instance of the class
	 *
	 * @var Martfury_WPML
	 */
	protected static $instance = null;

	/**
	 * Main instance
	 *
	 * @return Martfury_WPML
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'customize_save_after', array( $this, 'register_strings' ) );

		add_filter( 'martfury_search_hot_words', array( $this, 'translate_search_hot_words' ) );
		add_filter( 'martfury_catalog_products_carousel_1', array( $this, 'translate_catalog_product_carousel' ) );
		add_filter( 'martfury_catalog_products_carousel_2', array( $this, 'translate_catalog_product_carousel_2' ) );
		add_filter( 'martfury_catalog_products_carousel_3', array( $this, 'translate_catalog_product_carousel_3' ) );

		add_filter( 'wpml_pb_shortcode_encode', array( $this, 'shortcode_encode_urlencoded_json' ), 10, 3 );
		add_filter( 'wpml_pb_shortcode_decode', array( $this, 'shortcode_decode_urlencoded_json' ), 10, 3 );
	}

	/**
	 * Register special theme strings for translation
	 *
	 * @return void
	 */
	public function register_strings() {
		$this->register_hot_word_strings();
		$this->register_catalog_product_carousel_strings();
		$this->register_catalog_product_carousel_2_strings();
		$this->register_catalog_product_carousel_3_strings();
	}

	/**
	 * Register header search links for translation
	 */
	public function register_hot_word_strings() {
		$links = (array)martfury_get_option( 'header_hot_words' );

		if ( empty( $links ) ) {
			return;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			do_action( 'wpml_register_single_string', self::HOT_WORDS_DOMAIN, self::HOT_WORDS_PREFIX . $count . '_text', $link['text'] );
			do_action( 'wpml_register_single_string', self::HOT_WORDS_DOMAIN, self::HOT_WORDS_PREFIX . $count . '_link', $link['link'] );
		}
	}

	/**
	 * Apply the WPML translation for search quick links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function translate_search_hot_words( $links ) {
		if ( empty( $links ) ) {
			return $links;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			$links[ $id ]['text'] = apply_filters( 'wpml_translate_single_string', $link['text'], self::HOT_WORDS_DOMAIN, self::HOT_WORDS_PREFIX . $count . '_text' );
			$links[ $id ]['link']  = apply_filters( 'wpml_translate_single_string', $link['link'], self::HOT_WORDS_DOMAIN, self::HOT_WORDS_PREFIX . $count . '_link' );
		}

		return $links;
	}

	/**
	 * Register header search links for translation
	 */
	public function register_catalog_product_carousel_strings() {
		$links = (array)martfury_get_option( 'catalog_products_carousel_1' );

		if ( empty( $links ) ) {
			return;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			do_action( 'wpml_register_single_string', self::CATALOG_PRODUCT_CAROUSEL_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_PREFIX . $count . '_title', $link['title'] );
		}
	}

	/**
	 * Apply the WPML translation for search quick links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function translate_catalog_product_carousel( $links ) {
		if ( empty( $links ) ) {
			return $links;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			$links[ $id ]['title'] = apply_filters( 'wpml_translate_single_string', $link['title'], self::CATALOG_PRODUCT_CAROUSEL_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_PREFIX . $count . '_title' );
		}

		return $links;
	}

	/**
	 * Register header search links for translation
	 */
	public function register_catalog_product_carousel_2_strings() {
		$links = (array)martfury_get_option( 'catalog_products_carousel_2' );

		if ( empty( $links ) ) {
			return;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			do_action( 'wpml_register_single_string', self::CATALOG_PRODUCT_CAROUSEL_2_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_2_PREFIX . $count . '_title', $link['title'] );
		}
	}


	/**
	 * Apply the WPML translation for search quick links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function translate_catalog_product_carousel_2( $links ) {
		if ( empty( $links ) ) {
			return $links;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			$links[ $id ]['title'] = apply_filters( 'wpml_translate_single_string', $link['title'], self::CATALOG_PRODUCT_CAROUSEL_2_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_2_PREFIX . $count . '_title' );
		}

		return $links;
	}

	/**
	 * Register header search links for translation
	 */
	public function register_catalog_product_carousel_3_strings() {
		$links = (array)martfury_get_option( 'catalog_products_carousel_3' );

		if ( empty( $links ) ) {
			return;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			do_action( 'wpml_register_single_string', self::CATALOG_PRODUCT_CAROUSEL_3_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_3_PREFIX . $count . '_title', $link['title'] );
		}
	}


	/**
	 * Apply the WPML translation for search quick links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function translate_catalog_product_carousel_3( $links ) {
		if ( empty( $links ) ) {
			return $links;
		}

		foreach ( $links as $id => $link ) {
			$count = $id + 1;

			$links[ $id ]['title'] = apply_filters( 'wpml_translate_single_string', $link['title'], self::CATALOG_PRODUCT_CAROUSEL_3_DOMAIN, self::CATALOG_PRODUCT_CAROUSEL_3_PREFIX . $count . '_title' );
		}

		return $links;
	}

	/**
	 * Encode the param_groups type of js_composer
	 *
	 * @param string $string
	 * @param string $encoding
	 * @param array $original_string
	 * @return string
	 */
	public function shortcode_encode_urlencoded_json( $string, $encoding, $original_string ) {
		if ( 'urlencoded_json' === $encoding ) {
			$output = array();

			foreach ( $original_string as $combined_key => $value ) {
				$parts = explode( '_', $combined_key );
				$i     = array_pop( $parts );
				$key   = implode( '_', $parts );

				$output[ $i ][ $key ] = $value;
			}

			$string = urlencode( json_encode( $output ) );
		}

		return $string;
	}

	/**
	 * Decode urleconded string of param_groups type of js_composer
	 *
	 * @param string $string
	 * @param string $encoding
	 * @param string $original_string
	 * @return string
	 */
	public function shortcode_decode_urlencoded_json( $string, $encoding, $original_string ) {
		if ( 'urlencoded_json' === $encoding ) {
			$rows   = json_decode( urldecode( $original_string ), true );
			$string = array();
			$atts   = array( 'title', 'desc', 'link');

			foreach ( (array) $rows as $i => $row ) {
				foreach ( $row as $key => $value ) {
				if ( in_array( $key, $atts ) ) {
						$string[ $key . '_' . $i ] = array( 'value' => $value, 'translate' => true );
					} else {
						$string[ $key . '_' . $i ] = array( 'value' => $value, 'translate' => false );
					}
				}
			}
		}

		return $string;
	}


	/**
	 * Display the currency switcher of WooCommerce Multilingual
	 */
	public function currency_switcher( $args ) {
		if ( ! function_exists( 'wcml_is_multi_currency_on' ) || ! wcml_is_multi_currency_on() ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'label'     => '',
			'direction' => 'down',
			'class'     => '',
		) );

		$classes = array(
			'currency',
			'currency-switcher--wcml',
			$args['direction'],
			$args['class'],
		);

		printf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		do_action( 'wcml_currency_switcher', array( 'format' => '%code%' ) );

		echo '</div>';
	}
}

Martfury_WPML::instance();
