<?php
/** Settings: business identity, pricing, payments, email, templates, automation, SEO. */
defined( 'ABSPATH' ) || exit;

function lmc_settings_schema() {
	return array(
		'business' => array( 'Business details', array(
			'business_name'      => array( 'Trading name' ),
			'legal_entity'       => array( 'Legal entity', 'Company or person that legally owns the business, e.g. "Example Digital Ltd". Shown in emails and legal pages.' ),
			'company_number'     => array( 'Company number' ),
			'registered_office'  => array( 'Registered office address', '', 'textarea' ),
			'postal_address'     => array( 'Postal address for emails', 'Required before any outreach email can be sent (PECR / E-Commerce Regulations).', 'textarea' ),
			'vat_registered'     => array( 'VAT registered', 'When ticked, VAT is added at checkout and prices show "+ VAT".', 'checkbox' ),
			'vat_number'         => array( 'VAT number' ),
			'vat_rate'           => array( 'VAT rate (%)', '', 'number' ),
			'advert_disclosure'  => array( 'Disclosure shown on every vacancy', 'Conduct Regulations 2003 reg 27: adverts must give the agency\'s name and nature. Have a solicitor confirm this wording.', 'textarea' ),
			'contact_email'      => array( 'Public contact email' ),
			'admin_report_email' => array( 'Admin / weekly report email' ),
			'linkedin_url'       => array( 'LinkedIn company page URL' ),
			'facebook_url'       => array( 'Facebook page URL' ),
		) ),
		'pricing'  => array( 'Pricing & listings', array(
			'price_standard'     => array( 'Standard advert (pence, ex VAT)', '3900 = £39', 'number' ),
			'price_featured'     => array( 'Featured advert (pence)', '', 'number' ),
			'price_premium'      => array( 'Premium advert (pence)', '', 'number' ),
			'price_pack5'        => array( 'Five standard adverts (pence)', '', 'number' ),
			'price_pack10'       => array( 'Ten standard adverts (pence)', '', 'number' ),
			'listing_days'       => array( 'Listing length (days)', '', 'number' ),
			'pack_validity_days' => array( 'Pack credits valid for (days)', '', 'number' ),
			'moderation'         => array( 'Approval before publishing', '', 'select', array( 'first' => 'Review each employer\'s first vacancy (recommended)', 'all' => 'Review every vacancy', 'none' => 'Publish automatically (flagged content still held)' ) ),
			'require_verified'   => array( 'Employers must confirm email before posting', '', 'checkbox' ),
		) ),
		'payments' => array( 'Payments (Stripe)', array(
			'payments_enabled'    => array( 'Enable online payments', 'Only tick after completing the test checklist in docs/operations/stripe-setup.md.', 'checkbox' ),
			'stripe_mode'         => array( 'Mode', '', 'select', array( 'test' => 'Test mode (no real charges)', 'live' => 'Live mode' ) ),
			'stripe_test_secret'  => array( 'Test secret key (sk_test_…)', 'Or define LMC_STRIPE_TEST_SECRET in wp-config.php (recommended).', 'password' ),
			'stripe_test_webhook' => array( 'Test webhook signing secret (whsec_…)', '', 'password' ),
			'stripe_live_secret'  => array( 'Live secret key (sk_live_… or restricted rk_live_…)', 'Or define LMC_STRIPE_LIVE_SECRET in wp-config.php (recommended).', 'password' ),
			'stripe_live_webhook' => array( 'Live webhook signing secret', '', 'password' ),
			'stripe_invoices'     => array( 'Create Stripe invoices for each payment', 'Stripe charges a small extra fee per invoice. Receipts are always free.', 'checkbox' ),
		) ),
		'email'    => array( 'Email delivery', array(
			'from_name'   => array( 'From name' ),
			'from_email'  => array( 'From email', 'Use an address on your own domain, with SPF/DKIM set up (see deployment guide).' ),
			'reply_to'    => array( 'Reply-to email' ),
			'smtp_host'   => array( 'SMTP host', 'e.g. smtp-relay.brevo.com. Leave blank to use the server\'s mail.' ),
			'smtp_port'   => array( 'SMTP port', '', 'number' ),
			'smtp_secure' => array( 'Encryption', '', 'select', array( 'tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None' ) ),
			'smtp_user'   => array( 'SMTP username' ),
			'smtp_pass'   => array( 'SMTP password', 'Or define LMC_SMTP_PASS in wp-config.php (recommended).', 'password' ),
		) ),
		'automation' => array( 'Automation', array(
			'reminder_days_1'        => array( 'First expiry reminder (days before)', '', 'number' ),
			'reminder_days_2'        => array( 'Second expiry reminder (days before)', '', 'number' ),
			'filled_followup_days'   => array( '"Did you fill it?" email (days after expiry)', '', 'number' ),
			'repeat_followup_days'   => array( 'Repeat-advertising email (days after expiry)', 'Only sent if the employer has no live adverts and hasn\'t opted out.', 'number' ),
			'discovery_enabled'      => array( 'Run careers-page monitoring', '', 'checkbox' ),
			'discovery_max_per_run'  => array( 'Max careers pages checked per day', '', 'number' ),
			'outreach_daily_cap'     => array( 'Max outreach emails per day', '', 'number' ),
			'outreach_followup_days' => array( 'Follow-up reminder after outreach (days)', '', 'number' ),
			'social_webhook_url'     => array( 'Social automation webhook (Zapier/Make/Buffer)', 'Optional. Approved posts are POSTed here as JSON.' ),
			'social_drafts'          => array( 'Create social post drafts for new vacancies', 'Off by default. No advert package promises social posts, so this is optional extra promotion.', 'checkbox' ),
			'social_auto_send'       => array( 'Send social posts automatically (skip approval)', 'Needs a webhook above. Not recommended at launch.', 'checkbox' ),
		) ),
		'growth'   => array( 'Growth', array(
			'launch_date'          => array( 'Launch date (YYYY-MM-DD)', 'Month 1 of the 12-month growth plan – the monthly report compares against that month\'s targets.' ),
			'monthly_report_email' => array( 'Monthly report email', 'Blank = admin/report email.' ),
			'alerts_enabled'       => array( 'Send candidate job alerts', '', 'checkbox' ),
			'alerts_max_per_run'   => array( 'Max job alert emails per hour', 'Keep within your SMTP plan\'s limits (e.g. Brevo free = 300/day).', 'number' ),
			'track_returning'      => array( 'Measure returning visitors (asks for consent)', 'Shows the analytics consent banner even without GA4. Only visitors who accept are measured.', 'checkbox' ),
			'feed_enabled'         => array( 'Publish XML job feed for aggregators', 'Feed URL: /feeds/jobs.xml – submit it to Adzuna, Jooble, Careerjet etc. (see growth docs).', 'checkbox' ),
			'backlink_bonus'       => array( 'Reward employers who link to us', 'One free Featured advert credit per employer for a verified link.', 'checkbox' ),
			'google_indexing'      => array( 'Notify Google Indexing API on publish/close', 'Needs the service account below with Owner access in Search Console.', 'checkbox' ),
			'gsc_property'         => array( 'Search Console property', 'e.g. sc-domain:liftandmobilitycareers.co.uk or https://liftandmobilitycareers.co.uk/' ),
			'google_sa_json'       => array( 'Google service account key', 'Prefer LMC_GOOGLE_SA_JSON in wp-config.php (path to the JSON file outside the web root).', 'password' ),
			'indexnow_enabled'     => array( 'Notify Bing (IndexNow)', 'A key is generated automatically when enabled.', 'checkbox' ),
		) ),
		'seo'      => array( 'SEO & analytics', array(
			'gsc_verification'  => array( 'Google Search Console verification code', 'The content="…" value of the HTML tag method.' ),
			'bing_verification' => array( 'Bing Webmaster verification code' ),
			'ga4_id'            => array( 'Google Analytics 4 measurement ID (G-…)', 'Leave blank for no analytics cookies. When set, a consent banner is shown and GA only loads after "Accept".' ),
			'index_threshold'   => array( 'Live vacancies needed before a location page is indexed', 'Prevents thin pages.', 'number' ),
		) ),
	);
}

