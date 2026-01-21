<?php
/**
 * Main Plugin Class
 *
 * @package Business_Directory_Booking
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class BDB_Plugin {

	/**
	 * Database handler
	 *
	 * @var BDB_Database
	 */
	public $db;

	/**
	 * Stripe handler
	 *
	 * @var BDB_Stripe
	 */
	public $stripe;

	/**
	 * Run plugin
	 */
	public function run() {
		// Initialize database handler
		$this->db = new BDB_Database();
		
		// Initialize Stripe handler
		$this->stripe = new BDB_Stripe( $this->db );

		// Register hooks
		add_action( 'init', array( $this, 'register_cpts' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		
		// Admin pages (only in admin)
		if ( is_admin() ) {
			new BDB_Admin_Pages();
		}
		
		// Initialize AJAX handlers
		new BDB_AJAX();
		
		// Activation/Deactivation
		register_activation_hook( BDB_PLUGIN_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( BDB_PLUGIN_BASENAME, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create custom tables
		$this->db->create_tables();
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Seed initial data
		$this->seed_initial_data();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Register custom post types
	 */
	public function register_cpts() {
		$cpt = new BDB_CPT();
		$cpt->register_all();
	}

	/**
	 * Register taxonomies
	 */
	public function register_taxonomies() {
		$cpt = new BDB_CPT();
		$cpt->register_taxonomies();
	}

	/**
	 * Seed initial data
	 */
	private function seed_initial_data() {
		// Create sample business categories
		$categories = array(
			array(
				'name'        => 'Plumbing',
				'slug'        => 'plumbing',
				'description' => 'Plumbing services',
			),
			array(
				'name'        => 'Electrical',
				'slug'        => 'electrical',
				'description' => 'Electrical services',
			),
			array(
				'name'        => 'Hair & Salon',
				'slug'        => 'salon',
				'description' => 'Hair and beauty salon services',
			),
			array(
				'name'        => 'Photography',
				'slug'        => 'photography',
				'description' => 'Photography services',
			),
			array(
				'name'        => 'Fitness',
				'slug'        => 'fitness',
				'description' => 'Gym and fitness services',
			),
		);

		foreach ( $categories as $cat ) {
			// Check if category exists
			if ( ! term_exists( $cat['slug'], 'business_category' ) ) {
				wp_insert_term(
					$cat['name'],
					'business_category',
					array(
						'slug'        => $cat['slug'],
						'description' => $cat['description'],
					)
				);
			}
		}

		// Create sample businesses
		$sample_businesses = array(
			array(
				'title'       => 'Quick Plumbing Solutions',
				'category'    => 'plumbing',
				'description' => 'Professional plumbing services for residential and commercial properties.',
				'phone'       => '+63 917-123-4567',
				'email'       => 'contact@quickplumbing.local',
				'address'     => '123 Main St, Business City',
			),
			array(
				'title'       => 'Bright Electrical Services',
				'category'    => 'electrical',
				'description' => 'Experienced electrical contractors providing safe and reliable solutions.',
				'phone'       => '+63 918-234-5678',
				'email'       => 'info@brightelectric.local',
				'address'     => '456 Oak Ave, Business City',
			),
			array(
				'title'       => 'Glamour Hair Studio',
				'category'    => 'salon',
				'description' => 'Modern salon offering haircuts, styling, and beauty treatments.',
				'phone'       => '+63 919-345-6789',
				'email'       => 'hello@glamourhair.local',
				'address'     => '789 Elm St, Business City',
			),
		);

		foreach ( $sample_businesses as $business ) {
			// Check if business already exists
			$existing = get_posts( array(
				'post_type'      => 'business_listing',
				'post_title'     => $business['title'],
				'posts_per_page' => 1,
			) );

			if ( empty( $existing ) ) {
				$post_id = wp_insert_post( array(
					'post_type'    => 'business_listing',
					'post_title'   => $business['title'],
					'post_content' => $business['description'],
					'post_status'  => 'publish',
				) );

				if ( $post_id ) {
					// Set category
					wp_set_post_terms( $post_id, $business['category'], 'business_category' );

					// Add metadata
					update_post_meta( $post_id, '_business_phone', $business['phone'] );
					update_post_meta( $post_id, '_business_email', $business['email'] );
					update_post_meta( $post_id, '_business_address', $business['address'] );
				}
			}
		}
	}
}
