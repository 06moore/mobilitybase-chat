<?php
/**
 * Town pages (stairlift installation / removal in <town>).
 * The first heading and intro become a hero with the enquiry form alongside;
 * the rest of the page content follows below.
 */
get_header();
while ( have_posts() ) :
	the_post();
	$html = apply_filters( 'the_content', get_the_content() );
	// Pull out the H1 and the enquiry form so they can sit in the hero.
	$h1 = get_the_title();
	if ( preg_match( '#<h1[^>]*>(.*?)</h1>#s', $html, $m ) ) {
		$h1   = wp_strip_all_tags( $m[1] );
		$html = str_replace( $m[0], '', $html );
	}
	$form = '';
	if ( preg_match( '#<form class="mb-form".*?</form>#s', $html, $m ) ) {
		$form = $m[0];
		$html = str_replace( $m[0], '', $html );
	}
	// Remove the form's own heading/lead-in paragraphs, which the hero repeats.
	$html = preg_replace( '#<p>Fill in the form below[^<]*</p>#', '', $html );
	$form_title = 'Get a free quote';
	if ( preg_match( '#<h2>((?:Book|Arrange)[^<]*)</h2>#', $html, $m ) ) {
		$form_title = $m[1];
		$html       = str_replace( $m[0], '', $html );
	}
	$is_removal = str_contains( get_post_field( 'post_name' ), 'removal' );
	// The hero already has the selling points, phone number and form.
	$html = preg_replace( '#<ul class="mb-ticks">.*?</ul>#s', '', $html, 1 );
	$html = preg_replace( '#<p class="mb-call">.*?</p>#s', '', $html );
	$figure = '';
	if ( preg_match( '#<figure class="mb-figure">.*?</figure>#s', $html, $m ) ) {
		$figure = str_replace( 'loading="lazy"', 'loading="eager"', $m[0] );
		$html   = str_replace( $m[0], '', $html );
	}
	$self = preg_quote( '/' . get_post_field( 'post_name' ), '#' );
	$html = preg_replace( '#<p class="mb-cta"><a class="mb-btn" href="' . $self . '">.*?</p>#s', '', $html );
	?>
	<section class="mb-lochero">
		<div class="mb-wrap mb-lochero__inner">
			<div class="mb-lochero__text">
				<h1><?php echo esc_html( $h1 ); ?></h1>
				<ul class="mb-ticks">
					<?php if ( $is_removal ) : ?>
						<li>Straight &amp; curved stairlifts removed</li>
						<li>Staircase left clear and safe</li>
						<li>Local Kent engineers, fast response</li>
					<?php else : ?>
						<li>Straight &amp; curved stairlifts</li>
						<li>Free home surveys</li>
						<li>Experienced local engineers</li>
					<?php endif; ?>
				</ul>
				<p class="mb-lochero__call">Prefer to speak to us? Call <?php echo mb_tel(); // phpcs:ignore ?></p>
				<?php echo str_replace( 'mb-figure', 'mb-lochero__img', $figure ); // phpcs:ignore — post content ?>
			</div>
			<?php if ( $form ) : ?>
				<div class="mb-lochero__form mb-panel">
					<h2><?php echo esc_html( $form_title ); ?></h2>
					<p>Fill in the form and a member of the Mobilitybase team will contact you to arrange a visit.</p>
					<?php echo $form; // phpcs:ignore — rendered by our shortcode ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<article class="mb-page">
		<div class="mb-wrap mb-prose mb-prose--location">
			<?php echo $html; // phpcs:ignore — post content ?>
		</div>
	</article>
	<?php get_template_part( 'template-parts/cta' ); ?>
	<?php
endwhile;
get_footer();
