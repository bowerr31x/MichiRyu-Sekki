<?php
/**
 * File-based content provider.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads provider data from a separately managed JSON content directory.
 */
class MichiRyu_Sekki_File_Content_Provider extends MichiRyu_Sekki_Local_Content_Provider {
	/**
	 * Content directory path.
	 *
	 * @var string
	 */
	private $content_path;

	/**
	 * Optional base URL for relative image paths.
	 *
	 * @var string
	 */
	private $content_url;

	/**
	 * JSON cache.
	 *
	 * @var array<string,mixed>
	 */
	private $json_cache = array();

	/**
	 * Constructor.
	 *
	 * @param string $content_path Content directory path.
	 * @param string $content_url Optional content base URL.
	 */
	public function __construct( $content_path, $content_url = '' ) {
		$this->content_path = rtrim( (string) $content_path, '/\\' );
		$this->content_url  = rtrim( (string) $content_url, '/' );
	}

	/**
	 * Return whether the path can be used as an external content directory.
	 *
	 * @param string $content_path Candidate content path.
	 * @return bool
	 */
	public static function is_valid_content_path( $content_path ) {
		if ( '' === trim( (string) $content_path ) ) {
			return false;
		}

		$content_realpath = realpath( (string) $content_path );
		$plugin_realpath  = realpath( MICHIRYU_SEKKI_PATH );

		if ( false === $content_realpath || ! is_dir( $content_realpath ) || ! is_readable( $content_realpath ) ) {
			return false;
		}

		if ( false !== $plugin_realpath ) {
			$plugin_realpath = rtrim( $plugin_realpath, '/\\' );
		}

		if ( false !== $plugin_realpath && ( $content_realpath === $plugin_realpath || 0 === strpos( $content_realpath, $plugin_realpath . DIRECTORY_SEPARATOR ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return provider path from constants and filters.
	 *
	 * @return string
	 */
	public static function get_configured_content_path() {
		$content_path = defined( 'MICHIRYU_SEKKI_CONTENT_PATH' ) ? MICHIRYU_SEKKI_CONTENT_PATH : '';

		/**
		 * Filters the external file provider content directory path.
		 *
		 * @param string $content_path Content path.
		 */
		return (string) apply_filters( 'michiryu_sekki_file_content_provider_path', $content_path );
	}

	/**
	 * Return provider URL from constants and filters.
	 *
	 * @return string
	 */
	public static function get_configured_content_url() {
		$content_url = defined( 'MICHIRYU_SEKKI_CONTENT_URL' ) ? MICHIRYU_SEKKI_CONTENT_URL : '';

		/**
		 * Filters the external file provider content base URL.
		 *
		 * @param string $content_url Content URL.
		 */
		return (string) apply_filters( 'michiryu_sekki_file_content_provider_url', $content_url );
	}

	/**
	 * Return Sekki records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_sekki_content() {
		$sekki = $this->read_json_file( 'sekki.json' );
		return is_array( $sekki ) && ! empty( $sekki ) ? $sekki : parent::get_sekki_content();
	}

	/**
	 * Return Ko records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_ko_content() {
		$ko = $this->read_json_file( 'ko.json' );
		return is_array( $ko ) && ! empty( $ko ) ? $ko : parent::get_ko_content();
	}

	/**
	 * Return image metadata or URL.
	 *
	 * @param string $id Image identifier.
	 * @return array<string,mixed>|string
	 */
	public function get_image( $id ) {
		$images = $this->read_json_file( 'images.json' );
		$id     = trim( (string) $id, '/' );

		if ( ! is_array( $images ) || empty( $images[ $id ] ) ) {
			return '';
		}

		return $this->resolve_image_value( $images[ $id ] );
	}

	/**
	 * Return map records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_map_data() {
		$map_locations = $this->read_json_file( 'map-locations.json' );
		return is_array( $map_locations ) && ! empty( $map_locations ) ? $map_locations : parent::get_map_data();
	}

	/**
	 * Return enhanced content.
	 *
	 * @return array<string,mixed>
	 */
	public function get_featured_content() {
		$featured_content = $this->read_json_file( 'featured-content.json' );

		if ( ! is_array( $featured_content ) ) {
			return parent::get_featured_content();
		}

		return array(
			'stories'    => is_array( $featured_content['stories'] ?? null ) ? $featured_content['stories'] : array(),
			'characters' => is_array( $featured_content['characters'] ?? null ) ? $featured_content['characters'] : array(),
		);
	}

	/**
	 * Read JSON from the content directory.
	 *
	 * @param string $filename JSON filename.
	 * @return mixed
	 */
	private function read_json_file( $filename ) {
		$filename = basename( $filename );

		if ( array_key_exists( $filename, $this->json_cache ) ) {
			return $this->json_cache[ $filename ];
		}

		$path = $this->content_path . DIRECTORY_SEPARATOR . $filename;
		if ( ! is_readable( $path ) ) {
			$this->json_cache[ $filename ] = null;
			return null;
		}

		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			$this->json_cache[ $filename ] = null;
			return null;
		}

		$decoded = json_decode( (string) $raw, true );
		$this->json_cache[ $filename ] = is_array( $decoded ) ? $decoded : null;

		return $this->json_cache[ $filename ];
	}

	/**
	 * Resolve an image mapping value.
	 *
	 * @param mixed $image Image mapping value.
	 * @return array<string,mixed>|string
	 */
	private function resolve_image_value( $image ) {
		if ( is_array( $image ) ) {
			return $image;
		}

		if ( ! is_string( $image ) || '' === trim( $image ) ) {
			return '';
		}

		$image = ltrim( trim( $image ), '/' );

		if ( preg_match( '#^https?://#i', $image ) ) {
			return $image;
		}

		if ( '' === $this->content_url ) {
			return '';
		}

		return array(
			'url' => $this->content_url . '/' . $image,
		);
	}
}
