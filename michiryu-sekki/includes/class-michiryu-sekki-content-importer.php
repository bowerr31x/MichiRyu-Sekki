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
	 * @param string $access_token Optional access token.
	 * @return array<string,mixed>
	 */
	public function import( $remote_url, $access_token = '' ) {
		$remote_url = $this->normalize_remote_url( $remote_url );
		$access_token = trim( (string) $access_token );

		if ( '' === $remote_url ) {
			return $this->error( __( 'Enter a valid remote content URL.', 'michiryu-sekki' ) );
		}

		$content_path = MichiRyu_Sekki_Imported_Content_Provider::get_content_path();
		if ( ! wp_mkdir_p( $content_path ) ) {
			return $this->error( __( 'WordPress could not create the local content folder.', 'michiryu-sekki' ) );
		}

		$featured_content = $this->fetch_json( $remote_url . '/featured-content.json', $access_token );
		if ( is_wp_error( $featured_content ) ) {
			return $this->error( $featured_content->get_error_message() );
		}

		$images = $this->fetch_json( $remote_url . '/images.json', $access_token );
		if ( is_wp_error( $images ) ) {
			return $this->error( $images->get_error_message() );
		}

		if ( ! is_array( $featured_content ) || ! is_array( $featured_content['stories'] ?? null ) || ! is_array( $featured_content['characters'] ?? null ) ) {
			return $this->error( __( 'featured-content.json must include stories and characters.', 'michiryu-sekki' ) );
		}

		if ( ! is_array( $images ) ) {
			return $this->error( __( 'images.json must be a JSON object.', 'michiryu-sekki' ) );
		}

		$image_result = $this->import_images( $remote_url, $images, $content_path, $access_token );
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
			'uses_token'    => '' !== $access_token,
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
	 * Remove locally imported content.
	 *
	 * @return array<string,mixed>
	 */
	public function remove_imported_content() {
		$content_path = MichiRyu_Sekki_Imported_Content_Provider::get_content_path();
		$upload_dir   = wp_upload_dir();
		$uploads_path = is_array( $upload_dir ) && ! empty( $upload_dir['basedir'] ) ? realpath( $upload_dir['basedir'] ) : false;
		$content_realpath = realpath( $content_path );

		if ( false === $content_realpath ) {
			delete_option( self::OPTION_NAME );
			return array(
				'success' => true,
				'message' => __( 'Imported content status was cleared. No imported content folder was found.', 'michiryu-sekki' ),
			);
		}

		if ( false === $uploads_path || 0 !== strpos( $content_realpath, rtrim( $uploads_path, '/\\' ) . DIRECTORY_SEPARATOR ) ) {
			return $this->error( __( 'The imported content folder is outside WordPress uploads and was not removed.', 'michiryu-sekki' ) );
		}

		if ( ! $this->delete_directory( $content_realpath ) ) {
			return $this->error( __( 'WordPress could not remove the imported content folder.', 'michiryu-sekki' ) );
		}

		delete_option( self::OPTION_NAME );

		return array(
			'success' => true,
			'message' => __( 'Imported MichiRyu content was removed from this site.', 'michiryu-sekki' ),
		);
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
	 * @param string $access_token Optional access token.
	 * @return array<string,mixed>|WP_Error
	 */
	private function fetch_json( $url, $access_token = '' ) {
		$response = wp_remote_get(
			$url,
			$this->get_request_args(
				array(
					'timeout'     => 20,
					'redirection' => 3,
				),
				$access_token
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
	 * @param string              $access_token Optional access token.
	 * @return array<string,int>|WP_Error
	 */
	private function import_images( $remote_url, $images, $content_path, $access_token = '' ) {
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

			$result = $this->download_file( $source_url, $content_path . '/' . $relative_path, $access_token );
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
	 * @param string $access_token Optional access token.
	 * @return true|WP_Error
	 */
	private function download_file( $source_url, $destination, $access_token = '' ) {
		$response = wp_remote_get(
			$source_url,
			$this->get_request_args(
				array(
					'timeout'     => 30,
					'redirection' => 3,
				),
				$access_token
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
	 * Return remote request arguments.
	 *
	 * @param array<string,mixed> $args Request arguments.
	 * @param string              $access_token Optional access token.
	 * @return array<string,mixed>
	 */
	private function get_request_args( $args, $access_token ) {
		$access_token = trim( (string) $access_token );
		if ( '' === $access_token ) {
			return $args;
		}

		$headers = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();
		$headers['Authorization'] = 'Bearer ' . $access_token;
		$args['headers'] = $headers;

		return $args;
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
	 * Delete a directory tree.
	 *
	 * @param string $directory Directory path.
	 * @return bool
	 */
	private function delete_directory( $directory ) {
		if ( ! is_dir( $directory ) ) {
			return true;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				if ( ! rmdir( $item->getPathname() ) ) {
					return false;
				}
			} elseif ( ! unlink( $item->getPathname() ) ) {
				return false;
			}
		}

		return rmdir( $directory );
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
