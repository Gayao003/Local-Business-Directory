<?php
/**
 * Customer Dashboard Template
 */

if (!is_user_logged_in()) {
	wp_redirect(wp_login_url(get_permalink()));
	exit;
}

get_header();

$current_user = wp_get_current_user();
?>

<main class="customer-dashboard">
	<div class="container">
		
		<header class="dashboard-header">
			<h1>Welcome, <?php echo esc_html($current_user->display_name); ?>!</h1>
			<p>Manage your bookings and reviews</p>
		</header>

		<div class="dashboard-tabs">
			<button class="tab-btn active" data-tab="bookings">My Bookings</button>
			<button class="tab-btn" data-tab="reviews">My Reviews</button>
			<button class="tab-btn" data-tab="favorites">Favorites</button>
			<button class="tab-btn" data-tab="account">Account Settings</button>
		</div>

		<!-- My Bookings Tab -->
		<div class="tab-content active" id="bookings-tab">
			<h2>My Bookings</h2>
			<?php
			global $wpdb;
			$bookings = $wpdb->get_results($wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}bookings WHERE customer_email = %s ORDER BY created_at DESC",
				$current_user->user_email
			));

			if ($bookings) :
			?>
				<div class="bookings-list">
					<?php foreach ($bookings as $booking) : 
						$business = get_post($booking->business_id);
						$status_class = 'status-' . $booking->status;
					?>
						<div class="booking-card <?php echo esc_attr($status_class); ?>">
							<div class="booking-header">
								<h3><?php echo esc_html($business->post_title); ?></h3>
								<span class="status-badge <?php echo esc_attr($status_class); ?>">
									<?php echo ucfirst($booking->status); ?>
								</span>
							</div>
							<div class="booking-details">
								<div class="detail-row">
									<strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking->booking_date)); ?>
								</div>
								<div class="detail-row">
									<strong>Time:</strong> <?php echo date('g:i A', strtotime($booking->booking_time)); ?>
								</div>
								<div class="detail-row">
									<strong>Duration:</strong> <?php echo $booking->duration; ?> minutes
								</div>
								<?php if ($booking->payment_amount > 0) : ?>
								<div class="detail-row">
									<strong>Amount:</strong> $<?php echo number_format($booking->payment_amount, 2); ?>
								</div>
								<?php endif; ?>
							</div>
							<div class="booking-actions">
								<?php if ($booking->status === 'confirmed') : ?>
									<a href="<?php echo get_permalink($booking->business_id); ?>" class="btn-secondary">View Business</a>
								<?php endif; ?>
								<?php if ($booking->status === 'completed' && !has_user_reviewed($current_user->ID, $booking->business_id)) : ?>
									<a href="<?php echo get_permalink($booking->business_id); ?>#reviews" class="btn-primary">Leave Review</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<p>You haven't made any bookings yet.</p>
					<a href="<?php echo get_post_type_archive_link('business_listing'); ?>" class="btn-primary">Browse Businesses</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- My Reviews Tab -->
		<div class="tab-content" id="reviews-tab">
			<h2>My Reviews</h2>
			<?php
			$reviews = new WP_Query(array(
				'post_type' => 'review',
				'author' => $current_user->ID,
				'posts_per_page' => -1,
				'orderby' => 'date',
				'order' => 'DESC'
			));

			if ($reviews->have_posts()) :
			?>
				<div class="reviews-list">
					<?php while ($reviews->have_posts()) : $reviews->the_post();
						$business_id = get_post_meta(get_the_ID(), '_business_id', true);
						$business = get_post($business_id);
						$rating = get_post_meta(get_the_ID(), '_rating', true);
					?>
						<div class="review-card">
							<div class="review-header">
								<h3><?php echo esc_html($business->post_title); ?></h3>
								<div class="stars">
									<?php for ($i = 1; $i <= 5; $i++) : ?>
										<span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
									<?php endfor; ?>
								</div>
							</div>
							<div class="review-content">
								<h4><?php the_title(); ?></h4>
								<?php the_content(); ?>
							</div>
							<div class="review-meta">
								<small>Posted on <?php echo get_the_date(); ?></small>
							</div>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<div class="empty-state">
					<p>You haven't written any reviews yet.</p>
					<p>Complete a booking to leave a review!</p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Favorites Tab -->
		<div class="tab-content" id="favorites-tab">
			<h2>Favorite Businesses</h2>
			<div class="empty-state">
				<p>Favorites feature coming soon!</p>
				<p>You'll be able to save your favorite businesses here.</p>
			</div>
		</div>

		<!-- Account Settings Tab -->
		<div class="tab-content" id="account-tab">
			<h2>Account Settings</h2>
			<div class="account-info">
				<div class="info-row">
					<strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?>
				</div>
				<div class="info-row">
					<strong>Username:</strong> <?php echo esc_html($current_user->user_login); ?>
				</div>
				<div class="info-row">
					<strong>Member since:</strong> <?php echo date('F Y', strtotime($current_user->user_registered)); ?>
				</div>
			</div>
			<div class="account-actions">
				<a href="<?php echo admin_url('profile.php'); ?>" class="btn-secondary">Edit Profile</a>
				<a href="<?php echo wp_logout_url(home_url()); ?>" class="btn-outline">Logout</a>
			</div>
		</div>

	</div>
