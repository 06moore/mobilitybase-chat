</main>
<footer class="mb-footer">
	<div class="mb-contactband">
		<div class="mb-wrap mb-contactband__inner">
			<div class="mb-contactband__item">
				<h2 class="mb-contactband__title"><?php echo mb_icon( 'phone' ); // phpcs:ignore ?> Call us</h2>
				<p>Tel: <?php echo mb_tel(); // phpcs:ignore ?></p>
			</div>
			<div class="mb-contactband__item">
				<h2 class="mb-contactband__title"><?php echo mb_icon( 'mail' ); // phpcs:ignore ?> Email us</h2>
				<p><a href="mailto:<?php echo esc_attr( mb_biz( 'email' ) ); ?>"><?php echo esc_html( mb_biz( 'email' ) ); ?></a></p>
			</div>
			<div class="mb-contactband__item">
				<h2 class="mb-contactband__title"><?php echo mb_icon( 'clock' ); // phpcs:ignore ?> Opening hours</h2>
				<p><?php echo esc_html( mb_biz( 'hours' ) ); ?></p>
			</div>
		</div>
	</div>
	<div class="mb-footmain">
		<div class="mb-wrap mb-footmain__inner">
			<div>
				<h2 class="mb-foot__title">Mobilitybase</h2>
				<p>Family-run since <?php echo esc_html( mb_biz( 'founded' ) ); ?>. Stairlifts, homelifts, platform lifts and ceiling hoists supplied, installed, serviced and removed by our own DBS-checked, fully insured engineers.</p>
				<ul class="mb-social" aria-label="Mobilitybase on social media">
					<?php foreach ( mb_biz( 'social' ) as $net => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php echo mb_social_icon( $net ); // phpcs:ignore ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h2 class="mb-foot__title">Our services</h2>
				<?php mb_menu( 'footer', mb_footer_services(), 'mb-foot__list' ); ?>
			</div>
			<div>
				<h2 class="mb-foot__title">Areas we cover</h2>
				<ul class="mb-foot__list mb-foot__list--cols">
					<?php foreach ( array_slice( mb_towns(), 0, 10, true ) as $town_slug => $town ) : ?>
						<li><a href="<?php echo esc_url( home_url( '/stairlift-installation-' . $town_slug ) ); ?>"><?php echo esc_html( $town ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h2 class="mb-foot__title">Where we’re based</h2>
				<address>
					<?php echo esc_html( 'Unit 29, Branbridges Industrial Estate' ); ?><br>
					<?php echo esc_html( 'Branbridges Road, ' . mb_biz( 'locality' ) ); ?><br>
					<?php echo esc_html( mb_biz( 'town' ) . ', ' . mb_biz( 'region' ) . ', ' . mb_biz( 'postcode' ) ); ?>
				</address>
			</div>
		</div>
	</div>
	<div class="mb-footbar">
		<div class="mb-wrap">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Mobilitybase Ltd. Registered in England &amp; Wales. All rights reserved. <span class="mb-footbar__links"><a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>">Privacy</a> · <a href="<?php echo esc_url( home_url( '/service-agreement-terms' ) ); ?>">Terms</a></span></p>
		</div>
	</div>
</footer>
<div class="mb-mobilebar" aria-label="Quick contact">
	<a href="tel:<?php echo esc_attr( mb_biz( 'phone_e164' ) ); ?>" class="mb-mobilebar__call"><?php echo mb_icon( 'phone' ); // phpcs:ignore ?> Call us</a>
	<a href="<?php echo esc_url( home_url( '/book-your-visit' ) ); ?>" class="mb-mobilebar__quote">Free quote</a>
</div>
<?php wp_footer(); ?>
</body>
</html>
