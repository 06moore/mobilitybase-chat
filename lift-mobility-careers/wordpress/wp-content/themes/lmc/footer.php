</main>
<footer class="lmc-footer">
	<div class="lmc-wrap lmc-footer__grid">
		<div class="lmc-footer__brand">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-primary-reversed.svg' ); ?>" width="221" height="60" alt="Lift and Mobility Careers" loading="lazy">
			<p>Connecting mobility engineering talent with opportunity.</p>
			<p class="lmc-footer__small">An independent UK job board for the stairlift, home lift, platform lift and patient handling industry. We are an advertising platform, not a recruitment agency: applications go directly to employers.</p>
		</div>
		<div>
			<h2>Candidates</h2>
			<ul>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>">Browse all jobs</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'sectors' ) ); ?>">Engineering sectors</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'lp_stairlift_service' ) ); ?>">Stairlift service engineer jobs</a></li>
				<li><a href="<?php echo esc_url( home_url( '/jobs/role/loler-inspector/' ) ); ?>">LOLER inspector jobs</a></li>
				<li><a href="<?php echo esc_url( home_url( '/jobs/role/apprentice/' ) ); ?>">Apprenticeships</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'alerts' ) ); ?>">Job alerts</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'guides' ) ); ?>">Career guides</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'g_salary' ) ); ?>">Salary guide</a></li>
			</ul>
		</div>
		<div>
			<h2>Employers</h2>
			<ul>
				<li><a href="<?php echo esc_url( lmc_page_url( 'advertise' ) ); ?>">Advertise a vacancy</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'employers' ) ); ?>">How it works</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'register' ) ); ?>">Create an account</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'login' ) ); ?>">Employer sign in</a></li>
			</ul>
		</div>
		<div>
			<h2>Company</h2>
			<ul>
				<li><a href="<?php echo esc_url( lmc_page_url( 'about' ) ); ?>">About us</a></li>
				<li><a href="<?php echo esc_url( lmc_page_url( 'contact' ) ); ?>">Contact</a></li>
				<?php if ( lmc_opt( 'linkedin_url' ) ) : ?><li><a href="<?php echo esc_url( lmc_opt( 'linkedin_url' ) ); ?>" rel="noopener">LinkedIn</a></li><?php endif; ?>
				<?php if ( lmc_opt( 'facebook_url' ) ) : ?><li><a href="<?php echo esc_url( lmc_opt( 'facebook_url' ) ); ?>" rel="noopener">Facebook</a></li><?php endif; ?>
			</ul>
		</div>
	</div>
	<div class="lmc-wrap lmc-footer__legal">
		<nav aria-label="Legal">
			<?php foreach ( array( 'terms' => 'Website terms', 'agreement' => 'Employer terms', 'privacy' => 'Privacy', 'cookies' => 'Cookie policy' ) as $k => $l ) : ?>
				<a href="<?php echo esc_url( lmc_page_url( $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
			<?php if ( lmc_opt( 'ga4_id' ) || lmc_opt( 'track_returning' ) ) : ?><button type="button" class="lmc-link lmc-footer__cookies" onclick="window.lmcResetConsent&&window.lmcResetConsent()">Cookie settings</button><?php endif; ?>
		</nav>
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( lmc_opt( 'business_name' ) ); ?><?php $lmc_id = lmc_identity(); echo $lmc_id['footer'] && ! $lmc_id['missing'] ? ' · ' . esc_html( $lmc_id['footer'] ) : ''; ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
