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
		<p class="mb-section__intro"><?php echo esc_html( $range['intro'] ); ?></p>
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
		<div class="mb-survey__media">
			<?php echo mb_img( '13b4ad69e4514301.webp', 'Couple discussing ' . strtolower( $cat ) . ' options' ); // phpcs:ignore ?>
			<p class="mb-survey__call">Prefer to talk it through? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
		</div>
	</div>
</section>
<?php
get_footer();
