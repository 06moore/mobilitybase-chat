<?php
/**
 * Stripe Checkout integration (hosted payment page – card details never touch this site).
 *
 * Flow: employer chooses a package → we create a Checkout Session → Stripe redirects back →
 * Stripe's webhook (checkout.session.completed) confirms payment → we fulfil exactly once.
 * The return page also re-checks the session so fulfilment doesn't depend on webhook timing.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Payments {

	const API = 'https://api.stripe.com/v1/';

	public static function init() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'lmc/v1', '/stripe-webhook', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'webhook' ),
				'permission_callback' => '__return_true', // Authenticated by Stripe signature.
			) );
		} );
	}

	public static function mode() {
		// Real payments are impossible until the site has passed the launch checks.
		if ( class_exists( 'LMC_Launch' ) && ! LMC_Launch::is_live() ) {
			return 'test';
		}
		return 'live' === lmc_opt( 'stripe_mode' ) ? 'live' : 'test';
	}

	public static function secret_key() {
		return (string) lmc_opt( 'stripe_' . self::mode() . '_secret' );
	}

	public static function webhook_secret() {
		return (string) lmc_opt( 'stripe_' . self::mode() . '_webhook' );
	}

	/** Payments are only offered when switched on AND keys are present. */
	public static function enabled() {
		return (bool) lmc_opt( 'payments_enabled' ) && self::secret_key() !== '';
	}

	public static function request( $method, $path, $params = array() ) {
		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization'  => 'Bearer ' . self::secret_key(),
				'Stripe-Version' => '2024-06-20',
			),
		);
		if ( $params ) {
			$args['body'] = $params;
		}
		$res = wp_remote_request( self::API . ltrim( $path, '/' ), $args );
		if ( is_wp_error( $res ) ) {
			lmc_log( 'error', 'stripe', 'Stripe request failed: ' . $res->get_error_message() );
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		$code = wp_remote_retrieve_response_code( $res );
		if ( $code >= 400 ) {
			$msg = $body['error']['message'] ?? ( 'HTTP ' . $code );
			lmc_log( 'error', 'stripe', 'Stripe API error: ' . $msg );
			return new WP_Error( 'stripe', $msg );
		}
		return $body;
	}

	/**
	 * Start a Checkout Session. Returns the Stripe-hosted URL or WP_Error.
	 */
	public static function create_checkout( $user_id, $package_key, $job_id = 0, $purpose = 'new' ) {
		global $wpdb;
		$pkg = lmc_package( $package_key );
		if ( ! $pkg ) {
			return new WP_Error( 'lmc', 'Unknown package.' );
		}
		if ( ! self::enabled() ) {
			return new WP_Error( 'lmc', 'Online payments are not switched on yet.' );
		}
		$user  = get_userdata( $user_id );
		$net   = (int) $pkg['price'];
		$vat   = lmc_vat_amount( $net );
		$wpdb->insert( lmc_table( 'payments' ), array(
			'user_id'    => $user_id,
			'job_id'     => $job_id,
			'package'    => $package_key,
			'purpose'    => $purpose,
			'net'        => $net,
			'vat'        => $vat,
			'total'      => $net + $vat,
			'status'     => 'pending',
			'mode'       => self::mode(),
			'customer'   => mb_substr( get_user_meta( $user_id, 'lmc_company', true ) . ' <' . $user->user_email . '>', 0, 255 ),
			'created_at' => lmc_mysql_time(),
		) );
		$payment_id = (int) $wpdb->insert_id;
		$name       = $pkg['label'] . ( $job_id ? ' – ' . wp_strip_all_tags( get_the_title( $job_id ) ) : '' );

		$params = array(
			'mode'                                   => 'payment',
			'customer_email'                         => $user->user_email,
			'client_reference_id'                    => (string) $payment_id,
			'success_url'                            => add_query_arg( 'lmc_paid', '{CHECKOUT_SESSION_ID}', lmc_page_url( 'dashboard' ) ),
			'cancel_url'                             => add_query_arg( 'lmc_cancelled', $payment_id, lmc_page_url( 'dashboard' ) ),
			'line_items[0][quantity]'                => 1,
			'line_items[0][price_data][currency]'    => 'gbp',
			'line_items[0][price_data][unit_amount]' => $net,
			'line_items[0][price_data][product_data][name]' => mb_substr( $name, 0, 250 ),
			'metadata[payment_id]'                   => $payment_id,
			'metadata[package]'                      => $package_key,
			'metadata[job_id]'                       => $job_id,
			'metadata[purpose]'                      => $purpose,
			'payment_intent_data[description]'       => mb_substr( lmc_opt( 'business_name' ) . ': ' . $name, 0, 250 ),
		);
		if ( $vat ) {
			$params['line_items[1][quantity]']                         = 1;
			$params['line_items[1][price_data][currency]']             = 'gbp';
			$params['line_items[1][price_data][unit_amount]']          = $vat;
			$params['line_items[1][price_data][product_data][name]']   = 'VAT at ' . (float) lmc_opt( 'vat_rate' ) . '%';
		}
		if ( lmc_opt( 'stripe_invoices' ) ) {
			$params['invoice_creation[enabled]'] = 'true';
		}
		$session = self::request( 'POST', 'checkout/sessions', $params );
		if ( is_wp_error( $session ) ) {
			$wpdb->update( lmc_table( 'payments' ), array( 'status' => 'failed' ), array( 'id' => $payment_id ) );
			return $session;
		}
		$wpdb->update( lmc_table( 'payments' ), array( 'stripe_session' => $session['id'] ), array( 'id' => $payment_id ) );
		return $session['url'];
	}

	/** Verify a Stripe-Signature header (v1 scheme, 5-minute tolerance). */
	public static function verify_signature( $payload, $header, $secret, $tolerance = 300 ) {
		if ( ! $secret || ! $header ) {
			return false;
		}
		$t  = null;
		$v1 = array();
		foreach ( explode( ',', $header ) as $part ) {
			$kv = explode( '=', trim( $part ), 2 );
			if ( count( $kv ) !== 2 ) {
				continue;
			}
			if ( 't' === $kv[0] ) {
				$t = (int) $kv[1];
			} elseif ( 'v1' === $kv[0] ) {
				$v1[] = $kv[1];
			}
		}
		if ( ! $t || ! $v1 || abs( lmc_now() - $t ) > $tolerance ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', $t . '.' . $payload, $secret );
		foreach ( $v1 as $sig ) {
			if ( hash_equals( $expected, $sig ) ) {
				return true;
			}
		}
		return false;
	}

	public static function webhook( WP_REST_Request $req ) {
		$payload = $req->get_body();
		if ( ! self::verify_signature( $payload, $req->get_header( 'stripe_signature' ), self::webhook_secret() ) ) {
			lmc_log( 'error', 'stripe', 'Webhook signature check failed – check the webhook signing secret in Settings → Payments.' );
			return new WP_REST_Response( array( 'error' => 'bad signature' ), 400 );
		}
		$event = json_decode( $payload, true );
		$type  = $event['type'] ?? '';
		$obj   = $event['data']['object'] ?? array();
		if ( in_array( $type, array( 'checkout.session.completed', 'checkout.session.async_payment_succeeded' ), true ) ) {
			self::fulfil_session( $obj );
		} elseif ( 'checkout.session.expired' === $type ) {
			self::close_session( $obj['id'] ?? '', 'expired' );
		} elseif ( 'checkout.session.async_payment_failed' === $type ) {
			self::close_session( $obj['id'] ?? '', 'failed' );
		} elseif ( 'charge.refunded' === $type ) {
			self::mark_refunded( $obj['payment_intent'] ?? '', ! empty( $obj['refunded'] ), (int) ( $obj['amount_refunded'] ?? 0 ) );
		}
		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	/** Fulfil a paid Checkout Session exactly once. Safe to call repeatedly. */
	public static function fulfil_session( array $s ) {
		global $wpdb;
		$t = lmc_table( 'payments' );
		if ( ( $s['payment_status'] ?? '' ) !== 'paid' ) {
			return false;
		}
		$p = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE stripe_session = %s", $s['id'] ?? '' ) );
		if ( ! $p ) {
			lmc_log( 'error', 'stripe', 'Paid Stripe session not found locally: ' . ( $s['id'] ?? '?' ) );
			return false;
		}
		if ( 'paid' === $p->status ) {
			return true; // Already done.
		}
		if ( (int) ( $s['amount_total'] ?? -1 ) !== (int) $p->total || strtolower( $s['currency'] ?? '' ) !== 'gbp' ) {
			lmc_log( 'error', 'stripe', "Amount mismatch for payment {$p->id}: expected {$p->total}, got " . ( $s['amount_total'] ?? '?' ) );
			return false;
		}
		// Claim the row atomically so a simultaneous webhook + return page can't both fulfil.
		$claimed = $wpdb->query( $wpdb->prepare( "UPDATE $t SET status = 'paid', paid_at = %s, stripe_intent = %s WHERE id = %d AND status = 'pending'", lmc_mysql_time(), (string) ( $s['payment_intent'] ?? '' ), $p->id ) );
		if ( ! $claimed ) {
			return true;
		}
		self::apply_purchase( $p );
		return true;
	}

	/** A pending checkout that will never complete (expired, or a delayed payment method failed). */
	public static function close_session( $session_id, $status ) {
		global $wpdb;
		$t = lmc_table( 'payments' );
		$p = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE stripe_session = %s AND status = 'pending'", $session_id ) );
		if ( ! $p ) {
			return false;
		}
		$wpdb->update( $t, array( 'status' => $status ), array( 'id' => $p->id ) );
		if ( 'failed' === $status ) {
			$user = get_userdata( $p->user_id );
			if ( $user ) {
				lmc_send_email( 'payment_failed', $user->user_email, array( 'first_name' => $user->first_name ?: 'there', 'package' => lmc_package( $p->package )['label'] ?? $p->package, 'job_title' => $p->job_id ? lmc_plain_title( $p->job_id ) : 'your advert' ), $p->id );
			}
		}
		return true;
	}

	/** Pending checkouts older than 2 days are closed (Stripe sessions expire after 24 hours). */
	public static function cleanup_stale() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . lmc_table( 'payments' ) . " SET status = 'expired' WHERE status = 'pending' AND created_at < %s", lmc_mysql_time( lmc_now() - 2 * DAY_IN_SECONDS ) ) );
	}

	/** Can this payment still be applied to its vacancy in the way it was bought? */
	public static function job_accepts( $p ) {
		if ( ! $p->job_id || 'lmc_job' !== get_post_type( $p->job_id ) ) {
			return false;
		}
		$st = get_post_status( $p->job_id );
		return 'renew' === $p->purpose ? in_array( $st, array( 'publish', 'lmc_expired', 'lmc_closed' ), true ) : 'lmc_unpaid' === $st;
	}

	/** Grant what was bought: credits and/or vacancy activation, then confirm by email. */
	public static function apply_purchase( $p ) {
		$pkg    = lmc_package( $p->package );
		$job_id = (int) $p->job_id;
		$detail = '';
		// Paid twice for the same vacancy (e.g. two browser tabs)? Never charge for nothing: give a credit instead.
		if ( $job_id && 'new' === $p->purpose && ! self::job_accepts( $p ) ) {
			lmc_log( 'error', 'stripe', "Payment {$p->id} arrived for vacancy $job_id, which was already paid for. Converted to advert credit(s) – consider refunding if the employer asks." );
			$job_id = 0;
			$detail = 'This vacancy had already been paid for, so instead of charging twice we have added this purchase to your account as advert credit, ready for your next vacancy. If you would prefer a refund, just reply to this email. ';
		}
		if ( $pkg['credits'] > 1 ) {
			LMC_Credits::add( $p->user_id, 'standard', $pkg['credits'], 'purchase', $p->id, $pkg['label'] );
			$detail .= $pkg['credits'] . ' standard advert credits have been added to your account. They can be used any time in the next ' . round( lmc_opt( 'pack_validity_days' ) / 30 ) . ' months.';
			if ( $job_id && LMC_Credits::consume( $p->user_id, 'standard' ) ) {
				$res     = LMC_Jobs::activate( $job_id, 'standard', $p->purpose );
				$detail .= ' One credit has been used for "' . get_the_title( $job_id ) . '".';
			}
		} elseif ( $job_id ) {
			$res    = LMC_Jobs::activate( $job_id, $pkg['tier'], $p->purpose );
			$detail = 'This covers "' . get_the_title( $job_id ) . '" (' . $pkg['label'] . ').';
			if ( 'pending' === ( $res ?? '' ) ) {
				$detail .= ' It is now with our team for a quick check before going live.';
			}
		} else {
			LMC_Credits::add( $p->user_id, $pkg['tier'], 1, 'purchase', $p->id, $pkg['label'] );
			$detail .= 'A ' . strtolower( $pkg['label'] ) . ' credit has been added to your account.';
		}
		$user = get_userdata( $p->user_id );
		if ( $user ) {
			lmc_send_email( 'payment_confirmation', $user->user_email, array(
				'first_name'     => $user->first_name ?: 'there',
				'amount'         => lmc_money( $p->total ) . ( $p->vat ? ' (including ' . lmc_money( $p->vat ) . ' VAT)' : '' ),
				'package'        => $pkg['label'],
				'payment_detail' => $detail,
			), $p->id );
		}
		do_action( 'lmc_payment_completed', $p );
	}

	/** Called from the success page: confirm with Stripe directly in case the webhook is slow. */
	public static function confirm_return( $session_id ) {
		if ( ! self::enabled() || ! preg_match( '/^cs_[A-Za-z0-9_]+$/', $session_id ) ) {
			return false;
		}
		$s = self::request( 'GET', 'checkout/sessions/' . rawurlencode( $session_id ) );
		return is_wp_error( $s ) ? false : self::fulfil_session( $s );
	}

	/**
	 * Refund issued in Stripe. A full refund automatically reverses what was bought:
	 * new vacancy → taken down (back to "awaiting payment"); renewal → the added days are removed;
	 * unused credits from that payment are cancelled. Partial refunds are recorded for review only.
	 */
	public static function mark_refunded( $intent, $full = true, $amount_refunded = 0 ) {
		global $wpdb;
		$t = lmc_table( 'payments' );
		if ( ! $intent ) {
			return false;
		}
		$p = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE stripe_intent = %s", $intent ) );
		if ( ! $p || 'refunded' === $p->status ) {
			return false;
		}
		if ( ! $full ) {
			$wpdb->update( $t, array( 'status' => 'part_refunded' ), array( 'id' => $p->id ) );
			lmc_log( 'warning', 'stripe', "Payment {$p->id} partly refunded (" . lmc_money( $amount_refunded ) . '). Nothing was changed automatically.' );
			return true;
		}
		$wpdb->update( $t, array( 'status' => 'refunded' ), array( 'id' => $p->id ) );
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . lmc_table( 'credits' ) . ' SET remaining = 0 WHERE payment_id = %d', $p->id ) );
		$what = 'Any unused credits from this purchase have been cancelled.';
		if ( $p->job_id && 'lmc_job' === get_post_type( $p->job_id ) ) {
			$st = get_post_status( $p->job_id );
			if ( 'renew' === $p->purpose ) {
				$exp = (int) lmc_job_meta( $p->job_id, 'expires' ) - (int) lmc_opt( 'listing_days' ) * DAY_IN_SECONDS;
				update_post_meta( $p->job_id, '_lmc_expires', $exp );
				if ( 'publish' === $st && $exp <= lmc_now() ) {
					LMC_Jobs::expire( $p->job_id );
					update_post_meta( $p->job_id, '_lmc_sent_expired', 1 );
				}
				$what = 'The extra time added by this renewal has been removed from "' . lmc_plain_title( $p->job_id ) . '".';
			} elseif ( in_array( $st, array( 'publish', 'pending' ), true ) && (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE job_id = %d AND status = 'paid' AND id <> %d", $p->job_id, $p->id ) ) === 0 ) {
				wp_update_post( array( 'ID' => $p->job_id, 'post_status' => 'lmc_unpaid' ) );
				delete_post_meta( $p->job_id, '_lmc_paid' );
				$what = '"' . lmc_plain_title( $p->job_id ) . '" has been taken off the job board. It is saved in your dashboard if you want to advertise it again.';
			}
		}
		$user = get_userdata( $p->user_id );
		if ( $user ) {
			lmc_send_email( 'refund_confirmation', $user->user_email, array( 'first_name' => $user->first_name ?: 'there', 'amount' => lmc_money( $p->total ), 'package' => lmc_package( $p->package )['label'] ?? $p->package, 'refund_detail' => $what ), $p->id );
		}
		lmc_log( 'info', 'stripe', "Payment {$p->id} fully refunded and reversed automatically." );
		return true;
	}

	/** Revenue in pence between two timestamps (paid, not refunded, net of VAT). */
	public static function revenue( $from, $to = null ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(net),0) FROM ' . lmc_table( 'payments' ) . " WHERE status = 'paid' AND mode = %s AND paid_at >= %s AND paid_at < %s", self::mode(), lmc_mysql_time( $from ), lmc_mysql_time( $to ?: lmc_now() + 1 ) ) );
	}
}
LMC_Payments::init();
