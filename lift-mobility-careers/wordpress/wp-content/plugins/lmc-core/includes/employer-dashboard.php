<?php
/**
 * Employer dashboard: vacancies with status/expiry/stats, edit, renew, close,
 * credits, buying packs, payment history and company profile.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Employer_Dashboard {

	public static function init() {
		add_shortcode( 'lmc_employer_dashboard', array( __CLASS__, 'render' ) );
		add_action( 'lmc_post_lmc_close_job', array( __CLASS__, 'do_close' ) );
		add_action( 'lmc_post_lmc_profile', array( __CLASS__, 'do_profile' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_return' ), 4 );
	}

	/** Stripe return: confirm the session straight away. */
	public static function handle_return() {
		if ( empty( $_GET['lmc_paid'] ) || ! is_user_logged_in() ) {
			return;
		}
		$ok = LMC_Payments::confirm_return( sanitize_text_field( wp_unslash( $_GET['lmc_paid'] ) ) );
		lmc_flash( $ok ? 'Payment received – thank you. A confirmation email is on its way.' : 'Thank you. We\'re waiting for confirmation from our payment provider – this usually takes a few seconds. Refresh this page shortly.', $ok ? 'success' : 'info' );
		wp_safe_redirect( lmc_page_url( 'dashboard' ) );
		exit;
	}

	public static function status_label( $s ) {
		return array(
			'publish'     => array( 'Live', 'live' ),
			'pending'     => array( 'Awaiting approval', 'pending' ),
			'lmc_unpaid'  => array( 'Awaiting payment', 'unpaid' ),
			'lmc_expired' => array( 'Expired', 'expired' ),
			'lmc_closed'  => array( 'Closed', 'expired' ),
			'draft'       => array( 'Draft', 'unpaid' ),
		)[ $s ] ?? array( ucfirst( $s ), 'unpaid' );
	}

	public static function render() {
		if ( ! is_user_logged_in() ) {
			return LMC_Forms::sc_employer_login();
		}
		if ( ! lmc_is_employer() ) {
			return lmc_notice( 'This area is for employer accounts.' );
		}
		$uid = get_current_user_id();
		$out = lmc_render_flash();
		if ( ! empty( $_GET['lmc_cancelled'] ) ) {
			$out .= lmc_notice( 'Payment was cancelled – nothing has been charged. Your vacancy is saved and you can complete payment whenever you\'re ready.', 'info' );
		}
		if ( ! LMC_Forms::is_verified( $uid ) ) {
			return $out . LMC_Forms::verify_prompt();
		}
		if ( ! empty( $_GET['lmc_renew'] ) ) {
			$id = absint( $_GET['lmc_renew'] );
			if ( in_array( get_post_status( $id ), array( 'publish', 'lmc_expired', 'lmc_closed' ), true ) ) {
				return $out . LMC_Forms::package_step( $id, 'renew' ) . '<p><a href="' . esc_url( lmc_page_url( 'dashboard' ) ) . '">← Back to dashboard</a></p>';
			}
			$out .= lmc_notice( 'That vacancy can\'t be renewed right now.', 'info' );
		}
		$tab = sanitize_key( $_GET['tab'] ?? 'vacancies' );
		$tabs = array( 'vacancies' => 'Vacancies', 'credits' => 'Credits & packs', 'payments' => 'Payments', 'profile' => 'Company profile' );
		$out .= '<div class="lmc-dash-head"><div><p class="lmc-eyebrow">' . esc_html( get_user_meta( $uid, 'lmc_company', true ) ) . '</p><h2>Employer dashboard</h2></div><a class="lmc-btn lmc-btn--primary" href="' . esc_url( lmc_page_url( 'submit' ) ) . '">+ Post a vacancy</a></div>';
		$out .= '<nav class="lmc-tabs">';
		foreach ( $tabs as $k => $l ) {
			$out .= '<a class="' . ( $tab === $k ? 'is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'tab', $k, lmc_page_url( 'dashboard' ) ) ) . '">' . esc_html( $l ) . '</a>';
		}
		$out .= '</nav>';
		$method = 'tab_' . ( isset( $tabs[ $tab ] ) ? $tab : 'vacancies' );
		return $out . self::$method( $uid );
	}

	private static function tab_vacancies( $uid ) {
		$jobs = get_posts( array( 'post_type' => 'lmc_job', 'author' => $uid, 'post_status' => array( 'publish', 'pending', 'lmc_unpaid', 'lmc_expired', 'lmc_closed', 'draft' ), 'numberposts' => 200, 'orderby' => 'modified' ) );
		if ( ! $jobs ) {
			return '<div class="lmc-panel lmc-center"><h3>No vacancies yet</h3><p>Post your first vacancy – it takes about five minutes.</p><a class="lmc-btn lmc-btn--primary" href="' . esc_url( lmc_page_url( 'submit' ) ) . '">Post a vacancy</a></div>';
		}
		$out = '<div class="lmc-table-wrap"><table class="lmc-table"><thead><tr><th>Vacancy</th><th>Status</th><th>Advert</th><th>Ends</th><th>Views</th><th>Apply clicks</th><th>Actions</th></tr></thead><tbody>';
		foreach ( $jobs as $j ) {
			$st   = self::status_label( $j->post_status );
			$exp  = (int) lmc_job_meta( $j->ID, 'expires' );
			$s    = LMC_Stats::totals( $j->ID );
			$acts = array();
			if ( 'lmc_unpaid' === $j->post_status ) {
				$acts[] = '<a class="lmc-btn lmc-btn--small lmc-btn--primary" href="' . esc_url( lmc_page_url( 'submit', array( 'step' => 'package', 'job' => $j->ID ) ) ) . '">Complete payment</a>';
			}
			if ( 'publish' === $j->post_status ) {
				$acts[] = '<a href="' . esc_url( get_permalink( $j ) ) . '">View</a>';
			}
			$acts[] = '<a href="' . esc_url( lmc_page_url( 'submit', array( 'job' => $j->ID ) ) ) . '">Edit</a>';
			if ( in_array( $j->post_status, array( 'publish', 'lmc_expired', 'lmc_closed' ), true ) ) {
				$acts[] = '<a href="' . esc_url( lmc_page_url( 'dashboard', array( 'lmc_renew' => $j->ID ) ) ) . '">Renew</a>';
			}
			if ( 'publish' === $j->post_status ) {
				$acts[] = '<form method="post" class="lmc-inline-form" onsubmit="return confirm(\'Close this advert now? It will be removed from the job board.\')">' . LMC_Forms::hidden( 'lmc_close_job' ) . '<input type="hidden" name="job_id" value="' . esc_attr( $j->ID ) . '"><button type="submit" class="lmc-link">Close (filled)</button></form>';
			}
			$soon = ( 'publish' === $j->post_status && $exp && $exp - lmc_now() < 7 * DAY_IN_SECONDS );
			$out .= '<tr><td data-label="Vacancy"><strong>' . esc_html( $j->post_title ) . '</strong><br><span class="lmc-muted">' . esc_html( lmc_location_text( $j->ID ) ) . '</span></td>'
				. '<td data-label="Status"><span class="lmc-badge lmc-badge--' . esc_attr( $st[1] ) . '">' . esc_html( $st[0] ) . '</span></td>'
				. '<td data-label="Advert">' . esc_html( ucfirst( lmc_job_tier( $j->ID ) ) ) . '</td>'
				. '<td data-label="Ends"' . ( $soon ? ' class="lmc-warn"' : '' ) . '>' . ( $exp ? esc_html( lmc_date( $exp ) ) : '–' ) . '</td>'
				. '<td data-label="Views">' . (int) $s['views'] . '</td><td data-label="Apply clicks">' . (int) $s['clicks'] . '</td>'
				. '<td data-label="Actions" class="lmc-actions">' . implode( ' · ', $acts ) . '</td></tr>';
		}
		return $out . '</tbody></table></div><p class="lmc-muted">Adverts close automatically at the end date. We email you 7 days and 2 days before, with a one-click renewal link.</p>';
	}

	private static function tab_credits( $uid ) {
		$bal = LMC_Credits::balance( $uid );
		$out = '<div class="lmc-panel"><h3>Your advert credits</h3>';
		$out .= $bal ? '<ul>' . implode( '', array_map( function ( $t, $n ) {
			return '<li><strong>' . (int) $n . '</strong> ' . esc_html( $t ) . ' ' . _n( 'credit', 'credits', $n ) . '</li>';
		}, array_keys( $bal ), $bal ) ) . '</ul><p class="lmc-muted">Credits are applied when you post a vacancy – choose "Use 1 credit" at the advert step.</p>' : '<p>You have no credits at the moment.</p>';
		$out .= '</div><h3>Buy a multi-advert pack</h3><div class="lmc-pricing lmc-pricing--packs">';
		foreach ( array( 'pack5', 'pack10' ) as $k ) {
			$out .= LMC_Forms::package_card( $k, lmc_package( $k ), 0, 'credits' );
		}
		return $out . '</div>' . ( LMC_Payments::enabled() ? '' : lmc_notice( 'Online payment is not available just yet – please contact us to buy a pack.', 'info' ) );
	}

	private static function tab_payments( $uid ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'payments' ) . " WHERE user_id = %d AND status IN ('paid','refunded') ORDER BY id DESC LIMIT 100", $uid ) );
		if ( ! $rows ) {
			return '<p>No payments yet.</p>';
		}
		$out = '<div class="lmc-table-wrap"><table class="lmc-table"><thead><tr><th>Date</th><th>Item</th><th>Net</th><th>VAT</th><th>Total</th><th>Status</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$out .= '<tr><td>' . esc_html( lmc_date( strtotime( $r->paid_at . ' UTC' ) ) ) . '</td><td>' . esc_html( lmc_package( $r->package )['label'] ?? $r->package ) . ( $r->job_id ? ' – ' . esc_html( get_the_title( $r->job_id ) ) : '' ) . '</td><td>' . lmc_money( $r->net ) . '</td><td>' . lmc_money( $r->vat ) . '</td><td>' . lmc_money( $r->total ) . '</td><td>' . esc_html( ucfirst( $r->status ) ) . '</td></tr>';
		}
		return $out . '</tbody></table></div><p class="lmc-muted">Card receipts are emailed by Stripe at the time of payment.</p>';
	}

	private static function tab_profile( $uid ) {
		$u = get_userdata( $uid );
		LMC_Forms::$old = LMC_Forms::$old ?: array(
			'company'           => get_user_meta( $uid, 'lmc_company', true ),
			'website'           => get_user_meta( $uid, 'lmc_company_website', true ),
			'phone'             => get_user_meta( $uid, 'lmc_phone', true ),
			'first_name'        => $u->first_name,
			'last_name'         => $u->last_name,
			'marketing_opt_out' => get_user_meta( $uid, 'lmc_marketing_opt_out', true ) ? '1' : '',
		);
		ob_start();
		echo LMC_Forms::errors_summary();
		echo '<form method="post" class="lmc-form lmc-form--narrow">' . LMC_Forms::hidden( 'lmc_profile' );
		echo '<div class="lmc-grid-2">';
		LMC_Forms::input( 'first_name', 'First name', array( 'required' => 1 ) );
		LMC_Forms::input( 'last_name', 'Last name', array( 'required' => 1 ) );
		echo '</div>';
		LMC_Forms::input( 'company', 'Company name', array( 'required' => 1 ) );
		LMC_Forms::input( 'website', 'Company website', array( 'type' => 'url' ) );
		LMC_Forms::input( 'phone', 'Phone', array( 'type' => 'tel' ) );
		echo '<div class="lmc-check"><label><input type="checkbox" name="marketing_opt_out" value="1" ' . checked( LMC_Forms::old( 'marketing_opt_out' ), '1', false ) . '> Don\'t send me emails about advertising offers</label></div>';
		echo '<p class="lmc-muted">Account email: ' . esc_html( $u->user_email ) . '. To change your email or close your account, please <a href="' . esc_url( lmc_page_url( 'contact' ) ) . '">contact us</a>.</p>';
		echo '<button class="lmc-btn lmc-btn--primary" type="submit">Save profile</button> <a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Sign out</a></form>';
		return ob_get_clean();
	}

	public static function do_profile() {
		if ( ! is_user_logged_in() || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ?? '' ) ), 'lmc_profile' ) ) {
			return;
		}
		$uid = get_current_user_id();
		$p   = wp_unslash( $_POST );
		if ( empty( trim( $p['company'] ?? '' ) ) ) {
			LMC_Forms::$errors['company'] = 'Company name is required.';
			return;
		}
		wp_update_user( array( 'ID' => $uid, 'first_name' => sanitize_text_field( $p['first_name'] ), 'last_name' => sanitize_text_field( $p['last_name'] ) ) );
		update_user_meta( $uid, 'lmc_company', sanitize_text_field( $p['company'] ) );
		update_user_meta( $uid, 'lmc_company_website', esc_url_raw( $p['website'] ?? '' ) );
		update_user_meta( $uid, 'lmc_phone', sanitize_text_field( $p['phone'] ?? '' ) );
		update_user_meta( $uid, 'lmc_marketing_opt_out', empty( $p['marketing_opt_out'] ) ? 0 : 1 );
		lmc_flash( 'Profile saved.' );
		wp_safe_redirect( add_query_arg( 'tab', 'profile', lmc_page_url( 'dashboard' ) ) );
		exit;
	}

	public static function do_close() {
		if ( ! is_user_logged_in() || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ?? '' ) ), 'lmc_close_job' ) ) {
			return;
		}
		$id = absint( $_POST['job_id'] ?? 0 );
		if ( 'lmc_job' === get_post_type( $id ) && (int) get_post_field( 'post_author', $id ) === get_current_user_id() && 'publish' === get_post_status( $id ) ) {
			LMC_Jobs::close( $id );
			lmc_flash( 'The advert has been closed. Congratulations on filling the role!' );
		}
		wp_safe_redirect( lmc_page_url( 'dashboard' ) );
		exit;
	}
}
LMC_Employer_Dashboard::init();
