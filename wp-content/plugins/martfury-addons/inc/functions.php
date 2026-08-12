<?php
/**
 * Hooks for share socials
 *
 * @package martfury
 */

/**
 * Share link socials
 *
 * @since  1.0
 */

if ( ! function_exists( 'martfury_addons_share_link_socials' ) ) :
	function martfury_addons_share_link_socials( $title, $link, $media ) {

		if ( ! function_exists( 'martfury_get_option' ) ) {
			return;
		}

		$socials = array();
		if ( is_singular( 'post' ) ) {
			if ( ! intval( martfury_get_option( 'show_post_socials' ) ) ) {
				return;
			}

			$socials = martfury_get_option( 'post_social_icons' );
		} elseif ( is_singular( 'product' ) ) {

			if ( ! intval( martfury_get_option( 'show_product_socials' ) ) ) {
				return;
			}

			$socials = martfury_get_option( 'product_social_icons' );
		}

		$target = '_blank';

		if ( function_exists( 'martfury_is_mobile' ) && martfury_is_mobile() ) {
			$target = '_self';
		}

		$socials_html = '';
		if ( $socials ) {
			if ( in_array( 'facebook', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-facebook martfury-facebook" title="%s" href="http://www.facebook.com/sharer.php?u=%s" target="%s"><i class="ion-social-facebook"></i></a>',
					esc_attr( $title ),
					urlencode( $link ),
					esc_attr($target)
				);
			}

			if ( in_array( 'twitter', $socials ) ) {
				$icon_x = '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 512 512"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>';
				$socials_html .= sprintf(
					'<a class="share-twitter martfury-twitter" href="https://twitter.com/intent/tweet?text=%s&url=%s" title="%s" target="%s">%s</a>',
					esc_attr( $title ),
					urlencode( $link ),
					urlencode( $title ),
					esc_attr($target),
					$icon_x
				);
			}

			if ( in_array( 'google', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-google-plus martfury-google-plus" href="https://plus.google.com/share?url=%s" title="%s" target="%s"><i class="ion-social-googleplus"></i></a>',
					urlencode( $link ),
					esc_attr( $title ),
					esc_attr($target)
				);
			}

			if ( in_array( 'linkedin', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-linkedin martfury-linkedin" href="http://www.linkedin.com/shareArticle?url=%s&title=%s" title="%s" target="%s"><i class="ion-social-linkedin"></i></a>',
					urlencode( $link ),
					esc_attr( $title ),
					urlencode( $title ),
					esc_attr($target)
				);
			}

			if ( in_array( 'vkontakte', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-vkontakte martfury-vkontakte" href="http://vk.com/share.php?url=%s&title=%s&image=%s" title="%s" target="%s"><i class="fa fa-vk"></i></a>',
					urlencode( $link ),
					esc_attr( $title ),
					urlencode( $media ),
					urlencode( $title ),
					esc_attr($target)
				);
			}

			if ( in_array( 'pinterest', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-pinterest martfury-pinterest" href="http://pinterest.com/pin/create/button?media=%s&url=%s&description=%s" title="%s" target="%s"><i class="ion-social-pinterest"></i></a>',
					urlencode( $media ),
					urlencode( $link ),
					esc_attr( $title ),
					urlencode( $title ),
					esc_attr($target)
				);
			}

			if ( in_array( 'whatsapp', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-whatsapp martfury-whatsapp" href="https://api.whatsapp.com/send?text=%s" title="%s" target="%s"><i class="ion-social-whatsapp"></i></a>',
					urlencode( $link ),
					esc_attr( $title ),
					esc_attr($target)
				);
			}

			if ( in_array( 'email', $socials ) ) {
				$socials_html .= sprintf(
					'<a class="share-email martfury-email" href="mailto:?subject=%s&body=%s" title="%s" target="%s"><i class="ion-email"></i></a>',
					esc_html( $title ),
					urlencode( $link ),
					esc_attr( $title ),
					esc_attr($target)
				);
			}

		}

		if ( $socials_html ) {
			$socials_html = apply_filters( 'martfury_addons_share_link_socials', $socials_html );
			printf( '<div class="social-links">%s</div>', $socials_html );
		}
		?>
		<?php
	}

endif;

/**
 * Current Year shortcode
 */

add_shortcode( 'current_year', 'current_year' );

function current_year() {
	return 	date( 'Y' );
}