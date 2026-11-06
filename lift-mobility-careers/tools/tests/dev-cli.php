<?php
/** CLI helper for the browser tests (local dev site only). Usage: php dev-cli.php <command> [args] */
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require ( getenv( 'LMC_DEV_DIR' ) ?: '/tmp/lmc-dev' ) . '/wp/wp-load.php';
global $wpdb;
$cmd = $argv[1] ?? '';
$out = null;
switch ( $cmd ) {
	case 'setup-payments':
		lmc_update_opts( array( 'stripe_test_secret' => 'sk_test_fake', 'stripe_test_webhook' => 'whsec_fake', 'payments_enabled' => 1, 'ga4_id' => 'G-TEST123' ) );
		$out = array( 'enabled' => LMC_Payments::enabled() );
		break;
	case 'shift-expiry':
		update_post_meta( (int) $argv[2], '_lmc_expires', time() + (float) $argv[3] * DAY_IN_SECONDS );
		$out = LMC_Jobs::run_lifecycle();
		break;
	case 'refund':
		$p   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'payments' ) . " WHERE job_id = %d AND status = 'paid' ORDER BY id DESC LIMIT 1", (int) $argv[2] ) );
		$out = array( 'refunded_payment' => $p->id, 'ok' => LMC_Payments::mark_refunded( $p->stripe_intent, true, (int) $p->total ) );
		break;
	case 'state':
		$id  = (int) $argv[2];
		$out = array( 'status' => get_post_status( $id ), 'title' => get_the_title( $id ), 'expires' => (int) get_post_meta( $id, '_lmc_expires', true ), 'salary_min' => get_post_meta( $id, '_lmc_salary_min', true ), 'logo' => (int) get_post_meta( $id, '_lmc_logo_id', true ), 'payments' => $wpdb->get_results( $wpdb->prepare( 'SELECT id, package, purpose, status FROM ' . lmc_table( 'payments' ) . ' WHERE job_id = %d ORDER BY id', $id ) ) );
		break;
	case 'credits':
		$u   = get_user_by( 'email', $argv[2] );
		$out = $u ? LMC_Credits::balance( $u->ID ) : null;
		break;
	case 'demo-count':
		$out = LMC_Launch::demo_counts()['jobs'];
		break;
	case 'user-exists':
		$out = (bool) email_exists( $argv[2] );
		break;
}
echo wp_json_encode( $out );
