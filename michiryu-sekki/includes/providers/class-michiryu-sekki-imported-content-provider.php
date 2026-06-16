<?php
/**
 * Imported content provider.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads administrator-imported content from the WordPress uploads directory.
 */
class MichiRyu_Sekki_Imported_Content_Provider extends MichiRyu_Sekki_File_Content_Provider {
	/**
	 * Return the imported content directory path.
	 *
	 * @return string
	 */
	public static function get_content_path() {
		$upload_dir = wp_upload_dir();
		$base_dir   = is_array( $upload_dir ) && ! empty( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : '';

		return rtrim( (string) $base_dir, '/\\' ) . '/michiryu-sekki-content';
	}

	/**
	 * Return the imported content directory URL.
	 *
	 * @return string
	 */
	public static function get_content_url() {
		$upload_dir = wp_upload_dir();
		$base_url   = is_array( $upload_dir ) && ! empty( $upload_dir['baseurl'] ) ? $upload_dir['baseurl'] : '';

		return rtrim( (string) $base_url, '/' ) . '/michiryu-sekki-content';
	}

	/**
	 * Return whether imported content exists and is readable.
	 *
	 * @return bool
	 */
	public static function has_imported_content() {
		$content_path = self::get_content_path();

		return is_readable( $content_path . '/featured-content.json' ) && is_readable( $content_path . '/images.json' );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( self::get_content_path(), self::get_content_url() );
	}
}
