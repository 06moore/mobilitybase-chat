<?php
/**
 * Email templates and sending.
 * Every template can be overridden in WP Admin → Lift & Mobility → Settings → Email templates.
 * Placeholders use {curly_braces}. Blank lines separate paragraphs; lines starting "[button:URL]" render a button.
 */
defined( 'ABSPATH' ) || exit;

function lmc_email_templates_default() {
	return array(
		'employer_registration' => array(
			'name'    => '1. Employer registration confirmation',
			'subject' => 'Welcome to {site_name} – please confirm your email',
			'body'    => "Hi {first_name},\n\nThanks for creating an employer account for {company}. Please confirm your email address so you can start posting vacancies:\n\n[button:{verify_url}|Confirm my email]\n\nOnce confirmed, you can post a vacancy in a few minutes. Every advert runs for {listing_days} days, candidates apply directly to you, and there are no placement fees.\n\nIf you didn't create this account, you can ignore this email and nothing further will happen.\n\nKind regards,\nThe {site_name} team",
		),
		'vacancy_submitted'     => array(
			'name'    => '2. Vacancy submission confirmation',
			'subject' => 'We\'ve received your vacancy: {job_title}',
			'body'    => "Hi {first_name},\n\nThanks for submitting \"{job_title}\". Here's where things stand:\n\n{status_line}\n\nYou can review or edit the vacancy at any time from your dashboard:\n\n[button:{dashboard_url}|Go to my dashboard]\n\nKind regards,\nThe {site_name} team",
		),
		'payment_confirmation'  => array(
			'name'    => '3. Payment confirmation',
			'subject' => 'Payment received – {package}',
			'body'    => "Hi {first_name},\n\nThank you – we've received your payment of {amount} for {package}.\n\n{payment_detail}\n\nA card receipt is sent separately by our payment provider, Stripe. Your full payment history is available in your dashboard.\n\n[button:{dashboard_url}|View my dashboard]\n\nKind regards,\nThe {site_name} team",
		),
		'vacancy_published'     => array(
			'name'    => '4. Vacancy successfully published',
			'subject' => 'Your vacancy is now live: {job_title}',
			'body'    => "Hi {first_name},\n\nGood news – \"{job_title}\" is now live on {site_name} and visible to candidates.\n\n[button:{job_url}|View the live advert]\n\nThe advert runs until {expiry_date}. Applications go straight to you using the method you chose, and you'll see views and application clicks in your dashboard.\n\nTip: adverts shared by the employer get noticeably more views. Your share kit has a ready-written LinkedIn post, one-click share links and a small \"Advertised on {site_name}\" badge for your careers page:\n\n[button:{share_url}|Open my share kit]\n\nWe'll send you a reminder a week before the advert ends.\n\nKind regards,\nThe {site_name} team",
		),
		'expiry_7'              => array(
			'name'    => '5. Seven-day expiry reminder',
			'subject' => '{job_title} – your advert ends in 7 days',
			'body'    => "Hi {first_name},\n\nJust a heads-up that your advert for \"{job_title}\" will end on {expiry_date}.\n\nSo far it has had {views} views and {clicks} application clicks.\n\nIf you're still recruiting, you can extend it for another {listing_days} days now – the extra time is added to the end of the current listing, so you won't lose any days:\n\n[button:{renew_url}|Extend this advert]\n\nIf you've already found the right person, there's nothing you need to do. The advert will close automatically.\n\nKind regards,\nThe {site_name} team",
		),
		'expiry_2'              => array(
			'name'    => '6. Two-day expiry reminder',
			'subject' => '{job_title} – advert ends in 2 days',
			'body'    => "Hi {first_name},\n\nYour advert for \"{job_title}\" ends on {expiry_date}.\n\nIf the role is still open, renewing takes less than a minute:\n\n[button:{renew_url}|Renew this advert]\n\nOtherwise, it will close automatically and no further action is needed.\n\nKind regards,\nThe {site_name} team",
		),
		'vacancy_expired'       => array(
			'name'    => '7. Vacancy expired notification',
			'subject' => 'Your advert for {job_title} has ended',
			'body'    => "Hi {first_name},\n\nYour advert for \"{job_title}\" reached the end of its listing period today and has been removed from search results.\n\nIt received {views} views and {clicks} application clicks in total.\n\nIf you'd like to reopen it, you can renew it with the same details – it will go live again straight away once renewed:\n\n[button:{renew_url}|Renew and republish]\n\nKind regards,\nThe {site_name} team",
		),
		'renewal_confirmation'  => array(
			'name'    => '8. Renewal confirmation',
			'subject' => 'Renewed: {job_title} is live until {expiry_date}',
			'body'    => "Hi {first_name},\n\nThanks for renewing. \"{job_title}\" is live and will now run until {expiry_date}.\n\n[button:{job_url}|View the advert]\n\nKind regards,\nThe {site_name} team",
		),
		'filled_followup'       => array(
			'name'    => '9. Follow-up: has the vacancy been filled?',
			'subject' => 'Did you fill the {job_title} role?',
			'body'    => "Hi {first_name},\n\nYour advert for \"{job_title}\" closed a few days ago, and we'd love to know how it went. It only takes one click and helps us improve the board for employers like you:\n\n[button:{filled_yes_url}|Yes, we filled it]\n\n[button:{filled_no_url}|Not yet]\n\nIf you're still looking, you can renew the advert from your dashboard at any time:\n\n{renew_url}\n\nThanks,\nThe {site_name} team",
		),
		'repeat_followup'       => array(
			'name'    => '10. Follow-up encouraging repeat advertising',
			'subject' => 'Recruiting again at {company}?',
			'body'    => "Hi {first_name},\n\nIt's been a few weeks since your last advert with us, so we thought we'd check in.\n\nIf you have engineering roles coming up – installation, service, LOLER inspection, trainees or management – you can post them in a few minutes. If you expect to recruit regularly, our multi-advert packs work out cheaper per advert and can be used any time within 12 months.\n\n[button:{pricing_url}|See advert options]\n\nNo need to reply if now isn't the right time.\n\nKind regards,\nThe {site_name} team",
			'marketing' => true,
		),

		'payment_failed'        => array(
			'name'    => 'Payment failed',
			'subject' => 'Your payment for {package} didn\'t go through',
			'body'    => "Hi {first_name},\n\nUnfortunately your payment for {package} ({job_title}) didn't go through, so nothing has been charged and the advert hasn't been published.\n\nYour vacancy is saved – you can try again with the same or a different card from your dashboard:\n\n[button:{dashboard_url}|Go to my dashboard]\n\nKind regards,\nThe {site_name} team",
		),
		'refund_confirmation'   => array(
			'name'    => 'Refund confirmation',
			'subject' => 'Refund processed – {package}',
			'body'    => "Hi {first_name},\n\nWe've refunded {amount} for {package}. Refunds usually reach your card within 5–10 working days.\n\n{refund_detail}\n\nKind regards,\nThe {site_name} team",
		),
		'account_closed'        => array(
			'name'    => 'Employer account closed',
			'subject' => 'Your {site_name} account has been closed',
			'body'    => "Hi {first_name},\n\nAs requested, your employer account for {company} has been closed. Your live adverts have been removed and your account details deleted. We keep payment records for as long as the law requires (normally 6 years) and nothing else.\n\nYou're welcome back any time.\n\nThe {site_name} team",
		),

		// Candidate job alerts.
		'alert_confirm'         => array(
			'name'    => 'Job alerts: confirm subscription',
			'subject' => 'Confirm your job alert',
			'body'    => "Hello,\n\nPlease confirm you'd like to receive job alerts from {site_name}:\n\n{alert_summary}\n\n[button:{confirm_url}|Confirm my job alert]\n\nIf you didn't ask for this, ignore this email – you won't hear from us again and your address will be deleted within 7 days.\n\nThe {site_name} team",
		),
		'alert_manage'          => array(
			'name'    => 'Job alerts: manage existing alert',
			'subject' => 'Your job alert',
			'body'    => "Hello,\n\nSomeone (hopefully you) asked to set up a job alert for this address, which already has an active alert. You can change or cancel it here:\n\n[button:{manage_url}|Manage my job alert]\n\nIf this wasn't you, no action is needed.\n\nThe {site_name} team",
		),

		// Operational / internal.
		'admin_review'          => array(
			'name'    => 'Admin: vacancy needs approval',
			'subject' => '[Action] Vacancy awaiting approval: {job_title} ({company})',
			'body'    => "A vacancy is paid for (or credited) and waiting for your approval.\n\nVacancy: {job_title}\nEmployer: {company}\nPackage: {package}\n\n[button:{admin_url}|Review in admin]\n\nApproving publishes it immediately and notifies the employer.",
		),
		'admin_contact'         => array(
			'name'    => 'Admin: contact form message',
			'subject' => 'Website enquiry from {sender_name}',
			'body'    => "Name: {sender_name}\nEmail: {sender_email}\nCompany: {sender_company}\nTopic: {topic}\n\n{message}",
		),

		// Employer outreach (sent individually by the operator, never in bulk – see CRM).
		'outreach_free_listing' => array(
			'name'      => 'Outreach: introductory free listing offer',
			'subject'   => 'A free 30-day vacancy advert for {company}',
			'body'      => "Hello {contact_greeting},\n\nI'm getting in touch from {site_name}, a new UK job board dedicated solely to engineering roles in the stairlift, home lift, platform lift and patient handling sector.\n\nWe're inviting a small number of employers in the industry to try the board, and I'd like to offer {company} one free 30-day vacancy advert. There's no payment, no card details and no obligation to buy anything afterwards.\n\nIf you have an engineering role open now or coming up – installation, service, LOLER inspection, trainees or supervisors – simply reply to this email and we'll set it up, or create an account here:\n\n{register_url}\n\nCandidates apply directly to you, every advert shows salary, and we never charge placement fees.\n\nKind regards,\n{sender_name}\n{site_name}",
			'marketing' => true,
		),
		'outreach_vacancy_feature' => array(
			'name'      => 'Outreach: may we feature your vacancy?',
			'subject'   => 'Your {opportunity_title} vacancy – may we feature it?',
			'body'      => "Hello {contact_greeting},\n\nI noticed {company} is currently recruiting for a {opportunity_title}{opportunity_location}. I run {site_name}, a UK job board focused only on the stairlift, lift and patient handling engineering sector.\n\nWith your permission, we would like to feature this vacancy free of charge for 30 days. We would write a short original summary and link candidates straight to your own application page – we wouldn't copy your advert or use your logo unless you ask us to.\n\nIf you're happy for us to go ahead, just reply \"yes\" and we'll send you the summary to check before anything is published. If not, no problem at all – we won't contact you about it again.\n\nKind regards,\n{sender_name}\n{site_name}",
			'marketing' => true,
		),
		'outreach_followup'     => array(
			'name'      => 'Outreach: single polite follow-up',
			'subject'   => 'Re: {original_subject}',
			'body'      => "Hello {contact_greeting},\n\nJust following up on my note last week about a free 30-day vacancy advert for {company} on {site_name}. If it's useful, the offer stands – simply reply and we'll set it up.\n\nIf it isn't relevant, I won't follow up again.\n\nKind regards,\n{sender_name}\n{site_name}",
			'marketing' => true,
		),
	);
}

