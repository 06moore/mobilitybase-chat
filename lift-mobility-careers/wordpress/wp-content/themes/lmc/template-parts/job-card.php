<?php
/** Vacancy card. Expects $job_id. */
$tier   = lmc_job_tier( $job_id );
$logo   = lmc_job_meta( $job_id, 'logo_id' );
$type   = lmc_job_term_names( $job_id, 'lmc_type' );
$sector = lmc_job_terms( $job_id, 'lmc_sector' );
?>
<article class="lmc-job-card lmc-job-card--<?php echo esc_attr( $tier ); ?>">
	<div class="lmc-job-card__logo" aria-hidden="true">
		<?php if ( $logo ) : ?>
			<?php echo wp_get_attachment_image( $logo, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span><?php echo esc_html( mb_strtoupper( mb_substr( lmc_job_meta( $job_id, 'employer_name' ), 0, 1 ) ) ); ?></span>
		<?php endif; ?>
	</div>
	<div class="lmc-job-card__body">
		<?php if ( 'standard' !== $tier ) : ?><span class="lmc-tag lmc-tag--<?php echo esc_attr( $tier ); ?>"><?php echo esc_html( ucfirst( $tier ) ); ?></span><?php endif; ?>
		<h3 class="lmc-job-card__title"><a href="<?php echo esc_url( get_permalink( $job_id ) ); ?>"><?php echo esc_html( get_the_title( $job_id ) ); ?></a></h3>
		<p class="lmc-job-card__employer"><?php echo esc_html( lmc_job_meta( $job_id, 'employer_name' ) ); ?></p>
		<ul class="lmc-job-card__meta">
			<li><?php echo lmc_theme_icon( 'pin' ); ?><?php echo esc_html( lmc_location_text( $job_id ) ); ?></li>
			<li><?php echo lmc_theme_icon( 'pound' ); ?><?php echo esc_html( lmc_salary_text( $job_id ) ); ?></li>
			<?php if ( $type ) : ?><li><?php echo lmc_theme_icon( 'clock' ); ?><?php echo esc_html( $type ); ?></li><?php endif; ?>
		</ul>
	</div>
	<div class="lmc-job-card__side">
		<?php if ( $sector ) : ?><span class="lmc-chip"><?php echo esc_html( $sector[0]->name ); ?></span><?php endif; ?>
		<span class="lmc-job-card__date"><?php echo esc_html( lmc_theme_posted( $job_id ) ); ?></span>
	</div>
</article>