add_action( 'lmc_admin_action_save_settings', function () {
	$schema = lmc_settings_schema();
	$tab    = sanitize_key( $_POST['tab'] ?? 'business' );
	if ( ! isset( $schema[ $tab ] ) ) {
		return;
	}
	$vals = array();
	foreach ( $schema[ $tab ][1] as $k => $f ) {
		$type = $f[2] ?? 'text';
		$raw  = wp_unslash( $_POST[ $k ] ?? '' );
		if ( 'checkbox' === $type ) {
			$vals[ $k ] = empty( $raw ) ? 0 : 1;
		} elseif ( 'number' === $type ) {
			$vals[ $k ] = max( 0, (float) $raw );
		} elseif ( 'textarea' === $type ) {
			$vals[ $k ] = sanitize_textarea_field( $raw );
		} elseif ( 'password' === $type ) {
			if ( '' !== $raw && '••••••••' !== $raw ) {
				$vals[ $k ] = trim( sanitize_text_field( $raw ) );
			}
		} else {
			$vals[ $k ] = sanitize_text_field( $raw );
		}
	}
	if ( 'payments' === $tab && ! empty( $vals['payments_enabled'] ) ) {
		$mode = $vals['stripe_mode'] ?? 'test';
		if ( ! ( $vals[ 'stripe_' . $mode . '_secret' ] ?? lmc_opt( 'stripe_' . $mode . '_secret' ) ) || ! ( $vals[ 'stripe_' . $mode . '_webhook' ] ?? lmc_opt( 'stripe_' . $mode . '_webhook' ) ) ) {
			$vals['payments_enabled'] = 0;
			lmc_update_opts( $vals );
			lmc_admin_redirect( 'lmc-settings', 'Payments NOT enabled: add the ' . $mode . ' secret key and webhook signing secret first.', true, array( 'tab' => $tab ) );
		}
	}
	if ( 'growth' === $tab && ! empty( $vals['indexnow_enabled'] ) && ! lmc_opt( 'indexnow_key' ) ) {
		$vals['indexnow_key'] = strtolower( wp_generate_password( 32, false ) );
	}
	if ( isset( $vals['launch_date'] ) && $vals['launch_date'] && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $vals['launch_date'] ) ) {
		$vals['launch_date'] = '';
	}
	lmc_update_opts( $vals );
	lmc_admin_redirect( 'lmc-settings', 'Settings saved.', false, array( 'tab' => $tab ) );
} );