function lmc_email_template( $key ) {
	$defaults  = lmc_email_templates_default();
	$overrides = (array) get_option( 'lmc_email_templates', array() );
	if ( ! isset( $defaults[ $key ] ) ) {
		return null;
	}
	$t = $defaults[ $key ];
	if ( ! empty( $overrides[ $key ]['subject'] ) ) {
		$t['subject'] = $overrides[ $key ]['subject'];
	}
	if ( ! empty( $overrides[ $key ]['body'] ) ) {
		$t['body'] = $overrides[ $key ]['body'];
	}
	return $t;
}

function lmc_email_common_vars() {
	return array(
		'site_name'     => lmc_opt( 'business_name' ),
		'listing_days'  => (int) lmc_opt( 'listing_days' ),
		'dashboard_url' => lmc_page_url( 'dashboard' ),
		'pricing_url'   => lmc_page_url( 'advertise' ),
		'register_url'  => lmc_page_url( 'register' ),
		'contact_email' => lmc_opt( 'contact_email' ),
	);
}

/** Variables describing a vacancy and its employer, for templates. */
function lmc_job_email_vars( $job_id ) {
	$job  = get_post( $job_id );
	$user = get_userdata( $job->post_author );
	$exp  = (int) lmc_job_meta( $job_id, 'expires' );
	$stat = LMC_Stats::totals( $job_id );
	return array(
		'job_title'      => lmc_plain_title( $job_id ),
		'job_url'        => get_permalink( $job_id ),
		'company'        => lmc_job_meta( $job_id, 'employer_name' ) ?: ( $user ? get_user_meta( $user->ID, 'lmc_company', true ) : '' ),
		'first_name'     => $user ? ( $user->first_name ?: 'there' ) : 'there',
		'expiry_date'    => $exp ? lmc_date( $exp ) : '',
		'renew_url'      => lmc_page_url( 'dashboard', array( 'lmc_renew' => $job_id ) ),
		'views'          => number_format( $stat['views'] ),
		'clicks'         => number_format( $stat['clicks'] ),
		'admin_url'      => admin_url( 'post.php?post=' . $job_id . '&action=edit' ),
		'share_url'      => lmc_page_url( 'dashboard', array( 'lmc_share' => $job_id ) ),
		'package'        => ( lmc_package( lmc_job_tier( $job_id ) )['label'] ?? '' ),
		'filled_yes_url' => add_query_arg( array( 'lmc_filled' => $job_id, 'a' => 'yes', 's' => lmc_sign( 'filled' . $job_id ) ), home_url( '/' ) ),
		'filled_no_url'  => add_query_arg( array( 'lmc_filled' => $job_id, 'a' => 'no', 's' => lmc_sign( 'filled' . $job_id ) ), home_url( '/' ) ),
	);
}

