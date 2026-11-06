<?php get_header(); ?>
<section class="lmc-page-head lmc-page-head--compact"><div class="lmc-wrap"><h1><?php echo esc_html( wp_get_document_title() ); ?></h1></div></section>
<div class="lmc-wrap lmc-page lmc-prose">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
	<article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
<?php endwhile; the_posts_pagination(); else : ?><p>Nothing found.</p><?php endif; ?>
</div>
<?php get_footer(); ?>
