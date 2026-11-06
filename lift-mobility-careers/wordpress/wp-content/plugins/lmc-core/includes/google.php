<?php
/**
 * Search engine integrations (all optional, all off until configured):
 *  - Google Indexing API: Google supports it specifically for JobPosting pages – new vacancies can be
 *    crawled within minutes instead of days, and closed ones dropped promptly.
 *  - Google Search Console API: clicks, impressions, CTR, position and top queries for the monthly report.
 *  - IndexNow (Bing, Yandex, Seznam…): instant URL notifications.
 * Google access uses a service account (JSON key) – see docs/growth/google-setup.md.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Google {

	public static function init() {
		add_action( 'lmc_job_published', array( __CLASS__, 'on_live' ) );
		add_action( 'lmc_job_renewed', array( __CLASS__, 'on_live' ) );
		add_action( 'transition_post_status', function ( $new, $old, $post ) {
			if ( 'lmc_job' === $post->post_type && 'publish' === $old && in_array( $new, array( 'lmc_expired', 'lmc_closed', 'lmc_unpaid', 'pending', 'draft', 'trash' ), true ) ) {
				LMC_Google::notify( get_permalink( $post ), 'URL_DELETED' );
			}
		}, 20, 3 );
		add_action( 'init', array( __CLASS__, 'serve_indexnow_key' ), 1 );
	}

	public static function on_live( $job_id ) {
		self::notify( get_permalink( $job_id ), 'URL_UPDATED' );
	}

	public static function notify( $url, $type ) {
		$id = url_to_postid( $url );
		if ( ! LMC_Launch::is_live() || ( $id && LMC_Launch::is_demo_job( $id ) ) ) {
			return; // Never ping search engines before launch or about demo content.
		}
		if ( lmc_opt( 'google_indexing' ) && self::credentials() ) {
			$r = self::api( 'POST', 'https://indexing.googleapis.com/v3/urlNotifications:publish', array( 'url' => $url, 'type' => $type ), 'https://www.googleapis.com/auth/indexing' );
			if ( is_wp_error( $r ) ) {
				lmc_log( 'error', 'google', 'Indexing API: ' . $r->get_error_message() );
			} else {
				LMC_Analytics::bump( 'indexing_api', $type );
			}
		}
		if ( lmc_opt( 'indexnow_enabled' ) && lmc_opt( 'indexnow_key' ) ) {
			$res = wp_remote_get( add_query_arg( array( 'url' => rawurlencode( $url ), 'key' => lmc_opt( 'indexnow_key' ) ), 'https://api.indexnow.org/indexnow' ), array( 'timeout' => 5 ) );
			if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 400 ) {
				lmc_log( 'warning', 'indexnow', 'IndexNow ping failed for ' . $url );
			}
		}
	}

	/** IndexNow requires the key to be served at /{key}.txt */
	public static function serve_indexnow_key() {
		$key = lmc_opt( 'indexnow_key' );
		if ( $key && lmc_opt( 'indexnow_enabled' ) && '/' . $key . '.txt' === wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $key );
			exit;
		}
	}

	/* ---------- Service-account auth ---------- */

	public static function credentials() {
		$json = defined( 'LMC_GOOGLE_SA_JSON' ) ? LMC_GOOGLE_SA_JSON : lmc_opt( 'google_sa_json' );
		if ( ! $json ) {
			return null;
		}
		if ( is_string( $json ) && is_readable( $json ) && ! str_starts_with( trim( $json ), '{' ) ) {
			$json = file_get_contents( $json ); // A path to the key file outside the web root is preferred.
		}
		$c = json_decode( $json, true );
		return ( $c && ! empty( $c['client_email'] ) && ! empty( $c['private_key'] ) ) ? $c : null;
	}

	public static function b64url( $s ) {
		return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
	}

	/** Build a signed JWT assertion (RS256) for the OAuth token exchange. */
	public static function jwt( array $c, $scope, $now = null ) {
		$now    = $now ?: time();
		$header = self::b64url( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
		$claims = self::b64url( wp_json_encode( array( 'iss' => $c['client_email'], 'scope' => $scope, 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600 ) ) );
		$sig    = '';
		if ( ! openssl_sign( $header . '.' . $claims, $sig, $c['private_key'], OPENSSL_ALGO_SHA256 ) ) {
			return new WP_Error( 'google', 'Could not sign with the service account key.' );
		}
		return $header . '.' . $claims . '.' . self::b64url( $sig );
	}

	public static function token( $scope ) {
		$key    = 'lmc_gtok_' . md5( $scope );
		$cached = get_transient( $key );
		if ( $cached ) {
			return $cached;
		}
		$c = self::credentials();
		if ( ! $c ) {
			return new WP_Error( 'google', 'No Google service account configured.' );
		}
		$jwt = self::jwt( $c, $scope );
		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}
		$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'timeout' => 10, 'body' => array( 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt ) ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$b = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $b['access_token'] ) ) {
			return new WP_Error( 'google', 'Token request failed: ' . ( $b['error_description'] ?? $b['error'] ?? 'unknown' ) );
		}
		set_transient( $key, $b['access_token'], max( 60, (int) ( $b['expires_in'] ?? 3600 ) - 120 ) );
		return $b['access_token'];
	}

	public static function api( $method, $url, $body, $scope ) {
		$tok = self::token( $scope );
		if ( is_wp_error( $tok ) ) {
			return $tok;
		}
		$res = wp_remote_request( $url, array( 'method' => $method, 'timeout' => 15, 'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json' ), 'body' => $body ? wp_json_encode( $body ) : null ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$b = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( wp_remote_retrieve_response_code( $res ) >= 400 ) {
			return new WP_Error( 'google', $b['error']['message'] ?? ( 'HTTP ' . wp_remote_retrieve_response_code( $res ) ) );
		}
		return $b;
	}

	/**
	 * Search Console performance for a date range. Returns ['totals'=>[clicks,impressions,ctr,position], 'queries'=>[], 'pages'=>[]] or WP_Error.
	 */
	public static function search_console( $from, $to ) {
		$prop = lmc_opt( 'gsc_property' );
		if ( ! $prop || ! self::credentials() ) {
			return new WP_Error( 'google', 'Search Console not connected.' );
		}
		$url   = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode( $prop ) . '/searchAnalytics/query';
		$scope = 'https://www.googleapis.com/auth/webmasters.readonly';
		$tot   = self::api( 'POST', $url, array( 'startDate' => $from, 'endDate' => $to ), $scope );
		if ( is_wp_error( $tot ) ) {
			return $tot;
		}
		$out = array( 'totals' => $tot['rows'][0] ?? array( 'clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0 ), 'queries' => array(), 'pages' => array() );
		foreach ( array( 'query' => 'queries', 'page' => 'pages' ) as $dim => $k ) {
			$r = self::api( 'POST', $url, array( 'startDate' => $from, 'endDate' => $to, 'dimensions' => array( $dim ), 'rowLimit' => 10 ), $scope );
			if ( ! is_wp_error( $r ) ) {
				$out[ $k ] = $r['rows'] ?? array();
			}
		}
		return $out;
	}
}
LMC_Google::init();
