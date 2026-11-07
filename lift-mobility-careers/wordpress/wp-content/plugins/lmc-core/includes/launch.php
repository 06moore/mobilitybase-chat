<?php
/**
 * Launch control.
 *
 * The site starts in PRE-LAUNCH mode and cannot be switched to LIVE until every launch blocker is
 * cleared – including a recorded specialist review of the Employment Agencies Act position.
 *
 * While in pre-launch:
 *  - every page is noindex and robots.txt disallows crawling; no XML sitemap; no job feed;
 *  - no Google Indexing API / IndexNow notifications;
 *  - Stripe is forced to TEST mode – real card payments are impossible.
 *
 * Demo content (from the local dev seed) is flagged and is never indexed, never in sitemaps, the job
 * feed, job alerts or structured data – and its presence is itself a launch blocker.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Launch {

	public static function init() {
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 99 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 99, 2 );
		add_filter( 'wp_sitemaps_enabled', function ( $on ) {
			return self::is_live() ? $on : false;
		} );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'sitemap_exclude_demo' ), 20, 2 );
		add_action( 'wp_footer', array( __CLASS__, 'admin_badge' ) );
	}

	public static function is_live() {
		return 'live' === lmc_opt( 'launch_mode' );
	}

	public static function robots( $r ) {
		$demo = is_singular( 'lmc_job' ) && self::is_demo_job( get_queried_object_id() );
		if ( ! self::is_live() || $demo ) {
			unset( $r['max-image-preview'], $r['max-snippet'], $r['index'] );
			$r['noindex']  = true;
			$r['nofollow'] = ! self::is_live();
		}
		return $r;
	}

	public static function robots_txt( $out, $public ) {
		return self::is_live() ? $out : "# Pre-launch: not open to search engines yet\nUser-agent: *\nDisallow: /\n";
	}

	public static function admin_badge() {
		if ( ! self::is_live() && current_user_can( 'manage_options' ) ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=lmc-launch' ) ) . '" style="position:fixed;right:12px;bottom:12px;z-index:300;background:#B42318;color:#fff;padding:8px 12px;border-radius:6px;font:600 13px system-ui;text-decoration:none">Pre-launch mode – hidden from Google</a>';
		}
	}

	/* ---------- Demo content ---------- */

	public static function is_demo_job( $id ) {
		return (bool) get_post_meta( $id, '_lmc_demo', true );
	}

	public static function demo_meta_query() {
		return array( 'key' => '_lmc_demo', 'compare' => 'NOT EXISTS' );
	}

	public static function sitemap_exclude_demo( $args, $type ) {
		if ( 'lmc_job' === $type ) {
			$args['meta_query']   = (array) ( $args['meta_query'] ?? array() );
			$args['meta_query'][] = self::demo_meta_query();
		}
		return $args;
	}

	public static function demo_counts() {
		$jobs  = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_lmc_demo' ) );
		$users = get_users( array( 'meta_key' => 'lmc_demo', 'fields' => 'ID' ) );
		global $wpdb;
		$pros = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . lmc_table( 'prospects' ) . " WHERE notes LIKE 'Demo%'" );
		return array( 'jobs' => count( $jobs ), 'users' => count( $users ), 'prospects' => $pros );
	}

	public static function remove_demo() {
		global $wpdb;
		foreach ( get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_lmc_demo' ) ) as $id ) {
			$wpdb->delete( lmc_table( 'social' ), array( 'job_id' => $id ) );
			$wpdb->delete( lmc_table( 'stats' ), array( 'job_id' => $id ) );
			wp_delete_post( $id, true );
		}
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( get_users( array( 'meta_key' => 'lmc_demo', 'fields' => 'ID' ) ) as $uid ) {
			$wpdb->delete( lmc_table( 'credits' ), array( 'user_id' => $uid ) );
			wp_delete_user( $uid );
		}
		$wpdb->query( 'DELETE FROM ' . lmc_table( 'prospects' ) . " WHERE notes LIKE 'Demo%'" );
		lmc_log( 'info', 'launch', 'Demo content removed.' );
	}

	/* ---------- Legal placeholders ---------- */

	/** Every [BRACKETED PLACEHOLDER] and "DRAFT" marker still present on the site's legal pages. */
	public static function placeholders() {
		$out = array();
		foreach ( array( 'terms', 'privacy', 'agreement' ) as $k ) {
			$id = (int) get_option( 'lmc_page_' . $k );
			if ( ! $id ) {
				continue;
			}
			$c = wp_strip_all_tags( do_shortcode( get_post_field( 'post_content', $id ) ) ); // Scan the page as visitors see it.
			preg_match_all( '/\[(?:NOT SET: [^\]]+|[A-Z][A-Z0-9 \-\/]{2,})\]/', $c, $m );
			$found = array_values( array_unique( $m[0] ) );
			if ( false !== stripos( $c, 'DRAFT' ) ) {
				$found[] = '"DRAFT" notice';
			}
			if ( $found ) {
				$out[ get_the_title( $id ) ] = $found;
			}
		}
		return $out;
	}

	/* ---------- Blockers ---------- */

	/** Launch checks: [ label, passed?, detail, blocker? ] */
	public static function checks() {
		$c    = array();
		$home = home_url( '/' );
		$c['legal'] = array( 'Specialist review of the employment agency position (EAA 1973 / Conduct Regulations 2003) and legal documents', (bool) ( lmc_opt( 'legal_signoff_by' ) && lmc_opt( 'legal_signoff_date' ) ), lmc_opt( 'legal_signoff_by' ) ? 'Signed off by ' . lmc_opt( 'legal_signoff_by' ) . ' on ' . lmc_opt( 'legal_signoff_date' ) : 'Record the solicitor / compliance specialist and date below once they have approved the compliance position, the advert disclosure wording and all legal pages.', true );
		$ph = self::placeholders();
		$c['placeholders'] = array( 'No placeholders or DRAFT notices left in the legal pages', ! $ph, $ph ? implode( ' · ', array_map( function ( $page, $items ) {
			return $page . ': ' . implode( ', ', $items );
		}, array_keys( $ph ), $ph ) ) : 'None found.', true );
		$d = self::demo_counts();
		$c['demo'] = array( 'Demo vacancies, employers and prospects removed', 0 === array_sum( $d ), array_sum( $d ) ? "{$d['jobs']} demo vacancies, {$d['users']} demo employers, {$d['prospects']} demo prospects." : 'None found.', true );
		$missing = lmc_identity()['missing'];
		foreach ( array( 'admin_report_email' => 'admin email', 'ico_number' => 'ICO registration number' ) as $k => $l ) {
			$v = (string) lmc_opt( $k );
			if ( '' === trim( $v ) || false !== strpos( $v, 'example.' ) ) {
				$missing[] = $l;
			}
		}
		$c['business'] = array( 'Business details complete for a ' . ( lmc_is_limited() ? 'limited company' : 'sole trader' ) . ' (Settings → Business details)', ! $missing, $missing ? 'Missing: ' . implode( ', ', $missing ) : 'Complete.', true );
		$https = 0 === strpos( $home, 'https://' ) && ! preg_match( '#//(localhost|127\.|10\.|192\.168\.)#', $home );
		$c['domain'] = array( 'Site runs on its real domain over HTTPS', $https, 'Site address: ' . $home, true );
		$c['email'] = array( 'Email delivery configured (SMTP with SPF/DKIM)', (bool) lmc_opt( 'smtp_host' ), lmc_opt( 'smtp_host' ) ? 'SMTP: ' . lmc_opt( 'smtp_host' ) : 'No SMTP server set – emails may go to spam or not arrive.', true );
		$live_keys = (bool) ( lmc_opt( 'stripe_live_secret' ) && lmc_opt( 'stripe_live_webhook' ) );
		$c['stripe'] = array( 'Stripe live keys and webhook secret added and tested', $live_keys, $live_keys ? 'Live keys present.' : 'Not yet. (You can launch with free listings only, but nobody can pay.)', false );
		$hb = get_option( 'lmc_heartbeats', array() );
		$c['cron'] = array( 'Automation running (server cron)', ! empty( $hb['lifecycle'] ) && lmc_now() - $hb['lifecycle'] < 2 * HOUR_IN_SECONDS, ! empty( $hb['lifecycle'] ) ? 'Last run ' . human_time_diff( $hb['lifecycle'], lmc_now() ) . ' ago.' : 'Never run.', false );
		return $c;
	}

	public static function blockers() {
		return array_filter( self::checks(), function ( $x ) {
			return $x[3] && ! $x[1];
		} );
	}

	public static function go_live() {
		if ( self::blockers() ) {
			return false;
		}
		lmc_update_opts( array( 'launch_mode' => 'live' ) );
		update_option( 'blog_public', 1 );
		lmc_log( 'info', 'launch', 'Site switched to LIVE.' );
		return true;
	}
}
LMC_Launch::init();
