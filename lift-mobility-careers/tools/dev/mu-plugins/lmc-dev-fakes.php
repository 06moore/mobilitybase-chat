<?php
/**
 * DEV ONLY – installed by tools/dev/setup-local.sh into the disposable local site. Never deployed.
 *
 * 1. Fake Stripe: intercepts calls to api.stripe.com and provides a fake hosted checkout page at
 *    /?lmc_fake_checkout=cs_… with "Pay", "Card declined" and "Abandon" buttons. Paying sends a
 *    correctly SIGNED checkout.session.completed webhook through the real webhook endpoint, so the
 *    plugin's signature check, idempotency and fulfilment code all run for real.
 * 2. Test helpers (admins only): shift a vacancy's end date, run the hourly automation, refund a payment.
 */
if ( ! defined( 'LMC_FAKE_STRIPE' ) || ! LMC_FAKE_STRIPE ) {
	return;
}

function lmc_fake_sessions( $set = null ) {
	if ( null !== $set ) {
		update_option( 'lmc_fake_sessions', $set, false );
	}
	return get_option( 'lmc_fake_sessions', array() );
}

function lmc_fake_webhook( array $event ) {
	$payload = wp_json_encode( $event );
	$ts      = time();
	$req     = new WP_REST_Request( 'POST', '/lmc/v1/stripe-webhook' );
	$req->set_body( $payload );
	$req->set_header( 'Stripe-Signature', 't=' . $ts . ',v1=' . hash_hmac( 'sha256', $ts . '.' . $payload, LMC_Payments::webhook_secret() ) );
	return rest_do_request( $req )->get_status();
}

add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( 0 !== strpos( $url, 'https://api.stripe.com/v1/' ) ) {
		return $pre;
	}
	$path = substr( $url, strlen( 'https://api.stripe.com/v1/' ) );
	$resp = function ( $body, $code = 200 ) {
		return array( 'headers' => array(), 'body' => wp_json_encode( $body ), 'response' => array( 'code' => $code, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
	};
	if ( 'Bearer ' . LMC_Payments::secret_key() !== ( $args['headers']['Authorization'] ?? '' ) ) {
		return $resp( array( 'error' => array( 'message' => 'Invalid API key' ) ), 401 );
	}
	if ( 'balance' === $path ) {
		return $resp( array( 'object' => 'balance' ) );
	}
	if ( 'checkout/sessions' === $path && 'POST' === $args['method'] ) {
		$b     = $args['body'];
		$total = 0;
		for ( $i = 0; isset( $b[ "line_items[$i][price_data][unit_amount]" ] ); $i++ ) {
			$total += (int) $b[ "line_items[$i][price_data][unit_amount]" ];
		}
		$all = lmc_fake_sessions();
		$id  = 'cs_test_fake' . ( count( $all ) + 1 ) . wp_generate_password( 6, false );
		$all[ $id ] = array( 'id' => $id, 'amount_total' => $total, 'currency' => 'gbp', 'payment_status' => 'unpaid', 'status' => 'open', 'success_url' => str_replace( '{CHECKOUT_SESSION_ID}', $id, $b['success_url'] ), 'cancel_url' => $b['cancel_url'], 'payment_intent' => 'pi_fake_' . $id, 'name' => $b['line_items[0][price_data][product_data][name]'] ?? '' );
		lmc_fake_sessions( $all );
		return $resp( array( 'id' => $id, 'url' => add_query_arg( 'lmc_fake_checkout', $id, home_url( '/' ) ) ) );
	}
	if ( preg_match( '#^checkout/sessions/(cs_[A-Za-z0-9_]+)$#', $path, $m ) ) {
		$s = lmc_fake_sessions()[ $m[1] ] ?? null;
		return $s ? $resp( $s ) : $resp( array( 'error' => array( 'message' => 'No such session' ) ), 404 );
	}
	return $resp( array( 'error' => array( 'message' => 'Fake Stripe: unsupported ' . $path ) ), 400 );
}, 10, 3 );

