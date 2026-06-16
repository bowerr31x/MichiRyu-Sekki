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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_michiryu_sekki_import_content', array( $this, 'handle_content_import' ) );
		add_action( 'admin_post_michiryu_sekki_remove_imported_content', array( $this, 'handle_remove_imported_content' ) );
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
	 * Enqueue settings page assets.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_michiryu' !== $hook_suffix ) {
			return;
		}

		$style_path    = MICHIRYU_SEKKI_PATH . 'assets/css/michiryu-sekki-admin.css';
		$style_version = file_exists( $style_path ) ? MICHIRYU_SEKKI_VERSION . '.' . filemtime( $style_path ) : MICHIRYU_SEKKI_VERSION;

		wp_enqueue_style(
			'michiryu-sekki-admin',
			MICHIRYU_SEKKI_URL . 'assets/css/michiryu-sekki-admin.css',
			array(),
			$style_version
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
		<div class="wrap michiryu-sekki-admin">
			<h1><?php esc_html_e( 'MichiRyu-Sekki-Calendar', 'michiryu-sekki' ); ?></h1>
			<p class="michiryu-sekki-admin__lede"><?php esc_html_e( 'Set up the seasonal journey, imported content, and map experience.', 'michiryu-sekki' ); ?></p>

			<div class="notice notice-info inline michiryu-sekki-admin__notice">
				<p><strong><?php esc_html_e( 'Recommended setup', 'michiryu-sekki' ); ?></strong></p>
				<p><?php esc_html_e( 'Use', 'michiryu-sekki' ); ?> <code>[michiryu_sekki]</code> <?php esc_html_e( 'for the main seasonal display. Story, image, character, and map enhancements appear when a content provider supplies them.', 'michiryu-sekki' ); ?></p>
				<p><?php esc_html_e( 'Optional provider-backed sections or pages:', 'michiryu-sekki' ); ?> <code>[michiryu_story]</code> <code>[michiryu_sekki_map]</code></p>
			</div>

			<?php $this->render_provider_status(); ?>
			<?php $this->render_import_notice(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'michiryu_sekki_settings' ); ?>

				<section class="michiryu-sekki-admin__section">
					<h2><?php esc_html_e( 'MichiRyu Content Library', 'michiryu-sekki' ); ?></h2>
					<p><?php esc_html_e( 'Import MichiRyu stories and images into this WordPress site. After import, visitors use the local WordPress copy.', 'michiryu-sekki' ); ?></p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Import consent', 'michiryu-sekki' ); ?></th>
							<td>
								<?php $this->render_checkbox_field( 'content_import_ack_copyright', __( 'I understand this will download MichiRyu copyrighted content to this site.', 'michiryu-sekki' ), $options['content_import_ack_copyright'] ); ?>
								<?php $this->render_checkbox_field( 'content_import_accept_license', __( 'I agree to use the content under the MichiRyu Content License.', 'michiryu-sekki' ), $options['content_import_accept_license'] ); ?>
								<?php $this->render_checkbox_field( 'content_import_ack_privacy', __( 'I understand no personal visitor data is transmitted.', 'michiryu-sekki' ), $options['content_import_ack_privacy'] ); ?>
								<p class="description"><?php esc_html_e( 'Save these acknowledgements before importing content.', 'michiryu-sekki' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="michiryu-sekki-content-update-mode"><?php esc_html_e( 'Content updates', 'michiryu-sekki' ); ?></label></th>
							<td>
								<?php $this->render_select( 'content_update_mode', $options['content_update_mode'], $this->get_content_update_mode_options(), 'michiryu-sekki-content-update-mode' ); ?>
								<p class="description"><?php esc_html_e( 'Manual updates only is the default. Automatic checks will be opt-in later.', 'michiryu-sekki' ); ?></p>
							</td>
						</tr>
					</table>

					<details class="michiryu-sekki-admin__details">
						<summary><?php esc_html_e( 'Advanced content settings', 'michiryu-sekki' ); ?></summary>
						<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="michiryu-sekki-content-library-url"><?php esc_html_e( 'Custom remote content URL', 'michiryu-sekki' ); ?></label></th>
							<td>
								<input id="michiryu-sekki-content-library-url" type="url" class="large-text" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[content_library_url]" value="<?php echo esc_attr( $options['content_library_url'] ); ?>" placeholder="https://example.com/michiryu-content" />
								<p class="description"><?php esc_html_e( 'For testing, support, or self-hosted content libraries. Use a manifest endpoint or a folder that exposes featured-content.json, images.json, and referenced image files.', 'michiryu-sekki' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="michiryu-sekki-content-access-token"><?php esc_html_e( 'Custom content access token', 'michiryu-sekki' ); ?></label></th>
							<td>
								<input id="michiryu-sekki-content-access-token" type="password" class="regular-text" autocomplete="off" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[content_access_token]" value="<?php echo esc_attr( $options['content_access_token'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Optional. Custom import requests send this as an Authorization bearer token. Future premium libraries will use license-specific tokens here.', 'michiryu-sekki' ); ?></p>
							</td>
						</tr>
						</table>
						<h3><?php esc_html_e( 'Future premium access', 'michiryu-sekki' ); ?></h3>
						<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="michiryu-sekki-premium-license-token"><?php esc_html_e( 'Premium license token', 'michiryu-sekki' ); ?></label></th>
							<td>
								<input id="michiryu-sekki-premium-license-token" type="password" class="regular-text" autocomplete="off" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[premium_license_token]" value="" placeholder="<?php echo ! empty( $options['premium_license_token'] ) ? esc_attr__( 'Token saved', 'michiryu-sekki' ) : ''; ?>" />
								<p class="description"><?php esc_html_e( 'Reserved for future premium MichiRyu libraries. This token is saved locally but is not used by the current basic import.', 'michiryu-sekki' ); ?></p>
								<?php if ( ! empty( $options['premium_license_token'] ) ) : ?>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( MichiRyu_Sekki::OPTION_NAME ); ?>[premium_license_token_clear]" value="1" />
										<?php esc_html_e( 'Clear saved premium license token', 'michiryu-sekki' ); ?>
									</label>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Premium status', 'michiryu-sekki' ); ?></th>
							<td>
								<p><?php echo esc_html( $this->get_premium_license_status_label( $options ) ); ?></p>
								<p class="description"><?php esc_html_e( 'Premium validation will be added later through a server-side entitlement check before premium manifests are imported.', 'michiryu-sekki' ); ?></p>
							</td>
						</tr>
						</table>
					</details>
				</section>

				<section class="michiryu-sekki-admin__section">
					<h2><?php esc_html_e( 'Display Settings', 'michiryu-sekki' ); ?></h2>
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
				</section>

				<?php submit_button(); ?>
			</form>
			<section class="michiryu-sekki-admin__section">
				<h2><?php esc_html_e( 'Content Actions', 'michiryu-sekki' ); ?></h2>
				<p><?php esc_html_e( 'Save settings first if you changed consent, update mode, or advanced content settings.', 'michiryu-sekki' ); ?></p>
				<?php $this->render_import_summary(); ?>
				<?php $this->render_import_forms( $options ); ?>
				<?php $this->render_remove_imported_content_form(); ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Handle remote content import.
	 */
	public function handle_content_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import MichiRyu content.', 'michiryu-sekki' ) );
		}

		check_admin_referer( 'michiryu_sekki_import_content' );

		$options = $this->plugin->get_options();
		$has_consent = ! empty( $options['content_import_ack_copyright'] )
			&& ! empty( $options['content_import_accept_license'] )
			&& ! empty( $options['content_import_ack_privacy'] );

		if ( ! $has_consent ) {
			$result = array(
				'success' => false,
				'message' => __( 'All import acknowledgements must be saved before importing content.', 'michiryu-sekki' ),
			);
		} else {
			$importer = new MichiRyu_Sekki_Content_Importer();
			$import_type = sanitize_key( $_POST['michiryu_import_type'] ?? 'basic' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'custom' === $import_type ) {
				$result = $importer->import( $options['content_library_url'] ?? '', $options['content_access_token'] ?? '', 'custom' );
			} else {
				$result = $importer->import(
					$this->get_basic_content_url( $options ),
					$this->get_basic_content_token( $options ),
					'basic'
				);
			}
		}

		set_transient( $this->get_import_notice_key(), $result, MINUTE_IN_SECONDS );

		wp_safe_redirect( admin_url( 'admin.php?page=michiryu&michiryu_import=1' ) );
		exit;
	}

	/**
	 * Handle imported content removal.
	 */
	public function handle_remove_imported_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to remove MichiRyu content.', 'michiryu-sekki' ) );
		}

		check_admin_referer( 'michiryu_sekki_remove_imported_content' );

		if ( empty( $_POST['michiryu_confirm_remove_imported_content'] ) ) {
			$result = array(
				'success' => false,
				'message' => __( 'Confirm that you want to remove imported content before continuing.', 'michiryu-sekki' ),
			);
		} else {
			$importer = new MichiRyu_Sekki_Content_Importer();
			$result = $importer->remove_imported_content();
		}

		set_transient( $this->get_import_notice_key(), $result, MINUTE_IN_SECONDS );

		wp_safe_redirect( admin_url( 'admin.php?page=michiryu&michiryu_import=1' ) );
		exit;
	}

	/**
	 * Render content provider diagnostics.
	 */
	private function render_provider_status() {
		$status = $this->get_provider_status();
		?>
		<section class="michiryu-sekki-admin__section">
			<h2><?php esc_html_e( 'Content Provider Status', 'michiryu-sekki' ); ?></h2>
			<div class="notice <?php echo esc_attr( $status['notice_class'] ); ?> inline michiryu-sekki-admin__notice">
				<p>
					<strong><?php echo esc_html( $status['label'] ); ?></strong>
					<?php echo esc_html( $status['message'] ); ?>
				</p>
			</div>
			<table class="widefat striped michiryu-sekki-admin__status-table" role="table">
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
		</section>
		<?php
	}

	/**
	 * Render import result notice.
	 */
	private function render_import_notice() {
		if ( empty( $_GET['michiryu_import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$notice = get_transient( $this->get_import_notice_key() );
		delete_transient( $this->get_import_notice_key() );

		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		$class = ! empty( $notice['success'] ) ? 'notice-success' : 'notice-error';
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> inline">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render last import summary.
	 */
	private function render_import_summary() {
		$import_status = MichiRyu_Sekki_Content_Importer::get_status();
		$local_active = MichiRyu_Sekki_Imported_Content_Provider::has_imported_content();

		if ( empty( $import_status ) ) {
			?>
			<div class="michiryu-sekki-admin__import-summary">
				<h3><?php esc_html_e( 'Last content import', 'michiryu-sekki' ); ?></h3>
				<p><?php esc_html_e( 'No MichiRyu content has been imported yet.', 'michiryu-sekki' ); ?></p>
				<p class="description"><?php esc_html_e( 'The plugin can still render the basic local Sekki and Ko calendar without imported content.', 'michiryu-sekki' ); ?></p>
			</div>
			<?php
			return;
		}

		$rows = array(
			array(
				'label' => __( 'Source', 'michiryu-sekki' ),
				'value' => $this->get_import_source_label( $import_status ),
			),
			array(
				'label' => __( 'Imported', 'michiryu-sekki' ),
				'value' => $this->format_import_date( $import_status['imported_at'] ?? '' ),
			),
			array(
				'label' => __( 'Stories', 'michiryu-sekki' ),
				'value' => isset( $import_status['stories'] ) ? number_format_i18n( (int) $import_status['stories'] ) : __( 'Unknown', 'michiryu-sekki' ),
			),
			array(
				'label' => __( 'Characters', 'michiryu-sekki' ),
				'value' => isset( $import_status['characters'] ) ? number_format_i18n( (int) $import_status['characters'] ) : __( 'Unknown', 'michiryu-sekki' ),
			),
			array(
				'label' => __( 'Image references', 'michiryu-sekki' ),
				'value' => isset( $import_status['images'] ) ? number_format_i18n( (int) $import_status['images'] ) : __( 'Unknown', 'michiryu-sekki' ),
			),
			array(
				'label' => __( 'Images copied', 'michiryu-sekki' ),
				'value' => isset( $import_status['images_copied'] ) ? number_format_i18n( (int) $import_status['images_copied'] ) : __( 'Unknown', 'michiryu-sekki' ),
			),
			array(
				'label' => __( 'Token used', 'michiryu-sekki' ),
				'value' => ! empty( $import_status['uses_token'] ) ? __( 'Yes', 'michiryu-sekki' ) : __( 'No', 'michiryu-sekki' ),
			),
			array(
				'label' => __( 'Local copy', 'michiryu-sekki' ),
				'value' => $local_active ? __( 'Active', 'michiryu-sekki' ) : __( 'Not found', 'michiryu-sekki' ),
			),
		);
		?>
		<div class="michiryu-sekki-admin__import-summary">
			<h3><?php esc_html_e( 'Last content import', 'michiryu-sekki' ); ?></h3>
			<dl>
				<?php foreach ( $rows as $row ) : ?>
					<div>
						<dt><?php echo esc_html( $row['label'] ); ?></dt>
						<dd><?php echo esc_html( $row['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php
	}

	/**
	 * Render import action forms.
	 *
	 * @param array<string,mixed> $options Saved options.
	 */
	private function render_import_forms( $options ) {
		$has_consent = ! empty( $options['content_import_ack_copyright'] )
			&& ! empty( $options['content_import_accept_license'] )
			&& ! empty( $options['content_import_ack_privacy'] );
		$has_basic_url = '' !== $this->get_basic_content_url( $options );
		$has_custom_url = ! empty( $options['content_library_url'] );
		$basic_disabled = ! $has_consent || ! $has_basic_url;
		$custom_disabled = ! $has_consent || ! $has_custom_url;
		?>
		<div class="michiryu-sekki-admin__actions">
			<form class="michiryu-sekki-admin__action" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-michiryu-content-import-form>
				<h3><?php esc_html_e( 'Basic MichiRyu content', 'michiryu-sekki' ); ?></h3>
				<p><?php esc_html_e( 'Imports the protected basic MichiRyu library, including stories, characters, and image references.', 'michiryu-sekki' ); ?></p>
				<input type="hidden" name="action" value="michiryu_sekki_import_content" />
				<input type="hidden" name="michiryu_import_type" value="basic" />
				<?php wp_nonce_field( 'michiryu_sekki_import_content' ); ?>
				<?php submit_button( __( 'Import Basic MichiRyu Content', 'michiryu-sekki' ), 'primary', 'submit', false, $basic_disabled ? array( 'disabled' => 'disabled' ) : array( 'data-importing-label' => esc_attr__( 'Importing content...', 'michiryu-sekki' ) ) ); ?>
				<span class="spinner" data-michiryu-content-import-spinner></span>
				<p class="description" data-michiryu-content-import-message hidden><?php esc_html_e( 'Importing content. This may take up to a minute while images are copied into WordPress. Please keep this tab open.', 'michiryu-sekki' ); ?></p>
				<?php if ( $basic_disabled ) : ?>
					<p class="description"><?php esc_html_e( 'Check all acknowledgements and save settings before importing.', 'michiryu-sekki' ); ?></p>
				<?php endif; ?>
			</form>
			<form class="michiryu-sekki-admin__action" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-michiryu-content-import-form>
				<h3><?php esc_html_e( 'Custom content library', 'michiryu-sekki' ); ?></h3>
				<p><?php esc_html_e( 'Imports from the custom URL and access token saved in Advanced content settings.', 'michiryu-sekki' ); ?></p>
				<input type="hidden" name="action" value="michiryu_sekki_import_content" />
				<input type="hidden" name="michiryu_import_type" value="custom" />
				<?php wp_nonce_field( 'michiryu_sekki_import_content' ); ?>
				<?php submit_button( __( 'Import Custom Content Library', 'michiryu-sekki' ), 'secondary', 'submit', false, $custom_disabled ? array( 'disabled' => 'disabled' ) : array( 'data-importing-label' => esc_attr__( 'Importing custom content...', 'michiryu-sekki' ) ) ); ?>
				<span class="spinner" data-michiryu-content-import-spinner></span>
				<p class="description" data-michiryu-content-import-message hidden><?php esc_html_e( 'Importing custom content. This may take up to a minute while images are copied into WordPress. Please keep this tab open.', 'michiryu-sekki' ); ?></p>
				<?php if ( $custom_disabled ) : ?>
					<p class="description"><?php esc_html_e( 'Enter a custom remote content URL, check all acknowledgements, and save settings before importing custom content.', 'michiryu-sekki' ); ?></p>
				<?php endif; ?>
			</form>
		</div>
		<script>
			(function () {
				document.querySelectorAll( '[data-michiryu-content-import-form]' ).forEach( function ( form ) {
					form.addEventListener( 'submit', function () {
						var submit = form.querySelector( '[type="submit"]' );
						var spinner = form.querySelector( '[data-michiryu-content-import-spinner]' );
						var message = form.querySelector( '[data-michiryu-content-import-message]' );

						if ( submit ) {
							submit.value = submit.getAttribute( 'data-importing-label' ) || submit.value;
							submit.disabled = true;
						}

						if ( spinner ) {
							spinner.classList.add( 'is-active' );
						}

						if ( message ) {
							message.hidden = false;
						}
					} );
				} );
			}());
		</script>
		<?php
	}

	/**
	 * Render imported content removal form.
	 */
	private function render_remove_imported_content_form() {
		if ( empty( MichiRyu_Sekki_Content_Importer::get_status() ) && ! MichiRyu_Sekki_Imported_Content_Provider::has_imported_content() ) {
			return;
		}
		?>
		<div class="michiryu-sekki-admin__danger">
			<h3><?php esc_html_e( 'Remove imported content', 'michiryu-sekki' ); ?></h3>
			<p><?php esc_html_e( 'Deletes the local imported MichiRyu copy and returns the plugin to the basic local calendar until content is imported again.', 'michiryu-sekki' ); ?></p>
		</div>
		<form class="michiryu-sekki-admin__remove-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="michiryu_sekki_remove_imported_content" />
			<?php wp_nonce_field( 'michiryu_sekki_remove_imported_content' ); ?>
			<label>
				<input type="checkbox" name="michiryu_confirm_remove_imported_content" value="1" />
				<?php esc_html_e( 'I understand this will delete the local imported MichiRyu content copy from this site.', 'michiryu-sekki' ); ?>
			</label>
			<?php submit_button( __( 'Remove Imported Content', 'michiryu-sekki' ), 'delete', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Return the default/basic content library URL.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_basic_content_url( $options ) {
		$default_content_url = 'https://www.bowerr31x.com/michiryu-content-api/index.php?route=manifest';
		$content_url = defined( 'MICHIRYU_SEKKI_BASIC_CONTENT_URL' ) ? MICHIRYU_SEKKI_BASIC_CONTENT_URL : $default_content_url;
		$content_url = '' !== trim( (string) $content_url ) ? $content_url : ( $options['content_library_url'] ?? '' );

		/**
		 * Filters the basic MichiRyu content library URL.
		 *
		 * @param string $content_url Default content URL.
		 */
		return rtrim( esc_url_raw( apply_filters( 'michiryu_sekki_basic_content_url', $content_url ) ), '/' );
	}

	/**
	 * Return the default/basic content access token.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_basic_content_token( $options ) {
		$default_content_token = 'michiryu-basic-test';
		$content_token         = defined( 'MICHIRYU_SEKKI_BASIC_CONTENT_TOKEN' ) ? MICHIRYU_SEKKI_BASIC_CONTENT_TOKEN : $default_content_token;
		$content_token = '' !== trim( (string) $content_token ) ? $content_token : ( $options['content_access_token'] ?? '' );

		/**
		 * Filters the basic MichiRyu content access token.
		 *
		 * @param string $content_token Default content token.
		 */
		return sanitize_text_field( apply_filters( 'michiryu_sekki_basic_content_token', $content_token ) );
	}

	/**
	 * Return the saved premium license status label.
	 *
	 * @param array<string,mixed> $options Saved options.
	 * @return string
	 */
	private function get_premium_license_status_label( $options ) {
		if ( empty( $options['premium_license_token'] ) ) {
			return __( 'No premium license token saved.', 'michiryu-sekki' );
		}

		return __( 'Premium license token saved. Validation is not active yet.', 'michiryu-sekki' );
	}

	/**
	 * Return a readable import source label.
	 *
	 * @param array<string,mixed> $import_status Import status.
	 * @return string
	 */
	private function get_import_source_label( $import_status ) {
		if ( 'basic' === ( $import_status['import_context'] ?? '' ) ) {
			return __( 'Basic MichiRyu', 'michiryu-sekki' );
		}

		if ( 'custom' === ( $import_status['import_context'] ?? '' ) ) {
			return __( 'Custom content library', 'michiryu-sekki' );
		}

		if ( 'manifest' === ( $import_status['source_type'] ?? '' ) ) {
			return __( 'Manifest endpoint', 'michiryu-sekki' );
		}

		return __( 'Remote content folder', 'michiryu-sekki' );
	}

	/**
	 * Format an import date for display.
	 *
	 * @param string $date_string Stored date string.
	 * @return string
	 */
	private function format_import_date( $date_string ) {
		if ( '' === (string) $date_string ) {
			return __( 'Unknown', 'michiryu-sekki' );
		}

		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		return mysql2date( $format, $date_string );
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
		$is_imported = $provider instanceof MichiRyu_Sekki_Imported_Content_Provider;
		$file_status = $this->get_file_provider_status( $provider_key );
		$rows = array(
			array(
				'label' => __( 'Active source', 'michiryu-sekki' ),
				'value' => $this->get_active_source_label( $provider_key, $is_local, $is_imported ),
			),
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

		$import_status = MichiRyu_Sekki_Content_Importer::get_status();
		if ( ! empty( $import_status ) ) {
			$rows[] = array(
				'label' => __( 'Imported content URL', 'michiryu-sekki' ),
				'value' => $import_status['remote_url'] ?? __( 'Unknown', 'michiryu-sekki' ),
			);
			$rows[] = array(
				'label' => __( 'Last content import', 'michiryu-sekki' ),
				'value' => $import_status['imported_at'] ?? __( 'Unknown', 'michiryu-sekki' ),
			);
			$rows[] = array(
				'label' => __( 'Imported storage', 'michiryu-sekki' ),
				'value' => MichiRyu_Sekki_Imported_Content_Provider::get_content_path(),
			);
			$rows[] = array(
				'label' => __( 'Imported image references', 'michiryu-sekki' ),
				'value' => isset( $import_status['images'] ) ? (string) $import_status['images'] : __( 'Unknown', 'michiryu-sekki' ),
			);
			$rows[] = array(
				'label' => __( 'Token used for last import', 'michiryu-sekki' ),
				'value' => ! empty( $import_status['uses_token'] ) ? __( 'Yes', 'michiryu-sekki' ) : __( 'No', 'michiryu-sekki' ),
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
			'notice_class' => $this->get_provider_notice_class( $is_local, $is_imported ),
			'label'    => $this->get_provider_status_label( $provider_key, $is_local, $is_imported, $file_status ),
			'message'  => $this->get_provider_status_message( $provider_key, $is_local, $is_imported, $file_status ),
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
	 * @param bool                $is_imported Whether the imported provider is active.
	 * @param array<string,mixed> $file_status File provider status.
	 * @return string
	 */
	private function get_provider_status_label( $provider_key, $is_local, $is_imported, $file_status ) {
		if ( 'file' === $provider_key && $is_local ) {
			return __( 'File provider requested; local fallback active.', 'michiryu-sekki' );
		}

		if ( $is_imported ) {
			return __( 'Imported content provider active.', 'michiryu-sekki' );
		}

		return $is_local ? __( 'GPL-safe local provider active.', 'michiryu-sekki' ) : __( 'External content provider active.', 'michiryu-sekki' );
	}

	/**
	 * Return provider status message.
	 *
	 * @param string              $provider_key Provider key.
	 * @param bool                $is_local Whether the active provider is local.
	 * @param bool                $is_imported Whether the imported provider is active.
	 * @param array<string,mixed> $file_status File provider status.
	 * @return string
	 */
	private function get_provider_status_message( $provider_key, $is_local, $is_imported, $file_status ) {
		if ( 'file' === $provider_key && empty( $file_status['is_valid'] ) ) {
			return __( 'Configure MICHIRYU_SEKKI_CONTENT_PATH with a readable external directory to activate file content.', 'michiryu-sekki' );
		}

		if ( $is_imported ) {
			return __( 'The plugin is using the local WordPress copy created by the last content import.', 'michiryu-sekki' );
		}

		return $is_local
			? __( 'The plugin is using factual calendar data without proprietary content.', 'michiryu-sekki' )
			: __( 'Confirm that any supplied content is licensed separately from the GPL plugin.', 'michiryu-sekki' );
	}

	/**
	 * Return notice class for provider status.
	 *
	 * @param bool $is_local Whether the local provider is active.
	 * @param bool $is_imported Whether the imported provider is active.
	 * @return string
	 */
	private function get_provider_notice_class( $is_local, $is_imported ) {
		return $is_local || $is_imported ? 'notice-success' : 'notice-warning';
	}

	/**
	 * Return a friendly active source label.
	 *
	 * @param string $provider_key Provider key.
	 * @param bool   $is_local Whether the local provider is active.
	 * @param bool   $is_imported Whether the imported provider is active.
	 * @return string
	 */
	private function get_active_source_label( $provider_key, $is_local, $is_imported ) {
		if ( $is_imported ) {
			return __( 'Imported WordPress copy', 'michiryu-sekki' );
		}

		if ( 'file' === $provider_key && ! $is_local ) {
			return __( 'External file provider', 'michiryu-sekki' );
		}

		return __( 'Basic local calendar', 'michiryu-sekki' );
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
	 * Return the current user's import notice transient key.
	 *
	 * @return string
	 */
	private function get_import_notice_key() {
		return 'michiryu_sekki_import_notice_' . get_current_user_id();
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
