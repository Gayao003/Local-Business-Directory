<!-- Review Form Template -->
<div class="bdb-review-form-wrapper">
	<?php
	// Check if user has a booking with this business
	global $wpdb;
	$business_id = get_the_ID();
	$user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';
	
	// Get customer's bookings for this business
	$has_booking = false;
	if ( $user_email ) {
		$booking_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}bookings 
				WHERE business_id = %d AND customer_email = %s AND status = %s",
				$business_id,
				$user_email,
				'completed'
			)
		);
		$has_booking = $booking_count > 0;
	}
	?>

	<?php if ( ! is_user_logged_in() ) : ?>
		<p class="review-login-notice">You must be <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">logged in</a> to leave a review.</p>
	<?php elseif ( ! $has_booking ) : ?>
		<p class="review-notice">You must have a completed booking with this business to leave a review.</p>
	<?php else : ?>
		<h3>Leave a Review</h3>
		<form id="bdb-review-form" class="bdb-review-form">
			<?php wp_nonce_field( 'bdb_review_nonce', 'bdb_review_nonce' ); ?>
			<input type="hidden" name="business_id" value="<?php echo esc_attr( $business_id ); ?>">

			<div class="form-group">
				<label for="rating">Rating *</label>
				<div class="star-rating">
					<input type="radio" id="star5" name="rating" value="5" required>
					<label for="star5" title="5 stars">★</label>
					<input type="radio" id="star4" name="rating" value="4">
					<label for="star4" title="4 stars">★</label>
					<input type="radio" id="star3" name="rating" value="3">
					<label for="star3" title="3 stars">★</label>
					<input type="radio" id="star2" name="rating" value="2">
					<label for="star2" title="2 stars">★</label>
					<input type="radio" id="star1" name="rating" value="1">
					<label for="star1" title="1 star">★</label>
				</div>
			</div>

			<div class="form-group">
				<label for="review_title">Review Title *</label>
				<input type="text" id="review_title" name="review_title" required maxlength="100" placeholder="Summary of your experience">
			</div>

			<div class="form-group">
				<label for="review_content">Your Review *</label>
				<textarea id="review_content" name="review_content" rows="6" required placeholder="Share details of your experience..."></textarea>
			</div>

			<button type="submit" class="button button-primary">Submit Review</button>
		</form>

		<div id="review-message" class="review-message" style="display:none;"></div>
	<?php endif; ?>
</div>