/** Render "[button:URL|Label]" and paragraphs into branded HTML. */
function lmc_email_render( $body, $footer_extra = '', $raw_html = null ) {
	$blocks = null === $raw_html ? preg_split( "/\n\s*\n/", trim( $body ) ) : array();
	$html   = (string) $raw_html;
	foreach ( $blocks as $b ) {
		if ( preg_match( '/^\[button:([^|\]]+)\|?([^\]]*)\]$/', trim( $b ), $m ) ) {
			$label = $m[2] ?: 'Open';
			$html .= '<p style="margin:24px 0"><a href="' . esc_url( $m[1] ) . '" style="background:#A7D943;color:#142F40;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;display:inline-block">' . esc_html( $label ) . '</a></p>';
		} else {
			$txt   = esc_html( $b );
			$txt   = preg_replace( '#(https?://[^\s<]+)#', '<a href="$1" style="color:#142F40">$1</a>', $txt );
			$html .= '<p style="margin:0 0 16px;line-height:1.6">' . nl2br( $txt ) . '</p>';
		}
	}
	$identity = array_filter( array( lmc_opt( 'business_name' ), lmc_opt( 'legal_entity' ) ? 'a trading name of ' . lmc_opt( 'legal_entity' ) : '', lmc_opt( 'company_number' ) ? 'Company no. ' . lmc_opt( 'company_number' ) : '', lmc_opt( 'postal_address' ) ) );
	return '<!doctype html><html><body style="margin:0;background:#F4F6F7;font-family:Arial,Helvetica,sans-serif;color:#142F40">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F6F7;padding:24px 0"><tr><td align="center">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden">'
		. '<tr><td style="background:#142F40;padding:20px 28px;color:#ffffff;font-size:18px;font-weight:700">Lift and Mobility <span style="color:#A7D943">Careers</span></td></tr>'
		. '<tr><td style="padding:28px;font-size:15px">' . $html . '</td></tr>'
		. '<tr><td style="padding:18px 28px;background:#F4F6F7;font-size:12px;color:#55636d;line-height:1.5">' . esc_html( implode( ' · ', $identity ) ) . ( $footer_extra ? '<br>' . $footer_extra : '' ) . '</td></tr>'
		. '</table></td></tr></table></body></html>';
}

