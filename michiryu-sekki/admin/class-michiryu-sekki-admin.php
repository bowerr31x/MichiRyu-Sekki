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
			<p><?php esc_html_e( 'Set up the seasonal journey and map experience.', 'michiryu-sekki' ); ?></p>

			<div class="notice notice-info inline">
				<p><strong><?php esc_html_e( 'Recommended setup', 'michiryu-sekki' ); ?></strong></p>
				<p><?php esc_html_e( 'Use', 'michiryu-sekki' ); ?> <code>[michiryu_sekki]</code> <?php esc_html_e( 'for the main experience. It shows the current Sekki, current Ko, story entry, and journey map handoff.', 'michiryu-sekki' ); ?></p>
				<p><?php esc_html_e( 'Optional for other sections or pages:', 'michiryu-sekki' ); ?> <code>[michiryu_story]</code> <code>[michiryu_sekki_map]</code></p>
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
					<?php $this->render_checkbox_row( 'show_ikebana_materials', __( 'Show ikebana materials and theme', 'michiryu-sekki' ), $options['show_ikebana_materials'] ); ?>
					<?php $this->render_checkbox_row( 'show_date_stamp', __( 'Show current date stamp', 'michiryu-sekki' ), $options['show_date_stamp'] ); ?>
					<?php $this->render_checkbox_row( 'show_story_teaser', __( 'Show story teaser', 'michiryu-sekki' ), $options['show_story_teaser'] ); ?>
					<?php $this->render_checkbox_row( 'show_creator_link', __( 'Link creator website in About panel', 'michiryu-sekki' ), $options['show_creator_link'] ); ?>
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
			'text'                => __( 'Text (text only)', 'michiryu-sekki' ),
			'small'               => __( 'Small (small format with minimal image)', 'michiryu-sekki' ),
			'standard_vertical'   => __( 'Standard vertical (default)', 'michiryu-sekki' ),
			'standard_horizontal' => __( 'Standard horizontal', 'michiryu-sekki' ),
			'banner_tall'         => __( 'Banner tall (current full image layout)', 'michiryu-sekki' ),
			'banner_narrow'       => __( 'Banner narrow (image, details, story)', 'michiryu-sekki' ),
		);
	}

}
