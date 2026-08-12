<?php

if ( ! class_exists( 'Martfury_Search' ) ) {
	class Martfury_Search extends WP_Widget {
		/**
		 * Holds widget settings defaults, populated in constructor.
		 *
		 * @var array
		 */
		protected $defaults;

		/**
		 * Constructor
		 *
		 * @return Martfury_Search
		 */
		function __construct() {
			$this->defaults = array(
				'title'         => '',
			);

			parent::__construct(
				'mf_search_widget',
				esc_html__( 'Martfury - Search for blog', 'martfury-addons' ),
				array(
					'classname'   => 'mf-search-widget',
					'description' => esc_html__( 'Advanced search widget.', 'martfury-addons' ),
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

			echo sprintf(
				'<form role="search" method="get" class="search-form" action="%s">
					<label>
						<span class="screen-reader-text">%s</span>
						<input type="search" class="search-field" placeholder="%s" value="" name="s">
						<input type="hidden" name="post_type" value="post">
					</label>
					<input type="submit" class="search-submit" value="%s">
				</form>',
				esc_url( home_url( '/' ) ),
				esc_html__( 'Search for:', 'martfury-addons'),
				esc_html__( 'Search …', 'martfury-addons'),
				esc_html__( 'Search', 'martfury-addons')
			);

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
			<?php
		}
	}
}