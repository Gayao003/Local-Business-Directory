<?php
/**
 * Admin Pages Handler
 *
 * Registers admin pages and menu items for the Business Directory Booking plugin.
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BDB_Admin_Pages class
 */
class BDB_Admin_Pages {

	/**
	 * Initialize admin pages
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register admin menu pages
	 */
	public function register_admin_menus() {
		// Main menu
		add_menu_page(
			'Business Directory',
			'Business Directory',
			'manage_options',
			'bdb-dashboard',
			array( $this, 'dashboard_page' ),
			'dashicons-store',
			26
		);

		// Bookings submenu
		add_submenu_page(
			'bdb-dashboard',
			'All Bookings',
			'Bookings',
			'manage_options',
			'bdb-bookings',
			array( $this, 'bookings_page' )
		);

		// Calendar submenu
		add_submenu_page(
			'bdb-dashboard',
			'Booking Calendar',
			'Calendar',
			'manage_options',
			'bdb-calendar',
			array( $this, 'calendar_page' )
		);

		// Businesses submenu
		add_submenu_page(
			'bdb-dashboard',
			'All Businesses',
			'Businesses',
			'manage_options',
			'bdb-businesses',
			array( $this, 'businesses_page' )
		);

		// Settings submenu
		add_submenu_page(
			'bdb-dashboard',
			'Business Directory Settings',
			'Settings',
			'manage_options',
			'bdb-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'bdb-' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'bdb-admin-ui',
			plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'admin/js/admin-ui.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			'1.0.0',
			true
		);

		wp_enqueue_style(
			'bdb-admin-ui',
			plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'admin/css/admin-ui.css',
			array( 'jquery-ui-core' ),
			'1.0.0'
		);

		// Pass nonce to JS
		wp_localize_script(
			'bdb-admin-ui',
			'bdbAdmin',
			array(
				'nonce'    => wp_create_nonce( 'bdb_admin_nonce' ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Dashboard page callback
	 */
	public function dashboard_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/dashboard-page.php';
	}

	/**
	 * Bookings page callback
	 */
	public function bookings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/bookings-page.php';
	}

	/**
	 * Calendar page callback
	 */
	public function calendar_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/calendar-page.php';
	}

	/**
	 * Businesses page callback
	 */
	public function businesses_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/businesses-page.php';
	}

	/**
	 * Settings page callback
	 */
	public function settings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/settings-page.php';
	}
}
