<?php
/**
 * Main plugin class.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end integration for shortcode, block assets, and rendering.
 */
class MichiRyu_Sekki {
	const OPTION_NAME = 'michiryu_sekki_options';

	/**
	 * Allowed display styles.
	 *
	 * @var array<int,string>
	 */
	private $styles = array( 'text', 'small', 'standard_vertical', 'standard_horizontal', 'banner_tall', 'banner_narrow' );

	/**
	 * Allowed plan names.
	 *
	 * @var array<int,string>
	 */
	private $plans = array( 'minimal', 'standard', 'ikebana', 'banner', 'educational' );

	/**
	 * Register hooks.
	 */
	public function init() {
		load_plugin_textdomain( 'michiryu-sekki', false, dirname( plugin_basename( MICHIRYU_SEKKI_FILE ) ) . '/languages' );

		add_shortcode( 'michiryu_sekki', array( $this, 'shortcode' ) );
		add_shortcode( 'michiryu_sekki_map', array( $this, 'map_shortcode' ) );
		add_shortcode( 'michiryu_journey', array( $this, 'journey_shortcode' ) );
		add_shortcode( 'michiryu_story', array( $this, 'story_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		if ( is_admin() ) {
			$admin = new MichiRyu_Sekki_Admin( $this );
			$admin->init();
		}
	}

	/**
	 * Register styles without enqueueing until render time.
	 */
	public function register_assets() {
		$style_path     = MICHIRYU_SEKKI_PATH . 'assets/css/michiryu-sekki.css';
		$script_path    = MICHIRYU_SEKKI_PATH . 'assets/js/michiryu-sekki.js';
		$style_version  = file_exists( $style_path ) ? MICHIRYU_SEKKI_VERSION . '.' . filemtime( $style_path ) : MICHIRYU_SEKKI_VERSION;
		$script_version = file_exists( $script_path ) ? MICHIRYU_SEKKI_VERSION . '.' . filemtime( $script_path ) : MICHIRYU_SEKKI_VERSION;

		wp_register_style(
			'michiryu-sekki',
			MICHIRYU_SEKKI_URL . 'assets/css/michiryu-sekki.css',
			array(),
			$style_version
		);

		wp_register_script(
			'michiryu-sekki',
			MICHIRYU_SEKKI_URL . 'assets/js/michiryu-sekki.js',
			array(),
			$script_version,
			true
		);
	}

	/**
	 * Register a simple dynamic Gutenberg block.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'michiryu/sekki',
			array(
				'api_version'     => 2,
				'title'           => __( 'MichiRyu Sekki', 'michiryu-sekki' ),
				'description'     => __( 'Display the current Japanese 24 Sekki solar term.', 'michiryu-sekki' ),
				'category'        => 'widgets',
				'icon'            => 'palmtree',
				'attributes'      => array(
					'style' => array(
						'type'    => 'string',
						'default' => '',
					),
					'plan'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'showKo' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'carousel' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showDateStamp' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'signaturePosition' => array(
						'type'    => 'string',
						'default' => '',
					),
					'signatureSize' => array(
						'type'    => 'string',
						'default' => '',
					),
					'signatureOpacity' => array(
						'type'    => 'number',
						'default' => 1,
					),
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Register widget.
	 */
	public function register_widget() {
		register_widget( 'MichiRyu_Sekki_Widget' );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array<string,string> $atts Attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'style'                  => '',
				'plan'                   => '',
				'show_kanji'             => '',
				'show_romanized'         => '',
				'show_english'           => '',
				'show_date_range'        => '',
				'show_description'       => '',
				'show_image'             => '',
				'show_sekki_image'       => '',
				'show_ko'                => '',
				'show_ikebana_materials' => '',
				'carousel'               => '',
				'show_date_stamp'        => '',
				'show_story'             => '',
				'signature_position'     => '',
				'signature_size'         => '',
				'signature_opacity'      => '',
				'show_map_link'          => '',
				'map_link'               => '',
			),
			$atts,
			'michiryu_sekki'
		);

		if ( $this->resolve_bool_arg( $atts['map_link'], false ) ) {
			$atts['show_map_link'] = 'true';
		}