add_action( 'lmc_admin_action_save_templates', function () {
	$over = array();
	foreach ( lmc_email_templates_default() as $k => $t ) {
		$s = sanitize_text_field( wp_unslash( $_POST['tpl'][ $k ]['subject'] ?? '' ) );
		$b = sanitize_textarea_field( wp_unslash( $_POST['tpl'][ $k ]['body'] ?? '' ) );
		if ( $s !== $t['subject'] || $b !== $t['body'] ) {
			$over[ $k ] = array( 'subject' => $s, 'body' => $b );
		}
	}
	update_option( 'lmc_email_templates', $over );
	lmc_admin_redirect( 'lmc-settings', 'Email templates saved.', false, array( 'tab' => 'templates' ) );
} );

add_action( 'lmc_admin_action_test_email', function () {
	$key = sanitize_key( $_REQUEST['tpl'] ?? 'vacancy_published' );
	$to  = wp_get_current_user()->user_email;
	$demo = array( 'first_name' => 'Sam', 'company' => 'Example Lifts Ltd', 'job_title' => 'Stairlift Service Engineer', 'job_url' => home_url( '/jobs/' ), 'expiry_date' => lmc_date( lmc_now() + 7 * DAY_IN_SECONDS ), 'renew_url' => lmc_page_url( 'dashboard' ), 'views' => 120, 'clicks' => 14, 'amount' => '£69.00', 'package' => 'Featured advert', 'payment_detail' => 'This covers "Stairlift Service Engineer" (Featured advert).', 'verify_url' => home_url( '/' ), 'status_line' => 'Your vacancy is saved.', 'filled_yes_url' => home_url( '/' ), 'filled_no_url' => home_url( '/' ), 'contact_greeting' => 'Sam', 'sender_name' => lmc_opt( 'from_name' ), 'opportunity_title' => 'Stairlift Engineer', 'opportunity_location' => ' in Kent', 'original_subject' => 'A free 30-day vacancy advert', 'admin_url' => admin_url(), 'sender_email' => 'x@example.com', 'sender_company' => 'Example', 'topic' => 'Test', 'message' => 'Test', 'sender_name' => 'Test' );
	$ok = lmc_send_email( $key, $to, $demo );
	lmc_admin_redirect( 'lmc-settings', $ok ? "Test of \"$key\" sent to $to." : 'Sending failed – check SMTP settings and Logs.', ! $ok, array( 'tab' => 'templates' ) );
} );

add_action( 'lmc_admin_action_stripe_test', function () {
	$r = LMC_Payments::request( 'GET', 'balance' );
	lmc_admin_redirect( 'lmc-settings', is_wp_error( $r ) ? 'Stripe connection failed: ' . $r->get_error_message() : 'Stripe connection OK (' . LMC_Payments::mode() . ' mode).', is_wp_error( $r ), array( 'tab' => 'payments' ) );
} );

