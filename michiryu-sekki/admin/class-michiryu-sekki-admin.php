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
				<p><?php esc_html_e( 'Use', 'michiryu-sekki' ); ?> <code>[michiryu_sekki]</code> <?php esc_html_e( 'for the main seasonal display. Story, image, character, and map enhancements appear when a content provider supplies them.', 'michiryu-sekki' ); ?></p>
				<p><?php esc_html_e( 'Optional provider-backed sections or pages:', 'michiryu-sekki' ); ?> <code>[michiryu_story]</code> <code>[michiryu_sekki_map]</code></p>
			</div>

			<?php $this->render_provider_status(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'michiryu_sekki_settings' ); ?>

				<h2><?php esc_html_e( 'MichiRyu Content Library', 'michiryu-sekki' ); ?></h2>
				<p><?php esc_html_e( 'These settings prepare the future admin-approved content import workflow. The plugin will not download MichiRyu content until an import feature is added and an administrator explicitly starts it.', 'michiryu-sekki' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Import consent', 'michiryu-sekki' ); ?></th>
						<td>
							<?php $this->render_checkbox_field( 'content_import_ack_copyright', __( 'I understand this will download MichiRyu copyrighted content to this site.', 'michiryu-sekki' ), $options['content_import_ack_copyright'] ); ?>
							<?php $this->render_checkbox_field( 'content_import_accept_license', __( 'I agree to use the content under the MichiRyu Content License.', 'michiryu-sekki' ), $options['content_import_accept_license'] ); ?>
							<?php $this->render_checkbox_field( 'content_import_ack_privacy', __( 'I understand no personal visitor data is transmitted.', 'michiryu-sekki' ), $options['content_import_ack_privacy'] ); ?>
							<p class="description"><?php esc_html_e( 'All acknowledgements will be required before a future MichiRyu content import can run.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="michiryu-sekki-content-update-mode"><?php esc_html_e( 'Content updates', 'michiryu-sekki' ); ?></label></th>
						<td>
							<?php $this->render_select( 'content_update_mode', $options['content_update_mode'], $this->get_content_update_mode_options(), 'michiryu-sekki-content-update-mode' ); ?>
							<p class="description"><?php esc_html_e( 'Manual updates only is the default. Automatic checks will be opt-in when the import feature is implemented.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Import action', 'michiryu-sekki' ); ?></th>
						<td>
							<button type="button" class="button" disabled="disabled"><?php esc_html_e( 'Connect and Import Content', 'michiryu-sekki' ); ?></button>
							<p class="description"><?php esc_html_e( 'Import is not enabled yet. The plugin continues to use basic local content or the configured provider.', 'michiryu-sekki' ); ?></p>
						</td>
					</tr>
				</table>

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
	 * Render content provider diagnostics.
	 */
	private function render_provider_status() {
		$status = $this->get_provider_status();
		?>
		<h2><?php esc_html_e( 'Content Provider Status', 'michiryu-sekki' ); ?></h2>
		<div class="notice <?php echo $status['is_local'] ? 'notice-success' : 'notice-warning'; ?> inline">
			<p>
				<strong><?php echo esc_html( $status['label'] ); ?></strong>
				<?php echo esc_html( $status['message'] ); ?>
			</p>
		</div>
		<table class="widefat striped" role="table">
			<tbody>
				<?php foreach ( $status['rows'] as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td><?php echo esc_html( $row['value'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'The plugin package contains GPL software only. Proprietary stories, artwork, maps, icons, PDFs, educational materials, and Yuki no Sato content must come from a separate provider.', 'michiryu-sekki' ); ?></p>
		<?php
	}

	/**
	 * Return content provider diagnostics.
	 *
	 * @return array<string,mixed>
	 */
	private function get_provider_status() {
		$provider = MichiRyu_Sekki_Content::get_provider();
		$content = MichiRyu_Sekki_Content::get_content();
		$provider_key = MichiRyu_Sekki_Content::get_provider_key();
		$is_local = MichiRyu_Sekki_Content::is_local_provider();
		$file_status = $this->get_file_provider_status( $provider_key );
		$rows = array(
			array(
				'label' => __( 'Provider key', 'michiryu-sekki' ),
				'value' => $provider_key,
			),
			array(
				'label' => __( 'Provider class', 'michiryu-sekki' ),
				'value' => get_class( $provider ),
			),
		);

		if ( 'file' === $provider_key ) {
			$rows[] = array(
				'label' => __( 'File content path', 'michiryu-sekki' ),
				'value' => $file_status['path_label'],
			);
			$rows[] = array(
				'label' => __( 'File content path status', 'michiryu-sekki' ),
				'value' => $file_status['status_label'],
			);
			$rows[] = array(
				'label' => __( 'File content URL', 'michiryu-sekki' ),
				'value' => $file_status['url_label'],
			);
		}

		$rows = array_merge(
			$rows,
			array(
				array(
					'label' => __( 'Sekki records', 'michiryu-sekki' ),
					'value' => (string) count( $content['sekki'] ?? array() ),
				),
				array(
					'label' => __( 'Ko records', 'michiryu-sekki' ),
					'value' => (string) count( $content['ko'] ?? array() ),
				),
				array(
					'label' => __( 'Story records', 'michiryu-sekki' ),
					'value' => (string) count( $content['stories'] ?? array() ),
				),
				array(
					'label' => __( 'Character records', 'michiryu-sekki' ),
					'value' => (string) count( $content['characters'] ?? array() ),
				),
				array(
					'label' => __( 'Map locations', 'michiryu-sekki' ),
					'value' => (string) count( $content['map_locations'] ?? array() ),
				),
				array(
					'label' => __( 'Map image', 'michiryu-sekki' ),
					'value' => $this->provider_has_image( 'map' ) ? __( 'Available', 'michiryu-sekki' ) : __( 'Not supplied', 'michiryu-sekki' ),
				),
				array(
					'label' => __( 'Signature image', 'michiryu-sekki' ),
					'value' => $this->provider_has_image( 'signature' ) ? __( 'Available', 'michiryu-sekki' ) : __( 'Not supplied', 'michiryu-sekki' ),
				),
			)
		);

		return array(
			'is_local' => $is_local,
			'label'    => $this->get_provider_status_label( $provider_key, $is_local, $file_status ),
			'message'  => $this->get_provider_status_message( $provider_key, $is_local, $file_status ),
			'rows'     => $rows,
		);
	}

	/**
	 * Return diagnostics for the file provider.
	 *
	 * @param string $provider_key Provider key.
	 * @return array<string,mixed>
	 */
	private function get_file_provider_status( $provider_key ) {
		if ( 'file' !== $provider_key ) {
			return array(
				'is_requested' => false,
				'is_valid'     => false,
				'path_label'   => __( 'Not requested', 'michiryu-sekki' ),
				'status_label' => __( 'Not requested', 'michiryu-sekki' ),
				'url_label'    => __( 'Not configured', 'michiryu-sekki' ),
			);
		}

		$content_path = MichiRyu_Sekki_File_Content_Provider::get_configured_content_path();
		$content_url = MichiRyu_Sekki_File_Content_Provider::get_configured_content_url();
		$is_valid = MichiRyu_Sekki_File_Content_Provider::is_valid_content_path( $content_path );

		return array(
			'is_requested' => true,
			'is_valid'     => $is_valid,
			'path_label'   => '' === trim( (string) $content_path ) ? __( 'Not configured', 'michiryu-sekki' ) : (string) $content_path,
			'status_label' => $is_valid ? __( 'Valid external directory', 'michiryu-sekki' ) : __( 'Missing, unreadable, or inside the plugin folder', 'michiryu-sekki' ),
			'url_label'    => '' === trim( (string) $content_url ) ? __( 'Not configured', 'michiryu-sekki' ) : (string) $content_url,
		);
	}

	/**
	 * Return provider status label.
	 *
	 * @param string              $provider_key Provider key.
	 * @param bool                $is_local Whether the active provider is local.
	 * @param array<string,mixed> $file_status File provider status.
	 * @return string
	 */
	private function get_provider_status_label( $provider_key, $is_local, $file_status ) {
		if ( 'file' === $provider_key && $is_local ) {
			return __( 'File provider requested; local fallback active.', 'michiryu-sekki' );
		}

		return $is_local ? __( 'GPL-safe local provider active.', 'michiryu-sekki' ) : __( 'External content provider active.', 'michiryu-sekki' );
	}

	/**
	 * Return provider status message.
	 *
	 * @param string              $provider_key Provider key.
	 * @param bool                $is_local Whether the active provider is local.
	 * @param array<string,mixed> $file_status File provider status.
	 * @return string
	 */
	private function get_provider_status_message( $provider_key, $is_local, $file_status ) {
		if ( 'file' === $provider_key && empty( $file_status['is_valid'] ) ) {
			return __( 'Configure MICHIRYU_SEKKI_CONTENT_PATH with a readable external directory to activate file content.', 'michiryu-sekki' );
		}

		return $is_local
			? __( 'The plugin is using factual calendar data without proprietary content.', 'michiryu-sekki' )
			: __( 'Confirm that any supplied content is licensed separately from the GPL plugin.', 'michiryu-sekki' );
	}

	/**
	 * Return whether the active provider supplies an image id.
	 *
	 * @param string $id Image id.
	 * @return bool
	 */
	private function provider_has_image( $id ) {
		try {
			$image = MichiRyu_Sekki_Content::get_provider()->get_image( $id );
		} catch ( Throwable $error ) {
			return false;
		}

		if ( is_string( $image ) ) {
			return '' !== trim( $image );
		}

		return is_array( $image ) && ! empty( $image['url'] );
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
	 * Render checkbox field.
	 *
	 * @param string $key Option key.
	 * @param string $label Label.
	 * @param bool   $checked Checked state.
	 */
	private function render_checkbox_field( $key, $label, $checked ) {
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?> />
			<?php echo esc_html( $label ); ?>
		</label><br />
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

	/**
	 * Content update mode choices.
	 *
	 * @return array<string,string>
	 */
	private function get_content_update_mode_options() {
		return array(
			'manual'  => __( 'Manual updates only', 'michiryu-sekki' ),
			'monthly' => __( 'Check monthly for updates', 'michiryu-sekki' ),
			'sekki'   => __( 'Check every Sekki', 'michiryu-sekki' ),
		);
	}

}
