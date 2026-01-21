<?php
/**
 * Bookings Management Page
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Handle booking status update
if ( isset( $_POST['bdb_action'] ) && 'update_status' === $_POST['bdb_action'] ) {
	check_admin_referer( 'bdb_booking_nonce' );

	$booking_id = intval( $_POST['booking_id'] );
	$new_status = sanitize_text_field( $_POST['new_status'] );

	$wpdb->update(
		$wpdb->prefix . 'bookings',
		array( 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) ),
		array( 'id' => $booking_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	echo '<div class="notice notice-success"><p>Booking status updated.</p></div>';
}

// Get filter values
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$business_filter = isset( $_GET['business'] ) ? intval( $_GET['business'] ) : 0;

// Build WHERE clause
$where = '1=1';
if ( $status_filter ) {
	$where .= $wpdb->prepare( ' AND status = %s', $status_filter );
}
if ( $business_filter ) {
	$where .= $wpdb->prepare( ' AND business_id = %d', $business_filter );
}

// Get bookings
$bookings = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}bookings WHERE {$where} ORDER BY booking_date DESC, booking_time DESC"
);

// Get businesses for filter dropdown
$businesses = get_posts( array(
	'post_type'      => 'business_listing',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Filters -->
	<div class="bdb-filters">
		<form method="get" action="">
			<input type="hidden" name="page" value="bdb-bookings">

			<select name="status" onchange="this.form.submit();">
				<option value="">All Statuses</option>
				<option value="pending" <?php selected( $status_filter, 'pending' ); ?>>Pending</option>
				<option value="confirmed" <?php selected( $status_filter, 'confirmed' ); ?>>Confirmed</option>
				<option value="completed" <?php selected( $status_filter, 'completed' ); ?>>Completed</option>
				<option value="cancelled" <?php selected( $status_filter, 'cancelled' ); ?>>Cancelled</option>
			</select>

			<select name="business" onchange="this.form.submit();">
				<option value="">All Businesses</option>
				<?php foreach ( $businesses as $bid ) : ?>
					<option value="<?php echo esc_attr( $bid ); ?>" <?php selected( $business_filter, $bid ); ?>>
						<?php echo esc_html( get_the_title( $bid ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php if ( $status_filter || $business_filter ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bdb-bookings' ) ); ?>" class="button">Clear Filters</a>
			<?php endif; ?>
		</form>
	</div>

	<!-- Bookings Table -->
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th width="20%">Customer</th>
				<th width="20%">Business</th>
				<th width="20%">Date/Time</th>
				<th width="15%">Status</th>
				<th width="15%">Amount</th>
				<th width="10%">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( ! empty( $bookings ) ) {
				foreach ( $bookings as $booking ) {
					$business = get_post( $booking->business_id );
					$status_class = 'bdb-status-' . $booking->status;
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $booking->customer_name ); ?></strong><br>
							<small><?php echo esc_html( $booking->customer_email ); ?></small>
						</td>
						<td><?php echo esc_html( $business ? $business->post_title : 'N/A' ); ?></td>
						<td>
							<?php
							echo esc_html( date_i18n( 'M d, Y @ g:i A', strtotime( $booking->booking_date . ' ' . $booking->booking_time ) ) );
							?>
							<br>
							<small><?php echo esc_html( $booking->duration_minutes ); ?> mins</small>
						</td>
						<td>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'bdb_booking_nonce' ); ?>
								<input type="hidden" name="bdb_action" value="update_status">
								<input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->id ); ?>">

								<select name="new_status" onchange="this.form.submit();">
									<option value="pending" <?php selected( $booking->status, 'pending' ); ?>>Pending</option>
									<option value="confirmed" <?php selected( $booking->status, 'confirmed' ); ?>>Confirmed</option>
									<option value="completed" <?php selected( $booking->status, 'completed' ); ?>>Completed</option>
									<option value="cancelled" <?php selected( $booking->status, 'cancelled' ); ?>>Cancelled</option>
								</select>
							</form>
						</td>
						<td>
							<?php
							echo '$' . esc_html( number_format( $booking->amount_paid, 2 ) );
							if ( $booking->payment_intent_id ) {
								echo '<br><small>Stripe ID: ' . esc_html( substr( $booking->payment_intent_id, 0, 8 ) ) . '...</small>';
							}
							?>
						</td>
						<td>
							<button class="button button-small bdb-view-booking" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">View</button>
							<button class="button button-small button-link-delete bdb-delete-booking" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">Delete</button>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="6" style="text-align:center; padding: 20px;">
						<em><?php esc_html_e( 'No bookings found.' ); ?></em>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<!-- Modal for viewing booking details -->
<div id="bdb-booking-modal" style="display:none;" class="bdb-modal">
	<div class="bdb-modal-content">
		<span class="bdb-close">&times;</span>
		<h2>Booking Details</h2>
		<div id="bdb-booking-details"></div>
	</div>
</div>