function lmc_email_replace( $text, $vars ) {
	foreach ( $vars as $k => $v ) {
		$text = str_replace( '{' . $k . '}', (string) $v, $text );
	}
	return $text;
}

/**
 * Send a templated email. Returns true on success.
 *
 * @param string $key       Template key.
 * @param string $to        Recipient.
 * @param array  $vars      Placeholder values.
 * @param int    $object_id Related vacancy/prospect id (for the email log and de-duplication).
 */
function lmc_send_email( $key, $to, $vars = array(), $object_id = 0, $args = array() ) {
	$t = lmc_email_template( $key );
	if ( ! $t || ! is_email( $to ) ) {
		lmc_log( 'error', 'email', "Template $key or recipient '$to' invalid" );
		return false;
	}
	$is_marketing = ! empty( $t['marketing'] );
	if ( $is_marketing && LMC_CRM::is_suppressed( $to ) ) {
		lmc_log( 'info', 'email', "Skipped $key to suppressed address $to" );
		return false;
	}
	$vars    = array_merge( lmc_email_common_vars(), $vars );
	$subject = lmc_email_replace( $t['subject'], $vars );
	$body    = lmc_email_replace( $t['body'], $vars );
	$footer  = '';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $is_marketing ) {
		$unsub   = LMC_CRM::unsubscribe_url( $to );
		$footer  = 'You are receiving this because ' . esc_html( $args['reason'] ?? 'of your business relationship with us' ) . '. <a href="' . esc_url( $unsub ) . '" style="color:#142F40">Unsubscribe</a> and we will not email you again.';
		$headers[] = 'List-Unsubscribe: <' . esc_url_raw( $unsub ) . '>';
		$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
	}
	if ( lmc_opt( 'reply_to' ) ) {
		$headers[] = 'Reply-To: ' . lmc_opt( 'reply_to' );
	}
	$html = lmc_email_render( $body, $footer );
	$ok   = wp_mail( $to, $subject, $html, $headers );
	global $wpdb;
	$wpdb->insert( lmc_table( 'email_log' ), array( 'recipient' => $to, 'template' => $key, 'subject' => $subject, 'object_id' => (int) $object_id, 'status' => $ok ? 'sent' : 'failed', 'created_at' => lmc_mysql_time() ) );
	return (bool) $ok;
}

