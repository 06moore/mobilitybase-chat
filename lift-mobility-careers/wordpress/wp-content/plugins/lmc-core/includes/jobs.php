<?php
/**
 * Vacancy lifecycle: activation after payment/credit, moderation, publishing, expiry and renewal.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Jobs {

	public static function init() {
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_filled_link' ) );
	}

	/** Does this vacancy need operator approval before going live? */
	public static function needs_review( $job_id ) {
		$mode = lmc_opt( 'moderation' );
		if ( 'none' === $mode ) {
			return false;
		}
		if ( 'all' === $mode ) {
			return true;
		}
		// "first": review until the employer has had one vacancy approved.
		$author = (int) get_post_field( 'post_author', $job_id );
		if ( user_can( $author, 'manage_options' ) ) {
			return false;
		}
		return ! get_user_meta( $author, 'lmc_trusted', true );
	}

	/**
	 * Called once a vacancy is paid for (or a credit is used).
	 * $purpose: 'new' or 'renew'.
	 */
	public static function activate( $job_id, $tier, $purpose = 'new' ) {
		$job = get_post( $job_id );
		if ( ! $job || 'lmc_job' !== $job->post_type ) {
			return false;
		}
		update_post_meta( $job_id, '_lmc_tier', $tier );
		update_post_meta( $job_id, '_lmc_priority', lmc_tier_priority( $tier ) );
		update_post_meta( $job_id, '_lmc_paid', 1 );

		if ( 'renew' === $purpose ) {
			return self::renew( $job_id );
		}
		if ( self::needs_review( $job_id ) ) {
			wp_update_post( array( 'ID' => $job_id, 'post_status' => 'pending' ) );
			$vars = lmc_job_email_vars( $job_id );
			lmc_send_admin_email( 'admin_review', $vars, $job_id );
			lmc_send_job_email( 'vacancy_submitted', $job_id, array( 'status_line' => 'Your advert is paid for and is now with our team for a quick check. We aim to review vacancies within one working day, and you\'ll get an email as soon as it goes live.' ) );
			return 'pending';
		}
		self::publish( $job_id );
		return 'publish';
	}

	public static function publish( $job_id ) {
		wp_update_post( array( 'ID' => $job_id, 'post_status' => 'publish' ) );
		return true;
	}

	/** Listing end: the earlier of the employer's closing date and N days after publishing. */
	public static function compute_expiry( $job_id, $from ) {
		$exp     = $from + (int) lmc_opt( 'listing_days' ) * DAY_IN_SECONDS;
		$closing = lmc_job_meta( $job_id, 'closing_date' );
		if ( $closing ) {
			$c = strtotime( $closing . ' 23:59:59 Europe/London' );
			if ( $c && $c > $from && $c < $exp ) {
				$exp = $c;
			}
		}
		return $exp;
	}

	/** Everything that must happen whenever a vacancy goes live, however it got there. */
	public static function on_transition( $new, $old, $post ) {
		if ( 'lmc_job' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		$id  = $post->ID;
		$now = lmc_now();
		if ( ! lmc_job_meta( $id, 'tier' ) ) {
			update_post_meta( $id, '_lmc_tier', 'standard' );
			update_post_meta( $id, '_lmc_priority', 1 );
		}
		$exp = (int) lmc_job_meta( $id, 'expires' );
		if ( ! $exp || $exp <= $now ) {
			update_post_meta( $id, '_lmc_expires', self::compute_expiry( $id, $now ) );
		}
		if ( ! lmc_job_meta( $id, 'published_at' ) ) {
			update_post_meta( $id, '_lmc_published_at', $now );
		}
		// The employer is now trusted for "first vacancy" moderation.
		update_user_meta( $post->post_author, 'lmc_trusted', 1 );
		delete_post_meta( $id, '_lmc_sent_expiry_7' );
		delete_post_meta( $id, '_lmc_sent_expiry_2' );

		LMC_Location::geocode_job( $id );

		if ( ! lmc_job_meta( $id, 'notified_published' ) && ! lmc_job_meta( $id, 'renewing' ) ) {
			update_post_meta( $id, '_lmc_notified_published', 1 );
			if ( ! user_can( $post->post_author, 'manage_options' ) ) {
				lmc_send_job_email( 'vacancy_published', $id );
			}
			do_action( 'lmc_job_published', $id );
		}
		delete_post_meta( $id, '_lmc_renewing' );
	}

	public static function renew( $job_id ) {
		$now    = lmc_now();
		$status = get_post_status( $job_id );
		$exp    = (int) lmc_job_meta( $job_id, 'expires' );
		$base   = ( 'publish' === $status && $exp > $now ) ? $exp : $now;
		$new    = $base + (int) lmc_opt( 'listing_days' ) * DAY_IN_SECONDS;
		// A closing date in the past would immediately re-expire; renewal clears it.
		$closing = lmc_job_meta( $job_id, 'closing_date' );
		if ( $closing && strtotime( $closing . ' 23:59:59 Europe/London' ) < $new ) {
			delete_post_meta( $job_id, '_lmc_closing_date' );
		}
		update_post_meta( $job_id, '_lmc_expires', $new );
		foreach ( array( 'sent_expiry_7', 'sent_expiry_2', 'sent_expired', 'sent_filled', 'expired_at' ) as $k ) {
			delete_post_meta( $job_id, '_lmc_' . $k );
		}
		update_post_meta( $job_id, '_lmc_renewals', (int) lmc_job_meta( $job_id, 'renewals' ) + 1 );
		if ( 'publish' !== $status ) {
			update_post_meta( $job_id, '_lmc_renewing', 1 );
			wp_update_post( array( 'ID' => $job_id, 'post_status' => 'publish' ) );
		}
		lmc_send_job_email( 'renewal_confirmation', $job_id );
		do_action( 'lmc_job_renewed', $job_id );
		return 'renewed';
	}

	public static function expire( $job_id ) {
		wp_update_post( array( 'ID' => $job_id, 'post_status' => 'lmc_expired' ) );
		update_post_meta( $job_id, '_lmc_expired_at', lmc_now() );
	}

	/** Employer closes a vacancy early (e.g. filled). */
	public static function close( $job_id ) {
		wp_update_post( array( 'ID' => $job_id, 'post_status' => 'lmc_closed' ) );
		update_post_meta( $job_id, '_lmc_expired_at', lmc_now() );
		update_post_meta( $job_id, '_lmc_sent_filled', 1 ); // They told us; don't ask.
		update_post_meta( $job_id, '_lmc_outcome', 'filled' );
	}

	/**
	 * The hourly lifecycle run. Idempotent: every email is guarded by a meta flag,
	 * so running it more often (or after downtime) never double-sends.
	 */
	public static function run_lifecycle() {
		$now = lmc_now();
		$r1  = (int) lmc_opt( 'reminder_days_1' ) * DAY_IN_SECONDS;
		$r2  = (int) lmc_opt( 'reminder_days_2' ) * DAY_IN_SECONDS;
		$log = array( 'expired' => 0, 'rem7' => 0, 'rem2' => 0, 'filled' => 0, 'repeat' => 0 );

		$live = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true ) );
		foreach ( $live as $id ) {
			$exp = (int) lmc_job_meta( $id, 'expires' );
			if ( ! $exp ) {
				update_post_meta( $id, '_lmc_expires', self::compute_expiry( $id, $now ) );
				continue;
			}
			$left = $exp - $now;
			if ( $left <= 0 ) {
				self::expire( $id );
				if ( ! lmc_job_meta( $id, 'sent_expired' ) ) {
					update_post_meta( $id, '_lmc_sent_expired', 1 );
					lmc_send_job_email( 'vacancy_expired', $id );
				}
				$log['expired']++;
			} elseif ( $left <= $r2 && ! lmc_job_meta( $id, 'sent_expiry_2' ) ) {
				update_post_meta( $id, '_lmc_sent_expiry_2', 1 );
				update_post_meta( $id, '_lmc_sent_expiry_7', 1 );
				lmc_send_job_email( 'expiry_2', $id );
				$log['rem2']++;
			} elseif ( $left <= $r1 && $left > $r2 && ! lmc_job_meta( $id, 'sent_expiry_7' ) ) {
				update_post_meta( $id, '_lmc_sent_expiry_7', 1 );
				lmc_send_job_email( 'expiry_7', $id );
				$log['rem7']++;
			}
		}

		// Post-expiry follow-ups.
		$expired = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => array( 'lmc_expired', 'lmc_closed' ), 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_query' => array( array( 'key' => '_lmc_expired_at', 'value' => $now - 90 * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC' ) ) ) );
		foreach ( $expired as $id ) {
			$at = (int) lmc_job_meta( $id, 'expired_at' );
			if ( ! $at ) {
				continue;
			}
			if ( ! lmc_job_meta( $id, 'sent_filled' ) && $now - $at >= (int) lmc_opt( 'filled_followup_days' ) * DAY_IN_SECONDS ) {
				update_post_meta( $id, '_lmc_sent_filled', 1 );
				lmc_send_job_email( 'filled_followup', $id );
				$log['filled']++;
			}
			$author = (int) get_post_field( 'post_author', $id );
			if ( ! lmc_job_meta( $id, 'sent_repeat' ) && $now - $at >= (int) lmc_opt( 'repeat_followup_days' ) * DAY_IN_SECONDS ) {
				update_post_meta( $id, '_lmc_sent_repeat', 1 );
				// Only if they have nothing live and we haven't nudged them in the last 60 days.
				$last_nudge = (int) get_user_meta( $author, 'lmc_last_repeat_nudge', true );
				if ( ! self::employer_live_count( $author ) && $now - $last_nudge > 60 * DAY_IN_SECONDS ) {
					update_user_meta( $author, 'lmc_last_repeat_nudge', $now );
					lmc_send_job_email( 'repeat_followup', $id );
					$log['repeat']++;
				}
			}
		}
		lmc_heartbeat( 'lifecycle' );
		if ( array_sum( $log ) ) {
			lmc_log( 'info', 'lifecycle', $log );
		}
		return $log;
	}

	public static function employer_live_count( $user_id ) {
		return count( get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'author' => $user_id, 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true ) ) );
	}

	/** One-click "did you fill it?" answers from the follow-up email (signed links). */
	public static function handle_filled_link() {
		if ( empty( $_GET['lmc_filled'] ) ) {
			return;
		}
		$id = absint( $_GET['lmc_filled'] );
		if ( ! lmc_verify_sign( 'filled' . $id, sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ) ) {
			wp_die( 'This link is invalid or has expired.', 'Link invalid', array( 'response' => 403 ) );
		}
		$answer = ( 'yes' === ( $_GET['a'] ?? '' ) ) ? 'filled' : 'not_filled';
		update_post_meta( $id, '_lmc_outcome', $answer );
		$msg = 'filled' === $answer
			? '<h1>Thank you!</h1><p>Great to hear the role has been filled. We hope to help with your next vacancy.</p>'
			: '<h1>Thanks for letting us know</h1><p>If you are still recruiting, you can <a href="' . esc_url( lmc_page_url( 'dashboard', array( 'lmc_renew' => $id ) ) ) . '">renew the advert</a> from your dashboard.</p>';
		wp_die( $msg, 'Thank you', array( 'response' => 200 ) );
	}
}
LMC_Jobs::init();
