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
		// Who operates the business. ONE switch: sole_trader → limited (see includes/identity.php).
		'business_structure' => 'sole_trader',
		'owner_name'         => '',          // Sole trader: the owner's full legal name.
		'company_name'       => 'Lift & Mobility Careers Ltd', // Limited company: exact name as registered.
		'company_number'     => '',
		'registered_office'  => '',
		'postal_address'     => '',          // Trading / correspondence address (e.g. virtual office). Required before outreach.
		'ico_number'         => '',
		'hosting_provider'   => 'Krystal Hosting Ltd',
		'email_provider'     => '',          // Named in the Privacy Policy, e.g. "Brevo (Sendinblue SAS)".
		'legal_updated'      => '',          // YYYY-MM-DD shown as "Last updated" on the legal pages.
		'legal_entity'       => '',          // Deprecated – replaced by business_structure/owner_name/company_name.
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
		'free_listing_tier'  => 'featured', // Introductory free listing offered through outreach (standard | featured | premium).

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

		// Launch control (see includes/launch.php). Stays "prelaunch" until every blocker is cleared.
		'launch_mode'        => 'prelaunch',
		'legal_signoff_by'   => '',
		'legal_signoff_date' => '',
		'social_drafts'      => 0,           // Auto-create social post drafts. Off by default: low-maintenance mode.

		// Growth: analytics, Google, alerts, feeds, employer sharing.
		'launch_date'        => '',          // YYYY-MM-DD – month 1 of the growth plan for target tracking.
		'track_returning'    => 1,           // Count returning visitors (only for visitors who accept analytics).
		'google_sa_json'     => '',          // Service account JSON (prefer LMC_GOOGLE_SA_JSON constant) for Indexing API + Search Console.
		'gsc_property'       => '',          // e.g. sc-domain:liftandmobilitycareers.co.uk
		'google_indexing'    => 0,           // Notify Google Indexing API when vacancies go live / close.
		'indexnow_key'       => '',          // Bing/Yandex IndexNow key (auto-generated when enabled).
		'indexnow_enabled'   => 0,
		'alerts_enabled'     => 1,
		'alerts_max_per_run' => 250,         // Emails per hourly run – size to your SMTP plan.
		'feed_enabled'       => 1,           // XML job feed for job-search aggregators.
		'backlink_bonus'     => 1,           // Free Featured upgrade credit for a verified link to us.
		'monthly_report_email' => '',        // Defaults to the admin/report email.
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
				'Highlighted in candidate job alert emails',
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
				'Listed first in candidate job alert emails',
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
