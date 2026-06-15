<?php
/**
 * Temporary bundled content provider.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserves current bundled content behavior while migration work moves access behind a provider.
 */
class MichiRyu_Sekki_Bundled_Content_Provider extends MichiRyu_Sekki_Local_Content_Provider {
	const STORIES_DIR = 'stories';

	/**
	 * Return a bundled image relative path when available.
	 *
	 * @param string $id Image identifier.
	 * @return string
	 */
	public function get_image( $id ) {
		$id = trim( (string) $id, '/' );
		$candidates = array();

		if ( 'map' === $id ) {
			$candidates = array(
				'assets/images/map/yuki-no-sato-sekki-map.jpg',
				'assets/images/YukiNoSato.png',
			);
		} elseif ( preg_match( '#^(sekki|ko)/(.+)$#', $id, $matches ) ) {
			$kind = 'ko' === $matches[1] ? 'ko' : 'sekki';
			foreach ( $this->get_asset_filename_candidates( basename( $matches[2] ), $kind ) as $candidate ) {
				$candidates[] = 'assets/images/' . $kind . '/' . $candidate;
			}
		}

		foreach ( $candidates as $relative ) {
			if ( file_exists( MICHIRYU_SEKKI_PATH . $relative ) ) {
				return $relative;
			}
		}

		return '';
	}

	/**
	 * Return bundled enhanced content records.
	 *
	 * @return array<string,mixed>
	 */
	public function get_featured_content() {
		$stories = $this->load_stories();

		return array(
			'stories'    => $stories,
			'characters' => $this->load_characters( $stories ),
		);
	}

	/**
	 * Load Markdown stories.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function load_stories() {
		$stories_dir = MICHIRYU_SEKKI_PATH . self::STORIES_DIR;
		if ( ! is_readable( $stories_dir ) ) {
			return array();
		}

		$files = glob( $stories_dir . '/Sekki_*/*.md' );
		$stories = array();

		foreach ( $files ? $files : array() as $file ) {
			$parsed = $this->parse_story_file( $file );
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
	private function parse_story_file( $file ) {
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$raw = file_get_contents( $file );
		if ( false === $raw ) {
			return array();
		}

		if ( ! preg_match( '/^---\s*(.*?)\s*---\s*(.*)$/s', (string) $raw, $matches ) ) {
			return array();
		}

		$front = $this->parse_front_matter( $matches[1] );
		$markdown = trim( $matches[2] );
		$story_markdown = preg_split( '/^##\s+Ikebana Reflection\s*$/m', $markdown )[0] ?? $markdown;
		$story_markdown = preg_replace( '/^#\s+.*?$/m', '', $story_markdown );
		$spotlight = '';

		if ( preg_match( '/^##\s+Character Spotlight\s*(.*?)$/ms', $markdown, $spotlight_matches ) ) {
			$spotlight = trim( $spotlight_matches[1] );
		}

		$character = is_array( $front['character'] ?? null ) ? $front['character'] : array();
		$character_ids = $this->get_character_ids_from_front_matter( $front );
		$character_id = $character_ids[0] ?? '';
		$materials = $front['arrangement_materials'] ?? ( $front['materials'] ?? '' );

		return array(
			'id'                    => sanitize_title( basename( $file, '.md' ) ),
			'source_file'           => str_replace( MICHIRYU_SEKKI_PATH, '', $file ),
			'sekki_number'          => (int) ( $front['sekki_number'] ?? 0 ),
			'sekki_slug'            => sanitize_key( $front['sekki_slug'] ?? '' ),
			'sekki_name'            => $front['sekki_name'] ?? '',
			'sekki_english'         => $front['sekki_english'] ?? '',
			'ko_number'             => (int) ( $front['ko_number'] ?? 0 ),
			'ko_slug'               => sanitize_key( $front['ko_slug'] ?? '' ),
			'ko_name'               => $front['ko_name'] ?? ( $front['title'] ?? basename( $file, '.md' ) ),
			'title'                 => $front['ko_name'] ?? ( $front['title'] ?? basename( $file, '.md' ) ),
			'story_file'            => $front['story_file'] ?? basename( $file ),
			'image'                 => $front['image'] ?? '',
			'icon'                  => $front['icon'] ?? '',
			'location'              => (int) ( $front['location'] ?? 0 ),
			'characters'            => is_array( $front['characters'] ?? null ) ? $front['characters'] : $character_ids,
			'character'             => array(
				'id'   => $character_id,
				'name' => $character['name'] ?? '',
				'role' => $character['role'] ?? '',
			),
			'arrangement_materials' => is_array( $materials ) ? array_values( $materials ) : array_filter( array_map( 'trim', explode( ',', (string) $materials ) ) ),
			'materials'             => is_array( $materials ) ? implode( ', ', $materials ) : (string) $materials,
			'theme'                 => $front['theme'] ?? '',
			'lesson'                => $front['lesson'] ?? '',
			'spotlight_character'   => sanitize_key( $front['spotlight_character'] ?? $character_id ),
			'body_markdown'         => trim( $story_markdown ),
			'body_text'             => $this->markdown_to_text( $story_markdown ),
			'body_html'             => $this->markdown_to_html( $story_markdown ),
			'spotlight'             => $this->markdown_to_text( $spotlight ),
		);
	}

	/**
	 * Parse simple YAML front matter.
	 *
	 * @param string $yaml YAML text.
	 * @return array<string,mixed>
	 */
	private function parse_front_matter( $yaml ) {
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
	private function get_character_ids_from_front_matter( $front ) {
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
	 * Load character source.
	 *
	 * @param array<int,array<string,mixed>> $stories Story records.
	 * @return array<string,array<string,mixed>>
	 */
	private function load_characters( $stories ) {
		$path = MICHIRYU_SEKKI_PATH . self::STORIES_DIR . '/characters.json';
		$raw = is_readable( $path ) ? file_get_contents( $path ) : false;
		$characters = false !== $raw ? json_decode( (string) $raw, true ) : array();
		return is_array( $characters ) ? $characters : array();
	}

	/**
	 * Convert simple Markdown paragraphs to HTML.
	 *
	 * @param string $markdown Markdown text.
	 * @return string
	 */
	private function markdown_to_html( $markdown ) {
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
	private function markdown_to_text( $markdown ) {
		$text = preg_replace( '/^#{1,6}\s+/m', '', $markdown );
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}

	/**
	 * Build predictable filename fallbacks for bundled image assets.
	 *
	 * @param string $filename Stored filename.
	 * @param string $kind Asset kind.
	 * @return array<int,string>
	 */
	private function get_asset_filename_candidates( $filename, $kind ) {
		$pathinfo  = pathinfo( $filename );
		$dirname   = empty( $pathinfo['dirname'] ) || '.' === $pathinfo['dirname'] ? '' : trailingslashit( $pathinfo['dirname'] );
		$basename  = $pathinfo['filename'] ?? $filename;
		$extension = strtolower( $pathinfo['extension'] ?? '' );
		$preferred = 'ko' === $kind ? array( 'svg', 'jpg', 'jpeg', 'png' ) : array( 'jpg', 'jpeg', 'png' );
		$extensions = array_values( array_unique( array_filter( array_merge( array( $extension ), $preferred ) ) ) );
		$candidates = array();

		foreach ( $extensions as $candidate_extension ) {
			$candidates[] = $dirname . $basename . '.' . $candidate_extension;
		}

		return array_values( array_unique( $candidates ) );
	}
}
