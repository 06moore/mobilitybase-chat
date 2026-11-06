<?php
/**
 * Privacy-friendly vacancy statistics: page views and "apply" clicks per vacancy per day.
 * No cookies, no IP addresses, no personal data – counts only. Recorded by a small beacon
 * so figures stay accurate behind page caching.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Stats {

	public static function init() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'lmc/v1', '/track', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'track_endpoint' ),
				'permission_callback' => '__return_true',
			) );
		} );
	}

	public static function track_endpoint( WP_REST_Request $r ) {
		$id   = absint( $r->get_param( 'job' ) );
		$type = 'click' === $r->get_param( 'e' ) ? 'clicks' : 'views';
		if ( $id && 'lmc_job' === get_post_type( $id ) && 'publish' === get_post_status( $id ) && ! lmc_is_bot() && ! current_user_can( 'edit_post', $id ) ) {
			self::record( $id, $type );
		}
		return new WP_REST_Response( null, 204 );
	}

	public static function record( $job_id, $type = 'views', $n = 1 ) {
		global $wpdb;
		$t   = lmc_table( 'stats' );
		$day = wp_date( 'Y-m-d', lmc_now() );
		$col = 'clicks' === $type ? 'clicks' : 'views';
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE $t SET $col = $col + %d WHERE day = %s AND job_id = %d", $n, $day, $job_id ) );
		if ( ! $updated ) {
			$wpdb->insert( $t, array( 'day' => $day, 'job_id' => $job_id, 'views' => 'views' === $col ? $n : 0, 'clicks' => 'clicks' === $col ? $n : 0 ) );
		}
	}

	public static function totals( $job_id ) {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( 'SELECT COALESCE(SUM(views),0) v, COALESCE(SUM(clicks),0) c FROM ' . lmc_table( 'stats' ) . ' WHERE job_id = %d', $job_id ) );
		return array( 'views' => (int) ( $r->v ?? 0 ), 'clicks' => (int) ( $r->c ?? 0 ) );
	}

	/** Site-wide totals between two timestamps. */
	public static function period( $from, $to ) {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( 'SELECT COALESCE(SUM(views),0) v, COALESCE(SUM(clicks),0) c FROM ' . lmc_table( 'stats' ) . ' WHERE day >= %s AND day < %s', wp_date( 'Y-m-d', $from ), wp_date( 'Y-m-d', $to ) ) );
		return array( 'views' => (int) $r->v, 'clicks' => (int) $r->c );
	}

	/** Views per sector over a period – "most popular categories". */
	public static function by_sector( $from, $to ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT job_id, SUM(views) v, SUM(clicks) c FROM ' . lmc_table( 'stats' ) . ' WHERE day >= %s AND day < %s GROUP BY job_id', wp_date( 'Y-m-d', $from ), wp_date( 'Y-m-d', $to ) ) );
		$out  = array();
		foreach ( $rows as $r ) {
			foreach ( lmc_job_terms( $r->job_id, 'lmc_sector' ) as $t ) {
				$out[ $t->name ] = array(
					'views'  => ( $out[ $t->name ]['views'] ?? 0 ) + (int) $r->v,
					'clicks' => ( $out[ $t->name ]['clicks'] ?? 0 ) + (int) $r->c,
				);
			}
		}
		uasort( $out, function ( $a, $b ) {
			return $b['views'] <=> $a['views'];
		} );
		return $out;
	}
}
LMC_Stats::init();
