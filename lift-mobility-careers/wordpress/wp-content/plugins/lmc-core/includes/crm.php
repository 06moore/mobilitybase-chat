<?php
/**
 * Employer acquisition CRM and compliant outreach.
 *
 * PECR guard rails (enforced in code, not just documented):
 *  - Emails are composed and sent ONE AT A TIME by the operator. There is no bulk send.
 *  - Sole traders, ordinary partnerships and unknown entity types are "individual subscribers"
 *    under PECR: marketing email is blocked unless consent has been recorded.
 *  - Every outreach email identifies the sender, includes a postal address and an unsubscribe link.
 *  - Unsubscribes go on a permanent suppression list that every marketing send checks first.
 *  - A daily cap prevents accidental high-volume sending.
 */
defined( 'ABSPATH' ) || exit;

class LMC_CRM {

	const STATUSES = array(
		'researching'          => 'Researching',
		'ready'                => 'Ready to contact',
		'contacted'            => 'Contacted',
		'replied'              => 'Replied',
		'free_listing_offered' => 'Free listing offered',
		'free_listing_active'  => 'Free listing active',
		'customer'             => 'Paying customer',
		'not_interested'       => 'Not interested',
		'do_not_contact'       => 'Do not contact',
	);

	const PERMISSIONS = array(
		'none'               => 'No permission',
		'vacancy_authorised' => 'May feature vacancies',
		'logo_authorised'    => 'Vacancies + logo',
		'declined'           => 'Declined',
	);

	/** Entity types where B2B "corporate subscriber" rules apply under PECR. */
	const CORPORATE = array( 'limited_company', 'plc', 'llp', 'public_body', 'scottish_partnership' );

	public static function init() {
		add_shortcode( 'lmc_email_preferences', array( __CLASS__, 'sc_preferences' ) );
		add_action( 'init', array( __CLASS__, 'one_click_unsubscribe' ) );
	}

	public static function entity_types() {
		return array(
			'unknown'              => 'Unknown – check Companies House',
			'limited_company'      => 'Limited company',
			'plc'                  => 'PLC',
			'llp'                  => 'LLP',
			'scottish_partnership' => 'Scottish partnership',
			'public_body'          => 'Public body / charity company',
			'partnership'          => 'Partnership (England, Wales, NI)',
			'sole_trader'          => 'Sole trader',
		);
	}

	public static function is_corporate( $entity ) {
		return in_array( $entity, self::CORPORATE, true );
	}

	public static function normalise_email( $e ) {
		return strtolower( trim( (string) $e ) );
	}

