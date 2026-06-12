<?php
/**
 * Plugin Name: MichiRyu-Sekki-Calendar
 * Plugin URI:  https://michiryu.com
 * Description: Display the current Japanese 24 Sekki solar term with elegant seasonal cards, banners, widgets, and ikebana prompts.
 * Version:     1.2.19
 * Author:      MichiRyu
 * Author URI:  https://michiryu.com
 * Text Domain: michiryu-sekki
 * Domain Path: /languages
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MICHIRYU_SEKKI_VERSION', '1.2.19' );
define( 'MICHIRYU_SEKKI_FILE', __FILE__ );
define( 'MICHIRYU_SEKKI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MICHIRYU_SEKKI_URL', plugin_dir_url( __FILE__ ) );

require_once MICHIRYU_SEKKI_PATH . 'includes/class-michiryu-sekki-data.php';
require_once MICHIRYU_SEKKI_PATH . 'includes/class-michiryu-sekki-content.php';
require_once MICHIRYU_SEKKI_PATH . 'includes/class-michiryu-sekki.php';
require_once MICHIRYU_SEKKI_PATH . 'admin/class-michiryu-sekki-admin.php';
require_once MICHIRYU_SEKKI_PATH . 'includes/class-michiryu-sekki-widget.php';

/**
 * Start the plugin.
 */
function michiryu_sekki_bootstrap() {
	$plugin = new MichiRyu_Sekki();
	$plugin->init();
}
add_action( 'plugins_loaded', 'michiryu_sekki_bootstrap' );
