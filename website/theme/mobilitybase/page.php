<?php
/**
 * Standard page: content written in the WordPress editor (or imported from the old site).
 */
get_header();
while ( have_posts() ) :
	the_post();
	$content = get_the_content();
	?>
	<article <?php post_class( 'mb-page' ); ?>>
		<div class="mb-wrap mb-prose">
			<?php if ( ! preg_match( '/<h1[\s>]/i', $content ) ) : ?>
				<h1><?php the_title(); ?></h1>
			<?php endif; ?>
			<?php the_content(); ?>
		</div>
	</article>
	<?php get_template_part( 'template-parts/cta' ); ?>
	<?php
endwhile;
get_footer();
