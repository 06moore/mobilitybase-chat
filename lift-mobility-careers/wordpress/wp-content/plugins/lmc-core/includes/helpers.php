<?php
defined( 'ABSPATH' ) || exit;

/** Current time as a UTC unix timestamp (filterable so tests can "time travel"). */
function lmc_now() {
	return (int) apply_filters( 'lmc_now', time() );
}

/** MySQL datetime (UTC) for a timestamp. */
function lmc_mysql_time( $ts = null ) {
	return gmdate( 'Y-m-d H:i:s', null === $ts ? lmc_now() : $ts );
}

/** Format a timestamp for UK display in the site timezone. */
function lmc_date( $ts, $format = 'j M Y' ) {
	if ( ! $ts ) {
		return '–';
	}
	return wp_date( $format, (int) $ts );
}

/** Pence to "£39.00" (or "£39" when whole pounds and $short). */
function lmc_money( $pence, $short = false ) {
	$pounds = ( (int) $pence ) / 100;
	if ( $short && floor( $pounds ) == $pounds ) {
		return '£' . number_format( $pounds, 0 );
	}
	return '£' . number_format( $pounds, 2 );
}

function lmc_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'lmc_' . $name;
}

/** Signed token for links in emails (unsubscribe, filled confirmation). */
function lmc_sign( $data ) {
	return substr( hash_hmac( 'sha256', $data, wp_salt( 'auth' ) . 'lmc' ), 0, 32 );
}

function lmc_verify_sign( $data, $sig ) {
	return is_string( $sig ) && hash_equals( lmc_sign( $data ), $sig );
}

function lmc_page_url( $key, $args = array() ) {
	$id  = (int) get_option( 'lmc_page_' . $key );
	$url = $id ? get_permalink( $id ) : home_url( '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function lmc_is_employer( $user_id = null ) {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $user && $user->exists() && ( in_array( 'lmc_employer', (array) $user->roles, true ) || user_can( $user, 'manage_options' ) );
}

/** Simple flash messages stored per user/session cookie-less via transient keyed by user. */
function lmc_flash( $message, $type = 'success' ) {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return;
	}
	$all   = get_transient( 'lmc_flash_' . $uid ) ?: array();
	$all[] = array( 'm' => $message, 't' => $type );
	set_transient( 'lmc_flash_' . $uid, $all, 300 );
}

function lmc_render_flash() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return '';
	}
	$all = get_transient( 'lmc_flash_' . $uid );
	if ( ! $all ) {
		return '';
	}
	delete_transient( 'lmc_flash_' . $uid );
	$out = '';
	foreach ( $all as $f ) {
		$out .= '<div class="lmc-notice lmc-notice--' . esc_attr( $f['t'] ) . '" role="status">' . wp_kses_post( $f['m'] ) . '</div>';
	}
	return $out;
}

function lmc_notice( $message, $type = 'error' ) {
	return '<div class="lmc-notice lmc-notice--' . esc_attr( $type ) . '" role="alert">' . wp_kses_post( $message ) . '</div>';
}

/** Turn a textarea of lines into an array of trimmed, non-empty bullet items. */
function lmc_lines( $text ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	$lines = array_map( function ( $l ) {
		return trim( preg_replace( '/^[\-\*\x{2022}]\s*/u', '', $l ) );
	}, $lines );
	return array_values( array_filter( $lines, 'strlen' ) );
}

/** Is this request most likely from a crawler? (Used to keep view stats honest.) */
function lmc_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/', $ua );
}

/** Plain-text vacancy title (no HTML entities) for emails, social posts and structured data. */
function lmc_plain_title( $post_id ) {
	return html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' );
}
