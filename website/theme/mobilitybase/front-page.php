<?php
/**
 * Home page — built around the three things customers come for:
 * 1) servicing / repairs, 2) a new lift, 3) removing a stairlift.
 */
get_header();
$prices = mb_prices();
?>
<section class="mb-hero">
	<div class="mb-hero__text">
		<h1 class="mb-hero__title">Stairlifts &amp; Home Lifts Across Kent &amp; London <span class="mb-hero__titlesub">Supplied, installed &amp; looked after</span></h1>
		<p class="mb-hero__lead">Expert advice, professional installation and ongoing support from your local family-run team. Every new lift starts with a <strong>free home survey and a written quote</strong>. Already have a lift and need a service plan? We can help with that too.</p>
		<p class="mb-hero__ctas">
			<a class="mb-btn" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Book a Free Home Survey</a>
			<a class="mb-btn mb-btn--outline" href="<?php echo esc_url( home_url( '/aftercare-warranty#join' ) ); ?>">Cover My Existing Lift</a>
		</p>
		<p class="mb-hero__call">Or call us today on <?php echo mb_tel(); // phpcs:ignore ?></p>
	</div>
	<?php $mb_slides = mb_hero_slides(); ?>
	<div class="mb-hero__media mb-slider" data-mb-slider aria-roledescription="carousel" aria-label="Recent Mobilitybase installations">
		<?php foreach ( $mb_slides as $i => $sl ) : ?>
			<div class="mb-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' of ' . count( $mb_slides ) ); ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
				<?php echo mb_img( $sl[0], $sl[1], 0 === $i ? array( 'loading' => false, 'fetchpriority' => 'high' ) : array( 'loading' => false ) ); // phpcs:ignore ?>
				<p class="mb-hero__tag"><?php echo mb_icon( 'check' ); // phpcs:ignore ?> <?php echo esc_html( $sl[2] ); ?></p>
			</div>
		<?php endforeach; ?>
		<?php if ( count( $mb_slides ) > 1 ) : ?>
			<button type="button" class="mb-slider__btn mb-slider__btn--prev" aria-label="Previous photo">&#8249;</button>
			<button type="button" class="mb-slider__btn mb-slider__btn--next" aria-label="Next photo">&#8250;</button>
			<div class="mb-slider__dots">
				<?php foreach ( $mb_slides as $i => $sl ) : ?>
					<button type="button" aria-label="<?php echo esc_attr( 'Show photo ' . ( $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/trust' ); ?>

<section class="mb-paths" aria-labelledby="mb-paths-title">
	<div class="mb-wrap">
		<h2 class="mb-title" id="mb-paths-title">How can we help?</h2>
		<div class="mb-paths__grid">
			<a class="mb-path" href="<?php echo esc_url( home_url( '/products' ) ); ?>">
				<span class="mb-path__icon"><?php echo mb_icon( 'star' ); // phpcs:ignore ?></span>
				<span class="mb-path__kicker">Need a lift?</span>
				<span class="mb-path__title">New stairlifts, homelifts &amp; platform lifts</span>
				<span class="mb-path__text">Straight, curved and reconditioned stairlifts, through-floor homelifts, platform lifts and ceiling hoists.</span>
				<span class="mb-path__price"><?php echo esc_html( ( mb_price( 'straight_from', 'Straight stairlifts from £' ) ? mb_price( 'straight_from', 'Straight stairlifts from £' ) . ' + VAT' : 'Free, no-obligation home survey' ) ); ?></span>
				<span class="mb-path__btn">See our lifts</span>
			</a>
			<a class="mb-path" href="<?php echo esc_url( home_url( '/aftercare-warranty' ) ); ?>">
				<span class="mb-path__icon"><?php echo mb_icon( 'shield' ); // phpcs:ignore ?></span>
				<span class="mb-path__kicker">Already have a lift?</span>
				<span class="mb-path__title">Warranty &amp; service plans</span>
				<span class="mb-path__text">Already have a lift and need a service plan? Annual servicing and breakdown cover for stairlifts, homelifts and hoists.</span>
				<?php if ( $prices['plan_from'] ) : ?><span class="mb-path__price">Plans from £<?php echo esc_html( $prices['plan_from'] ); ?> a year + VAT</span><?php endif; ?>
				<span class="mb-path__btn">Cover my lift</span>
			</a>
			<a class="mb-path" href="<?php echo esc_url( home_url( '/stairlift-removal-kent' ) ); ?>">
				<span class="mb-path__icon"><?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></span>
				<span class="mb-path__kicker">Lift no longer needed?</span>
				<span class="mb-path__title">Stairlift &amp; lift removals</span>
				<span class="mb-path__text">Quick, tidy removal of straight and curved stairlifts, homelifts and platform lifts — ideal before a house sale.</span>
				<?php if ( $prices['removal_straight'] ) : ?><span class="mb-path__price">Stairlift removal from £<?php echo esc_html( $prices['removal_straight'] ); ?> + VAT</span><?php endif; ?>
				<span class="mb-path__btn">Get a removal quote</span>
			</a>
		</div>
		<p class="mb-paths__urgent">Lift broken down? Call <?php echo mb_tel(); // phpcs:ignore ?> or <a href="<?php echo esc_url( home_url( '/lift-repairs-kent#quote' ) ); ?>">report a fault online</a>.</p>
	</div>
