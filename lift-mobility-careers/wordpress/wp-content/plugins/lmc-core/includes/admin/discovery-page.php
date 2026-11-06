<?php
/** Discovery review dashboard: monitored careers pages and discovered opportunities. */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_save_source', function () {
	global $wpdb;
	$id   = absint( $_POST['id'] ?? 0 );
	$data = array(
		'company'        => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
		'careers_url'    => esc_url_raw( wp_unslash( $_POST['careers_url'] ?? '' ) ),
		'terms_reviewed' => empty( $_POST['terms_reviewed'] ) ? 0 : 1,
		'enabled'        => empty( $_POST['enabled'] ) ? 0 : 1,
		'interval_days'  => max( 1, absint( $_POST['interval_days'] ?? 2 ) ),
		'notes'          => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
	);
	if ( ! $data['company'] ) {
		lmc_admin_redirect( 'lmc-discovery', 'Company name is required.', true );
	}
	if ( $id ) {
		$wpdb->update( lmc_table( 'sources' ), $data, array( 'id' => $id ) );
	} else {
		$data['created_at'] = lmc_mysql_time();
		$wpdb->insert( lmc_table( 'sources' ), $data );
	}
	lmc_admin_redirect( 'lmc-discovery', 'Careers page saved.', false, array( 'view' => 'sources' ) );
} );

add_action( 'lmc_admin_action_check_source', function () {
	global $wpdb;
	$s = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'sources' ) . ' WHERE id = %d', absint( $_REQUEST['id'] ?? 0 ) ) );
	if ( $s ) {
		$r = LMC_Discovery::check_source( $s );
		lmc_admin_redirect( 'lmc-discovery', $s->company . ': ' . str_replace( '_', ' ', $r ), 'ok' !== $r, array( 'view' => 'sources' ) );
	}
} );

add_action( 'lmc_admin_action_opp_status', function () {
	global $wpdb;
	$id     = absint( $_REQUEST['id'] ?? 0 );
	$status = sanitize_key( $_REQUEST['status'] ?? '' );
	if ( 'authorised' === $status ) {
		$job = LMC_Discovery::create_draft_from_opportunity( $id );
		wp_safe_redirect( admin_url( 'post.php?post=' . $job . '&action=edit&lmc_from_discovery=1' ) );
		exit;
	}
	if ( in_array( $status, array( 'new', 'rejected', 'contacting', 'gone' ), true ) ) {
		$wpdb->update( lmc_table( 'opportunities' ), array( 'status' => $status ), array( 'id' => $id ) );
	}
	lmc_admin_redirect( 'lmc-discovery', 'Updated.' );
} );

