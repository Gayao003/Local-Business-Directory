<?php
/**
 * Template for displaying archives
 *
 * @package Business_Directory_Theme
 */

get_header(); ?>

<div class="container">
	<div class="content-area">
		<header class="archive-header">
			<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
		</header>
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			}
			the_posts_pagination();
		} else {
			?>
			<p><?php esc_html_e( 'No content found.', 'bdb-theme' ); ?></p>
			<?php
		}
		?>
	</div>
	<?php get_sidebar(); ?>
</div>

<?php get_footer();