function lmc_admin_settings() {
	$schema = lmc_settings_schema();
	$tab    = sanitize_key( $_GET['tab'] ?? 'business' );
	lmc_admin_header( 'Settings' );
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $schema as $k => $s ) {
		echo '<a class="nav-tab ' . ( $tab === $k ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmc-settings&tab=' . $k ) ) . '">' . esc_html( $s[0] ) . '</a>';
	}
	echo '<a class="nav-tab ' . ( 'templates' === $tab ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmc-settings&tab=templates' ) ) . '">Email templates</a></nav>';

	if ( 'templates' === $tab ) {
		echo '<p>Placeholders like <code>{first_name}</code> are filled in automatically. A paragraph of the form <code>[button:{renew_url}|Renew]</code> becomes a button. Clear a field and save to restore the default.</p>';
		echo lmc_admin_form_open( 'save_templates' );
		foreach ( lmc_email_templates_default() as $k => $d ) {
			$t = lmc_email_template( $k );
			echo '<div class="lmc-card"><h3>' . esc_html( $d['name'] ) . ( ! empty( $d['marketing'] ) ? ' ' . lmc_admin_badge( 'Marketing – unsubscribe link added', 'alert' ) : '' ) . '</h3>';
			echo '<p><label>Subject<br><input class="large-text" name="tpl[' . esc_attr( $k ) . '][subject]" value="' . esc_attr( $t['subject'] ) . '"></label></p>';
			echo '<p><label>Body<br><textarea class="large-text code" rows="9" name="tpl[' . esc_attr( $k ) . '][body]">' . esc_textarea( $t['body'] ) . '</textarea></label></p>';
			echo '<p><a class="button" href="' . esc_url( lmc_admin_action_url( 'test_email', array( 'tpl' => $k ) ) ) . '">Send me a test</a></p></div>';
		}
		submit_button( 'Save templates' );
		echo '</form>';
		lmc_admin_footer();
		return;
	}
	if ( ! isset( $schema[ $tab ] ) ) {
		$tab = 'business';
	}
	echo lmc_admin_form_open( 'save_settings' ) . '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '"><table class="form-table"><tbody>';
	foreach ( $schema[ $tab ][1] as $k => $f ) {
		$type = $f[2] ?? 'text';
		$val  = lmc_opt( $k );
		echo '<tr><th scope="row"><label for="lmc-' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<input type="checkbox" id="lmc-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="1" ' . checked( $val, 1, false ) . '>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="3" id="lmc-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '">' . esc_textarea( $val ) . '</textarea>';
		} elseif ( 'select' === $type ) {
			echo '<select id="lmc-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '">';
			foreach ( $f[3] as $ok => $ol ) {
				echo '<option value="' . esc_attr( $ok ) . '" ' . selected( $val, $ok, false ) . '>' . esc_html( $ol ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'password' === $type ) {
			$const = defined( 'LMC_' . strtoupper( $k ) ) || ( 'smtp_pass' === $k && defined( 'LMC_SMTP_PASS' ) );
			echo $const ? '<em>Set in wp-config.php</em>' : '<input type="password" class="regular-text" autocomplete="new-password" id="lmc-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . ( $val ? '••••••••' : '' ) . '">';
		} else {
			echo '<input type="' . ( 'number' === $type ? 'number' : 'text' ) . '" class="regular-text" id="lmc-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( $val ) . '">';
			if ( 0 === strpos( $k, 'price_' ) ) {
				echo ' <span class="description">= ' . esc_html( lmc_money( $val ) ) . '</span>';
			}
		}
		if ( ! empty( $f[1] ) ) {
			echo '<p class="description">' . esc_html( $f[1] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	submit_button();
	echo '</form>';
	if ( 'payments' === $tab ) {
		echo '<div class="lmc-card"><h3>Webhook endpoint</h3><p>In Stripe → Developers → Webhooks, add this endpoint and subscribe to <code>checkout.session.completed</code>, <code>checkout.session.async_payment_succeeded</code> and <code>charge.refunded</code>:</p><p><code>' . esc_html( rest_url( 'lmc/v1/stripe-webhook' ) ) . '</code></p>';
		echo '<p><a class="button" href="' . esc_url( lmc_admin_action_url( 'stripe_test' ) ) . '">Test Stripe connection</a></p></div>';
	}
	lmc_admin_footer();
}
