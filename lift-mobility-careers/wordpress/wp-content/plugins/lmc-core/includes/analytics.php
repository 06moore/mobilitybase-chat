<?php
/**
 * Built-in, privacy-first traffic analytics.
 *
 * Without any cookies or device storage we count: page views, visits (entries from outside the site),
 * traffic source of each visit, landing pages, top referrers, job views, Apply clicks and shares.
 * Returning-vs-new visitors needs a value stored on the device, so it is ONLY measured for
 * visitors who accept analytics in the consent banner (PECR reg 6).
 *
 * GA4 (optional, consent-gated) receives the same events for deeper analysis.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Analytics {

	public static function init() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'lmc/v1', '/hit', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'endpoint' ),
				'permission_callback' => '__return_true',
			) );
		} );
	}

	public static function bump( $metric, $dim = '', $n = 1, $day = null ) {
		global $wpdb;
		$t   = lmc_table( 'traffic' );
		$day = $day ?: wp_date( 'Y-m-d', lmc_now() );
		$dim = mb_substr( (string) $dim, 0, 190 );
		$u   = $wpdb->query( $wpdb->prepare( "UPDATE $t SET n = n + %d WHERE day = %s AND metric = %s AND dim = %s", $n, $day, $metric, $dim ) );
		if ( ! $u ) {
			$wpdb->insert( $t, array( 'day' => $day, 'metric' => $metric, 'dim' => $dim, 'n' => $n ) );
		}
	}

	/** Classify a visit's source from utm_source and the referrer. Returns [group, detail] or null for internal navigation. */
	public static function classify( $referrer, $utm = '' ) {
		$utm = strtolower( trim( (string) $utm ) );
		if ( $utm ) {
			$map = array( 'jobalert' => 'Job alert emails', 'linkedin' => 'Social', 'facebook' => 'Social', 'whatsapp' => 'Social', 'x' => 'Social', 'employer' => 'Employer links', 'badge' => 'Employer links', 'outreach' => 'Email', 'email' => 'Email' );
			return array( $map[ $utm ] ?? 'Campaign', $utm );
		}
		$host = strtolower( (string) wp_parse_url( (string) $referrer, PHP_URL_HOST ) );
		if ( ! $host ) {
			return array( 'Direct', '' );
		}
		$own = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		if ( $host === $own || 'www.' . $own === $host || 'www.' . $host === $own ) {
			return null;
		}
		$host = preg_replace( '/^www\./', '', $host );
		$rules = array(
			'Organic search'  => '/(^|\.)(google\.[a-z.]+|bing\.com|duckduckgo\.com|search\.yahoo\.com|ecosia\.org|yandex\.[a-z]+|search\.brave\.com)$/',
			'Social'          => '/(^|\.)(linkedin\.com|lnkd\.in|facebook\.com|fb\.com|m\.facebook\.com|l\.facebook\.com|t\.co|x\.com|twitter\.com|instagram\.com|whatsapp\.com|wa\.me|reddit\.com|youtube\.com|tiktok\.com)$/',
			'Email'           => '/(^|\.)(mail\.google\.com|outlook\.(live|office)\.com|mail\.yahoo\.com|webmail\.)/',
			'Job aggregators' => '/(^|\.)(indeed\.[a-z.]+|adzuna\.[a-z.]+|jooble\.org|careerjet\.[a-z.]+|talent\.com|glassdoor\.[a-z.]+|totaljobs\.com|reed\.co\.uk|cv-library\.co\.uk|jobrapido\.com)$/',
		);
		foreach ( $rules as $group => $re ) {
			if ( preg_match( $re, $host ) ) {
				return array( $group, $host );
			}
		}
		return array( 'Referral', $host );
	}

	public static function endpoint( WP_REST_Request $r ) {
		if ( lmc_is_bot() || current_user_can( 'edit_posts' ) ) {
			return new WP_REST_Response( null, 204 );
		}
		$type = sanitize_key( $r->get_param( 't' ) );
		$job  = absint( $r->get_param( 'job' ) );
		$live = $job && 'lmc_job' === get_post_type( $job ) && 'publish' === get_post_status( $job );
		switch ( $type ) {
			case 'page':
				self::bump( 'pageview' );
				$src = self::classify( esc_url_raw( (string) $r->get_param( 'ref' ) ), sanitize_key( (string) $r->get_param( 'utm' ) ) );
				if ( $src ) {
					self::bump( 'visit' );
					self::bump( 'source', $src[0] );
					if ( $src[1] && in_array( $src[0], array( 'Referral', 'Job aggregators', 'Organic search', 'Social' ), true ) ) {
						self::bump( 'referrer', $src[1] );
					}
					$path = '/' . ltrim( (string) wp_parse_url( esc_url_raw( home_url( (string) $r->get_param( 'path' ) ) ), PHP_URL_PATH ), '/' );
					self::bump( 'landing', $path );
					$ret = $r->get_param( 'ret' );
					if ( '1' === $ret || '0' === $ret ) {
						self::bump( '1' === $ret ? 'visitor_returning' : 'visitor_new' );
					}
				}
				if ( $live ) {
					LMC_Stats::record( $job, 'views' );
					if ( $src ) {
						self::bump( 'job_view_source', $src[0] );
					}
				}
				break;
			case 'click':
				if ( $live ) {
					LMC_Stats::record( $job, 'clicks' );
				}
				break;
			case 'share':
				$net = sanitize_key( (string) $r->get_param( 'net' ) );
				if ( $live && in_array( $net, array( 'linkedin', 'facebook', 'whatsapp', 'x', 'email', 'copy', 'native' ), true ) ) {
					self::bump( 'share', $net );
				}
				break;
		}
		return new WP_REST_Response( null, 204 );
	}

	/** Sum a metric between two Y-m-d dates (inclusive from, exclusive to), optionally grouped by dim. */
	public static function sum( $metric, $from, $to, $by_dim = false, $limit = 10 ) {
		global $wpdb;
		$t = lmc_table( 'traffic' );
		if ( $by_dim ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT dim, SUM(n) n FROM $t WHERE metric = %s AND day >= %s AND day < %s GROUP BY dim ORDER BY n DESC LIMIT %d", $metric, $from, $to, $limit ) );
			$out  = array();
			foreach ( $rows as $row ) {
				$out[ $row->dim ] = (int) $row->n;
			}
			return $out;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(n),0) FROM $t WHERE metric = %s AND day >= %s AND day < %s", $metric, $from, $to ) );
	}
}
LMC_Analytics::init();
