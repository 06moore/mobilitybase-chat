<?php
/**
 * Product range page (straight / curved stairlifts, homelifts, platform lifts, ceiling hoists).
 * Models come from mb_ranges() in inc/data.php.
 */
get_header();
$slug  = mb_slug();
$range = mb_ranges()[ $slug ];
$cat   = mb_products()[ $slug ]['name'] ?? '';
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle"><?php echo esc_html( $range['h1'] ); ?></h1>
		<?php if ( $mb_rp = mb_range_price( $slug ) ) : ?>
			<p class="mb-rangeprice"><span class="mb-pricebadge"><?php echo esc_html( $mb_rp ); ?></span> <span class="mb-rangeprice__note">Fully fitted with a minimum 1-year guarantee. Your exact price is confirmed in writing after a free, no-obligation home survey. VAT relief may apply if the lift is for someone with a long-term illness or disability.</span></p>
		<?php endif; ?>
		<p class="mb-section__intro"><?php echo esc_html( $range['intro'] ); ?></p>
		<div class="mb-included">
			<h2 class="mb-included__title">Every installation includes</h2>
			<ul class="mb-included__list">
				<li><?php echo mb_icon( 'pin' ); // phpcs:ignore ?><span><strong>Free home survey</strong> No obligation — we measure up and talk you through the options.</span></li>
				<li><?php echo mb_icon( 'pound' ); // phpcs:ignore ?><span><strong>Written quote</strong> Your price in writing before anything is ordered.</span></li>
				<li><?php echo mb_icon( 'wrench' ); // phpcs:ignore ?><span><strong>Fitted by our own engineers</strong> DBS-checked and fully insured.</span></li>
				<li><?php echo mb_icon( 'shield' ); // phpcs:ignore ?><span><strong>Minimum 1-year guarantee</strong> Then optional service plans to keep it running.</span></li>
			</ul>
		</div>
		<div class="mb-models">
			<?php foreach ( $range['models'] as $m ) : ?>
				<article class="mb-model">
					<div class="mb-model__img"><?php echo mb_img( $m[1], $m[2] ); // phpcs:ignore ?></div>
					<div class="mb-model__body">
						<h2><?php echo esc_html( $m[0] ); ?></h2>
						<?php foreach ( $m[3] as $para ) : ?>
							<p><?php echo esc_html( $para ); ?></p>
						<?php endforeach; ?>
						<p><a class="mb-btn" href="#survey" data-mb-enquire="<?php echo esc_attr( $m[0] ); ?>">Enquire about the <?php echo esc_html( $m[0] ); ?></a></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $range['compare'] ) ) : $cmp = $range['compare']; ?>
			<div class="mb-compare">
				<h2 class="mb-title"><?php echo esc_html( $cmp['title'] ); ?></h2>
				<div class="mb-compare__grid">
					<?php foreach ( $cmp['cols'] as $col ) : ?>
						<div class="mb-compare__col">
							<h3><?php echo esc_html( $col[0] ); ?></h3>
							<p class="mb-compare__price"><?php echo esc_html( $col[1] ); ?></p>
							<ul class="mb-plan__list">
								<?php foreach ( $col[2] as $item ) : ?><li><?php echo mb_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="mb-section__intro"><?php echo esc_html( $cmp['note'] ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $range['cross'] ) ) : ?>
			<p class="mb-crosslink"><a href="<?php echo esc_url( home_url( $range['cross'][1] ) ); ?>"><?php echo esc_html( $range['cross'][0] ); ?> <?php echo mb_icon( 'arrow' ); // phpcs:ignore ?></a></p>
		<?php endif; ?>
	</div>
</section>

<?php get_template_part( 'template-parts/reviews', null, array( 'limit' => 3, 'topic' => 'install' ) ); ?>

<section class="mb-section mb-section--grey" id="survey">
	<div class="mb-wrap mb-survey">
		<div class="mb-survey__form mb-panel">
			<h2>Request a free home survey</h2>
			<p><?php echo esc_html( $range['survey'] ); ?></p>
			<?php
			echo do_shortcode( sprintf(
				'[mb_form id="survey-form" fields="name,phone,email,postcode,message" title="%s survey request" product="" button="Submit"]',
				esc_attr( $cat )
			) );
			?>
		</div>
		<div class="mb-survey__media mb-survey__what">
			<h2>What happens at your free survey</h2>
			<ol class="mb-steps mb-steps--stack">
				<li><strong>We visit at a time that suits you</strong><span>One of our engineers comes to your home. Family members are welcome to join.</span></li>
				<li><strong>We measure up and listen</strong><span>We look at your stairs or space and talk through how the lift will be used day to day.</span></li>
				<li><strong>You get a written quote</strong><span>A clear price in writing, with no pressure and no obligation to go ahead.</span></li>
			</ol>
			<p class="mb-survey__call">Prefer to talk it through? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
		</div>
	</div>
</section>
<?php
get_footer();
