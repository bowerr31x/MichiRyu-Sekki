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
	const MAX_JSON_BYTES = 1048576;
	const MAX_IMPORT_FILE_BYTES = 26214400;
	const ALLOWED_IMPORT_FILE_EXTENSIONS = array( 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf' );

	/**
	 * Import a remote content library.
	 *
	 * @param string $remote_url Remote content library base URL.
	 * @param string $access_token Optional access token.
	 * @param string $import_context Import context label.
	 * @return array<string,mixed>
	 */
	public function import( $remote_url, $access_token = '', $import_context = 'custom' ) {
		$remote_url = $this->normalize_remote_url( $remote_url );
		$access_token = trim( (string) $access_token );
		$import_context = sanitize_key( $import_context );

		if ( '' === $remote_url ) {
			return $this->error( __( 'Enter a valid remote content URL.', 'michiryu-sekki' ) );
		}

		$content_path = MichiRyu_Sekki_Imported_Content_Provider::get_content_path();
		if ( ! wp_mkdir_p( $content_path ) ) {
			return $this->error( __( 'WordPress could not create the local content folder.', 'michiryu-sekki' ) );
		}

		$source = $this->resolve_content_source( $remote_url, $access_token );
		if ( is_wp_error( $source ) ) {
			return $this->error( $source->get_error_message() );
		}

		$featured_content = $this->fetch_json( $source['featured_content_url'], $this->get_request_token_for_url( $source['featured_content_url'], $source, $access_token ) );
		if ( is_wp_error( $featured_content ) ) {
			return $this->error( $featured_content->get_error_message() );
		}

		$images = $this->fetch_json( $source['images_url'], $this->get_request_token_for_url( $source['images_url'], $source, $access_token ) );
		if ( is_wp_error( $images ) ) {
			return $this->error( $images->get_error_message() );
		}

		if ( ! is_array( $featured_content ) || ! is_array( $featured_content['stories'] ?? null ) || ! is_array( $featured_content['characters'] ?? null ) ) {
			return $this->error( __( 'featured-content.json must include stories and characters.', 'michiryu-sekki' ) );
		}

		if ( ! is_array( $images ) ) {
			return $this->error( __( 'images.json must be a JSON object.', 'michiryu-sekki' ) );
		}

		$image_result = $this->import_images( $source, $images, $content_path, $access_token );
		if ( is_wp_error( $image_result ) ) {
			return $this->error( $image_result->get_error_message() );
		}

		$this->write_json( $content_path . '/featured-content.json', $featured_content );
		$this->write_json( $content_path . '/images.json', $images );

		$status = array(
			'remote_url'    => $remote_url,
			'source_type'   => $source['type'],
			'import_context' => '' !== $import_context ? $import_context : 'custom',
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

		if ( function_exists( 'wp_http_validate_url' ) && ! wp_http_validate_url( $remote_url ) ) {
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
		$response = $this->remote_get(
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

		$body = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > self::MAX_JSON_BYTES ) {
			return new WP_Error( 'michiryu_sekki_import_json_too_large', sprintf(
				/* translators: %s: URL. */
				__( 'The response from %s was too large.', 'michiryu-sekki' ),
				$url
			) );
		}

		$decoded = json_decode( $body, true );
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
	 * Resolve a static-folder or manifest-based content source.
	 *
	 * @param string $remote_url Remote URL.
	 * @param string $access_token Optional access token.
	 * @return array<string,string>|WP_Error
	 */
	private function resolve_content_source( $remote_url, $access_token = '' ) {
		$manifest_url = $this->looks_like_manifest_url( $remote_url ) ? $remote_url : $remote_url . '/manifest';
		$manifest = $this->fetch_json( $manifest_url, $access_token );

		if ( is_array( $manifest ) && ( ! empty( $manifest['featured_content_url'] ) || ! empty( $manifest['featured_content'] ) ) && ( ! empty( $manifest['images_url'] ) || ! empty( $manifest['images'] ) ) ) {
			return $this->source_from_manifest( $remote_url, $manifest );
		}

		if ( $this->looks_like_manifest_url( $remote_url ) ) {
			return is_wp_error( $manifest ) ? $manifest : new WP_Error( 'michiryu_sekki_import_manifest', __( 'The content manifest did not include featured content and image URLs.', 'michiryu-sekki' ) );
		}

		return array(
			'type'                 => 'static',
			'base_url'             => $remote_url,
			'auth_origin'          => $this->get_url_origin( $remote_url ),
			'featured_content_url' => $remote_url . '/featured-content.json',
			'images_url'           => $remote_url . '/images.json',
			'file_base_url'        => $remote_url . '/',
		);
	}

	/**
	 * Return a source definition from manifest data.
	 *
	 * @param string              $remote_url Remote URL.
	 * @param array<string,mixed> $manifest Manifest data.
	 * @return array<string,string>
	 */
	private function source_from_manifest( $remote_url, $manifest ) {
		$base_url = ! empty( $manifest['base_url'] ) && is_string( $manifest['base_url'] ) ? rtrim( $manifest['base_url'], '/' ) : $remote_url;
		$file_base_url = ! empty( $manifest['file_base_url'] ) && is_string( $manifest['file_base_url'] ) ? $manifest['file_base_url'] : $base_url . '/';

		return array(
			'type'                 => 'manifest',
			'base_url'             => $base_url,
			'auth_origin'          => $this->get_url_origin( $remote_url ),
			'featured_content_url' => $this->resolve_manifest_url( $base_url, $manifest['featured_content_url'] ?? $manifest['featured_content'] ?? 'featured-content.json' ),
			'images_url'           => $this->resolve_manifest_url( $base_url, $manifest['images_url'] ?? $manifest['images'] ?? 'images.json' ),
			'file_base_url'        => $file_base_url,
		);
	}

	/**
	 * Resolve a manifest URL or relative path.
	 *
	 * @param string $base_url Base URL.
	 * @param mixed  $value URL or path value.
	 * @return string
	 */
	private function resolve_manifest_url( $base_url, $value ) {
		$value = trim( (string) $value );
		if ( preg_match( '#^https?://#i', $value ) ) {
			return $value;
		}

		return rtrim( $base_url, '/' ) . '/' . ltrim( $value, '/' );
	}

	/**
	 * Return whether a URL appears to be a manifest endpoint.
	 *
	 * @param string $remote_url Remote URL.
	 * @return bool
	 */
	private function looks_like_manifest_url( $remote_url ) {
		return false !== strpos( $remote_url, 'route=manifest' ) || preg_match( '#/manifest/?$#', $remote_url );
	}

	/**
	 * Import image files referenced by images.json.
	 *
	 * @param array<string,string> $source Content source.
	 * @param array<string,mixed> $images Image mapping.
	 * @param string              $content_path Local content path.
	 * @param string              $access_token Optional access token.
	 * @return array<string,int>|WP_Error
	 */
	private function import_images( $source, $images, $content_path, $access_token = '' ) {
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

			if ( ! $this->is_allowed_import_file( $relative_path ) ) {
				return new WP_Error( 'michiryu_sekki_import_file_type', sprintf(
					/* translators: %s: relative file path. */
					__( 'Imported file type is not allowed: %s.', 'michiryu-sekki' ),
					$relative_path
				) );
			}

			$source_url = $this->get_source_file_url( $source, $relative_path );
			if ( is_array( $image ) && ! empty( $image['url'] ) && is_string( $image['url'] ) && preg_match( '#^https?://#i', $image['url'] ) ) {
				$source_url = $image['url'];
			}

			$result = $this->download_file( $source_url, $content_path . '/' . $relative_path, $this->get_request_token_for_url( $source_url, $source, $access_token ) );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$copied++;
		}

		return array( 'copied' => $copied );
	}

	/**
	 * Return the remote URL for a source file path.
	 *
	 * @param array<string,string> $source Content source.
	 * @param string               $relative_path Relative path.
	 * @return string
	 */
	private function get_source_file_url( $source, $relative_path ) {
		$file_base_url = $source['file_base_url'] ?? ( ( $source['base_url'] ?? '' ) . '/' );

		if ( false !== strpos( $file_base_url, '?' ) ) {
			return $file_base_url . rawurlencode( $relative_path );
		}

		return rtrim( $file_base_url, '/' ) . '/' . ltrim( $relative_path, '/' );
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
		if ( ! $this->is_allowed_import_file( $destination ) ) {
			return new WP_Error( 'michiryu_sekki_import_file_type', __( 'Imported file type is not allowed.', 'michiryu-sekki' ) );
		}

		$response = $this->remote_get(
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
		if ( strlen( (string) $body ) > self::MAX_IMPORT_FILE_BYTES ) {
			return new WP_Error( 'michiryu_sekki_import_image_too_large', sprintf(
				/* translators: %s: URL. */
				__( 'Downloaded image %s was too large.', 'michiryu-sekki' ),
				$source_url
			) );
		}

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
		$args['reject_unsafe_urls'] = true;
		$access_token = trim( (string) $access_token );
		if ( '' === $access_token ) {
			return $args;
		}

		$args['redirection'] = 0;
		$headers = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();
		$headers['X-MichiRyu-Content-Token'] = $access_token;
		$args['headers'] = $headers;

		return $args;
	}

	/**
	 * Perform a guarded remote GET request.
	 *
	 * @param string              $url Remote URL.
	 * @param array<string,mixed> $args Request arguments.
	 * @return array<string,mixed>|WP_Error
	 */
	private function remote_get( $url, $args ) {
		if ( function_exists( 'wp_safe_remote_get' ) ) {
			return wp_safe_remote_get( $url, $args );
		}

		return wp_remote_get( $url, $args );
	}

	/**
	 * Return a bearer token only for URLs on the original content source origin.
	 *
	 * @param string              $url Request URL.
	 * @param array<string,mixed> $source Content source.
	 * @param string              $access_token Access token.
	 * @return string
	 */
	private function get_request_token_for_url( $url, $source, $access_token ) {
		$access_token = trim( (string) $access_token );
		if ( '' === $access_token ) {
			return '';
		}

		$auth_origin = (string) ( $source['auth_origin'] ?? '' );
		if ( '' === $auth_origin || ! $this->url_origins_match_for_token( $auth_origin, $this->get_url_origin( $url ) ) ) {
			return '';
		}

		return $access_token;
	}

	/**
	 * Return whether two URL origins are close enough to share a content token.
	 *
	 * @param string $expected_origin Original configured content source origin.
	 * @param string $request_origin Requested content file origin.
	 * @return bool
	 */
	private function url_origins_match_for_token( $expected_origin, $request_origin ) {
		if ( '' === $expected_origin || '' === $request_origin ) {
			return false;
		}

		if ( $expected_origin === $request_origin ) {
			return true;
		}

		$expected = wp_parse_url( $expected_origin );
		$request  = wp_parse_url( $request_origin );

		if ( ! is_array( $expected ) || ! is_array( $request ) ) {
			return false;
		}

		$expected_scheme = strtolower( (string) ( $expected['scheme'] ?? '' ) );
		$request_scheme  = strtolower( (string) ( $request['scheme'] ?? '' ) );
		$expected_port   = isset( $expected['port'] ) ? absint( $expected['port'] ) : 0;
		$request_port    = isset( $request['port'] ) ? absint( $request['port'] ) : 0;

		if ( $expected_scheme !== $request_scheme || $expected_port !== $request_port ) {
			return false;
		}

		return $this->normalize_token_host( $expected['host'] ?? '' ) === $this->normalize_token_host( $request['host'] ?? '' );
	}

	/**
	 * Normalize a host for same-site content token checks.
	 *
	 * @param string $host Host name.
	 * @return string
	 */
	private function normalize_token_host( $host ) {
		$host = strtolower( trim( (string) $host ) );

		if ( 0 === strpos( $host, 'www.' ) ) {
			return substr( $host, 4 );
		}

		return $host;
	}

	/**
	 * Return a normalized URL origin.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function get_url_origin( $url ) {
		$parts = wp_parse_url( (string) $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		$host = strtolower( (string) $parts['host'] );
		$port = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';

		return $scheme . '://' . $host . $port;
	}

	/**
	 * Return whether an imported file path uses an allowed extension.
	 *
	 * @param string $path Relative or absolute file path.
	 * @return bool
	 */
	private function is_allowed_import_file( $path ) {
		$extension = strtolower( pathinfo( (string) $path, PATHINFO_EXTENSION ) );

		return in_array( $extension, self::ALLOWED_IMPORT_FILE_EXTENSIONS, true );
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
