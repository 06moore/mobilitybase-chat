<?php
/**
 * Employer account closure (self-service, password-confirmed).
 * Removes the employer's vacancies, credits, account and CRM record, suppresses the address from
 * marketing, and keeps only payment records (with a customer name/email snapshot) for tax purposes.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Account {

	public static function init() {
		add_action( 'lmc_post_lmc_close_account', array( __CLASS__, 'do_close' ) );
	}

	public static function form() {
		return '<details class="lmc-danger"><summary>Close my account</summary><p>This permanently deletes your employer account and removes all of your vacancies from the job board. Unused credits are lost. Payment records are kept for tax purposes. This cannot be undone.</p>'
			. '<form method="post" class="lmc-form lmc-form--narrow">' . LMC_Forms::hidden( 'lmc_close_account' )
			. '<div class="lmc-field"><label for="lmc-close-pass">Enter your password to confirm</label><input id="lmc-close-pass" type="password" name="close_password" autocomplete="current-password" required></div>'
			. '<button class="lmc-btn lmc-btn--ghost" type="submit">Permanently close my account</button></form></details>';
	}

	public static function close( $uid ) {
		global $wpdb;
		$user = get_userdata( $uid );
		if ( ! $user || ! in_array( 'lmc_employer', (array) $user->roles, true ) ) {
			return false;
		}
		$company = get_user_meta( $uid, 'lmc_company', true );
		lmc_send_email( 'account_closed', $user->user_email, array( 'first_name' => $user->first_name ?: 'there', 'company' => $company ), $uid );
		foreach ( get_posts( array( 'post_type' => 'lmc_job', 'author' => $uid, 'post_status' => 'any', 'numberposts' => -1 ) ) as $j ) {
			if ( 'publish' === $j->post_status ) {
				LMC_Google::notify( get_permalink( $j ), 'URL_DELETED' );
			}
			$wpdb->delete( lmc_table( 'social' ), array( 'job_id' => $j->ID ) );
			wp_delete_post( $j->ID, true );
		}
		$wpdb->delete( lmc_table( 'credits' ), array( 'user_id' => $uid ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . lmc_table( 'prospects' ) . ' WHERE employer_user_id = %d OR LOWER(contact_email) = %s', $uid, LMC_CRM::normalise_email( $user->user_email ) ) );
		LMC_CRM::suppress( $user->user_email, 'account closed' );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $uid );
		lmc_log( 'info', 'account', "Employer account $uid ($company) closed by the employer." );
		return true;
	}

	public static function do_close() {
		if ( ! is_user_logged_in() || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ?? '' ) ), 'lmc_close_account' ) ) {
			return;
		}
		$u = wp_get_current_user();
		if ( ! in_array( 'lmc_employer', (array) $u->roles, true ) ) {
			return;
		}
		if ( ! wp_check_password( (string) wp_unslash( $_POST['close_password'] ?? '' ), $u->user_pass, $u->ID ) ) {
			lmc_flash( 'That password wasn\'t right, so your account has not been closed.', 'error' );
			wp_safe_redirect( lmc_page_url( 'dashboard', array( 'tab' => 'profile' ) ) );
			exit;
		}
		self::close( $u->ID );
		wp_logout();
		wp_safe_redirect( add_query_arg( 'account_closed', 1, home_url( '/' ) ) );
		exit;
	}
}
LMC_Account::init();
