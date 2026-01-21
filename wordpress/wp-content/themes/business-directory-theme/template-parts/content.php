<?php
/**
 * Content template part
 *
 * @package Business_Directory_Theme
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
		if ( is_singular() ) {
			the_title( '<h1 class="entry-title">', '</h1>' );
		} else {
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );
		}
		?>
	</header>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="entry-thumbnail">
			<?php the_post_thumbnail( 'medium' ); ?>
		</div>
	<?php endif; ?>
	<div class="entry-content">
		<?php
		the_excerpt();
		?>
	</div>
	<footer class="entry-footer">
		<p class="posted-on"><?php echo esc_html( get_the_date() ); ?></p>
	</footer>
</article>