	public static function is_suppressed( $email ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . lmc_table( 'suppression' ) . ' WHERE email = %s', self::normalise_email( $email ) ) );
	}

	public static function suppress( $email, $reason = 'unsubscribed' ) {
		global $wpdb;
		$email = self::normalise_email( $email );
		if ( ! is_email( $email ) || self::is_suppressed( $email ) ) {
			return;
		}
		$wpdb->insert( lmc_table( 'suppression' ), array( 'email' => $email, 'reason' => $reason, 'created_at' => lmc_mysql_time() ) );
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . lmc_table( 'prospects' ) . " SET status = 'do_not_contact', updated_at = %s WHERE LOWER(contact_email) = %s", lmc_mysql_time(), $email ) );
		$u = get_user_by( 'email', $email );
		if ( $u ) {
			update_user_meta( $u->ID, 'lmc_marketing_opt_out', 1 );
		}
		lmc_log( 'info', 'crm', "Suppressed $email ($reason)" );
	}

	public static function unsubscribe_url( $email ) {
		$email = self::normalise_email( $email );
		return add_query_arg( array( 'e' => rawurlencode( $email ), 's' => lmc_sign( 'unsub' . $email ) ), lmc_page_url( 'preferences' ) );
	}

	/** RFC 8058 one-click unsubscribe (mail clients POST to the List-Unsubscribe URL). */
	public static function one_click_unsubscribe() {
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_GET['e'], $_GET['s'] ) && 'One-Click' === ( $_POST['List-Unsubscribe'] ?? '' ) ) {
			$email = self::normalise_email( rawurldecode( wp_unslash( $_GET['e'] ) ) );
			if ( lmc_verify_sign( 'unsub' . $email, sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) ) {
				self::suppress( $email, 'one-click unsubscribe' );
			}
			status_header( 200 );
			exit;
		}
	}

	public static function sc_preferences() {
		$email = self::normalise_email( rawurldecode( wp_unslash( $_GET['e'] ?? '' ) ) );
		$sig   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		if ( ! $email || ! lmc_verify_sign( 'unsub' . $email, $sig ) ) {
			return '<p>To change your email preferences, use the link at the bottom of any email from us, or <a href="' . esc_url( lmc_page_url( 'contact' ) ) . '">contact us</a>.</p>';
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'lmc_unsub' ) ) {
			self::suppress( $email );
		}
		if ( self::is_suppressed( $email ) ) {
			return lmc_notice( '<strong>' . esc_html( $email ) . '</strong> has been unsubscribed. We won\'t send you marketing emails again. If you have an employer account, you\'ll still receive essential emails about your own adverts.', 'success' );
		}
		return '<form method="post" class="lmc-form lmc-form--narrow">' . wp_nonce_field( 'lmc_unsub', '_wpnonce', true, false ) . '<p>Unsubscribe <strong>' . esc_html( $email ) . '</strong> from all marketing emails from Lift and Mobility Careers?</p><button class="lmc-btn lmc-btn--primary" type="submit">Unsubscribe</button></form>';
	}

	/* ---------- Prospects ---------- */

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'prospects' ) . ' WHERE id = %d', $id ) );
	}

	public static function save( array $d, $id = 0 ) {
		global $wpdb;
		$cols  = array( 'company', 'entity_type', 'company_number', 'sector', 'website', 'careers_url', 'contact_name', 'contact_role', 'contact_email', 'phone', 'town', 'region', 'status', 'permission_status', 'consent_marketing', 'consent_note', 'follow_up', 'notes', 'source' );
		$row   = array();
		foreach ( $cols as $c ) {
			if ( array_key_exists( $c, $d ) ) {
				$v = $d[ $c ];
				if ( in_array( $c, array( 'website', 'careers_url' ), true ) ) {
					$v = esc_url_raw( $v );
				} elseif ( 'contact_email' === $c ) {
					$v = sanitize_email( $v );
				} elseif ( 'notes' === $c ) {
					$v = sanitize_textarea_field( $v );
				} elseif ( 'consent_marketing' === $c ) {
					$v = $v ? 1 : 0;
				} elseif ( 'follow_up' === $c ) {
					$v = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : null;
				} else {
					$v = sanitize_text_field( $v );
				}
				$row[ $c ] = $v;
			}
		}
		if ( isset( $row['entity_type'] ) && ! isset( self::entity_types()[ $row['entity_type'] ] ) ) {
			$row['entity_type'] = 'unknown';
		}
		$row['updated_at'] = lmc_mysql_time();
		if ( $id ) {
			$wpdb->update( lmc_table( 'prospects' ), $row, array( 'id' => $id ) );
		} else {
			$row['created_at'] = lmc_mysql_time();
			$wpdb->insert( lmc_table( 'prospects' ), $row );
			$id = (int) $wpdb->insert_id;
		}
		self::sync_source( $id );
		return $id;
	}

	/** Keep a discovery source in step with the prospect's careers page URL. */
	public static function sync_source( $prospect_id ) {
		global $wpdb;
		$p = self::get( $prospect_id );
		if ( ! $p || ! $p->careers_url ) {
			return;
		}
		$st  = lmc_table( 'sources' );
		$sid = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $st WHERE prospect_id = %d", $prospect_id ) );
		if ( $sid ) {
			$wpdb->update( $st, array( 'company' => $p->company, 'careers_url' => $p->careers_url ), array( 'id' => $sid ) );
		} else {
			$wpdb->insert( $st, array( 'prospect_id' => $prospect_id, 'company' => $p->company, 'careers_url' => $p->careers_url, 'created_at' => lmc_mysql_time() ) );
		}
	}

	/** When an employer registers, link them to a matching prospect record. */
	public static function link_registration( $uid ) {
		global $wpdb;
		$u = get_userdata( $uid );
		$t = lmc_table( 'prospects' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE LOWER(contact_email) = %s OR LOWER(company) = %s LIMIT 1", self::normalise_email( $u->user_email ), strtolower( get_user_meta( $uid, 'lmc_company', true ) ) ) );
		if ( $id ) {
			$wpdb->update( $t, array( 'employer_user_id' => $uid, 'updated_at' => lmc_mysql_time() ), array( 'id' => $id ) );
		} else {
			$id = self::save( array(
				'company'       => get_user_meta( $uid, 'lmc_company', true ),
				'entity_type'   => get_user_meta( $uid, 'lmc_entity_type', true ) ?: 'unknown',
				'website'       => get_user_meta( $uid, 'lmc_company_website', true ),
				'contact_name'  => $u->display_name,
				'contact_email' => $u->user_email,
				'status'        => 'replied',
				'source'        => 'inbound',
				'notes'         => 'Registered an employer account.',
			) );
			$wpdb->update( $t, array( 'employer_user_id' => $uid ), array( 'id' => $id ) );
		}
	}

	/**
	 * Can we send this prospect a marketing email right now? Returns true or a reason string.
	 */
	public static function can_email( $p ) {
		$lmc_id = lmc_identity();
		if ( ! $lmc_id['address'] || in_array( 'owner name', $lmc_id['missing'], true ) || in_array( 'company name', $lmc_id['missing'], true ) ) {
			return 'Add your business details (owner or company name and trading address) in Settings first – every marketing email must identify the sender with a valid address.';
		}
		if ( ! is_email( $p->contact_email ) ) {
			return 'No valid contact email recorded.';
		}
		if ( self::is_suppressed( $p->contact_email ) || 'do_not_contact' === $p->status ) {
			return 'This contact has unsubscribed or is marked "do not contact".';
		}
		if ( ! self::is_corporate( $p->entity_type ) && ! $p->consent_marketing ) {
			return 'Blocked by PECR: this is a sole trader, partnership or unverified business. You need their prior consent before sending marketing email (record it on the prospect). Phone or LinkedIn contact may be more appropriate – see the outreach guide.';
		}
		if ( self::sent_today() >= (int) lmc_opt( 'outreach_daily_cap' ) ) {
			return 'Daily outreach limit reached (' . (int) lmc_opt( 'outreach_daily_cap' ) . '). This protects your sending reputation – try again tomorrow.';
		}
		if ( $p->contact_count >= 2 && ! in_array( $p->status, array( 'replied', 'free_listing_active', 'customer' ), true ) ) {
			return 'Already contacted twice without a reply. Our policy is no more than one follow-up.';
		}
		return true;
	}

	public static function sent_today() {
		global $wpdb;
		$start = strtotime( wp_date( 'Y-m-d 00:00:00', lmc_now() ) . ' Europe/London' );
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'email_log' ) . " WHERE template LIKE 'outreach_%%' AND status = 'sent' AND created_at >= %s", lmc_mysql_time( $start ) ) );
	}

	public static function outreach_vars( $p, $opp = null ) {
		$sender = wp_get_current_user();
		return array(
			'company'               => $p->company,
			'contact_greeting'      => $p->contact_name ? strtok( $p->contact_name, ' ' ) : 'there',
			'sender_name'           => ( $sender && $sender->exists() ) ? ( get_user_meta( $sender->ID, 'lmc_sender_name', true ) ?: lmc_opt( 'from_name' ) ) : lmc_opt( 'from_name' ),
			'opportunity_title'     => $opp ? $opp->title : 'engineering',
			'opportunity_location'  => ( $opp && $opp->location ) ? ' in ' . $opp->location : '',
			'original_subject'      => 'A free 30-day vacancy advert for ' . $p->company,
		);
	}

	/** Send one outreach email to one prospect, after all checks. Returns true or error string. */
	public static function send_outreach( $prospect_id, $template, $opp_id = 0 ) {
		global $wpdb;
		$p = self::get( $prospect_id );
		if ( ! $p ) {
			return 'Prospect not found.';
		}
		if ( ! in_array( $template, array( 'outreach_free_listing', 'outreach_vacancy_feature', 'outreach_followup' ), true ) ) {
			return 'Unknown template.';
		}
		$ok = self::can_email( $p );
		if ( true !== $ok ) {
			return $ok;
		}
		$opp  = $opp_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . ' WHERE id = %d', $opp_id ) ) : null;
		$sent = lmc_send_email( $template, $p->contact_email, self::outreach_vars( $p, $opp ), $p->id, array( 'reason' => 'we believe your organisation recruits engineers in the lift and mobility sector and this address is published as a business contact' ) );
		if ( ! $sent ) {
			return 'Sending failed – see Logs.';
		}
		$status = 'outreach_followup' === $template ? $p->status : ( 'outreach_free_listing' === $template ? 'free_listing_offered' : 'contacted' );
		$wpdb->update( lmc_table( 'prospects' ), array(
			'status'         => in_array( $p->status, array( 'replied', 'free_listing_active', 'customer' ), true ) ? $p->status : $status,
			'last_contacted' => lmc_mysql_time(),
			'contact_count'  => (int) $p->contact_count + 1,
			'follow_up'      => wp_date( 'Y-m-d', lmc_now() + (int) lmc_opt( 'outreach_followup_days' ) * DAY_IN_SECONDS ),
			'updated_at'     => lmc_mysql_time(),
		), array( 'id' => $p->id ) );
		if ( $opp ) {
			$wpdb->update( lmc_table( 'opportunities' ), array( 'status' => 'contacting' ), array( 'id' => $opp->id ) );
		}
		return true;
	}

	public static function followups_due_count() {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'prospects' ) . " WHERE follow_up IS NOT NULL AND follow_up <= %s AND status NOT IN ('do_not_contact','not_interested','customer')", wp_date( 'Y-m-d', lmc_now() ) ) );
	}

	/** Hourly: mark employers who've paid as customers. */
	public static function refresh_followups() {
		global $wpdb;
		// Retention (Privacy Policy): researched contacts with no relationship after 24 months are deleted.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . lmc_table( 'prospects' ) . " WHERE employer_user_id = 0 AND status IN ('researching','ready','contacted','not_interested','free_listing_offered') AND updated_at < %s", lmc_mysql_time( lmc_now() - 730 * DAY_IN_SECONDS ) ) );
		$wpdb->query( 'UPDATE ' . lmc_table( 'prospects' ) . " SET status = 'customer' WHERE employer_user_id > 0 AND employer_user_id IN (SELECT user_id FROM " . lmc_table( 'payments' ) . " WHERE status = 'paid') AND status <> 'do_not_contact'" );
	}

	/** CSV import: header row with any of the prospect column names. */
	public static function import_csv( $path ) {
		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			return 0;
		}
		$head = array_map( function ( $h ) {
			return sanitize_key( str_replace( ' ', '_', strtolower( trim( $h ) ) ) );
		}, (array) fgetcsv( $fh ) );
		$n = 0;
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( count( $row ) !== count( $head ) ) {
				continue;
			}
			$d = array_combine( $head, $row );
			if ( empty( $d['company'] ) ) {
				continue;
			}
			$d['source'] = $d['source'] ?? 'research';
			self::save( $d );
			$n++;
		}
		fclose( $fh );
		return $n;
	}
}
LMC_CRM::init();
