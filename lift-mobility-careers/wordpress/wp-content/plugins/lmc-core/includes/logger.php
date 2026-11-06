<?php
defined( 'ABSPATH' ) || exit;

/**
 * Operational log. Anything at 'error' level appears in the dashboard's "Automation errors"
 * panel and the weekly report until it is marked resolved.
 */
function lmc_log( $level, $context, $message ) {
	global $wpdb;
	$wpdb->insert(
		lmc_table( 'log' ),
		array(
			'level'      => $level,
			'context'    => substr( $context, 0, 40 ),
			'message'    => is_string( $message ) ? $message : wp_json_encode( $message ),
			'resolved'   => 'error' === $level ? 0 : 1,
			'created_at' => lmc_mysql_time(),
		)
	);
}

function lmc_open_errors( $limit = 20 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'log' ) . " WHERE level = 'error' AND resolved = 0 ORDER BY id DESC LIMIT %d", $limit ) );
}

/** Record the last successful run of each automation so silent failures become visible. */
function lmc_heartbeat( $job ) {
	$all         = get_option( 'lmc_heartbeats', array() );
	$all[ $job ] = lmc_now();
	update_option( 'lmc_heartbeats', $all, false );
}

/** Returns human-readable warnings for automations that have not run when expected. */
function lmc_heartbeat_warnings() {
	$hb       = get_option( 'lmc_heartbeats', array() );
	$expected = array(
		'lifecycle' => array( 2 * HOUR_IN_SECONDS, 'Vacancy expiry & reminder automation' ),
		'discovery' => array( 2 * DAY_IN_SECONDS, 'Vacancy discovery monitor' ),
		'weekly'    => array( 8 * DAY_IN_SECONDS, 'Weekly report' ),
	);
	$out      = array();
	$installed = (int) get_option( 'lmc_installed_at', lmc_now() );
	foreach ( $expected as $k => $e ) {
		$last = $hb[ $k ] ?? 0;
		if ( 'discovery' === $k && ! lmc_opt( 'discovery_enabled' ) ) {
			continue;
		}
		if ( ( lmc_now() - max( $last, $installed ) ) > $e[0] ) {
			$out[] = $e[1] . ( $last ? ' last ran ' . human_time_diff( $last, lmc_now() ) . ' ago.' : ' has not run yet.' ) . ' Check that the server cron job is calling wp-cron.php.';
		}
	}
	return $out;
}
add_action( 'init', function () {
	if ( ! get_option( 'lmc_installed_at' ) ) {
		update_option( 'lmc_installed_at', lmc_now() );
	}
} );

add_action( 'wp_mail_failed', function ( $error ) {
	lmc_log( 'error', 'email', 'Email failed: ' . $error->get_error_message() . ' → ' . implode( ', ', (array) ( $error->get_error_data()['to'] ?? array() ) ) );
} );
