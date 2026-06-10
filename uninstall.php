<?php
/**
 * Remove plugin options on uninstall.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'michiryu_sekki_options' );