</section>

<?php get_template_part( 'template-parts/reviews', null, array( 'limit' => 3 ) ); ?>

<section class="mb-section mb-section--grey">
	<div class="mb-wrap">
		<h2 class="mb-title">Lifts we supply &amp; install</h2>
		<p class="mb-section__intro">Every new lift starts with a free home survey by one of our engineers, so you get the right solution for your stairs, your space and your budget.</p>
		<div class="mb-cards mb-cards--5">
			<?php
			foreach ( mb_products() as $slug => $p ) :
				$price = mb_range_price( $slug, true );
				?>
				<a class="mb-card" href="<?php echo esc_url( home_url( '/' . $slug ) ); ?>">
					<span class="mb-card__img"><?php echo mb_img( $p['gallery'][0], $p['name'] ); // phpcs:ignore ?></span>
					<span class="mb-card__body">
						<span class="mb-card__title"><?php echo esc_html( $p['name'] ); ?></span>
						<span class="mb-card__price<?php echo $price ? '' : ' mb-card__price--quote'; ?>"><?php echo $price ? nl2br( esc_html( $price ) ) : 'Priced after a free survey'; // phpcs:ignore ?></span>
						<span class="mb-card__link">View range <?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
		<ol class="mb-steps">
			<li><strong>Free home survey</strong><span>An engineer measures up, listens to what you need and talks you through the options.</span></li>
			<li><strong>Clear quote</strong><span>A written quote that explains your options, with no hidden costs.</span></li>
			<li><strong>Installation &amp; aftercare</strong><span>Fitted by our own DBS-checked engineers with a minimum 1-year guarantee, then serviced and supported for years to come.</span></li>
		</ol>
		<p class="mb-center"><a class="mb-btn" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Book your free survey</a></p>
	</div>
</section>

