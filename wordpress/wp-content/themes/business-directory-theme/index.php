<?php
/**
 * The main template file - Homepage
 * Tesla-Inspired Design
 *
 * @package Business_Directory_Theme
 */

get_header(); ?>

<main class="site-main homepage">
	
	<!-- Tesla-Style Hero Section -->
	<section class="tesla-hero">
		<?php
		// Get a featured image from first business or use placeholder
		$hero_query = new WP_Query(array(
			'post_type' => 'business_listing',
			'posts_per_page' => 1,
			'meta_key' => '_thumbnail_id'
		));
		
		if ($hero_query->have_posts()) {
			$hero_query->the_post();
			if (has_post_thumbnail()) {
				the_post_thumbnail('full', array('class' => 'tesla-hero-image'));
			}
			wp_reset_postdata();
		}
		?>
		<div class="tesla-hero-overlay"></div>
		<div class="tesla-hero-content">
			<h1 class="tesla-hero-title">Discover Local Excellence</h1>
			<p class="tesla-hero-subtitle">Book the best services in your area with confidence</p>
			<div class="tesla-hero-cta">
				<a href="<?php echo esc_url(home_url('/business_listing/')); ?>" class="tesla-btn tesla-btn-primary">Explore Businesses</a>
				<a href="#featured" class="tesla-btn tesla-btn-secondary">Learn More</a>
			</div>
		</div>
	</section>

	<!-- Statistics Section -->
	<section class="tesla-section tesla-section-alt">
		<div class="tesla-stats">
			<?php
			$business_count = wp_count_posts('business_listing')->publish;
			$booking_count = 0;
			global $wpdb;
			$booking_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bookings WHERE status = 'confirmed'");
			$category_count = wp_count_terms('business_category');
			?>
			<div class="tesla-stat">
				<div class="tesla-stat-value"><?php echo number_format($business_count); ?>+</div>
				<div class="tesla-stat-label">Local Businesses</div>
			</div>
			<div class="tesla-stat">
				<div class="tesla-stat-value"><?php echo number_format($booking_count); ?>+</div>
				<div class="tesla-stat-label">Bookings Made</div>
			</div>
			<div class="tesla-stat">
				<div class="tesla-stat-value"><?php echo number_format($category_count); ?>+</div>
				<div class="tesla-stat-label">Categories</div>
			</div>
			<div class="tesla-stat">
				<div class="tesla-stat-value">4.8</div>
				<div class="tesla-stat-label">Avg Rating</div>
			</div>
		</div>
	</section>

	<!-- Featured Businesses -->
	<section class="tesla-section" id="featured">
		<div class="tesla-section-header">
			<h2 class="tesla-heading-xl">Featured Businesses</h2>
			<p class="tesla-text-body">Discover top-rated local services</p>
		</div>
		
		<?php
		$featured_businesses = new WP_Query(array(
			'post_type' => 'business_listing',
			'posts_per_page' => 6,
			'orderby' => 'date',
			'order' => 'DESC'
		));

		if ($featured_businesses->have_posts()) :
		?>
			<div class="tesla-grid tesla-grid-3">
				<?php while ($featured_businesses->have_posts()) : $featured_businesses->the_post(); ?>
					<article class="tesla-card">
						<?php if (has_post_thumbnail()) : ?>
							<div class="tesla-card-image-wrapper">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail('large', array('class' => 'tesla-card-image')); ?>
								</a>
							</div>
						<?php endif; ?>
						
						<div class="tesla-card-content">
							<h3 class="tesla-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>
							
							<?php
							// Get categories
							$categories = get_the_terms(get_the_ID(), 'business_category');
							if ($categories && !is_wp_error($categories)) :
							?>
								<div class="tesla-card-meta">
									<?php foreach (array_slice($categories, 0, 2) as $category) : ?>
										<span class="tesla-badge"><?php echo esc_html($category->name); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							
							<p class="tesla-text-body" style="margin: var(--spacing-sm) 0;">
								<?php echo wp_trim_words(get_the_excerpt(), 15); ?>
							</p>
							
							<?php
							// Get rating
							$reviews = new WP_Query(array(
								'post_type' => 'review',
								'meta_query' => array(array('key' => '_business_id', 'value' => get_the_ID())),
								'posts_per_page' => -1
							));
							
							$total = 0;
							$count = 0;
							if ($reviews->have_posts()) {
								while ($reviews->have_posts()) {
									$reviews->the_post();
									$rating = get_post_meta(get_the_ID(), '_rating', true);
									if ($rating) {
										$total += intval($rating);
										$count++;
									}
								}
								wp_reset_postdata();
							}
							
							if ($count > 0) :
								$average = round($total / $count, 1);
							?>
								<div class="tesla-rating">
									<span class="tesla-stars">
										<?php
										for ($i = 1; $i <= 5; $i++) {
											echo $i <= $average ? '★' : '☆';
										}
										?>
									</span>
									<span class="tesla-rating-text"><?php echo $average; ?> (<?php echo $count; ?>)</span>
								</div>
							<?php endif; ?>
							
							<a href="<?php the_permalink(); ?>" class="tesla-btn tesla-btn-primary" style="margin-top: var(--spacing-sm); width: 100%;">
								View Details
							</a>
						</div>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<div class="no-content">
					<h3>No businesses yet</h3>
					<p>Businesses will appear here once added to the directory.</p>
					<?php if (current_user_can('edit_posts')) : ?>
						<a href="<?php echo admin_url('post-new.php?post_type=business_listing'); ?>" class="cta-btn">Add Your First Business</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="view-all-wrapper">
				<a href="<?php echo get_post_type_archive_link('business_listing'); ?>" class="view-all-btn">View All Businesses →</a>
			</div>
		</div>
	</section>

	<!-- How It Works -->
	<section class="how-it-works">
		<div class="container">
			<h2 class="section-title">How It Works</h2>
			<div class="steps-grid">
				<div class="step">
					<div class="step-number">1</div>
					<h3>Find a Business</h3>
					<p>Search and browse through our directory</p>
				</div>
				<div class="step">
					<div class="step-number">2</div>
					<h3>Check Availability</h3>
					<p>View real-time available time slots</p>
				</div>
				<div class="step">
					<div class="step-number">3</div>
					<h3>Book Instantly</h3>
					<p>Complete your booking with secure payment</p>
				</div>
				<div class="step">
					<div class="step-number">4</div>
					<h3>Leave a Review</h3>
					<p>Share your experience with others</p>
				</div>
			</div>
		</div>
	</section>

