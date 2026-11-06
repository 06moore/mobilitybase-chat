<?php
/** Traffic & growth dashboard: last 30 days of on-site analytics, subscribers, shares and SEO tools. */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_monthly_report', function () {
	$ok = LMC_Monthly_Report::send( sanitize_text_field( wp_unslash( $_REQUEST['month'] ?? '' ) ) ?: null );
	lmc_admin_redirect( 'lmc-growth', $ok ? 'Monthly report sent.' : 'Report could not be sent – check email settings.', ! $ok );
} );
add_action( 'lmc_admin_action_run_alerts', function () {
	$n = LMC_Alerts::run();
	lmc_admin_redirect( 'lmc-growth', "Job alert run complete: $n emails sent (only subscribers who are due and have new matches receive one)." );
} );

function lmc_admin_growth() {
	global $wpdb;
	$to   = wp_date( 'Y-m-d', lmc_now() + DAY_IN_SECONDS );
	$from = wp_date( 'Y-m-d', lmc_now() - 29 * DAY_IN_SECONDS );
	$pf   = wp_date( 'Y-m-d', lmc_now() - 59 * DAY_IN_SECONDS );
	$visits  = LMC_Analytics::sum( 'visit', $from, $to );
	$pvisits = LMC_Analytics::sum( 'visit', $pf, $from );
	$sources = LMC_Analytics::sum( 'source', $from, $to, true, 12 );
	$views   = LMC_Stats::period( lmc_now() - 29 * DAY_IN_SECONDS, lmc_now() + DAY_IN_SECONDS );
	$alerts  = LMC_Alerts::counts();
	$ret     = LMC_Analytics::sum( 'visitor_returning', $from, $to );
	$new     = LMC_Analytics::sum( 'visitor_new', $from, $to );
	lmc_admin_header( 'Traffic & growth', 'Last 30 days, measured on-site without cookies. Detailed visitor analytics: Google Analytics (if connected). Search performance: Google Search Console.' );
	$num = LMC_Monthly_Report::month_number( lmc_now() );
	$tg  = LMC_Monthly_Report::targets()[ $num ] ?? null;
	$tiles = array(
		array( 'Visits', number_format( $visits ) . ' <small>' . ( $pvisits ? ( $visits >= $pvisits ? '+' : '' ) . round( ( $visits - $pvisits ) / $pvisits * 100 ) . '% vs previous 30 days' : 'first period' ) . ( $tg ? ' · month ' . $num . ' target ' . number_format( $tg['visits'] ) : '' ) . '</small>' ),
		array( 'From search engines', ( $visits ? round( ( $sources['Organic search'] ?? 0 ) / $visits * 100 ) : 0 ) . '% <small>' . number_format( $sources['Organic search'] ?? 0 ) . ' visits</small>' ),
		array( 'Job views', number_format( $views['views'] ) . ( $tg ? ' <small>target ' . number_format( $tg['job_views'] ) . '</small>' : '' ) ),
		array( 'Apply Now clicks', number_format( $views['clicks'] ) . ' <small>' . ( $views['views'] ? round( $views['clicks'] / $views['views'] * 100, 1 ) . '% of job views' : '' ) . '</small>' ),
		array( 'Returning visitors', ( $ret + $new ? round( $ret / ( $ret + $new ) * 100 ) . '%' : '–' ) . ' <small>of consenting visitors</small>' ),
		array( 'Job alert subscribers', number_format( $alerts['active'] ) . ' <small>' . (int) $alerts['pending'] . ' awaiting confirmation' . ( $tg ? ' · target ' . number_format( $tg['subscribers'] ) : '' ) . '</small>' ),
		array( 'Vacancy shares', number_format( LMC_Analytics::sum( 'share', $from, $to ) ) ),
		array( 'Verified employer backlinks', number_format( LMC_Employer_Share::live_backlink_count() ) ),
	);
	echo '<div class="lmc-tiles">';
	foreach ( $tiles as $t ) {
		echo '<div class="lmc-tile"><span class="lmc-tile__label">' . esc_html( $t[0] ) . '</span><span class="lmc-tile__value">' . wp_kses_post( $t[1] ) . '</span></div>';
	}
	echo '</div><div class="lmc-cols"><div>';
	$table = function ( $title, $rows, $total = 0 ) {
		echo '<div class="lmc-card"><h2>' . esc_html( $title ) . '</h2>';
		if ( ! $rows ) {
			echo '<p>No data yet.</p></div>';
			return;
		}
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rows as $k => $v ) {
			echo '<tr><td>' . esc_html( $k ?: '(none)' ) . '</td><td style="text-align:right">' . number_format( (int) $v ) . ( $total ? ' <span class="description">(' . round( $v / $total * 100 ) . '%)</span>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	};
	$table( 'Traffic sources', $sources, $visits );
	$table( 'Job views by source', LMC_Analytics::sum( 'job_view_source', $from, $to, true, 12 ) );
	$table( 'Top landing pages', LMC_Analytics::sum( 'landing', $from, $to, true, 15 ) );
	echo '</div><div>';
	$table( 'Top referring websites', LMC_Analytics::sum( 'referrer', $from, $to, true, 15 ) );
	$table( 'Shares by network', LMC_Analytics::sum( 'share', $from, $to, true ) );
	$table( 'Job alert sign-ups by location on site', LMC_Analytics::sum( 'alert_signup', $from, $to, true ) );
	$gsc = LMC_Google::search_console( wp_date( 'Y-m-d', lmc_now() - 31 * DAY_IN_SECONDS ), wp_date( 'Y-m-d', lmc_now() - 3 * DAY_IN_SECONDS ) );
	echo '<div class="lmc-card"><h2>Google Search Console (last 28 days)</h2>';
	if ( is_wp_error( $gsc ) ) {
		echo '<p>' . esc_html( $gsc->get_error_message() ) . ' Set it up in <a href="' . esc_url( admin_url( 'admin.php?page=lmc-settings&tab=growth' ) ) . '">Settings → Growth</a> (guide: docs/growth/google-setup.md).</p>';
	} else {
		$g = $gsc['totals'];
		echo '<p><strong>' . number_format( (int) $g['clicks'] ) . '</strong> clicks · <strong>' . number_format( (int) $g['impressions'] ) . '</strong> impressions · CTR ' . round( $g['ctr'] * 100, 1 ) . '% · avg position ' . round( $g['position'], 1 ) . '</p><table class="widefat striped"><thead><tr><th>Query</th><th>Clicks</th><th>Impr.</th><th>Pos.</th></tr></thead><tbody>';
		foreach ( $gsc['queries'] as $q ) {
			echo '<tr><td>' . esc_html( $q['keys'][0] ) . '</td><td>' . (int) $q['clicks'] . '</td><td>' . (int) $q['impressions'] . '</td><td>' . round( $q['position'], 1 ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
	echo '<div class="lmc-card"><h2>Tools</h2><p><a class="button" href="' . esc_url( lmc_admin_action_url( 'monthly_report' ) ) . '">Send last month\'s report now</a> <a class="button" href="' . esc_url( lmc_admin_action_url( 'run_alerts' ) ) . '">Run job alerts now</a> <a class="button" href="' . esc_url( home_url( '/feeds/jobs.xml' ) ) . '" target="_blank">View job feed</a> <a class="button" href="' . esc_url( home_url( '/wp-sitemap.xml' ) ) . '" target="_blank">View sitemap</a></p>';
	echo '<p class="description">Indexing API: ' . ( lmc_opt( 'google_indexing' ) && LMC_Google::credentials() ? 'on' : 'off' ) . ' · IndexNow: ' . ( lmc_opt( 'indexnow_enabled' ) ? 'on' : 'off' ) . ' · Job feed: ' . ( lmc_opt( 'feed_enabled' ) ? 'on' : 'off' ) . ' · Launch date: ' . esc_html( lmc_opt( 'launch_date' ) ?: 'not set' ) . '</p></div>';
	echo '</div></div>';
	lmc_admin_footer();
}
