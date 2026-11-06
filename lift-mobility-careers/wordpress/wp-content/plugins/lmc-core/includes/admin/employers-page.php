<?php
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_employer_free', function () {
	$uid = absint( $_REQUEST['user'] ?? 0 );
	$c   = LMC_Credits::grant_free_listing( $uid );
	lmc_admin_redirect( 'lmc-employers', $c ? 'Free listing credit granted.' : 'This employer has already had a free listing.', ! $c );
} );
add_action( 'lmc_admin_action_employer_credit', function () {
	$uid  = absint( $_POST['user'] ?? 0 );
	$tier = sanitize_key( $_POST['tier'] ?? 'standard' );
	$qty  = max( 1, absint( $_POST['qty'] ?? 1 ) );
	LMC_Credits::add( $uid, $tier, $qty, 'admin', 0, sanitize_text_field( wp_unslash( $_POST['note'] ?? 'Added by admin' ) ) );
	lmc_admin_redirect( 'lmc-employers', "$qty $tier credit(s) added." );
} );
add_action( 'lmc_admin_action_employer_verify', function () {
	update_user_meta( absint( $_REQUEST['user'] ?? 0 ), 'lmc_email_verified', lmc_now() );
	lmc_admin_redirect( 'lmc-employers', 'Email marked as verified.' );
} );

function lmc_admin_employers() {
	lmc_admin_header( 'Employers', 'Employer accounts. Use "Free listing" for introductory offers agreed through outreach.' );
	$users = get_users( array( 'role' => 'lmc_employer', 'number' => 500, 'orderby' => 'registered', 'order' => 'DESC' ) );
	echo '<table class="widefat striped"><thead><tr><th>Company</th><th>Contact</th><th>Registered</th><th>Verified</th><th>Live</th><th>Total vacancies</th><th>Credits</th><th>Actions</th></tr></thead><tbody>';
	foreach ( $users as $u ) {
		$bal   = LMC_Credits::balance( $u->ID );
		$total = count( get_posts( array( 'post_type' => 'lmc_job', 'author' => $u->ID, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) );
		$ver   = get_user_meta( $u->ID, 'lmc_email_verified', true );
		echo '<tr><td><strong>' . esc_html( get_user_meta( $u->ID, 'lmc_company', true ) ) . '</strong><br><span class="description">' . esc_html( get_user_meta( $u->ID, 'lmc_entity_type', true ) ) . '</span></td><td>' . esc_html( $u->display_name ) . '<br><a href="mailto:' . esc_attr( $u->user_email ) . '">' . esc_html( $u->user_email ) . '</a></td><td>' . esc_html( substr( $u->user_registered, 0, 10 ) ) . '</td><td>' . ( $ver ? lmc_admin_badge( 'yes', 'good' ) : lmc_admin_badge( 'no', 'alert' ) . ' <a href="' . esc_url( lmc_admin_action_url( 'employer_verify', array( 'user' => $u->ID ) ) ) . '">mark verified</a>' ) . '</td><td>' . (int) LMC_Jobs::employer_live_count( $u->ID ) . '</td><td><a href="' . esc_url( admin_url( 'edit.php?post_type=lmc_job&author=' . $u->ID ) ) . '">' . (int) $total . '</a></td><td>' . esc_html( $bal ? implode( ', ', array_map( function ( $t, $n ) {
			return "$n $t";
		}, array_keys( $bal ), $bal ) ) : '–' ) . '</td><td>' . ( get_user_meta( $u->ID, 'lmc_free_listing_granted', true ) ? '<span class="description">free listing used</span>' : '<a class="button button-small" href="' . esc_url( lmc_admin_action_url( 'employer_free', array( 'user' => $u->ID ) ) ) . '">Free listing</a>' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	if ( $users ) {
		echo '<div class="lmc-card"><h3>Add credits manually</h3>' . lmc_admin_form_open( 'employer_credit' ) . '<select name="user">';
		foreach ( $users as $u ) {
			echo '<option value="' . (int) $u->ID . '">' . esc_html( get_user_meta( $u->ID, 'lmc_company', true ) . ' – ' . $u->user_email ) . '</option>';
		}
		echo '</select> <select name="tier"><option>standard</option><option>featured</option><option>premium</option></select> <input type="number" name="qty" value="1" min="1" style="width:60px"> <input name="note" placeholder="Reason"> ';
		submit_button( 'Add', 'secondary', 'submit', false );
		echo '</form></div>';
	}
	lmc_admin_footer();
}
