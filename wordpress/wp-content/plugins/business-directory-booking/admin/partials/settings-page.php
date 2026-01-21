<?php
/**
 * Settings Page
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form submission
if ( isset( $_POST['bdb_save_settings'] ) ) {
	check_admin_referer( 'bdb_settings_nonce' );

	// Get database handler
	global $bdb_plugin;
	$db = $bdb_plugin->db;

	// Save settings
	$settings = array(
		'booking_duration'     => intval( $_POST['booking_duration'] ?? 60 ),
		'booking_buffer_time'  => intval( $_POST['booking_buffer_time'] ?? 15 ),
		'require_payment'      => isset( $_POST['require_payment'] ) ? 1 : 0,
		'stripe_test_mode'     => isset( $_POST['stripe_test_mode'] ) ? 1 : 0,
		'stripe_publishable'   => sanitize_text_field( $_POST['stripe_publishable'] ?? '' ),
		'stripe_secret'        => sanitize_text_field( $_POST['stripe_secret'] ?? '' ),
		'google_maps_api_key'  => sanitize_text_field( $_POST['google_maps_api_key'] ?? '' ),
		'enable_reviews'       => isset( $_POST['enable_reviews'] ) ? 1 : 0,
		'enable_chatbot'       => isset( $_POST['enable_chatbot'] ) ? 1 : 0,
		'booking_auto_confirm' => isset( $_POST['booking_auto_confirm'] ) ? 1 : 0,
	);

	foreach ( $settings as $key => $value ) {
		$db->update_setting( $key, $value );
	}

	echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get database handler
global $bdb_plugin;
$db = $bdb_plugin->db;

// Get current settings
$booking_duration = $db->get_setting( 'booking_duration', 60 );
$booking_buffer = $db->get_setting( 'booking_buffer_time', 15 );
$require_payment = $db->get_setting( 'require_payment', 1 );
$stripe_test_mode = $db->get_setting( 'stripe_test_mode', 1 );
$stripe_publishable = $db->get_setting( 'stripe_publishable', '' );
$stripe_secret = $db->get_setting( 'stripe_secret', '' );
$google_maps_api_key = $db->get_setting( 'google_maps_api_key', '' );
$enable_reviews = $db->get_setting( 'enable_reviews', 1 );
$enable_chatbot = $db->get_setting( 'enable_chatbot', 1 );
$booking_auto_confirm = $db->get_setting( 'booking_auto_confirm', 0 );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" class="bdb-settings-form">
		<?php wp_nonce_field( 'bdb_settings_nonce' ); ?>

		<!-- Booking Settings -->
		<h2>Booking Settings</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="booking_duration">Default Booking Duration (minutes)</label></th>
				<td>
					<input type="number" id="booking_duration" name="booking_duration" value="<?php echo esc_attr( $booking_duration ); ?>" min="15" step="15">
					<p class="description">Default duration for new bookings</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="booking_buffer_time">Buffer Time Between Bookings (minutes)</label></th>
				<td>
					<input type="number" id="booking_buffer_time" name="booking_buffer_time" value="<?php echo esc_attr( $booking_buffer ); ?>" min="0" step="5">
					<p class="description">Time to buffer between bookings to prevent overbooking</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="booking_auto_confirm">Auto-Confirm Bookings</label></th>
				<td>
					<input type="checkbox" id="booking_auto_confirm" name="booking_auto_confirm" <?php checked( $booking_auto_confirm ); ?>>
					<p class="description">Automatically confirm bookings without admin review</p>
				</td>
			</tr>
		</table>

		<!-- Payment Settings -->
		<h2>Payment Settings</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="require_payment">Require Payment for Bookings</label></th>
				<td>
					<input type="checkbox" id="require_payment" name="require_payment" <?php checked( $require_payment ); ?>>
					<p class="description">Require customers to pay before confirming booking</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="stripe_test_mode">Stripe Test Mode</label></th>
				<td>
					<input type="checkbox" id="stripe_test_mode" name="stripe_test_mode" <?php checked( $stripe_test_mode ); ?>>
					<p class="description">Use Stripe test keys for development</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="stripe_publishable">Stripe Publishable Key</label></th>
				<td>
					<input type="text" id="stripe_publishable" name="stripe_publishable" value="<?php echo esc_attr( $stripe_publishable ); ?>" class="regular-text">
					<p class="description">Your Stripe publishable key (starts with pk_)</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="stripe_secret">Stripe Secret Key</label></th>
				<td>
					<input type="password" id="stripe_secret" name="stripe_secret" value="<?php echo esc_attr( $stripe_secret ); ?>" class="regular-text">
					<p class="description">Your Stripe secret key (starts with sk_) - Never share this publicly</p>
				</td>
			</tr>
		</table>

		<!-- Google Maps Integration -->
		<h2>Google Maps Integration</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="google_maps_api_key">Google Maps API Key</label></th>
				<td>
					<input type="text" id="google_maps_api_key" name="google_maps_api_key" value="<?php echo esc_attr( $google_maps_api_key ); ?>" class="regular-text">
					<p class="description">
						Enter your Google Maps API key to enable location features, maps, and "Near Me" search.
						<br><a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Get your API key here →</a>
					</p>
				</td>
			</tr>
		</table>

		<!-- Features -->
		<h2>Features</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="enable_reviews">Enable Customer Reviews</label></th>
				<td>
					<input type="checkbox" id="enable_reviews" name="enable_reviews" <?php checked( $enable_reviews ); ?>>
					<p class="description">Allow customers to leave reviews and ratings</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="enable_chatbot">Enable AI Chatbot</label></th>
				<td>
					<input type="checkbox" id="enable_chatbot" name="enable_chatbot" <?php checked( $enable_chatbot ); ?>>
					<p class="description">Enable AI chatbot assistance for customer inquiries</p>
				</td>
			</tr>
		</table>

		<!-- Submit -->
		<p>
			<button type="submit" name="bdb_save_settings" class="button button-primary">Save Settings</button>
		</p>
	</form>

	<!-- Quick Reference -->
	<h2>Quick Reference</h2>
	<div class="bdb-info-box">
		<h3>Stripe Setup</h3>
		<ol>
			<li>Go to <a href="https://stripe.com" target="_blank">stripe.com</a> and create an account</li>
			<li>In your Stripe dashboard, navigate to Developers → API Keys</li>
			<li>Copy your publishable and secret keys into the fields above</li>
			<li>For testing, use your test keys (toggle test mode in dashboard)</li>
			<li>Click "Save Settings" to apply</li>
		</ol>
	</div>
</div>
