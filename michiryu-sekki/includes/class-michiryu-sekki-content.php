<?php
/**
 * Markdown story importer and in-request content cache.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and reads the story content model.
 */
class MichiRyu_Sekki_Content {
	/**
	 * In-request content cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private static $content_cache = null;

	/**
	 * In-request stories-by-Sekki cache.
	 *
	 * @var array<int,array<int,array<string,mixed>>>
	 */
	private static $stories_by_sekki = array();

	/**
	 * In-request provider cache.
	 *
	 * @var MichiRyu_Sekki_Content_Provider_Interface|null
	 */
	private static $provider = null;

	/**
	 * Active provider key.
	 *
	 * @var string|null
	 */
	private static $provider_key = null;

	/**
	 * Return full runtime content.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_content() {
		if ( null !== self::$content_cache ) {
			return self::$content_cache;
		}

		self::$content_cache = self::build_content();

		return self::$content_cache;
	}

	/**
	 * Return character records.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_characters() {
		$content = self::get_content();
		return is_array( $content['characters'] ?? null ) ? $content['characters'] : array();
	}

	/**
	 * Return story records for a Sekki number.
	 *
	 * @param int $sekki_number Sekki number.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_stories_for_sekki( $sekki_number ) {
		$sekki_number = absint( $sekki_number );

		if ( isset( self::$stories_by_sekki[ $sekki_number ] ) ) {
			return self::$stories_by_sekki[ $sekki_number ];
		}

		$content = self::get_content();
		$stories = is_array( $content['stories'] ?? null ) ? $content['stories'] : array();

		self::$stories_by_sekki[ $sekki_number ] = array_values(
			array_filter(
				$stories,
				function ( $story ) use ( $sekki_number ) {
					return (int) ( $story['sekki_number'] ?? 0 ) === (int) $sekki_number;
				}
			)
		);

		return self::$stories_by_sekki[ $sekki_number ];
	}

	/**
	 * Return the active content provider.
	 *
	 * @return MichiRyu_Sekki_Content_Provider_Interface
	 */
	public static function get_provider() {
		if ( null !== self::$provider ) {
			return self::$provider;
		}

		$provider_key = self::get_provider_key();
		$provider = new MichiRyu_Sekki_Local_Content_Provider();

		if ( 'file' === $provider_key ) {
			$content_path = MichiRyu_Sekki_File_Content_Provider::get_configured_content_path();
			if ( MichiRyu_Sekki_File_Content_Provider::is_valid_content_path( $content_path ) ) {
				$provider = new MichiRyu_Sekki_File_Content_Provider(
					$content_path,
					MichiRyu_Sekki_File_Content_Provider::get_configured_content_url()
				);
			}
		} elseif ( 'imported' === $provider_key || ( 'local' === $provider_key && MichiRyu_Sekki_Imported_Content_Provider::has_imported_content() ) ) {
			if ( MichiRyu_Sekki_Imported_Content_Provider::has_imported_content() ) {
				$provider = new MichiRyu_Sekki_Imported_Content_Provider();
			}
		}

		/**
		 * Filters the active content provider.
		 *
		 * Custom providers can supply separately licensed content without requiring
		 * proprietary files to be included in the plugin repository.
		 *
		 * @param MichiRyu_Sekki_Content_Provider_Interface $provider Active provider.
		 * @param string                                    $provider_key Requested provider key.
		 */
		$provider = apply_filters( 'michiryu_sekki_content_provider', $provider, $provider_key );

		if ( ! $provider instanceof MichiRyu_Sekki_Content_Provider_Interface ) {
			$provider = new MichiRyu_Sekki_Local_Content_Provider();
		}

		self::$provider = $provider;

		return self::$provider;
	}

	/**
	 * Return the selected content provider key.
	 *
	 * @return string
	 */
	public static function get_provider_key() {
		if ( null !== self::$provider_key ) {
			return self::$provider_key;
		}

		$provider_key = defined( 'MICHIRYU_SEKKI_CONTENT_PROVIDER' ) ? MICHIRYU_SEKKI_CONTENT_PROVIDER : 'local';

		/**
		 * Filters the active content provider key.
		 *
		 * Core defaults to "local". Custom providers can be supplied with the
		 * michiryu_sekki_content_provider filter.
		 *
		 * @param string $provider_key Provider key.
		 */
		self::$provider_key = sanitize_key( apply_filters( 'michiryu_sekki_content_provider_key', $provider_key ) );

		return self::$provider_key;
	}

	/**
	 * Return whether the active provider is the GPL-safe local provider.
	 *
	 * @return bool
	 */
	public static function is_local_provider() {
		$provider = self::get_provider();
		return get_class( $provider ) === MichiRyu_Sekki_Local_Content_Provider::class;
	}