</main>

<style>
	.hero-section {
		background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
		color: #fff;
		padding: 80px 20px;
		text-align: center;
	}
	.hero-title {
		font-size: 3em;
		margin: 0 0 20px 0;
	}
	.hero-subtitle {
		font-size: 1.3em;
		margin-bottom: 40px;
		opacity: 0.9;
	}
	.hero-search {
		max-width: 600px;
		margin: 0 auto;
		display: flex;
		gap: 10px;
	}
	.hero-search-input {
		flex: 1;
		padding: 15px 20px;
		border: none;
		border-radius: 8px;
		font-size: 16px;
	}
	.hero-search-btn {
		padding: 15px 40px;
		background: #fff;
		color: #2271b1;
		border: none;
		border-radius: 8px;
		font-weight: 600;
		cursor: pointer;
	}
	.featured-businesses, .how-it-works {
		padding: 60px 20px;
	}
	.section-title {
		text-align: center;
		font-size: 2.2em;
		margin-bottom: 40px;
		color: #2271b1;
	}
	.business-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
		gap: 30px;
		margin-bottom: 40px;
	}
	.business-card {
		background: #fff;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
		transition: transform 0.3s;
	}
	.business-card:hover {
		transform: translateY(-5px);
	}
	.business-thumb img {
		width: 100%;
		height: 200px;
		object-fit: cover;
	}
	.business-info {
		padding: 20px;
	}
	.business-info h3 {
		margin: 0 0 10px 0;
	}
	.business-info h3 a {
		color: #2271b1;
		text-decoration: none;
	}
	.book-now-btn {
		display: inline-block;
		padding: 10px 20px;
		background: #2271b1;
		color: #fff;
		text-decoration: none;
		border-radius: 6px;
		font-weight: 600;
	}
	.no-content {
		text-align: center;
		padding: 60px 20px;
		background: #f5f5f5;
		border-radius: 12px;
	}
	.cta-btn {
		display: inline-block;
		margin-top: 20px;
		padding: 12px 30px;
		background: #2271b1;
		color: #fff;
		text-decoration: none;
		border-radius: 8px;
	}
	.view-all-wrapper {
		text-align: center;
	}
	.view-all-btn {
		padding: 12px 30px;
		color: #2271b1;
		text-decoration: none;
		border: 2px solid #2271b1;
		border-radius: 8px;
		font-weight: 600;
	}
	.steps-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 30px;
	}
	.step {
		text-align: center;
		padding: 30px;
	}
	.step-number {
		width: 60px;
		height: 60px;
		background: #2271b1;
		color: #fff;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 1.5em;
		font-weight: bold;
		margin: 0 auto 20px;
	}
	@media (max-width: 768px) {
		.hero-title { font-size: 2em; }
		.hero-search { flex-direction: column; }
		.business-grid, .steps-grid { grid-template-columns: 1fr; }
	}
</style>

<?php get_footer();