<!-- Display Reviews -->
<div class="bdb-reviews-list">
	<h3>Customer Reviews</h3>
	<?php
	$reviews = get_posts( array(
		'post_type'      => 'review',
		'posts_per_page' => 10,
		'meta_query'     => array(
			array(
				'key'     => '_business_id',
				'value'   => $business_id,
				'compare' => '=',
			),
		),
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	// Calculate average rating
	$total_rating = 0;
	$count = count( $reviews );
	
	if ( ! empty( $reviews ) ) {
		foreach ( $reviews as $review ) {
			$rating = get_post_meta( $review->ID, '_rating', true );
			$total_rating += intval( $rating );
		}
		$average = $count > 0 ? round( $total_rating / $count, 1 ) : 0;
	}
	?>

	<?php if ( ! empty( $reviews ) ) : ?>
		<div class="reviews-summary">
			<div class="average-rating">
				<span class="rating-number"><?php echo esc_html( $average ); ?></span>
				<div class="stars"><?php echo str_repeat( '★', round( $average ) ) . str_repeat( '☆', 5 - round( $average ) ); ?></div>
				<span class="review-count"><?php echo esc_html( $count ); ?> <?php echo $count === 1 ? 'review' : 'reviews'; ?></span>
			</div>
		</div>

		<div class="reviews-container">
			<?php foreach ( $reviews as $review ) : ?>
				<?php
				$rating = get_post_meta( $review->ID, '_rating', true );
				$reviewer_name = get_post_meta( $review->ID, '_reviewer_name', true );
				$reviewer_email = get_post_meta( $review->ID, '_reviewer_email', true );
				?>
				<div class="review-item">
					<div class="review-header">
						<div class="reviewer-info">
							<strong class="reviewer-name"><?php echo esc_html( $reviewer_name ); ?></strong>
							<div class="review-rating">
								<?php echo str_repeat( '★', intval( $rating ) ) . str_repeat( '☆', 5 - intval( $rating ) ); ?>
							</div>
						</div>
						<div class="review-date">
							<?php echo esc_html( get_the_date( 'F j, Y', $review ) ); ?>
						</div>
					</div>
					<h4 class="review-title"><?php echo esc_html( $review->post_title ); ?></h4>
					<div class="review-content">
						<?php echo wp_kses_post( wpautop( $review->post_content ) ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="no-reviews">No reviews yet. Be the first to review this business!</p>
	<?php endif; ?>
</div>

<style>
	.bdb-review-form-wrapper,
	.bdb-reviews-list {
		margin: 30px 0;
	}

	.bdb-review-form-wrapper h3,
	.bdb-reviews-list h3 {
		margin-bottom: 20px;
		font-size: 24px;
	}

	.review-login-notice,
	.review-notice {
		padding: 15px;
		background: #fff3cd;
		border-left: 4px solid #ffc107;
		margin-bottom: 20px;
	}

	.bdb-review-form {
		background: #f9f9f9;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.bdb-review-form .form-group {
		margin-bottom: 20px;
	}

	.bdb-review-form label {
		display: block;
		margin-bottom: 8px;
		font-weight: 600;
		color: #333;
	}

	/* Star Rating */
	.star-rating {
		display: flex;
		flex-direction: row-reverse;
		justify-content: flex-end;
		gap: 5px;
		font-size: 32px;
	}

	.star-rating input[type="radio"] {
		display: none;
	}

	.star-rating label {
		cursor: pointer;
		color: #ddd;
		transition: color 0.2s;
	}

	.star-rating input:checked ~ label,
	.star-rating label:hover,
	.star-rating label:hover ~ label {
		color: #ffc107;
	}

	.bdb-review-form input[type="text"],
	.bdb-review-form textarea {
		width: 100%;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 3px;
		font-size: 14px;
		font-family: inherit;
	}

	.bdb-review-form textarea {
		resize: vertical;
	}

	.bdb-review-form button {
		padding: 12px 24px;
		font-size: 16px;
		cursor: pointer;
	}

	.review-message {
		padding: 15px;
		border-radius: 5px;
		margin-top: 15px;
	}

	.review-message.success {
		background: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}

	.review-message.error {
		background: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}

	/* Reviews Display */
	.reviews-summary {
		background: #f8f9fa;
		padding: 20px;
		border-radius: 5px;
		margin-bottom: 30px;
		text-align: center;
	}

	.average-rating {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 10px;
	}

	.rating-number {
		font-size: 48px;
		font-weight: bold;
		color: #ffc107;
	}

	.average-rating .stars {
		font-size: 24px;
		color: #ffc107;
	}

	.review-count {
		font-size: 14px;
		color: #666;
	}

	.reviews-container {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	.review-item {
		background: #fff;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.review-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 15px;
	}

	.reviewer-info {
		flex: 1;
	}

	.reviewer-name {
		font-size: 16px;
		color: #333;
	}

	.review-rating {
		font-size: 18px;
		color: #ffc107;
		margin-top: 5px;
	}

	.review-date {
		font-size: 14px;
		color: #666;
	}

	.review-title {
		font-size: 18px;
		margin: 10px 0;
		color: #333;
	}

	.review-content {
		color: #555;
		line-height: 1.6;
	}

	.no-reviews {
		text-align: center;
		padding: 40px;
		color: #666;
		font-style: italic;
	}

	@media (max-width: 768px) {
		.review-header {
			flex-direction: column;
		}

		.review-date {
			margin-top: 10px;
		}

		.rating-number {
			font-size: 36px;
		}
	}
</style>

<script>
jQuery(document).ready(function($) {
	$('#bdb-review-form').on('submit', function(e) {
		e.preventDefault();

		const $form = $(this);
		const $message = $('#review-message');
		const businessId = $('input[name="business_id"]').val();
		const nonce = $('input[name="bdb_review_nonce"]').val();

		$.ajax({
			url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type: 'POST',
			data: {
				action: 'bdb_submit_review',
				business_id: businessId,
				rating: $('input[name="rating"]:checked').val(),
				review_title: $('#review_title').val(),
				review_content: $('#review_content').val(),
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$message.html(response.data.message)
						.removeClass('error')
						.addClass('success')
						.show();
					$form.hide();

					// Reload page after 2 seconds to show new review
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$message.html(response.data.message || 'An error occurred')
						.removeClass('success')
						.addClass('error')
						.show();
				}
			},
			error: function() {
				$message.html('Error submitting review')
					.removeClass('success')
					.addClass('error')
					.show();
			}
		});
	});
});
</script>
