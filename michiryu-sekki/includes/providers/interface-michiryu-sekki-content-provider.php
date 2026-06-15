<?php
/**
 * Content provider contract.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the source boundary for seasonal and enhanced content.
 */
interface MichiRyu_Sekki_Content_Provider_Interface {
	/**
	 * Return all Sekki records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_sekki_content();

	/**
	 * Return all Ko records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_ko_content();

	/**
	 * Return image metadata or a resolved image identifier.
	 *
	 * @param string $id Image identifier.
	 * @return array<string,mixed>|string
	 */
	public function get_image( $id );

	/**
	 * Return map data.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_map_data();

	/**
	 * Return featured/enhanced content records.
	 *
	 * @return array<string,mixed>
	 */
	public function get_featured_content();
}

