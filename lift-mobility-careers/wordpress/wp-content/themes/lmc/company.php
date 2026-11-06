<?php
/** Public company page: /companies/{slug}/ */
get_header();
$uid  = LMC_Employer_Share::current_company();
$name = get_user_meta( $uid, 'lmc_company', true );
$jobs = LMC_Employer_Share::live_jobs( $uid );
$site = get_user_meta( $uid, 'lmc_company_website', true );
?>
<section class="lmc-page-head">
	<div class="lmc-wrap">
		<nav class="lmc-crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Jobs</a> / <?php echo esc_html( $name ); ?></nav>
		<h1><?php echo esc_html( $name ); ?> jobs</h1>
		<p class="lmc-job__employer"><?php echo count( $jobs ) ? (int) count( $jobs ) . ' live engineering ' . esc_html( _n( 'vacancy', 'vacancies', count( $jobs ) ) ) : 'No live vacancies right now'; ?><?php if ( $site ) : ?> · <a style="color:#D5E0E6" href="<?php echo esc_url( $site ); ?>" rel="nofollow noopener" target="_blank">Company website ↗</a><?php endif; ?></p>
	</div>
</section>
<div class="lmc-wrap lmc-results lmc-results--company">
	<div class="lmc-results__main">
		<?php if ( $jobs ) : ?>
			<div class="lmc-job-list"><?php foreach ( $jobs as $j ) { lmc_render_job_card( $j->ID ); } ?></div>
		<?php else : ?>
			<div class="lmc-panel"><p><?php echo esc_html( $name ); ?> has no live vacancies on Lift and Mobility Careers at the moment. Set up a job alert to hear when they – or similar employers – are recruiting.</p></div>
		<?php endif; ?>
	</div>
	<aside class="lmc-filters"><?php echo do_shortcode( '[lmc_alert_box source="company"]' ); ?></aside>
</div>
<?php get_footer(); ?>
