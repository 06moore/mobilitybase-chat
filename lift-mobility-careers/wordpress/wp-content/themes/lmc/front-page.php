<?php get_header(); ?>
<section class="lmc-hero">
	<div class="lmc-hero__rails" aria-hidden="true"></div>
	<div class="lmc-wrap lmc-hero__inner">
		<p class="lmc-eyebrow lmc-eyebrow--lime">The UK job board for lift &amp; mobility engineers</p>
		<h1>Find your next engineering opportunity.</h1>
		<p class="lmc-hero__lead">Specialist vacancies for stairlift, home lift, platform lift, hoist and accessibility equipment engineers – from trainees to service managers. Every advert shows the salary, and you apply directly to the employer, free.</p>
		<?php lmc_theme_search_form( 'hero' ); ?>
		<p class="lmc-hero__popular"><span>Popular:</span>
			<a href="<?php echo esc_url( get_term_link( 'stairlifts', 'lmc_sector' ) ); ?>">Stairlift engineer</a>
			<a href="<?php echo esc_url( get_term_link( 'service-engineer', 'lmc_role' ) ); ?>">Service engineer</a>
			<a href="<?php echo esc_url( get_term_link( 'loler-inspector', 'lmc_role' ) ); ?>">LOLER inspector</a>
			<a href="<?php echo esc_url( get_term_link( 'home-lifts', 'lmc_sector' ) ); ?>">Home lifts</a>
			<a href="<?php echo esc_url( get_term_link( 'trainee-engineer', 'lmc_role' ) ); ?>">Trainee</a>
		</p>
		<div class="lmc-hero__actions">
			<a class="lmc-btn lmc-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Browse Jobs</a>
			<a class="lmc-btn lmc-btn--outline-light" href="<?php echo esc_url( lmc_page_url( 'advertise' ) ); ?>">Advertise a Vacancy</a>
		</div>
	</div>
</section>

<section class="lmc-trust" aria-label="Why Lift and Mobility Careers">
	<div class="lmc-wrap lmc-trust__grid">
		<div><?php echo lmc_theme_icon( 'level' ); ?><strong>Specialist only</strong><span>Lifts, hoists and accessibility equipment – nothing else.</span></div>
		<div><?php echo lmc_theme_icon( 'pound' ); ?><strong>Salary on every advert</strong><span>No "competitive salary" guesswork.</span></div>
		<div><?php echo lmc_theme_icon( 'arrow' ); ?><strong>Apply direct, free</strong><span>No account, no agency – straight to the employer.</span></div>
		<div><?php echo lmc_theme_icon( 'pin' ); ?><strong>UK-wide</strong><span>Search by town, county or postcode.</span></div>
	</div>
</section>

<?php $premium = do_shortcode( '[lmc_job_list tier="premium" limit="4" empty=" "]' ); ?>
<?php if ( false === strpos( $premium, 'lmc-empty' ) ) : ?>
<section class="lmc-section lmc-section--grey">
	<div class="lmc-wrap">
		<div class="lmc-section__head"><div><p class="lmc-eyebrow">Premium vacancies</p><h2>Featured opportunities</h2></div></div>
		<?php echo $premium; // phpcs:ignore -- escaped in template. ?>
	</div>
</section>
<?php endif; ?>

<section class="lmc-section">
	<div class="lmc-wrap">
		<div class="lmc-section__head"><div><p class="lmc-eyebrow">Browse by engineering sector</p><h2>Where do you want to work?</h2></div><a class="lmc-link-arrow" href="<?php echo esc_url( lmc_page_url( 'sectors' ) ); ?>">All sectors <?php echo lmc_theme_icon( 'arrow' ); ?></a></div>
		<?php echo do_shortcode( '[lmc_sector_grid]' ); ?>
	</div>
</section>

<section class="lmc-section lmc-section--grey">
	<div class="lmc-wrap">
		<div class="lmc-section__head"><div><p class="lmc-eyebrow">Latest vacancies</p><h2>New engineering jobs</h2></div><a class="lmc-link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">View all jobs <?php echo lmc_theme_icon( 'arrow' ); ?></a></div>
		<?php
		$latest = new WP_Query( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'posts_per_page' => 8, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
		if ( $latest->have_posts() ) {
			echo '<div class="lmc-job-list">';
			while ( $latest->have_posts() ) {
				$latest->the_post();
				lmc_render_job_card( get_the_ID() );
			}
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<p class="lmc-empty">New vacancies are being added. Check back soon.</p>';
		}
		?>
	</div>
</section>

<section class="lmc-employers">
	<div class="lmc-wrap lmc-employers__grid">
		<div>
			<p class="lmc-eyebrow lmc-eyebrow--lime">For employers</p>
			<h2>Recruiting engineers? Reach people who know the industry.</h2>
			<p>Advertise your installation, service, LOLER and management vacancies to a specialist audience – without agency fees or long contracts.</p>
			<ul class="lmc-ticks">
				<li><?php echo lmc_theme_icon( 'check' ); ?> Adverts from <?php echo esc_html( lmc_money( lmc_opt( 'price_standard' ), true ) . lmc_price_suffix() ); ?> for <?php echo (int) lmc_opt( 'listing_days' ); ?> days</li>
				<li><?php echo lmc_theme_icon( 'check' ); ?> Applications go straight to you – no placement fees</li>
				<li><?php echo lmc_theme_icon( 'check' ); ?> Featured and premium options for extra visibility</li>
				<li><?php echo lmc_theme_icon( 'check' ); ?> Post, edit and renew online in minutes</li>
			</ul>
			<div class="lmc-hero__actions">
				<a class="lmc-btn lmc-btn--primary" href="<?php echo esc_url( lmc_page_url( 'advertise' ) ); ?>">Advertise a Vacancy</a>
				<a class="lmc-btn lmc-btn--outline-light" href="<?php echo esc_url( lmc_page_url( 'employers' ) ); ?>">How it works</a>
			</div>
		</div>
		<div class="lmc-employers__steps">
			<ol>
				<li><strong>Create a free account</strong><span>Two minutes, no card needed.</span></li>
				<li><strong>Post your vacancy</strong><span>A simple, structured form.</span></li>
				<li><strong>Choose an advert and pay</strong><span>Secure card payment via Stripe.</span></li>
				<li><strong>Receive applications</strong><span>Directly, by your link or email.</span></li>
			</ol>
		</div>
	</div>
</section>

<section class="lmc-section">
	<div class="lmc-wrap">
		<div class="lmc-section__head"><div><p class="lmc-eyebrow">Browse by role</p><h2>Jobs at every stage of your career</h2></div></div>
		<div class="lmc-roles">
			<?php foreach ( get_terms( array( 'taxonomy' => 'lmc_role', 'hide_empty' => false, 'orderby' => 'term_id' ) ) as $r ) : ?>
				<a href="<?php echo esc_url( get_term_link( $r ) ); ?>"><?php echo esc_html( $r->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php get_footer(); ?>