<section class="mb-section mb-spot">
	<div class="mb-wrap mb-spot__inner">
		<div class="mb-spot__media">
			<?php echo mb_img( '2186e1e35a254577.webp', 'Mobilitybase engineer servicing a stairlift' ); // phpcs:ignore ?>
		</div>
		<div class="mb-spot__text">
			<p class="mb-eyebrow">Warranty &amp; service plans</p>
			<h2>Already have a lift? Let us look after it</h2>
			<p>Already have a lift and need a service plan? After an initial service and safety check, choose the plan that suits you — from an annual service to full parts-and-labour cover with 24/7 breakdown support.</p>
			<p class="mb-spot__brands"><strong>We service all major makes, including:</strong> <?php echo esc_html( implode( ', ', mb_serviced_brands() ) ); ?>.</p>
			<?php
			$plans = mb_warranties()['straight-stairlift-warranty']['plans'];
			?>
			<ul class="mb-miniplans" aria-label="Stairlift service plans">
				<?php foreach ( $plans as $key => $p ) : ?>
					<li class="mb-miniplan mb-miniplan--<?php echo esc_attr( $key ); ?>">
						<span class="mb-miniplan__name"><?php echo esc_html( $p['name'] ); ?></span>
						<span class="mb-miniplan__price">£<?php echo esc_html( $p['price'] ); ?><small>/yr</small></span>
						<span class="mb-miniplan__note"><?php echo esc_html( 'gold' === $key ? 'Parts & labour included' : ( 'silver' === $key ? '24/7 priority breakdowns' : 'Annual service' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="mb-small">Straight stairlift prices shown, excluding VAT. <a href="<?php echo esc_url( home_url( '/aftercare-warranty' ) ); ?>">See plans for curved stairlifts, homelifts and hoists</a>.</p>
			<p class="mb-btnrow">
				<a class="mb-btn" href="<?php echo esc_url( home_url( '/aftercare-warranty#join' ) ); ?>">Cover my lift</a>
				<a class="mb-btn mb-btn--outline" href="<?php echo esc_url( home_url( '/lift-servicing-kent#quote' ) ); ?>">Book a one-off service</a>
			</p>
		</div>
	</div>
</section>

<section class="mb-section mb-removal">
	<div class="mb-wrap mb-spot__inner mb-spot__inner--rev">
		<div class="mb-spot__text">
			<p class="mb-eyebrow">Stairlift removals</p>
			<h2>Stairlift no longer needed? We’ll take it away</h2>
			<p>Selling a house, clearing a property or the lift has simply stopped working — we remove straight, curved and outdoor stairlifts of any make, and leave your staircase clean and ready to use.</p>
			<?php if ( $prices['removal_straight'] ) : ?>
				<div class="mb-removal__prices">
					<p><span>Straight stairlift</span><strong>from £<?php echo esc_html( $prices['removal_straight'] ); ?></strong></p>
					<p><span>Curved stairlift</span><strong>from £<?php echo esc_html( $prices['removal_curved'] ); ?></strong></p>
				</div>
				<p class="mb-small">Prices exclude VAT. Final price depends on access, type and location. Homelift and platform lift removals quoted individually.</p>
			<?php endif; ?>
			<p class="mb-btnrow">
				<a class="mb-btn" href="<?php echo esc_url( home_url( '/stairlift-removal-kent#quote' ) ); ?>">Get a removal quote</a>
				<a class="mb-btn mb-btn--outline" href="<?php echo esc_url( home_url( '/lift-removals' ) ); ?>">Other lift removals</a>
			</p>
		</div>
		<div class="mb-spot__media">
			<?php echo mb_img( '149a6f0425254593.webp', 'Staircase after a stairlift removal in Kent' ); // phpcs:ignore ?>
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
?>
<section class="mb-section mb-about">
	<div class="mb-wrap mb-about__inner">
		<div>
			<p class="mb-eyebrow">About Mobilitybase</p>
			<h2>A family-run lift company you can rely on</h2>
			<p>Mobilitybase was set up in <?php echo esc_html( mb_biz( 'founded' ) ); ?> and supplies, installs, services and removes stairlifts, home lifts, platform lifts and ceiling hoists across Kent, London and the South East.</p>
			<p>We’re an independent team of engineers who look after the whole job, from your free home survey through to installation and years of servicing afterwards.</p>
			<p class="mb-btnrow"><a class="mb-btn" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Book a free home survey</a> <a class="mb-btn mb-btn--outline" href="<?php echo esc_url( home_url( '/about-us' ) ); ?>">More about us</a></p>
		</div>
		<ul class="mb-about__facts">
			<li><strong>Since <?php echo esc_html( mb_biz( 'founded' ) ); ?></strong><span>Family-run</span></li>
			<li><strong>5.0 ★</strong><span>From <?php echo esc_html( mb_review_summary()['count'] ); ?> Google reviews</span></li>
			<li><strong>DBS-checked</strong><span>Fully insured engineers</span></li>
			<li><strong>1 year+</strong><span>Guarantee on every lift</span></li>
			<li class="mb-about__addr"><strong>Where we’re based</strong><span><?php echo esc_html( mb_biz( 'street' ) . ', ' . mb_biz( 'locality' ) . ', ' . mb_biz( 'town' ) . ' ' . mb_biz( 'postcode' ) ); ?></span></li>
		</ul>
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

<?php get_template_part( 'template-parts/areas', null, array( 'type' => 'all' ) ); ?>

<section class="mb-section mb-homeform" id="quote">
	<div class="mb-wrap mb-twocol">
		<div>
			<h2 class="mb-title mb-title--left">Get a free quote</h2>
			<p>Tell us what you need and where you are. A member of the Mobilitybase team will get back to you to talk it through or arrange a visit.</p>
			<ul class="mb-ticks">
				<li>Free, no-obligation home surveys</li>
				<li>Clear prices — no hidden costs</li>
				<li>Servicing &amp; repairs for all major makes</li>
			</ul>
			<p class="mb-lochero__call">Prefer to talk? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
		</div>
		<div class="mb-panel">
			<?php echo do_shortcode( '[mb_form id="home-form" fields="name,phone,email,postcode,message" title="Home page enquiry" options="A new stairlift or lift|A warranty or service plan for my lift|A one-off service or repair|Removing a stairlift or lift|Something else" options_label="What can we help with?" button="Send my enquiry"]' ); ?>
		</div>
	</div>
</section>
<?php
get_footer();
