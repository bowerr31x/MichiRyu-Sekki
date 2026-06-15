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

		$provider_key = defined( 'MICHIRYU_SEKKI_CONTENT_PROVIDER' ) ? MICHIRYU_SEKKI_CONTENT_PROVIDER : 'bundled';

		/**
		 * Filters the active content provider key.
		 *
		 * Supported core values are "bundled" and "local". The bundled provider
		 * is the temporary migration default; local returns GPL-safe factual data.
		 *
		 * @param string $provider_key Provider key.
		 */
		$provider_key = sanitize_key( apply_filters( 'michiryu_sekki_content_provider_key', $provider_key ) );
		$provider = 'local' === $provider_key ? new MichiRyu_Sekki_Local_Content_Provider() : new MichiRyu_Sekki_Bundled_Content_Provider();

		/**
		 * Filters the active content provider.
		 *
		 * The bundled provider preserves current behavior during migration. Future
		 * providers can replace it with a GPL-safe local provider or remote provider.
		 *
		 * @param MichiRyu_Sekki_Content_Provider_Interface $provider Active provider.
		 */
		$provider = apply_filters( 'michiryu_sekki_content_provider', $provider );

		if ( ! $provider instanceof MichiRyu_Sekki_Content_Provider_Interface ) {
			$provider = new MichiRyu_Sekki_Local_Content_Provider();
		}

		self::$provider = $provider;

		return self::$provider;
	}

	/**
	 * Return whether the active provider is the GPL-safe local provider.
	 *
	 * @return bool
	 */
	public static function is_local_provider() {
		$provider = self::get_provider();
		return $provider instanceof MichiRyu_Sekki_Local_Content_Provider && ! ( $provider instanceof MichiRyu_Sekki_Bundled_Content_Provider );
	}

	/**
	 * Build complete content model from Markdown and existing seasonal data.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_content() {
		$provider = self::get_provider();
		$featured_content = $provider->get_featured_content();
		$stories = is_array( $featured_content['stories'] ?? null ) ? $featured_content['stories'] : array();
		$characters = is_array( $featured_content['characters'] ?? null ) ? $featured_content['characters'] : array();
		$seasons = $provider->get_sekki_content();
		$ko = $provider->get_ko_content();
		$map_locations = $provider->get_map_data();

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
