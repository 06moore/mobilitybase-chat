<?php
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_social_status', function () {
	global $wpdb;
	$id = absint( $_REQUEST['id'] ?? 0 );
	$st = sanitize_key( $_REQUEST['status'] ?? '' );
	if ( 'send' === $st ) {
		$ok = LMC_Social::send( $id );
		lmc_admin_redirect( 'lmc-social', $ok ? 'Sent to your social automation webhook.' : 'Could not send – check the webhook URL in Settings → Automation.', ! $ok );
	}
	if ( in_array( $st, array( 'posted', 'skipped', 'draft' ), true ) ) {
		$wpdb->update( lmc_table( 'social' ), array( 'status' => $st, 'posted_at' => 'posted' === $st ? lmc_mysql_time() : null ), array( 'id' => $id ) );
	}
	lmc_admin_redirect( 'lmc-social', 'Updated.' );
} );
add_action( 'lmc_admin_action_social_edit', function () {
	global $wpdb;
	$wpdb->update( lmc_table( 'social' ), array( 'body' => sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) ) ), array( 'id' => absint( $_POST['id'] ?? 0 ) ) );
	lmc_admin_redirect( 'lmc-social', 'Post text saved.' );
} );

function lmc_admin_social() {
	global $wpdb;
	lmc_admin_header( 'Social posts', 'Drafts are created automatically when a vacancy goes live and the employer has allowed social promotion. Copy them to LinkedIn/Facebook (or send to your Zapier/Make/Buffer webhook), then mark as posted.' );
	$rows = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'social' ) . " ORDER BY CASE status WHEN 'draft' THEN 0 WHEN 'posted' THEN 1 ELSE 2 END, id DESC LIMIT 100" );
	if ( ! $rows ) {
		echo '<p>No drafts yet.</p>';
	}
	foreach ( $rows as $r ) {
		echo '<div class="lmc-card lmc-social"><h3>' . esc_html( ucfirst( $r->platform ) ) . ' – ' . esc_html( $r->job_id ? get_the_title( $r->job_id ) : 'General' ) . ' ' . lmc_admin_badge( $r->status, 'draft' === $r->status ? 'alert' : 'good' ) . '</h3>' . lmc_admin_form_open( 'social_edit' ) . '<input type="hidden" name="id" value="' . (int) $r->id . '"><textarea class="large-text" rows="7" name="body" id="lmc-s-' . (int) $r->id . '">' . esc_textarea( $r->body ) . '</textarea><p>';
		submit_button( 'Save text', 'secondary', 'submit', false );
		echo ' <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById(\'lmc-s-' . (int) $r->id . '\').value);this.textContent=\'Copied ✓\'">Copy</button> ';
		if ( lmc_opt( 'social_webhook_url' ) ) {
			echo '<a class="button" href="' . esc_url( lmc_admin_action_url( 'social_status', array( 'id' => $r->id, 'status' => 'send' ) ) ) . '">Send via webhook</a> ';
		}
		echo '<a class="button button-primary" href="' . esc_url( lmc_admin_action_url( 'social_status', array( 'id' => $r->id, 'status' => 'posted' ) ) ) . '">Mark posted</a> <a class="button" href="' . esc_url( lmc_admin_action_url( 'social_status', array( 'id' => $r->id, 'status' => 'skipped' ) ) ) . '">Skip</a></p></form></div>';
	}
	lmc_admin_footer();
}
