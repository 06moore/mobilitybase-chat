<?php
/**
 * Book a survey / visit.
 */
get_header();
?>
<section class="mb-section mb-section--top">
	<div class="mb-wrap mb-twocol mb-twocol--form">
		<div class="mb-panel">
			<h1 class="mb-pagetitle mb-pagetitle--caps mb-pagetitle--left">Get in touch</h1>
			<p>Book a free, no-obligation home or site visit. One of our engineers will assess your space, talk through suitable options and give you a clear quote.</p>
			<?php echo do_shortcode( '[mb_form id="visit-form" fields="first_name,last_name,phone,email,postcode,message" title="Book a visit" options="Stairlift|Homelift / through-floor lift|Platform lift|Ceiling hoist|Servicing or repair|Not sure yet" options_label="What is the visit about?"]' ); ?>
		</div>
		<div class="mb-book__side">
			<?php echo mb_img( 'f377b01ade614859.webp', 'Mobilitybase engineer measuring a staircase during a home survey' ); // phpcs:ignore ?>
			<div class="mb-notice">
				<p><strong>Important:</strong> Our contact form is not monitored outside of normal working hours.</p>
				<p>If you are reporting a breakdown, please call <?php echo mb_tel(); // phpcs:ignore ?> to speak to our out-of-hours team.</p>
				<p>This applies to Gold and Silver warranty cover only.</p>
			</div>
			<ul class="mb-ticks">
				<li>Free home surveys across Kent, London &amp; the South East</li>
				<li>Clear, no-obligation quotes — no hidden costs</li>
				<li>Engineers who survey, install and service themselves</li>
			</ul>
		</div>
	</div>
</section>
<?php
get_footer();
