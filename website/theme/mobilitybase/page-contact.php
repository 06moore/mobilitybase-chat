<?php
/**
 * Contact page.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle mb-pagetitle--caps">Contact us today!</h1>
		<div class="mb-contact">
			<div class="mb-contact__info">
				<h2>Telephone</h2>
				<p>Tel: <?php echo mb_tel(); // phpcs:ignore ?></p>
				<h2>Email</h2>
				<p><a href="mailto:<?php echo esc_attr( mb_biz( 'email' ) ); ?>"><?php echo esc_html( mb_biz( 'email' ) ); ?></a></p>
				<h2>Opening Hours</h2>
				<p><?php echo esc_html( mb_biz( 'hours' ) ); ?></p>
				<h2>Address</h2>
				<p>Unit 29, Branbridges Industrial Estate<br>Branbridges Road, East Peckham<br>Tonbridge, Kent, TN12 5HF</p>
				<div class="mb-notice">
					<p><strong>Important:</strong> Our contact form is not monitored outside of normal working hours.</p>
					<p>If you are reporting a breakdown, please call <?php echo mb_tel(); // phpcs:ignore ?> to speak to our out-of-hours team.</p>
					<p>This applies to Gold and Silver warranty cover only.</p>
				</div>
			</div>
			<div class="mb-contact__form">
				<?php echo do_shortcode( '[mb_form id="contact-form" fields="first_name,last_name,email,phone,subject,message" title="Contact form"]' ); ?>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