	/**
	 * Build complete content model from Markdown and existing seasonal data.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_content() {
		$provider = self::get_provider();
		$local_provider = new MichiRyu_Sekki_Local_Content_Provider();
		$featured_content = self::normalize_featured_content(
			self::get_provider_value( $provider, 'get_featured_content', $local_provider->get_featured_content() )
		);
		$stories = $featured_content['stories'];
		$characters = $featured_content['characters'];
		$seasons = self::normalize_list(
			self::get_provider_value( $provider, 'get_sekki_content', $local_provider->get_sekki_content() ),
			$local_provider->get_sekki_content()
		);
		$ko = self::normalize_list(
			self::get_provider_value( $provider, 'get_ko_content', $local_provider->get_ko_content() ),
			$local_provider->get_ko_content()
		);
		$map_locations = self::normalize_list(
			self::get_provider_value( $provider, 'get_map_data', $local_provider->get_map_data() ),
			$local_provider->get_map_data()
		);

		self::add_navigation_links( $stories, $seasons, $ko );

		return array(
			'generated_at'   => gmdate( 'c' ),
			'models'         => array( 'stories', 'characters', 'sekki', 'ko', 'map_locations' ),
			'stories'        => $stories,
			'characters'     => $characters,
			'sekki'          => $seasons,
			'ko'             => $ko,
			'map_locations'  => $map_locations,
		);
	}

	/**
	 * Safely read a value from a provider.
	 *
	 * @param MichiRyu_Sekki_Content_Provider_Interface $provider Provider instance.
	 * @param string                                    $method Provider method.
	 * @param mixed                                     $fallback Fallback value.
	 * @return mixed
	 */
	private static function get_provider_value( $provider, $method, $fallback ) {
		try {
			return $provider->$method();
		} catch ( Throwable $error ) {
			return $fallback;
		}
	}

	/**
	 * Normalize a provider list response.
	 *
	 * @param mixed                  $value Provider response.
	 * @param array<int,mixed>|null  $fallback Optional fallback list.
	 * @return array<int,mixed>
	 */
	private static function normalize_list( $value, $fallback = null ) {
		if ( ! is_array( $value ) ) {
			return is_array( $fallback ) ? array_values( $fallback ) : array();
		}

		return array_values(
			array_filter(
				$value,
				function ( $item ) {
					return is_array( $item );
				}
			)
		);
	}

	/**
	 * Normalize enhanced provider content.
	 *
	 * @param mixed $value Provider response.
	 * @return array{stories:array<int,array<string,mixed>>,characters:array<string,array<string,mixed>>}
	 */
	private static function normalize_featured_content( $value ) {
		$content = is_array( $value ) ? $value : array();
		$characters = is_array( $content['characters'] ?? null ) ? $content['characters'] : array();

		return array(
			'stories'    => self::normalize_list( $content['stories'] ?? array() ),
			'characters' => array_filter(
				$characters,
				function ( $character ) {
					return is_array( $character );
				}
			),
		);
	}

	/**
	 * Add previous/next links to story records.
	 *
	 * @param array<int,array<string,mixed>> $stories Story records.
	 * @param array<int,array<string,mixed>> $seasons Sekki records.
	 * @param array<int,array<string,mixed>> $ko Ko records.
	 */
	private static function add_navigation_links( &$stories, $seasons, $ko ) {
		$count = count( $stories );

		foreach ( $stories as $index => &$story ) {
			$story['previous_story_id'] = $count ? $stories[ ( $index - 1 + $count ) % $count ]['id'] : '';
			$story['next_story_id'] = $count ? $stories[ ( $index + 1 ) % $count ]['id'] : '';
			$season = $seasons[ max( 0, (int) $story['sekki_number'] - 1 ) ] ?? array();
			$ko_record = $ko[ max( 0, (int) $story['ko_number'] - 1 ) ] ?? array();
			$previous = ! empty( $season['slug'] ) ? MichiRyu_Sekki_Data::get_previous( $season['slug'] ) : array();
			$next = ! empty( $season['slug'] ) ? MichiRyu_Sekki_Data::get_next( $season['slug'] ) : array();

			$story['previous_sekki_slug'] = $previous['slug'] ?? '';
			$story['next_sekki_slug'] = $next['slug'] ?? '';
			$story['related_season_image'] = ! empty( $story['image'] ) ? $story['image'] : ( $season['image_file'] ?? '' );
			$story['related_ko_icon'] = ! empty( $story['icon'] ) ? $story['icon'] : ( $ko_record['icon_file'] ?? '' );
			$story['map_location'] = array(
				'x_percent' => $season['map_x_percent'] ?? 50,
				'y_percent' => $season['map_y_percent'] ?? 50,
			);
		}
		unset( $story );
	}

}
