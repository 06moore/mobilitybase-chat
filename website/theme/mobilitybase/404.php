<?php
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap mb-center">
		<h1 class="mb-pagetitle">Sorry, we can’t find that page</h1>
		<p>It may have moved when we updated our website. Try one of these instead:</p>
		<p class="mb-404links">
			<a class="mb-btn" href="<?php echo esc_url( home_url( '/products' ) ); ?>">Our lifts</a>
			<a class="mb-btn" href="<?php echo esc_url( home_url( '/services' ) ); ?>">Our services</a>
			<a class="mb-btn mb-btn--grey" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact us</a>
		</p>
		<p>Or call us on <?php echo mb_tel(); // phpcs:ignore ?>.</p>
	</div>
</section>
<?php
get_footer();
