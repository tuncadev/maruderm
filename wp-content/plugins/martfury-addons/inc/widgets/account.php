<?php

if ( ! class_exists( 'Martfury_Account' ) ) {
	class Martfury_Account extends WP_Widget {
		/**
		 * Holds widget settings defaults, populated in constructor.
		 *
		 * @var array
		 */
		protected $defaults;

		/**
		 * Constructor
		 *
		 * @return Martfury_Account
		 */
		function __construct() {
			$this->defaults = array(
				'title'         => '',
				'login_text'    => esc_html__( 'Login', 'martfury-addons' ),
				'register_text' => esc_html__( 'Register', 'martfury-addons' )
			);

			parent::__construct(
				'tl_account_widget',
				esc_html__( 'Martfury - Account', 'martfury-addons' ),
				array(
					'classname'   => 'mr-account-widget',
					'description' => esc_html__( 'Advanced account widget.', 'martfury-addons' ),
				)
			);
		}

		/**
		 * Display widget
		 *
		 * @param array $args Sidebar configuration
		 * @param array $instance Widget settings
		 *
		 * @return void
		 */
		function widget( $args, $instance ) {
			$instance = wp_parse_args( $instance, $this->defaults );
			extract( $args );

			echo $before_widget;

			if ( $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) ) {
				echo $before_title . $title . $after_title;
			}
			if ( ! is_user_logged_in() ) {
				$account_link = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
				?>
                <ul>
                    <li><i class="wd-login-icon icon-user"></i></li>
                    <li>
                        <a data-account="0" class="login-link mr-login"
                           href="<?php echo esc_url( $account_link ); ?>"><?php echo esc_html( $instance['login_text'] ); ?></a>
                    </li>
					<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
                        <li>
                            <span>/</span>
                        </li>
                        <li>
                            <a data-account="1" class="register-link mr-login"
                               href="<?php echo esc_url( $account_link ); ?>"><?php echo esc_html( $instance['register_text'] ); ?></a>
                        </li>
					<?php endif; ?>
                </ul>

				<?php
			} else {
				$user_menu = function_exists('martfury_nav_vendor_menu') ?  martfury_nav_vendor_menu() : '';
				$user_id   = get_current_user_id();
				if ( empty( $user_menu ) ) {
					$user_menu = function_exists('martfury_nav_user_menu') ?  martfury_nav_user_menu() : '';
				}
				$account      = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
				$account_link = $account;
				$author       = get_user_by( 'id', $user_id );
				$author_name  = $author->display_name;

				if ( class_exists( 'WeDevs_Dokan' ) && in_array( 'seller', $author->roles ) ) {
					$account_link = function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url() : $account_link;
					$shop_info    = get_user_meta( $user_id, 'dokan_profile_settings', true );
					if ( $shop_info && isset( $shop_info['store_name'] ) && $shop_info['store_name'] ) {
						$author_name = $shop_info['store_name'];
					}
				} elseif ( class_exists( 'WCVendors_Pro' ) && in_array( 'vendor', $author->roles ) ) {
					$dashboard_page_id = WCVendors_Pro::get_option( 'dashboard_page_id' );
					if ( $dashboard_page_id ) {
						$account_link = get_permalink( $dashboard_page_id );
					}

				} elseif ( class_exists( 'WC_Vendors' ) && in_array( 'vendor', $author->roles ) ) {
					$vendor_dashboard_page = get_option( 'wcvendors_vendor_dashboard_page_id' );
					$account_link          = get_permalink( $vendor_dashboard_page );

				} elseif ( class_exists( 'WCMp' ) ) {
					if ( function_exists( 'wcmp_vendor_dashboard_page_id' ) && wcmp_vendor_dashboard_page_id() ) {
						$account_link = get_permalink( wcmp_vendor_dashboard_page_id() );
					}
					if ( function_exists( 'get_wcmp_vendor' ) ) {
						$store_user  = get_wcmp_vendor( $user_id );
						$author_name = $store_user->page_title;
					}
				} elseif ( function_exists( 'wcfm_is_vendor' ) && wcfm_is_vendor() ) {
					$pages = get_option( "wcfm_page_options" );
					if ( isset( $pages['wc_frontend_manager_page_id'] ) && $pages['wc_frontend_manager_page_id'] ) {
						$account_link = get_permalink( $pages['wc_frontend_manager_page_id'] );
					}
					global $WCFM;
					$author_name = $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_name_by_vendor( absint( $user_id ) );

				}
				?>
                <ul>
                    <li>
                        <a href="<?php echo esc_url( $account_link ); ?>">
                            <i class="wd-login-icon wd-logged-in icon-user"></i>
                            <span>
                            <?php
                            esc_html_e( 'Hi,', 'martfury-addons' );
                            echo ' ' . esc_html( $author_name );
                            ?>
                        </span>
                        </a>
                        <ul>
                            <li>
								<?php echo implode( ' ', $user_menu ); ?>
                            </li>
                            <li class="logout">
                                <a href="<?php echo esc_url( wp_logout_url( $account ) ); ?>"><?php esc_html_e( 'Logout', 'martfury-addons' ); ?></a>
                            </li>
                        </ul>
                    </li>

                </ul>
				<?php
			}

			echo $after_widget;

		}

		/**
		 * Update widget
		 *
		 * @param array $new_instance New widget settings
		 * @param array $old_instance Old widget settings
		 *
		 * @return array
		 */
		function update( $new_instance, $old_instance ) {
			$new_instance['title']         = strip_tags( $new_instance['title'] );
			$new_instance['login_text']    = strip_tags( $new_instance['login_text'] );
			$new_instance['register_text'] = strip_tags( $new_instance['register_text'] );

			return $new_instance;
		}

		/**
		 * Display widget settings
		 *
		 * @param array $instance Widget settings
		 *
		 * @return void
		 */
		function form( $instance ) {
			$instance = wp_parse_args( $instance, $this->defaults );
			?>

            <p>
                <label
                        for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'martfury-addons' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $instance['title'] ); ?>">
            </p>

            <p>
                <label
                        for="<?php echo esc_attr( $this->get_field_id( 'login_text' ) ); ?>"><?php esc_html_e( 'Login Text', 'martfury-addons' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'login_text' ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'login_text' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $instance['login_text'] ); ?>">
            </p>
            <p>
                <label
                        for="<?php echo esc_attr( $this->get_field_id( 'register_text' ) ); ?>"><?php esc_html_e( 'Register Text', 'martfury-addons' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'register_text' ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'register_text' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $instance['register_text'] ); ?>">
            </p>
			<?php
		}
	}
}