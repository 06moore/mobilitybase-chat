<?php
/** Payments ledger. Refunds are issued in Stripe; the webhook marks them here automatically. */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_mark_paid', function () {
	global $wpdb;
	// Manual (offline) payment, e.g. bank transfer before Stripe is live. Records the sale and fulfils it.
	$job = absint( $_POST['job_id'] ?? 0 );
	$pkg = sanitize_key( $_POST['package'] ?? 'standard' );
	$uid = (int) get_post_field( 'post_author', $job );
	if ( ! $job || ! lmc_package( $pkg ) ) {
		lmc_admin_redirect( 'lmc-payments', 'Choose a vacancy and package.', true );
	}
	$net = (int) lmc_package( $pkg )['price'];
	$vat = lmc_vat_amount( $net );
	$wpdb->insert( lmc_table( 'payments' ), array( 'user_id' => $uid, 'job_id' => $job, 'package' => $pkg, 'purpose' => 'publish' === get_post_status( $job ) || 'lmc_expired' === get_post_status( $job ) ? 'renew' : 'new', 'net' => $net, 'vat' => $vat, 'total' => $net + $vat, 'method' => 'manual', 'status' => 'paid', 'mode' => LMC_Payments::mode(), 'created_at' => lmc_mysql_time(), 'paid_at' => lmc_mysql_time() ) );
	$p = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'payments' ) . ' WHERE id = %d', $wpdb->insert_id ) );
	LMC_Payments::apply_purchase( $p );
	lmc_admin_redirect( 'lmc-payments', 'Manual payment recorded and vacancy activated.' );
} );

function lmc_admin_payments() {
	global $wpdb;
	lmc_admin_header( 'Payments', 'Card payments are processed by Stripe Checkout. Issue refunds from the Stripe dashboard – they are reflected here automatically.' );
	$rows = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'payments' ) . ' ORDER BY id DESC LIMIT 300' );
	$now  = lmc_now();
	echo '<div class="lmc-tiles">';
	foreach ( array( 'Last 7 days' => 7, 'Last 30 days' => 30, 'Last 365 days' => 365 ) as $l => $d ) {
		echo '<div class="lmc-tile"><span class="lmc-tile__label">' . esc_html( $l ) . ' (net)</span><span class="lmc-tile__value">' . esc_html( lmc_money( LMC_Payments::revenue( $now - $d * DAY_IN_SECONDS, $now ) ) ) . '</span></div>';
	}
	echo '</div><table class="widefat striped"><thead><tr><th>#</th><th>Date</th><th>Employer</th><th>Item</th><th>Net</th><th>VAT</th><th>Total</th><th>Method</th><th>Status</th><th>Stripe</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$u    = get_userdata( $r->user_id );
		$kind = array( 'paid' => 'good', 'pending' => 'alert', 'failed' => 'bad', 'refunded' => 'bad' )[ $r->status ] ?? '';
		$link = $r->stripe_intent ? 'https://dashboard.stripe.com/' . ( 'test' === $r->mode ? 'test/' : '' ) . 'payments/' . $r->stripe_intent : '';
		echo '<tr><td>' . (int) $r->id . '</td><td>' . esc_html( $r->paid_at ?: $r->created_at ) . '</td><td>' . esc_html( $u ? get_user_meta( $u->ID, 'lmc_company', true ) . ' (' . $u->user_email . ')' : '?' ) . '</td><td>' . esc_html( lmc_package( $r->package )['label'] ?? $r->package ) . ( $r->job_id ? '<br><a href="' . esc_url( get_edit_post_link( $r->job_id ) ) . '">' . esc_html( get_the_title( $r->job_id ) ) . '</a>' : '' ) . ' <span class="description">' . esc_html( $r->purpose ) . '</span></td><td>' . lmc_money( $r->net ) . '</td><td>' . lmc_money( $r->vat ) . '</td><td>' . lmc_money( $r->total ) . '</td><td>' . esc_html( $r->method ) . ( 'test' === $r->mode ? ' ' . lmc_admin_badge( 'test' ) : '' ) . '</td><td>' . lmc_admin_badge( $r->status, $kind ) . '</td><td>' . ( $link ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">View ↗</a>' : '' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	$unpaid = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => array( 'lmc_unpaid', 'lmc_expired' ), 'numberposts' => 100 ) );
	if ( $unpaid ) {
		echo '<div class="lmc-card"><h2>Record a manual payment</h2><p class="description">For invoices paid by bank transfer. Activates or renews the vacancy and emails the employer.</p>' . lmc_admin_form_open( 'mark_paid' ) . '<select name="job_id">';
		foreach ( $unpaid as $j ) {
			echo '<option value="' . (int) $j->ID . '">' . esc_html( $j->post_title . ' – ' . lmc_job_meta( $j->ID, 'employer_name' ) . ' (' . get_post_status_object( $j->post_status )->label . ')' ) . '</option>';
		}
		echo '</select> <select name="package">';
		foreach ( lmc_packages() as $k => $p ) {
			echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $p['label'] . ' – ' . lmc_money( $p['price'] ) ) . '</option>';
		}
		echo '</select> ';
		submit_button( 'Record payment', 'secondary', 'submit', false );
		echo '</form></div>';
	}
	lmc_admin_footer();
}
