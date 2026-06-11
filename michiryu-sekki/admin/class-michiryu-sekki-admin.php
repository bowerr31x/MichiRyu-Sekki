<?php
/**
 * Admin settings page.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the MichiRyu-Sekki-Calendar settings screen.
 */
class MichiRyu_Sekki_Admin {
	/**
	 * Main plugin instance.
	 *
	 * @var MichiRyu_Sekki
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param MichiRyu_Sekki $plugin Main plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'MichiRyu-Sekki-Calendar', 'michiryu-sekki' ),
			__( 'MichiRyu-Sekki-Calendar', 'michiryu-sekki' ),
			'manage_options',
			'michiryu',
			array( $this, 'render_page' ),
			'dashicons-palmtree',
			58
		);
	}

	/**
	 * Register option.
	 */
	public function register_settings() {
		register_setting(
			'michiryu_sekki_settings',
			MichiRyu_Sekki::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this->plugin, 'sanitize_options' ),
				'default'           => $this->plugin->get_default_options(),
			)
		);
	}

	/**
	 * Render page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = $this->plugin->get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MichiRyu-Sekki-Calendar', 'michiryu-sekki' ); ?></h1>
			<p><?php esc_html_e( 'Set up the seasonal journey, story reader, and map experience.', 'michiryu-sekki' ); ?></p>

			<div class="notice notice-info inline">
				<p><strong><?php esc_html_e( 'Recommended journey setup', 'michiryu-sekki' ); ?></strong></p>
				<p><?php esc_html_e( 'Use [michiryu_journey] for the main experience. It shows the current Sekki, current Ko, story entry, reading progress, and journey map handoff.', 'michiryu-sekki' ); ?></p>
				<p><code>[michiryu_journey]</code> <code>[michiryu_story]</code> <code>[michiryu_sekki_map]</code></p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'michiryu_sekki_settings' ); ?>

				<h2><?php esc_html_e( 'Core Settings', 'michiryu-sekki' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="michiryu-sekki-default-style"><?php esc_html_e( 'Default display style', 'michiryu-sekki' ); ?></label></th>
						<td>
							<?php $this->render_select( 'default_style', $options['default_style'], $this->get_style_options(), 'michiryu-sekki-default-style' ); ?>
							<p class="description"><?php esc_html_e( 'Used by [michiryu_sekki], widgets, and blocks.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<?php $this->render_checkbox_row( 'show_ko_icon', __( 'Show Ko microseason section', 'michiryu-sekki' ), $options['show_ko_icon'] ); ?>
					<?php $this->render_checkbox_row( 'show_kanji', __( 'Show Japanese kanji', 'michiryu-sekki' ), $options['show_kanji'] ); ?>
					<?php $this->render_checkbox_row( 'show_romanized', __( 'Show romanized name', 'michiryu-sekki' ), $options['show_romanized'] ); ?>
					<?php $this->render_checkbox_row( 'show_english', __( 'Show English name', 'michiryu-sekki' ), $options['show_english'] ); ?>
					<?php $this->render_checkbox_row( 'show_sekki_image', __( 'Show Sekki image', 'michiryu-sekki' ), $options['show_sekki_image'] ); ?>
					<?php $this->render_checkbox_row( 'show_ikebana_materials', __( 'Show ikebana materials', 'michiryu-sekki' ), $options['show_ikebana_materials'] ); ?>
					<?php $this->render_checkbox_row( 'show_date_stamp', __( 'Show current date stamp', 'michiryu-sekki' ), $options['show_date_stamp'] ); ?>
					<?php $this->render_checkbox_row( 'show_story_teaser', __( 'Show story teaser', 'michiryu-sekki' ), $options['show_story_teaser'] ); ?>
					<tr>
						<th scope="row"><label for="michiryu-sekki-map-page-url"><?php esc_html_e( 'Dedicated map page URL', 'michiryu-sekki' ); ?></label></th>
						<td>
							<input id="michiryu-sekki-map-page-url" type="url" class="large-text" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[map_page_url]" value="<?php echo esc_attr( $options['map_page_url'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Use this when Map open behavior is Dedicated page or New tab. Add [michiryu_sekki_map] to that page.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-custom-css"><?php esc_html_e( 'Optional custom CSS', 'michiryu-sekki' ); ?></label></th>
						<td>
							<textarea id="michiryu-sekki-custom-css" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[custom_css]" class="large-text code" rows="8"><?php echo esc_textarea( $options['custom_css'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'CSS is printed only when the Sekki display renders.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
				</table>

				<details>
					<summary><strong><?php esc_html_e( 'Advanced display settings', 'michiryu-sekki' ); ?></strong></summary>
					<p class="description"><?php esc_html_e( 'These options are mostly for older widgets, shortcodes, and custom map/page setups.', 'michiryu-sekki' ); ?></p>
					<table class="form-table" role="presentation">
					<?php
					$this->render_checkbox_row( 'show_date_range', __( 'Show date range', 'michiryu-sekki' ), $options['show_date_range'] );
					$this->render_checkbox_row( 'show_description', __( 'Show description', 'michiryu-sekki' ), $options['show_description'] );
					$this->render_checkbox_row( 'use_bundled_images', __( 'Use bundled images', 'michiryu-sekki' ), $options['use_bundled_images'] );
					$this->render_checkbox_row( 'enable_map_link', __( 'Enable Explore Map link', 'michiryu-sekki' ), $options['enable_map_link'] );
					$this->render_checkbox_row( 'show_map_in_widget', __( 'Show map in widget', 'michiryu-sekki' ), $options['show_map_in_widget'] );
					$this->render_checkbox_row( 'show_current_map_highlight', __( 'Show current season highlight', 'michiryu-sekki' ), $options['show_current_map_highlight'] );
					?>
					<tr>
						<th scope="row"><label for="michiryu-sekki-default-plan"><?php esc_html_e( 'Seasonal Plan', 'michiryu-sekki' ); ?></label></th>
						<td>
							<?php $this->render_select( 'default_plan', $options['default_plan'], $this->get_plan_options(), 'michiryu-sekki-default-plan' ); ?>
							<p class="description"><?php esc_html_e( 'Choose how much seasonal context to show by default.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-map-progression-style"><?php esc_html_e( 'Map progression style', 'michiryu-sekki' ); ?></label></th>
						<td>
							<?php $this->render_select( 'map_progression_style', $options['map_progression_style'], $this->get_map_progression_style_options(), 'michiryu-sekki-map-progression-style' ); ?>
							<p class="description"><?php esc_html_e( 'Choose how the map shows movement through the 24 Sekki seasons.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-reader-open-behavior"><?php esc_html_e( 'Story reader behavior', 'michiryu-sekki' ); ?></label></th>
						<td>
							<?php $this->render_select( 'reader_open_behavior', $options['reader_open_behavior'], $this->get_reader_open_behavior_options(), 'michiryu-sekki-reader-open-behavior' ); ?>
							<p class="description"><?php esc_html_e( 'Choose whether journey stories open as a pop-out or appear below the journey card.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-signature-opacity"><?php esc_html_e( 'Signature opacity', 'michiryu-sekki' ); ?></label></th>
						<td>
							<input id="michiryu-sekki-signature-opacity" type="number" min="0.5" max="1" step="0.05" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[signature_opacity]" value="<?php echo esc_attr( $options['signature_opacity'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Used on seasonal images and story reader images.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-map-open-behavior"><?php esc_html_e( 'Map open behavior', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'map_open_behavior', $options['map_open_behavior'], $this->get_map_open_behavior_options(), 'michiryu-sekki-map-open-behavior' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-read-more-behavior"><?php esc_html_e( 'Read more link behavior', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'read_more_link_behavior', $options['read_more_link_behavior'], $this->get_read_more_link_behavior_options(), 'michiryu-sekki-read-more-behavior' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-external-season-base-url"><?php esc_html_e( 'Base URL for external season pages', 'michiryu-sekki' ); ?></label></th>
						<td>
							<input id="michiryu-sekki-external-season-base-url" type="url" class="large-text" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[external_season_base_url]" value="<?php echo esc_attr( $options['external_season_base_url'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-image-style"><?php esc_html_e( 'Image style', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'image_style', $options['image_style'], $this->get_image_style_options(), 'michiryu-sekki-image-style' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-signature-position"><?php esc_html_e( 'Signature position', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'signature_position', $options['signature_position'], $this->get_signature_position_options(), 'michiryu-sekki-signature-position' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-signature-size"><?php esc_html_e( 'Signature size', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'signature_size', $options['signature_size'], $this->get_signature_size_options(), 'michiryu-sekki-signature-size' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-icon-style"><?php esc_html_e( 'Icon style', 'michiryu-sekki' ); ?></label></th>
						<td><?php $this->render_select( 'icon_style', $options['icon_style'], $this->get_icon_style_options(), 'michiryu-sekki-icon-style' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-fallback-image"><?php esc_html_e( 'Custom fallback image URL', 'michiryu-sekki' ); ?></label></th>
						<td>
							<input id="michiryu-sekki-fallback-image" type="url" class="large-text" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[custom_fallback_image_url]" value="<?php echo esc_attr( $options['custom_fallback_image_url'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Used for Sekki displays when the matching bundled image file is missing. Per-season custom image URLs can be added later.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					</table>
				</details>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render select.
	 *
	 * @param string              $key Option key.
	 * @param string              $value Current value.
	 * @param array<string,string> $choices Choices.
	 * @param string              $id Field ID.
	 */
	private function render_select( $key, $value, $choices, $id ) {
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]">
			<?php foreach ( $choices as $choice_value => $label ) : ?>
				<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( $value, $choice_value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render checkbox row.
	 *
	 * @param string $key Option key.
	 * @param string $label Label.
	 * @param bool   $checked Checked state.
	 */
	private function render_checkbox_row( $key, $label, $checked ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?> />
					<?php esc_html_e( 'Yes', 'michiryu-sekki' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Style choices.
	 *
	 * @return array<string,string>
	 */
	private function get_style_options() {
		return array(
			'text'       => __( 'Text', 'michiryu-sekki' ),
			'compact'    => __( 'Compact', 'michiryu-sekki' ),
			'small'      => __( 'Small', 'michiryu-sekki' ),
			'banner'     => __( 'Banner', 'michiryu-sekki' ),
			'image_card' => __( 'Image card', 'michiryu-sekki' ),
			'ikebana'    => __( 'Ikebana', 'michiryu-sekki' ),
			'explore_map' => __( 'Explore Map', 'michiryu-sekki' ),
		);
	}

	/**
	 * Seasonal plan choices.
	 *
	 * @return array<string,string>
	 */
	private function get_plan_options() {
		return array(
			'minimal'     => __( 'Minimal', 'michiryu-sekki' ),
			'standard'    => __( 'Standard', 'michiryu-sekki' ),
			'ikebana'     => __( 'Ikebana', 'michiryu-sekki' ),
			'banner'      => __( 'Banner', 'michiryu-sekki' ),
			'educational' => __( 'Educational', 'michiryu-sekki' ),
		);
	}

	/**
	 * Image style choices.
	 *
	 * @return array<string,string>
	 */
	private function get_image_style_options() {
		return array(
			'square' => __( 'Square', 'michiryu-sekki' ),
			'banner' => __( 'Banner', 'michiryu-sekki' ),
			'circle' => __( 'Circle', 'michiryu-sekki' ),
			'none'   => __( 'None', 'michiryu-sekki' ),
		);
	}

	/**
	 * Icon style choices.
	 *
	 * @return array<string,string>
	 */
	private function get_icon_style_options() {
		return array(
			'outline' => __( 'Outline', 'michiryu-sekki' ),
			'circle'  => __( 'Circle', 'michiryu-sekki' ),
			'none'    => __( 'None', 'michiryu-sekki' ),
		);
	}

	/**
	 * Map open behavior choices.
	 *
	 * @return array<string,string>
	 */
	private function get_map_open_behavior_options() {
		return array(
			'modal'   => __( 'Modal', 'michiryu-sekki' ),
			'page'    => __( 'Dedicated page', 'michiryu-sekki' ),
			'new_tab' => __( 'New tab', 'michiryu-sekki' ),
		);
	}

	/**
	 * Map progression display choices.
	 *
	 * @return array<string,string>
	 */
	private function get_map_progression_style_options() {
		return array(
			'wheel'    => __( 'Seasonal compass', 'michiryu-sekki' ),
			'timeline' => __( 'Slim seasonal timeline', 'michiryu-sekki' ),
			'none'     => __( 'Disabled', 'michiryu-sekki' ),
		);
	}

	/**
	 * Story reader display choices.
	 *
	 * @return array<string,string>
	 */
	private function get_reader_open_behavior_options() {
		return array(
			'modal'  => __( 'Pop-out reader', 'michiryu-sekki' ),
			'inline' => __( 'Below journey card', 'michiryu-sekki' ),
		);
	}

	/**
	 * Read more behavior choices.
	 *
	 * @return array<string,string>
	 */
	private function get_read_more_link_behavior_options() {
		return array(
			'none'     => __( 'None', 'michiryu-sekki' ),
			'internal' => __( 'Internal', 'michiryu-sekki' ),
			'external' => __( 'External', 'michiryu-sekki' ),
		);
	}

	/**
	 * Signature position choices.
	 *
	 * @return array<string,string>
	 */
	private function get_signature_position_options() {
		return array(
			'bottom-right' => __( 'Bottom right', 'michiryu-sekki' ),
			'bottom-left'  => __( 'Bottom left', 'michiryu-sekki' ),
			'top-right'    => __( 'Top right', 'michiryu-sekki' ),
			'top-left'     => __( 'Top left', 'michiryu-sekki' ),
		);
	}

	/**
	 * Signature size choices.
	 *
	 * @return array<string,string>
	 */
	private function get_signature_size_options() {
		return array(
			'small'  => __( 'Small', 'michiryu-sekki' ),
			'medium' => __( 'Medium', 'michiryu-sekki' ),
			'large'  => __( 'Large', 'michiryu-sekki' ),
		);
	}
}
