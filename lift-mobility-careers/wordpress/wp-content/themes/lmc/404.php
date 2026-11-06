<?php get_header(); ?>
<section class="lmc-page-head lmc-page-head--compact"><div class="lmc-wrap"><h1>Page not found</h1></div></section>
<div class="lmc-wrap lmc-page lmc-prose">
	<p>The page you were looking for has moved or no longer exists. If you followed a link to a vacancy, it may have closed.</p>
	<?php lmc_theme_search_form( 'bar' ); ?>
	<p><a class="lmc-btn lmc-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Browse all live vacancies</a></p>
</div>
<?php get_footer(); ?>
