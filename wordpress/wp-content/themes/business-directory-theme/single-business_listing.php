<?php
/**
 * Template for displaying a single business listing
 *
 * @package Business_Directory_Theme
 */

get_header();
?>

<main class="business-single-page">
	<?php while ( have_posts() ) : the_post(); ?>
		
		<article id="business-<?php the_ID(); ?>" <?php post_class( 'business-detail' ); ?>>
			
			<!-- Business Header -->
			<header class="business-header">
				<div class="container">
					<div class="business-header-content">
						<div class="business-title-section">
							<h1 class="business-title"><?php the_title(); ?></h1>
							
							<?php
							// Display categories
							$categories = get_the_terms( get_the_ID(), 'business_category' );
							if ( $categories && ! is_wp_error( $categories ) ) :
							?>
								<div class="business-categories">
									<?php foreach ( $categories as $category ) : ?>
										<span class="category-badge"><?php echo esc_html( $category->name ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php
							// Check if business can be claimed
							$business_owner = get_post_meta( get_the_ID(), '_business_owner', true );
							$current_user = get_current_user_id();
							$is_owner = ( $business_owner && $business_owner == $current_user );
							
							if ( ! $business_owner && is_user_logged_in() ) :
							?>
								<div class="claim-business-section">
									<button class="button button-claim" id="claimBusinessBtn" data-business-id="<?php echo get_the_ID(); ?>">
										📋 Claim This Business
									</button>
								</div>
							<?php elseif ( $is_owner ) : ?>
								<div class="owner-badge">
									<span class="badge-owner">✓ You own this business</span>
								</div>
							<?php endif; ?>

							<?php
							// Display average rating
							$args = array(
								'post_type' => 'review',
								'meta_query' => array(
									array(
										'key' => '_business_id',
										'value' => get_the_ID(),
										'compare' => '='
									)
								),
								'posts_per_page' => -1
							);
							$reviews = new WP_Query( $args );
							$total_rating = 0;
							$count = 0;

							if ( $reviews->have_posts() ) {
								while ( $reviews->have_posts() ) {
									$reviews->the_post();
									$rating = get_post_meta( get_the_ID(), '_rating', true );
									if ( $rating ) {
										$total_rating += intval( $rating );
										$count++;
									}
								}
								wp_reset_postdata();
							}

							if ( $count > 0 ) {
						$average_rating = round( $total_rating / $count, 1 );
						?>
						<div class="business-rating">
							<span class="stars">
								<?php
								for ( $i = 1; $i <= 5; $i++ ) {
									echo $i <= $average_rating ? '★' : '☆';
								}
								?>
							</span>
						<span class="rating-text"><?php echo esc_html( $average_rating ); ?> (<?php echo esc_html( $count ); ?> reviews)</span>
					</div>
					<?php
				}
				?>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="business-image">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>
				$full_address = trim( implode( ', ', array_filter( array( $address, $city, $state, $zip ) ) ) );
			?>
			<section class="business-location-section">
				<div class="container">
					<h2>Location</h2>
					<?php if ( $full_address ) : ?>
						<div class="business-address">
							<p><?php echo esc_html( $full_address ); ?></p>
							<a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode( $full_address ); ?>" 
							   class="button button-primary" target="_blank" rel="noopener">
								📍 Get Directions
							</a>
						</div>
					<?php endif; ?>
					<div class="business-map">
						<?php echo do_shortcode( '[bdb_map business_id="' . get_the_ID() . '" height="400px" zoom="15"]' ); ?>
					</div>
				</div>
			</section>
            
			<!-- Business Details -->
			<section class="business-info-section">
				<div class="container">
					<div class="business-grid">
						
						<!-- Left Column: Details & Description -->
						<div class="business-main-content">
							
							<!-- Meta Information -->
							<div class="business-meta-box">
								<h2>Business Information</h2>
								<ul class="business-meta-list">
									<?php
									$phone = get_post_meta( get_the_ID(), '_business_phone', true );
									$email = get_post_meta( get_the_ID(), '_business_email', true );
									$website = get_post_meta( get_the_ID(), '_business_website', true );
									$address = get_post_meta( get_the_ID(), '_business_address', true );
									?>
									
									<?php if ( $phone ) : ?>
										<li>
											<strong>📞 Phone:</strong>
											<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
										</li>
									<?php endif; ?>

									<?php if ( $email ) : ?>
										<li>
											<strong>✉️ Email:</strong>
											<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
										</li>
									<?php endif; ?>

									<?php if ( $website ) : ?>
										<li>
											<strong>🌐 Website:</strong>
											<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $website ); ?></a>
										</li>
									<?php endif; ?>

									<?php if ( $address ) : ?>
										<li>
											<strong>📍 Address:</strong>
											<?php echo esc_html( $address ); ?>
										</li>
									<?php endif; ?>
								</ul>
							</div>

							<!-- Description -->
							<div class="business-description">
								<h2>About This Business</h2>
								<div class="business-content">
									<?php the_content(); ?>
								</div>
							</div>

							<!-- Services -->
							<?php
							$services = get_the_terms( get_the_ID(), 'service_type' );
							if ( $services && ! is_wp_error( $services ) ) :
							?>
								<div class="business-services">
									<h2>Services Offered</h2>
									<ul class="services-list">
										<?php foreach ( $services as $service ) : ?>
											<li>✓ <?php echo esc_html( $service->name ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<!-- Reviews Section -->
							<div class="reviews-section">
								<?php
								// Include review form
								$review_form_path = plugin_dir_path( dirname( __FILE__ ) ) . '../plugins/business-directory-booking/public/review-form.php';
								if ( file_exists( $review_form_path ) ) {
									include $review_form_path;
								}
								?>
							</div>
						</div>

						<!-- Right Column: Booking Form -->
						<aside class="business-sidebar">
							<div class="booking-widget sticky">
								<h3>Book This Business</h3>
								<?php
								// Include booking form
								$booking_form_path = plugin_dir_path( dirname( __FILE__ ) ) . '../plugins/business-directory-booking/public/booking-form.php';
								if ( file_exists( $booking_form_path ) ) {
									include $booking_form_path;
								}
								?>
							</div>
						</aside>

					</div>
				</div>
			</section>

		</article>

	<?php endwhile; ?>
</main>

<!-- Chatbot Widget -->
<?php
$chatbot_path = plugin_dir_path( dirname( __FILE__ ) ) . '../plugins/business-directory-booking/public/chatbot-widget.php';
if ( file_exists( $chatbot_path ) ) {
	include $chatbot_path;
}
?>

<style>
	/* Business Single Page Styles */
	.business-single-page {
		background: #f5f5f5;
		padding-bottom: 60px;
	}

	.business-header {
		background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
		color: #fff;
		padding: 60px 0 40px;
		margin-bottom: 40px;
	}

	.business-header-content {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 40px;
		align-items: start;
	}

	.business-title {
		font-size: 2.5em;
		margin: 0 0 15px 0;
		color: #fff;
	}

	.business-categories {
		margin-bottom: 20px;
	}

	.category-badge {
		display: inline-block;
		background: rgba(255,255,255,0.2);
		padding: 6px 16px;
		border-radius: 20px;
		font-size: 14px;
		margin-right: 10px;
		margin-bottom: 10px;
	}

	.business-rating {
		display: flex;
		align-items: center;
		gap: 12px;
		font-size: 18px;
	}

	.stars {
		color: #ffc107;
		font-size: 24px;
	}

	.star.filled {
		color: #ffc107;
	}

	.star.half {
		color: #ffc107;
		opacity: 0.5;
	}

	.star {
		color: rgba(255,255,255,0.3);
	}

	.rating-text {
		font-size: 16px;
	}

	.business-image img {
		width: 100%;
		height: auto;
		border-radius: 12px;
		box-shadow: 0 8px 24px rgba(0,0,0,0.2);
	}

	.business-grid {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 30px;
		max-width: 1200px;
		margin: 0 auto;
	}

	.business-main-content,
	.business-sidebar {
		animation: fadeInUp 0.5s ease;
	}

	.business-meta-box,
	.business-description,
	.business-services,
	.reviews-section {
		background: #fff;
		padding: 30px;
		border-radius: 12px;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
		margin-bottom: 30px;
	}

	.business-meta-box h2,
	.business-description h2,
	.business-services h2 {
		font-size: 1.5em;
		margin: 0 0 20px 0;
		color: #2271b1;
		border-bottom: 2px solid #e0e0e0;
		padding-bottom: 15px;
	}

	.business-meta-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.business-meta-list li {
		padding: 12px 0;
		border-bottom: 1px solid #f0f0f0;
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.business-meta-list li:last-child {
		border-bottom: none;
	}

	.business-meta-list strong {
		min-width: 120px;
		color: #333;
	}

	.business-meta-list a {
		color: #2271b1;
		text-decoration: none;
	}

	.business-meta-list a:hover {
		text-decoration: underline;
	}

	.business-content {
		line-height: 1.8;
		color: #555;
	}

	.services-list {
		list-style: none;
		padding: 0;
		margin: 0;
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 12px;
	}

	.services-list li {
		padding: 12px 16px;
		background: #f8f9fa;
		border-radius: 8px;
		border-left: 3px solid #2271b1;
	}

	.booking-widget {
		background: #fff;
		padding: 30px;
		border-radius: 12px;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
	}

	.booking-widget.sticky {
		position: sticky;
		top: 20px;
	}

	.booking-widget h3 {
		font-size: 1.4em;
		margin: 0 0 20px 0;
		color: #2271b1;
	}

	@keyframes fadeInUp {
		from {
			opacity: 0;
			transform: translateY(30px);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@media (max-width: 968px) {
		.business-header-content,
		.business-grid {
			grid-template-columns: 1fr;
		}

		.business-title {
			font-size: 2em;
		}

		.booking-widget.sticky {
			position: relative;
			top: 0;
		}

		.services-list {
			grid-template-columns: 1fr;
		}
	}

	/* Claim Business Styles */
	.claim-business-section {
		margin: 15px 0;
	}

	.button-claim {
		background: #f0ad4e;
		color: #fff;
		border: none;
		padding: 10px 20px;
		border-radius: 6px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.button-claim:hover {
		background: #ec971f;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0,0,0,0.2);
	}

	.owner-badge {
		margin: 15px 0;
	}

	.badge-owner {
		background: #5cb85c;
		color: #fff;
		padding: 8px 16px;
		border-radius: 20px;
		font-size: 14px;
		font-weight: 600;
		display: inline-block;
	}
</style>

<script>
jQuery(document).ready(function($) {
	// Claim Business Button
	$('#claimBusinessBtn').on('click', function() {
		const button = $(this);
		const businessId = button.data('business-id');

		if (!confirm('Are you sure you want to claim this business? An administrator will review your request.')) {
			return;
		}

		button.prop('disabled', true).text('Processing...');

		$.ajax({
			url: bdb_obj.ajax_url,
			type: 'POST',
			data: {
				action: 'bdb_claim_business',
				business_id: businessId,
				nonce: bdb_obj.nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					button.text('✓ Claim Submitted').css('background', '#5cb85c');
				} else {
					alert('Error: ' + response.data.message);
					button.prop('disabled', false).text('📋 Claim This Business');
				}
			},
			error: function() {
				alert('An error occurred. Please try again.');
				button.prop('disabled', false).text('📋 Claim This Business');
			}
		});
	});
});
</script>

<?php get_footer(); ?>
