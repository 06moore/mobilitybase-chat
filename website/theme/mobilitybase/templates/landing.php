<?php
/**
 * "Money page" layout: servicing, repairs, removals, installations and every town page.
 * Headline, selling points, price badge and an enquiry form sit in the first screen;
 * the page's own content (from the editor) follows, then areas covered.
 * Settings per page: mb_landing() in inc/data.php.
 */
get_header();
while ( have_posts() ) :
	the_post();
	$slug    = get_post_field( 'post_name' );
	$landing = mb_landing( $slug );
	$html    = apply_filters( 'the_content', get_the_content() );

	// Headline.
	$h1 = get_post_meta( get_the_ID(), '_mb_h1', true ) ?: get_the_title();
	if ( preg_match( '#<h1[^>]*>(.*?)</h1>#s', $html, $m ) ) {
		$h1   = wp_strip_all_tags( $m[1] );
		$html = str_replace( $m[0], '', $html );
	}

	// The hero has its own form, ticks and phone number, so remove the old in-page versions.
	$html = preg_replace( '#<form class="mb-form".*?</form>#s', '', $html );
	$html = preg_replace( '#<h2>(?:Book|Arrange|Get a Quick)[^<]*</h2>#', '', $html );
	$html = preg_replace( '#<p>(?:Fill in the form below|Takes less than 30 seconds)[^<]*</p>#', '', $html );
	$html = preg_replace( '#<ul class="mb-ticks">.*?</ul>#s', '', $html, 1 );
	$html = preg_replace( '#<p class="mb-call">.*?</p>#s', '', $html );
	$self = preg_quote( '/' . $slug, '#' );
	$html = preg_replace( '#<p class="mb-cta"><a class="mb-btn" href="' . $self . '">.*?</p>#s', '', $html );

	$figure = '';
	if ( preg_match( '#<figure class="mb-figure">.*?</figure>#s', $html, $m ) ) {
		$figure = str_replace( 'loading="lazy"', 'loading="eager"', $m[0] );
		$html   = str_replace( $m[0], '', $html );
	}

	$is_removal = str_contains( $slug, 'removal' );
	$is_install = str_contains( $slug, 'installation' ) || 'lift-installations' === $slug;
	?>
	<section class="mb-lochero">
		<div class="mb-wrap mb-lochero__inner">
			<div class="mb-lochero__text">
				<?php if ( ! empty( $landing['price'] ) ) : ?>
					<p class="mb-pricebadge"><?php echo esc_html( $landing['price'] ); ?></p>
				<?php endif; ?>
				<h1><?php echo esc_html( $h1 ); ?></h1>
				<ul class="mb-ticks">
					<?php foreach ( $landing['ticks'] as $tick ) : ?>
						<li><?php echo esc_html( $tick ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( ! empty( $landing['urgent'] ) ) : ?>
					<div class="mb-urgent">
						<p><strong>Lift broken down?</strong> Call us now on <?php echo mb_tel(); // phpcs:ignore ?></p>
						<p>Gold &amp; Silver plan customers: 24/7 breakdown line on the same number.</p>
					</div>
				<?php else : ?>
					<p class="mb-lochero__call">Prefer to speak to us? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
				<?php endif; ?>
				<?php echo str_replace( 'mb-figure', 'mb-lochero__img', $figure ); // phpcs:ignore — post content ?>
			</div>
			<div class="mb-lochero__form mb-panel" id="quote">
				<h2><?php echo esc_html( $landing['form_title'] ); ?></h2>
				<p>Fill in the form and a member of the Mobilitybase team will contact you to arrange a visit.</p>
				<?php echo do_shortcode( '[mb_form id="quote-form" ' . $landing['form'] . ']' ); ?>
			</div>
		</div>
	</section>
	<?php get_template_part( 'template-parts/trust' ); ?>
	<?php get_template_part( 'template-parts/reviews', null, array( 'limit' => 3, 'topic' => mb_review_topic( mb_slug() ) ) ); ?>
	<article class="mb-page">
		<div class="mb-wrap mb-prose mb-prose--location">
			<?php echo $html; // phpcs:ignore — post content ?>
		</div>
	</article>
	<?php
	get_template_part( 'template-parts/areas', null, array(
		'type'    => $is_removal ? 'removal' : ( $is_install ? 'installation' : 'all' ),
		'current' => $slug,
	) );
	get_template_part( 'template-parts/cta' );
endwhile;
get_footer();
