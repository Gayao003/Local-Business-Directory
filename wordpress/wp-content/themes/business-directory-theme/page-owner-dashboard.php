<?php
/**
 * Template Name: Business Owner Dashboard
 * 
 * @package Business_Directory_Theme
 */

get_header();
?>

<div class="container">
	<div class="content-area">
		<?php echo do_shortcode( '[bdb_owner_dashboard]' ); ?>
	</div>
</div>

<?php
get_footer();
