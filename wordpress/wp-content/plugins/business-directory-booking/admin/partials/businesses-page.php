<?php
/**
 * Businesses Management Page
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all businesses
$businesses = get_posts( array(
	'post_type'      => 'business_listing',
	'posts_per_page' => -1,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

global $wpdb;
?>

<div class="wrap">
	<h1>
		<?php echo esc_html( get_admin_page_title() ); ?>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=business_listing' ) ); ?>" class="page-title-action">Add New</a>
	</h1>

	<!-- Businesses Table -->
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th width="25%">Business Name</th>
				<th width="20%">Category</th>
				<th width="20%">Contact</th>
				<th width="15%">Bookings</th>
				<th width="20%">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( ! empty( $businesses ) ) {
				foreach ( $businesses as $business ) {
					// Get business metadata
					$phone = get_post_meta( $business->ID, '_business_phone', true );
					$email = get_post_meta( $business->ID, '_business_email', true );
					$address = get_post_meta( $business->ID, '_business_address', true );

					// Get category
					$categories = wp_get_post_terms( $business->ID, 'business_category' );
					$category_name = ! empty( $categories ) ? $categories[0]->name : 'Uncategorized';

					// Get booking count
					$booking_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE business_id = %d",
							$business->ID
						)
					);

					// Get booking statistics
					$confirmed_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE business_id = %d AND status = %s",
							$business->ID,
							'confirmed'
						)
					);
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $business->post_title ); ?></strong>
							<?php if ( 'publish' !== $business->post_status ) : ?>
								<br><small><?php echo esc_html( ucfirst( $business->post_status ) ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $category_name ); ?></td>
						<td>
							<?php if ( $phone ) : ?>
								<strong><?php echo esc_html( $phone ); ?></strong><br>
							<?php endif; ?>
							<?php if ( $email ) : ?>
								<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
							<?php endif; ?>
						</td>
						<td>
							<strong><?php echo esc_html( $booking_count ); ?></strong> total<br>
							<small><?php echo esc_html( $confirmed_count ); ?> confirmed</small>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $business->ID . '&action=edit' ) ); ?>" class="button button-small">Edit</a>
							<a href="<?php echo esc_url( get_permalink( $business->ID ) ); ?>" class="button button-small" target="_blank">View</a>
							<?php if ( current_user_can( 'delete_post', $business->ID ) ) : ?>
								<a href="<?php echo esc_url( get_delete_post_link( $business->ID, '', true ) ); ?>" class="button button-small button-link-delete">Delete</a>
							<?php endif; ?>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="5" style="text-align:center; padding: 20px;">
						<em><?php esc_html_e( 'No businesses yet.' ); ?></em>
						<br><br>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=business_listing' ) ); ?>" class="button button-primary">Add Your First Business</a>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<!-- Statistics -->
	<h2>Business Statistics</h2>
	<div class="bdb-stats-grid">
		<?php
		$total_businesses = count( $businesses );
		$total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bookings" );
		$avg_bookings = $total_businesses > 0 ? round( $total_bookings / $total_businesses, 1 ) : 0;
		?>

		<div class="bdb-stat-card">
			<div class="stat-icon">🏢</div>
			<div class="stat-content">
				<h3>Total Businesses</h3>
				<p class="stat-number"><?php echo esc_html( $total_businesses ); ?></p>
			</div>
		</div>

		<div class="bdb-stat-card">
			<div class="stat-icon">📊</div>
			<div class="stat-content">
				<h3>Total Bookings</h3>
				<p class="stat-number"><?php echo esc_html( $total_bookings ); ?></p>
			</div>
		</div>

		<div class="bdb-stat-card">
			<div class="stat-icon">📈</div>
			<div class="stat-content">
				<h3>Avg Bookings</h3>
				<p class="stat-number"><?php echo esc_html( $avg_bookings ); ?></p>
			</div>
		</div>
	</div>
</div>
