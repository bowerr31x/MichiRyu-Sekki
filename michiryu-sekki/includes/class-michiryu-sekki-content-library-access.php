<?php
/**
 * Content library access configuration.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralizes basic content access decisions.
 */
class MichiRyu_Sekki_Content_Library_Access {
	const BASIC_LIBRARY = 'michiryu-basic';
	const BASIC_CONTENT_URL = 'https://michiryu.com/michiryu-content-api/index.php?route=manifest&library=basic';
	const BASIC_CONTENT_TOKEN = 'michiryu-basic-content';

	/**
	 * Return the configured Basic MichiRyu content library.
	 *
	 * @param array<string,mixed> $options Saved plugin options.
	 * @return array<string,string>
	 */
	public static function get_basic_library( $options = array() ) {
		$url = defined( 'MICHIRYU_SEKKI_BASIC_CONTENT_URL' ) ? MICHIRYU_SEKKI_BASIC_CONTENT_URL : self::BASIC_CONTENT_URL;
		$token = defined( 'MICHIRYU_SEKKI_BASIC_CONTENT_TOKEN' ) ? MICHIRYU_SEKKI_BASIC_CONTENT_TOKEN : self::BASIC_CONTENT_TOKEN;

		if ( '' === trim( (string) $url ) && ! empty( $options['content_library_url'] ) ) {
			$url = $options['content_library_url'];
		}

		if ( '' === trim( (string) $token ) && ! empty( $options['content_access_token'] ) ) {
			$token = $options['content_access_token'];
		}

		/**
		 * Filters the basic MichiRyu content library URL.
		 *
		 * @param string $url Default content URL.
		 */
		$url = apply_filters( 'michiryu_sekki_basic_content_url', $url );

		/**
		 * Filters the basic MichiRyu content access token.
		 *
		 * @param string $token Default content token.
		 */
		$token = apply_filters( 'michiryu_sekki_basic_content_token', $token );

		return array(
			'library' => self::BASIC_LIBRARY,
			'url'     => rtrim( esc_url_raw( $url ), '/' ),
			'token'   => self::sanitize_access_token( $token ),
		);
	}

	/**
	 * Sanitize an access token for local option storage or import use.
	 *
	 * @param mixed $token Raw token.
	 * @return string
	 */
	public static function sanitize_access_token( $token ) {
		return sanitize_text_field( (string) $token );
	}
}
