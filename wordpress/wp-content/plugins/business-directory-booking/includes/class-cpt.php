<?php
/**
 * Custom Post Types and Taxonomies
 *
 * @package Business_Directory_Booking
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT Registration Class
 */
class BDB_CPT {

	/**
	 * Register all custom post types
	 */
	public function register_all() {
		$this->register_business_listing();
		$this->register_booking();
		$this->register_review();
	}

	/**
	 * Register business listing CPT
	 */
	private function register_business_listing() {
		register_post_type( 'business_listing', array(
			'labels'             => array(
				'name'               => esc_html_x( 'Business Listings', 'Post type general name', 'business-directory-booking' ),
				'singular_name'      => esc_html_x( 'Business', 'Post type singular name', 'business-directory-booking' ),
				'menu_name'          => esc_html_x( 'Directory', 'Admin Menu text', 'business-directory-booking' ),
				'name_admin_bar'     => esc_html_x( 'Business', 'Add New on Toolbar', 'business-directory-booking' ),
				'add_new'            => esc_html__( 'Add New', 'business-directory-booking' ),
				'add_new_item'       => esc_html__( 'Add New Business', 'business-directory-booking' ),
				'new_item'           => esc_html__( 'New Business', 'business-directory-booking' ),
				'edit_item'          => esc_html__( 'Edit Business', 'business-directory-booking' ),
				'view_item'          => esc_html__( 'View Business', 'business-directory-booking' ),
				'all_items'          => esc_html__( 'All Businesses', 'business-directory-booking' ),
				'search_items'       => esc_html__( 'Search Businesses', 'business-directory-booking' ),
				'not_found'          => esc_html__( 'No businesses found', 'business-directory-booking' ),
				'not_found_in_trash' => esc_html__( 'No businesses found in Trash', 'business-directory-booking' ),
			),
			'public'             => true,
			'hierarchical'       => false,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_nav_menus'  => true,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'businesses',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
			'rewrite'            => array( 'slug' => 'business', 'with_front' => false ),
			'menu_icon'          => 'dashicons-store',
			'capability_type'    => 'post',
		) );
	}

