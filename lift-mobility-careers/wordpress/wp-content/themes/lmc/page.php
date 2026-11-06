<?php get_header(); while ( have_posts() ) : the_post(); $wide = has_shortcode( get_the_content(), 'lmc_submit_job' ) || has_shortcode( get_the_content(), 'lmc_employer_dashboard' ) || has_shortcode( get_the_content(), 'lmc_pricing' ) || has_shortcode( get_the_content(), 'lmc_sector_grid' ); ?>
<section class="lmc-page-head lmc-page-head--compact"><div class="lmc-wrap"><h1><?php the_title(); ?></h1></div></section>
<div class="lmc-wrap <?php echo $wide ? 'lmc-page--wide' : 'lmc-page'; ?>">
	<div class="lmc-prose"><?php echo function_exists( 'lmc_render_flash' ) && ! has_shortcode( get_the_content(), 'lmc_employer_dashboard' ) ? lmc_render_flash() : ''; the_content(); ?></div>
</div>
<?php endwhile; get_footer(); ?>
