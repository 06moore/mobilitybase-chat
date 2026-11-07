<?php
/**
 * Advert credits. Multi-advert packs and free introductory listings are stored as credits;
 * publishing a vacancy with a credit needs no payment step.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Credits {

	public static function add( $user_id, $tier, $qty, $source = 'purchase', $payment_id = 0, $note = '', $valid_days = null ) {
		global $wpdb;
		$valid_days = null === $valid_days ? (int) lmc_opt( 'pack_validity_days' ) : (int) $valid_days;
		$wpdb->insert(
			lmc_table( 'credits' ),
			array(
				'user_id'    => $user_id,
				'tier'       => $tier,
				'quantity'   => $qty,
				'remaining'  => $qty,
				'source'     => $source,
				'payment_id' => $payment_id,
				'note'       => $note,
				'created_at' => lmc_mysql_time(),
				'expires_at' => $valid_days ? lmc_mysql_time( lmc_now() + $valid_days * DAY_IN_SECONDS ) : null,
			)
		);
		return (int) $wpdb->insert_id;
	}

	/** Available credits per tier for a user: [tier => count]. */
	public static function balance( $user_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT tier, SUM(remaining) AS n FROM ' . lmc_table( 'credits' ) . ' WHERE user_id = %d AND remaining > 0 AND (expires_at IS NULL OR expires_at > %s) GROUP BY tier', $user_id, lmc_mysql_time() ) );
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ $r->tier ] = (int) $r->n;
		}
		return $out;
	}

	/** Use one credit of $tier (earliest-expiring first). Returns the credit id used, or 0. */
	public static function consume( $user_id, $tier ) {
		global $wpdb;
		$t   = lmc_table( 'credits' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $t WHERE user_id = %d AND tier = %s AND remaining > 0 AND (expires_at IS NULL OR expires_at > %s) ORDER BY (expires_at IS NULL), expires_at ASC, id ASC LIMIT 1", $user_id, $tier, lmc_mysql_time() ) );
		if ( ! $row ) {
			return 0;
		}
		$ok = $wpdb->query( $wpdb->prepare( "UPDATE $t SET remaining = remaining - 1 WHERE id = %d AND remaining > 0", $row->id ) );
		return $ok ? (int) $row->id : 0;
	}

	/** Free introductory listing granted from the CRM / employer screen. One per employer. */
	public static function grant_free_listing( $user_id, $note = 'Introductory free listing' ) {
		if ( get_user_meta( $user_id, 'lmc_free_listing_granted', true ) ) {
			return 0;
		}
		update_user_meta( $user_id, 'lmc_free_listing_granted', lmc_now() );
		lmc_log( 'info', 'credits', "Free listing granted to user $user_id" );
		$tier = lmc_opt( 'free_listing_tier' );
		$tier = in_array( $tier, array( 'standard', 'featured', 'premium' ), true ) ? $tier : 'standard';
		return self::add( $user_id, $tier, 1, 'free', 0, $note, 90 );
	}
}
