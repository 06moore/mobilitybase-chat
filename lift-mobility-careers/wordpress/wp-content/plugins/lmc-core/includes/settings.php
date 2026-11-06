<?php
/**
 * Central settings. Everything commercial (prices, durations, moderation, keys) lives here
 * so it can be changed from WP Admin → Lift & Mobility → Settings without touching code.
 */
defined( 'ABSPATH' ) || exit;

function lmc_settings_defaults() {
	return array(
		// Business identity (shown in footer, emails and legal pages – required by the E-Commerce Regs / PECR).
		'business_name'      => 'Lift and Mobility Careers',
		'legal_entity'       => '',          // e.g. "LMC Digital Ltd" – the legal owner of the trading name.
		'company_number'     => '',
		'registered_office'  => '',
		'postal_address'     => '',          // Required before any outreach email can be sent.
		'vat_number'         => '',
		'vat_registered'     => 0,           // When 1, 20% VAT is added at checkout and "+VAT" shown.
		'vat_rate'           => 20,
		'contact_email'      => 'hello@example.com',
		'admin_report_email' => get_option( 'admin_email' ),
		'from_name'          => 'Lift and Mobility Careers',
		'from_email'         => '',          // Defaults to WordPress admin email when blank.
		'reply_to'           => '',

		// Pricing (pence, excluding VAT) and durations.
		'price_standard'     => 3900,
		'price_featured'     => 6900,
		'price_premium'      => 9900,
		'price_pack5'        => 17500,
		'price_pack10'       => 30000,
		'listing_days'       => 30,
		'pack_validity_days' => 365,

		// Workflow.
		'moderation'         => 'first',     // all | first | none – "first" = review each employer's first vacancy.
		'require_verified'   => 1,           // Employer must confirm email before submitting.
		'reminder_days_1'    => 7,
		'reminder_days_2'    => 2,
		'filled_followup_days' => 3,
		'repeat_followup_days' => 21,

		// Payments (Stripe Checkout). Payments stay OFF until explicitly enabled after testing.
		'payments_enabled'   => 0,
		'stripe_mode'        => 'test',      // test | live
		'stripe_test_secret' => '',
		'stripe_test_webhook'=> '',
		'stripe_live_secret' => '',
		'stripe_live_webhook'=> '',
		'stripe_invoices'    => 0,           // Stripe charges extra for Checkout invoices; receipts are free.

		// Email transport (SMTP). Leave host blank to use the host's mail() or an SMTP plugin.
		'smtp_host'          => '',
		'smtp_port'          => 587,
		'smtp_secure'        => 'tls',
		'smtp_user'          => '',
		'smtp_pass'          => '',          // Prefer defining LMC_SMTP_PASS in wp-config.php.

		// Discovery and outreach.
		'discovery_enabled'  => 1,
		'discovery_max_per_run' => 15,
		'outreach_daily_cap' => 10,
		'outreach_followup_days' => 10,

		// Social.
		'social_webhook_url' => '',          // Optional Zapier/Make/Buffer webhook for approved posts.
		'social_auto_send'   => 0,

		// SEO / analytics.
		'ga4_id'             => '',
		'gsc_verification'   => '',
		'bing_verification'  => '',
		'linkedin_url'       => '',
		'facebook_url'       => '',
		'index_threshold'    => 3,
		// Shown on every vacancy page. Conduct Regs 2003 reg 27 requires agencies' adverts to name the agency and state its nature – confirm wording with a solicitor.
		'advert_disclosure'  => 'Advertised by Lift and Mobility Careers, an employment agency providing job advertising services only. We are not the employer and do not charge candidates any fee.',           // Location/role pages without curated copy need ≥ this many live jobs to be indexed.
	);
}

function lmc_settings() {
	static $cache = null;
	if ( null === $cache || doing_action( 'update_option_lmc_settings' ) ) {
		$cache = wp_parse_args( (array) get_option( 'lmc_settings', array() ), lmc_settings_defaults() );
	}
	return $cache;
}

