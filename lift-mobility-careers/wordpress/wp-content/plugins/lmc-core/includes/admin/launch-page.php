<?php
/** Launch checklist: the site cannot go live until every blocker is cleared. */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'lmc', 'Launch checklist', 'Launch checklist', 'manage_options', 'lmc-launch', 'lmc_admin_launch' );
}, 20 );

add_action( 'lmc_admin_action_legal_signoff', function () {
	$by   = sanitize_text_field( wp_unslash( $_POST['legal_signoff_by'] ?? '' ) );
	$date = sanitize_text_field( wp_unslash( $_POST['legal_signoff_date'] ?? '' ) );
	if ( empty( $_POST['confirm'] ) || ! $by || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		lmc_admin_redirect( 'lmc-launch', 'Enter who reviewed it, the date, and tick the confirmation.', true );
	}
	lmc_update_opts( array( 'legal_signoff_by' => $by, 'legal_signoff_date' => $date ) );
	lmc_log( 'info', 'launch', "Legal sign-off recorded: $by, $date" );
	lmc_admin_redirect( 'lmc-launch', 'Sign-off recorded.' );
} );
add_action( 'lmc_admin_action_remove_demo', function () {
	LMC_Launch::remove_demo();
	lmc_admin_redirect( 'lmc-launch', 'Demo content removed.' );
} );
add_action( 'lmc_admin_action_go_live', function () {
	$ok = LMC_Launch::go_live();
	lmc_admin_redirect( 'lmc-launch', $ok ? 'The site is LIVE and open to search engines.' : 'Cannot go live yet – clear the blockers first.', ! $ok );
} );
add_action( 'lmc_admin_action_back_to_prelaunch', function () {
	lmc_update_opts( array( 'launch_mode' => 'prelaunch' ) );
	lmc_admin_redirect( 'lmc-launch', 'Back in pre-launch mode: hidden from search engines, payments in test mode.' );
} );

add_action( 'admin_notices', function () {
	if ( current_user_can( 'manage_options' ) && ! LMC_Launch::is_live() ) {
		echo '<div class="notice notice-error"><p><strong>Pre-launch mode.</strong> The site is hidden from search engines and card payments are locked to Stripe test mode. ' . count( LMC_Launch::blockers() ) . ' launch blocker(s) remain – <a href="' . esc_url( admin_url( 'admin.php?page=lmc-launch' ) ) . '">see the launch checklist</a>.</p></div>';
	}
} );

function lmc_admin_launch() {
	$live = LMC_Launch::is_live();
	lmc_admin_header( 'Launch checklist', $live ? '<strong>Status: LIVE.</strong>' : '<strong>Status: PRE-LAUNCH.</strong> Hidden from search engines (noindex, robots.txt blocks crawling, no sitemap or job feed), no search-engine notifications, and Stripe is forced to test mode.' );
	echo '<table class="widefat striped"><thead><tr><th style="width:40px"></th><th>Check</th><th>Detail</th><th>Type</th></tr></thead><tbody>';
	foreach ( LMC_Launch::checks() as $k => $c ) {
		$icon = $c[1] ? '<span class="dashicons dashicons-yes-alt" style="color:#2e7d32" aria-label="Passed"></span>' : ( $c[3] ? '<span class="dashicons dashicons-dismiss" style="color:#d63638" aria-label="Blocker"></span>' : '<span class="dashicons dashicons-warning" style="color:#dba617" aria-label="Recommended"></span>' );
		echo '<tr><td>' . $icon . '</td><td><strong>' . esc_html( $c[0] ) . '</strong></td><td>' . esc_html( $c[2] ) . '</td><td>' . ( $c[3] ? lmc_admin_badge( 'Blocker', $c[1] ? 'good' : 'bad' ) : lmc_admin_badge( 'Recommended', $c[1] ? 'good' : 'alert' ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<div class="lmc-cols" style="margin-top:16px"><div><div class="lmc-card"><h2>Record the specialist legal review</h2><p>Only complete this once a solicitor or employment-agency compliance specialist has reviewed: whether the business is an employment agency under the Employment Agencies Act 1973 and which Conduct Regulations 2003 duties apply; the advert disclosure wording (Settings → Business details); and every legal page. See <code>docs/legal/compliance-review.md</code>.</p>' . lmc_admin_form_open( 'legal_signoff' );
	echo '<p><label>Reviewed by (name and firm)<br><input class="regular-text" name="legal_signoff_by" value="' . esc_attr( lmc_opt( 'legal_signoff_by' ) ) . '"></label></p><p><label>Date<br><input type="date" name="legal_signoff_date" value="' . esc_attr( lmc_opt( 'legal_signoff_date' ) ) . '"></label></p><p><label><input type="checkbox" name="confirm" value="1"> I confirm the specialist has approved the compliance position and the legal pages as now published.</label></p>';
	submit_button( 'Record sign-off', 'secondary', 'submit', false );
	echo '</form></div></div><div>';
	$d = LMC_Launch::demo_counts();
	echo '<div class="lmc-card"><h2>Demo content</h2><p>' . (int) $d['jobs'] . ' demo vacancies, ' . (int) $d['users'] . ' demo employers, ' . (int) $d['prospects'] . ' demo prospects. Demo vacancies are never indexed or listed in sitemaps, feeds or alerts.</p>';
	if ( array_sum( $d ) ) {
		echo '<p><a class="button" href="' . esc_url( lmc_admin_action_url( 'remove_demo' ) ) . '" onclick="return confirm(\'Delete all demo content?\')">Remove all demo content</a></p>';
	}
	echo '</div><div class="lmc-card"><h2>Go live</h2>';
	if ( $live ) {
		echo '<p>The site is live. <a class="button" href="' . esc_url( lmc_admin_action_url( 'back_to_prelaunch' ) ) . '">Return to pre-launch mode</a></p>';
	} elseif ( LMC_Launch::blockers() ) {
		echo '<p>Clear every red blocker above to enable this button.</p><p><button class="button button-primary" disabled>Go live</button></p>';
	} else {
		echo '<p>All blockers are cleared.</p><p><a class="button button-primary" href="' . esc_url( lmc_admin_action_url( 'go_live' ) ) . '">Go live</a></p>';
	}
	echo '</div></div></div>';
	lmc_admin_footer();
}
