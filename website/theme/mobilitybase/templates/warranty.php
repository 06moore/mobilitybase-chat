<?php
/**
 * Warranty plan page (Bronze / Silver / Gold). Data in mb_warranties(), inc/data.php.
 */
get_header();
$w = mb_warranties()[ mb_slug() ];
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle"><?php echo esc_html( ucwords( strtolower( $w['h1'] ) ) ); ?></h1>
		<p class="mb-section__intro"><?php echo esc_html( $w['intro'] ); ?></p>
		<div class="mb-plans">
			<?php foreach ( $w['plans'] as $key => $p ) : ?>
				<article class="mb-plan mb-plan--<?php echo esc_attr( $key ); ?><?php echo $p['best'] ? ' mb-plan--best' : ''; ?>">
					<?php if ( $p['best'] ) : ?><p class="mb-plan__badge">Best value</p><?php endif; ?>
					<h2 class="mb-plan__name"><?php echo esc_html( $p['name'] ); ?></h2>
					<p class="mb-plan__price">£<?php echo esc_html( $p['price'] ); ?><span> / year</span></p>
					<p class="mb-plan__summary"><?php echo esc_html( $p['summary'] ); ?></p>
					<p class="mb-plan__term"><?php echo esc_html( $p['term'] ); ?></p>
					<ul class="mb-plan__list">
						<?php foreach ( $p['items'] as $item ) : ?>
							<li><?php echo mb_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p><a class="mb-btn mb-btn--block" href="#plan-enquiry" data-mb-enquire="<?php echo esc_attr( $w['label'] . ' – ' . $p['name'] . ' plan (£' . $p['price'] . ')' ); ?>">Choose <?php echo esc_html( $p['name'] ); ?></a></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="mb-section mb-section--grey" id="plan-enquiry">
	<div class="mb-wrap mb-twocol">
		<div class="mb-prose">
			<h2>Important information about our warranty plans</h2>
			<ul>
				<?php foreach ( $w['notes'] as $note ) : ?>
					<li><?php echo esc_html( $note ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>For full details of cover, please refer to our <a href="<?php echo esc_url( home_url( '/service-agreement-terms' ) ); ?>">Terms &amp; Conditions</a>.</p>
		</div>
		<div class="mb-panel">
			<h2>Enquire about a plan</h2>
			<?php
			echo do_shortcode( sprintf(
				'[mb_form id="plan-form" fields="name,phone,email,postcode,message" title="%s warranty plan enquiry" options="Bronze|Silver|Gold|Not sure yet" options_label="Which plan are you interested in?"]',
				esc_attr( $w['label'] )
			) );
			?>
		</div>
	</div>
</section>
<?php
get_footer();
