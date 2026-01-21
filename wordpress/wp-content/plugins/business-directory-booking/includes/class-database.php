<?php
/**
 * Database Handler
 *
 * @package Business_Directory_Booking
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Class - Manages custom tables
 */
class BDB_Database {

	/**
	 * Create custom tables on plugin activation
	 */
	public function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Bookings table
		$bookings_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bookings (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				business_id BIGINT(20) NOT NULL,
				customer_name VARCHAR(255) NOT NULL,
				customer_email VARCHAR(255) NOT NULL,
				customer_phone VARCHAR(20),
				booking_date DATE NOT NULL,
				booking_time TIME NOT NULL,
				duration_minutes INT DEFAULT 60,
				status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
				notes LONGTEXT,
				amount_paid DECIMAL(10, 2) DEFAULT 0.00,
				payment_intent_id VARCHAR(255),
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY business_id (business_id),
				KEY booking_date (booking_date),
				KEY status (status),
				CONSTRAINT fk_business_id FOREIGN KEY (business_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
			) $charset_collate;
		";

		// Availability table
		$availability_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}availability (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				business_id BIGINT(20) NOT NULL,
				day_of_week INT DEFAULT 0,
				start_time TIME,
				end_time TIME,
				is_available BOOLEAN DEFAULT TRUE,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY business_id (business_id),
				KEY day_of_week (day_of_week),
				CONSTRAINT fk_availability_business_id FOREIGN KEY (business_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
			) $charset_collate;
		";

		// Settings table (optional for plugin settings)
		$settings_table = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bdb_settings (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				setting_key VARCHAR(255) NOT NULL UNIQUE,
				setting_value LONGTEXT,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY setting_key (setting_key)
			) $charset_collate;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Execute table creation
		dbDelta( $bookings_table );
		dbDelta( $availability_table );
		dbDelta( $settings_table );

		// Initialize default settings
		$this->init_settings();
	}

	/**
	 * Initialize default settings
	 */
	private function init_settings() {
		global $wpdb;

		$default_settings = array(
			'booking_duration'     => 60,
			'booking_buffer_time'  => 15,
			'require_payment'      => 1,
			'stripe_test_mode'     => 1,
			'enable_reviews'       => 1,
			'enable_chatbot'       => 1,
			'booking_auto_confirm' => 0,
		);

		foreach ( $default_settings as $key => $value ) {
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT setting_value FROM {$wpdb->prefix}bdb_settings WHERE setting_key = %s",
				$key
			) );

			if ( $existing === null ) {
				$wpdb->insert(
					"{$wpdb->prefix}bdb_settings",
					array(
						'setting_key'   => $key,
						'setting_value' => $value,
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Get setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $default Default value if not found
	 * @return mixed Setting value
	 */
	public static function get_setting( $key, $default = null ) {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare(
			"SELECT setting_value FROM {$wpdb->prefix}bdb_settings WHERE setting_key = %s",
			$key
		) );

		return $value !== null ? $value : $default;
	}

	/**
	 * Update setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $value Setting value
	 * @return bool True on success, false on failure
	 */
	public static function update_setting( $key, $value ) {
		global $wpdb;

		$result = $wpdb->update(
			"{$wpdb->prefix}bdb_settings",
			array( 'setting_value' => $value ),
			array( 'setting_key' => $key ),
			array( '%s' ),
			array( '%s' )
		);

		return $result !== false;
	}

	/**
	 * Insert booking
	 *
	 * @param array $booking_data Booking data
	 * @return int|false Booking ID or false on failure
	 */
	public static function insert_booking( $booking_data ) {
		global $wpdb;

		$defaults = array(
			'business_id'     => 0,
			'customer_name'   => '',
			'customer_email'  => '',
			'customer_phone'  => '',
			'booking_date'    => current_time( 'Y-m-d' ),
			'booking_time'    => '09:00:00',
			'duration_minutes' => 60,
			'status'          => 'pending',
			'notes'           => '',
			'amount_paid'     => 0.00,
			'payment_intent_id' => '',
		);

		$data = wp_parse_args( $booking_data, $defaults );

		$result = $wpdb->insert(
			"{$wpdb->prefix}bookings",
			array(
				'business_id'        => $data['business_id'],
				'customer_name'      => $data['customer_name'],
				'customer_email'     => $data['customer_email'],
				'customer_phone'     => $data['customer_phone'],
				'booking_date'       => $data['booking_date'],
				'booking_time'       => $data['booking_time'],
				'duration_minutes'   => $data['duration_minutes'],
				'status'             => $data['status'],
				'notes'              => $data['notes'],
				'amount_paid'        => $data['amount_paid'],
				'payment_intent_id'  => $data['payment_intent_id'],
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get booking by ID
	 *
	 * @param int $booking_id Booking ID
	 * @return object|null Booking object or null
	 */
	public static function get_booking( $booking_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}bookings WHERE id = %d",
			$booking_id
		) );
	}

	/**
	 * Get bookings for business
	 *
	 * @param int $business_id Business ID
	 * @param array $args Query arguments
	 * @return array Array of booking objects
	 */
	public static function get_bookings( $business_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => null,
			'date'    => null,
			'limit'   => 50,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$query = "SELECT * FROM {$wpdb->prefix}bookings WHERE business_id = %d";
		$params = array( $business_id );

		if ( $args['status'] ) {
			$query  .= " AND status = %s";
			$params[] = $args['status'];
		}

		if ( $args['date'] ) {
			$query  .= " AND booking_date = %s";
			$params[] = $args['date'];
		}

		$query .= " ORDER BY booking_date DESC, booking_time DESC LIMIT %d OFFSET %d";
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}

	/**
	 * Update booking status
	 *
	 * @param int    $booking_id Booking ID
	 * @param string $status New status
	 * @return bool True on success, false on failure
	 */
	public static function update_booking_status( $booking_id, $status ) {
		global $wpdb;

		$valid_statuses = array( 'pending', 'confirmed', 'completed', 'cancelled' );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			"{$wpdb->prefix}bookings",
			array( 'status' => $status ),
			array( 'id' => $booking_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}
}
