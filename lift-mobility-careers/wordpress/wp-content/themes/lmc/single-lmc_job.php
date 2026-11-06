<?php
get_header();
while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$live    = 'publish' === get_post_status( $id );
	$method  = lmc_job_meta( $id, 'apply_method' );
	$apply   = 'email' === $method ? 'mailto:' . antispambot( lmc_job_meta( $id, 'apply_email' ) ) . '?subject=' . rawurlencode( 'Application: ' . lmc_plain_title( $id ) . ' (via Lift and Mobility Careers)' ) : lmc_job_meta( $id, 'apply_url' );
	$logo    = lmc_job_meta( $id, 'logo_id' );
	$sectors = lmc_job_terms( $id, 'lmc_sector' );
	$details = array(
		array( 'pound', 'Salary', lmc_salary_text( $id ) . ( lmc_job_meta( $id, 'salary_note' ) ? ' ' . lmc_job_meta( $id, 'salary_note' ) : '' ) ),
		array( 'pin', 'Location', lmc_location_text( $id ) . ( lmc_job_meta( $id, 'location_note' ) ? ' – ' . lmc_job_meta( $id, 'location_note' ) : '' ) ),
		array( 'clock', 'Employment type', lmc_job_term_names( $id, 'lmc_type' ) ),
		array( 'level', 'Experience level', lmc_job_term_names( $id, 'lmc_experience' ) ),
		array( 'briefcase', 'Job category', lmc_job_term_names( $id, 'lmc_role' ) ),
		array( 'gear', 'Sector', lmc_job_term_names( $id, 'lmc_sector' ) ),
		array( 'calendar', 'Closing date', lmc_job_meta( $id, 'closing_date' ) ? lmc_date( strtotime( lmc_job_meta( $id, 'closing_date' ) ) ) : 'Advert ends ' . lmc_date( lmc_job_meta( $id, 'expires' ) ) ),
		array( 'calendar', 'Date published', lmc_date( (int) lmc_job_meta( $id, 'published_at' ) ?: get_post_time( 'U', true ) ) ),
	);
	?>
	<article class="lmc-job" data-lmc-job="<?php echo $live ? (int) $id : 0; ?>">
		<header class="lmc-page-head lmc-job__head">
			<div class="lmc-wrap">
				<nav class="lmc-crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Jobs</a><?php if ( $sectors ) : ?> / <a href="<?php echo esc_url( get_term_link( $sectors[0] ) ); ?>"><?php echo esc_html( $sectors[0]->name ); ?></a><?php endif; ?></nav>
				<div class="lmc-job__title-row">
					<div class="lmc-job-card__logo lmc-job__logo" aria-hidden="true"><?php echo $logo ? wp_get_attachment_image( $logo, 'thumbnail', false, array( 'alt' => '' ) ) : '<span>' . esc_html( mb_strtoupper( mb_substr( lmc_job_meta( $id, 'employer_name' ), 0, 1 ) ) ) . '</span>'; ?></div>
					<div>
						<?php $tier = lmc_job_tier( $id ); if ( 'standard' !== $tier && $live ) : ?><span class="lmc-tag lmc-tag--<?php echo esc_attr( $tier ); ?>"><?php echo esc_html( ucfirst( $tier ) ); ?></span><?php endif; ?>
						<h1><?php the_title(); ?></h1>
						<p class="lmc-job__employer"><?php echo esc_html( lmc_job_meta( $id, 'employer_name' ) ); ?></p>
						<ul class="lmc-job-card__meta lmc-job-card__meta--light">
							<li><?php echo lmc_theme_icon( 'pin' ); ?><?php echo esc_html( lmc_location_text( $id ) ); ?></li>
							<li><?php echo lmc_theme_icon( 'pound' ); ?><?php echo esc_html( lmc_salary_text( $id ) ); ?></li>
							<li><?php echo lmc_theme_icon( 'clock' ); ?><?php echo esc_html( lmc_job_term_names( $id, 'lmc_type' ) ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</header>
		<div class="lmc-wrap lmc-job__layout">
			<div class="lmc-job__content lmc-prose">
				<?php if ( ! $live ) : ?>
					<div class="lmc-notice lmc-notice--info" role="status"><strong>This vacancy has closed</strong> and is no longer accepting applications through this site. <a href="<?php echo esc_url( $sectors ? get_term_link( $sectors[0] ) : get_post_type_archive_link( 'lmc_job' ) ); ?>">See similar live vacancies</a>.</div>
				<?php endif; ?>
				<h2>About the role</h2>
				<?php the_content(); ?>
				<?php foreach ( array( 'responsibilities' => 'Key responsibilities', 'requirements' => 'Requirements', 'benefits' => 'Benefits' ) as $k => $label ) : $items = lmc_lines( lmc_job_meta( $id, $k ) ); if ( $items ) : ?>
					<h2><?php echo esc_html( $label ); ?></h2>
					<ul class="lmc-ticks lmc-ticks--dark"><?php foreach ( $items as $it ) : ?><li><?php echo lmc_theme_icon( 'check' ); ?> <?php echo esc_html( $it ); ?></li><?php endforeach; ?></ul>
				<?php endif; endforeach; ?>
				<p class="lmc-muted lmc-job__report"><?php echo esc_html( lmc_opt( 'advert_disclosure' ) ); ?> The employer is responsible for the vacancy and all recruitment decisions. Something wrong with this advert? <a href="<?php echo esc_url( lmc_page_url( 'contact', array( 'ref' => $id ) ) ); ?>">Report it</a>.</p>
			</div>
			<aside class="lmc-job__aside">
				<div class="lmc-apply-box">
					<?php if ( $live && $apply ) : ?>
						<a class="lmc-btn lmc-btn--primary lmc-btn--block" href="<?php echo esc_url( $apply, array( 'http', 'https', 'mailto' ) ); ?>" data-lmc-apply rel="nofollow noopener" <?php echo 'email' === $method ? '' : 'target="_blank"'; ?>>Apply for this job</a>
						<p class="lmc-muted"><?php echo 'email' === $method ? 'Opens your email app to send your application directly to the employer.' : 'You\'ll apply on the employer\'s own website.'; ?> Free – no account needed.</p>
					<?php else : ?>
						<p><strong>Applications closed.</strong></p>
					<?php endif; ?>
					<dl class="lmc-details">
						<?php foreach ( $details as $d ) : if ( trim( $d[2] ) ) : ?>
							<div><dt><?php echo lmc_theme_icon( $d[0] ); ?><?php echo esc_html( $d[1] ); ?></dt><dd><?php echo esc_html( $d[2] ); ?></dd></div>
						<?php endif; endforeach; ?>
					</dl>
				</div>
				<?php if ( lmc_job_meta( $id, 'employer_website' ) ) : ?>
					<p class="lmc-employer-link"><a href="<?php echo esc_url( lmc_job_meta( $id, 'employer_website' ) ); ?>" rel="nofollow noopener" target="_blank">Visit <?php echo esc_html( lmc_job_meta( $id, 'employer_name' ) ); ?>'s website ↗</a></p>
				<?php endif; ?>
			</aside>
		</div>
		<?php
		if ( $sectors ) :
			$related = new WP_Query( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'posts_per_page' => 3, 'post__not_in' => array( $id ), 'no_found_rows' => true, 'tax_query' => array( array( 'taxonomy' => 'lmc_sector', 'terms' => $sectors[0]->term_id ) ) ) );
			if ( $related->have_posts() ) :
				?>
				<section class="lmc-section lmc-section--grey"><div class="lmc-wrap"><h2>Similar vacancies</h2><div class="lmc-job-list"><?php while ( $related->have_posts() ) { $related->the_post(); lmc_render_job_card( get_the_ID() ); } wp_reset_postdata(); ?></div></div></section>
				<?php
			endif;
		endif;
		?>
	</article>
<?php endwhile; get_footer(); ?>
