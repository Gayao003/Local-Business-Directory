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
	// Main stylesheet
	wp_enqueue_style( 'bdb-style', BDB_THEME_URI . '/css/style.css', array(), BDB_THEME_VERSION );
	
	// Business listing styles
	wp_enqueue_style( 'bdb-business-listing', BDB_THEME_URI . '/css/business-listing.css', array('bdb-style'), BDB_THEME_VERSION );
	
	// Tesla-inspired design system
	wp_enqueue_style( 'bdb-tesla-design', BDB_THEME_URI . '/css/tesla-design.css', array('bdb-business-listing'), BDB_THEME_VERSION );
	
	// Theme stylesheet (required by WordPress)
	wp_enqueue_style( 'bdb-theme-style', get_stylesheet_uri(), array(), BDB_THEME_VERSION );
	
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
	
	// Tesla interactions
	wp_enqueue_script( 
		'bdb-tesla',
		BDB_THEME_URI . '/js/tesla-interactions.js',
		array( 'jquery', 'bdb-main' ),
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

/**
 * Modify business listing query for location-based search
 */
function bdb_filter_businesses_by_location( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'business_listing' ) ) {
		
		// Check if Near Me search is active
		if ( isset( $_GET['latitude'] ) && isset( $_GET['longitude'] ) ) {
			$user_lat = floatval( $_GET['latitude'] );
			$user_lng = floatval( $_GET['longitude'] );
			$distance = isset( $_GET['distance'] ) ? intval( $_GET['distance'] ) : 999999; // Default: all results
			
			// Get all businesses with coordinates
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'key' => '_business_latitude',
				'compare' => 'EXISTS'
			);
			$meta_query[] = array(
				'key' => '_business_longitude',
				'compare' => 'EXISTS'
			);
			
			$query->set( 'meta_query', $meta_query );
			
			// We'll filter by distance in the template after querying
			// WordPress doesn't support native distance calculations in WP_Query
			// So we'll use posts_where filter or post-process results
			
			// Store user location for later filtering
			$query->set( 'user_latitude', $user_lat );
			$query->set( 'user_longitude', $user_lng );
			$query->set( 'max_distance', $distance );
		}
	}
}
add_action( 'pre_get_posts', 'bdb_filter_businesses_by_location' );

/**
 * Filter businesses by distance after query
 */
function bdb_filter_posts_by_distance( $posts, $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'business_listing' ) ) {
		$user_lat = $query->get( 'user_latitude' );
		$user_lng = $query->get( 'user_longitude' );
		$max_distance = $query->get( 'max_distance' );
		
		if ( $user_lat && $user_lng && $max_distance && $max_distance < 999999 ) {
			$filtered_posts = array();
			
			foreach ( $posts as $post ) {
				$bus_lat = floatval( get_post_meta( $post->ID, '_business_latitude', true ) );
				$bus_lng = floatval( get_post_meta( $post->ID, '_business_longitude', true ) );
				
				if ( $bus_lat && $bus_lng ) {
					$distance = bdb_calculate_distance( $user_lat, $user_lng, $bus_lat, $bus_lng );
					
					if ( $distance <= $max_distance ) {
						// Store distance for sorting
						$post->distance = $distance;
						$filtered_posts[] = $post;
					}
				}
			}
			
			// Sort by distance
			usort( $filtered_posts, function( $a, $b ) {
				return $a->distance <=> $b->distance;
			} );
			
			return $filtered_posts;
		}
	}
	
	return $posts;
}
add_filter( 'the_posts', 'bdb_filter_posts_by_distance', 10, 2 );

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function bdb_calculate_distance( $lat1, $lon1, $lat2, $lon2 ) {
	$earth_radius = 3959; // miles
	
	$dLat = deg2rad( $lat2 - $lat1 );
	$dLon = deg2rad( $lon2 - $lon1 );
	
	$a = sin( $dLat / 2 ) * sin( $dLat / 2 ) +
		cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
		sin( $dLon / 2 ) * sin( $dLon / 2 );
	
	$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	
	return $earth_radius * $c;
}
