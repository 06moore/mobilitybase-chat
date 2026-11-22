<?php
/**
 * Aftercare & Warranty overview.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle mb-pagetitle--caps">Which product do you have?</h1>
		<p class="mb-section__intro">Choose the product you have below to view the available warranty and service pricing plans.</p>
		<div class="mb-cards mb-cards--wide">
			<?php foreach ( mb_warranty_products() as $w ) : ?>
				<article class="mb-card mb-card--warranty<?php echo $w[1] ? '' : ' is-soon'; ?>">
					<div class="mb-card__img"><?php echo mb_img( $w[4], $w[0] ); // phpcs:ignore ?></div>
					<div class="mb-card__body">
						<h2 class="mb-card__title"><?php echo $w[1] ? '<a href="' . esc_url( home_url( $w[1] ) ) . '">' . esc_html( $w[0] ) . '</a>' : esc_html( $w[0] ); ?></h2>
						<p class="mb-card__price"><?php echo esc_html( $w[5] ); ?><?php echo $w[1] ? ' <small>per year</small>' : ''; ?></p>
						<p><?php echo esc_html( $w[2] ); ?></p>
						<p class="mb-card__note"><?php echo esc_html( $w[3] ); ?></p>
						<?php if ( $w[1] ) : ?>
							<p><a class="mb-btn mb-btn--caps mb-btn--sm" href="<?php echo esc_url( home_url( $w[1] ) ); ?>">Learn more<span class="mb-sr"> about <?php echo esc_html( $w[0] ); ?> plans</span></a></p>
						<?php else : ?>
							<p><a class="mb-btn mb-btn--caps mb-btn--sm mb-btn--grey" href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>">Book now</a></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
