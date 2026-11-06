<?php
/**
 * Candidate job alerts. Optional, free, no account.
 *
 * Data collected: email address + chosen sectors, job categories, location/radius and frequency.
 * Nothing else (no name, no CV, no phone). Double opt-in: nothing is sent until the address is confirmed.
 * Every alert has a one-click unsubscribe, which deletes the subscription entirely.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Alerts {

	public static function init() {
		add_shortcode( 'lmc_job_alerts', array( __CLASS__, 'sc_form' ) );
		add_shortcode( 'lmc_alert_box', array( __CLASS__, 'sc_box' ) );
		add_action( 'lmc_post_lmc_alert_signup', array( __CLASS__, 'do_signup' ) );
		add_action( 'lmc_post_lmc_alert_manage', array( __CLASS__, 'do_manage' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_links' ), 3 );
		add_action( 'lmc_hourly', array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'one_click_unsubscribe' ) );
	}

	public static function table() {
		return lmc_table( 'subscribers' );
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		if ( ! preg_match( '/^[A-Za-z0-9]{20,64}$/', (string) $token ) ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) );
	}

	public static function manage_url( $s ) {
		return add_query_arg( 'manage', $s->token, lmc_page_url( 'alerts' ) );
	}

	public static function unsubscribe_url( $s ) {
		return add_query_arg( array( 'lmc_alert_unsub' => $s->token ), home_url( '/' ) );
	}

	/** Validate + normalise preferences from a request array. */
	public static function prefs( array $p ) {
		$sectors = array_values( array_intersect( array_map( 'sanitize_title', (array) ( $p['alert_sectors'] ?? array() ) ), array_keys( LMC_Forms::term_options( 'lmc_sector' ) ) ) );
		$roles   = array_values( array_intersect( array_map( 'sanitize_title', (array) ( $p['alert_roles'] ?? array() ) ), array_keys( LMC_Forms::term_options( 'lmc_role' ) ) ) );
		$loc     = mb_substr( sanitize_text_field( $p['alert_location'] ?? '' ), 0, 100 );
		$radius  = (int) ( $p['alert_radius'] ?? 25 );
		$radius  = in_array( $radius, array( 10, 25, 50, 75, 100 ), true ) ? $radius : 25;
		$freq    = 'weekly' === ( $p['alert_frequency'] ?? '' ) ? 'weekly' : 'daily';
		$geo     = null;
		if ( $loc && ! LMC_Location::match_term( $loc ) ) {
			$geo = LMC_Location::geocode( $loc );
		}
		return array(
			'sectors'   => implode( ',', $sectors ),
			'roles'     => implode( ',', $roles ),
			'location'  => $loc,
			'lat'       => $geo ? $geo[0] : null,
			'lng'       => $geo ? $geo[1] : null,
			'radius'    => $radius,
			'frequency' => $freq,
		);
	}

	/** Create or refresh a subscription. Always behaves the same whether or not the email exists (no enumeration). */
	public static function subscribe( $email, array $prefs, $source = '' ) {
		global $wpdb;
		$email = LMC_CRM::normalise_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'lmc', 'Please enter a valid email address.' );
		}
		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email = %s', $email ) );
		if ( $existing && 'active' === $existing->status ) {
			// Don't silently change an active subscription: email a link to manage it instead.
			lmc_send_email( 'alert_manage', $email, array( 'manage_url' => self::manage_url( $existing ) ), $existing->id );
			return (int) $existing->id;
		}
		$token = wp_generate_password( 40, false );
		$row   = array_merge( $prefs, array( 'email' => $email, 'status' => 'pending', 'token' => $token, 'source' => mb_substr( $source, 0, 40 ), 'created_at' => lmc_mysql_time() ) );
		if ( $existing ) {
			$wpdb->update( self::table(), $row, array( 'id' => $existing->id ) );
			$id = (int) $existing->id;
		} else {
			$wpdb->insert( self::table(), $row );
			$id = (int) $wpdb->insert_id;
		}
		lmc_send_email( 'alert_confirm', $email, array(
			'confirm_url' => add_query_arg( 'lmc_alert_confirm', $token, home_url( '/' ) ),
			'alert_summary' => self::describe( (object) $row ),
		), $id );
		return $id;
	}

	public static function describe( $s ) {
		$sec  = $s->sectors ? implode( ', ', array_map( function ( $x ) {
			$t = get_term_by( 'slug', $x, 'lmc_sector' );
			return $t ? $t->name : $x;
		}, explode( ',', $s->sectors ) ) ) : 'all sectors';
		$role = $s->roles ? implode( ', ', array_map( function ( $x ) {
			$t = get_term_by( 'slug', $x, 'lmc_role' );
			return $t ? $t->name : $x;
		}, explode( ',', $s->roles ) ) ) : 'all job types';
		$loc  = $s->location ? ( $s->lat ? 'within ' . (int) $s->radius . ' miles of ' : 'in ' ) . $s->location : 'anywhere in the UK';
		return $sec . ' · ' . $role . ' · ' . $loc . ' · ' . ( 'weekly' === $s->frequency ? 'weekly' : 'daily' ) . ' emails';
	}

	public static function handle_links() {
		global $wpdb;
		if ( ! empty( $_GET['lmc_alert_confirm'] ) ) {
			$s = self::get_by_token( sanitize_text_field( wp_unslash( $_GET['lmc_alert_confirm'] ) ) );
			if ( ! $s ) {
				wp_die( 'This confirmation link is invalid or has expired. Please sign up again.', 'Link invalid', array( 'response' => 400 ) );
			}
			if ( 'pending' === $s->status ) {
				$wpdb->update( self::table(), array( 'status' => 'active', 'confirmed_at' => lmc_mysql_time(), 'last_sent' => lmc_mysql_time() ), array( 'id' => $s->id ) );
				LMC_Analytics::bump( 'alert_confirmed', $s->source );
			}
			wp_safe_redirect( add_query_arg( array( 'manage' => $s->token, 'confirmed' => 1 ), lmc_page_url( 'alerts' ) ) );
			exit;
		}
		if ( ! empty( $_GET['lmc_alert_unsub'] ) ) {
			$s = self::get_by_token( sanitize_text_field( wp_unslash( $_GET['lmc_alert_unsub'] ) ) );
			if ( $s ) {
				self::delete( $s );
			}
			wp_die( '<h1>You\'ve been unsubscribed</h1><p>Your job alert and email address have been deleted. You can <a href="' . esc_url( lmc_page_url( 'alerts' ) ) . '">set up a new alert</a> at any time.</p>', 'Unsubscribed', array( 'response' => 200 ) );
		}
	}

	public static function one_click_unsubscribe() {
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && ! empty( $_GET['lmc_alert_unsub'] ) && 'One-Click' === ( $_POST['List-Unsubscribe'] ?? '' ) ) {
			$s = self::get_by_token( sanitize_text_field( wp_unslash( $_GET['lmc_alert_unsub'] ) ) );
			if ( $s ) {
				self::delete( $s );
			}
			status_header( 200 );
			exit;
		}
	}

	public static function delete( $s ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $s->id ) );
		LMC_Analytics::bump( 'alert_unsubscribed' );
	}

	/* ---------- Forms ---------- */

	public static function do_signup() {
		// No nonce check: this anonymous form is shown on cached pages (nonces would go stale). Protection is the
		// honeypot, a per-connection rate limit and double opt-in – nothing is sent until the owner confirms.
		if ( ! empty( $_POST['lmc_website_hp'] ) ) {
			LMC_Forms::$errors['_form'] = 'Please try again.';
			return;
		}
		$p = wp_unslash( $_POST );
		if ( empty( $p['alert_consent'] ) ) {
			LMC_Forms::$errors['alert_consent'] = 'Please tick the box to confirm you want these emails.';
			return;
		}
		$ipkey = 'lmc_al_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
		if ( (int) get_transient( $ipkey ) >= 10 ) {
			LMC_Forms::$errors['_form'] = 'Too many sign-ups from this connection – please try later.';
			return;
		}
		set_transient( $ipkey, (int) get_transient( $ipkey ) + 1, HOUR_IN_SECONDS );
		$r = self::subscribe( $p['alert_email'] ?? '', self::prefs( $p ), sanitize_key( $p['alert_source'] ?? 'form' ) );
		if ( is_wp_error( $r ) ) {
			LMC_Forms::$errors['alert_email'] = $r->get_error_message();
			return;
		}
		LMC_Analytics::bump( 'alert_signup', sanitize_key( $p['alert_source'] ?? 'form' ) );
		wp_safe_redirect( add_query_arg( 'alert', 'check', lmc_page_url( 'alerts' ) ) );
		exit;
	}

	public static function do_manage() {
		global $wpdb;
		$s = self::get_by_token( sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ) );
		if ( ! $s || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ?? '' ) ), 'lmc_alert_manage' ) ) {
			return;
		}
		if ( ! empty( $_POST['delete'] ) ) {
			self::delete( $s );
			wp_safe_redirect( add_query_arg( 'alert', 'deleted', lmc_page_url( 'alerts' ) ) );
			exit;
		}
		$wpdb->update( self::table(), self::prefs( wp_unslash( $_POST ) ), array( 'id' => $s->id ) );
		wp_safe_redirect( add_query_arg( array( 'manage' => $s->token, 'saved' => 1 ), lmc_page_url( 'alerts' ) ) );
		exit;
	}

	private static function pref_fields( $v = array() ) {
		$sel_s = (array) ( $v['sectors'] ?? array() );
		$sel_r = (array) ( $v['roles'] ?? array() );
		ob_start();
		?>
		<div class="lmc-field"><label>Sectors <span class="lmc-muted">(leave blank for all)</span></label><div class="lmc-checks">
			<?php foreach ( LMC_Forms::term_options( 'lmc_sector' ) as $k => $n ) : ?><label><input type="checkbox" name="alert_sectors[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $sel_s, true ) ); ?>> <?php echo esc_html( $n ); ?></label><?php endforeach; ?>
		</div></div>
		<div class="lmc-field"><label>Job categories <span class="lmc-muted">(leave blank for all)</span></label><div class="lmc-checks">
			<?php foreach ( LMC_Forms::term_options( 'lmc_role' ) as $k => $n ) : ?><label><input type="checkbox" name="alert_roles[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $sel_r, true ) ); ?>> <?php echo esc_html( $n ); ?></label><?php endforeach; ?>
		</div></div>
		<div class="lmc-grid-3">
			<div class="lmc-field"><label for="lmc-alert-loc">Location</label><input id="lmc-alert-loc" type="text" name="alert_location" list="lmc-locations" value="<?php echo esc_attr( $v['location'] ?? '' ); ?>" placeholder="County, town or postcode"><span class="lmc-help">Blank = anywhere in the UK.</span></div>
			<div class="lmc-field"><label for="lmc-alert-radius">Distance</label><select id="lmc-alert-radius" name="alert_radius"><?php foreach ( array( 10, 25, 50, 75, 100 ) as $r ) : ?><option value="<?php echo (int) $r; ?>" <?php selected( (int) ( $v['radius'] ?? 25 ), $r ); ?>>Within <?php echo (int) $r; ?> miles</option><?php endforeach; ?></select><span class="lmc-help">Used for towns and postcodes.</span></div>
			<div class="lmc-field"><label for="lmc-alert-freq">How often</label><select id="lmc-alert-freq" name="alert_frequency"><option value="daily" <?php selected( $v['frequency'] ?? 'daily', 'daily' ); ?>>Daily (only when there are new jobs)</option><option value="weekly" <?php selected( $v['frequency'] ?? '', 'weekly' ); ?>>Weekly summary</option></select></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function sc_form() {
		$out = '';
		$st  = sanitize_key( $_GET['alert'] ?? '' );
		if ( 'check' === $st ) {
			return lmc_notice( '<strong>Nearly done – check your inbox.</strong> We\'ve sent you an email with a link to confirm your job alert. If it doesn\'t arrive in a few minutes, check your spam folder.', 'success' );
		}
		if ( 'deleted' === $st ) {
			return lmc_notice( 'Your job alert and email address have been deleted.', 'success' );
		}
		if ( ! empty( $_GET['manage'] ) ) {
			$s = self::get_by_token( sanitize_text_field( wp_unslash( $_GET['manage'] ) ) );
			if ( ! $s ) {
				return lmc_notice( 'This link is no longer valid.' ) . self::sc_form_new();
			}
			if ( ! empty( $_GET['confirmed'] ) ) {
				$out .= lmc_notice( '<strong>Your job alert is active.</strong> We\'ll email you when matching vacancies are published.', 'success' );
			}
			if ( ! empty( $_GET['saved'] ) ) {
				$out .= lmc_notice( 'Your preferences have been saved.', 'success' );
			}
			$v = array( 'sectors' => array_filter( explode( ',', $s->sectors ) ), 'roles' => array_filter( explode( ',', $s->roles ) ), 'location' => $s->location, 'radius' => $s->radius, 'frequency' => $s->frequency );
			return $out . '<form method="post" class="lmc-form">' . wp_nonce_field( 'lmc_alert_manage', '_lmc_nonce', true, false ) . '<input type="hidden" name="lmc_action" value="lmc_alert_manage"><input type="hidden" name="token" value="' . esc_attr( $s->token ) . '"><p>Alert for <strong>' . esc_html( $s->email ) . '</strong></p>' . self::pref_fields( $v ) . '<button class="lmc-btn lmc-btn--primary" type="submit">Save preferences</button> <button class="lmc-btn lmc-btn--ghost" type="submit" name="delete" value="1" onclick="return confirm(\'Delete this alert and your email address?\')">Unsubscribe and delete</button></form>';
		}
		return self::sc_form_new();
	}

	private static function sc_form_new() {
		$f = class_exists( 'LMC_Search' ) ? LMC_Search::filters() : array();
		$v = array( 'sectors' => array_filter( array( $f['sector'] ?? '' ) ), 'roles' => array_filter( array( $f['role'] ?? '' ) ), 'location' => $f['location'] ?? '' );
		return LMC_Forms::errors_summary() . '<form method="post" class="lmc-form">' . wp_nonce_field( 'lmc_alert_signup', '_lmc_nonce', true, false ) . '<input type="hidden" name="lmc_action" value="lmc_alert_signup"><input type="hidden" name="alert_source" value="alerts_page"><div class="lmc-hp" aria-hidden="true"><input type="text" name="lmc_website_hp" tabindex="-1" autocomplete="off"></div>'
			. '<div class="lmc-field"><label for="lmc-alert-email">Email address <span class="req">*</span></label><input id="lmc-alert-email" type="email" name="alert_email" required autocomplete="email" value="' . esc_attr( LMC_Forms::old( 'alert_email' ) ) . '">' . LMC_Forms::err( 'alert_email' ) . '</div>'
			. self::pref_fields( $v )
			. '<div class="lmc-check"><label><input type="checkbox" name="alert_consent" value="1"> Email me new vacancies matching these choices. I can change or cancel my alert at any time with the link in every email.</label>' . LMC_Forms::err( 'alert_consent' ) . '</div>'
			. '<p class="lmc-muted">We only store your email address and these preferences, and only to send your alerts. See our <a href="' . esc_url( lmc_page_url( 'privacy' ) ) . '">Privacy Policy</a>.</p>'
			. '<button class="lmc-btn lmc-btn--primary" type="submit">Create my free job alert</button></form>';
	}

	/** Compact sign-up box for job pages and search results; pre-filled from context. */
	public static function sc_box( $atts = array() ) {
		$a = shortcode_atts( array( 'sector' => '', 'role' => '', 'location' => '', 'source' => 'box' ), $atts );
		$label = 'Get new ' . ( $a['sector'] ? esc_html( strtolower( get_term_by( 'slug', $a['sector'], 'lmc_sector' )->name ?? '' ) ) . ' ' : '' ) . 'jobs' . ( $a['location'] ? ' in ' . esc_html( $a['location'] ) : '' ) . ' by email';
		return '<form method="post" action="' . esc_url( lmc_page_url( 'alerts' ) ) . '" class="lmc-alert-box">' . wp_nonce_field( 'lmc_alert_signup', '_lmc_nonce', true, false ) . '<input type="hidden" name="lmc_action" value="lmc_alert_signup"><input type="hidden" name="alert_source" value="' . esc_attr( $a['source'] ) . '">'
			. ( $a['sector'] ? '<input type="hidden" name="alert_sectors[]" value="' . esc_attr( $a['sector'] ) . '">' : '' )
			. ( $a['role'] ? '<input type="hidden" name="alert_roles[]" value="' . esc_attr( $a['role'] ) . '">' : '' )
			. ( $a['location'] ? '<input type="hidden" name="alert_location" value="' . esc_attr( $a['location'] ) . '">' : '' )
			. '<div class="lmc-hp" aria-hidden="true"><input type="text" name="lmc_website_hp" tabindex="-1" autocomplete="off"></div>'
			. '<p class="lmc-alert-box__title">' . $label . '</p><p class="lmc-muted">Free. Just your email – no account, no CV.</p>'
			. '<label class="screen-reader-text" for="lmc-ab-' . esc_attr( $a['source'] ) . '">Email address</label><input id="lmc-ab-' . esc_attr( $a['source'] ) . '" type="email" name="alert_email" required placeholder="you@example.com" autocomplete="email">'
			. '<label class="lmc-alert-box__consent"><input type="checkbox" name="alert_consent" value="1" required> Email me matching jobs. Unsubscribe any time.</label>'
			. '<button class="lmc-btn lmc-btn--primary lmc-btn--block" type="submit">Create job alert</button>'
			. '<a class="lmc-alert-box__more" href="' . esc_url( lmc_page_url( 'alerts' ) ) . '">More alert options</a></form>';
	}

	/* ---------- Matching & sending ---------- */

	public static function job_matches( $s, $job_id ) {
		if ( LMC_Launch::is_demo_job( $job_id ) ) {
			return false;
		}
		if ( $s->sectors && ! array_intersect( explode( ',', $s->sectors ), wp_list_pluck( lmc_job_terms( $job_id, 'lmc_sector' ), 'slug' ) ) ) {
			return false;
		}
		if ( $s->roles && ! array_intersect( explode( ',', $s->roles ), wp_list_pluck( lmc_job_terms( $job_id, 'lmc_role' ), 'slug' ) ) ) {
			return false;
		}
		if ( $s->location ) {
			$term = LMC_Location::match_term( $s->location );
			if ( $term ) {
				$ids = wp_list_pluck( lmc_job_terms( $job_id, 'lmc_location' ), 'term_id' );
				return in_array( (int) $term->term_id, array_map( 'intval', $ids ), true );
			}
			if ( null !== $s->lat ) {
				$lat = get_post_meta( $job_id, '_lmc_lat', true );
				return '' !== $lat && LMC_Location::miles( array( (float) $s->lat, (float) $s->lng ), array( (float) $lat, (float) get_post_meta( $job_id, '_lmc_lng', true ) ) ) <= (int) $s->radius;
			}
			return false !== stripos( lmc_job_meta( $job_id, 'town' ), $s->location );
		}
		return true;
	}

	/** Is this subscriber due an email now? Daily: once per day from 07:00; weekly: Mondays from 07:00. */
	public static function is_due( $s, $now ) {
		$tz    = new DateTimeZone( 'Europe/London' );
		$local = ( new DateTime( '@' . $now ) )->setTimezone( $tz );
		if ( (int) $local->format( 'G' ) < 7 ) {
			return false;
		}
		$last = $s->last_sent ? strtotime( $s->last_sent . ' UTC' ) : 0;
		$today7 = ( new DateTime( $local->format( 'Y-m-d' ) . ' 07:00', $tz ) )->getTimestamp();
		if ( 'weekly' === $s->frequency ) {
			return 1 === (int) $local->format( 'N' ) && $last < $today7;
		}
		return $last < $today7;
	}

	/** Hourly run. Sends digests to due subscribers who have matching new vacancies. */
	public static function run() {
		global $wpdb;
		if ( ! lmc_opt( 'alerts_enabled' ) ) {
			return 0;
		}
		$now = lmc_now();
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . " WHERE status = 'pending' AND created_at < %s", lmc_mysql_time( $now - 7 * DAY_IN_SECONDS ) ) );
		$subs = $wpdb->get_results( 'SELECT * FROM ' . self::table() . " WHERE status = 'active' ORDER BY (last_sent IS NOT NULL), last_sent ASC" );
		if ( ! $subs ) {
			return 0;
		}
		$oldest = $now;
		foreach ( $subs as $s ) {
			$oldest = min( $oldest, $s->last_sent ? strtotime( $s->last_sent . ' UTC' ) : $now - 7 * DAY_IN_SECONDS );
		}
		$jobs = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => 500, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_lmc_published_at', 'value' => max( $oldest, $now - 8 * DAY_IN_SECONDS ), 'compare' => '>', 'type' => 'NUMERIC' ) ), 'meta_key' => '_lmc_priority', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) );
		$sent = 0;
		$cap  = (int) lmc_opt( 'alerts_max_per_run' );
		foreach ( $subs as $s ) {
			if ( $sent >= $cap ) {
				break;
			}
			if ( ! self::is_due( $s, $now ) ) {
				continue;
			}
			$since   = $s->last_sent ? strtotime( $s->last_sent . ' UTC' ) : strtotime( $s->confirmed_at . ' UTC' );
			$matches = array();
			foreach ( $jobs as $j ) {
				if ( (int) lmc_job_meta( $j, 'published_at' ) > $since && self::job_matches( $s, $j ) ) {
					$matches[] = $j;
				}
			}
			if ( ! $matches ) {
				continue; // Nothing new – no email. Window stays open until something matches.
			}
			if ( self::send_digest( $s, $matches ) ) {
				$wpdb->update( self::table(), array( 'last_sent' => lmc_mysql_time( $now ), 'sent_count' => (int) $s->sent_count + 1 ), array( 'id' => $s->id ) );
				$sent++;
			}
		}
		if ( $sent ) {
			LMC_Analytics::bump( 'alert_emails', '', $sent );
			lmc_log( 'info', 'alerts', "Sent $sent job alert emails" );
		}
		return $sent;
	}

	public static function send_digest( $s, array $job_ids ) {
		$count = count( $job_ids );
		$show  = array_slice( $job_ids, 0, 10 );
		$utm   = array( 'utm_source' => 'jobalert', 'utm_medium' => 'email', 'utm_campaign' => $s->frequency );
		$html  = '<p style="margin:0 0 16px">' . ( 1 === $count ? 'A new vacancy matches your alert:' : $count . ' new vacancies match your alert:' ) . '</p>';
		foreach ( $show as $j ) {
			$html .= '<div style="border:1px solid #E2E7EA;border-radius:8px;padding:14px 16px;margin:0 0 10px">'
				. '<a href="' . esc_url( add_query_arg( $utm, get_permalink( $j ) ) ) . '" style="font-size:16px;font-weight:700;color:#142F40">' . esc_html( lmc_plain_title( $j ) ) . '</a>'
				. ( 'standard' !== lmc_job_tier( $j ) ? ' <span style="background:#A7D943;color:#142F40;font-size:11px;font-weight:700;padding:2px 6px;border-radius:3px">' . esc_html( strtoupper( lmc_job_tier( $j ) ) ) . '</span>' : '' )
				. '<br><span style="color:#55636d">' . esc_html( lmc_job_meta( $j, 'employer_name' ) . ' · ' . lmc_location_text( $j ) ) . '</span><br><strong>' . esc_html( lmc_salary_text( $j ) ) . '</strong></div>';
		}
		if ( $count > 10 ) {
			$html .= '<p><a href="' . esc_url( add_query_arg( $utm, get_post_type_archive_link( 'lmc_job' ) ) ) . '" style="color:#142F40">See all ' . (int) $count . ' new vacancies</a></p>';
		}
		$html .= '<p style="margin:20px 0 0;color:#55636d;font-size:13px">Your alert: ' . esc_html( self::describe( $s ) ) . '. <a href="' . esc_url( self::manage_url( $s ) ) . '" style="color:#142F40">Change it</a>. Know someone who\'d be interested? Forward this email.</p>';
		$unsub  = self::unsubscribe_url( $s );
		$footer = 'You asked for these job alerts on Lift and Mobility Careers. <a href="' . esc_url( $unsub ) . '" style="color:#142F40">Unsubscribe</a> (deletes your alert and email address).';
		$subject = ( 1 === $count ? lmc_plain_title( $show[0] ) . ' – ' . lmc_location_text( $show[0] ) : $count . ' new engineering jobs matching your alert' );
		$ok = wp_mail( $s->email, $subject, lmc_email_render( '', $footer, $html ), array( 'Content-Type: text/html; charset=UTF-8', 'List-Unsubscribe: <' . esc_url_raw( $unsub ) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' ) );
		global $wpdb;
		$wpdb->insert( lmc_table( 'email_log' ), array( 'recipient' => $s->email, 'template' => 'job_alert', 'subject' => $subject, 'object_id' => $s->id, 'status' => $ok ? 'sent' : 'failed', 'created_at' => lmc_mysql_time() ) );
		return $ok;
	}

	public static function counts() {
		global $wpdb;
		$r = $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . self::table() . ' GROUP BY status', OBJECT_K );
		return array( 'active' => (int) ( $r['active']->n ?? 0 ), 'pending' => (int) ( $r['pending']->n ?? 0 ) );
	}
}
LMC_Alerts::init();