/** Send to the vacancy's employer. */
function lmc_send_job_email( $key, $job_id, $extra = array() ) {
	$job  = get_post( $job_id );
	$user = $job ? get_userdata( $job->post_author ) : null;
	if ( ! $user ) {
		return false;
	}
	if ( 'repeat_followup' === $key && get_user_meta( $user->ID, 'lmc_marketing_opt_out', true ) ) {
		return false;
	}
	$args = 'repeat_followup' === $key ? array( 'reason' => 'you have advertised with us before' ) : array();
	return lmc_send_email( $key, $user->user_email, array_merge( lmc_job_email_vars( $job_id ), $extra ), $job_id, $args );
}

function lmc_send_admin_email( $key, $vars = array(), $object_id = 0 ) {
	return lmc_send_email( $key, lmc_opt( 'admin_report_email' ) ?: get_option( 'admin_email' ), $vars, $object_id );
}

/* ---------- Transport ---------- */

add_filter( 'wp_mail_from', function ( $from ) {
	return lmc_opt( 'from_email' ) ?: $from;
} );
add_filter( 'wp_mail_from_name', function ( $name ) {
	return lmc_opt( 'from_name' ) ?: $name;
} );

add_action( 'phpmailer_init', function ( $mailer ) {
	if ( 'text/html' === $mailer->ContentType && $mailer->Body ) {
		$text            = preg_replace( '#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#', '$2 ($1)', $mailer->Body );
		$mailer->AltBody = trim( html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '<br>' ), "\n\n", $text ) ) ) );
	}
	if ( lmc_opt( 'smtp_host' ) ) {
		$mailer->isSMTP();
		$mailer->Host       = lmc_opt( 'smtp_host' );
		$mailer->Port       = (int) lmc_opt( 'smtp_port' );
		$mailer->SMTPSecure = lmc_opt( 'smtp_secure' ) === 'none' ? '' : lmc_opt( 'smtp_secure' );
		if ( lmc_opt( 'smtp_user' ) ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = lmc_opt( 'smtp_user' );
			$mailer->Password = lmc_opt( 'smtp_pass' );
		}
	}
} );

/** Development capture: with LMC_MAIL_CAPTURE defined, emails are written to a file and never sent. */
if ( defined( 'LMC_MAIL_CAPTURE' ) && LMC_MAIL_CAPTURE ) {
	add_filter( 'pre_wp_mail', function ( $null, $atts ) {
		$to = is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'];
		$body = preg_replace( '#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#', '$2 [$1]', $atts['message'] );
		$body = html_entity_decode( wp_strip_all_tags( str_replace( array( '</p>', '</td>', '<br>', '</li>' ), "\n", $body ) ), ENT_QUOTES );
		file_put_contents( LMC_MAIL_CAPTURE, "=====\nTO: $to\nSUBJECT: {$atts['subject']}\n" . $body . "\n", FILE_APPEND );
		return true;
	}, 10, 2 );
}
