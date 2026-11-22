<?php
/**
 * Services overview.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap">
		<h1 class="mb-pagetitle mb-pagetitle--caps">Which service do you need?</h1>
		<p class="mb-section__intro">Choose the service you’re looking for below and submit an enquiry. Our team will review the details and get back to you to arrange the next steps.</p>
		<div class="mb-services">
			<?php foreach ( mb_services() as $s ) : ?>
				<article class="mb-service">
					<?php echo mb_icon( $s[2] ); // phpcs:ignore ?>
					<h2><a href="<?php echo esc_url( home_url( $s[1] ) ); ?>"><?php echo esc_html( $s[0] ); ?></a></h2>
					<p><?php echo esc_html( $s[3] ); ?></p>
					<p class="mb-service__note"><?php echo esc_html( $s[4] ); ?></p>
					<p class="mb-service__more"><a class="mb-btn mb-btn--caps mb-btn--sm" href="<?php echo esc_url( home_url( $s[1] ) ); ?>">Learn more<span class="mb-sr"> about <?php echo esc_html( $s[0] ); ?></span></a></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="mb-section mb-section--grey" id="enquiry">
	<div class="mb-wrap mb-twocol">
		<div>
			<h2 class="mb-title mb-title--left">Send us an enquiry</h2>
			<p>Tell us what you need and which area you’re in. We’ll get back to you to arrange the next steps.</p>
			<p><strong>Reporting a breakdown?</strong> Please call <?php echo mb_tel(); // phpcs:ignore ?>. Out-of-hours breakdown support applies to Gold and Silver warranty cover.</p>
		</div>
		<div class="mb-panel">
			<?php echo do_shortcode( '[mb_form id="service-form" fields="name,phone,email,postcode,message" title="Service enquiry" options="Call-out & repairs|New installation|Servicing & maintenance|Lift removal|LOLER examination|Survey & advice" options_label="Which service do you need?"]' ); ?>
		</div>
	</div>
</section>
<?php
get_footer();
