<?php
/**
 * Archive template for business listings
 * Tesla-Inspired Design
 */

get_header();
?>

<main class="business-archive">
	
	<!-- Hero Search Section -->
	<section class="tesla-search-section">
		<div class="tesla-section-header">
			<h1 class="tesla-heading-xl">Business Directory</h1>
			<p class="tesla-text-body">Discover and book top-rated local services</p>
		</div>

		<!-- Search & Filter -->
		<form method="get" class="tesla-search-form">
			<input type="text" name="s" placeholder="Search businesses..." 
				value="<?php echo get_search_query(); ?>" class="tesla-input">
			
			<?php
			$categories = get_terms(array('taxonomy' => 'business_category', 'hide_empty' => true));
			if ($categories && !is_wp_error($categories)) :
			?>
				<select name="category" class="tesla-select">
					<option value="">All Categories</option>
					<?php foreach ($categories as $cat) : ?>
						<option value="<?php echo esc_attr($cat->slug); ?>" <?php selected(get_query_var('category'), $cat->slug); ?>>
							<?php echo esc_html($cat->name); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<button type="submit" class="tesla-btn tesla-btn-primary">Search</button>
		</form>

		<!-- Location & View Controls -->
		<div style="display: flex; gap: var(--spacing-sm); justify-content: center; margin-top: var(--spacing-md); flex-wrap: wrap;">
			<button type="button" id="nearMeBtn" class="tesla-btn tesla-btn-accent">
				📍 Near Me
			</button>
			
			<?php if ( isset( $_GET['latitude'] ) && isset( $_GET['longitude'] ) ) : ?>
				<select id="distanceFilter" class="tesla-select">
					<option value="">Any Distance</option>
					<option value="5" <?php selected( $_GET['distance'] ?? '', '5' ); ?>>Within 5 miles</option>
					<option value="10" <?php selected( $_GET['distance'] ?? '', '10' ); ?>>Within 10 miles</option>
					<option value="25" <?php selected( $_GET['distance'] ?? '', '25' ); ?>>Within 25 miles</option>
						<option value="50" <?php selected( $_GET['distance'] ?? '', '50' ); ?>>Within 50 miles</option>
					</select>
				<?php endif; ?>

				<button type="button" id="toggleMapView" class="button button-secondary">
					🗺️ Show Map
				</button>
			</div>
		</div>

		<!-- Map View Container -->
		<?php
		// Prepare businesses data for map
		$map_businesses = array();
		if ( have_posts() ) {
			$temp_query = $GLOBALS['wp_query'];
			while ( $temp_query->have_posts() ) {
				$temp_query->the_post();
				$lat = get_post_meta( get_the_ID(), '_business_latitude', true );
				$lng = get_post_meta( get_the_ID(), '_business_longitude', true );
				if ( $lat && $lng ) {
					$map_businesses[] = array(
						'id' => get_the_ID(),
						'title' => get_the_title(),
						'latitude' => $lat,
						'longitude' => $lng,
						'url' => get_permalink(),
						'thumbnail' => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' )
					);
				}
			}
			rewind_posts();
		}
		?>
		<div id="mapViewContainer" class="map-view-container" style="display: none; margin-bottom: 30px;">
			<div id="businessesMap" class="businesses-map" 
			     data-businesses='<?php echo esc_attr( json_encode( $map_businesses ) ); ?>'
			     style="width: 100%; height: 500px; border-radius: 8px; border: 1px solid #ddd;"></div>
		</div>

		<!-- Business Grid -->
		<?php if (have_posts()) : ?>
		<div class="tesla-grid tesla-grid-3">
			<?php while (have_posts()) : the_post(); ?>
				<article class="tesla-card">
					
					<?php if (has_post_thumbnail()) : ?>
						<div class="tesla-card-image-wrapper">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail('medium', array('class' => 'tesla-card-image')); ?>
							</a>
						</div>
					<?php endif; ?>

					<div class="tesla-card-content">
						<h2 class="tesla-card-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php
						// Display distance if Near Me search is active
						global $post;
						if ( isset( $post->distance ) ) :
						?>
							<div class="tesla-distance">
								📍 <?php echo number_format( $post->distance, 1 ); ?> miles away
							</div>
						<?php endif; ?>

						<div class="tesla-card-meta">
							<?php
							// Get categories
							$cats = get_the_terms(get_the_ID(), 'business_category');
							if ($cats && !is_wp_error($cats)) :
								foreach ($cats as $cat) : ?>
									<span class="tesla-badge"><?php echo esc_html($cat->name); ?></span>
								<?php endforeach;
							endif;
							?>
						</div>

						<div class="business-card-excerpt">
							<?php echo wp_trim_words(get_the_excerpt(), 20); ?>
						</div>

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
							$avg = round($total / $count, 1);
						?>
							<div class="tesla-rating">
								<div class="tesla-stars">
									<?php
									for ($i = 1; $i <= 5; $i++) {
										echo $i <= floor($avg) ? '<span class="star filled">★</span>' : '<span class="star">☆</span>';
									}
									?>
								</div>
								<span class="tesla-rating-text"><?php echo $avg; ?> (<?php echo $count; ?>)</span>
							</div>
						<?php endif; ?>

						<?php
						$phone = get_post_meta(get_the_ID(), '_business_phone', true);
						$address = get_post_meta(get_the_ID(), '_business_address', true);
						?>

						<div class="tesla-card-meta">
							<?php if ($phone) : ?>
								<div class="meta-item">📞 <?php echo esc_html($phone); ?></div>
							<?php endif; ?>
							<?php if ($address) : ?>
								<div class="meta-item">📍 <?php echo esc_html(wp_trim_words($address, 5)); ?></div>
							<?php endif; ?>
						</div>

						<a href="<?php the_permalink(); ?>" class="tesla-btn tesla-btn-primary">View Details & Book</a>
				<?php endwhile; ?>
			</div>

			<!-- Pagination -->
			<div class="pagination">
				<?php
				echo paginate_links(array(
					'prev_text' => '« Previous',
					'next_text' => 'Next »',
				));
				?>
			</div>

		<?php else : ?>
			<div class="no-businesses">
				<h2>No businesses found</h2>
				<p>Check back soon as we add more businesses to our directory!</p>
			</div>
		<?php endif; ?>

	</div>
