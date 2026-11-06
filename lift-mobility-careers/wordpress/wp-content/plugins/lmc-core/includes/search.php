<?php
/**
 * Vacancy search and filtering. Works on /jobs/, sector/role/location pages and the
 * [lmc_job_list] shortcode. Paid tier ordering: Premium → Featured → Standard → newest.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Search {

	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'main_query' ) );
	}

	public static function filters() {
		$g = wp_unslash( $_GET );
		$f = array(
			'q'          => sanitize_text_field( $g['q'] ?? '' ),
			'location'   => sanitize_text_field( $g['location'] ?? '' ),
			'radius'     => absint( $g['radius'] ?? 25 ) ?: 25,
			'sector'     => sanitize_title( $g['sector'] ?? '' ),
			'role'       => sanitize_title( $g['role'] ?? '' ),
			'type'       => sanitize_title( $g['type'] ?? '' ),
			'experience' => sanitize_title( $g['experience'] ?? '' ),
			'salary_min' => absint( $g['salary_min'] ?? 0 ),
			'sort'       => in_array( $g['sort'] ?? '', array( 'date', 'salary' ), true ) ? $g['sort'] : '',
		);
		return $f;
	}

	public static function has_filters( $f = null ) {
		$f = $f ?: self::filters();
		foreach ( array( 'q', 'location', 'sector', 'role', 'type', 'experience', 'salary_min', 'sort' ) as $k ) {
			if ( ! empty( $f[ $k ] ) ) {
				return true;
			}
		}
		return false;
	}

	public static function main_query( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( ! ( $q->is_post_type_archive( 'lmc_job' ) || $q->is_tax( array( 'lmc_sector', 'lmc_role', 'lmc_location' ) ) ) ) {
			return;
		}
		$args = self::build_args( self::filters() );
		foreach ( $args as $k => $v ) {
			if ( 'tax_query' === $k ) {
				$existing = $q->get( 'tax_query' ) ?: array();
				$v        = array_merge( (array) $existing, $v );
			}
			$q->set( $k, $v );
		}
		$q->set( 'posts_per_page', 20 );
	}

	/** Build WP_Query args from filters. */
	public static function build_args( array $f, array $base = array() ) {
		$args = array_merge(
			array(
				'post_type'   => 'lmc_job',
				'post_status' => 'publish',
				'tax_query'   => array(),
				'meta_query'  => array(),
			),
			$base
		);
		if ( $f['q'] ) {
			$args['s'] = $f['q'];
		}
		foreach ( array( 'sector' => 'lmc_sector', 'role' => 'lmc_role', 'type' => 'lmc_type', 'experience' => 'lmc_experience' ) as $k => $tax ) {
			if ( ! empty( $f[ $k ] ) ) {
				$args['tax_query'][] = array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $f[ $k ] ) ) );
			}
		}
		if ( $f['salary_min'] ) {
			$args['meta_query'][] = array( 'key' => '_lmc_salary_annual', 'value' => $f['salary_min'], 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( $f['location'] ) {
			$term = LMC_Location::match_term( $f['location'] );
			if ( $term ) {
				$args['tax_query'][] = array( 'taxonomy' => 'lmc_location', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => true );
			} else {
				$geo = LMC_Location::geocode( $f['location'] );
				if ( $geo ) {
					$ids              = self::ids_within( $geo, $f['radius'] );
					$args['post__in'] = $ids ?: array( 0 );
				} else {
					$args['meta_query'][] = array( 'key' => '_lmc_town', 'value' => $f['location'], 'compare' => 'LIKE' );
				}
			}
		}
		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}
		// Ordering.
		$args['meta_query']['lmc_priority'] = array( 'key' => '_lmc_priority', 'type' => 'NUMERIC', 'compare' => 'EXISTS' );
		if ( 'date' === $f['sort'] ) {
			$args['orderby'] = array( 'date' => 'DESC' );
		} elseif ( 'salary' === $f['sort'] ) {
			$args['meta_query']['lmc_salary'] = array( 'key' => '_lmc_salary_annual', 'type' => 'NUMERIC', 'compare' => 'EXISTS' );
			$args['orderby']                  = array( 'lmc_salary' => 'DESC', 'date' => 'DESC' );
		} else {
			$args['orderby'] = array( 'lmc_priority' => 'DESC', 'date' => 'DESC' );
		}
		return $args;
	}

	public static function ids_within( $geo, $miles ) {
		$ids = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_lmc_lat' ) );
		$out = array();
		foreach ( $ids as $id ) {
			$p = array( (float) get_post_meta( $id, '_lmc_lat', true ), (float) get_post_meta( $id, '_lmc_lng', true ) );
			if ( LMC_Location::miles( $geo, $p ) <= $miles ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	public static function annualise( $amount, $period ) {
		$amount = (float) $amount;
		return (int) round( 'HOUR' === $period ? $amount * 37.5 * 52 : ( 'DAY' === $period ? $amount * 230 : $amount ) );
	}

	/** Live counts per sector, for the homepage and sector grid. */
	public static function sector_counts() {
		$out = array();
		foreach ( get_terms( array( 'taxonomy' => 'lmc_sector', 'hide_empty' => false ) ) as $t ) {
			$out[ $t->slug ] = count( get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'lmc_sector', 'terms' => $t->term_id ) ) ) ) );
		}
		return $out;
	}
}
LMC_Search::init();
