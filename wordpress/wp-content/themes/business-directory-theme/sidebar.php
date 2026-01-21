<?php
/**
 * Sidebar template
 *
 * @package Business_Directory_Theme
 */

if ( ! is_active_sidebar( 'primary-sidebar' ) ) {
	return;
}
?>

<aside id="secondary" class="primary-sidebar">
	<?php dynamic_sidebar( 'primary-sidebar' ); ?>
</aside>
