<?php
/**
 * Weekly operator report – one concise email, every Monday morning.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Reports {

	public static function count_jobs( $status ) {
		$c = wp_count_posts( 'lmc_job' );
		return (int) ( $c->$status ?? 0 );
	}

	/** Jobs whose meta timestamp falls within [from, to). */
	public static function jobs_between( $meta, $from, $to, $status = 'any' ) {
		return get_posts( array(
			'post_type'        => 'lmc_job',
			'post_status'      => $status,
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_query'       => array( array( 'key' => '_lmc_' . $meta, 'value' => array( $from, $to ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ) ),
		) );
	}

	public static function employers( $since = 0 ) {
		$args = array( 'role' => 'lmc_employer', 'fields' => 'ID', 'number' => -1 );
		if ( $since ) {
			$args['date_query'] = array( array( 'after' => gmdate( 'Y-m-d H:i:s', $since ), 'inclusive' => true ) );
		}
		return count( get_users( $args ) );
	}

	public static function data( $now = null ) {
		global $wpdb;
		$now    = $now ?: lmc_now();
		$from   = $now - WEEK_IN_SECONDS;
		$month  = strtotime( wp_date( 'Y-m-01 00:00:00', $now ) . ' Europe/London' );
		$upcoming = array();
		foreach ( self::jobs_between( 'expires', $now, $now + WEEK_IN_SECONDS, 'publish' ) as $id ) {
			$upcoming[] = array( 'title' => lmc_plain_title( $id ), 'company' => lmc_job_meta( $id, 'employer_name' ), 'expires' => (int) lmc_job_meta( $id, 'expires' ) );
		}
		usort( $upcoming, function ( $a, $b ) {
			return $a['expires'] <=> $b['expires'];
		} );
		$live_by_sector = array();
		foreach ( LMC_Search::sector_counts() as $slug => $n ) {
			if ( $n ) {
				$live_by_sector[ get_term_by( 'slug', $slug, 'lmc_sector' )->name ] = $n;
			}
		}
		arsort( $live_by_sector );
		return array(
			'period'            => array( $from, $now ),
			'active'            => self::count_jobs( 'publish' ),
			'new_published'     => count( self::jobs_between( 'published_at', $from, $now ) ),
			'expired'           => count( self::jobs_between( 'expired_at', $from, $now ) ),
			'pending_approval'  => self::count_jobs( 'pending' ),
			'awaiting_payment'  => self::count_jobs( 'lmc_unpaid' ),
			'employers_total'   => self::employers(),
			'employers_new'     => self::employers( $from ),
			'revenue_week'      => LMC_Payments::revenue( $from, $now ),
			'revenue_month'     => LMC_Payments::revenue( $month, $now ),
			'payments_week'     => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'payments' ) . " WHERE status='paid' AND paid_at >= %s", lmc_mysql_time( $from ) ) ),
			'upcoming_renewals' => $upcoming,
			'traffic'           => LMC_Stats::period( $from, $now + DAY_IN_SECONDS ),
			'popular_sectors'   => array_slice( LMC_Stats::by_sector( $from, $now + DAY_IN_SECONDS ), 0, 5, true ),
			'live_by_sector'    => array_slice( $live_by_sector, 0, 5, true ),
			'new_opportunities' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . lmc_table( 'opportunities' ) . " WHERE status = 'new'" ),
			'followups_due'     => LMC_CRM::followups_due_count(),
			'sources_failing'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . lmc_table( 'sources' ) . " WHERE enabled = 1 AND status IN ('error','blocked_robots','no_parse','js_required')" ),
			'errors'            => count( lmc_open_errors( 100 ) ),
			'warnings'          => lmc_heartbeat_warnings(),
		);
	}

	public static function render_html( $d ) {
		$row = function ( $label, $value ) {
			return '<tr><td style="padding:6px 0;color:#55636d">' . esc_html( $label ) . '</td><td style="padding:6px 0;text-align:right;font-weight:700">' . $value . '</td></tr>';
		};
		$h  = '<h2 style="margin:0 0 4px;font-size:20px">Weekly report</h2><p style="margin:0 0 20px;color:#55636d">' . esc_html( lmc_date( $d['period'][0] ) . ' – ' . lmc_date( $d['period'][1] ) ) . '</p>';
		$todo = array();
		if ( $d['pending_approval'] ) {
			$todo[] = $d['pending_approval'] . ' vacancy/vacancies waiting for your approval';
		}
		if ( $d['new_opportunities'] ) {
			$todo[] = $d['new_opportunities'] . ' newly discovered vacancies to review';
		}
		if ( $d['followups_due'] ) {
			$todo[] = $d['followups_due'] . ' employer follow-ups due';
		}
		if ( $d['sources_failing'] ) {
			$todo[] = $d['sources_failing'] . ' careers pages could not be monitored reliably';
		}
		if ( $d['errors'] ) {
			$todo[] = $d['errors'] . ' automation errors to check';
		}
		foreach ( $d['warnings'] as $w ) {
			$todo[] = $w;
		}
		$h .= '<div style="background:' . ( $todo ? '#fff7e0' : '#eef8dc' ) . ';padding:14px 16px;border-radius:6px;margin-bottom:20px"><strong>' . ( $todo ? 'Needs your attention' : 'Nothing needs your attention this week.' ) . '</strong>';
		if ( $todo ) {
			$h .= '<ul style="margin:8px 0 0;padding-left:18px">' . implode( '', array_map( function ( $t ) {
				return '<li>' . esc_html( $t ) . '</li>';
			}, $todo ) ) . '</ul>';
		}
		$h .= '</div><table width="100%" style="border-collapse:collapse;font-size:14px">';
		$h .= $row( 'Active vacancies', (int) $d['active'] );
		$h .= $row( 'New vacancies published', (int) $d['new_published'] );
		$h .= $row( 'Vacancies expired', (int) $d['expired'] );
		$h .= $row( 'Awaiting payment', (int) $d['awaiting_payment'] );
		$h .= $row( 'Employer accounts (total)', (int) $d['employers_total'] );
		$h .= $row( 'New employer accounts', (int) $d['employers_new'] );
		$h .= $row( 'Revenue this week (net)', esc_html( lmc_money( $d['revenue_week'] ) ) . ' <span style="font-weight:400;color:#55636d">(' . (int) $d['payments_week'] . ' payments)</span>' );
		$h .= $row( 'Revenue month to date (net)', esc_html( lmc_money( $d['revenue_month'] ) ) );
		$h .= $row( 'Vacancy page views', number_format( $d['traffic']['views'] ) );
		$h .= $row( 'Apply clicks', number_format( $d['traffic']['clicks'] ) );
		$h .= '</table>';
		$h .= '<h3 style="font-size:16px;margin:22px 0 6px">Most popular sectors (views)</h3>';
		$h .= $d['popular_sectors'] ? '<ol style="margin:0;padding-left:18px">' . implode( '', array_map( function ( $n, $v ) {
			return '<li>' . esc_html( $n ) . ' – ' . (int) $v['views'] . ' views, ' . (int) $v['clicks'] . ' apply clicks</li>';
		}, array_keys( $d['popular_sectors'] ), $d['popular_sectors'] ) ) . '</ol>' : '<p style="color:#55636d">No vacancy views recorded this week.</p>';
		$h .= '<h3 style="font-size:16px;margin:22px 0 6px">Upcoming renewals (next 7 days)</h3>';
		$h .= $d['upcoming_renewals'] ? '<ul style="margin:0;padding-left:18px">' . implode( '', array_map( function ( $u ) {
			return '<li>' . esc_html( $u['title'] . ' – ' . $u['company'] . ' (ends ' . lmc_date( $u['expires'] ) . ')' ) . '</li>';
		}, $d['upcoming_renewals'] ) ) . '</ul>' : '<p style="color:#55636d">None.</p>';
		$h .= '<p style="margin-top:22px;font-size:12px;color:#55636d">Website traffic figures are vacancy page views counted by the site itself (no cookies). For full visitor analytics, connect Google Analytics in Settings → SEO & analytics.</p>';
		$h .= '<p style="margin:24px 0"><a href="' . esc_url( admin_url( 'admin.php?page=lmc' ) ) . '" style="background:#A7D943;color:#142F40;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;display:inline-block">Open the dashboard</a></p>';
		return $h;
	}

	public static function send_weekly() {
		$d    = self::data();
		$html = lmc_email_render( '', '', self::render_html( $d ) );
		$to   = lmc_opt( 'admin_report_email' ) ?: get_option( 'admin_email' );
		$ok   = wp_mail( $to, lmc_opt( 'business_name' ) . ' weekly report: ' . $d['active'] . ' live, ' . lmc_money( $d['revenue_week'] ) . ' revenue', $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		lmc_heartbeat( 'weekly' );
		if ( ! $ok ) {
			lmc_log( 'error', 'report', 'Weekly report email failed to send to ' . $to );
		}
		return $ok;
	}
}
