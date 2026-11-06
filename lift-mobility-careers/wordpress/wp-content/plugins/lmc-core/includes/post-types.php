<?php
defined( 'ABSPATH' ) || exit;

/**
 * Vacancy post type, taxonomies and statuses.
 *
 * Statuses:
 *  lmc_unpaid  – submitted by employer, waiting for payment / credit
 *  pending     – paid, waiting for operator approval (core WP status)
 *  publish     – live
 *  lmc_expired – listing period ended (page stays viewable with a "closed" notice, noindex)
 *  lmc_closed  – employer closed it early / filled
 */
class LMC_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	public static function register() {
		// Taxonomies first so /jobs/sector/… rules take precedence over the vacancy permalink rules.
		$tax = array(
			'lmc_sector'     => array( 'Sectors', 'Sector', 'jobs/sector', true, true ),
			'lmc_role'       => array( 'Job categories', 'Job category', 'jobs/role', true, false ),
			'lmc_location'   => array( 'Locations', 'Location', 'jobs/location', true, true ),
			'lmc_type'       => array( 'Employment types', 'Employment type', false, false, false ),
			'lmc_experience' => array( 'Experience levels', 'Experience level', false, false, false ),
		);
		foreach ( $tax as $name => $t ) {
			register_taxonomy(
				$name,
				array( 'lmc_job' ),
				array(
					'labels'             => array( 'name' => $t[0], 'singular_name' => $t[1], 'menu_name' => $t[0] ),
					'public'             => $t[3],
					'publicly_queryable' => $t[3],
					'hierarchical'       => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
					'show_admin_column'  => in_array( $name, array( 'lmc_sector', 'lmc_location' ), true ),
					'rewrite'            => $t[2] ? array( 'slug' => $t[2], 'with_front' => false, 'hierarchical' => false ) : false,
					'query_var'          => $t[3] ? $name : false,
					'show_in_rest'       => false,
				)
			);
		}

		register_post_type(
			'lmc_job',
			array(
				'labels'          => array(
					'name'          => 'Vacancies',
					'singular_name' => 'Vacancy',
					'add_new_item'  => 'Add vacancy',
					'edit_item'     => 'Edit vacancy',
					'all_items'     => 'All vacancies',
					'menu_name'     => 'Vacancies',
				),
				'public'          => true,
				'show_in_menu'    => 'lmc',
				'has_archive'     => 'jobs',
				'rewrite'         => array( 'slug' => 'jobs', 'with_front' => false ),
				'supports'        => array( 'title', 'editor', 'author', 'revisions' ),
				'show_in_rest'    => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-businessman',
			)
		);

		register_post_status( 'lmc_unpaid', array( 'label' => 'Awaiting payment', 'public' => false, 'internal' => false, 'protected' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Awaiting payment <span class="count">(%s)</span>', 'Awaiting payment <span class="count">(%s)</span>' ) ) );
		register_post_status( 'lmc_expired', array( 'label' => 'Expired', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' ) ) );
		register_post_status( 'lmc_closed', array( 'label' => 'Closed', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>' ) ) );
	}

	/** Field definitions shared by the employer form, admin metabox and templates. */
	public static function fields() {
		return array(
			'employer_name'    => array( 'label' => 'Employer name', 'required' => true ),
			'employer_website' => array( 'label' => 'Employer website', 'type' => 'url' ),
			'town'             => array( 'label' => 'Town / city', 'required' => true ),
			'postcode'         => array( 'label' => 'Postcode or outward code (e.g. ME14)', 'help' => 'Used for distance search only. The outward code is enough.' ),
			'location_note'    => array( 'label' => 'Location detail', 'help' => 'e.g. "Field-based covering Kent and East Sussex"' ),
			'salary_min'       => array( 'label' => 'Salary from (£)', 'required' => true, 'type' => 'number' ),
			'salary_max'       => array( 'label' => 'Salary to (£)', 'type' => 'number' ),
			'salary_period'    => array( 'label' => 'Salary period', 'type' => 'select', 'options' => array( 'YEAR' => 'per year', 'HOUR' => 'per hour', 'DAY' => 'per day' ) ),
			'salary_note'      => array( 'label' => 'Package extras', 'help' => 'e.g. "+ company van, overtime, on-call allowance"' ),
			'responsibilities' => array( 'label' => 'Key responsibilities', 'type' => 'textarea', 'required' => true, 'help' => 'One per line.' ),
			'requirements'     => array( 'label' => 'Requirements', 'type' => 'textarea', 'required' => true, 'help' => 'Qualifications, experience, licences – one per line.' ),
			'benefits'         => array( 'label' => 'Benefits', 'type' => 'textarea', 'help' => 'One per line (optional).' ),
			'closing_date'     => array( 'label' => 'Closing date', 'type' => 'date' ),
			'apply_method'     => array( 'label' => 'How should candidates apply?', 'type' => 'select', 'options' => array( 'url' => 'Via a link to our website / application system', 'email' => 'By email' ) ),
			'apply_url'        => array( 'label' => 'Application link', 'type' => 'url' ),
			'apply_email'      => array( 'label' => 'Application email', 'type' => 'email' ),
		);
	}
}
LMC_Post_Types::init();

/** Get a vacancy meta value. */
function lmc_job_meta( $job_id, $key ) {
	return get_post_meta( $job_id, '_lmc_' . $key, true );
}

function lmc_job_terms( $job_id, $tax ) {
	$t = get_the_terms( $job_id, $tax );
	return ( $t && ! is_wp_error( $t ) ) ? $t : array();
}

function lmc_job_term_names( $job_id, $tax ) {
	return implode( ', ', wp_list_pluck( lmc_job_terms( $job_id, $tax ), 'name' ) );
}

function lmc_salary_text( $job_id ) {
	$min    = (float) lmc_job_meta( $job_id, 'salary_min' );
	$max    = (float) lmc_job_meta( $job_id, 'salary_max' );
	$period = lmc_job_meta( $job_id, 'salary_period' ) ?: 'YEAR';
	if ( ! $min ) {
		return '';
	}
	$fmt = function ( $v ) use ( $period ) {
		return '£' . ( 'YEAR' === $period ? number_format( $v ) : number_format( $v, $v == floor( $v ) ? 0 : 2 ) );
	};
	$label = array( 'YEAR' => 'per year', 'HOUR' => 'per hour', 'DAY' => 'per day' )[ $period ] ?? '';
	$txt   = $fmt( $min ) . ( $max > $min ? ' – ' . $fmt( $max ) : '' ) . ' ' . $label;
	return trim( $txt );
}

function lmc_location_text( $job_id ) {
	$town   = lmc_job_meta( $job_id, 'town' );
	$county = '';
	foreach ( lmc_job_terms( $job_id, 'lmc_location' ) as $t ) {
		if ( $t->parent ) {
			$county = $t->name;
		}
	}
	if ( $county && $town && false !== stripos( $county, $town ) ) {
		$county = '';
	}
	return implode( ', ', array_filter( array( $town, $county ) ) );
}

function lmc_job_tier( $job_id ) {
	return lmc_job_meta( $job_id, 'tier' ) ?: 'standard';
}
