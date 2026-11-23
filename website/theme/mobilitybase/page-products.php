<?php
/**
 * Products overview.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle mb-pagetitle--caps">Find the perfect lift for you</h1>
		<p class="mb-section__intro">Select the type of lift you’d like to explore below and discover our full range of tailored solutions.</p>
		<div class="mb-prodlist">
			<?php foreach ( mb_products() as $slug => $p ) : ?>
				<article class="mb-prod">
					<div class="mb-prod__text">
						<h2><a href="<?php echo esc_url( home_url( '/' . $slug ) ); ?>"><?php echo esc_html( $p['name'] ); ?></a></h2>
						<?php if ( $mb_rp = mb_range_price( $slug ) ) : ?><p class="mb-prod__price"><?php echo esc_html( $mb_rp ); ?></p><?php endif; ?>
						<p><?php echo esc_html( $p['intro'] ); ?></p>
						<p class="mb-prod__suitable"><?php echo esc_html( $p['suitable'] ); ?></p>
						<p><a class="mb-btn mb-btn--caps" href="<?php echo esc_url( home_url( '/' . $slug ) ); ?>">Learn more</a></p>
					</div>
					<div class="mb-gallery" tabindex="0" aria-label="<?php echo esc_attr( $p['name'] ); ?> photos">
						<?php foreach ( $p['gallery'] as $i => $img ) : ?>
							<figure><?php echo mb_img( $img, $p['name'] . ' installed by Mobilitybase (' . ( $i + 1 ) . ')' ); // phpcs:ignore ?></figure>
						<?php endforeach; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
