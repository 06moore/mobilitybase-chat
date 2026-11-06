<?php
/** The single operator dashboard: everything that needs a human, on one screen. */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_approve_job', function () {
	$id = absint( $_REQUEST['job'] ?? 0 );
	if ( 'lmc_job' === get_post_type( $id ) && 'pending' === get_post_status( $id ) ) {
		LMC_Jobs::publish( $id );
		lmc_admin_redirect( 'lmc', 'Vacancy approved and published. The employer has been notified.' );
	}
} );
add_action( 'lmc_admin_action_reject_job', function () {
	$id = absint( $_REQUEST['job'] ?? 0 );
	if ( 'lmc_job' === get_post_type( $id ) ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		lmc_log( 'info', 'moderation', "Vacancy $id returned to draft by operator" );
		lmc_admin_redirect( 'lmc', 'Vacancy moved to draft. Contact the employer to explain what needs changing (and refund via Stripe if appropriate).' );
	}
} );
add_action( 'lmc_admin_action_run_now', function () {
	$what = sanitize_key( $_REQUEST['what'] ?? '' );
	if ( 'lifecycle' === $what ) {
		$r = LMC_Jobs::run_lifecycle();
		lmc_admin_redirect( 'lmc', 'Lifecycle run: ' . wp_json_encode( $r ) );
	} elseif ( 'discovery' === $what ) {
		$r = LMC_Discovery::run();
		lmc_admin_redirect( 'lmc-discovery', 'Checked ' . count( $r ) . ' careers pages.' );
	} elseif ( 'report' === $what ) {
		$ok = LMC_Reports::send_weekly();
		lmc_admin_redirect( 'lmc', $ok ? 'Weekly report sent to ' . ( lmc_opt( 'admin_report_email' ) ?: get_option( 'admin_email' ) ) : 'Report could not be sent – check email settings.', ! $ok );
	}
} );
add_action( 'lmc_admin_action_resolve_errors', function () {
	global $wpdb;
	$wpdb->query( 'UPDATE ' . lmc_table( 'log' ) . " SET resolved = 1 WHERE level = 'error'" );
	lmc_admin_redirect( 'lmc', 'Errors marked as resolved.' );
} );