add_action( 'lmc_admin_action_opp_contact', function () {
	global $wpdb;
	$o = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . ' WHERE id = %d', absint( $_REQUEST['id'] ?? 0 ) ) );
	if ( ! $o ) {
		return;
	}
	$src = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'sources' ) . ' WHERE id = %d', $o->source_id ) );
	$pid = $src && $src->prospect_id ? (int) $src->prospect_id : (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . lmc_table( 'prospects' ) . ' WHERE LOWER(company) = %s', strtolower( $o->company ) ) );
	if ( ! $pid ) {
		$pid = LMC_CRM::save( array( 'company' => $o->company, 'status' => 'researching', 'source' => 'discovery', 'notes' => 'Created from discovered vacancy: ' . $o->title ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=lmc-crm&compose=' . $pid . '&tpl=outreach_vacancy_feature&opp=' . $o->id ) );
	exit;
} );

function lmc_admin_discovery() {
	global $wpdb;
	$view = sanitize_key( $_GET['view'] ?? 'opps' );
	lmc_admin_header( 'Vacancy discovery', 'Discovered vacancies are leads only. <strong>Never republish them without the employer\'s permission</strong>: contact the employer first, then write your own short summary and link to their application page.' );
	echo '<nav class="nav-tab-wrapper"><a class="nav-tab ' . ( 'opps' === $view ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmc-discovery' ) ) . '">Discovered vacancies</a><a class="nav-tab ' . ( 'sources' === $view ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmc-discovery&view=sources' ) ) . '">Monitored careers pages</a></nav>';

	if ( 'sources' === $view ) {
		$edit = ! empty( $_GET['edit'] ) ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'sources' ) . ' WHERE id = %d', absint( $_GET['edit'] ) ) ) : null;
		echo '<div class="lmc-card"><h2>' . ( $edit ? 'Edit careers page' : 'Add a careers page' ) . '</h2>' . lmc_admin_form_open( 'save_source' ) . '<input type="hidden" name="id" value="' . esc_attr( $edit->id ?? 0 ) . '"><table class="form-table">';
		echo '<tr><th>Company</th><td><input class="regular-text" name="company" value="' . esc_attr( $edit->company ?? '' ) . '" required></td></tr>';
		echo '<tr><th>Careers page URL</th><td><input class="large-text" type="url" name="careers_url" value="' . esc_attr( $edit->careers_url ?? '' ) . '"></td></tr>';
		echo '<tr><th>Check every</th><td><input type="number" min="1" name="interval_days" value="' . esc_attr( $edit->interval_days ?? 2 ) . '" style="width:70px"> days</td></tr>';
		echo '<tr><th>Terms reviewed</th><td><label><input type="checkbox" name="terms_reviewed" value="1" ' . checked( $edit->terms_reviewed ?? 0, 1, false ) . '> I have read this website\'s terms of use and they do not prohibit automated checking of this page.</label></td></tr>';
		echo '<tr><th>Enabled</th><td><input type="checkbox" name="enabled" value="1" ' . checked( $edit->enabled ?? 1, 1, false ) . '></td></tr>';
		echo '<tr><th>Notes</th><td><textarea class="large-text" rows="2" name="notes">' . esc_textarea( $edit->notes ?? '' ) . '</textarea></td></tr></table>';
		submit_button( 'Save' );
		echo '</form></div>';
		$rows = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'sources' ) . ' ORDER BY company' );
		echo '<table class="widefat striped"><thead><tr><th>Company</th><th>Status</th><th>Last checked</th><th>Found</th><th></th></tr></thead><tbody>';
		foreach ( $rows as $s ) {
			$kind = 'ok' === $s->status ? 'good' : ( 'awaiting_review' === $s->status ? 'alert' : 'bad' );
			echo '<tr><td><strong>' . esc_html( $s->company ) . '</strong><br><a href="' . esc_url( $s->careers_url ) . '" target="_blank" rel="noopener">' . esc_html( $s->careers_url ?: 'no URL' ) . '</a></td><td>' . lmc_admin_badge( str_replace( '_', ' ', $s->status ), $kind ) . ( $s->enabled ? '' : ' ' . lmc_admin_badge( 'disabled' ) ) . '<br><span class="description">' . esc_html( $s->status_note ) . '</span></td><td>' . esc_html( $s->last_checked ? $s->last_checked . ' UTC' : 'never' ) . '</td><td>' . (int) $s->last_found . '</td><td style="white-space:nowrap"><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-discovery&view=sources&edit=' . $s->id ) ) . '">Edit</a> <a class="button" href="' . esc_url( lmc_admin_action_url( 'check_source', array( 'id' => $s->id ) ) ) . '">Check now</a></td></tr>';
		}
		echo '</tbody></table>';
		lmc_admin_footer();
		return;
	}

	$status = sanitize_key( $_GET['status'] ?? 'new' );
	echo '<ul class="subsubsub">';
	foreach ( array( 'new' => 'New', 'contacting' => 'Contacting employer', 'authorised' => 'Authorised', 'rejected' => 'Rejected', 'gone' => 'No longer live' ) as $k => $l ) {
		$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'opportunities' ) . ' WHERE status = %s', $k ) );
		echo '<li><a class="' . ( $status === $k ? 'current' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmc-discovery&status=' . $k ) ) . '">' . esc_html( $l ) . ' <span class="count">(' . $n . ')</span></a> | </li>';
	}
	echo '</ul><br class="clear">';
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . ' WHERE status = %s ORDER BY score DESC, first_seen DESC LIMIT 200', $status ) );
	if ( ! $rows ) {
		echo '<p>Nothing here. Add careers pages under "Monitored careers pages" – they are checked daily.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Vacancy</th><th>Employer</th><th>Location</th><th>First seen</th><th>Still live?</th><th>Relevance</th><th>Actions</th></tr></thead><tbody>';
		foreach ( $rows as $o ) {
			$acts = array(
				'<a class="button button-small" href="' . esc_url( lmc_admin_action_url( 'opp_contact', array( 'id' => $o->id ) ) ) . '">Ask employer</a>',
				'<a class="button button-small" href="' . esc_url( lmc_admin_action_url( 'opp_status', array( 'id' => $o->id, 'status' => 'authorised' ) ) ) . '" onclick="return confirm(\'Only continue if the employer has given permission in writing. Create a draft vacancy?\')">Employer approved → draft</a>',
				'<a class="button button-small" href="' . esc_url( lmc_admin_action_url( 'opp_status', array( 'id' => $o->id, 'status' => 'rejected' ) ) ) . '">Reject</a>',
			);
			if ( $o->job_id ) {
				$acts = array( '<a href="' . esc_url( get_edit_post_link( $o->job_id ) ) . '">Edit draft vacancy</a>' );
			}
			echo '<tr><td><strong>' . esc_html( $o->title ) . '</strong><br><a href="' . esc_url( $o->url ) . '" target="_blank" rel="noopener">Original advert ↗</a></td><td>' . esc_html( $o->company ) . '</td><td>' . esc_html( $o->location ?: '–' ) . '</td><td>' . esc_html( substr( $o->first_seen, 0, 10 ) ) . '</td><td>' . ( $o->still_live ? lmc_admin_badge( 'Live', 'good' ) : lmc_admin_badge( 'Gone', 'bad' ) ) . '<br><span class="description">seen ' . esc_html( substr( $o->last_seen, 0, 10 ) ) . '</span></td><td>' . (int) $o->score . '<br><span class="description">' . esc_html( $o->keywords ) . '</span></td><td>' . implode( ' ', $acts ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '<p><a class="button" href="' . esc_url( lmc_admin_action_url( 'run_now', array( 'what' => 'discovery' ) ) ) . '">Run discovery now</a></p>';
	lmc_admin_footer();
}
