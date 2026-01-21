<?php
/**
 * Archive template for business listings
 */

get_header();
?>

<main class="business-archive">
	<div class="container">
		
		<header class="archive-header">
			<h1 class="archive-title">Business Directory</h1>
			<p class="archive-description">Find and book local businesses in your area</p>
		</header>

		<!-- Search & Filter -->
		<div class="business-filters">
			<form method="get" class="filter-form">
				<input type="text" name="s" placeholder="Search businesses..." value="<?php echo get_search_query(); ?>" class="search-input">
				
				<?php
				$categories = get_terms(array('taxonomy' => 'business_category', 'hide_empty' => true));
				if ($categories && !is_wp_error($categories)) :
				?>
					<select name="category" class="category-filter">
						<option value="">All Categories</option>
						<?php foreach ($categories as $cat) : ?>
							<option value="<?php echo esc_attr($cat->slug); ?>" <?php selected(get_query_var('category'), $cat->slug); ?>>
								<?php echo esc_html($cat->name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>

				<button type="submit" class="filter-btn">Search</button>
			</form>
		</div>

		<!-- Business Grid -->
		<?php if (have_posts()) : ?>
			<div class="business-grid">
				<?php while (have_posts()) : the_post(); ?>
					<article class="business-card">
						
						<?php if (has_post_thumbnail()) : ?>
							<div class="business-card-image">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail('medium'); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="business-card-content">
							<h2 class="business-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<?php
							// Get categories
							$cats = get_the_terms(get_the_ID(), 'business_category');
							if ($cats && !is_wp_error($cats)) :
							?>
								<div class="business-card-cats">
									<?php foreach ($cats as $cat) : ?>
										<span class="cat-badge"><?php echo esc_html($cat->name); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

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
								<div class="business-card-rating">
									<div class="stars">
										<?php
										for ($i = 1; $i <= 5; $i++) {
											echo $i <= floor($avg) ? '<span class="star filled">★</span>' : '<span class="star">☆</span>';
										}
										?>
									</div>
									<span class="rating-count"><?php echo $avg; ?> (<?php echo $count; ?>)</span>
								</div>
							<?php endif; ?>

							<?php
							$phone = get_post_meta(get_the_ID(), '_business_phone', true);
							$address = get_post_meta(get_the_ID(), '_business_address', true);
							?>

							<div class="business-card-meta">
								<?php if ($phone) : ?>
									<div class="meta-item">📞 <?php echo esc_html($phone); ?></div>
								<?php endif; ?>
								<?php if ($address) : ?>
									<div class="meta-item">📍 <?php echo esc_html(wp_trim_words($address, 5)); ?></div>
								<?php endif; ?>
							</div>

							<a href="<?php the_permalink(); ?>" class="view-business-btn">View Details & Book</a>
						</div>
					</article>
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
