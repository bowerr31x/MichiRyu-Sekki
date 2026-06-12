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
	const STORIES_DIR = 'stories';

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
	 * Build complete content model from Markdown and existing seasonal data.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_content() {
		$stories = self::load_stories();
		$characters = self::load_characters( $stories );
		$seasons = MichiRyu_Sekki_Data::get_seasons();
		$ko = MichiRyu_Sekki_Data::get_ko();
		$map_locations = array();

		foreach ( $seasons as $season ) {
			$map_locations[] = array(
				'sekki_number' => $season['sekki_number'],
				'sekki_slug'   => $season['slug'],
				'x_percent'    => $season['map_x_percent'],
				'y_percent'    => $season['map_y_percent'],
			);
		}

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
	 * Load Markdown stories.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function load_stories() {
		$stories_dir = MICHIRYU_SEKKI_PATH . self::STORIES_DIR;
		if ( ! is_readable( $stories_dir ) ) {
			return array();
		}

		$pattern = $stories_dir . '/Sekki_*/*.md';
		$files = glob( $pattern );
		$stories = array();

		foreach ( $files ? $files : array() as $file ) {
			$parsed = self::parse_story_file( $file );
			if ( ! empty( $parsed ) ) {
				$stories[] = $parsed;
			}
		}

		usort(
			$stories,
			function ( $a, $b ) {
				return array( (int) $a['sekki_number'], (int) $a['ko_number'] ) <=> array( (int) $b['sekki_number'], (int) $b['ko_number'] );
			}
		);

		return $stories;
	}

	/**
	 * Parse a Markdown story file.
	 *
	 * @param string $file File path.
	 * @return array<string,mixed>
	 */
	private static function parse_story_file( $file ) {
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$raw = file_get_contents( $file );
		if ( false === $raw ) {
			return array();
		}

		$raw = (string) $raw;
		if ( ! preg_match( '/^---\s*(.*?)\s*---\s*(.*)$/s', $raw, $matches ) ) {
			return array();
		}

		$front = self::parse_front_matter( $matches[1] );
		$markdown = trim( $matches[2] );
		$story_markdown = preg_split( '/^##\s+Ikebana Reflection\s*$/m', $markdown )[0] ?? $markdown;
		$story_markdown = preg_replace( '/^#\s+.*?$/m', '', $story_markdown );
		$spotlight = '';

		if ( preg_match( '/^##\s+Character Spotlight\s*(.*?)$/ms', $markdown, $spotlight_matches ) ) {
			$spotlight = trim( $spotlight_matches[1] );
		}

		$character = is_array( $front['character'] ?? null ) ? $front['character'] : array();
		$character_ids = self::get_character_ids_from_front_matter( $front );
		$character_id = $character_ids[0] ?? '';
		$materials = $front['arrangement_materials'] ?? ( $front['materials'] ?? '' );

		return array(
			'id'                  => sanitize_title( basename( $file, '.md' ) ),
			'source_file'         => str_replace( MICHIRYU_SEKKI_PATH, '', $file ),
			'sekki_number'        => (int) ( $front['sekki_number'] ?? 0 ),
			'sekki_slug'          => sanitize_key( $front['sekki_slug'] ?? '' ),
			'sekki_name'          => $front['sekki_name'] ?? '',
			'sekki_english'       => $front['sekki_english'] ?? '',
			'ko_number'           => (int) ( $front['ko_number'] ?? 0 ),
			'ko_slug'             => sanitize_key( $front['ko_slug'] ?? '' ),
			'ko_name'             => $front['ko_name'] ?? ( $front['title'] ?? basename( $file, '.md' ) ),
			'title'               => $front['ko_name'] ?? ( $front['title'] ?? basename( $file, '.md' ) ),
			'story_file'          => $front['story_file'] ?? basename( $file ),
			'image'               => $front['image'] ?? '',
			'icon'                => $front['icon'] ?? '',
			'location'            => (int) ( $front['location'] ?? 0 ),
			'characters'          => is_array( $front['characters'] ?? null ) ? $front['characters'] : $character_ids,
			'character'           => array(
				'id'   => $character_id,
				'name' => $character['name'] ?? '',
				'role' => $character['role'] ?? '',
			),
			'arrangement_materials' => is_array( $materials ) ? array_values( $materials ) : array_filter( array_map( 'trim', explode( ',', (string) $materials ) ) ),
			'materials'           => is_array( $materials ) ? implode( ', ', $materials ) : (string) $materials,
			'theme'               => $front['theme'] ?? '',
			'lesson'              => $front['lesson'] ?? '',
			'spotlight_character' => sanitize_key( $front['spotlight_character'] ?? $character_id ),
			'body_markdown'       => trim( $story_markdown ),
			'body_text'           => self::markdown_to_text( $story_markdown ),
			'body_html'           => self::markdown_to_html( $story_markdown ),
			'spotlight'           => self::markdown_to_text( $spotlight ),
		);
	}

	/**
	 * Parse simple YAML front matter.
	 *
	 * @param string $yaml YAML text.
	 * @return array<string,mixed>
	 */
	private static function parse_front_matter( $yaml ) {
		$data = array();
		$current_key = '';
		$current_parent = '';

		foreach ( preg_split( '/\r?\n/', $yaml ) as $line ) {
			if ( preg_match( '/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $matches ) ) {
				$current_key = $matches[1];
				$current_parent = '';
				$value = trim( $matches[2] );
				if ( '' === $value ) {
					$data[ $current_key ] = array();
					$current_parent = $current_key;
				} else {
					$data[ $current_key ] = trim( $value, "\"'" );
				}
				continue;
			}

			if ( $current_parent && preg_match( '/^\s+([A-Za-z0-9_-]+):\s*(.+)$/', $line, $matches ) ) {
				$data[ $current_parent ][ $matches[1] ] = trim( $matches[2], "\"'" );
				continue;
			}

			if ( $current_key && preg_match( '/^\s*-\s*(.+)$/', $line, $matches ) ) {
				if ( ! is_array( $data[ $current_key ] ) ) {
					$data[ $current_key ] = array();
				}
				$value = trim( $matches[1], "\"'" );
				$data[ $current_key ][] = 'characters' === $current_key ? sanitize_key( $value ) : $value;
			}
		}

		return $data;
	}

	/**
	 * Resolve character ids from front matter.
	 *
	 * @param array<string,mixed> $front Parsed front matter.
	 * @return array<int,string>
	 */
	private static function get_character_ids_from_front_matter( $front ) {
		if ( ! empty( $front['spotlight_character'] ) ) {
			return array( sanitize_key( $front['spotlight_character'] ) );
		}

		if ( ! empty( $front['character'] ) && is_array( $front['character'] ) && ! empty( $front['character']['name'] ) ) {
			$name = trim( (string) $front['character']['name'] );
			if ( false !== stripos( $name, ' and ' ) ) {
				return array_values( array_filter( array_map( 'sanitize_title', preg_split( '/\s+and\s+/i', $name ) ) ) );
			}
			return array( sanitize_title( $name ) );
		}

		return array();
	}

	/**
	 * Load character source and keep only linked characters when possible.
	 *
	 * @param array<int,array<string,mixed>> $stories Story records.
	 * @return array<string,array<string,mixed>>
	 */
	private static function load_characters( $stories ) {
		$path = MICHIRYU_SEKKI_PATH . self::STORIES_DIR . '/characters.json';
		$raw = is_readable( $path ) ? file_get_contents( $path ) : false;
		$characters = false !== $raw ? json_decode( (string) $raw, true ) : array();
		return is_array( $characters ) ? $characters : array();
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

	/**
	 * Convert simple Markdown paragraphs to HTML.
	 *
	 * @param string $markdown Markdown text.
	 * @return string
	 */
	private static function markdown_to_html( $markdown ) {
		$paragraphs = array_filter( array_map( 'trim', preg_split( '/\n+/', trim( $markdown ) ) ) );
		$html = array();

		foreach ( $paragraphs as $paragraph ) {
			if ( preg_match( '/^#{1,6}\s+/', $paragraph ) ) {
				continue;
			}
			$html[] = '<p>' . esc_html( preg_replace( '/\s+/', ' ', $paragraph ) ) . '</p>';
		}

		return implode( '', $html );
	}

	/**
	 * Convert simple Markdown to plain text.
	 *
	 * @param string $markdown Markdown text.
	 * @return string
	 */
	private static function markdown_to_text( $markdown ) {
		$text = preg_replace( '/^#{1,6}\s+/m', '', $markdown );
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}

}