function lmc_admin_dashboard() {
	global $wpdb;
	$d   = LMC_Reports::data();
	$now = lmc_now();
	lmc_admin_header( 'Lift and Mobility Careers – Dashboard', 'Everything that needs your attention is on this page. Automations run hourly (expiry, reminders), daily (discovery) and weekly (report).' );

	if ( ! LMC_Payments::enabled() ) {
		echo '<div class="notice notice-warning"><p><strong>Payments are switched off.</strong> Employers can register and submit vacancies, but cannot pay online yet. Connect and test Stripe in <a href="' . esc_url( admin_url( 'admin.php?page=lmc-settings&tab=payments' ) ) . '">Settings → Payments</a>.</p></div>';
	}
	if ( 'live' !== LMC_Payments::mode() && LMC_Payments::enabled() ) {
		echo '<div class="notice notice-info"><p>Stripe is in <strong>test mode</strong> – no real money is being taken. Revenue figures show test payments.</p></div>';
	}
	foreach ( $d['warnings'] as $w ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $w ) . '</p></div>';
	}

	$tiles = array(
		array( 'Live vacancies', $d['active'], admin_url( 'edit.php?post_type=lmc_job&post_status=publish' ) ),
		array( 'Awaiting approval', $d['pending_approval'], admin_url( 'edit.php?post_type=lmc_job&post_status=pending' ), $d['pending_approval'] ? 'alert' : '' ),
		array( 'Awaiting payment', $d['awaiting_payment'], admin_url( 'edit.php?post_type=lmc_job&post_status=lmc_unpaid' ) ),
		array( 'Expired', LMC_Reports::count_jobs( 'lmc_expired' ), admin_url( 'edit.php?post_type=lmc_job&post_status=lmc_expired' ) ),
		array( 'Employers', $d['employers_total'] . ' <small>+' . $d['employers_new'] . ' this week</small>', admin_url( 'admin.php?page=lmc-employers' ) ),
		array( 'Revenue this month', lmc_money( $d['revenue_month'] ) . ' <small>' . lmc_money( $d['revenue_week'] ) . ' this week</small>', admin_url( 'admin.php?page=lmc-payments' ) ),
		array( 'Renewals due (7 days)', count( $d['upcoming_renewals'] ), '#renewals' ),
		array( 'New discovered vacancies', $d['new_opportunities'], admin_url( 'admin.php?page=lmc-discovery' ), $d['new_opportunities'] ? 'alert' : '' ),
		array( 'Outreach follow-ups due', $d['followups_due'], admin_url( 'admin.php?page=lmc-crm&view=due' ), $d['followups_due'] ? 'alert' : '' ),
		array( 'Automation errors', $d['errors'] + $d['sources_failing'], '#errors', ( $d['errors'] + $d['sources_failing'] ) ? 'bad' : 'good' ),
	);
	echo '<div class="lmc-tiles">';
	foreach ( $tiles as $t ) {
		echo '<a class="lmc-tile ' . esc_attr( $t[3] ?? '' ) . '" href="' . esc_url( $t[2] ) . '"><span class="lmc-tile__label">' . esc_html( $t[0] ) . '</span><span class="lmc-tile__value">' . wp_kses_post( $t[1] ) . '</span></a>';
	}
	echo '</div>';

	echo '<div class="lmc-cols"><div>';
	// Approvals.
	$pending = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'pending', 'numberposts' => 20 ) );
	echo '<div class="lmc-card"><h2>Vacancies awaiting approval</h2>';
	if ( $pending ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( $pending as $p ) {
			$flags = (array) lmc_job_meta( $p->ID, 'flags' );
			echo '<tr><td><strong><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a></strong><br>' . esc_html( lmc_job_meta( $p->ID, 'employer_name' ) . ' · ' . ucfirst( lmc_job_tier( $p->ID ) ) . ' · ' . lmc_salary_text( $p->ID ) ) . ( $flags ? '<br>' . lmc_admin_badge( 'Check: ' . implode( '; ', $flags ), 'bad' ) : '' ) . '</td>'
				. '<td style="white-space:nowrap"><a class="button" href="' . esc_url( get_preview_post_link( $p->ID ) ) . '" target="_blank">Preview</a> <a class="button button-primary" href="' . esc_url( lmc_admin_action_url( 'approve_job', array( 'job' => $p->ID ) ) ) . '">Approve</a> <a class="button" href="' . esc_url( lmc_admin_action_url( 'reject_job', array( 'job' => $p->ID ) ) ) . '" onclick="return confirm(\'Return to draft?\')">Reject</a></td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p class="lmc-ok">Nothing waiting. ✓</p>';
	}
	echo '</div>';

	// Renewals.
	echo '<div class="lmc-card" id="renewals"><h2>Ending in the next 7 days</h2>';
	if ( $d['upcoming_renewals'] ) {
		echo '<ul>';
		foreach ( $d['upcoming_renewals'] as $u ) {
			echo '<li>' . esc_html( $u['title'] . ' – ' . $u['company'] ) . ' <span class="description">ends ' . esc_html( lmc_date( $u['expires'] ) ) . '</span></li>';
		}
		echo '</ul><p class="description">Employers are reminded automatically at 7 and 2 days.</p>';
	} else {
		echo '<p>None.</p>';
	}
	echo '</div>';

	// Discovery.
	$opps = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . " WHERE status = 'new' ORDER BY score DESC, first_seen DESC LIMIT 8" );
	echo '<div class="lmc-card"><h2>Newly discovered vacancies</h2>';
	if ( $opps ) {
		echo '<ul>';
		foreach ( $opps as $o ) {
			echo '<li><strong>' . esc_html( $o->title ) . '</strong> – ' . esc_html( $o->company ) . ( $o->location ? ', ' . esc_html( $o->location ) : '' ) . ' <a href="' . esc_url( $o->url ) . '" target="_blank" rel="noopener">source ↗</a></li>';
		}
		echo '</ul><p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-discovery' ) ) . '">Review all</a></p>';
	} else {
		echo '<p>No new opportunities.</p>';
	}
	echo '</div></div><div>';

	// Recent payments.
	$pay = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'payments' ) . " WHERE status IN ('paid','refunded') ORDER BY id DESC LIMIT 6" );
	echo '<div class="lmc-card"><h2>Recent payments</h2>';
	if ( $pay ) {
		echo '<ul>';
		foreach ( $pay as $p ) {
			$u = get_userdata( $p->user_id );
			echo '<li>' . esc_html( lmc_money( $p->net ) . ' – ' . ( lmc_package( $p->package )['label'] ?? $p->package ) . ' – ' . ( $u ? get_user_meta( $u->ID, 'lmc_company', true ) : '?' ) ) . ' ' . ( 'refunded' === $p->status ? lmc_admin_badge( 'Refunded', 'bad' ) : '' ) . '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No payments yet.</p>';
	}
	echo '</div>';

	// Outreach due.
	$due = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'prospects' ) . " WHERE follow_up IS NOT NULL AND follow_up <= %s AND status NOT IN ('do_not_contact','not_interested','customer') ORDER BY follow_up LIMIT 8", wp_date( 'Y-m-d', $now ) ) );
	echo '<div class="lmc-card"><h2>Outreach follow-ups due</h2>';
	if ( $due ) {
		echo '<ul>';
		foreach ( $due as $p ) {
			echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&edit=' . $p->id ) ) . '">' . esc_html( $p->company ) . '</a> – ' . esc_html( LMC_CRM::STATUSES[ $p->status ] ?? $p->status ) . ' (due ' . esc_html( $p->follow_up ) . ')</li>';
		}
		echo '</ul>';
	} else {
		echo '<p>None due.</p>';
	}
	echo '</div>';

	// Errors.
	$errs   = lmc_open_errors( 10 );
	$failing = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'sources' ) . " WHERE enabled = 1 AND status IN ('error','blocked_robots','no_parse','js_required') LIMIT 10" );
	echo '<div class="lmc-card" id="errors"><h2>Automation errors &amp; warnings</h2>';
	if ( ! $errs && ! $failing ) {
		echo '<p class="lmc-ok">All automations healthy. ✓</p>';
	}
	if ( $failing ) {
		echo '<p><strong>Careers pages that can\'t be monitored reliably:</strong></p><ul>';
		foreach ( $failing as $s ) {
			echo '<li>' . esc_html( $s->company ) . ' – ' . lmc_admin_badge( str_replace( '_', ' ', $s->status ), 'bad' ) . ' <span class="description">' . esc_html( $s->status_note ) . '</span></li>';
		}
		echo '</ul>';
	}
	if ( $errs ) {
		echo '<ul>';
		foreach ( $errs as $e ) {
			echo '<li><code>' . esc_html( $e->context ) . '</code> ' . esc_html( $e->message ) . ' <span class="description">' . esc_html( $e->created_at ) . ' UTC</span></li>';
		}
		echo '</ul><p><a class="button" href="' . esc_url( lmc_admin_action_url( 'resolve_errors' ) ) . '">Mark all resolved</a></p>';
	}
	echo '</div>';

	// Manual triggers.
	$hb = get_option( 'lmc_heartbeats', array() );
	echo '<div class="lmc-card"><h2>Automations</h2><table class="widefat"><tbody>';
	foreach ( array( 'lifecycle' => 'Expiry, reminders & follow-ups (hourly)', 'discovery' => 'Careers page monitor (daily)', 'report' => 'Weekly report (Mondays)' ) as $k => $label ) {
		$hk = 'report' === $k ? 'weekly' : $k;
		echo '<tr><td>' . esc_html( $label ) . '<br><span class="description">Last run: ' . ( isset( $hb[ $hk ] ) ? esc_html( human_time_diff( $hb[ $hk ], $now ) . ' ago' ) : 'never' ) . '</span></td><td><a class="button" href="' . esc_url( lmc_admin_action_url( 'run_now', array( 'what' => $k ) ) ) . '">Run now</a></td></tr>';
	}
	echo '</tbody></table></div>';
	echo '</div></div>';
	lmc_admin_footer();
}
