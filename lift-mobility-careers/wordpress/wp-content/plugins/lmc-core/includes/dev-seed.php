<?php
/**
 * Demo content for LOCAL DEVELOPMENT ONLY (triggered by tools/dev/install.php).
 * All employers are fictional and clearly named "Demo …". Never run this on the live site.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_dev_seed', 'lmc_dev_seed' );

function lmc_dev_seed() {
	$emp = wp_insert_user( array( 'user_login' => 'demo-employer', 'user_email' => 'employer@example.test', 'user_pass' => 'demo-employer-pass', 'first_name' => 'Sam', 'last_name' => 'Example', 'role' => 'lmc_employer' ) );
	update_user_meta( $emp, 'lmc_company', 'Demo Stairlifts Ltd' );
	update_user_meta( $emp, 'lmc_email_verified', time() );
	update_user_meta( $emp, 'lmc_trusted', 1 );
	update_user_meta( $emp, 'lmc_entity_type', 'limited_company' );
	update_user_meta( $emp, 'lmc_demo', 1 );

	$jobs = array(
		array( 'Stairlift Service Engineer', 'Demo Stairlifts Ltd', 'stairlifts', 'service-engineer', 'kent', 'Maidstone', 'ME14', 31000, 36000, 'premium', 'experienced' ),
		array( 'Curved Stairlift Installation Engineer', 'Demo Stairlifts Ltd', 'stairlifts', 'installation-engineer', 'west-yorkshire', 'Leeds', 'LS10', 30000, 34000, 'featured', 'experienced' ),
		array( 'Home Lift Commissioning Engineer', 'Demo Home Lifts Ltd', 'home-lifts', 'commissioning-engineer', 'greater-manchester', 'Manchester', 'M17', 38000, 44000, 'premium', 'senior' ),
		array( 'Platform Lift Service & Breakdown Engineer', 'Demo Access Lifts Ltd', 'platform-lifts', 'breakdown-engineer', 'greater-london', 'London', 'SE1', 40000, 46000, 'standard', 'experienced' ),
		array( 'LOLER Inspector – Patient Hoists', 'Demo Care Equipment Ltd', 'patient-handling', 'loler-inspector', 'west-midlands-county', 'Birmingham', 'B6', 36000, 41000, 'featured', 'senior' ),
		array( 'Ceiling Track Hoist Installer', 'Demo Care Equipment Ltd', 'ceiling-track-hoists', 'installation-engineer', 'bristol', 'Bristol', 'BS2', 29000, 33000, 'standard', 'experienced' ),
		array( 'Trainee Stairlift Engineer', 'Demo Stairlifts Ltd', 'stairlifts', 'trainee-engineer', 'hampshire', 'Southampton', 'SO15', 25000, 27000, 'standard', 'entry-level' ),
		array( 'Through-Floor Lift Installation Engineer', 'Demo Home Lifts Ltd', 'through-floor-lifts', 'installation-engineer', 'glasgow-strathclyde', 'Glasgow', 'G2', 33000, 37000, 'standard', 'experienced' ),
		array( 'Service Manager – Access & Platform Lifts', 'Demo Access Lifts Ltd', 'access-lifts', 'service-manager', 'essex', 'Chelmsford', 'CM1', 50000, 58000, 'standard', 'management' ),
	);
	$i = 0;
	foreach ( $jobs as $j ) {
		$id = wp_insert_post( array(
			'post_type'    => 'lmc_job',
			'post_status'  => 'draft',
			'post_title'   => $j[0],
			'post_author'  => $emp,
			'post_content' => '<p>This is a demonstration vacancy used to preview the site design. ' . $j[1] . ' is a fictional employer.</p><p>You will join a friendly regional team looking after customers across your patch, with a fully equipped company van, manufacturer training and a clear route to senior engineer. Most work is planned in advance, with a fair on-call rota and paid overtime.</p>',
		) );
		$meta = array(
			'employer_name'    => $j[1],
			'town'             => $j[5],
			'postcode'         => $j[6],
			'location_note'    => 'Field-based covering ' . $j[5] . ' and surrounding areas',
			'salary_min'       => $j[7],
			'salary_max'       => $j[8],
			'salary_period'    => 'YEAR',
			'salary_note'      => '+ company van, overtime, on-call allowance',
			'responsibilities' => "Carry out planned maintenance and safety checks\nDiagnose and repair electrical and mechanical faults\nComplete digital service reports accurately\nProvide friendly, clear advice to customers",
			'requirements'     => "Electrical or electromechanical background\nFull UK driving licence\nGood fault-finding skills\nCustomer-focused attitude",
			'benefits'         => "25 days holiday plus bank holidays\nCompany pension\nManufacturer training",
			'apply_method'     => 'url',
			'apply_url'        => 'https://example.com/careers',
			'salary_annual'    => $j[8],
			'social_ok'        => 1,
			'authorised'       => array( 'name' => 'Demo data', 'time' => time() ),
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, '_lmc_' . $k, $v );
		}
		update_post_meta( $id, '_lmc_demo', 1 ); // Never indexed, never in sitemaps/feeds/alerts; removable in one click.
		update_post_meta( $id, '_lmc_tier', $j[9] );
		update_post_meta( $id, '_lmc_priority', lmc_tier_priority( $j[9] ) );
		update_post_meta( $id, '_lmc_lat', 51.5 + $i * 0.3 );
		update_post_meta( $id, '_lmc_lng', -0.1 - $i * 0.2 );
		wp_set_object_terms( $id, $j[2], 'lmc_sector' );
		wp_set_object_terms( $id, $j[3], 'lmc_role' );
		wp_set_object_terms( $id, 'full-time', 'lmc_type' );
		wp_set_object_terms( $id, $j[10], 'lmc_experience' );
		$c = get_term_by( 'slug', $j[4], 'lmc_location' );
		if ( $c ) {
			wp_set_object_terms( $id, array( $c->term_id, $c->parent ), 'lmc_location' );
		}
		add_filter( 'lmc_pre_geocode', '__return_false' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish', 'post_date' => gmdate( 'Y-m-d H:i:s', time() - $i * DAY_IN_SECONDS ) ) );
		update_post_meta( $id, '_lmc_lat', 51.27 + $i * 0.5 );
		$i++;
	}
	LMC_CRM::save( array( 'company' => 'Demo Prospect Lifts Ltd', 'entity_type' => 'limited_company', 'contact_name' => 'Alex Demo', 'contact_email' => 'careers@example.test', 'status' => 'ready', 'sector' => 'Stairlifts', 'notes' => 'Demo prospect.' ) );
}