</main>

<style>
	.customer-dashboard {
		padding: 40px 20px;
		background: #f5f5f5;
		min-height: 70vh;
	}
	.dashboard-header {
		background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
		color: #fff;
		padding: 40px;
		border-radius: 12px;
		text-align: center;
		margin-bottom: 30px;
	}
	.dashboard-header h1 {
		margin: 0 0 10px 0;
		font-size: 2.5em;
	}
	.dashboard-tabs {
		display: flex;
		gap: 10px;
		margin-bottom: 30px;
		flex-wrap: wrap;
	}
	.tab-btn {
		padding: 12px 24px;
		background: #fff;
		border: 2px solid #ddd;
		border-radius: 8px;
		cursor: pointer;
		font-weight: 600;
		transition: all 0.3s;
	}
	.tab-btn.active {
		background: #2271b1;
		color: #fff;
		border-color: #2271b1;
	}
	.tab-content {
		display: none;
		background: #fff;
		padding: 30px;
		border-radius: 12px;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
	}
	.tab-content.active {
		display: block;
	}
	.bookings-list, .reviews-list {
		display: grid;
		gap: 20px;
	}
	.booking-card, .review-card {
		background: #f9f9f9;
		border-left: 4px solid #2271b1;
		padding: 20px;
		border-radius: 8px;
	}
	.booking-card.status-pending {
		border-left-color: #f0ad4e;
	}
	.booking-card.status-confirmed {
		border-left-color: #5cb85c;
	}
	.booking-card.status-completed {
		border-left-color: #5bc0de;
	}
	.booking-card.status-cancelled {
		border-left-color: #d9534f;
	}
	.booking-header, .review-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 15px;
	}
	.booking-header h3, .review-header h3 {
		margin: 0;
		color: #2271b1;
	}
	.status-badge {
		padding: 6px 12px;
		border-radius: 20px;
		font-size: 13px;
		font-weight: 600;
	}
	.status-badge.status-pending {
		background: #fcf8e3;
		color: #8a6d3b;
	}
	.status-badge.status-confirmed {
		background: #dff0d8;
		color: #3c763d;
	}
	.status-badge.status-completed {
		background: #d9edf7;
		color: #31708f;
	}
	.status-badge.status-cancelled {
		background: #f2dede;
		color: #a94442;
	}
	.booking-details {
		margin-bottom: 15px;
	}
	.detail-row {
		padding: 8px 0;
		border-bottom: 1px solid #eee;
	}
	.booking-actions {
		display: flex;
		gap: 10px;
		margin-top: 15px;
	}
	.btn-primary, .btn-secondary, .btn-outline {
		padding: 10px 20px;
		border-radius: 6px;
		text-decoration: none;
		font-weight: 600;
		display: inline-block;
		transition: all 0.3s;
	}
	.btn-primary {
		background: #2271b1;
		color: #fff;
	}
	.btn-secondary {
		background: #6c757d;
		color: #fff;
	}
	.btn-outline {
		background: transparent;
		color: #2271b1;
		border: 2px solid #2271b1;
	}
	.empty-state {
		text-align: center;
		padding: 60px 20px;
		color: #666;
	}
	.stars .star.filled {
		color: #ffc107;
	}
	.stars .star {
		color: #ddd;
		font-size: 20px;
	}
	.account-info {
		margin-bottom: 30px;
	}
	.info-row {
		padding: 15px;
		border-bottom: 1px solid #eee;
	}
	.account-actions {
		display: flex;
		gap: 15px;
	}
	@media (max-width: 768px) {
		.dashboard-tabs {
			flex-direction: column;
		}
		.booking-header, .review-header {
			flex-direction: column;
			align-items: flex-start;
		}
		.booking-actions, .account-actions {
			flex-direction: column;
		}
	}
</style>

<script>
jQuery(document).ready(function($) {
	$('.tab-btn').on('click', function() {
		var tab = $(this).data('tab');
		$('.tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.tab-content').removeClass('active');
		$('#' + tab + '-tab').addClass('active');
	});
});
</script>

<?php
function has_user_reviewed($user_id, $business_id) {
	$reviews = get_posts(array(
		'post_type' => 'review',
		'author' => $user_id,
		'meta_query' => array(
			array('key' => '_business_id', 'value' => $business_id)
		)
	));
	return !empty($reviews);
}

get_footer();
?>
