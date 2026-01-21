<?php
/**
 * Shortcodes for embedding forms
 */

class BDB_Shortcodes {

	public function __construct() {
		add_shortcode('bdb_booking_form', array($this, 'booking_form_shortcode'));
		add_shortcode('bdb_business_list', array($this, 'business_list_shortcode'));
		add_shortcode('bdb_search', array($this, 'search_shortcode'));
	}

	/**
	 * Booking form shortcode
	 * Usage: [bdb_booking_form business_id="123"]
	 */
	public function booking_form_shortcode($atts) {
		$atts = shortcode_atts(array(
			'business_id' => get_the_ID()
		), $atts);

		if (!$atts['business_id']) {
			return '<p>Please specify a business ID.</p>';
		}

		ob_start();
		$GLOBALS['bdb_shortcode_business_id'] = $atts['business_id'];
		include plugin_dir_path(dirname(__FILE__)) . 'public/booking-form.php';
		return ob_get_clean();
	}

	/**
	 * Business list shortcode
	 * Usage: [bdb_business_list category="beauty" limit="6"]
	 */
	public function business_list_shortcode($atts) {
		$atts = shortcode_atts(array(
			'category' => '',
			'limit' => 6,
			'columns' => 3
		), $atts);

		$args = array(
			'post_type' => 'business_listing',
			'posts_per_page' => intval($atts['limit']),
			'orderby' => 'date',
			'order' => 'DESC'
		);

		if ($atts['category']) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'business_category',
					'field' => 'slug',
					'terms' => $atts['category']
				)
			);
		}

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			return '<p>No businesses found.</p>';
		}

		ob_start();
		?>
		<div class="bdb-shortcode-grid" style="display: grid; grid-template-columns: repeat(<?php echo intval($atts['columns']); ?>, 1fr); gap: 20px;">
			<?php while ($query->have_posts()) : $query->the_post(); ?>
				<div class="bdb-business-item" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
					<?php if (has_post_thumbnail()) : ?>
						<div class="bdb-business-thumb">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail('medium', array('style' => 'width: 100%; height: 200px; object-fit: cover;')); ?>
							</a>
						</div>
					<?php endif; ?>
					<div class="bdb-business-content" style="padding: 20px;">
						<h3 style="margin: 0 0 10px 0; font-size: 1.2em;">
							<a href="<?php the_permalink(); ?>" style="color: #2271b1; text-decoration: none;">
								<?php the_title(); ?>
							</a>
						</h3>
						<p style="color: #666; margin-bottom: 15px;"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
						<a href="<?php the_permalink(); ?>" style="display: inline-block; padding: 8px 16px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px;">
							View Details
						</a>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Search form shortcode
	 * Usage: [bdb_search]
	 */
	public function search_shortcode($atts) {
		ob_start();
		?>
		<form method="get" action="<?php echo esc_url(home_url('/business_listing/')); ?>" class="bdb-search-form" style="display: flex; gap: 10px; max-width: 600px; margin: 0 auto;">
			<input type="text" name="s" placeholder="Search businesses..." style="flex: 1; padding: 12px 20px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
			<button type="submit" style="padding: 12px 30px; background: #2271b1; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
				Search
			</button>
		</form>
		<?php
		return ob_get_clean();
	}
}

// Initialize shortcodes
new BDB_Shortcodes();