	/**
	 * Register booking CPT
	 */
	private function register_booking() {
		register_post_type( 'booking', array(
			'labels'             => array(
				'name'               => esc_html_x( 'Bookings', 'Post type general name', 'business-directory-booking' ),
				'singular_name'      => esc_html_x( 'Booking', 'Post type singular name', 'business-directory-booking' ),
				'menu_name'          => esc_html_x( 'Bookings', 'Admin Menu text', 'business-directory-booking' ),
				'add_new'            => esc_html__( 'Add New', 'business-directory-booking' ),
				'add_new_item'       => esc_html__( 'Add New Booking', 'business-directory-booking' ),
				'edit_item'          => esc_html__( 'Edit Booking', 'business-directory-booking' ),
				'view_item'          => esc_html__( 'View Booking', 'business-directory-booking' ),
				'all_items'          => esc_html__( 'All Bookings', 'business-directory-booking' ),
				'search_items'       => esc_html__( 'Search Bookings', 'business-directory-booking' ),
				'not_found'          => esc_html__( 'No bookings found', 'business-directory-booking' ),
				'not_found_in_trash' => esc_html__( 'No bookings found in Trash', 'business-directory-booking' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor', 'author', 'custom-fields' ),
			'rewrite'            => false,
			'menu_icon'          => 'dashicons-calendar-alt',
			'capability_type'    => 'post',
		) );
	}

	/**
	 * Register review CPT
	 */
	private function register_review() {
		register_post_type( 'review', array(
			'labels'             => array(
				'name'               => esc_html_x( 'Reviews', 'Post type general name', 'business-directory-booking' ),
				'singular_name'      => esc_html_x( 'Review', 'Post type singular name', 'business-directory-booking' ),
				'menu_name'          => esc_html_x( 'Reviews', 'Admin Menu text', 'business-directory-booking' ),
				'add_new'            => esc_html__( 'Add New', 'business-directory-booking' ),
				'add_new_item'       => esc_html__( 'Add New Review', 'business-directory-booking' ),
				'edit_item'          => esc_html__( 'Edit Review', 'business-directory-booking' ),
				'view_item'          => esc_html__( 'View Review', 'business-directory-booking' ),
				'all_items'          => esc_html__( 'All Reviews', 'business-directory-booking' ),
				'search_items'       => esc_html__( 'Search Reviews', 'business-directory-booking' ),
				'not_found'          => esc_html__( 'No reviews found', 'business-directory-booking' ),
				'not_found_in_trash' => esc_html__( 'No reviews found in Trash', 'business-directory-booking' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_nav_menus'  => true,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor', 'author', 'custom-fields' ),
			'rewrite'            => array( 'slug' => 'review', 'with_front' => false ),
			'menu_icon'          => 'dashicons-star-filled',
			'capability_type'    => 'post',
		) );
	}

	/**
	 * Register taxonomies
	 */
	public function register_taxonomies() {
		// Business categories
		register_taxonomy( 'business_category', array( 'business_listing' ), array(
			'hierarchical'      => true,
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'labels'            => array(
				'name'              => esc_html_x( 'Business Categories', 'taxonomy general name', 'business-directory-booking' ),
				'singular_name'     => esc_html_x( 'Category', 'taxonomy singular name', 'business-directory-booking' ),
				'search_items'      => esc_html__( 'Search Categories', 'business-directory-booking' ),
				'popular_items'     => esc_html__( 'Popular Categories', 'business-directory-booking' ),
				'all_items'         => esc_html__( 'All Categories', 'business-directory-booking' ),
				'edit_item'         => esc_html__( 'Edit Category', 'business-directory-booking' ),
				'update_item'       => esc_html__( 'Update Category', 'business-directory-booking' ),
				'add_new_item'      => esc_html__( 'Add New Category', 'business-directory-booking' ),
				'new_item_name'     => esc_html__( 'New Category Name', 'business-directory-booking' ),
				'back_to_items'     => esc_html__( 'Back to Categories', 'business-directory-booking' ),
			),
			'rewrite'           => array(
				'slug'       => 'business-category',
				'with_front' => false,
				'hierarchical' => true,
			),
		) );

		// Service types
		register_taxonomy( 'service_type', array( 'business_listing' ), array(
			'hierarchical'      => false,
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'labels'            => array(
				'name'              => esc_html_x( 'Service Types', 'taxonomy general name', 'business-directory-booking' ),
				'singular_name'     => esc_html_x( 'Service Type', 'taxonomy singular name', 'business-directory-booking' ),
				'search_items'      => esc_html__( 'Search Service Types', 'business-directory-booking' ),
				'popular_items'     => esc_html__( 'Popular Service Types', 'business-directory-booking' ),
				'all_items'         => esc_html__( 'All Service Types', 'business-directory-booking' ),
				'edit_item'         => esc_html__( 'Edit Service Type', 'business-directory-booking' ),
				'update_item'       => esc_html__( 'Update Service Type', 'business-directory-booking' ),
				'add_new_item'      => esc_html__( 'Add New Service Type', 'business-directory-booking' ),
				'new_item_name'     => esc_html__( 'New Service Type Name', 'business-directory-booking' ),
				'back_to_items'     => esc_html__( 'Back to Service Types', 'business-directory-booking' ),
			),
			'rewrite'           => array( 'slug' => 'service-type', 'with_front' => false ),
		) );

		// Booking status taxonomy
		register_taxonomy( 'booking_status', array( 'booking' ), array(
			'hierarchical'      => false,
			'public'            => false,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'labels'            => array(
				'name'          => esc_html_x( 'Booking Status', 'taxonomy general name', 'business-directory-booking' ),
				'singular_name' => esc_html_x( 'Status', 'taxonomy singular name', 'business-directory-booking' ),
				'all_items'     => esc_html__( 'All Statuses', 'business-directory-booking' ),
				'edit_item'     => esc_html__( 'Edit Status', 'business-directory-booking' ),
				'update_item'   => esc_html__( 'Update Status', 'business-directory-booking' ),
				'add_new_item'  => esc_html__( 'Add New Status', 'business-directory-booking' ),
				'new_item_name' => esc_html__( 'New Status Name', 'business-directory-booking' ),
			),
			'rewrite'           => false,
		) );
	}
}
