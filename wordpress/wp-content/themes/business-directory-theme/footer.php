		</main>
		<footer id="colophon" class="site-footer">
			<div class="site-info">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
				?>
				<p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
			</div>
		</footer>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