add_action( 'template_redirect', function () {
	if ( empty( $_GET['lmc_fake_checkout'] ) ) {
		return;
	}
	$id  = sanitize_text_field( wp_unslash( $_GET['lmc_fake_checkout'] ) );
	$all = lmc_fake_sessions();
	$s   = $all[ $id ] ?? null;
	if ( ! $s ) {
		wp_die( 'Unknown checkout session' );
	}
	$act = sanitize_key( $_POST['act'] ?? '' );
	$msg = '';
	if ( 'pay' === $act ) {
		$all[ $id ]['payment_status'] = 'paid';
		$all[ $id ]['status']         = 'complete';
		lmc_fake_sessions( $all );
		lmc_fake_webhook( array( 'type' => 'checkout.session.completed', 'data' => array( 'object' => $all[ $id ] ) ) );
		wp_redirect( $s['success_url'] );
		exit;
	}
	if ( 'decline' === $act ) {
		$msg = '<p id="declined" style="color:#B42318"><strong>Your card was declined.</strong> Try another card.</p>';
	}
	if ( 'abandon' === $act ) {
		$all[ $id ]['status'] = 'expired';
		lmc_fake_sessions( $all );
		lmc_fake_webhook( array( 'type' => 'checkout.session.expired', 'data' => array( 'object' => $all[ $id ] ) ) );
		wp_redirect( $s['cancel_url'] );
		exit;
	}
	echo '<!doctype html><meta name="viewport" content="width=device-width"><title>Fake Stripe Checkout</title><body style="font-family:system-ui;max-width:420px;margin:40px auto;padding:0 16px"><p style="background:#fde68a;padding:8px">DEV ONLY – fake Stripe checkout</p><h1>Pay ' . esc_html( lmc_money( $s['amount_total'] ) ) . '</h1><p>' . esc_html( $s['name'] ) . '</p>' . $msg
		. '<form method="post"><button name="act" value="pay" id="pay">Pay with test card 4242</button> <button name="act" value="decline" id="decline">Use a declined card</button> <button name="act" value="abandon" id="abandon">Abandon (← back)</button></form></body>';
	exit;
} );

add_action( 'rest_api_init', function () {
	$admin = function () {
		return current_user_can( 'manage_options' );
	};
	register_rest_route( 'lmc-dev/v1', '/shift-expiry', array( 'methods' => 'POST', 'permission_callback' => $admin, 'callback' => function ( $r ) {
		$id = absint( $r['job'] );
		update_post_meta( $id, '_lmc_expires', time() + (float) $r['days'] * DAY_IN_SECONDS );
		return array( 'expires' => (int) get_post_meta( $id, '_lmc_expires', true ), 'lifecycle' => LMC_Jobs::run_lifecycle() );
	} ) );
	register_rest_route( 'lmc-dev/v1', '/refund', array( 'methods' => 'POST', 'permission_callback' => $admin, 'callback' => function ( $r ) {
		global $wpdb;
		$p = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'payments' ) . ' WHERE id = %d', absint( $r['payment'] ) ) );
		return array( 'status' => lmc_fake_webhook( array( 'type' => 'charge.refunded', 'data' => array( 'object' => array( 'payment_intent' => $p->stripe_intent, 'refunded' => true, 'amount_refunded' => (int) $p->total ) ) ) ) );
	} ) );
	register_rest_route( 'lmc-dev/v1', '/state', array( 'methods' => 'GET', 'permission_callback' => $admin, 'callback' => function ( $r ) {
		global $wpdb;
		$id = absint( $r['job'] );
		return array( 'status' => get_post_status( $id ), 'expires' => (int) get_post_meta( $id, '_lmc_expires', true ), 'payments' => $wpdb->get_results( $wpdb->prepare( 'SELECT id, user_id, job_id, package, purpose, status, total FROM ' . lmc_table( 'payments' ) . ' WHERE job_id = %d OR %d = 0 ORDER BY id', $id, $id ) ) );
	} ) );
} );
