<?php
/**
 * Dashboard Page Template
 *
 * @package Business_Directory_Booking
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get stats
global $wpdb;

$total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bookings" );
$pending_bookings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE status = %s", 'pending' ) );
$confirmed_bookings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE status = %s", 'confirmed' ) );
$completed_bookings = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE status = %s", 'completed' ) );
$total_businesses = wp_count_posts( 'business_listing' )->publish ?? 0;
$total_revenue = $wpdb->get_var( "SELECT SUM(amount_paid) FROM {$wpdb->prefix}bookings WHERE status IN ('confirmed', 'completed')" ) ?? 0;

// Get today's bookings
$today_bookings = $wpdb->get_var( $wpdb->prepare( 
	"SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE booking_date = %s", 
	date('Y-m-d') 
) );

// Get this month's revenue
$month_revenue = $wpdb->get_var( $wpdb->prepare(
	"SELECT SUM(amount_paid) FROM {$wpdb->prefix}bookings 
	WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d 
	AND status IN ('confirmed', 'completed')",
	date('n'),
	date('Y')
) ) ?? 0;

// Get bookings by status for chart
$status_counts = $wpdb->get_results(
	"SELECT status, COUNT(*) as count FROM {$wpdb->prefix}bookings GROUP BY status"
);

// Get last 7 days bookings for chart
$daily_bookings = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT DATE(created_at) as date, COUNT(*) as count 
		FROM {$wpdb->prefix}bookings 
		WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
		GROUP BY DATE(created_at)
		ORDER BY date ASC"
	)
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Stats Cards -->
	<div class="bdb-stats-grid">
		<div class="bdb-stat-card stat-primary">
			<div class="stat-icon">📊</div>
			<div class="stat-content">
				<h3>Total Bookings</h3>
				<p class="stat-number"><?php echo esc_html( number_format($total_bookings) ); ?></p>
				<span class="stat-label"><?php echo esc_html($today_bookings); ?> today</span>
			</div>
		</div>

		<div class="bdb-stat-card stat-warning">
			<div class="stat-icon">⏳</div>
			<div class="stat-content">
				<h3>Pending</h3>
				<p class="stat-number"><?php echo esc_html( number_format($pending_bookings) ); ?></p>
				<span class="stat-label">Need attention</span>
			</div>
		</div>

		<div class="bdb-stat-card stat-success">
			<div class="stat-icon">💰</div>
			<div class="stat-content">
				<h3>Revenue</h3>
				<p class="stat-number">$<?php echo esc_html( number_format($total_revenue, 2) ); ?></p>
				<span class="stat-label">$<?php echo esc_html( number_format($month_revenue, 2) ); ?> this month</span>
			</div>
		</div>

		<div class="bdb-stat-card stat-info">
			<div class="stat-icon">🏢</div>
			<div class="stat-content">
				<h3>Businesses</h3>
				<p class="stat-number"><?php echo esc_html( number_format($total_businesses) ); ?></p>
				<span class="stat-label">Active listings</span>
			</div>
		</div>
	</div>

	<!-- Charts Section -->
	<div class="bdb-charts-row">
		<div class="bdb-chart-container">
			<h2>Bookings Last 7 Days</h2>
			<canvas id="bookingsChart"></canvas>
		</div>

		<div class="bdb-chart-container">
			<h2>Status Distribution</h2>
			<canvas id="statusChart"></canvas>
		</div>
	</div>

	<!-- Quick Links -->
	<div class="bdb-quick-actions">
		<h2>Quick Actions</h2>
		<div class="bdb-action-buttons">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bdb-bookings' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-calendar-alt"></span> Manage Bookings
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bdb-businesses' ) ); ?>" class="button">
				<span class="dashicons dashicons-store"></span> Manage Businesses
			</a>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=business_listing' ) ); ?>" class="button">
				<span class="dashicons dashicons-plus-alt"></span> Add New Business
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bdb-settings' ) ); ?>" class="button">
				<span class="dashicons dashicons-admin-settings"></span> Settings
			</a>
		</div>
	</div>

	<!-- Recent Bookings -->
	<div class="bdb-recent-bookings">
		<h2>Recent Bookings</h2>
		<?php
		$recent_bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bookings ORDER BY created_at DESC LIMIT %d",
				10
			)
		);

		if ( ! empty( $recent_bookings ) ) {
			?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Business</th>
						<th>Customer</th>
						<th>Date/Time</th>
						<th>Status</th>
						<th>Amount</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_bookings as $booking ) : ?>
						<tr>
							<td><strong>#<?php echo esc_html($booking->id); ?></strong></td>
							<td>
								<?php
								$business = get_post( $booking->business_id );
								echo esc_html( $business ? $business->post_title : 'N/A' );
								?>
							</td>
							<td>
								<strong><?php echo esc_html( $booking->customer_name ); ?></strong><br>
								<small><?php echo esc_html( $booking->customer_email ); ?></small>
							</td>
							<td>
								<?php
								echo esc_html( date_i18n( 'M d, Y', strtotime( $booking->booking_date ) ) );
								?><br>
								<small><?php echo esc_html( date('g:i A', strtotime($booking->booking_time)) ); ?></small>
							</td>
							<td>
								<span class="bdb-status bdb-status-<?php echo esc_attr( $booking->status ); ?>">
									<?php echo esc_html( ucfirst( $booking->status ) ); ?>
								</span>
							</td>
							<td>
								<strong>$<?php echo esc_html( number_format( $booking->amount_paid, 2 ) ); ?></strong>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=bdb-bookings' ) ); ?>" class="button button-small">View</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		} else {
			echo '<div class="bdb-empty-state">
				<div class="empty-icon">📅</div>
				<h3>No bookings yet</h3>
				<p>Bookings will appear here once customers start booking.</p>
			</div>';
		}
		?>
	</div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
jQuery(document).ready(function($) {
	// Bookings Chart
	const bookingsCtx = document.getElementById('bookingsChart');
	if (bookingsCtx) {
		const bookingsData = <?php 
			$dates = array();
			$counts = array();
			for($i = 6; $i >= 0; $i--) {
				$date = date('Y-m-d', strtotime("-$i days"));
				$dates[] = date('M d', strtotime($date));
				$count = 0;
				foreach($daily_bookings as $booking) {
					if($booking->date === $date) {
						$count = $booking->count;
						break;
					}
				}
				$counts[] = $count;
			}
			echo json_encode(array('dates' => $dates, 'counts' => $counts));
		?>;
		
		new Chart(bookingsCtx, {
			type: 'line',
			data: {
				labels: bookingsData.dates,
				datasets: [{
					label: 'Bookings',
					data: bookingsData.counts,
					borderColor: '#2271b1',
					backgroundColor: 'rgba(34, 113, 177, 0.1)',
					tension: 0.4,
					fill: true
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false }
				},
				scales: {
					y: { beginAtZero: true, ticks: { stepSize: 1 } }
				}
			}
		});
	}

	// Status Chart
	const statusCtx = document.getElementById('statusChart');
	if (statusCtx) {
		const statusData = <?php
			$statuses = array('pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0);
			foreach($status_counts as $status) {
				$statuses[$status->status] = $status->count;
			}
			echo json_encode(array_values($statuses));
		?>;
		
		new Chart(statusCtx, {
			type: 'doughnut',
			data: {
				labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
				datasets: [{
					data: statusData,
					backgroundColor: [
						'#f0ad4e',
						'#5cb85c',
						'#0073aa',
						'#dc3545'
					]
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false
			}
		});
	}
});
</script>

<style>
.bdb-stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.bdb-stat-card {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 24px;
	display: flex;
	align-items: center;
	gap: 16px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.05);
	transition: transform 0.2s, box-shadow 0.2s;
}

.bdb-stat-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bdb-stat-card.stat-primary { border-left: 4px solid #2271b1; }
.bdb-stat-card.stat-warning { border-left: 4px solid #f0ad4e; }
.bdb-stat-card.stat-success { border-left: 4px solid #5cb85c; }
.bdb-stat-card.stat-info { border-left: 4px solid #00a0d2; }

.stat-icon {
	font-size: 48px;
	line-height: 1;
}

.stat-content h3 {
	margin: 0 0 8px 0;
	font-size: 14px;
	color: #666;
	font-weight: 500;
	text-transform: uppercase;
}

.stat-number {
	font-size: 32px;
	font-weight: 700;
	margin: 0;
	color: #1d2327;
}

.stat-label {
	font-size: 13px;
	color: #999;
}

.bdb-charts-row {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 20px;
	margin: 30px 0;
}

.bdb-chart-container {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 24px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.bdb-chart-container h2 {
	margin: 0 0 20px 0;
	font-size: 18px;
	color: #1d2327;
}

.bdb-chart-container canvas {
	max-height: 250px;
}

.bdb-quick-actions {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 24px;
	margin: 20px 0;
}

.bdb-quick-actions h2 {
	margin: 0 0 16px 0;
	font-size: 18px;
}

.bdb-action-buttons {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
}

.bdb-action-buttons .button {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.bdb-recent-bookings {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 24px;
	margin: 20px 0;
}

.bdb-recent-bookings h2 {
	margin: 0 0 16px 0;
	font-size: 18px;
}

.bdb-status {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
}

.bdb-status-pending {
	background: #fff3cd;
	color: #856404;
}

.bdb-status-confirmed {
	background: #d4edda;
	color: #155724;
}

.bdb-status-completed {
	background: #d1ecf1;
	color: #0c5460;
}

.bdb-status-cancelled {
	background: #f8d7da;
	color: #721c24;
}

.bdb-empty-state {
	text-align: center;
	padding: 60px 20px;
}

.empty-icon {
	font-size: 64px;
	margin-bottom: 16px;
}

.bdb-empty-state h3 {
	margin: 0 0 8px 0;
	color: #1d2327;
}

.bdb-empty-state p {
	color: #666;
	margin: 0;
}
</style>
