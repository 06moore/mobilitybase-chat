<?php
/**
 * Security hardening that sits alongside WordPress core:
 *  - Login throttling (per IP + username, and per IP) – stops password guessing.
 *  - Security headers (nosniff, frame protection, referrer policy so private tokens in URLs
 *    such as job-alert management links are never leaked to other sites).
 *  - No user enumeration: author archives and the public REST users list are disabled,
 *    because employer usernames are derived from email addresses.
 * Rate-limit counters use a salted hash of the IP address, held for at most an hour; IP
 * addresses themselves are never stored by this plugin.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Security {

	const LOGIN_MAX_PER_USER = 5;   // failures per IP+username per 15 minutes
	const LOGIN_MAX_PER_IP   = 20;  // failures per IP per 15 minutes

	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'check_lock' ), 30, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'record_failure' ) );
		add_action( 'send_headers', array( __CLASS__, 'headers' ) );
		add_action( 'template_redirect', array( __CLASS__, 'block_author_archives' ), 1 );
		add_filter( 'rest_endpoints', array( __CLASS__, 'restrict_user_endpoints' ) );
	}

	public static function ip_key( $extra = '' ) {
		return 'lmc_rl_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . strtolower( $extra ) . '|' . wp_salt( 'nonce' ) );
	}

	/** Generic fixed-window rate limiter. Returns true if the action is allowed (and counts it). */
	public static function allow( $bucket, $max, $window = HOUR_IN_SECONDS ) {
		$key = self::ip_key( $bucket );
		$n   = (int) get_transient( $key );
		if ( $n >= $max ) {
			return false;
		}
		set_transient( $key, $n + 1, $window );
		return true;
	}

	public static function check_lock( $user, $username ) {
		if ( ! $username ) {
			return $user;
		}
		if ( (int) get_transient( self::ip_key( 'login|' . $username ) ) >= self::LOGIN_MAX_PER_USER || (int) get_transient( self::ip_key( 'login-ip' ) ) >= self::LOGIN_MAX_PER_IP ) {
			return new WP_Error( 'lmc_locked', '<strong>Too many failed sign-in attempts.</strong> Please wait 15 minutes, or reset your password.' );
		}
		return $user;
	}

	public static function record_failure( $username ) {
		foreach ( array( 'login|' . $username, 'login-ip' ) as $b ) {
			$k = self::ip_key( $b );
			set_transient( $k, (int) get_transient( $k ) + 1, 15 * MINUTE_IN_SECONDS );
		}
	}

	public static function headers() {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
	}

	public static function block_author_archives() {
		if ( is_author() || isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	public static function restrict_user_endpoints( $endpoints ) {
		if ( current_user_can( 'list_users' ) ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
}
LMC_Security::init();
