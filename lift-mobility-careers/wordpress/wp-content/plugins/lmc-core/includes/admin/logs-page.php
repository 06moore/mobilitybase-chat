<?php
defined( 'ABSPATH' ) || exit;

function lmc_admin_logs() {
	global $wpdb;
	lmc_admin_header( 'Logs', 'Automation activity, errors and every email sent by the system.' );
	echo '<h2>Activity &amp; errors</h2><table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>Level</th><th>Area</th><th>Message</th></tr></thead><tbody>';
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'log' ) . ' ORDER BY id DESC LIMIT 200' ) as $l ) {
		echo '<tr><td>' . esc_html( $l->created_at ) . '</td><td>' . lmc_admin_badge( $l->level, 'error' === $l->level ? 'bad' : ( 'warning' === $l->level ? 'alert' : 'neutral' ) ) . '</td><td>' . esc_html( $l->context ) . '</td><td>' . esc_html( $l->message ) . '</td></tr>';
	}
	echo '</tbody></table><h2>Emails sent</h2><table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>To</th><th>Template</th><th>Subject</th><th>Status</th></tr></thead><tbody>';
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'email_log' ) . ' ORDER BY id DESC LIMIT 200' ) as $e ) {
		echo '<tr><td>' . esc_html( $e->created_at ) . '</td><td>' . esc_html( $e->recipient ) . '</td><td><code>' . esc_html( $e->template ) . '</code></td><td>' . esc_html( $e->subject ) . '</td><td>' . lmc_admin_badge( $e->status, 'sent' === $e->status ? 'good' : 'bad' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	lmc_admin_footer();
}
