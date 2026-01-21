<?php
/**
 * Business Directory Theme - Functions
 * 
 * @package Business_Directory_Theme
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Theme constants
define( 'BDB_THEME_VERSION', '1.0.0' );
define( 'BDB_THEME_URI', get_template_directory_uri() );
define( 'BDB_THEME_PATH', get_template_directory() );

/**
 * Set up theme
 */
function bdb_theme_setup() {
	// Add theme support
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo' );
	
	// Register menus
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'bdb-theme' ),
		'footer'  => esc_html__( 'Footer Menu', 'bdb-theme' ),
	) );
}
add_action( 'after_setup_theme', 'bdb_theme_setup' );

/**
 * Enqueue theme assets
 */
function bdb_enqueue_assets() {
	// Main stylesheet (Tailwind compiled)
	wp_enqueue_style( 'bdb-style', BDB_THEME_URI . '/style.css', array(), BDB_THEME_VERSION );
	
	// jQuery (WordPress default)
	wp_enqueue_script( 'jquery' );
	
	// Main theme script
	wp_enqueue_script( 
		'bdb-main',
		BDB_THEME_URI . '/js/main.js',
		array( 'jquery' ),
		BDB_THEME_VERSION,
		true
	);
	
	// Localize for AJAX
	wp_localize_script( 'bdb-main', 'bdb_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'bdb_nonce' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'bdb_enqueue_assets' );

/**
 * Admin enqueue scripts and styles
 */
function bdb_admin_enqueue_assets() {
	wp_enqueue_style( 'bdb-admin', BDB_THEME_URI . '/css/admin.css', array(), BDB_THEME_VERSION );
	wp_enqueue_script( 'bdb-admin', BDB_THEME_URI . '/js/admin.js', array(), BDB_THEME_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'bdb_admin_enqueue_assets' );

/**
 * Register sidebars
 */
function bdb_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Primary Sidebar', 'bdb-theme' ),
		'id'            => 'primary-sidebar',
		'description'   => esc_html__( 'Main sidebar', 'bdb-theme' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'bdb_widgets_init' );