function lmc_opt( $key ) {
	$s = wp_parse_args( (array) get_option( 'lmc_settings', array() ), lmc_settings_defaults() );
	if ( 'smtp_pass' === $key && defined( 'LMC_SMTP_PASS' ) ) {
		return LMC_SMTP_PASS;
	}
	if ( in_array( $key, array( 'stripe_test_secret', 'stripe_live_secret', 'stripe_test_webhook', 'stripe_live_webhook' ), true ) && defined( 'LMC_' . strtoupper( $key ) ) ) {
		return constant( 'LMC_' . strtoupper( $key ) );
	}
	return $s[ $key ] ?? null;
}

function lmc_update_opts( array $values ) {
	$s = wp_parse_args( (array) get_option( 'lmc_settings', array() ), lmc_settings_defaults() );
	update_option( 'lmc_settings', array_merge( $s, $values ) );
}

/**
 * Advertising packages. Prices come from settings so they can be changed any time.
 * 'tier' is the visibility level a single listing receives; packs grant standard credits.
 */
function lmc_packages() {
	$days = (int) lmc_opt( 'listing_days' );
	$p    = array(
		'standard' => array(
			'label'    => 'Standard advert',
			'price'    => (int) lmc_opt( 'price_standard' ),
			'tier'     => 'standard',
			'credits'  => 1,
			'summary'  => $days . '-day listing in search and sector pages',
			'features' => array(
				$days . ' days live on the job board',
				'Listed in search results and relevant sector pages',
				'Google for Jobs structured data',
				'Applications go straight to you – no fees per hire',
				'Expiry reminders and one-click renewal',
			),
		),
		'featured' => array(
			'label'    => 'Featured advert',
			'price'    => (int) lmc_opt( 'price_featured' ),
			'tier'     => 'featured',
			'credits'  => 1,
			'summary'  => 'Highlighted and ranked above standard adverts',
			'features' => array(
				'Everything in Standard',
				'Highlighted "Featured" listing design',
				'Ranked above standard adverts in search and sector pages',
				'Included in the weekly vacancy round-up post on our social channels',
			),
		),
		'premium'  => array(
			'label'    => 'Premium advert',
			'price'    => (int) lmc_opt( 'price_premium' ),
			'tier'     => 'premium',
			'credits'  => 1,
			'summary'  => 'Homepage exposure and top positioning',
			'features' => array(
				'Everything in Featured',
				'Shown in the homepage "Premium vacancies" section',
				'Top priority position in search and sector pages',
				'Your own dedicated vacancy post on LinkedIn and Facebook',
			),
		),
		'pack5'    => array(
			'label'    => 'Five standard adverts',
			'price'    => (int) lmc_opt( 'price_pack5' ),
			'tier'     => 'standard',
			'credits'  => 5,
			'summary'  => 'Five standard credits, use any time within 12 months',
			'features' => array( '5 standard adverts', 'Use whenever you need within 12 months', 'Saving on single adverts' ),
		),
		'pack10'   => array(
			'label'    => 'Ten standard adverts',
			'price'    => (int) lmc_opt( 'price_pack10' ),
			'tier'     => 'standard',
			'credits'  => 10,
			'summary'  => 'Ten standard credits, use any time within 12 months',
			'features' => array( '10 standard adverts', 'Use whenever you need within 12 months', 'Best value per advert' ),
		),
	);
	return apply_filters( 'lmc_packages', $p );
}

function lmc_package( $key ) {
	$all = lmc_packages();
	return $all[ $key ] ?? null;
}

function lmc_tier_priority( $tier ) {
	return array( 'premium' => 3, 'featured' => 2, 'standard' => 1 )[ $tier ] ?? 1;
}

function lmc_vat_amount( $net_pence ) {
	return lmc_opt( 'vat_registered' ) ? (int) round( $net_pence * ( (float) lmc_opt( 'vat_rate' ) / 100 ) ) : 0;
}

function lmc_price_suffix() {
	return lmc_opt( 'vat_registered' ) ? ' + VAT' : '';
}
