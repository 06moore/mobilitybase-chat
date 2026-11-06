<?php
/**
 * Monthly traffic & SEO performance report (emailed on the 1st of each month for the previous month)
 * with month-on-month change, progress against the 12-month growth targets and suggested actions.
 * Targets mirror docs/growth/12-month-growth-plan.md and can be changed with the lmc_growth_targets filter.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Monthly_Report {

	public static function init() {
		add_action( 'lmc_daily_discovery', array( __CLASS__, 'maybe_send' ), 20 );
	}

	/** Base-case targets per month since launch: visits, job views, active subscribers, paying employers (cumulative), live vacancies. */
	public static function targets() {
		$t = array(
			1  => array( 300, 400, 15, 0, 15 ),
			2  => array( 500, 700, 35, 1, 20 ),
			3  => array( 800, 1100, 60, 3, 25 ),
			4  => array( 1100, 1500, 90, 5, 30 ),
			5  => array( 1400, 2000, 120, 7, 35 ),
			6  => array( 1800, 2600, 160, 10, 40 ),
			7  => array( 2200, 3200, 200, 12, 45 ),
			8  => array( 2600, 3800, 240, 15, 50 ),
			9  => array( 3000, 4400, 290, 18, 55 ),
			10 => array( 3400, 5000, 340, 21, 60 ),
			11 => array( 3800, 5600, 390, 24, 65 ),
			12 => array( 4200, 6000, 450, 28, 70 ),
		);
		$out = array();
		foreach ( $t as $m => $v ) {
			$out[ $m ] = array_combine( array( 'visits', 'job_views', 'subscribers', 'paying_employers', 'live_vacancies' ), $v );
		}
		return apply_filters( 'lmc_growth_targets', $out );
	}

	public static function month_number( $month_start_ts ) {
		$launch = lmc_opt( 'launch_date' );
		if ( ! $launch ) {
			return 0;
		}
		$a = new DateTime( substr( $launch, 0, 7 ) . '-01' );
		$b = new DateTime( wp_date( 'Y-m-01', $month_start_ts ) );
		$d = $a->diff( $b );
		return $b < $a ? 0 : $d->y * 12 + $d->m + 1;
	}

	public static function paying_employers( $until ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT user_id) FROM ' . lmc_table( 'payments' ) . " WHERE status = 'paid' AND mode = %s AND paid_at < %s", LMC_Payments::mode(), lmc_mysql_time( $until ) ) );
	}

	/** Collect metrics for a calendar month. $month = 'Y-m'. */
	public static function data( $month ) {
		global $wpdb;
		$tz    = new DateTimeZone( 'Europe/London' );
		$start = new DateTime( $month . '-01 00:00', $tz );
		$end   = ( clone $start )->modify( '+1 month' );
		$pstart = ( clone $start )->modify( '-1 month' );
		$f = $start->format( 'Y-m-d' );
		$t = $end->format( 'Y-m-d' );
		$pf = $pstart->format( 'Y-m-d' );
		$m = function ( $from, $to ) {
			$views = LMC_Stats::period( strtotime( $from . ' 00:00 Europe/London' ), strtotime( $to . ' 00:00 Europe/London' ) );
			return array(
				'visits'      => LMC_Analytics::sum( 'visit', $from, $to ),
				'pageviews'   => LMC_Analytics::sum( 'pageview', $from, $to ),
				'job_views'   => $views['views'],
				'apply'       => $views['clicks'],
				'organic'     => LMC_Analytics::sum( 'source', $from, $to, true, 20 )['Organic search'] ?? 0,
				'new_subs'    => LMC_Analytics::sum( 'alert_confirmed', $from, $to ),
				'unsubs'      => LMC_Analytics::sum( 'alert_unsubscribed', $from, $to ),
				'alert_mails' => LMC_Analytics::sum( 'alert_emails', $from, $to ),
				'shares'      => LMC_Analytics::sum( 'share', $from, $to ),
				'returning'   => LMC_Analytics::sum( 'visitor_returning', $from, $to ),
				'new_vis'     => LMC_Analytics::sum( 'visitor_new', $from, $to ),
				'revenue'     => LMC_Payments::revenue( strtotime( $from . ' 00:00 Europe/London' ), strtotime( $to . ' 00:00 Europe/London' ) ),
			);
		};
		$cur  = $m( $f, $t );
		$prev = $m( $pf, $f );
		$num  = self::month_number( $start->getTimestamp() );
		$gsc  = LMC_Google::search_console( $f, ( clone $end )->modify( '-1 day' )->format( 'Y-m-d' ) );
		$top_jobs = $wpdb->get_results( $wpdb->prepare( 'SELECT job_id, SUM(views) v, SUM(clicks) c FROM ' . lmc_table( 'stats' ) . ' WHERE day >= %s AND day < %s GROUP BY job_id ORDER BY v DESC LIMIT 5', $f, $t ) );
		$alerts = LMC_Alerts::counts();
		return array(
			'month'        => $month,
			'label'        => $start->format( 'F Y' ),
			'month_number' => $num,
			'target'       => self::targets()[ $num ] ?? null,
			'cur'          => $cur,
			'prev'         => $prev,
			'sources'      => LMC_Analytics::sum( 'source', $f, $t, true, 10 ),
			'job_view_sources' => LMC_Analytics::sum( 'job_view_source', $f, $t, true, 10 ),
			'landing'      => LMC_Analytics::sum( 'landing', $f, $t, true, 10 ),
			'referrers'    => LMC_Analytics::sum( 'referrer', $f, $t, true, 10 ),
			'shares_by'    => LMC_Analytics::sum( 'share', $f, $t, true, 10 ),
			'top_jobs'     => $top_jobs,
			'sectors'      => array_slice( LMC_Stats::by_sector( strtotime( $f . ' 00:00 Europe/London' ), strtotime( $t . ' 00:00 Europe/London' ) ), 0, 5, true ),
			'subs_active'  => $alerts['active'],
			'paying_total' => self::paying_employers( $end->getTimestamp() ),
			'paying_new'   => self::paying_employers( $end->getTimestamp() ) - self::paying_employers( $start->getTimestamp() ),
			'live'         => LMC_Reports::count_jobs( 'publish' ),
			'backlinks'    => LMC_Employer_Share::live_backlink_count(),
			'gsc'          => is_wp_error( $gsc ) ? null : $gsc,
			'gsc_error'    => is_wp_error( $gsc ) ? $gsc->get_error_message() : '',
		);
	}

	public static function actions( $d ) {
		$a   = array();
		$cur = $d['cur'];
		$tg  = $d['target'];
		$organic_share = $cur['visits'] ? $cur['organic'] / $cur['visits'] : 0;
		if ( ! $d['gsc'] ) {
			$a[] = 'Connect Search Console (Settings → Growth) so this report shows Google clicks, impressions and top search queries.';
		}
		if ( $d['month_number'] >= 4 && $organic_share < 0.35 ) {
			$a[] = 'Google is under 35% of visits. Publish one new career guide this month and ask two employers to add the badge to their careers page.';
		}
		if ( $tg && $d['live'] < $tg['live_vacancies'] ) {
			$a[] = 'Live vacancies (' . $d['live'] . ') are below target (' . $tg['live_vacancies'] . '). Vacancy volume drives everything else – prioritise outreach and approve discovered vacancies.';
		}
		if ( $tg && $d['subs_active'] < $tg['subscribers'] ) {
			$a[] = 'Job alert subscribers are behind target. Post the "Get jobs by email" link on LinkedIn/Facebook and in relevant engineering groups.';
		}
		if ( $cur['job_views'] && $cur['apply'] / max( 1, $cur['job_views'] ) < 0.05 ) {
			$a[] = 'Apply rate is under 5%. Check that top adverts have clear salaries and simple application links.';
		}
		if ( 0 === $cur['shares'] ) {
			$a[] = 'No vacancies were shared this month. Remind employers about their share kit in your next outreach.';
		}
		if ( $d['gsc'] ) {
			foreach ( $d['gsc']['queries'] as $q ) {
				if ( $q['position'] > 8 && $q['position'] <= 20 && $q['impressions'] >= 50 ) {
					$a[] = 'Opportunity: "' . $q['keys'][0] . '" ranks at position ' . round( $q['position'], 1 ) . ' with ' . (int) $q['impressions'] . ' impressions. Improve the matching landing page and link to it from a guide.';
					break;
				}
			}
		}
		return $a ?: array( 'Everything is on track. Keep the weekly routine going.' );
	}

	public static function render( $d ) {
		$cur  = $d['cur'];
		$prev = $d['prev'];
		$chg  = function ( $k ) use ( $cur, $prev ) {
			if ( ! $prev[ $k ] ) {
				return $cur[ $k ] ? '<span style="color:#2E6B12">new</span>' : '–';
			}
			$p = round( ( $cur[ $k ] - $prev[ $k ] ) / $prev[ $k ] * 100 );
			return '<span style="color:' . ( $p >= 0 ? '#2E6B12' : '#B42318' ) . '">' . ( $p >= 0 ? '+' : '' ) . $p . '%</span>';
		};
		$tgt  = function ( $val, $key ) use ( $d ) {
			if ( ! $d['target'] ) {
				return '';
			}
			$t = $d['target'][ $key ];
			return ' <span style="color:' . ( $val >= $t ? '#2E6B12' : '#8A5A00' ) . ';font-size:12px">(' . ( $val >= $t ? 'on target ' : 'target ' ) . number_format( $t ) . ')</span>';
		};
		$row  = function ( $label, $val, $extra = '' ) {
			return '<tr><td style="padding:6px 0;color:#55636d">' . esc_html( $label ) . '</td><td style="padding:6px 0;text-align:right;font-weight:700">' . $val . '</td><td style="padding:6px 0 6px 10px;text-align:right">' . $extra . '</td></tr>';
		};
		$list = function ( $title, $arr, $fmt = null ) {
			if ( ! $arr ) {
				return '<h3 style="font-size:15px;margin:20px 0 4px">' . esc_html( $title ) . '</h3><p style="color:#55636d;margin:0">No data yet.</p>';
			}
			$h = '<h3 style="font-size:15px;margin:20px 0 4px">' . esc_html( $title ) . '</h3><ol style="margin:0;padding-left:18px">';
			foreach ( $arr as $k => $v ) {
				$h .= '<li>' . ( $fmt ? $fmt( $k, $v ) : esc_html( $k ?: '(none)' ) . ' – ' . number_format( (int) $v ) ) . '</li>';
			}
			return $h . '</ol>';
		};
		$organic_pct = $cur['visits'] ? round( $cur['organic'] / $cur['visits'] * 100 ) : 0;
		$h  = '<h2 style="margin:0 0 4px;font-size:20px">Monthly traffic &amp; SEO report</h2><p style="margin:0 0 18px;color:#55636d">' . esc_html( $d['label'] ) . ( $d['month_number'] ? ' · month ' . (int) $d['month_number'] . ' of the growth plan' : ' · set a launch date in Settings → Growth to track targets' ) . '</p>';
		$h .= '<div style="background:#F4F6F7;padding:14px 16px;border-radius:6px;margin-bottom:18px"><strong>Suggested actions</strong><ul style="margin:8px 0 0;padding-left:18px">';
		foreach ( self::actions( $d ) as $x ) {
			$h .= '<li>' . esc_html( $x ) . '</li>';
		}
		$h .= '</ul></div><table width="100%" style="border-collapse:collapse;font-size:14px">';
		$h .= $row( 'Visits', number_format( $cur['visits'] ) . $tgt( $cur['visits'], 'visits' ), $chg( 'visits' ) );
		$h .= $row( 'Page views', number_format( $cur['pageviews'] ), $chg( 'pageviews' ) );
		$h .= $row( 'Visits from Google & other search', number_format( $cur['organic'] ) . ' (' . $organic_pct . '%)', $chg( 'organic' ) );
		$h .= $row( 'Job views', number_format( $cur['job_views'] ) . $tgt( $cur['job_views'], 'job_views' ), $chg( 'job_views' ) );
		$h .= $row( 'Apply Now clicks', number_format( $cur['apply'] ) . ( $cur['job_views'] ? ' (' . round( $cur['apply'] / $cur['job_views'] * 100, 1 ) . '%)' : '' ), $chg( 'apply' ) );
		$h .= $row( 'Returning visitors*', $cur['returning'] + $cur['new_vis'] ? round( $cur['returning'] / ( $cur['returning'] + $cur['new_vis'] ) * 100 ) . '%' : '–', '' );
		$h .= $row( 'Job alert subscribers (active)', number_format( $d['subs_active'] ) . $tgt( $d['subs_active'], 'subscribers' ), '+' . (int) $cur['new_subs'] . ' / −' . (int) $cur['unsubs'] );
		$h .= $row( 'Job alert emails sent', number_format( $cur['alert_mails'] ), $chg( 'alert_mails' ) );
		$h .= $row( 'Vacancy shares', number_format( $cur['shares'] ), $chg( 'shares' ) );
		$h .= $row( 'Live vacancies (now)', number_format( $d['live'] ) . $tgt( $d['live'], 'live_vacancies' ), '' );
		$h .= $row( 'Paying employers (to date)', number_format( $d['paying_total'] ) . $tgt( $d['paying_total'], 'paying_employers' ), '+' . (int) $d['paying_new'] . ' new' );
		$h .= $row( 'Revenue (net)', esc_html( lmc_money( $cur['revenue'] ) ), $chg( 'revenue' ) );
		$h .= $row( 'Verified employer backlinks', number_format( $d['backlinks'] ), '' );
		$h .= '</table><p style="font-size:12px;color:#55636d">*Returning visitors are measured only for visitors who accept analytics.</p>';
		if ( $d['gsc'] ) {
			$g  = $d['gsc']['totals'];
			$h .= '<h3 style="font-size:15px;margin:20px 0 4px">Google Search Console</h3><p style="margin:0">' . number_format( (int) $g['clicks'] ) . ' clicks · ' . number_format( (int) $g['impressions'] ) . ' impressions · CTR ' . round( $g['ctr'] * 100, 1 ) . '% · average position ' . round( $g['position'], 1 ) . '</p>';
			$h .= $list( 'Top search queries', array_combine( array_map( fn( $r ) => $r['keys'][0], $d['gsc']['queries'] ), $d['gsc']['queries'] ), fn( $k, $r ) => esc_html( $k ) . ' – ' . (int) $r['clicks'] . ' clicks, ' . (int) $r['impressions'] . ' impr., pos. ' . round( $r['position'], 1 ) );
		} else {
			$h .= '<p style="color:#55636d;font-size:13px">Search Console: ' . esc_html( $d['gsc_error'] ) . '</p>';
		}
		$h .= $list( 'Traffic sources (visits)', $d['sources'] );
		$h .= $list( 'Top landing pages', $d['landing'] );
		$h .= $list( 'Top referring websites', $d['referrers'] );
		$h .= $list( 'Most viewed vacancies', $d['top_jobs'], fn( $k, $r ) => esc_html( lmc_plain_title( $r->job_id ) ) . ' – ' . (int) $r->v . ' views, ' . (int) $r->c . ' apply clicks' );
		$h .= $list( 'Most popular sectors', $d['sectors'], fn( $k, $v ) => esc_html( $k ) . ' – ' . (int) $v['views'] . ' views' );
		return $h;
	}

	public static function send( $month = null ) {
		$month = $month ?: wp_date( 'Y-m', strtotime( 'first day of last month', lmc_now() ) );
		$d     = self::data( $month );
		$to    = lmc_opt( 'monthly_report_email' ) ?: ( lmc_opt( 'admin_report_email' ) ?: get_option( 'admin_email' ) );
		$ok    = wp_mail( $to, lmc_opt( 'business_name' ) . ' monthly report – ' . $d['label'] . ': ' . number_format( $d['cur']['visits'] ) . ' visits, ' . number_format( $d['cur']['job_views'] ) . ' job views', lmc_email_render( '', '', self::render( $d ) ), array( 'Content-Type: text/html; charset=UTF-8' ) );
		update_option( 'lmc_monthly_sent', $month, false );
		lmc_heartbeat( 'monthly' );
		if ( ! $ok ) {
			lmc_log( 'error', 'report', 'Monthly report failed to send to ' . $to );
		}
		return $ok;
	}

	/** Runs daily; on/after the 1st sends last month's report once, and runs other monthly jobs. */
	public static function maybe_send() {
		$last_month = wp_date( 'Y-m', strtotime( 'first day of last month', lmc_now() ) );
		if ( get_option( 'lmc_monthly_sent' ) === $last_month ) {
			return false;
		}
		// Don't send an empty report for the month before the site was installed.
		if ( (int) get_option( 'lmc_installed_at', lmc_now() ) >= strtotime( wp_date( 'Y-m-01 00:00:00', lmc_now() ) . ' Europe/London' ) ) {
			update_option( 'lmc_monthly_sent', $last_month, false );
			return false;
		}
		do_action( 'lmc_monthly' );
		return self::send( $last_month );
	}
}
LMC_Monthly_Report::init();
