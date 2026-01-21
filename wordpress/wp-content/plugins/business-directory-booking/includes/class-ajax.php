<?php
/**
 * AJAX Handlers
 *
 * @package Business_Directory_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BDB_AJAX class
 */
class BDB_AJAX {

	/**
	 * Initialize AJAX handlers
	 */
	public function __construct() {
		// Admin AJAX
		add_action( 'wp_ajax_bdb_get_booking_details', array( $this, 'get_booking_details' ) );
		add_action( 'wp_ajax_bdb_delete_booking', array( $this, 'delete_booking' ) );
		add_action( 'wp_ajax_bdb_update_booking_status', array( $this, 'update_booking_status' ) );
		
		// Frontend AJAX
		add_action( 'wp_ajax_nopriv_bdb_submit_booking', array( $this, 'submit_booking' ) );
		add_action( 'wp_ajax_bdb_submit_booking', array( $this, 'submit_booking' ) );
		add_action( 'wp_ajax_nopriv_bdb_get_availability', array( $this, 'get_availability' ) );
		add_action( 'wp_ajax_bdb_get_availability', array( $this, 'get_availability' ) );
		add_action( 'wp_ajax_bdb_submit_review', array( $this, 'submit_review' ) );
	}

	/**
	 * Get booking details for modal
	 */
	public function get_booking_details() {
		check_ajax_referer( 'bdb_admin_nonce', 'nonce' );

		$booking_id = intval( $_POST['booking_id'] );
		global $wpdb;

		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bookings WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found' ) );
		}

		$business = get_post( $booking->business_id );
		$status = ucfirst( $booking->status );

