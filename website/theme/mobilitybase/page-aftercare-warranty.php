<?php
/**
 * Aftercare & Warranty — the page for putting an existing lift on a Mobilitybase plan,
 * including lifts installed by other companies.
 */
get_header();
$from = mb_prices()['plan_from'];
?>
<section class="mb-lochero">
	<div class="mb-wrap mb-lochero__inner">
		<div class="mb-lochero__text">
			<?php if ( $from ) : ?><p class="mb-pricebadge">Plans from £<?php echo esc_html( $from ); ?> a year</p><?php endif; ?>
			<h1>Warranty &amp; Service Plans for Your Lift</h1>
			<p class="mb-lead">Stairlifts, homelifts and ceiling hoists across Kent, London and the South East — <strong>including lifts installed by other companies</strong>.</p>
			<ul class="mb-ticks">
				<li>Annual service included in every plan</li>
				<li>Priority 24/7 breakdown cover on Silver &amp; Gold</li>
				<li>Parts &amp; labour included on Gold</li>
			</ul>
			<p class="mb-lochero__call">Questions about cover? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
		</div>
		<div class="mb-lochero__form mb-panel" id="join">
			<h2>Cover my lift</h2>
			<p>Tell us about your lift and we’ll recommend the right plan and arrange the initial service.</p>
			<?php echo do_shortcode( '[mb_form id="cover-form" fields="name,phone,email,postcode,lift_details,message" title="Warranty plan enquiry" options="Straight stairlift|Curved stairlift|Homelift / through-floor lift|Ceiling hoist|Platform lift" options_label="What type of lift do you have?" button="Get my lift covered"]' ); ?>
		</div>
	</div>
</section>
<?php get_template_part( 'template-parts/trust' ); ?>
<?php get_template_part( 'template-parts/reviews', null, array( 'limit' => 3, 'topic' => 'service' ) ); ?>

<section class="mb-section">
	<div class="mb-wrap">
		<h2 class="mb-title">Which product do you have?</h2>
		<p class="mb-section__intro">Choose your lift to see the Bronze, Silver and Gold plans and prices.</p>
		<div class="mb-cards mb-cards--wide">
			<?php foreach ( mb_warranty_products() as $w ) : ?>
				<article class="mb-card mb-card--warranty">
					<div class="mb-card__img"><?php echo mb_img( $w[4], $w[0] ); // phpcs:ignore ?></div>
					<div class="mb-card__body">
						<h3 class="mb-card__title"><?php echo $w[1] ? '<a href="' . esc_url( home_url( $w[1] ) ) . '">' . esc_html( $w[0] ) . '</a>' : esc_html( $w[0] ); ?></h3>
						<p class="mb-card__price"><?php echo esc_html( $w[5] ); ?><?php echo $w[1] ? ' <small>per year</small>' : ''; ?></p>
						<p><?php echo esc_html( $w[2] ); ?></p>
						<p class="mb-card__note"><?php echo esc_html( $w[3] ); ?></p>
						<?php if ( $w[1] ) : ?>
							<p><a class="mb-btn mb-btn--caps mb-btn--sm" href="<?php echo esc_url( home_url( $w[1] ) ); ?>">See plans<span class="mb-sr"> for <?php echo esc_html( $w[0] ); ?></span></a></p>
						<?php else : ?>
							<p><a class="mb-btn mb-btn--caps mb-btn--sm" href="<?php echo esc_url( home_url( '/lift-servicing-kent#quote' ) ); ?>">Get a service quote</a></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="mb-section mb-section--grey">
	<div class="mb-wrap">
		<h2 class="mb-title">Lift fitted by another company?</h2>
		<p class="mb-section__intro">If your original installer has stopped servicing your lift, gone out of business or is simply too far away, you can move your cover to a local team. Here’s how it works.</p>
		<ol class="mb-steps">
			<li><strong>Tell us about your lift</strong><span>The make, roughly when it was installed and who fitted it. Call us or use the form above.</span></li>
			<li><strong>Initial service &amp; safety check</strong><span>An engineer services the lift and checks it’s in safe working order. Any parts it needs first are quoted before we do anything.</span></li>
			<li><strong>Choose your plan</strong><span>Pick Bronze, Silver or Gold. From then on we look after your annual service and breakdowns.</span></li>
		</ol>
		<div class="mb-twocol mb-twocol--top">
			<div class="mb-prose">
				<h3>Which lifts can join?</h3>
				<ul>
					<li>Lifts from recognised manufacturers, installed by a reputable company.</li>
					<li>The lift must pass our initial service and safety check. Parts needed to return it to a safe working condition are chargeable before cover begins.</li>
					<li>For lifts over 3 years old, failure of a main component (such as the drive motor or main control board) may be uneconomical to repair. In that case we’ll offer a discount towards a replacement lift.</li>
					<li>Where parts are obsolete, suitable second-hand parts may be used where possible.</li>
				</ul>
				<p>Full details are in our <a href="<?php echo esc_url( home_url( '/service-agreement-terms' ) ); ?>">service agreement terms</a>.</p>
			</div>
			<div class="mb-prose">
				<h3>Makes we regularly look after</h3>
				<p><?php echo esc_html( implode( ', ', mb_serviced_brands() ) ); ?>.</p>
				<p>Not sure if your lift qualifies? Call <?php echo mb_tel(); // phpcs:ignore ?> and we’ll tell you straight away.</p>
				<p><a class="mb-btn" href="#join">Cover my lift</a></p>
			</div>
		</div>
	</div>
</section>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
