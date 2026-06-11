<?php
/**
 * Sidebar widget.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget wrapper for the Sekki renderer.
 */
class MichiRyu_Sekki_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'michiryu_sekki_widget',
			__( '[MichiRyu-Sekki]', 'michiryu-sekki' ),
			array(
				'description' => __( 'Display the current Japanese 24 Sekki solar term.', 'michiryu-sekki' ),
			)
		);
	}

	/**
	 * Output widget.
	 *
	 * @param array<string,mixed> $args Widget args.
	 * @param array<string,mixed> $instance Widget settings.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$plan  = ! empty( $instance['plan'] ) ? sanitize_key( $instance['plan'] ) : 'standard';
		$show_ko = array_key_exists( 'show_ko', $instance ) ? ! empty( $instance['show_ko'] ) : true;
		$carousel = ! empty( $instance['carousel'] );
		$show_date_stamp = array_key_exists( 'show_date_stamp', $instance ) ? ! empty( $instance['show_date_stamp'] ) : true;
		$sekki = new MichiRyu_Sekki();
		$options = $sekki->get_options();
		$allowed_widget_styles = array( 'text', 'small', 'standard_vertical', 'standard_horizontal', 'banner_tall', 'banner_narrow' );
		$style = ! empty( $instance['style'] ) ? sanitize_key( $instance['style'] ) : ( $options['default_style'] ?? 'standard_vertical' );
		$style = in_array( $style, $allowed_widget_styles, true ) ? $style : ( $options['default_style'] ?? 'standard_vertical' );
		$show_map_link = ! empty( $options['enable_map_link'] ) && ! empty( $options['show_map_in_widget'] );

		echo wp_kses_post( $args['before_widget'] );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . esc_html( $title ) . $args['after_title'] );
		}

		echo $sekki->render( array( 'style' => $style, 'plan' => $plan, 'show_ko' => $show_ko ? 'true' : 'false', 'carousel' => $carousel ? 'true' : 'false', 'show_date_stamp' => $show_date_stamp ? 'true' : 'false', 'show_map_link' => $show_map_link ? 'true' : 'false', 'show_story' => 'false' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Widget form.
	 *
	 * @param array<string,mixed> $instance Widget settings.
	 */
	public function form( $instance ) {
		$title = $instance['title'] ?? '';
		$style_options = array(
			'text'                => __( 'Text', 'michiryu-sekki' ),
			'small'               => __( 'Small', 'michiryu-sekki' ),
			'standard_vertical'   => __( 'Standard vertical', 'michiryu-sekki' ),
			'standard_horizontal' => __( 'Standard horizontal', 'michiryu-sekki' ),
			'banner_tall'         => __( 'Banner tall', 'michiryu-sekki' ),
			'banner_narrow'       => __( 'Banner narrow', 'michiryu-sekki' ),
		);
		$style = in_array( $instance['style'] ?? '', array_keys( $style_options ), true ) ? $instance['style'] : '';
		$plan  = $instance['plan'] ?? 'standard';
		$show_ko = array_key_exists( 'show_ko', $instance ) ? ! empty( $instance['show_ko'] ) : true;
		$carousel = ! empty( $instance['carousel'] );
		$show_date_stamp = array_key_exists( 'show_date_stamp', $instance ) ? ! empty( $instance['show_date_stamp'] ) : true;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'michiryu-sekki' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_html_e( 'Style:', 'michiryu-sekki' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
				<option value="" <?php selected( $style, '' ); ?>><?php esc_html_e( 'Use default setting', 'michiryu-sekki' ); ?></option>
				<?php foreach ( $style_options as $choice => $label ) : ?>
					<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $style, $choice ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'plan' ) ); ?>"><?php esc_html_e( 'Plan:', 'michiryu-sekki' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'plan' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'plan' ) ); ?>">
				<?php foreach ( array( 'minimal', 'standard', 'ikebana', 'banner', 'educational' ) as $choice ) : ?>
					<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $plan, $choice ); ?>><?php echo esc_html( ucfirst( $choice ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_ko' ) ); ?>" value="1" <?php checked( $show_ko ); ?> />
				<?php esc_html_e( 'Show Ko microseason', 'michiryu-sekki' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'carousel' ) ); ?>" value="1" <?php checked( $carousel ); ?> />
				<?php esc_html_e( 'Show as carousel', 'michiryu-sekki' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_date_stamp' ) ); ?>" value="1" <?php checked( $show_date_stamp ); ?> />
				<?php esc_html_e( 'Show current date stamp', 'michiryu-sekki' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Save widget options.
	 *
	 * @param array<string,mixed> $new_instance New settings.
	 * @param array<string,mixed> $old_instance Old settings.
	 * @return array<string,mixed>
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$allowed_styles    = array( 'text', 'small', 'standard_vertical', 'standard_horizontal', 'banner_tall', 'banner_narrow' );
		$allowed_plans     = array( 'minimal', 'standard', 'ikebana', 'banner', 'educational' );
		$instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['style'] = in_array( $new_instance['style'] ?? '', $allowed_styles, true ) ? $new_instance['style'] : '';
		$instance['plan']  = in_array( $new_instance['plan'] ?? '', $allowed_plans, true ) ? $new_instance['plan'] : 'standard';
		$instance['show_ko'] = ! empty( $new_instance['show_ko'] );
		$instance['carousel'] = ! empty( $new_instance['carousel'] );
		$instance['show_date_stamp'] = ! empty( $new_instance['show_date_stamp'] );

		return $instance;
	}
}