</main>

<style>
	.business-archive {
		padding: 40px 20px;
		background: #f5f5f5;
		min-height: 70vh;
	}

	.archive-header {
		text-align: center;
		margin-bottom: 40px;
		padding: 40px 20px;
		background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
		color: #fff;
		border-radius: 12px;
	}

	.archive-title {
		font-size: 2.5em;
		margin: 0 0 15px 0;
	}

	.archive-description {
		font-size: 1.2em;
		opacity: 0.9;
	}

	.business-filters {
		margin-bottom: 40px;
	}

	.filter-form {
		display: flex;
		gap: 15px;
		max-width: 800px;
		margin: 0 auto;
	}

	.search-input,
	.category-filter {
		flex: 1;
		padding: 12px 20px;
		border: 1px solid #ddd;
		border-radius: 8px;
		font-size: 16px;
	}

	.filter-btn {
		padding: 12px 30px;
		background: #2271b1;
		color: #fff;
		border: none;
		border-radius: 8px;
		cursor: pointer;
		font-weight: 600;
		transition: background 0.3s;
	}

	.filter-btn:hover {
		background: #135e96;
	}

	.business-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
		gap: 30px;
		margin-bottom: 40px;
	}

	.business-card {
		background: #fff;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
		transition: transform 0.3s, box-shadow 0.3s;
	}

	.business-card:hover {
		transform: translateY(-5px);
		box-shadow: 0 8px 24px rgba(0,0,0,0.15);
	}

	.business-card-image img {
		width: 100%;
		height: 200px;
		object-fit: cover;
	}

	.business-card-content {
		padding: 20px;
	}

	.business-card-title {
		margin: 0 0 10px 0;
		font-size: 1.3em;
	}

	.business-card-title a {
		color: #2271b1;
		text-decoration: none;
	}

	.business-card-title a:hover {
		text-decoration: underline;
	}

	.business-card-cats {
		margin-bottom: 15px;
	}

	.cat-badge {
		display: inline-block;
		background: #e8f0f7;
		color: #2271b1;
		padding: 4px 12px;
		border-radius: 12px;
		font-size: 13px;
		margin-right: 8px;
	}

	.business-card-excerpt {
		color: #666;
		margin-bottom: 15px;
		line-height: 1.6;
	}

	.business-card-rating {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 15px;
	}

	.stars .star.filled {
		color: #ffc107;
	}

	.stars .star {
		color: #ddd;
	}

	.rating-count {
		font-size: 14px;
		color: #666;
	}

	.business-card-meta {
		margin-bottom: 15px;
		font-size: 14px;
		color: #666;
	}

	.meta-item {
		margin-bottom: 8px;
	}

	.view-business-btn {
		display: block;
		text-align: center;
		padding: 12px 20px;
		background: #2271b1;
		color: #fff;
		text-decoration: none;
		border-radius: 8px;
		font-weight: 600;
		transition: background 0.3s;
	}

	.view-business-btn:hover {
		background: #135e96;
	}

	.no-businesses {
		text-align: center;
		padding: 60px 20px;
		background: #fff;
		border-radius: 12px;
	}

	.pagination {
		text-align: center;
		padding: 20px 0;
	}

	.pagination a,
	.pagination span {
		display: inline-block;
		padding: 8px 16px;
		margin: 0 5px;
		background: #fff;
		border: 1px solid #ddd;
		border-radius: 6px;
		text-decoration: none;
		color: #2271b1;
	}

	.pagination .current {
		background: #2271b1;
		color: #fff;
		border-color: #2271b1;
	}

	@media (max-width: 768px) {
		.filter-form {
			flex-direction: column;
		}

		.business-grid {
			grid-template-columns: 1fr;
		}

		.archive-title {
			font-size: 2em;
		}
	}
</style>

<?php get_footer(); ?>
