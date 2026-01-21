<?php
/**
 * Plugin Name:     Business Directory & Booking
 * Plugin URI:      http://localhost:8080
 * Description:     Custom plugin for business directory management and booking system
 * Author:          Nezer Gayao
 * Author URI:      http://localhost:8080
 * Text Domain:     business-directory-booking
 * Domain Path:     /languages
 * Version:         1.0.0
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package         Business_Directory_Booking
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'BDB_PLUGIN_VERSION', '1.0.0' );
define( 'BDB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BDB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load required files
require_once BDB_PLUGIN_PATH . 'includes/class-cpt.php';
require_once BDB_PLUGIN_PATH . 'includes/class-database.php';
require_once BDB_PLUGIN_PATH . 'includes/class-plugin.php';
require_once BDB_PLUGIN_PATH . 'includes/class-ajax.php';
require_once BDB_PLUGIN_PATH . 'includes/class-stripe.php';
require_once BDB_PLUGIN_PATH . 'includes/class-shortcodes.php';

// Load admin files (only in admin)
if ( is_admin() ) {
	require_once BDB_PLUGIN_PATH . 'admin/class-admin-pages.php';
}

/**
 * Main plugin class
 */
$bdb_plugin = new BDB_Plugin();
$bdb_plugin->run();
