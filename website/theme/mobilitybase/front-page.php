<?php
/**
 * Home page.
 */
get_header();
?>
<section class="mb-hero">
	<div class="mb-hero__text">
		<h1 class="mb-hero__title">Regain Your Independence.<br>Stairlifts, Home Lifts &amp; More.</h1>
		<p class="mb-hero__sub">Stairlifts, Home Lifts &amp; Platform Lifts. Installed &amp; Serviced Across Kent &amp; London</p>
		<p class="mb-hero__lead">Honest advice, professional installation and reliable aftercare from engineers based in Kent. We’ll help you find the right lift for your home and budget — with no hidden costs.</p>
		<p class="mb-hero__ctas">
			<a class="mb-btn" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Request a Free Quote</a>
			<a class="mb-btn mb-btn--grey" href="<?php echo esc_url( home_url( '/products' ) ); ?>">Explore Our Lifts</a>
		</p>
	</div>
	<div class="mb-hero__media">
		<?php echo mb_img( '32a1699136194f51.webp', 'Home lift installed in a bright living room', array( 'loading' => false, 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
	</div>
</section>

<section class="mb-band" aria-label="What we do">
	<div class="mb-wrap mb-band__inner">
		<a class="mb-band__item" href="<?php echo esc_url( home_url( '/lift-installations' ) ); ?>">
			<h2>Supply &amp; Installation</h2>
			<p>Every installation is tailored to your space, with clear advice, professional installation and dependable aftercare.</p>
		</a>
		<a class="mb-band__item" href="<?php echo esc_url( home_url( '/lift-servicing-kent' ) ); ?>">
			<h2>Service &amp; Maintenance</h2>
			<p>Planned servicing and maintenance to keep your lift safe, reliable and compliant.</p>
		</a>
		<a class="mb-band__item" href="<?php echo esc_url( home_url( '/lift-repairs-kent' ) ); ?>">
			<h2>Repairs &amp; Callouts</h2>
			<p>Fault finding and repairs on most lifts makes and models to minimise downtime.</p>
		</a>
	</div>
</section>

<section class="mb-section">
	<div class="mb-wrap">
		<h2 class="mb-title">Lifts we supply &amp; install</h2>
		<p class="mb-section__intro">Every lift starts with a free home survey, so you get the right solution for your stairs, your space and your budget.</p>
		<div class="mb-cards mb-cards--5">
			<?php foreach ( mb_products() as $slug => $p ) : ?>
				<a class="mb-card" href="<?php echo esc_url( home_url( '/' . $slug ) ); ?>">
					<span class="mb-card__img"><?php echo mb_img( $p['gallery'][0], $p['name'] ); // phpcs:ignore ?></span>
					<span class="mb-card__body">
						<span class="mb-card__title"><?php echo esc_html( $p['name'] ); ?></span>
						<span class="mb-card__link">View range <?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="mb-section mb-visit">
	<div class="mb-wrap mb-visit__inner">
		<div class="mb-visit__text">
			<h2 class="mb-title">Arrange a visit</h2>
			<p>We carry out home and site visits across the South East for stairlifts, homelifts and platform lifts. Our engineers will assess your space, discuss suitable options and answer any questions you may have.</p>
			<p><a class="mb-btn mb-btn--caps" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Arrange a visit</a></p>
		</div>
		<div class="mb-visit__media">
			<?php echo mb_img( 'f377b01ade614859.webp', 'Engineer measuring staircase for stairlift installation in Kent' ); // phpcs:ignore ?>
		</div>
	</div>
</section>

<section class="mb-section mb-section--grey">
	<div class="mb-wrap">
		<h2 class="mb-title">Offering the right mobility<br>solution for your home</h2>
		<p class="mb-section__intro">Mobilitybase provides expert advice, installations and ongoing support for stairlifts, homelifts and platform lifts. We help you choose the right solution for your needs, property and budget.</p>
		<div class="mb-features">
			<div class="mb-feature">
				<?php echo mb_icon( 'pin' ); // phpcs:ignore ?>
				<h3>Local coverage &amp; reliability</h3>
				<p>Covering Kent, London and the South East, our engineers carry out installations, servicing and repairs close to home.</p>
			</div>
			<div class="mb-feature">
				<?php echo mb_icon( 'pound' ); // phpcs:ignore ?>
				<h3>Fair &amp; transparent pricing</h3>
				<p>Clear, competitive pricing with no hidden costs. We’ll explain your options and costs clearly from the start.</p>
			</div>
			<div class="mb-feature">
				<?php echo mb_icon( 'star' ); // phpcs:ignore ?>
				<h3>Fully qualified engineers</h3>
				<p>All work is carried out by experienced, fully trained engineers, with safety, reliability and aftercare always our priority.</p>
			</div>
		</div>
	</div>
</section>

<section class="mb-section mb-servicecta">
	<div class="mb-wrap mb-servicecta__inner">
		<div>
			<h2 class="mb-title mb-title--left">Already have a lift?</h2>
			<p>We service and repair stairlifts, homelifts, platform lifts and ceiling hoists from all major manufacturers — including lifts we didn’t install. Annual warranty &amp; service plans start from £95 a year.</p>
		</div>
		<p class="mb-servicecta__btns">
			<a class="mb-btn" href="<?php echo esc_url( home_url( '/lift-servicing-kent' ) ); ?>">Lift servicing</a>
			<a class="mb-btn mb-btn--outline" href="<?php echo esc_url( home_url( '/aftercare-warranty' ) ); ?>">Warranty plans</a>
		</p>
	</div>
</section>

<?php
$latest = new WP_Query( array( 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
if ( $latest->have_posts() ) :
	?>
	<section class="mb-section mb-section--tight">
		<div class="mb-wrap">
			<h2 class="mb-title">Advice &amp; articles</h2>
			<div class="mb-cards mb-cards--3">
				<?php
				while ( $latest->have_posts() ) :
					$latest->the_post();
					get_template_part( 'template-parts/post-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<p class="mb-center"><a class="mb-textlink" href="<?php echo esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>">All articles <?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></a></p>
		</div>
	</section>
<?php endif; ?>

<section class="mb-section mb-brands">
	<div class="mb-wrap">
		<h2 class="mb-title">Selling only quality brands:</h2>
		<ul class="mb-brands__list">
			<?php foreach ( mb_brands() as $b ) : ?>
				<li><?php echo mb_img( $b[0], $b[1] ); // phpcs:ignore ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php
get_footer();