		return $this->render( $atts );
	}

	/**
	 * Map shortcode handler.
	 *
	 * @param array<string,string> $atts Attributes.
	 * @return string
	 */
	public function map_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'current_only' => 'false',
				'layout'       => '',
			),
			$atts,
			'michiryu_sekki_map'
		);

		wp_enqueue_style( 'michiryu-sekki' );
		wp_enqueue_script( 'michiryu-sekki' );

		return $this->render_map(
			array(
				'current_only' => $this->resolve_bool_arg( $atts['current_only'], false ),
				'is_modal'     => false,
				'layout'       => sanitize_key( $atts['layout'] ),
			),
			$this->get_options()
		);
	}

	/**
	 * Journey shortcode handler.
	 *
	 * @param array<string,string> $atts Attributes.
	 * @return string
	 */
	public function journey_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'variant' => 'large',
			),
			$atts,
			'michiryu_journey'
		);

		return $this->render_journey(
			array(
				'variant' => 'widget' === sanitize_key( $atts['variant'] ) ? 'widget' : 'large',
			)
		);
	}

	/**
	 * Story reader shortcode handler.
	 *
	 * @param array<string,string> $atts Attributes.
	 * @return string
	 */
	public function story_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'story'           => '',
				'ko'              => '',
				'sekki'           => '',
				'show_navigation' => 'true',
			),
			$atts,
			'michiryu_story'
		);

		return $this->render_story_reader(
			array(
				'story'           => sanitize_title( $atts['story'] ),
				'ko'              => absint( $atts['ko'] ),
				'sekki'           => sanitize_key( $atts['sekki'] ),
				'show_navigation' => $this->resolve_bool_arg( $atts['show_navigation'], true ),
			)
		);
	}

	/**
	 * Block callback.
	 *
	 * @param array<string,string> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		if ( isset( $attributes['showKo'] ) ) {
			$attributes['show_ko'] = $attributes['showKo'] ? 'true' : 'false';
		}

		if ( isset( $attributes['carousel'] ) ) {
			$attributes['carousel'] = $attributes['carousel'] ? 'true' : 'false';
		}

		if ( isset( $attributes['showDateStamp'] ) ) {
			$attributes['show_date_stamp'] = $attributes['showDateStamp'] ? 'true' : 'false';
		}

		if ( isset( $attributes['signaturePosition'] ) ) {
			$attributes['signature_position'] = $attributes['signaturePosition'];
		}

		if ( isset( $attributes['signatureSize'] ) ) {
			$attributes['signature_size'] = $attributes['signatureSize'];
		}

		if ( isset( $attributes['signatureOpacity'] ) ) {
			$attributes['signature_opacity'] = $attributes['signatureOpacity'];
		}

		return $this->render( $attributes );
	}

	/**
	 * Render the current season display.
	 *
	 * @param array<string,mixed> $args Render arguments.
	 * @return string
	 */
	public function render( $args = array() ) {
		wp_enqueue_style( 'michiryu-sekki' );

		$options = $this->get_options();
		$args    = $this->normalize_args( $args, $options );
		$timestamp_utc = $this->get_current_timestamp_utc();
		$display_timezone = $this->get_display_timezone();
		$args['timestamp_utc'] = $timestamp_utc;
		$args['display_timezone'] = $display_timezone;

		$has_sekki_image = $args['show_sekki_image'] && $this->should_show_image( $args['style'], $args['plan'] );

		if ( $args['carousel'] ) {
			wp_enqueue_script( 'michiryu-sekki' );
			return $this->render_carousel( $args, $options );
		}

		$season  = MichiRyu_Sekki_Data::get_current( $timestamp_utc, $display_timezone );
		$next    = MichiRyu_Sekki_Data::get_next( $season['slug'] );
		$ko      = $args['show_ko'] ? MichiRyu_Sekki_Data::get_current_ko( $timestamp_utc, $display_timezone ) : null;
		$story   = $args['show_story'] ? $this->get_current_story_for_season( $season, $timestamp_utc, $display_timezone ) : array();
		$image   = $has_sekki_image ? $this->render_sekki_image( $season, $options, $args ) : '';
		$ko_html = $ko ? $this->render_ko( $ko, $options, $args ) : '';

		if ( $has_sekki_image || $args['show_map_link'] || ! empty( $story ) ) {
			wp_enqueue_script( 'michiryu-sekki' );
		}

		$classes = array(
			'michiryu-sekki',
			'michiryu-sekki--' . $args['style'],
			'michiryu-sekki--plan-' . $args['plan'],
			'michiryu-sekki--image-' . $options['image_style'],
			'michiryu-sekki--icon-' . $options['icon_style'],
			empty( $image ) ? 'michiryu-sekki--no-image' : 'michiryu-sekki--has-image',
			empty( $ko_html ) ? 'michiryu-sekki--no-ko' : 'michiryu-sekki--has-ko',
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if ( ! empty( $image ) ) : ?>
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<div class="michiryu-sekki__body">
				<div class="michiryu-sekki__detail-panel">
					<?php echo $this->render_heading( $season, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php if ( 'educational' === $args['plan'] ) : ?>
						<p class="michiryu-sekki__education">
							<?php esc_html_e( 'The 24 Sekki are traditional Japanese solar terms: 24 divisions of the year based on the sun’s movement, each lasting about 15 days.', 'michiryu-sekki' ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $args['show_date_range'] && in_array( $args['plan'], array( 'standard', 'banner', 'educational' ), true ) ) : ?>
						<p class="michiryu-sekki__date"><span><?php esc_html_e( 'Sekki', 'michiryu-sekki' ); ?></span> <?php echo esc_html( $season['date_range'] ); ?></p>
					<?php endif; ?>

					<?php if ( $args['show_description'] && in_array( $args['plan'], array( 'standard', 'educational' ), true ) ) : ?>
						<p class="michiryu-sekki__description"><?php echo esc_html( $season['description'] ); ?></p>
					<?php endif; ?>

					<?php echo $this->render_season_materials_list( $season, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php if ( 'banner' === $args['plan'] || in_array( $args['style'], array( 'banner_tall', 'banner_narrow' ), true ) ) : ?>
						<p class="michiryu-sekki__phrase"><?php echo esc_html( $season['phrase'] ); ?></p>
					<?php endif; ?>

					<?php if ( 'educational' === $args['plan'] ) : ?>
						<p class="michiryu-sekki__next">
							<?php
							printf(
								/* translators: 1: next season romanized name, 2: days count. */
								esc_html__( 'Next: %1$s in %2$d days.', 'michiryu-sekki' ),
								esc_html( $next['romaji'] ),
								absint( MichiRyu_Sekki_Data::days_until_next( $season, $timestamp_utc, $display_timezone ) )
							);
							?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $ko_html ) ) : ?>
						<?php echo $ko_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>

				<div class="michiryu-sekki__story-panel">
					<?php if ( ! empty( $story ) ) : ?>
						<?php echo $this->render_story_teaser( $season, $story, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>

					<?php if ( $args['show_map_link'] || ! empty( $story ) ) : ?>
						<?php echo $this->render_map_link( $options, $season, $story, $args['show_map_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $options['custom_css'] ) ) : ?>
			<style id="michiryu-sekki-custom-css">
				<?php echo wp_strip_all_tags( $options['custom_css'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</style>
		<?php endif; ?>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the simple seasonal journey entry screen.
	 *
	 * @return string
	 */
	public function render_journey( $args = array() ) {
		wp_enqueue_style( 'michiryu-sekki' );
		wp_enqueue_script( 'michiryu-sekki' );

		$options = $this->get_options();
		$args    = wp_parse_args(
			$args,
			array(
				'variant' => 'large',
			)
		);
		$timestamp_utc = $this->get_current_timestamp_utc();
		$display_timezone = $this->get_display_timezone();
		$season  = MichiRyu_Sekki_Data::get_current( $timestamp_utc, $display_timezone );
		$ko      = MichiRyu_Sekki_Data::get_current_ko( $timestamp_utc, $display_timezone );
		$story   = $this->get_current_story_for_season( $season, $timestamp_utc, $display_timezone );
		$next    = MichiRyu_Sekki_Data::get_next( $season['slug'] );
		$story_id = $story['id'] ?? '';
		$excerpt = ! empty( $story['body_text'] ) ? wp_trim_words( (string) $story['body_text'], 28, '...' ) : '';
		$story_url = ! empty( $story_id ) ? $this->get_story_reader_url( $story ) : '';
		$stories = $this->get_all_stories();
		$image = $this->render_journey_image( $season, $options );
		$classes = array( 'michiryu-sekki-journey', 'michiryu-sekki-journey--' . sanitize_html_class( $args['variant'] ) );

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="michiryu-journey" aria-label="<?php esc_attr_e( 'MichiRyu seasonal journey', 'michiryu-sekki' ); ?>">
			<div class="michiryu-sekki-journey__top">
				<div class="michiryu-sekki-journey__content">
					<div class="michiryu-sekki-journey__header">
						<p class="michiryu-sekki-journey__eyebrow"><?php esc_html_e( 'Today in the seasonal journey', 'michiryu-sekki' ); ?></p>
						<h2><?php echo esc_html( $season['romaji'] ); ?> <span><?php echo esc_html( $season['kanji'] ); ?></span></h2>
						<p><?php echo esc_html( $season['english_name'] . ' - ' . $season['date_range'] ); ?></p>
					</div>

					<div class="michiryu-sekki-journey__grid">
						<article class="michiryu-sekki-journey__card">
							<p class="michiryu-sekki-journey__label"><?php esc_html_e( 'Current Sekki', 'michiryu-sekki' ); ?></p>
							<h3><?php echo esc_html( $season['romaji'] ); ?></h3>
							<p><?php echo esc_html( $season['description'] ); ?></p>
							<p class="michiryu-sekki-journey__meta">
								<?php
								printf(
									esc_html__( 'Next: %s', 'michiryu-sekki' ),
									esc_html( $next['romaji'] )
								);
								?>
							</p>
						</article>

						<article class="michiryu-sekki-journey__card">
							<p class="michiryu-sekki-journey__label"><?php esc_html_e( 'Current Ko', 'michiryu-sekki' ); ?></p>
							<h3><?php echo esc_html( sprintf( __( 'Ko %d', 'michiryu-sekki' ), absint( $ko['ko_number'] ?? 0 ) ) ); ?></h3>
							<p><strong><?php echo esc_html( $ko['english_name'] ?? '' ); ?></strong></p>
							<p><?php echo esc_html( $ko['short_description'] ?? '' ); ?></p>
						</article>
					</div>
				</div>

				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<article class="michiryu-sekki-journey__story">
				<p class="michiryu-sekki-journey__label"><?php esc_html_e( 'Story', 'michiryu-sekki' ); ?></p>
				<h3><?php echo esc_html( $story['title'] ?? __( 'Story coming soon', 'michiryu-sekki' ) ); ?></h3>
				<?php if ( ! empty( $excerpt ) ) : ?>
					<p><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<div class="michiryu-sekki-journey__actions">
					<?php if ( ! empty( $story_id ) ) : ?>
						<a class="michiryu-sekki-journey__button michiryu-sekki-journey__button--primary" href="<?php echo esc_url( $story_url ); ?>"><?php esc_html_e( 'Read today’s story', 'michiryu-sekki' ); ?></a>
						<a class="michiryu-sekki-journey__button" href="<?php echo esc_url( $story_url ); ?>" data-mrs-continue-journey><?php esc_html_e( 'Continue Journey', 'michiryu-sekki' ); ?></a>
					<?php else : ?>
						<button class="michiryu-sekki-journey__button" type="button" disabled><?php esc_html_e( 'Continue Journey', 'michiryu-sekki' ); ?></button>
					<?php endif; ?>
				</div>
			</article>

			<div class="michiryu-sekki-journey__progress" aria-label="<?php esc_attr_e( 'Reading progress', 'michiryu-sekki' ); ?>" data-mrs-journey-progress>
				<div>
					<p class="michiryu-sekki-journey__label"><?php esc_html_e( 'Reading Progress', 'michiryu-sekki' ); ?></p>
					<strong data-mrs-read-count><?php esc_html_e( 'Reading progress will appear here', 'michiryu-sekki' ); ?></strong>
				</div>
				<div class="michiryu-sekki-journey__bar" aria-hidden="true"><span data-mrs-read-bar style="width: 0%;"></span></div>
				<?php echo $this->render_story_index( $stories ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php

		if ( isset( $_GET['mrs_story'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'inline' === ( $options['reader_open_behavior'] ?? 'modal' ) ) {
				echo $this->render_story_reader( array( 'show_navigation' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo $this->render_story_reader_modal( array( 'show_navigation' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		return ob_get_clean();
	}

	/**
	 * Render the stable story reading template.
	 *
	 * @param array<string,mixed> $args Render arguments.
	 * @return string
	 */
	private function render_story_reader( $args = array() ) {
		wp_enqueue_style( 'michiryu-sekki' );
		wp_enqueue_script( 'michiryu-sekki' );

		$options = $this->get_options();
		$story   = $this->resolve_story_reader_story( $args );

		if ( empty( $story ) ) {
			return sprintf(
				'<section class="michiryu-sekki-story-reader"><p>%s</p></section>',
				esc_html__( 'This story is not available yet.', 'michiryu-sekki' )
			);
		}

		$season = $this->get_story_season( $story );
		$ko     = $this->get_story_ko( $story );
		$image  = ! empty( $ko ) ? $this->get_asset_url( 'ko', $story['related_ko_icon'] ?? ( $ko['icon_file'] ?? '' ), '', $options ) : '';
		$characters = $this->get_story_character_names( $story );
		$show_navigation = ! empty( $args['show_navigation'] );

		ob_start();
		?>
		<article class="michiryu-sekki-story-reader" id="michiryu-story-reader" aria-labelledby="michiryu-story-reader-title" data-mrs-story-reader data-story="<?php echo esc_attr( $story['id'] ?? '' ); ?>" data-ko="<?php echo esc_attr( $story['ko_number'] ?? '' ); ?>">
			<a class="michiryu-sekki-story-reader__close" href="<?php echo esc_url( $this->get_story_reader_close_url() ); ?>" aria-label="<?php esc_attr_e( 'Close story', 'michiryu-sekki' ); ?>">×</a>
			<header class="michiryu-sekki-story-reader__header">
				<div class="michiryu-sekki-story-reader__intro">
					<p class="michiryu-sekki-story-reader__eyebrow"><?php esc_html_e( 'Seasonal Story', 'michiryu-sekki' ); ?></p>
					<h2 id="michiryu-story-reader-title"><?php echo esc_html( $story['title'] ?? '' ); ?></h2>
					<?php if ( ! empty( $season ) ) : ?>
						<p class="michiryu-sekki-story-reader__season">
							<?php echo esc_html( $season['romaji'] . ' ' . $season['kanji'] . ' - ' . $season['english_name'] ); ?>
						</p>
					<?php endif; ?>
					<?php if ( ! empty( $ko ) ) : ?>
						<p class="michiryu-sekki-story-reader__ko">
							<?php
							printf(
								/* translators: 1: ko number, 2: ko English name, 3: ko date range. */
								esc_html__( 'Ko %1$d: %2$s - %3$s', 'michiryu-sekki' ),
								absint( $ko['ko_number'] ),
								esc_html( $ko['english_name'] ),
								esc_html( $ko['date_range'] )
							);
							?>
						</p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $image ) ) : ?>
					<figure class="michiryu-sekki-story-reader__image michiryu-sekki-image-wrap">
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( ( $ko['romaji'] ?? '' ) . ' - ' . ( $ko['english_name'] ?? '' ) ); ?>" loading="lazy" draggable="false" />
					</figure>
				<?php endif; ?>
			</header>

			<?php echo $this->render_story_reader_path( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<div class="michiryu-sekki-story-reader__body">
				<section class="michiryu-sekki-story-reader__story" aria-label="<?php esc_attr_e( 'Story', 'michiryu-sekki' ); ?>">
					<?php echo wp_kses_post( $story['body_html'] ?? '' ); ?>
				</section>

				<?php if ( ! empty( $story['spotlight'] ) || ! empty( $characters ) ) : ?>
					<section class="michiryu-sekki-story-reader__spotlight" aria-labelledby="michiryu-story-reader-character">
						<h3 id="michiryu-story-reader-character"><?php esc_html_e( 'Character Spotlight', 'michiryu-sekki' ); ?></h3>
						<?php if ( ! empty( $characters ) ) : ?>
							<p class="michiryu-sekki-story-reader__characters"><?php echo esc_html( implode( ', ', $characters ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $story['spotlight'] ) ) : ?>
							<p><?php echo esc_html( $story['spotlight'] ); ?></p>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<section class="michiryu-sekki-story-reader__reflection" aria-labelledby="michiryu-story-reader-reflection">
					<h3 id="michiryu-story-reader-reflection"><?php esc_html_e( 'Ikebana Reflection', 'michiryu-sekki' ); ?></h3>
					<dl>
						<?php if ( ! empty( $story['materials'] ) ) : ?>
							<div><dt><?php esc_html_e( 'Materials', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['materials'] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( ! empty( $story['theme'] ) ) : ?>
							<div><dt><?php esc_html_e( 'Theme', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['theme'] ); ?></dd></div>
						<?php endif; ?>
						<?php if ( ! empty( $story['lesson'] ) ) : ?>
							<div><dt><?php esc_html_e( 'Lesson', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['lesson'] ); ?></dd></div>
						<?php endif; ?>
					</dl>
				</section>
			</div>

			<?php if ( $show_navigation ) : ?>
				<?php echo $this->render_story_reader_navigation( $story ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php echo $this->render_story_reader_journey_map( $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</article>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render story reader inside a modal for the journey flow.
	 *
	 * @param array<string,mixed> $args Render arguments.
	 * @return string
	 */
	private function render_story_reader_modal( $args = array() ) {
		$close_url = $this->get_story_reader_close_url();

		return sprintf(
			'<div class="michiryu-sekki-story-modal" data-mrs-story-modal data-open="true"><div class="michiryu-sekki-story-modal__backdrop"><a href="%1$s" aria-label="%2$s"></a></div><div class="michiryu-sekki-story-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="michiryu-story-reader-title">%3$s</div></div>',
			esc_url( $close_url ),
			esc_attr__( 'Close story', 'michiryu-sekki' ),
			$this->render_story_reader( $args )
		);
	}

	/**
	 * Resolve the story record for the reader.
	 *
	 * @param array<string,mixed> $args Render arguments.
	 * @return array<string,mixed>
	 */
	private function resolve_story_reader_story( $args ) {
		$query_story = isset( $_GET['mrs_story'] ) ? sanitize_title( wp_unslash( $_GET['mrs_story'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$story_id = $query_story ?: (string) ( $args['story'] ?? '' );

		if ( ! empty( $story_id ) ) {
			$story = $this->get_story_by_id( $story_id );
			if ( ! empty( $story ) ) {
				return $story;
			}
		}

		if ( ! empty( $args['ko'] ) ) {
			$story = $this->get_story_by_ko( (int) $args['ko'] );
			if ( ! empty( $story ) ) {
				return $story;
			}
		}

		if ( ! empty( $args['sekki'] ) ) {
			$season = MichiRyu_Sekki_Data::get_by_slug( (string) $args['sekki'] );
			if ( ! empty( $season ) ) {
				return $this->get_current_story_for_season( $season, $this->get_current_timestamp_utc(), $this->get_display_timezone() );
			}
		}

		$timestamp_utc = $this->get_current_timestamp_utc();
		$display_timezone = $this->get_display_timezone();

		return $this->get_current_story_for_season( MichiRyu_Sekki_Data::get_current( $timestamp_utc, $display_timezone ), $timestamp_utc, $display_timezone );
	}

	/**
	 * Return one story by ID.
	 *
	 * @param string $story_id Story ID.
	 * @return array<string,mixed>
	 */
	private function get_story_by_id( $story_id ) {
		$content = MichiRyu_Sekki_Content::get_content();
		$stories = is_array( $content['stories'] ?? null ) ? $content['stories'] : array();

		foreach ( $stories as $story ) {
			if ( (string) ( $story['id'] ?? '' ) === (string) $story_id ) {
				return $story;
			}
		}

		return array();
	}

	/**
	 * Return one story by ko number.
	 *
	 * @param int $ko_number Ko number.
	 * @return array<string,mixed>
	 */
	private function get_story_by_ko( $ko_number ) {
		$content = MichiRyu_Sekki_Content::get_content();
		$stories = is_array( $content['stories'] ?? null ) ? $content['stories'] : array();

		foreach ( $stories as $story ) {
			if ( (int) ( $story['ko_number'] ?? 0 ) === (int) $ko_number ) {
				return $story;
			}
		}

		return array();
	}

	/**
	 * Return all stories in journey order.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_all_stories() {
		$content = MichiRyu_Sekki_Content::get_content();
		return is_array( $content['stories'] ?? null ) ? array_values( $content['stories'] ) : array();
	}

	/**
	 * Render a hidden story index for browser-side journey continuation.
	 *
	 * @param array<int,array<string,mixed>> $stories Story records.
	 * @return string
	 */
	private function render_story_index( $stories ) {
		if ( empty( $stories ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="michiryu-sekki-story-index" data-mrs-story-index hidden>
			<?php foreach ( $stories as $story ) : ?>
				<a href="<?php echo esc_url( $this->get_story_reader_url( $story ) ); ?>" data-story="<?php echo esc_attr( $story['id'] ?? '' ); ?>"><?php echo esc_html( $story['title'] ?? '' ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a visible 72-step reading progress grid.
	 *
	 * @param array<int,array<string,mixed>> $stories Story records.
	 * @param string                         $current_story_id Current story ID.
	 * @return string
	 */
	private function render_story_progress_grid( $stories, $current_story_id = '' ) {
		if ( empty( $stories ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="michiryu-sekki-story-progress-grid" data-mrs-story-progress-grid>
			<?php foreach ( $stories as $story ) : ?>
				<?php
				$story_id = (string) ( $story['id'] ?? '' );
				$ko_number = absint( $story['ko_number'] ?? 0 );

				if ( empty( $story_id ) || empty( $ko_number ) ) {
					continue;
				}

				$classes = array( 'michiryu-sekki-story-progress-grid__item' );
				if ( $story_id === $current_story_id ) {
					$classes[] = 'is-current';
				}

				$label = sprintf(
					/* translators: 1: ko number, 2: story title. */
					__( 'Ko %1$d: %2$s', 'michiryu-sekki' ),
					$ko_number,
					esc_html( $story['title'] ?? '' )
				);
				?>
				<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( $this->get_story_reader_url( $story ) ); ?>" data-mrs-story-progress-item data-story="<?php echo esc_attr( $story_id ); ?>" data-ko="<?php echo esc_attr( $ko_number ); ?>" data-status-label="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" <?php echo $story_id === $current_story_id ? 'aria-current="step"' : ''; ?>>
					<span><?php echo esc_html( $ko_number ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the journey card seasonal image.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_journey_image( $season, $options ) {
		$url = $this->get_asset_url( 'sekki', $season['image_file'] ?? '', $options['custom_fallback_image_url'], $options );

		if ( empty( $url ) ) {
			return '';
		}

		ob_start();
		?>
		<figure class="michiryu-sekki-journey__image michiryu-sekki-image-wrap">
			<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $season['romaji'] . ' - ' . $season['english_name'] ); ?>" loading="lazy" draggable="false" />
			<?php
			echo $this->render_signature(
				array(
					'signature_position' => 'bottom-right',
					'signature_size'     => 'small',
					'signature_opacity'  => $options['signature_opacity'] ?? 1,
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</figure>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the season record for a story.
	 *
	 * @param array<string,mixed> $story Story record.
	 * @return array<string,mixed>
	 */
	private function get_story_season( $story ) {
		foreach ( MichiRyu_Sekki_Data::get_seasons() as $season ) {
			if ( (int) ( $season['sekki_number'] ?? 0 ) === (int) ( $story['sekki_number'] ?? 0 ) ) {
				return $season;
			}
		}

		return array();
	}

	/**
	 * Get the ko record for a story.
	 *
	 * @param array<string,mixed> $story Story record.
	 * @return array<string,mixed>
	 */
	private function get_story_ko( $story ) {
		foreach ( MichiRyu_Sekki_Data::get_ko() as $ko ) {
			if ( (int) ( $ko['ko_number'] ?? 0 ) === (int) ( $story['ko_number'] ?? 0 ) ) {
				return $ko;
			}
		}

		return array();
	}

	/**
	 * Render reader previous and next story navigation.
	 *
	 * @param array<string,mixed> $story Story record.
	 * @return string
	 */
	private function render_story_reader_navigation( $story ) {
		$previous = $this->get_story_by_id( (string) ( $story['previous_story_id'] ?? '' ) );
		$next     = $this->get_story_by_id( (string) ( $story['next_story_id'] ?? '' ) );
		$next_label = $this->get_story_continue_label( $story, $next );

		ob_start();
		?>
		<nav class="michiryu-sekki-story-reader__nav" aria-label="<?php esc_attr_e( 'Story navigation', 'michiryu-sekki' ); ?>">
			<?php if ( ! empty( $previous ) ) : ?>
				<a class="michiryu-sekki-story-reader__nav-link" href="<?php echo esc_url( $this->get_story_reader_url( $previous ) ); ?>">
					<span><?php esc_html_e( 'Previous Story', 'michiryu-sekki' ); ?></span>
					<strong><?php echo esc_html( $previous['title'] ?? '' ); ?></strong>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $next ) ) : ?>
				<a class="michiryu-sekki-story-reader__nav-link" href="<?php echo esc_url( $this->get_story_reader_url( $next ) ); ?>">
					<span><?php echo esc_html( $next_label ); ?></span>
					<strong><?php echo esc_html( $next['title'] ?? '' ); ?></strong>
				</a>
				<a class="michiryu-sekki-story-reader__continue" href="<?php echo esc_url( $this->get_story_reader_url( $next ) ); ?>">
					<span><?php esc_html_e( 'Continue Journey', 'michiryu-sekki' ); ?></span>
					<strong><?php echo esc_html( $next['title'] ?? '' ); ?></strong>
				</a>
			<?php else : ?>
				<div class="michiryu-sekki-story-reader__continue is-disabled" aria-disabled="true">
					<span><?php esc_html_e( 'Continue Journey', 'michiryu-sekki' ); ?></span>
					<strong><?php esc_html_e( 'More stories coming soon', 'michiryu-sekki' ); ?></strong>
				</div>
			<?php endif; ?>
		</nav>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return contextual continue label for the next story.
	 *
	 * @param array<string,mixed> $story Current story record.
	 * @param array<string,mixed> $next Next story record.
	 * @return string
	 */
	private function get_story_continue_label( $story, $next ) {
		if ( empty( $next ) ) {
			return __( 'Next Story', 'michiryu-sekki' );
		}

		if ( (int) ( $story['sekki_number'] ?? 0 ) !== (int) ( $next['sekki_number'] ?? 0 ) ) {
			$season = $this->get_story_season( $next );
			return sprintf(
				/* translators: %s: next season romanized name. */
				__( 'Next Season: %s', 'michiryu-sekki' ),
				esc_html( $season['romaji'] ?? __( 'Next', 'michiryu-sekki' ) )
			);
		}

		return sprintf(
			/* translators: %d: next ko number. */
			__( 'Next Ko: %d', 'michiryu-sekki' ),
			absint( $next['ko_number'] ?? 0 )
		);
	}

	/**
	 * Render a compact linear path indicator for the current story.
	 *
	 * @param array<string,mixed> $story Current story record.
	 * @return string
	 */
	private function render_story_reader_path( $story ) {
		$ko_number = absint( $story['ko_number'] ?? 0 );
		$percent = min( 100, max( 0, ( $ko_number / 72 ) * 100 ) );
		$stories = $this->get_all_stories();

		ob_start();
		?>
		<div class="michiryu-sekki-story-reader__path" aria-label="<?php esc_attr_e( 'Journey position', 'michiryu-sekki' ); ?>">
			<div class="michiryu-sekki-story-reader__path-row">
				<span><?php echo esc_html( sprintf( __( 'Ko %1$d of %2$d', 'michiryu-sekki' ), $ko_number, 72 ) ); ?></span>
				<span data-mrs-read-count><?php esc_html_e( 'Read progress will appear here', 'michiryu-sekki' ); ?></span>
			</div>
			<div class="michiryu-sekki-story-reader__path-bar" aria-hidden="true"><i style="width: <?php echo esc_attr( $percent ); ?>%;"></i></div>
			<?php echo $this->render_story_progress_grid( $stories, (string) ( $story['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button class="michiryu-sekki-story-reader__restart" type="button" data-mrs-restart-journey><?php esc_html_e( 'Restart Journey', 'michiryu-sekki' ); ?></button>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the map entry after the story flow.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_story_reader_journey_map( $options ) {
		$description = MichiRyu_Sekki_Content::is_local_provider()
			? __( 'See where this story sits in the seasonal cycle and explore nearby seasons.', 'michiryu-sekki' )
			: __( 'See where this story sits in Yuki no Sato and explore nearby seasons.', 'michiryu-sekki' );

		ob_start();
		?>
		<section class="michiryu-sekki-story-reader__map">
			<div>
				<h3><?php esc_html_e( 'Journey Map', 'michiryu-sekki' ); ?></h3>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<a class="michiryu-sekki-story-reader__map-button" href="#michiryu-sekki-map" role="button" data-mrs-map-open><?php esc_html_e( 'Explore Journey Map', 'michiryu-sekki' ); ?></a>
			<?php echo $this->render_map_modal( $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return a URL for loading a story into the current reader page.
	 *
	 * @param array<string,mixed> $story Story record.
	 * @return string
	 */
	private function get_story_reader_url( $story ) {
		return add_query_arg( 'mrs_story', (string) ( $story['id'] ?? '' ) ) . '#michiryu-story-reader';
	}

	/**
	 * Return a URL that closes the inline story reader.
	 *
	 * @return string
	 */
	private function get_story_reader_close_url() {
		return remove_query_arg( 'mrs_story' ) . '#michiryu-journey';
	}

	/**
	 * Get saved options merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function get_options() {
		$saved = get_option( self::OPTION_NAME, array() );
		$options = wp_parse_args( is_array( $saved ) ? $saved : array(), $this->get_default_options() );
		$options['default_style'] = $this->normalize_style_value( $options['default_style'] ?? '', 'standard_vertical' );

		return $options;
	}

	/**
	 * Return the current UTC timestamp.
	 *
	 * @return int
	 */
	private function get_current_timestamp_utc() {
		return time();
	}

	/**
	 * Resolve the display timezone from the browser cookie or WordPress settings.
	 *
	 * @return string
	 */
	private function get_display_timezone() {
		$browser_timezone = isset( $_COOKIE['michiryu_sekki_timezone'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['michiryu_sekki_timezone'] ) ) : ''; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

		if ( $this->is_valid_timezone( $browser_timezone ) ) {
			return $browser_timezone;
		}

		$site_timezone = wp_timezone_string();

		return $this->is_valid_timezone( $site_timezone ) ? $site_timezone : wp_timezone()->getName();
	}

	/**
	 * Check whether a timezone string is a valid IANA timezone.
	 *
	 * @param string $timezone Timezone string.
	 * @return bool
	 */
	private function is_valid_timezone( $timezone ) {
		if ( ! is_string( $timezone ) || '' === $timezone ) {
			return false;
		}

		try {
			new DateTimeZone( $timezone );
			return true;
		} catch ( Exception $exception ) {
			return false;
		}
	}

	/**
	 * Format a UTC timestamp for display.
	 *
	 * @param int    $timestamp UTC timestamp.
	 * @param string $timezone Display timezone.
	 * @param string $format Date format.
	 * @return string
	 */
	private function format_timestamp_for_timezone( $timestamp, $timezone, $format ) {
		$timezone_object = $this->is_valid_timezone( $timezone ) ? new DateTimeZone( $timezone ) : wp_timezone();

		return ( new DateTimeImmutable( '@' . absint( $timestamp ) ) )
			->setTimezone( $timezone_object )
			->format( $format );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_default_options() {
		return array(
			'default_style'           => 'standard_vertical',
			'default_plan'            => 'standard',
			'show_kanji'              => true,
			'show_romanized'          => true,
			'show_english'            => true,
			'show_date_range'         => true,
			'show_description'        => true,
			'show_sekki_image'        => true,
			'show_ko_icon'            => true,
			'show_ikebana_materials'  => true,
			'show_story_teaser'       => true,
			'use_bundled_images'      => true,
			'show_date_stamp'         => true,
			'show_creator_link'       => false,
			'signature_position'      => 'bottom-right',
			'signature_size'          => 'medium',
			'signature_opacity'       => 1,
			'custom_fallback_image_url' => '',
			'image_style'             => 'banner',
			'icon_style'              => 'outline',
			'enable_map_link'         => false,
			'map_open_behavior'       => 'modal',
			'reader_open_behavior'    => 'modal',
			'show_map_in_widget'      => false,
			'show_current_map_highlight' => true,
			'map_progression_style'   => 'timeline',
			'read_more_link_behavior' => 'none',
			'external_season_base_url' => '',
			'map_page_url'            => '',
			'custom_css'              => '',
			'content_import_ack_copyright' => false,
			'content_import_accept_license' => false,
			'content_import_ack_privacy' => false,
			'content_library_url'     => '',
			'content_access_token'    => '',
			'premium_license_token'   => '',
			'content_update_mode'      => 'manual',
		);
	}

	/**
	 * Sanitize options for saving.
	 *
	 * @param array<string,mixed> $input Raw settings.
	 * @return array<string,mixed>
	 */
	public function sanitize_options( $input ) {
		$defaults = $this->get_default_options();
		$saved    = get_option( self::OPTION_NAME, array() );
		$saved    = is_array( $saved ) ? wp_parse_args( $saved, $defaults ) : $defaults;
		$input    = is_array( $input ) ? $input : array();
		$output   = array();

		$output['default_style'] = $this->normalize_style_value( $input['default_style'] ?? '', $defaults['default_style'] );
		$output['default_plan']  = in_array( $input['default_plan'] ?? '', $this->plans, true ) ? $input['default_plan'] : $saved['default_plan'];

		foreach ( array( 'show_kanji', 'show_romanized', 'show_english', 'show_sekki_image', 'show_ko_icon', 'show_ikebana_materials', 'show_story_teaser', 'show_date_stamp', 'show_creator_link', 'content_import_ack_copyright', 'content_import_accept_license', 'content_import_ack_privacy' ) as $key ) {
			$output[ $key ] = ! empty( $input[ $key ] );
		}

		foreach ( array( 'show_date_range', 'show_description', 'use_bundled_images', 'enable_map_link', 'show_map_in_widget', 'show_current_map_highlight' ) as $key ) {
			$output[ $key ] = array_key_exists( $key, $input ) ? ! empty( $input[ $key ] ) : ! empty( $saved[ $key ] );
		}

		$image_styles = array( 'square', 'banner', 'circle', 'none' );
		$icon_styles  = array( 'outline', 'circle', 'none' );
		$map_open_behaviors = array( 'modal', 'page', 'new_tab' );
		$reader_open_behaviors = array( 'modal', 'inline' );
		$map_progression_styles = array( 'wheel', 'timeline', 'none' );
		$read_more_link_behaviors = array( 'none', 'internal', 'external' );
		$content_update_modes = array( 'manual', 'monthly', 'sekki' );
		$signature_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
		$signature_sizes     = array( 'small', 'medium', 'large' );
		$signature_opacity   = isset( $input['signature_opacity'] ) ? (float) $input['signature_opacity'] : (float) $saved['signature_opacity'];

		$output['custom_fallback_image_url'] = array_key_exists( 'custom_fallback_image_url', $input ) ? esc_url_raw( $input['custom_fallback_image_url'] ) : $saved['custom_fallback_image_url'];
		$output['image_style']               = in_array( $input['image_style'] ?? '', $image_styles, true ) ? $input['image_style'] : $saved['image_style'];
		$output['icon_style']                = in_array( $input['icon_style'] ?? '', $icon_styles, true ) ? $input['icon_style'] : $saved['icon_style'];
		$output['map_open_behavior']         = in_array( $input['map_open_behavior'] ?? '', $map_open_behaviors, true ) ? $input['map_open_behavior'] : $saved['map_open_behavior'];
		$output['reader_open_behavior']      = in_array( $input['reader_open_behavior'] ?? '', $reader_open_behaviors, true ) ? $input['reader_open_behavior'] : $saved['reader_open_behavior'];
		$output['map_progression_style']     = in_array( $input['map_progression_style'] ?? '', $map_progression_styles, true ) ? $input['map_progression_style'] : $saved['map_progression_style'];
		$output['read_more_link_behavior']   = in_array( $input['read_more_link_behavior'] ?? '', $read_more_link_behaviors, true ) ? $input['read_more_link_behavior'] : $saved['read_more_link_behavior'];
		$output['content_update_mode']       = in_array( $input['content_update_mode'] ?? '', $content_update_modes, true ) ? $input['content_update_mode'] : $saved['content_update_mode'];
		$output['content_library_url']       = array_key_exists( 'content_library_url', $input ) ? esc_url_raw( $input['content_library_url'] ) : $saved['content_library_url'];
		if ( ! empty( $input['content_access_token_clear'] ) ) {
			$output['content_access_token'] = '';
		} elseif ( ! empty( $input['content_access_token'] ) ) {
			$output['content_access_token'] = MichiRyu_Sekki_Content_Library_Access::sanitize_access_token( $input['content_access_token'] );
		} else {
			$output['content_access_token'] = $saved['content_access_token'];
		}
		if ( ! empty( $input['premium_license_token_clear'] ) ) {
			$output['premium_license_token'] = '';
		} elseif ( ! empty( $input['premium_license_token'] ) ) {
			$output['premium_license_token'] = MichiRyu_Sekki_Content_Library_Access::sanitize_access_token( $input['premium_license_token'] );
		} else {
			$output['premium_license_token'] = $saved['premium_license_token'];
		}
		$output['external_season_base_url']  = array_key_exists( 'external_season_base_url', $input ) ? esc_url_raw( $input['external_season_base_url'] ) : $saved['external_season_base_url'];
		$output['map_page_url']              = esc_url_raw( $input['map_page_url'] ?? '' );
		$output['signature_position']        = in_array( $input['signature_position'] ?? '', $signature_positions, true ) ? $input['signature_position'] : $saved['signature_position'];
		$output['signature_size']            = in_array( $input['signature_size'] ?? '', $signature_sizes, true ) ? $input['signature_size'] : $saved['signature_size'];
		$output['signature_opacity']         = max( 0.5, min( 1.0, $signature_opacity ) );
		$output['custom_css']                = wp_kses( $input['custom_css'] ?? '', array() );

		return $output;
	}

	/**
	 * Normalize saved and legacy style values.
	 *
	 * @param string $style Raw style value.
	 * @param string $fallback Fallback style value.
	 * @return string
	 */
	private function normalize_style_value( $style, $fallback = 'standard_vertical' ) {
		$style = sanitize_key( $style );

		$legacy_styles = array(
			'compact'     => 'standard_vertical',
			'image_card'  => 'standard_vertical',
			'ikebana'     => 'standard_vertical',
			'explore_map' => 'standard_vertical',
			'banner'      => 'banner_tall',
		);

		if ( isset( $legacy_styles[ $style ] ) ) {
			$style = $legacy_styles[ $style ];
		}

		if ( in_array( $style, $this->styles, true ) ) {
			return $style;
		}

		$fallback = sanitize_key( $fallback );
		return in_array( $fallback, $this->styles, true ) ? $fallback : 'standard_vertical';
	}

	/**
	 * Normalize render arguments.
	 *
	 * @param array<string,mixed> $args Raw args.
	 * @param array<string,mixed> $options Saved options.
	 * @return array<string,mixed>
	 */
	private function normalize_args( $args, $options ) {
		$style = sanitize_key( $args['style'] ?? '' );
		$plan  = sanitize_key( $args['plan'] ?? '' );
		$signature_position = sanitize_key( $args['signature_position'] ?? '' );
		$signature_size     = sanitize_key( $args['signature_size'] ?? '' );
		$signature_opacity  = '' !== ( $args['signature_opacity'] ?? '' ) ? (float) $args['signature_opacity'] : (float) $options['signature_opacity'];
		$signature_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
		$signature_sizes     = array( 'small', 'medium', 'large' );

		$normalized = array(
			'style'                  => $this->normalize_style_value( $style, $this->normalize_style_value( $options['default_style'] ?? '', 'standard_vertical' ) ),
			'plan'                   => in_array( $plan, $this->plans, true ) ? $plan : $options['default_plan'],
			'show_kanji'             => $this->resolve_bool_arg( $args['show_kanji'] ?? '', $options['show_kanji'] ),
			'show_romanized'         => $this->resolve_bool_arg( $args['show_romanized'] ?? '', $options['show_romanized'] ),
			'show_english'           => $this->resolve_bool_arg( $args['show_english'] ?? '', $options['show_english'] ),
			'show_date_range'        => $this->resolve_bool_arg( $args['show_date_range'] ?? '', $options['show_date_range'] ),
			'show_description'       => $this->resolve_bool_arg( $args['show_description'] ?? '', $options['show_description'] ),
			'show_sekki_image'       => $this->resolve_bool_arg( $args['show_sekki_image'] ?? ( $args['show_image'] ?? '' ), $options['show_sekki_image'] ),
			'show_ko'                => $this->resolve_bool_arg( $args['show_ko'] ?? '', $options['show_ko_icon'] ),
			'show_ikebana_materials' => $this->resolve_bool_arg( $args['show_ikebana_materials'] ?? '', $options['show_ikebana_materials'] ),
			'carousel'               => $this->resolve_bool_arg( $args['carousel'] ?? '', false ),
			'show_date_stamp'        => $this->resolve_bool_arg( $args['show_date_stamp'] ?? '', $options['show_date_stamp'] ),
			'show_story'             => $this->resolve_bool_arg( $args['show_story'] ?? '', $options['show_story_teaser'] ),
			'show_map_link'          => $this->resolve_bool_arg( $args['show_map_link'] ?? '', $options['enable_map_link'] ),
			'signature_position'     => in_array( $signature_position, $signature_positions, true ) ? $signature_position : $options['signature_position'],
			'signature_size'         => in_array( $signature_size, $signature_sizes, true ) ? $signature_size : $options['signature_size'],
			'signature_opacity'      => max( 0.5, min( 1.0, $signature_opacity ) ),
		);

		if ( 'minimal' === $normalized['plan'] ) {
			$normalized['show_date_range']        = false;
			$normalized['show_description']       = false;
			$normalized['show_sekki_image']       = false;
			$normalized['show_ikebana_materials'] = false;
		}

		return $normalized;
	}

	/**
	 * Render all Sekki and ko records as navigable carousels.
	 *
	 * @param array<string,mixed> $args Render args.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_carousel( $args, $options ) {
		$seasons        = MichiRyu_Sekki_Data::get_seasons();
		$timestamp_utc  = $this->get_current_timestamp_utc();
		$display_timezone = $this->get_display_timezone();
		$current        = MichiRyu_Sekki_Data::get_current( $timestamp_utc, $display_timezone );
		$current_ko     = MichiRyu_Sekki_Data::get_current_ko( $timestamp_utc, $display_timezone );
		$show_ko_track  = $args['show_ko'];
		$carousel_id    = wp_unique_id( 'michiryu-sekki-carousel-' );
		$classes        = array(
			'michiryu-sekki-carousel',
			'michiryu-sekki-carousel--' . $args['style'],
			'michiryu-sekki--plan-' . $args['plan'],
			'michiryu-sekki--image-' . $options['image_style'],
			'michiryu-sekki--icon-' . $options['icon_style'],
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="<?php echo esc_attr( $carousel_id ); ?>">
			<?php echo $this->render_carousel_section( $carousel_id . '-seasons', __( 'Sekki seasons', 'michiryu-sekki' ), $this->render_season_slides( $seasons, $current, $args, $options ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( $show_ko_track ) : ?>
				<?php echo $this->render_carousel_section( $carousel_id . '-ko', __( 'Ko microseasons', 'michiryu-sekki' ), $this->render_ko_slides( MichiRyu_Sekki_Data::get_ko(), $current_ko, $options, $args ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $options['custom_css'] ) ) : ?>
			<style id="michiryu-sekki-custom-css">
				<?php echo wp_strip_all_tags( $options['custom_css'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</style>
		<?php endif; ?>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render one carousel section.
	 *
	 * @param string $track_id Track ID.
	 * @param string $label Section label.
	 * @param string $slides Slide markup.
	 * @return string
	 */
	private function render_carousel_section( $track_id, $label, $slides ) {
		ob_start();
		?>
		<section class="michiryu-sekki-carousel__section" aria-label="<?php echo esc_attr( $label ); ?>">
			<div class="michiryu-sekki-carousel__header">
				<h3 class="michiryu-sekki-carousel__title"><?php echo esc_html( $label ); ?></h3>
				<div class="michiryu-sekki-carousel__controls">
					<button class="michiryu-sekki-carousel__button" type="button" data-mrs-carousel-prev aria-controls="<?php echo esc_attr( $track_id ); ?>">
						<span aria-hidden="true">&lsaquo;</span>
						<span class="screen-reader-text"><?php esc_html_e( 'Previous', 'michiryu-sekki' ); ?></span>
					</button>
					<button class="michiryu-sekki-carousel__button" type="button" data-mrs-carousel-next aria-controls="<?php echo esc_attr( $track_id ); ?>">
						<span aria-hidden="true">&rsaquo;</span>
						<span class="screen-reader-text"><?php esc_html_e( 'Next', 'michiryu-sekki' ); ?></span>
					</button>
				</div>
			</div>
			<div class="michiryu-sekki-carousel__track" id="<?php echo esc_attr( $track_id ); ?>" tabindex="0">
				<?php echo $slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render season slides.
	 *
	 * @param array<int,array<string,mixed>> $seasons Season records.
	 * @param array<string,mixed>            $current Current season.
	 * @param array<string,mixed>            $args Render args.
	 * @param array<string,mixed>            $options Saved options.
	 * @return string
	 */
	private function render_season_slides( $seasons, $current, $args, $options ) {
		$slides = array();

		foreach ( $seasons as $season ) {
			$slides[] = $this->render_season_slide( $season, $current, $args, $options );
		}

		return implode( '', $slides );
	}

	/**
	 * Render one season slide.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current Current season.
	 * @param array<string,mixed> $args Render args.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_season_slide( $season, $current, $args, $options ) {
		$image     = $args['show_sekki_image'] && $this->should_show_image( $args['style'], $args['plan'] ) ? $this->render_sekki_image( $season, $options, $args ) : '';
		$is_current = $season['slug'] === $current['slug'];
		$classes   = array(
			'michiryu-sekki-carousel__slide',
			'michiryu-sekki',
			'michiryu-sekki--' . $args['style'],
			empty( $image ) ? 'michiryu-sekki--no-image' : 'michiryu-sekki--has-image',
		);

		if ( $is_current ) {
			$classes[] = 'is-current';
		}

		ob_start();
		?>
		<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php echo $is_current ? 'aria-current="date"' : ''; ?>>
			<?php if ( ! empty( $image ) ) : ?>
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<div class="michiryu-sekki__body">
				<?php if ( $is_current ) : ?>
					<p class="michiryu-sekki-carousel__eyebrow"><?php esc_html_e( 'Current season', 'michiryu-sekki' ); ?></p>
				<?php endif; ?>
				<?php echo $this->render_heading( $season, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $args['show_date_range'] ) : ?>
					<p class="michiryu-sekki__date"><span><?php esc_html_e( 'Sekki', 'michiryu-sekki' ); ?></span> <?php echo esc_html( $season['date_range'] ); ?></p>
				<?php endif; ?>
				<?php if ( $args['show_description'] ) : ?>
					<p class="michiryu-sekki__description"><?php echo esc_html( $season['description'] ); ?></p>
				<?php endif; ?>
				<?php echo $this->render_season_materials_list( $season, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</article>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render ko slides.
	 *
	 * @param array<int,array<string,mixed>> $ko_records Ko records.
	 * @param array<string,mixed>            $current_ko Current ko.
	 * @param array<string,mixed>            $options Saved options.
	 * @param array<string,mixed>            $args Render args.
	 * @return string
	 */
	private function render_ko_slides( $ko_records, $current_ko, $options, $args ) {
		$slides = array();

		foreach ( $ko_records as $ko ) {
			$is_current = $ko['ko_number'] === $current_ko['ko_number'];
			$classes    = array( 'michiryu-sekki-carousel__slide', 'michiryu-sekki-carousel__ko-slide' );

			if ( $is_current ) {
				$classes[] = 'is-current';
			}

			$slides[] = sprintf(
				'<article class="%1$s" %2$s>%3$s</article>',
				esc_attr( implode( ' ', $classes ) ),
				$is_current ? 'aria-current="date"' : '',
				$this->render_ko_card( $ko, $options, $is_current, $args )
			);
		}

		return implode( '', $slides );
	}

	/**
	 * Render a ko card for carousel mode.
	 *
	 * @param array<string,mixed> $ko Ko record.
	 * @param array<string,mixed> $options Saved options.
	 * @param bool                $is_current Whether this ko is current.
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_ko_card( $ko, $options, $is_current, $args ) {
		$icon  = '';
		$title = $this->render_ko_name_title( $ko, $args, 'michiryu-sekki__ko-title' );

		if ( 'none' !== $options['icon_style'] ) {
			$icon_url = $this->get_asset_url( 'ko', $ko['icon_file'], '', $options );
			if ( ! empty( $icon_url ) ) {
				$icon = sprintf(
					'<div class="michiryu-sekki__ko-icon"><img src="%1$s" alt="%2$s" loading="lazy" /></div>',
					esc_url( $icon_url ),
					esc_attr( $ko['romaji'] . ' - ' . $ko['english_name'] )
				);
			}
		}

		return sprintf(
			'<div class="michiryu-sekki__ko michiryu-sekki-carousel__ko">%1$s<div class="michiryu-sekki__ko-body">%2$s<p class="michiryu-sekki__ko-label">%3$s</p>%4$s<p class="michiryu-sekki__ko-date">%5$s</p><p class="michiryu-sekki__ko-description">%6$s</p></div></div>',
			$icon,
			$is_current ? '<p class="michiryu-sekki-carousel__eyebrow">' . esc_html__( 'Current microseason', 'michiryu-sekki' ) . '</p>' : '',
			esc_html__( 'Ko microseason', 'michiryu-sekki' ),
			$title,
			esc_html( $ko['date_range'] ),
			esc_html( $ko['short_description'] )
		);
	}

	/**
	 * Return the current ko story for a season, falling back to the first story.
	 *
	 * @param array<string,mixed> $season Season data.
	 * @return array<string,mixed>
	 */
	private function get_current_story_for_season( $season, $timestamp_utc = null, $display_timezone = null ) {
		$stories = MichiRyu_Sekki_Content::get_stories_for_sekki( (int) ( $season['sekki_number'] ?? 0 ) );

		if ( empty( $stories ) ) {
			return array();
		}

		$current_ko = MichiRyu_Sekki_Data::get_current_ko( $timestamp_utc ?? $this->get_current_timestamp_utc(), $display_timezone ?? $this->get_display_timezone() );
		foreach ( $stories as $story ) {
			if ( (int) ( $story['ko_number'] ?? 0 ) === (int) ( $current_ko['ko_number'] ?? 0 ) ) {
				return $story;
			}
		}

		return $stories[0];
	}

	/**
	 * Render a compact current story preview on the landing widget.
	 *
	 * @param array<string,mixed> $season Season data.
	 * @param array<string,mixed> $story Story data.
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_story_teaser( $season, $story, $args = array() ) {
		$character_names = $this->get_story_character_names( $story );
		$is_narrow_banner = 'banner_narrow' === ( $args['style'] ?? '' );
		$excerpt_words = $is_narrow_banner ? 80 : 24;
		$excerpt = wp_trim_words( (string) ( $story['body_text'] ?? '' ), $excerpt_words, '...' );
		$sentence_excerpt = $is_narrow_banner ? $this->get_story_teaser_sentences( (string) ( $story['body_text'] ?? '' ), 5 ) : array();

		ob_start();
		?>
		<aside class="michiryu-sekki__story-teaser" aria-label="<?php esc_attr_e( 'Current story preview', 'michiryu-sekki' ); ?>">
			<p class="michiryu-sekki__story-kicker"><?php esc_html_e( 'Ko story', 'michiryu-sekki' ); ?></p>
			<h4 class="michiryu-sekki__story-title"><?php echo esc_html( $story['title'] ?? '' ); ?></h4>
			<?php if ( ! empty( $sentence_excerpt ) ) : ?>
				<div class="michiryu-sekki__story-excerpt michiryu-sekki__story-excerpt--sentences">
					<?php foreach ( $sentence_excerpt as $sentence ) : ?>
						<span><?php echo esc_html( $sentence ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php elseif ( ! empty( $excerpt ) ) : ?>
				<p class="michiryu-sekki__story-excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $character_names ) ) : ?>
				<p class="michiryu-sekki__story-characters">
					<span><?php esc_html_e( 'Characters', 'michiryu-sekki' ); ?></span>
					<?php echo esc_html( implode( ', ', $character_names ) ); ?>
				</p>
			<?php endif; ?>
		</aside>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return a sentence-by-sentence story teaser.
	 *
	 * @param string $body_text Story body text.
	 * @param int    $limit Maximum sentences to show.
	 * @return array<int,string>
	 */
	private function get_story_teaser_sentences( $body_text, $limit = 5 ) {
		$body_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $body_text ) ) );

		if ( '' === $body_text ) {
			return array();
		}

		$sentences = preg_split( '/(?<=[.!?])\s+/', $body_text, -1, PREG_SPLIT_NO_EMPTY );
		$sentences = array_values( array_filter( array_map( 'trim', (array) $sentences ) ) );

		if ( empty( $sentences ) ) {
			return array();
		}

		$limited = array_slice( $sentences, 0, max( 1, absint( $limit ) ) );

		if ( count( $sentences ) > count( $limited ) ) {
			$last_index = count( $limited ) - 1;
			$limited[ $last_index ] = rtrim( $limited[ $last_index ], ".!?\t\n\r\0\x0B " ) . '...';
		}

		return $limited;
	}

	/**
	 * Return display names for the story's characters.
	 *
	 * @param array<string,mixed> $story Story data.
	 * @return array<int,string>
	 */
	private function get_story_character_names( $story ) {
		$characters = MichiRyu_Sekki_Content::get_characters();
		$ids = array_values( array_unique( array_merge( array( 'masaru' ), is_array( $story['characters'] ?? null ) ? $story['characters'] : array() ) ) );
		$names = array();

		foreach ( $ids as $id ) {
			if ( ! empty( $characters[ $id ]['name'] ) ) {
				$names[] = $characters[ $id ]['name'];
			}
		}

		return $names;
	}

	/**
	 * Render the Explore and Learn links with optional modal payloads.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @param array<string,mixed> $season Current season data.
	 * @param array<string,mixed> $story Current story data.
	 * @param bool                $show_explore Whether to show the Explore action.
	 * @return string
	 */
	private function render_map_link( $options, $season = array(), $story = array(), $show_explore = true ) {
		$behavior = $options['map_open_behavior'];
		$url      = $this->get_map_page_url( $options );
		$attrs    = '';
		$learn_id = wp_unique_id( 'michiryu-sekki-learn-' );
		$actions  = array();

		if ( 'modal' === $behavior ) {
			$attrs = ' href="#michiryu-sekki-map" role="button" data-mrs-map-open';
		} elseif ( 'new_tab' === $behavior ) {
			$attrs = ' href="' . esc_url( $url ) . '" target="_blank" rel="noopener"';
		} else {
			$attrs = ' href="' . esc_url( $url ) . '"';
		}

		if ( 'modal' === $behavior && ! empty( $story ) && ! empty( $season ) ) {
			$attrs .= sprintf(
				' data-mrs-read-story-open data-season="%1$s" data-story="%2$s"',
				esc_attr( $season['slug'] ?? '' ),
				esc_attr( $story['id'] ?? '' )
			);
		}

		if ( ! empty( $story ) && ! empty( $season ) ) {
			$story_attrs = $attrs;
			$actions[] = sprintf(
				'<a class="michiryu-sekki__action-link michiryu-sekki__action-link--primary"%1$s>%2$s</a>',
				$story_attrs,
				esc_html__( 'Read & Explore', 'michiryu-sekki' )
			);
		}

		if ( $show_explore ) {
			$actions[] = sprintf(
				'<a class="michiryu-sekki__action-link"%1$s>%2$s</a>',
				$attrs,
				esc_html__( 'Explore', 'michiryu-sekki' )
			);
		}

		$actions[] = sprintf(
			'<button class="michiryu-sekki__action-link" type="button" data-mrs-learn-open aria-controls="%1$s">%2$s</button>',
			esc_attr( $learn_id ),
			esc_html__( 'About Sekki & Ko', 'michiryu-sekki' )
		);

		$output = '<div class="michiryu-sekki__action-wrap">' . implode( '', $actions ) . '</div>';

		if ( 'modal' === $behavior ) {
			$output .= $this->render_map_modal( $options );
		}

		$output .= $this->render_learn_modal( $learn_id, $options );

		return $output;
	}

	/**
	 * Render the educational Sekki and ko explanation modal.
	 *
	 * @param string              $learn_id Modal ID.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_learn_modal( $learn_id, $options = array() ) {
		$title_id = $learn_id . '-title';

		ob_start();
		?>
		<div class="michiryu-sekki-learn-modal" id="<?php echo esc_attr( $learn_id ); ?>" data-mrs-learn-modal hidden>
			<div class="michiryu-sekki-learn-modal__backdrop" data-mrs-learn-close></div>
			<div class="michiryu-sekki-learn-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
				<button class="michiryu-sekki-learn-modal__close" type="button" data-mrs-learn-close aria-label="<?php esc_attr_e( 'Close learning panel', 'michiryu-sekki' ); ?>">×</button>
				<section class="michiryu-sekki-learn">
					<p class="michiryu-sekki-learn__eyebrow"><?php esc_html_e( 'Japanese Seasonal Time', 'michiryu-sekki' ); ?></p>
					<h2 class="michiryu-sekki-learn__title" id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Sekki and Ko Calendars', 'michiryu-sekki' ); ?></h2>
					<p><?php esc_html_e( 'The 24 Sekki divide the solar year into seasonal terms of about fifteen days. They came to Japan through the older East Asian lunisolar calendar tradition and helped people read the year by light, temperature, rainfall, wind, and plant growth.', 'michiryu-sekki' ); ?></p>
					<p><?php esc_html_e( 'Each Sekki is further divided into three ko, creating 72 small seasons. These microseasons name delicate changes in nature: insects stirring, blossoms opening, grain ripening, mist rising, or frost beginning. The names turn observation into a calendar.', 'michiryu-sekki' ); ?></p>
					<div class="michiryu-sekki-learn__grid">
						<div>
							<h3><?php esc_html_e( 'Why Sekki Matters', 'michiryu-sekki' ); ?></h3>
							<p><?php esc_html_e( 'Sekki offered farmers, makers, cooks, poets, and households a practical rhythm for planting, harvest, festivals, preservation, and ceremony. It linked daily work to the movement of the sun.', 'michiryu-sekki' ); ?></p>
						</div>
						<div>
							<h3><?php esc_html_e( 'Why Ko Matters', 'michiryu-sekki' ); ?></h3>
							<p><?php esc_html_e( 'Ko asks for close attention. Instead of treating spring or summer as a single mood, it honors brief thresholds: the first song, the first flower, the first sign that a season is turning.', 'michiryu-sekki' ); ?></p>
						</div>
					</div>
					<p><?php esc_html_e( 'MichiRyu uses these calendars as a way to notice nature, arrange seasonal materials, and tell stories about how small changes shape a whole year.', 'michiryu-sekki' ); ?></p>
					<?php if ( ! empty( $options['show_creator_link'] ) ) : ?>
						<p>
							<?php
							printf(
								wp_kses(
									/* translators: %s: MichiRyu creator website link. */
									__( 'This experience was created by %s.', 'michiryu-sekki' ),
									array(
										'a' => array(
											'href'   => array(),
											'target' => array(),
											'rel'    => array(),
										),
									)
								),
								'<a href="' . esc_url( 'https://michiryu.com' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'MichiRyu.com', 'michiryu-sekki' ) . '</a>'
							);
							?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'This experience was created by MichiRyu.com.', 'michiryu-sekki' ); ?></p>
					<?php endif; ?>
				</section>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render map modal.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map_modal( $options ) {
		return sprintf(
			'<div class="michiryu-sekki-map-modal" data-mrs-map-modal hidden><div class="michiryu-sekki-map-modal__backdrop" data-mrs-map-close></div><div class="michiryu-sekki-map-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="michiryu-sekki-map-modal-title"><button class="michiryu-sekki-map-modal__close" type="button" data-mrs-map-close aria-label="%1$s">×</button>%2$s</div></div>',
			esc_attr__( 'Close map', 'michiryu-sekki' ),
			$this->render_map(
				array(
					'is_modal' => true,
					'title_id' => 'michiryu-sekki-map-modal-title',
				),
				$options
			)
		);
	}

	/**
	 * Render the interactive Sekki map.
	 *
	 * @param array<string,mixed> $args Render arguments.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map( $args, $options ) {
		$seasons      = MichiRyu_Sekki_Data::get_seasons();
		$timestamp_utc = $this->get_current_timestamp_utc();
		$display_timezone = $this->get_display_timezone();
		$current      = MichiRyu_Sekki_Data::get_current( $timestamp_utc, $display_timezone );
		$current_ko   = MichiRyu_Sekki_Data::get_current_ko( $timestamp_utc, $display_timezone );
		$current_only = ! empty( $args['current_only'] );
		$title_id     = $args['title_id'] ?? wp_unique_id( 'michiryu-sekki-map-title-' );
		$map_url      = $this->get_map_image_url();
		$is_modal     = ! empty( $args['is_modal'] );
		$layout_arg   = sanitize_key( $args['layout'] ?? '' );
		$layout       = 'default' === $layout_arg ? 'default' : ( $is_modal ? 'default' : 'page' );
		$classes      = array( 'michiryu-sekki-map' );
		$is_local_provider = MichiRyu_Sekki_Content::is_local_provider();
		$map_subtitle = $is_local_provider ? __( 'Sekki Seasonal Map', 'michiryu-sekki' ) : __( 'Yuki no Sato Sekki Village Map', 'michiryu-sekki' );

		if ( $is_modal ) {
			$classes[] = 'michiryu-sekki-map--modal';
		}

		if ( 'page' === $layout ) {
			$classes[] = 'michiryu-sekki-map--page';
		}

		if ( $current_only ) {
			$seasons = array_values(
				array_filter(
					$seasons,
					function ( $season ) use ( $current ) {
						return $season['slug'] === $current['slug'];
					}
				)
			);
		}

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-mrs-map data-current-season="<?php echo esc_attr( $current['slug'] ); ?>" data-current-ko="<?php echo esc_attr( $current_ko['ko_number'] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="michiryu-sekki-map__header">
				<div>
					<h2 class="michiryu-sekki-map__title" id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Explore Map', 'michiryu-sekki' ); ?></h2>
					<p class="michiryu-sekki-map__subtitle"><?php echo esc_html( $map_subtitle ); ?></p>
				</div>
				<div class="michiryu-sekki-map__controls" aria-label="<?php esc_attr_e( 'Map controls', 'michiryu-sekki' ); ?>">
					<button type="button" data-mrs-map-zoom="in" aria-label="<?php esc_attr_e( 'Zoom in', 'michiryu-sekki' ); ?>">+</button>
					<button type="button" data-mrs-map-zoom="out" aria-label="<?php esc_attr_e( 'Zoom out', 'michiryu-sekki' ); ?>">−</button>
					<button type="button" data-mrs-map-reset><?php esc_html_e( 'Reset', 'michiryu-sekki' ); ?></button>
					<button type="button" data-mrs-map-current><?php esc_html_e( 'Jump to Current Season', 'michiryu-sekki' ); ?></button>
				</div>
			</div>
			<?php echo $this->render_map_progression( $seasons, $current, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( 'page' === $layout ) : ?>
				<div class="michiryu-sekki-map__page-layout">
					<div class="michiryu-sekki-map__page-primary">
						<div class="michiryu-sekki-map__page-map-stack">
							<div class="michiryu-sekki-map__page-map">
								<?php echo $this->render_map_viewport( $seasons, $current, $options, $map_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="michiryu-sekki-map__page-under-map">
								<?php echo $this->render_map_characters( $seasons, $current, $timestamp_utc, $display_timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<div class="michiryu-sekki-map__story-region" data-mrs-map-stories>
									<?php foreach ( $seasons as $season ) : ?>
										<?php echo $this->render_map_stories( $season, $current, $timestamp_utc, $display_timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
						<div class="michiryu-sekki-map__details" data-mrs-map-details aria-live="polite">
							<?php foreach ( $seasons as $season ) : ?>
								<?php echo $this->render_map_detail( $season, $current, $current_ko, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div class="michiryu-sekki-map__layout">
					<div class="michiryu-sekki-map__main">
						<div class="michiryu-sekki-map__main-top">
							<?php echo $this->render_map_characters( $seasons, $current, $timestamp_utc, $display_timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo $this->render_map_viewport( $seasons, $current, $options, $map_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<div class="michiryu-sekki-map__story-region" data-mrs-map-stories>
							<?php foreach ( $seasons as $season ) : ?>
								<?php echo $this->render_map_stories( $season, $current, $timestamp_utc, $display_timezone ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="michiryu-sekki-map__details" data-mrs-map-details aria-live="polite">
						<?php foreach ( $seasons as $season ) : ?>
							<?php echo $this->render_map_detail( $season, $current, $current_ko, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the interactive map viewport.
	 *
	 * @param array<int,array<string,mixed>> $seasons Visible season records.
	 * @param array<string,mixed>            $current Current season.
	 * @param array<string,mixed>            $options Saved options.
	 * @param string                         $map_url Map image URL.
	 * @return string
	 */
	private function render_map_viewport( $seasons, $current, $options, $map_url ) {
		$map_alt = MichiRyu_Sekki_Content::is_local_provider()
			? __( 'Seasonal map with the 24 Sekki locations.', 'michiryu-sekki' )
			: __( 'Illustrated location map of Yuki no Sato with the 24 Sekki locations.', 'michiryu-sekki' );

		ob_start();
		?>
		<div class="michiryu-sekki-map__viewport" data-mrs-map-viewport>
			<div class="michiryu-sekki-map__canvas" data-mrs-map-canvas>
				<?php if ( ! empty( $map_url ) ) : ?>
					<img class="michiryu-sekki-map__image" src="<?php echo esc_url( $map_url ); ?>" alt="<?php echo esc_attr( $map_alt ); ?>" loading="lazy" />
				<?php endif; ?>
				<?php echo $this->render_map_season_path( $seasons, $current ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="michiryu-sekki-map__markers" aria-label="<?php esc_attr_e( 'Sekki locations', 'michiryu-sekki' ); ?>">
					<?php foreach ( $seasons as $season ) : ?>
						<?php echo $this->render_map_marker( $season, $current, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the seasonal progression control.
	 *
	 * @param array<int,array<string,mixed>> $seasons Visible season records.
	 * @param array<string,mixed>            $current Current season.
	 * @param array<string,mixed>            $options Saved options.
	 * @return string
	 */
	private function render_map_progression( $seasons, $current, $options ) {
		$style = $options['map_progression_style'] ?? 'wheel';

		if ( 'none' === $style || count( $seasons ) < 2 ) {
			return '';
		}

		$active_index = 0;
		foreach ( $seasons as $index => $season ) {
			if ( $season['slug'] === $current['slug'] ) {
				$active_index = $index;
				break;
			}
		}

		$classes = array(
			'michiryu-sekki-map__progression',
			'michiryu-sekki-map__progression--' . sanitize_html_class( $style ),
		);

		ob_start();
		?>
		<nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php esc_attr_e( 'Seasonal progression', 'michiryu-sekki' ); ?>" data-mrs-progression="<?php echo esc_attr( $style ); ?>">
			<?php if ( 'wheel' === $style ) : ?>
				<div class="michiryu-sekki-map__wheel-viewport">
					<div class="michiryu-sekki-map__wheel" style="--mrs-season-count: <?php echo esc_attr( count( $seasons ) ); ?>; --mrs-active-index: <?php echo esc_attr( $active_index ); ?>;">
						<?php foreach ( $seasons as $index => $season ) : ?>
							<?php echo $this->render_progression_button( $season, $current, $index, 'wheel', $active_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
						<div class="michiryu-sekki-map__wheel-center" aria-hidden="true">
							<span><?php esc_html_e( '24', 'michiryu-sekki' ); ?></span>
							<small><?php esc_html_e( 'Sekki', 'michiryu-sekki' ); ?></small>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div class="michiryu-sekki-map__timeline" data-mrs-timeline>
					<?php foreach ( array( 'before', 'current', 'after' ) as $timeline_set ) : ?>
						<?php foreach ( $seasons as $index => $season ) : ?>
							<?php echo $this->render_progression_button( $season, $current, $index, 'timeline', $active_index, $timeline_set ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</nav>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single progression control button.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current Current season.
	 * @param int                 $index Season index.
	 * @param string              $style Progression style.
	 * @param int                 $active_index Active season index.
	 * @param string              $timeline_set Timeline copy set.
	 * @return string
	 */
	private function render_progression_button( $season, $current, $index, $style, $active_index, $timeline_set = '' ) {
		$is_current = $season['slug'] === $current['slug'];
		$is_primary = '' === $timeline_set || 'current' === $timeline_set;
		$classes = array( 'michiryu-sekki-map__progression-button' );

		if ( $is_current && $is_primary ) {
			$classes[] = 'is-active';
			$classes[] = 'is-current';
		}

		$label = sprintf(
			/* translators: 1: season number, 2: romanized season name, 3: English season name. */
			__( 'Sekki %1$d, %2$s, %3$s', 'michiryu-sekki' ),
			absint( $season['sekki_number'] ),
			esc_html( $season['romaji'] ),
			esc_html( $season['english_name'] )
		);

		if ( 'wheel' === $style ) {
			$angle = ( ( $index - $active_index ) * ( 360 / 24 ) ) - 38;

			return sprintf(
				'<button class="%1$s" type="button" style="--mrs-progress-index:%2$d; --mrs-progress-angle:%8$sdeg;" data-mrs-map-select="%3$s" data-mrs-progression-marker data-season="%3$s" data-mrs-progress-index="%2$d" aria-label="%4$s" aria-pressed="%5$s" %6$s><span class="michiryu-sekki-map__progression-number">%7$d</span><span class="michiryu-sekki-map__progression-name" aria-hidden="true">%9$s</span>%10$s</button>',
				esc_attr( implode( ' ', $classes ) ),
				absint( $index ),
				esc_attr( $season['slug'] ),
				esc_attr( $label ),
				$is_current ? 'true' : 'false',
				$is_current ? 'aria-current="date"' : '',
				absint( $season['sekki_number'] ),
				esc_attr( round( $angle, 3 ) ),
				esc_html( $season['romaji'] ),
				$this->render_progression_ko_dots( $season )
			);
		}

		return sprintf(
			'<button class="%1$s" type="button" data-mrs-map-select="%2$s" data-mrs-progression-marker data-season="%2$s" data-mrs-timeline-set="%8$s" aria-label="%3$s" aria-pressed="%4$s" %5$s><span>%6$02d</span><strong>%7$s</strong></button>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $season['slug'] ),
			esc_attr( $label ),
			$is_current && $is_primary ? 'true' : 'false',
			$is_current && $is_primary ? 'aria-current="date"' : '',
			absint( $season['sekki_number'] ),
			esc_html( $season['romaji'] ),
			esc_attr( $timeline_set )
		);
	}

	/**
	 * Render compact ko dots for the compass control.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @return string
	 */
	private function render_progression_ko_dots( $season ) {
		$ko_numbers = array_map(
			'absint',
			$season['related_ko'] ?? array()
		);

		return sprintf(
			'<span class="michiryu-sekki-map__progression-ko-list" aria-hidden="true">%s</span>',
			esc_html( sprintf(
				/* translators: 1: first ko number, 2: second ko number, 3: third ko number. */
				__( 'Ko %1$d / %2$d / %3$d', 'michiryu-sekki' ),
				$ko_numbers[0] ?? 0,
				$ko_numbers[1] ?? 0,
				$ko_numbers[2] ?? 0
			) )
		);
	}

	/**
	 * Render the character selector rail.
	 *
	 * @param array<int,array<string,mixed>> $seasons Visible season records.
	 * @param array<string,mixed>            $current Current season.
	 * @return string
	 */
	private function render_map_characters( $seasons, $current, $timestamp_utc, $display_timezone ) {
		$characters = MichiRyu_Sekki_Content::get_characters();
		$current_story = $this->get_current_story_for_season( $current, $timestamp_utc, $display_timezone );
		$current_story_id = $current_story['id'] ?? '';
		$story_characters = array();
		$has_characters = false;
		$has_current_characters = false;

		foreach ( $seasons as $season ) {
			$stories = MichiRyu_Sekki_Content::get_stories_for_sekki( $season['sekki_number'] );
			foreach ( $stories as $story_index => $story ) {
				$is_initial = $season['slug'] === $current['slug'] && ! empty( $current_story_id ) && $story['id'] === $current_story_id;
				$ids = array_values( array_unique( array_merge( array( 'masaru' ), $story['characters'] ) ) );
				$story_characters[] = array(
					'season_slug' => $season['slug'],
					'story_id'    => $story['id'],
					'is_initial'  => $is_initial,
					'lesson'      => $story['lesson'],
					'spotlight'   => $story['spotlight'],
					'ids'         => $ids,
				);

				if ( ! empty( $ids ) ) {
					$has_characters = true;
				}
				if ( $is_initial && ! empty( $ids ) ) {
					$has_current_characters = true;
				}
			}
		}

		ob_start();
		?>
		<aside class="michiryu-sekki-map__characters<?php echo $has_characters ? ' has-characters' : ''; ?><?php echo $has_current_characters ? ' has-visible-characters' : ''; ?>" aria-label="<?php esc_attr_e( 'Story characters', 'michiryu-sekki' ); ?>" data-mrs-map-characters>
			<h3 class="michiryu-sekki-map__characters-title"><?php esc_html_e( 'Characters', 'michiryu-sekki' ); ?></h3>
			<p class="michiryu-sekki-map__characters-empty"><?php esc_html_e( 'Character stories will appear here as they are added.', 'michiryu-sekki' ); ?></p>
			<?php foreach ( $story_characters as $story_group ) : ?>
				<?php foreach ( $story_group['ids'] as $character_id ) : ?>
					<?php if ( empty( $characters[ $character_id ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php
					$character = $characters[ $character_id ];
					$is_masaru  = 'masaru' === $character_id;
					$note       = $is_masaru ? sprintf(
						/* translators: %s: story lesson. */
						__( 'Experiences: %s', 'michiryu-sekki' ),
						esc_html( $story_group['lesson'] )
					) : $character['role'];
					$portrait_url = $this->get_character_portrait_url( $character );
					?>
					<button class="michiryu-sekki-map__character<?php echo $story_group['is_initial'] ? ' is-visible' : ''; ?>" type="button" data-mrs-character data-season="<?php echo esc_attr( $story_group['season_slug'] ); ?>" data-story="<?php echo esc_attr( $story_group['story_id'] ); ?>" data-character="<?php echo esc_attr( $character_id ); ?>" aria-expanded="false">
						<?php if ( ! empty( $portrait_url ) ) : ?>
							<img src="<?php echo esc_url( $portrait_url ); ?>" alt="" aria-hidden="true" loading="lazy" />
						<?php endif; ?>
						<span><?php echo esc_html( $character['name'] ); ?></span>
						<small><?php echo esc_html( $note ); ?></small>
					</button>
					<div class="michiryu-sekki-map__character-popover" data-mrs-character-popover data-season="<?php echo esc_attr( $story_group['season_slug'] ); ?>" data-story="<?php echo esc_attr( $story_group['story_id'] ); ?>" data-character="<?php echo esc_attr( $character_id ); ?>" hidden>
						<button class="michiryu-sekki-map__character-close" type="button" data-mrs-character-close aria-label="<?php esc_attr_e( 'Close character bio', 'michiryu-sekki' ); ?>">×</button>
						<h4><?php echo esc_html( $character['name'] ); ?></h4>
						<p class="michiryu-sekki-map__character-role"><?php echo esc_html( $character['role'] ); ?></p>
						<p><?php echo esc_html( $character['bio'] ); ?></p>
						<?php if ( $is_masaru ) : ?>
							<p><strong><?php esc_html_e( 'In this story', 'michiryu-sekki' ); ?></strong> <?php echo esc_html( $story_group['lesson'] ); ?></p>
						<?php elseif ( ! empty( $story_group['spotlight'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Story spotlight', 'michiryu-sekki' ); ?></strong> <?php echo esc_html( $story_group['spotlight'] ); ?></p>
						<?php endif; ?>
						<p><strong><?php esc_html_e( 'Represents', 'michiryu-sekki' ); ?></strong> <?php echo esc_html( $character['represents'] ); ?></p>
					</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</aside>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render one map marker button.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current Current season.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map_marker( $season, $current, $options ) {
		$is_selected = $season['slug'] === $current['slug'];
		$is_current  = ! empty( $options['show_current_map_highlight'] ) && $is_selected;
		$has_stories = ! empty( MichiRyu_Sekki_Content::get_stories_for_sekki( $season['sekki_number'] ) );
		$tooltip    = sprintf(
			"%s %s\n%s\n%s\n%s%s",
			esc_html( $season['romaji'] ),
			esc_html( $season['kanji'] ),
			esc_html( $season['english_name'] ),
			esc_html( $season['date_range'] ),
			esc_html( $season['phrase'] ),
			$has_stories ? "\n" . esc_html__( 'Stories available', 'michiryu-sekki' ) : ''
		);

		return sprintf(
			'<button class="michiryu-sekki-map__marker%1$s%2$s%11$s" type="button" style="--mrs-map-x:%3$s%%;--mrs-map-y:%4$s%%;" data-mrs-map-marker data-season="%5$s" data-tooltip="%6$s" aria-label="%7$s" aria-controls="mrs-map-detail-%5$s" aria-expanded="%8$s" %9$s><span>%10$d</span>%12$s</button>',
			$is_current ? ' is-current' : '',
			$is_selected ? ' is-active' : '',
			esc_attr( $season['map_x_percent'] ),
			esc_attr( $season['map_y_percent'] ),
			esc_attr( $season['slug'] ),
			esc_attr( $tooltip ),
			esc_attr( sprintf( __( '%1$s, %2$s, %3$s.', 'michiryu-sekki' ), esc_html( $season['romaji'] ), esc_html( $season['english_name'] ), esc_html( strtolower( $season['date_range'] ) ) ) ),
			$is_selected ? 'true' : 'false',
			$is_current ? 'aria-current="date"' : '',
			absint( $season['sekki_number'] ),
			$has_stories ? ' has-stories' : '',
			$has_stories ? '<span class="michiryu-sekki-map__marker-story" aria-hidden="true"></span>' : ''
		);
	}

	/**
	 * Render local previous/current/next directional path.
	 *
	 * @param array<int,array<string,mixed>> $seasons Visible season records.
	 * @param array<string,mixed>            $current Current season.
	 * @return string
	 */
	private function render_map_season_path( $seasons, $current ) {
		$season_count = count( $seasons );

		if ( $season_count < 2 ) {
			return '';
		}

		$paths = array();
		foreach ( $seasons as $index => $season ) {
			$previous = $seasons[ ( $index - 1 + $season_count ) % $season_count ];
			$next     = $seasons[ ( $index + 1 ) % $season_count ];
			$points   = sprintf(
				'%1$s,%2$s %3$s,%4$s %5$s,%6$s',
				esc_attr( $previous['map_x_percent'] ),
				esc_attr( $previous['map_y_percent'] ),
				esc_attr( $season['map_x_percent'] ),
				esc_attr( $season['map_y_percent'] ),
				esc_attr( $next['map_x_percent'] ),
				esc_attr( $next['map_y_percent'] )
			);

			$paths[] = sprintf(
				'<g class="michiryu-sekki-map__season-path%1$s" data-mrs-map-path data-season="%2$s"><polyline class="michiryu-sekki-map__season-path-halo" points="%3$s" /><polyline class="michiryu-sekki-map__season-path-line" points="%3$s" marker-mid="url(#mrs-map-path-arrow)" marker-end="url(#mrs-map-path-arrow)" /></g>',
				$season['slug'] === $current['slug'] ? ' is-active' : '',
				esc_attr( $season['slug'] ),
				$points
			);
		}

		return sprintf(
			'<svg class="michiryu-sekki-map__season-paths" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><defs><marker id="mrs-map-path-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" /></marker></defs>%s</svg>',
			implode( '', $paths )
		);
	}

	/**
	 * Render one map detail panel.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current Current season.
	 * @param array<string,mixed> $current_ko Current ko record.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map_detail( $season, $current, $current_ko, $options ) {
		$previous = MichiRyu_Sekki_Data::get_previous( $season['slug'] );
		$next     = MichiRyu_Sekki_Data::get_next( $season['slug'] );
		$image    = $this->render_map_detail_image( $season, $options );
		$read_more_url = $this->get_season_detail_url( $season, $options );
		$is_current = $season['slug'] === $current['slug'];
		$nav_bottom = 'none' !== ( $options['map_progression_style'] ?? 'wheel' );
		$nav        = $this->render_map_detail_nav( $previous, $next );

		ob_start();
		?>
		<article class="michiryu-sekki-map__detail<?php echo $is_current ? ' is-active' : ''; ?><?php echo $nav_bottom ? ' has-bottom-nav' : ' has-top-nav'; ?>" id="mrs-map-detail-<?php echo esc_attr( $season['slug'] ); ?>" data-season="<?php echo esc_attr( $season['slug'] ); ?>" <?php echo $is_current ? '' : 'hidden'; ?>>
			<?php if ( ! $nav_bottom ) : ?>
				<?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="michiryu-sekki-map__eyebrow">
				<?php
				printf(
					/* translators: %d: Sekki number. */
					esc_html__( 'Sekki %d', 'michiryu-sekki' ),
					absint( $season['sekki_number'] )
				);
				?>
			</p>
			<h3 class="michiryu-sekki-map__detail-title"><span><?php echo esc_html( $season['romaji'] ); ?></span> <span><?php echo esc_html( $season['kanji'] ); ?></span></h3>
			<?php if ( $is_current ) : ?>
				<p class="michiryu-sekki-map__badge"><?php esc_html_e( 'Current season', 'michiryu-sekki' ); ?></p>
			<?php endif; ?>
			<p class="michiryu-sekki-map__english"><?php echo esc_html( $season['english_name'] ); ?></p>
			<p class="michiryu-sekki-map__date"><?php echo esc_html( $season['date_range'] ); ?></p>
			<p class="michiryu-sekki-map__description"><?php echo esc_html( $season['description'] ); ?></p>
			<dl class="michiryu-sekki-map__meta">
				<div><dt><?php esc_html_e( 'Theme', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $season['theme'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Mood', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $season['mood'] ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Materials', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $season['materials'] ); ?></dd></div>
			</dl>
			<p class="michiryu-sekki-map__phrase"><?php echo esc_html( $season['phrase'] ); ?></p>
			<?php if ( ! empty( $read_more_url ) ) : ?>
				<p><a class="michiryu-sekki-map__read-more" href="<?php echo esc_url( $read_more_url ); ?>"><?php esc_html_e( 'Read more', 'michiryu-sekki' ); ?></a></p>
			<?php endif; ?>
			<?php echo $this->render_map_ko_details( $season, $current_ko, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $nav_bottom ) : ?>
				<?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</article>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render ko detail cards for a map season.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current_ko Current ko record.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map_ko_details( $season, $current_ko, $options ) {
		$ko_records = MichiRyu_Sekki_Data::get_ko();
		$stories = MichiRyu_Sekki_Content::get_stories_for_sekki( (int) $season['sekki_number'] );
		$story_ids_by_ko = array();
		$active_ko_number = absint( $season['related_ko'][0] ?? 0 );

		foreach ( $stories as $story ) {
			$story_ids_by_ko[ absint( $story['ko_number'] ?? 0 ) ] = $story['id'] ?? '';
		}

		if ( in_array( absint( $current_ko['ko_number'] ?? 0 ), array_map( 'absint', $season['related_ko'] ?? array() ), true ) ) {
			$active_ko_number = absint( $current_ko['ko_number'] );
		}

		ob_start();
		?>
		<div class="michiryu-sekki-map__ko-details" data-mrs-map-ko-details>
			<?php foreach ( $season['related_ko'] as $ko_number ) : ?>
				<?php $ko = $ko_records[ absint( $ko_number ) - 1 ] ?? array(); ?>
				<?php if ( empty( $ko ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<?php $is_active = absint( $ko['ko_number'] ) === $active_ko_number; ?>
				<?php $title = $this->render_ko_name_title( $ko, $options, 'michiryu-sekki-map__ko-title' ); ?>
				<article class="michiryu-sekki-map__ko-detail<?php echo $is_active ? ' is-active' : ''; ?>" data-ko="<?php echo esc_attr( $ko['ko_number'] ); ?>" data-story="<?php echo esc_attr( $story_ids_by_ko[ absint( $ko['ko_number'] ) ] ?? '' ); ?>" <?php echo $is_active ? '' : 'hidden'; ?>>
					<p class="michiryu-sekki-map__ko-eyebrow">
						<?php
						printf(
							/* translators: %d: Ko number. */
							esc_html__( 'Ko %d', 'michiryu-sekki' ),
							absint( $ko['ko_number'] )
						);
						?>
					</p>
					<?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<p class="michiryu-sekki-map__ko-date"><?php echo esc_html( $ko['date_range'] ); ?></p>
					<p class="michiryu-sekki-map__ko-description"><?php echo esc_html( $ko['short_description'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render previous and next season detail buttons.
	 *
	 * @param array<string,mixed> $previous Previous season.
	 * @param array<string,mixed> $next Next season.
	 * @return string
	 */
	private function render_map_detail_nav( $previous, $next ) {
		return sprintf(
			'<div class="michiryu-sekki-map__nav"><button type="button" data-mrs-map-select="%1$s">← %2$s</button><button type="button" data-mrs-map-select="%3$s">%4$s →</button></div>',
			esc_attr( $previous['slug'] ),
			esc_html( $previous['romaji'] ),
			esc_attr( $next['slug'] ),
			esc_html( $next['romaji'] )
		);
	}

	/**
	 * Render ko story cards for the selected map season.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $current Current season.
	 * @return string
	 */
	private function render_map_stories( $season, $current, $timestamp_utc, $display_timezone ) {
		$stories = MichiRyu_Sekki_Content::get_stories_for_sekki( $season['sekki_number'] );
		$is_current = $season['slug'] === $current['slug'];
		$current_story = $is_current ? $this->get_current_story_for_season( $season, $timestamp_utc, $display_timezone ) : array();
		$current_story_id = $current_story['id'] ?? '';

		if ( empty( $stories ) ) {
			return sprintf(
				'<section class="michiryu-sekki-map__stories michiryu-sekki-map__stories--empty" data-season="%1$s" %2$s><h3>%3$s</h3><p>%4$s</p></section>',
				esc_attr( $season['slug'] ),
				$is_current ? '' : 'hidden',
				esc_html__( 'Ko Stories', 'michiryu-sekki' ),
				esc_html__( 'Stories for this season have not been added yet.', 'michiryu-sekki' )
			);
		}

		ob_start();
		?>
		<section class="michiryu-sekki-map__stories" data-season="<?php echo esc_attr( $season['slug'] ); ?>" <?php echo $is_current ? '' : 'hidden'; ?>>
			<div class="michiryu-sekki-map__stories-header">
				<h3><?php esc_html_e( 'Ko Stories', 'michiryu-sekki' ); ?></h3>
				<p><?php echo esc_html( $season['romaji'] . ' - ' . $season['english_name'] ); ?></p>
			</div>
			<div class="michiryu-sekki-map__story-tabs" role="tablist" aria-label="<?php echo esc_attr( $season['romaji'] . ' ko stories' ); ?>">
				<?php foreach ( $stories as $story_index => $story ) : ?>
					<?php $panel_id = $this->get_story_panel_id( $season, $story ); ?>
					<?php $tab_id = $panel_id . '-tab'; ?>
					<?php $is_active_story = $is_current ? $story['id'] === $current_story_id : 0 === $story_index; ?>
					<button class="michiryu-sekki-map__story-tab<?php echo $is_active_story ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $tab_id ); ?>" type="button" role="tab" data-mrs-story-tab data-season="<?php echo esc_attr( $season['slug'] ); ?>" data-story="<?php echo esc_attr( $story['id'] ); ?>" data-ko="<?php echo esc_attr( $story['ko_number'] ); ?>" aria-selected="<?php echo $is_active_story ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>" tabindex="<?php echo $is_active_story ? '0' : '-1'; ?>">
						<span><?php echo esc_html( sprintf( __( 'Ko %d', 'michiryu-sekki' ), absint( $story['ko_number'] ) ) ); ?></span>
						<strong><?php echo esc_html( $story['title'] ); ?></strong>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="michiryu-sekki-map__story-list">
				<?php foreach ( $stories as $story_index => $story ) : ?>
					<?php $panel_id = $this->get_story_panel_id( $season, $story ); ?>
					<?php $tab_id = $panel_id . '-tab'; ?>
					<?php $is_active_story = $is_current ? $story['id'] === $current_story_id : 0 === $story_index; ?>
					<article class="michiryu-sekki-map__story<?php echo $is_active_story ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $tab_id ); ?>" tabindex="0" data-mrs-story data-story="<?php echo esc_attr( $story['id'] ); ?>" data-ko="<?php echo esc_attr( $story['ko_number'] ); ?>" <?php echo $is_active_story ? '' : 'hidden'; ?>>
						<p class="michiryu-sekki-map__story-eyebrow">
							<?php
							printf(
								/* translators: %d: Ko number. */
								esc_html__( 'Ko %d', 'michiryu-sekki' ),
								absint( $story['ko_number'] )
							);
							?>
						</p>
						<h4><?php echo esc_html( $story['title'] ); ?></h4>
						<?php echo wp_kses_post( $story['body_html'] ); ?>
						<?php if ( ! empty( $story['spotlight'] ) ) : ?>
							<div class="michiryu-sekki-map__story-spotlight">
								<h5><?php esc_html_e( 'Character Spotlight', 'michiryu-sekki' ); ?></h5>
								<p><?php echo esc_html( $story['spotlight'] ); ?></p>
							</div>
						<?php endif; ?>
						<div class="michiryu-sekki-map__story-reflection">
							<h5><?php esc_html_e( 'Ikebana Reflection', 'michiryu-sekki' ); ?></h5>
							<dl class="michiryu-sekki-map__story-meta">
								<div><dt><?php esc_html_e( 'Materials', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['materials'] ); ?></dd></div>
								<div><dt><?php esc_html_e( 'Theme', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['theme'] ); ?></dd></div>
								<div><dt><?php esc_html_e( 'Lesson', 'michiryu-sekki' ); ?></dt><dd><?php echo esc_html( $story['lesson'] ); ?></dd></div>
							</dl>
						</div>
						<div class="michiryu-sekki-map__story-controls">
							<?php echo $this->render_story_step_button( $season, $stories, $story_index, -1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo $this->render_story_step_button( $season, $stories, $story_index, 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return a stable tabpanel id for a story.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $story Story record.
	 * @return string
	 */
	private function get_story_panel_id( $season, $story ) {
		return 'mrs-story-' . sanitize_html_class( $season['slug'] ?? 'season' ) . '-' . sanitize_html_class( $story['id'] ?? 'story' );
	}

	/**
	 * Render a linear previous/next story button.
	 *
	 * @param array<string,mixed>          $season Current season record.
	 * @param array<int,array<string,mixed>> $stories Stories for the current season.
	 * @param int                         $story_index Current story index.
	 * @param int                         $direction -1 for previous, 1 for next.
	 * @return string
	 */
	private function render_story_step_button( $season, $stories, $story_index, $direction ) {
		$target = $this->get_story_step_target( $season, $stories, $story_index, $direction );

		if ( empty( $target['story_id'] ) || empty( $target['season_slug'] ) ) {
			return sprintf(
				'<button type="button" disabled>%s</button>',
				esc_html( $target['label'] )
			);
		}

		return sprintf(
			'<button type="button" data-mrs-story-step="%1$d" data-mrs-story-season="%2$s" data-mrs-story-target="%3$s">%4$s</button>',
			(int) $direction,
			esc_attr( $target['season_slug'] ),
			esc_attr( $target['story_id'] ),
			esc_html( $target['label'] )
		);
	}

	/**
	 * Resolve the next linear story target.
	 *
	 * @param array<string,mixed>            $season Current season record.
	 * @param array<int,array<string,mixed>> $stories Stories for the current season.
	 * @param int                           $story_index Current story index.
	 * @param int                           $direction -1 for previous, 1 for next.
	 * @return array<string,string>
	 */
	private function get_story_step_target( $season, $stories, $story_index, $direction ) {
		$next_index = $story_index + $direction;

		if ( isset( $stories[ $next_index ] ) ) {
			return array(
				'label'       => $direction > 0 ? __( 'Next Ko', 'michiryu-sekki' ) : __( 'Previous Ko', 'michiryu-sekki' ),
				'season_slug' => (string) $season['slug'],
				'story_id'    => (string) $stories[ $next_index ]['id'],
			);
		}

		$adjacent = $direction > 0 ? MichiRyu_Sekki_Data::get_next( $season['slug'] ) : MichiRyu_Sekki_Data::get_previous( $season['slug'] );
		$adjacent_stories = MichiRyu_Sekki_Content::get_stories_for_sekki( (int) ( $adjacent['sekki_number'] ?? 0 ) );

		if ( ! empty( $adjacent_stories ) ) {
			$target_story = $direction > 0 ? $adjacent_stories[0] : $adjacent_stories[ count( $adjacent_stories ) - 1 ];
			return array(
				'label'       => sprintf(
					$direction > 0 ? __( 'Next season: %s', 'michiryu-sekki' ) : __( 'Previous season: %s', 'michiryu-sekki' ),
					esc_html( $adjacent['romaji'] )
				),
				'season_slug' => (string) $adjacent['slug'],
				'story_id'    => (string) $target_story['id'],
			);
		}

		return array(
			'label'       => sprintf(
				$direction > 0 ? __( 'More stories coming with %s', 'michiryu-sekki' ) : __( 'Earlier stories coming with %s', 'michiryu-sekki' ),
				esc_html( $adjacent['romaji'] ?? '' )
			),
			'season_slug' => '',
			'story_id'    => '',
		);
	}

	/**
	 * Render the protected Sekki artwork inside the map detail panel.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_map_detail_image( $season, $options ) {
		$url = $this->get_asset_url( 'sekki', $season['image_file'], $options['custom_fallback_image_url'], $options );

		if ( empty( $url ) ) {
			return '';
		}

		return sprintf(
			'<div class="michiryu-sekki-map__thumb michiryu-sekki-image-wrap"><img src="%1$s" alt="%2$s" loading="lazy" draggable="false" />%3$s</div>',
			esc_url( $url ),
			esc_attr( $season['romaji'] . ' - ' . $season['english_name'] ),
			$this->render_signature(
				array(
					'signature_position' => 'bottom-right',
					'signature_size'     => 'small',
					'signature_opacity'  => $options['signature_opacity'] ?? 1,
				)
			)
		);
	}

	/**
	 * Get character portrait URL.
	 *
	 * @param array<string,string> $character Character record.
	 * @return string
	 */
	private function get_character_portrait_url( $character ) {
		if ( empty( $character['portrait_file'] ) ) {
			return '';
		}

		return $this->get_provider_image_url( 'characters/' . basename( $character['portrait_file'] ) );
	}

	/**
	 * Get the map image URL.
	 *
	 * @return string
	 */
	private function get_map_image_url() {
		return $this->get_provider_image_url( 'map' );
	}

	/**
	 * Get the dedicated map page URL.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_map_page_url( $options ) {
		if ( ! empty( $options['map_page_url'] ) ) {
			return $options['map_page_url'];
		}

		return home_url( '/sekki-map/' );
	}

	/**
	 * Get optional season detail URL.
	 *
	 * @param array<string,mixed> $season Season record.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_season_detail_url( $season, $options ) {
		if ( 'none' === $options['read_more_link_behavior'] ) {
			return '';
		}

		if ( ! empty( $season['detail_url'] ) ) {
			return $season['detail_url'];
		}

		if ( 'external' === $options['read_more_link_behavior'] && ! empty( $options['external_season_base_url'] ) ) {
			return trailingslashit( $options['external_season_base_url'] ) . $season['slug'];
		}

		if ( 'internal' === $options['read_more_link_behavior'] ) {
			return home_url( '/sekki/' . $season['slug'] . '/' );
		}

		return '';
	}

	/**
	 * Resolve shortcode boolean override.
	 *
	 * @param mixed $value Attribute value.
	 * @param bool  $fallback Saved setting.
	 * @return bool
	 */
	private function resolve_bool_arg( $value, $fallback ) {
		if ( '' === $value || null === $value ) {
			return (bool) $fallback;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Render the current heading.
	 *
	 * @param array<string,mixed> $season Season data.
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_heading( $season, $args ) {
		$parts = array();

		if ( $args['show_romanized'] ) {
			$parts[] = '<span class="michiryu-sekki__romanized">' . esc_html( $season['romaji'] ) . '</span>';
		}

		if ( $args['show_kanji'] ) {
			$parts[] = '<span class="michiryu-sekki__kanji">' . esc_html( $season['kanji'] ) . '</span>';
		}

		if ( $args['show_english'] ) {
			$parts[] = '<span class="michiryu-sekki__english">' . esc_html( $season['english_name'] ) . '</span>';
		}

		if ( empty( $parts ) ) {
			$parts[] = '<span class="michiryu-sekki__romanized">' . esc_html( $season['romaji'] ) . '</span>';
		}

		return '<h3 class="michiryu-sekki__title">' . implode( ' ', $parts ) . '</h3>';
	}

	/**
	 * Render seasonal theme, materials, and mood as a compact list.
	 *
	 * @param array<string,mixed> $season Season data.
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_season_materials_list( $season, $args ) {
		if ( empty( $args['show_ikebana_materials'] ) || ! in_array( $args['plan'], array( 'standard', 'ikebana' ), true ) ) {
			return '';
		}

		$items = array(
			__( 'Theme', 'michiryu-sekki' )    => $season['theme'] ?? '',
			__( 'Materials', 'michiryu-sekki' ) => $season['materials'] ?? '',
			__( 'Mood', 'michiryu-sekki' )     => $season['mood'] ?? '',
		);

		ob_start();
		?>
		<ul class="michiryu-sekki__materials-list">
			<?php foreach ( $items as $label => $value ) : ?>
				<?php if ( '' === (string) $value ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<li>
					<span><?php echo esc_html( $label ); ?></span>
					<em><?php echo esc_html( $value ); ?></em>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render visible ko name parts.
	 *
	 * @param array<string,mixed> $ko Ko data.
	 * @param array<string,mixed> $settings Visibility settings.
	 * @param string              $class_name Heading class.
	 * @return string
	 */
	private function render_ko_name_title( $ko, $settings, $class_name ) {
		$parts = array();

		if ( ! empty( $settings['show_kanji'] ) ) {
			$parts[] = '<span class="michiryu-sekki__ko-title-line">' . esc_html( $ko['kanji'] ) . '</span>';
		}

		if ( ! empty( $settings['show_romanized'] ) ) {
			$parts[] = '<span class="michiryu-sekki__ko-title-line">' . esc_html( $ko['romaji'] ) . '</span>';
		}

		if ( ! empty( $settings['show_english'] ) ) {
			$parts[] = '<span class="michiryu-sekki__ko-title-line">' . esc_html( $ko['english_name'] ) . '</span>';
		}

		if ( empty( $parts ) && empty( $settings['show_english'] ) ) {
			$parts[] = '<span class="michiryu-sekki__ko-title-line">' . esc_html( $ko['romaji'] ) . '</span>';
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return '<h4 class="' . esc_attr( $class_name ) . '">' . implode( ' ', $parts ) . '</h4>';
	}

	/**
	 * Render season image when available.
	 *
	 * @param array<string,mixed> $season Season data.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function render_sekki_image( $season, $options, $args = array() ) {
		if ( 'none' === $options['image_style'] ) {
			return '';
		}

		$url = $this->get_asset_url( 'sekki', $season['image_file'], $options['custom_fallback_image_url'], $options );

		if ( empty( $url ) ) {
			return '';
		}

		$image_classes = array(
			'michiryu-sekki__image',
			'michiryu-sekki-image-wrap',
			'michiryu-sekki__image--has-signature',
		);

		if ( ! empty( $args['show_date_stamp'] ) ) {
			$image_classes[] = 'michiryu-sekki__image--has-date-stamp';
		}

		return sprintf(
			'<div class="%1$s"><img src="%2$s" alt="%3$s" loading="lazy" />%4$s%5$s</div>',
			esc_attr( implode( ' ', $image_classes ) ),
			esc_url( $url ),
			esc_attr( $season['romaji'] . ' - ' . $season['english_name'] ),
			$this->render_date_stamp( $args ),
			$this->render_signature( $args )
		);
	}

	/**
	 * Render optional current date stamp over the image.
	 *
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_date_stamp( $args ) {
		if ( empty( $args['show_date_stamp'] ) ) {
			return '';
		}

		$timestamp = absint( $args['timestamp_utc'] ?? $this->get_current_timestamp_utc() );
		$timezone  = (string) ( $args['display_timezone'] ?? $this->get_display_timezone() );
		$month     = strtoupper( $this->format_timestamp_for_timezone( $timestamp, $timezone, 'M' ) );
		$day       = $this->format_timestamp_for_timezone( $timestamp, $timezone, 'j' );

		return sprintf(
			'<time class="michiryu-sekki-date-stamp" datetime="%1$s" data-mrs-date-stamp data-timestamp="%2$d" data-timezone="%3$s"><span class="michiryu-sekki-date-stamp__month">%4$s</span><span class="michiryu-sekki-date-stamp__day">%5$s</span></time>',
			esc_attr( $this->format_timestamp_for_timezone( $timestamp, $timezone, 'Y-m-d' ) ),
			absint( $timestamp ),
			esc_attr( $timezone ),
			esc_html( $month ),
			esc_html( $day )
		);
	}

	/**
	 * Render reusable signature overlay.
	 *
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_signature( $args ) {
		$signature_url = $this->get_provider_image_url( 'signature' );

		if ( empty( $signature_url ) ) {
			return '';
		}

		$position = sanitize_html_class( $args['signature_position'] ?? 'bottom-right' );
		$size     = sanitize_html_class( $args['signature_size'] ?? 'medium' );
		$opacity  = max( 0.5, min( 1.0, (float) ( $args['signature_opacity'] ?? 1 ) ) );

		return sprintf(
			'<img class="michiryu-sekki-signature michiryu-sekki-signature--%1$s michiryu-sekki-signature--%2$s" src="%3$s" alt="" aria-hidden="true" loading="lazy" style="--mrs-signature-opacity:%4$s;" />',
			esc_attr( $position ),
			esc_attr( $size ),
			esc_url( $signature_url ),
			esc_attr( $opacity )
		);
	}

	/**
	 * Render ko microseason details.
	 *
	 * @param array<string,mixed> $ko Ko data.
	 * @param array<string,mixed> $options Saved options.
	 * @param array<string,mixed> $args Render args.
	 * @return string
	 */
	private function render_ko( $ko, $options, $args ) {
		$icon = '';
		$title = $this->render_ko_name_title( $ko, $args, 'michiryu-sekki__ko-title' );
		$description = (string) ( $ko['short_description'] ?? '' );
		$duplicate_description = sprintf(
			/* translators: %s: ko English name. */
			__( 'A five-day microseason: %s.', 'michiryu-sekki' ),
			$ko['english_name'] ?? ''
		);

		if ( $description === $duplicate_description ) {
			$description = '';
		}

		if ( 'none' !== $options['icon_style'] ) {
			$icon_url = $this->get_asset_url( 'ko', $ko['icon_file'], '', $options );
			if ( ! empty( $icon_url ) ) {
				$icon = sprintf(
					'<div class="michiryu-sekki__ko-icon"><img src="%1$s" alt="%2$s" loading="lazy" /></div>',
					esc_url( $icon_url ),
					esc_attr( $ko['romaji'] . ' - ' . $ko['english_name'] )
				);
			}
		}

		return sprintf(
			'<div class="michiryu-sekki__ko">%1$s<div class="michiryu-sekki__ko-body"><p class="michiryu-sekki__ko-label"><span>%2$s</span> <span class="michiryu-sekki__ko-label-date">%4$s</span></p>%3$s%5$s</div></div>',
			$icon,
			esc_html__( 'Ko Microseason', 'michiryu-sekki' ),
			$title,
			esc_html( $ko['date_range'] ),
			'' === $description ? '' : '<p class="michiryu-sekki__ko-description">' . esc_html( $description ) . '</p>'
		);
	}

	/**
	 * Whether the selected mode should attempt image output.
	 *
	 * @param string $style Style.
	 * @param string $plan Plan.
	 * @return bool
	 */
	private function should_show_image( $style, $plan ) {
		return 'text' !== $style && 'minimal' !== $plan;
	}

	/**
	 * Resolve provider asset URL.
	 *
	 * @param string              $kind Asset kind: sekki or ko.
	 * @param string              $filename Stable asset filename.
	 * @param string              $fallback_url Optional fallback URL.
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_asset_url( $kind, $filename, $fallback_url, $options ) {
		$kind       = 'ko' === $kind ? 'ko' : 'sekki';
		$filename   = basename( (string) $filename );
		$provider_url = $this->get_provider_image_url( $kind . '/' . $filename );

		if ( ! empty( $provider_url ) ) {
			return $provider_url;
		}

		if ( ! empty( $fallback_url ) ) {
			return esc_url_raw( $fallback_url );
		}

		return '';
	}

	/**
	 * Resolve an image through the active content provider.
	 *
	 * @param string $id Image identifier.
	 * @return string
	 */
	private function get_provider_image_url( $id ) {
		try {
			$image = MichiRyu_Sekki_Content::get_provider()->get_image( $id );
		} catch ( Throwable $error ) {
			return '';
		}

		if ( is_string( $image ) && '' !== $image ) {
			if ( preg_match( '#^https?://#i', $image ) ) {
				return esc_url_raw( $image );
			}

			$relative = ltrim( $image, '/' );
			if ( file_exists( MICHIRYU_SEKKI_PATH . $relative ) ) {
				return MICHIRYU_SEKKI_URL . $relative;
			}
		}

		if ( is_array( $image ) && ! empty( $image['url'] ) && is_string( $image['url'] ) ) {
			return esc_url_raw( $image['url'] );
		}

		return '';
	}

}
