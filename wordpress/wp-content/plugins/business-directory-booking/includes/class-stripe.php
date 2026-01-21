<?php
/**
 * Stripe Payment Handler
 *
 * @package Business_Directory_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BDB_Stripe class
 */
class BDB_Stripe {

	/**
	 * Database handler
	 *
	 * @var BDB_Database
	 */
	private $db;

	/**
	 * Initialize Stripe handler
	 *
	 * @param BDB_Database $db Database handler.
	 */
	public function __construct( $db ) {
		$this->db = $db;

		// AJAX endpoints
		add_action( 'wp_ajax_nopriv_bdb_create_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_bdb_create_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_bdb_confirm_payment', array( $this, 'confirm_payment' ) );
		add_action( 'wp_ajax_bdb_confirm_payment', array( $this, 'confirm_payment' ) );
	}

	/**
	 * Get Stripe instance
	 */
	private function get_stripe() {
		// Check if Stripe library is installed
		if ( ! class_exists( 'Stripe\Stripe' ) ) {
			return null;
		}

		$secret_key = $this->db->get_setting( 'stripe_secret', '' );
		if ( ! $secret_key ) {
			return null;
		}

		\Stripe\Stripe::setApiKey( $secret_key );
		return true;
	}

	/**
	 * Create payment intent
	 */
	public function create_payment_intent() {
		check_ajax_referer( 'bdb_frontend_nonce', 'nonce' );

		if ( ! $this->get_stripe() ) {
			wp_send_json_error( array( 'message' => 'Payment system not configured' ) );
		}

		$booking_id = intval( $_POST['booking_id'] );
		$amount = floatval( $_POST['amount'] );

		global $wpdb;

		// Verify booking exists
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bookings WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found' ) );
		}

		try {
			// Create payment intent
			$intent = \Stripe\PaymentIntent::create( array(
				'amount'      => intval( $amount * 100 ), // Amount in cents
				'currency'    => 'usd',
				'description' => sprintf( 'Booking #%d', $booking_id ),
				'metadata'    => array(
					'booking_id' => $booking_id,
				),
			) );

			// Update booking with payment intent ID
			$wpdb->update(
				$wpdb->prefix . 'bookings',
				array( 'payment_intent_id' => $intent->id ),
				array( 'id' => $booking_id ),
				array( '%s' ),
				array( '%d' )
			);

			wp_send_json_success( array(
				'client_secret' => $intent->client_secret,
				'payment_intent_id' => $intent->id,
			) );
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			wp_send_json_error( array(
				'message' => 'Error creating payment: ' . $e->getMessage(),
			) );
		}
	}

	/**
	 * Confirm payment
	 */
	public function confirm_payment() {
		check_ajax_referer( 'bdb_frontend_nonce', 'nonce' );

		if ( ! $this->get_stripe() ) {
			wp_send_json_error( array( 'message' => 'Payment system not configured' ) );
		}

		$booking_id = intval( $_POST['booking_id'] );
		$payment_intent_id = sanitize_text_field( $_POST['payment_intent_id'] );

		global $wpdb;

		try {
			// Retrieve payment intent
			$intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );

			if ( 'succeeded' === $intent->status ) {
				// Payment successful - update booking
				$wpdb->update(
					$wpdb->prefix . 'bookings',
					array(
						'status'       => 'confirmed',
						'amount_paid'  => $intent->amount / 100,
						'updated_at'   => current_time( 'mysql' ),
					),
					array( 'id' => $booking_id ),
					array( '%s', '%f', '%s' ),
					array( '%d' )
				);

				wp_send_json_success( array(
					'message' => 'Payment successful!',
					'booking_id' => $booking_id,
				) );
			} else {
				wp_send_json_error( array(
					'message' => 'Payment status: ' . $intent->status,
				) );
			}
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			wp_send_json_error( array(
				'message' => 'Error confirming payment: ' . $e->getMessage(),
			) );
		}
	}

	/**
	 * Get publishable key for frontend
	 */
	public function get_publishable_key() {
		return $this->db->get_setting( 'stripe_publishable', '' );
	}

	/**
	 * Check if Stripe is configured
	 */
	public function is_configured() {
		$publishable = $this->db->get_setting( 'stripe_publishable', '' );
		$secret = $this->db->get_setting( 'stripe_secret', '' );
		return ! empty( $publishable ) && ! empty( $secret );
	}
}