		$html = sprintf(
			'<p><strong>Business:</strong> %s</p>
			<p><strong>Customer:</strong> %s</p>
			<p><strong>Email:</strong> <a href="mailto:%s">%s</a></p>
			<p><strong>Phone:</strong> %s</p>
			<p><strong>Date/Time:</strong> %s @ %s</p>
			<p><strong>Duration:</strong> %s minutes</p>
			<p><strong>Status:</strong> <span class="bdb-status bdb-status-%s">%s</span></p>
			<p><strong>Amount:</strong> $%s</p>
			<p><strong>Notes:</strong> %s</p>',
			esc_html( $business ? $business->post_title : 'N/A' ),
			esc_html( $booking->customer_name ),
			esc_attr( $booking->customer_email ),
			esc_html( $booking->customer_email ),
			esc_html( $booking->customer_phone ),
			esc_html( date_i18n( 'M d, Y', strtotime( $booking->booking_date ) ) ),
			esc_html( $booking->booking_time ),
			esc_html( $booking->duration_minutes ),
			esc_attr( $booking->status ),
			esc_html( $status ),
			esc_html( number_format( $booking->amount_paid, 2 ) ),
			esc_html( $booking->notes )
		);

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Delete booking
	 */
	public function delete_booking() {
		check_ajax_referer( 'bdb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$booking_id = intval( $_POST['booking_id'] );
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'bookings',
			array( 'id' => $booking_id ),
			array( '%d' )
		);

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Booking deleted' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Error deleting booking' ) );
		}
	}

	/**
	 * Update booking status
	 */
	public function update_booking_status() {
		check_ajax_referer( 'bdb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$booking_id = intval( $_POST['booking_id'] );
		$status = sanitize_text_field( $_POST['status'] );

		// Validate status
		$valid_statuses = array( 'pending', 'confirmed', 'completed', 'cancelled' );
		if ( ! in_array( $status, $valid_statuses ) ) {
			wp_send_json_error( array( 'message' => 'Invalid status' ) );
		}

		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'bookings',
			array( 
				'status' => $status,
				'updated_at' => current_time( 'mysql' )
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			// Send status update email
			require_once BDB_PLUGIN_PATH . 'includes/class-email-templates.php';
			$email = BDB_Email_Templates::status_update( $booking_id, $status );
			
			if ( $email ) {
				$booking = $wpdb->get_row( $wpdb->prepare(
					"SELECT customer_email FROM {$wpdb->prefix}bookings WHERE id = %d",
					$booking_id
				));
				
				if ( $booking ) {
					wp_mail( $booking->customer_email, $email['subject'], $email['message'], $email['headers'] );
				}
			}

			wp_send_json_success( array( 'message' => 'Status updated successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Error updating status' ) );
		}
	}

	/**
	 * Submit booking from frontend
	 */
	public function submit_booking() {
		check_ajax_referer( 'bdb_frontend_nonce', 'nonce' );

		global $wpdb;
		global $bdb_plugin;

		$business_id = intval( $_POST['business_id'] );
		$booking_date = sanitize_text_field( $_POST['booking_date'] );
		$booking_time = sanitize_text_field( $_POST['booking_time'] );
		$customer_name = sanitize_text_field( $_POST['customer_name'] );
		$customer_email = sanitize_email( $_POST['customer_email'] );
		$customer_phone = sanitize_text_field( $_POST['customer_phone'] );
		$duration = intval( $_POST['duration'] ?? $bdb_plugin->db->get_setting( 'booking_duration', 60 ) );
		$notes = sanitize_textarea_field( $_POST['notes'] ?? '' );

		// Validate
		if ( ! $business_id || ! $booking_date || ! $booking_time || ! $customer_name || ! $customer_email ) {
			wp_send_json_error( array( 'message' => 'Missing required fields' ) );
		}

		// Check if business exists
		if ( ! get_post( $business_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid business' ) );
		}

		// Insert booking
		$result = $wpdb->insert(
			$wpdb->prefix . 'bookings',
			array(
				'business_id'       => $business_id,
				'customer_name'     => $customer_name,
				'customer_email'    => $customer_email,
				'customer_phone'    => $customer_phone,
				'booking_date'      => $booking_date,
				'booking_time'      => $booking_time,
				'duration_minutes'  => $duration,
				'status'            => 'pending',
				'notes'             => $notes,
				'amount_paid'       => 0,
				'created_at'        => current_time( 'mysql' ),
				'updated_at'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s' )
		);

		if ( $result ) {
			$booking_id = $wpdb->insert_id;

			// Send confirmation email
			$this->send_booking_confirmation( $booking_id );

			wp_send_json_success( array(
				'message' => 'Booking submitted successfully!',
				'booking_id' => $booking_id,
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Error creating booking' ) );
		}
	}

	/**
	 * Get availability for a business
	 */
	public function get_availability() {
		check_ajax_referer( 'bdb_frontend_nonce', 'nonce' );

		$business_id = intval( $_POST['business_id'] );
		$booking_date = sanitize_text_field( $_POST['booking_date'] );

		global $wpdb;
		global $bdb_plugin;

		// Get business availability
		$day_of_week = date( 'w', strtotime( $booking_date ) );

		$availability = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}availability WHERE business_id = %d AND day_of_week = %d",
				$business_id,
				$day_of_week
			)
		);

		if ( ! $availability || ! $availability->is_available ) {
			wp_send_json_error( array( 'message' => 'Business not available on this date' ) );
		}

		// Get booked slots
		$booked = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT booking_time, duration_minutes FROM {$wpdb->prefix}bookings 
				 WHERE business_id = %d AND booking_date = %s AND status != %s",
				$business_id,
				$booking_date,
				'cancelled'
			)
		);

		// Generate available time slots
		$start_hour = intval( $availability->start_time );
		$end_hour = intval( $availability->end_time );
		$duration = $bdb_plugin->db->get_setting( 'booking_duration', 60 );
		$buffer = $bdb_plugin->db->get_setting( 'booking_buffer_time', 15 );

		$slots = array();
		for ( $hour = $start_hour; $hour < $end_hour; $hour++ ) {
			for ( $min = 0; $min < 60; $min += 30 ) {
				$slot_time = sprintf( '%02d:%02d', $hour, $min );
				$slot_end = date( 'H:i', strtotime( $slot_time ) + ( $duration + $buffer ) * 60 );

				// Check if slot is booked
				$is_booked = false;
				foreach ( $booked as $book ) {
					if ( $book->booking_time === $slot_time ) {
						$is_booked = true;
						break;
					}
				}

				if ( ! $is_booked ) {
					$slots[] = $slot_time;
				}
			}
		}

		wp_send_json_success( array( 'slots' => $slots ) );
	}

	/**
	 * Send booking confirmation email
	 */
	private function send_booking_confirmation( $booking_id ) {
		global $wpdb;

		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bookings WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			return;
		}

		// Prepare data for email template
		$booking_data = array(
			'business_id' => $booking->business_id,
			'booking_date' => $booking->booking_date,
			'booking_time' => $booking->booking_time,
			'duration' => $booking->duration_minutes,
			'customer_name' => $booking->customer_name,
			'customer_phone' => $booking->customer_phone,
		);

		// Use HTML email template
		require_once BDB_PLUGIN_PATH . 'includes/class-email-templates.php';
		$email = BDB_Email_Templates::booking_confirmation( $booking_data );

		wp_mail( $booking->customer_email, $email['subject'], $email['message'], $email['headers'] );
	}

	/**
	 * Submit review
	 */
	public function submit_review() {
		check_ajax_referer( 'bdb_review_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'You must be logged in to review' ) );
		}

		$business_id = intval( $_POST['business_id'] );
		$rating = intval( $_POST['rating'] );
		$review_title = sanitize_text_field( $_POST['review_title'] );
		$review_content = sanitize_textarea_field( $_POST['review_content'] );

		// Validate
		if ( ! $business_id || ! $rating || ! $review_title || ! $review_content ) {
			wp_send_json_error( array( 'message' => 'Missing required fields' ) );
		}

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => 'Invalid rating' ) );
		}

		// Check if business exists
		if ( ! get_post( $business_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid business' ) );
		}

		// Verify user has a completed booking
		$current_user = wp_get_current_user();
		global $wpdb;

		$booking_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}bookings 
				WHERE business_id = %d AND customer_email = %s AND status = %s",
				$business_id,
				$current_user->user_email,
				'completed'
			)
		);

		if ( $booking_count == 0 ) {
			wp_send_json_error( array( 'message' => 'You must have a completed booking to review' ) );
		}

		// Create review post
		$review_id = wp_insert_post( array(
			'post_type'    => 'review',
			'post_title'   => $review_title,
			'post_content' => $review_content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		) );

		if ( is_wp_error( $review_id ) ) {
			wp_send_json_error( array( 'message' => 'Error creating review' ) );
		}

		// Save review metadata
		update_post_meta( $review_id, '_business_id', $business_id );
		update_post_meta( $review_id, '_rating', $rating );
		update_post_meta( $review_id, '_reviewer_name', $current_user->display_name );
		update_post_meta( $review_id, '_reviewer_email', $current_user->user_email );

		wp_send_json_success( array(
			'message'   => 'Thank you for your review!',
			'review_id' => $review_id,
		) );
	}
}

