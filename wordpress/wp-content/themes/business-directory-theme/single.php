<?php
/**
 * Single post template
 *
 * @package Business_Directory_Theme
 */

get_header(); ?>

<div class="container">
	<div class="content-area">
		<?php
		while ( have_posts() ) {
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large' );
				}
				?>
				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages();
					?>
				</div>
			</article>
			<?php
		}
		?>
	</div>
	<?php get_sidebar(); ?>
</div>

<?php get_footer();
