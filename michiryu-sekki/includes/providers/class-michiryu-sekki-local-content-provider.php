<?php
/**
 * GPL-safe local content provider.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides factual seasonal calendar data without proprietary content.
 */
class MichiRyu_Sekki_Local_Content_Provider implements MichiRyu_Sekki_Content_Provider_Interface {
	/**
	 * Return factual Sekki records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_sekki_content() {
		return MichiRyu_Sekki_Data::get_seasons();
	}

	/**
	 * Return factual Ko records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_ko_content() {
		return MichiRyu_Sekki_Data::get_ko();
	}

	/**
	 * Return no proprietary image content.
	 *
	 * @param string $id Image identifier.
	 * @return string
	 */
	public function get_image( $id ) {
		return '';
	}

	/**
	 * Return generic map coordinates from factual Sekki records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_map_data() {
		$map_locations = array();

		foreach ( $this->get_sekki_content() as $season ) {
			$map_locations[] = array(
				'sekki_number' => $season['sekki_number'],
				'sekki_slug'   => $season['slug'],
				'x_percent'    => $season['map_x_percent'],
				'y_percent'    => $season['map_y_percent'],
			);
		}

		return $map_locations;
	}

	/**
	 * Return no proprietary featured content.
	 *
	 * @return array<string,mixed>
	 */
	public function get_featured_content() {
		return array(
			'stories'    => array(),
			'characters' => array(),
		);
	}
}

