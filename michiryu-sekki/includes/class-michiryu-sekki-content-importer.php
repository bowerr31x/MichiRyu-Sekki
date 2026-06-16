<?php
/**
 * Remote content importer.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports remote MichiRyu content into the local WordPress uploads directory.
 */
class MichiRyu_Sekki_Content_Importer {
	const OPTION_NAME = 'michiryu_sekki_content_import';

	/**
	 * Import a remote content library.
	 *
	 * @param string $remote_url Remote content library base URL.
	 * @return array<string,mixed>
	 */
	public function import( $remote_url ) {
		$remote_url = $this->normalize_remote_url( $remote_url );

		if ( '' === $remote_url ) {
			return $this->error( __( 'Enter a valid remote content URL.', 'michiryu-sekki' ) );
		}

		$content_path = MichiRyu_Sekki_Imported_Content_Provider::get_content_path();
		if ( ! wp_mkdir_p( $content_path ) ) {
			return $this->error( __( 'WordPress could not create the local content folder.', 'michiryu-sekki' ) );
		}

		$featured_content = $this->fetch_json( $remote_url . '/featured-content.json' );
		if ( is_wp_error( $featured_content ) ) {
			return $this->error( $featured_content->get_error_message() );
		}

		$images = $this->fetch_json( $remote_url . '/images.json' );
		if ( is_wp_error( $images ) ) {
			return $this->error( $images->get_error_message() );
		}

		if ( ! is_array( $featured_content ) || ! is_array( $featured_content['stories'] ?? null ) || ! is_array( $featured_content['characters'] ?? null ) ) {
			return $this->error( __( 'featured-content.json must include stories and characters.', 'michiryu-sekki' ) );
		}

		if ( ! is_array( $images ) ) {
			return $this->error( __( 'images.json must be a JSON object.', 'michiryu-sekki' ) );
		}

		$image_result = $this->import_images( $remote_url, $images, $content_path );
		if ( is_wp_error( $image_result ) ) {
			return $this->error( $image_result->get_error_message() );
		}

		$this->write_json( $content_path . '/featured-content.json', $featured_content );
		$this->write_json( $content_path . '/images.json', $images );

		$status = array(
			'remote_url'    => $remote_url,
			'imported_at'   => current_time( 'mysql' ),
			'stories'       => count( $featured_content['stories'] ),
			'characters'    => count( $featured_content['characters'] ),
			'images'        => count( $images ),
			'images_copied' => (int) $image_result['copied'],
		);

		update_option( self::OPTION_NAME, $status, false );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: story count, 2: character count, 3: image count. */
				__( 'Imported %1$d stories, %2$d characters, and %3$d image references.', 'michiryu-sekki' ),
				$status['stories'],
				$status['characters'],
				$status['images']
			),
			'status'  => $status,
		);
	}

	/**
	 * Return import status.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_status() {
		$status = get_option( self::OPTION_NAME, array() );

		return is_array( $status ) ? $status : array();
	}

	/**
	 * Normalize a remote content URL.
	 *
	 * @param string $remote_url Remote URL.
	 * @return string
	 */
	private function normalize_remote_url( $remote_url ) {
		$remote_url = esc_url_raw( trim( (string) $remote_url ) );

		if ( '' === $remote_url || ! preg_match( '#^https?://#i', $remote_url ) ) {
			return '';
		}

		return rtrim( $remote_url, '/' );
	}

	/**
	 * Fetch a JSON document.
	 *
	 * @param string $url JSON URL.
	 * @return array<string,mixed>|WP_Error
	 */
	private function fetch_json( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'michiryu_sekki_import_http', sprintf(
				/* translators: 1: URL, 2: HTTP status code. */
				__( 'Could not download %1$s. HTTP status: %2$d.', 'michiryu-sekki' ),
				$url,
				$code
			) );
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'michiryu_sekki_import_json', sprintf(
				/* translators: %s: URL. */
				__( 'The response from %s was not valid JSON.', 'michiryu-sekki' ),
				$url
			) );
		}

		return $decoded;
	}

	/**
	 * Import image files referenced by images.json.
	 *
	 * @param string              $remote_url Remote content base URL.
	 * @param array<string,mixed> $images Image mapping.
	 * @param string              $content_path Local content path.
	 * @return array<string,int>|WP_Error
	 */
	private function import_images( $remote_url, $images, $content_path ) {
		$copied = 0;

		foreach ( $images as $image ) {
			$relative_path = is_string( $image ) ? $image : '';
			if ( is_array( $image ) && ! empty( $image['path'] ) && is_string( $image['path'] ) ) {
				$relative_path = $image['path'];
			}

			$relative_path = $this->sanitize_relative_path( $relative_path );
			if ( '' === $relative_path ) {
				continue;
			}

			$source_url = $remote_url . '/' . $relative_path;
			if ( is_array( $image ) && ! empty( $image['url'] ) && is_string( $image['url'] ) && preg_match( '#^https?://#i', $image['url'] ) ) {
				$source_url = $image['url'];
			}

			$result = $this->download_file( $source_url, $content_path . '/' . $relative_path );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$copied++;
		}

		return array( 'copied' => $copied );
	}

	/**
	 * Download one remote file.
	 *
	 * @param string $source_url Source URL.
	 * @param string $destination Destination path.
	 * @return true|WP_Error
	 */
	private function download_file( $source_url, $destination ) {
		$response = wp_remote_get(
			$source_url,
			array(
				'timeout'     => 30,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'michiryu_sekki_import_image_http', sprintf(
				/* translators: 1: URL, 2: HTTP status code. */
				__( 'Could not download image %1$s. HTTP status: %2$d.', 'michiryu-sekki' ),
				$source_url,
				$code
			) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return new WP_Error( 'michiryu_sekki_import_empty_image', sprintf(
				/* translators: %s: URL. */
				__( 'Downloaded image %s was empty.', 'michiryu-sekki' ),
				$source_url
			) );
		}

		$directory = dirname( $destination );
		if ( ! wp_mkdir_p( $directory ) ) {
			return new WP_Error( 'michiryu_sekki_import_image_directory', __( 'WordPress could not create an image folder for imported content.', 'michiryu-sekki' ) );
		}

		return false === file_put_contents( $destination, $body ) ? new WP_Error( 'michiryu_sekki_import_image_write', __( 'WordPress could not save an imported image.', 'michiryu-sekki' ) ) : true;
	}

	/**
	 * Write JSON to disk.
	 *
	 * @param string $path File path.
	 * @param mixed  $data Data to encode.
	 */
	private function write_json( $path, $data ) {
		file_put_contents( $path, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
	}

	/**
	 * Sanitize a relative content path.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private function sanitize_relative_path( $path ) {
		$path = ltrim( str_replace( '\\', '/', trim( (string) $path ) ), '/' );

		if ( '' === $path || preg_match( '#^https?://#i', $path ) ) {
			return '';
		}

		foreach ( explode( '/', $path ) as $segment ) {
			if ( '..' === $segment || '' === $segment ) {
				return '';
			}
		}

		return $path;
	}

	/**
	 * Return an error result.
	 *
	 * @param string $message Error message.
	 * @return array<string,mixed>
	 */
	private function error( $message ) {
		return array(
			'success' => false,
			'message' => $message,
		);
	}
}
