<?php
/**
 * UK location search.
 *  1. Region / county names match the structured location taxonomy (includes child counties).
 *  2. Postcodes, outward codes and town names are geocoded with postcodes.io (free, open data,
 *     no key) and matched by distance.
 *  3. If geocoding is unavailable we fall back to matching the town text.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Location {

	const API = 'https://api.postcodes.io/';

	public static function is_postcode( $s ) {
		$s = strtoupper( trim( $s ) );
		return (bool) preg_match( '/^[A-Z]{1,2}[0-9][A-Z0-9]?(\s*[0-9][A-Z]{2})?$/', $s );
	}

	/** Returns [lat, lng] or null. Results cached for 30 days. */
	public static function geocode( $query ) {
		$query = trim( (string) $query );
		if ( '' === $query ) {
			return null;
		}
		$pre = apply_filters( 'lmc_pre_geocode', null, $query );
		if ( null !== $pre ) {
			return $pre ?: null;
		}
		$key    = 'lmc_geo_' . md5( strtolower( $query ) );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached ?: null;
		}
		$result = null;
		if ( self::is_postcode( $query ) ) {
			$pc   = strtoupper( preg_replace( '/\s+/', '', $query ) );
			$path = strlen( $pc ) <= 4 ? 'outcodes/' . rawurlencode( $pc ) : 'postcodes/' . rawurlencode( $pc );
			$res  = self::get( $path );
			if ( $res && isset( $res['result']['latitude'] ) ) {
				$result = array( (float) $res['result']['latitude'], (float) $res['result']['longitude'] );
			} elseif ( strlen( $pc ) > 4 ) {
				// Fall back to the outward code of a full postcode.
				return self::geocode( substr( $pc, 0, -3 ) );
			}
		} else {
			$res = self::get( 'places?limit=5&q=' . rawurlencode( $query ) );
			if ( $res && ! empty( $res['result'] ) ) {
				// Prefer towns/cities over hamlets with the same name.
				$best = $res['result'][0];
				foreach ( $res['result'] as $r ) {
					if ( in_array( $r['local_type'] ?? '', array( 'City', 'Town' ), true ) ) {
						$best = $r;
						break;
					}
				}
				$result = array( (float) $best['latitude'], (float) $best['longitude'] );
			}
		}
		set_transient( $key, $result ?: 0, $result ? 30 * DAY_IN_SECONDS : HOUR_IN_SECONDS );
		return $result;
	}

	private static function get( $path ) {
		$res = wp_remote_get( self::API . $path, array( 'timeout' => 5 ) );
		if ( is_wp_error( $res ) ) {
			return null;
		}
		if ( wp_remote_retrieve_response_code( $res ) !== 200 ) {
			return null;
		}
		return json_decode( wp_remote_retrieve_body( $res ), true );
	}

	public static function geocode_job( $job_id ) {
		$pc   = lmc_job_meta( $job_id, 'postcode' );
		$town = lmc_job_meta( $job_id, 'town' );
		$geo  = $pc ? self::geocode( $pc ) : null;
		if ( ! $geo && $town ) {
			$geo = self::geocode( $town );
		}
		if ( $geo ) {
			update_post_meta( $job_id, '_lmc_lat', $geo[0] );
			update_post_meta( $job_id, '_lmc_lng', $geo[1] );
		}
		return $geo;
	}

	public static function miles( $a, $b ) {
		$r    = 3958.8;
		$dlat = deg2rad( $b[0] - $a[0] );
		$dlng = deg2rad( $b[1] - $a[1] );
		$h    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $a[0] ) ) * cos( deg2rad( $b[0] ) ) * sin( $dlng / 2 ) ** 2;
		return 2 * $r * asin( min( 1, sqrt( $h ) ) );
	}

	/** Match a free-text location to a region/county term. */
	public static function match_term( $text ) {
		$text = trim( $text );
		if ( '' === $text ) {
			return null;
		}
		$t = get_term_by( 'slug', sanitize_title( $text ), 'lmc_location' );
		if ( ! $t ) {
			$t = get_term_by( 'name', $text, 'lmc_location' );
		}
		return $t ?: null;
	}

	/** For the search box autocomplete: all regions and counties. */
	public static function suggestions() {
		$terms = get_terms( array( 'taxonomy' => 'lmc_location', 'hide_empty' => false ) );
		return is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'name' );
	}
}
