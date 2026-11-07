<?php
/** Outreach CRM: prospect database, one-at-a-time compliant outreach, suppression list, CSV import/export. */
defined( 'ABSPATH' ) || exit;

add_action( 'lmc_admin_action_save_prospect', function () {
	$id = absint( $_POST['id'] ?? 0 );
	$d  = wp_unslash( $_POST );
	if ( empty( $d['company'] ) ) {
		lmc_admin_redirect( 'lmc-crm', 'Company is required.', true );
	}
	$d['consent_marketing'] = empty( $d['consent_marketing'] ) ? 0 : 1;
	if ( $d['consent_marketing'] && empty( $d['consent_note'] ) ) {
		lmc_admin_redirect( 'lmc-crm', 'Record how and when consent was given (e.g. "Replied to LinkedIn message 12/03 asking for info").', true, array( 'edit' => $id ) );
	}
	$id = LMC_CRM::save( $d, $id );
	lmc_admin_redirect( 'lmc-crm', 'Saved.', false, array( 'edit' => $id ) );
} );

add_action( 'lmc_admin_action_send_outreach', function () {
	$pid = absint( $_POST['prospect'] ?? 0 );
	$tpl = sanitize_key( $_POST['tpl'] ?? '' );
	$opp = absint( $_POST['opp'] ?? 0 );
	if ( empty( $_POST['confirm_reviewed'] ) ) {
		lmc_admin_redirect( 'lmc-crm', 'Tick the confirmation box after reviewing the email.', true, array( 'compose' => $pid, 'tpl' => $tpl, 'opp' => $opp ) );
	}
	$r = LMC_CRM::send_outreach( $pid, $tpl, $opp );
	if ( true === $r ) {
		lmc_admin_redirect( 'lmc-crm', 'Email sent and contact logged. Follow-up reminder set.', false, array( 'edit' => $pid ) );
	}
	lmc_admin_redirect( 'lmc-crm', $r, true, array( 'edit' => $pid ) );
} );

add_action( 'lmc_admin_action_grant_free', function () {
	global $wpdb;
	$p = LMC_CRM::get( absint( $_REQUEST['id'] ?? 0 ) );
	if ( ! $p || ! $p->employer_user_id ) {
		lmc_admin_redirect( 'lmc-crm', 'The employer needs to register an account first – send them the registration link.', true );
	}
	$c = LMC_Credits::grant_free_listing( $p->employer_user_id );
	if ( $c ) {
		$wpdb->update( lmc_table( 'prospects' ), array( 'status' => 'free_listing_active', 'free_listing_used' => 1 ), array( 'id' => $p->id ) );
	}
	lmc_admin_redirect( 'lmc-crm', $c ? 'Free 30-day ' . lmc_opt( 'free_listing_tier' ) . ' listing credit added to their account (valid 90 days).' : 'This employer has already had a free listing.', ! $c, array( 'edit' => $p->id ) );
} );

add_action( 'lmc_admin_action_suppress', function () {
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	LMC_CRM::suppress( $email, sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'manual' ) ) );
	lmc_admin_redirect( 'lmc-crm', $email . ' added to the suppression list.', false, array( 'view' => 'suppression' ) );
} );

add_action( 'lmc_admin_action_import_csv', function () {
	if ( empty( $_FILES['csv']['tmp_name'] ) ) {
		lmc_admin_redirect( 'lmc-crm', 'No file uploaded.', true );
	}
	$n = LMC_CRM::import_csv( $_FILES['csv']['tmp_name'] );
	lmc_admin_redirect( 'lmc-crm', "Imported $n prospects." );
} );

add_action( 'lmc_admin_action_export_csv', function () {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'prospects' ) . ' ORDER BY company', ARRAY_A );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=lmc-prospects-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	if ( $rows ) {
		fputcsv( $out, array_keys( $rows[0] ) );
		foreach ( $rows as $r ) {
			fputcsv( $out, $r );
		}
	}
	exit;
} );

function lmc_admin_crm() {
	global $wpdb;
	$t = lmc_table( 'prospects' );
	lmc_admin_header( 'Employer outreach', 'Target: relationships with 50–100 direct employers. Emails are sent individually, never in bulk, with PECR checks applied automatically.' );
	$view = sanitize_key( $_GET['view'] ?? '' );
	echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm' ) ) . '">All prospects</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&view=due' ) ) . '">Follow-ups due</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&edit=0&new=1' ) ) . '">+ Add prospect</a> <a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&view=suppression' ) ) . '">Suppression list</a> <a class="button" href="' . esc_url( lmc_admin_action_url( 'export_csv' ) ) . '">Export CSV</a></p>';

	// Compose screen.
	if ( isset( $_GET['compose'] ) ) {
		$p   = LMC_CRM::get( absint( $_GET['compose'] ) );
		$tpl = sanitize_key( $_GET['tpl'] ?? 'outreach_free_listing' );
		$opp = absint( $_GET['opp'] ?? 0 );
		if ( ! $p ) {
			echo '<p>Prospect not found.</p>';
			lmc_admin_footer();
			return;
		}
		$check = LMC_CRM::can_email( $p );
		$o     = $opp ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . ' WHERE id = %d', $opp ) ) : null;
		$vars  = array_merge( lmc_email_common_vars(), LMC_CRM::outreach_vars( $p, $o ) );
		$tp    = lmc_email_template( $tpl );
		echo '<div class="lmc-card"><h2>Email to ' . esc_html( $p->company ) . '</h2>';
		echo '<p><strong>To:</strong> ' . esc_html( $p->contact_email ?: '(no email recorded)' ) . ' · <strong>Entity:</strong> ' . esc_html( LMC_CRM::entity_types()[ $p->entity_type ] ?? $p->entity_type ) . ' · <strong>Previous contacts:</strong> ' . (int) $p->contact_count . '</p>';
		echo '<p>Template: ';
		foreach ( array( 'outreach_free_listing' => 'Free listing offer', 'outreach_vacancy_feature' => 'May we feature your vacancy?', 'outreach_followup' => 'Follow-up' ) as $k => $l ) {
			echo '<a class="button ' . ( $tpl === $k ? 'button-primary' : '' ) . '" href="' . esc_url( add_query_arg( 'tpl', $k ) ) . '">' . esc_html( $l ) . '</a> ';
		}
		echo '</p><div class="lmc-email-preview"><p><strong>Subject:</strong> ' . esc_html( lmc_email_replace( $tp['subject'], $vars ) ) . '</p><pre>' . esc_html( lmc_email_replace( $tp['body'], $vars ) ) . "\n\n— Sender identification, postal address and an unsubscribe link are added automatically.</pre></div>";
		if ( true !== $check ) {
			echo '<div class="notice notice-error inline"><p><strong>Cannot send:</strong> ' . esc_html( $check ) . '</p></div>';
		} else {
			echo lmc_admin_form_open( 'send_outreach' ) . '<input type="hidden" name="prospect" value="' . (int) $p->id . '"><input type="hidden" name="tpl" value="' . esc_attr( $tpl ) . '"><input type="hidden" name="opp" value="' . (int) $opp . '">';
			echo '<p><label><input type="checkbox" name="confirm_reviewed" value="1"> I have checked this is a relevant business contact, the details are correct, and this email is appropriate to send.</label></p>';
			submit_button( 'Send this one email', 'primary', 'submit', false );
			echo '</form>';
		}
		echo '<p class="description">Edit the wording under Settings → Email templates.</p></div>';
		lmc_admin_footer();
		return;
	}

	if ( 'suppression' === $view ) {
		echo '<div class="lmc-card"><h2>Suppression list</h2><p>These addresses never receive marketing or outreach email.</p>' . lmc_admin_form_open( 'suppress' ) . '<input type="email" name="email" placeholder="email@company.co.uk" required> <input name="reason" placeholder="Reason"> ';
		submit_button( 'Add', 'secondary', 'submit', false );
		echo '</form><table class="widefat striped"><tbody>';
		foreach ( $wpdb->get_results( 'SELECT * FROM ' . lmc_table( 'suppression' ) . ' ORDER BY id DESC LIMIT 500' ) as $s ) {
			echo '<tr><td>' . esc_html( $s->email ) . '</td><td>' . esc_html( $s->reason ) . '</td><td>' . esc_html( $s->created_at ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
		lmc_admin_footer();
		return;
	}

	// Edit screen.
	if ( isset( $_GET['edit'] ) ) {
		$p = absint( $_GET['edit'] ) ? LMC_CRM::get( absint( $_GET['edit'] ) ) : null;
		$f = function ( $k ) use ( $p ) {
			return $p->$k ?? '';
		};
		echo '<div class="lmc-card"><h2>' . ( $p ? esc_html( $p->company ) : 'New prospect' ) . '</h2>';
		if ( $p ) {
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&compose=' . $p->id ) ) . '">Compose email</a> ';
			if ( $p->employer_user_id ) {
				echo '<a class="button" href="' . esc_url( lmc_admin_action_url( 'grant_free', array( 'id' => $p->id ) ) ) . '">Grant free 30-day listing</a> <span class="description">Linked employer account #' . (int) $p->employer_user_id . '</span>';
			} else {
				echo '<span class="description">No employer account yet. Free listings are granted once they register.</span>';
			}
			echo '</p>';
		}
		echo lmc_admin_form_open( 'save_prospect' ) . '<input type="hidden" name="id" value="' . (int) ( $p->id ?? 0 ) . '"><table class="form-table">';
		$text = array( 'company' => 'Company', 'company_number' => 'Companies House number', 'sector' => 'Sector(s)', 'website' => 'Website', 'careers_url' => 'Careers page URL', 'contact_name' => 'Contact name', 'contact_role' => 'Contact role', 'contact_email' => 'Contact email', 'phone' => 'Phone', 'town' => 'Town', 'region' => 'Region' );
		foreach ( $text as $k => $l ) {
			echo '<tr><th>' . esc_html( $l ) . '</th><td><input class="regular-text" name="' . esc_attr( $k ) . '" value="' . esc_attr( $f( $k ) ) . '">' . ( 'careers_url' === $k ? '<p class="description">Saving a careers URL adds it to discovery monitoring (after you confirm the site\'s terms).</p>' : '' ) . ( 'contact_email' === $k ? '<p class="description">Prefer published role-based addresses (careers@, recruitment@, hr@). Record where you found it in notes.</p>' : '' ) . '</td></tr>';
		}
		$sel = function ( $name, $opts, $val ) {
			$o = '<select name="' . esc_attr( $name ) . '">';
			foreach ( $opts as $k => $l ) {
				$o .= '<option value="' . esc_attr( $k ) . '" ' . selected( $val, $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			return $o . '</select>';
		};
		echo '<tr><th>Entity type</th><td>' . $sel( 'entity_type', LMC_CRM::entity_types(), $f( 'entity_type' ) ?: 'unknown' ) . '<p class="description">Determines PECR rules. Check on Companies House. Sole traders & partnerships require prior consent for marketing email.</p></td></tr>';
		echo '<tr><th>Status</th><td>' . $sel( 'status', LMC_CRM::STATUSES, $f( 'status' ) ?: 'researching' ) . '</td></tr>';
		echo '<tr><th>Permission status</th><td>' . $sel( 'permission_status', LMC_CRM::PERMISSIONS, $f( 'permission_status' ) ?: 'none' ) . '</td></tr>';
		echo '<tr><th>Marketing consent</th><td><label><input type="checkbox" name="consent_marketing" value="1" ' . checked( $f( 'consent_marketing' ), 1, false ) . '> Recorded consent to receive marketing email</label><br><input class="large-text" name="consent_note" placeholder="How/when consent was given" value="' . esc_attr( $f( 'consent_note' ) ) . '"></td></tr>';
		echo '<tr><th>Follow-up date</th><td><input type="date" name="follow_up" value="' . esc_attr( $f( 'follow_up' ) ) . '"></td></tr>';
		echo '<tr><th>Notes</th><td><textarea class="large-text" rows="4" name="notes">' . esc_textarea( $f( 'notes' ) ) . '</textarea></td></tr>';
		if ( $p ) {
			echo '<tr><th>History</th><td>Last contacted: ' . esc_html( $p->last_contacted ?: 'never' ) . ' · Contacts: ' . (int) $p->contact_count . ' · Vacancies seen on careers page: ' . (int) $p->vacancies_seen . ' · Source: ' . esc_html( $p->source ) . '</td></tr>';
		}
		echo '</table>';
		submit_button( 'Save prospect' );
		echo '</form></div>';
		lmc_admin_footer();
		return;
	}

	// List.
	$where = '1=1';
	if ( 'due' === $view ) {
		$where = $wpdb->prepare( "follow_up IS NOT NULL AND follow_up <= %s AND status NOT IN ('do_not_contact','not_interested','customer')", wp_date( 'Y-m-d', lmc_now() ) );
	} elseif ( ! empty( $_GET['status'] ) ) {
		$where = $wpdb->prepare( 'status = %s', sanitize_key( $_GET['status'] ) );
	}
	$rows = $wpdb->get_results( "SELECT * FROM $t WHERE $where ORDER BY (follow_up IS NULL), follow_up, company LIMIT 500" );
	$counts = $wpdb->get_results( "SELECT status, COUNT(*) n FROM $t GROUP BY status", OBJECT_K );
	echo '<ul class="subsubsub">';
	foreach ( LMC_CRM::STATUSES as $k => $l ) {
		echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&status=' . $k ) ) . '">' . esc_html( $l ) . ' <span class="count">(' . (int) ( $counts[ $k ]->n ?? 0 ) . ')</span></a> | </li>';
	}
	echo '</ul><br class="clear"><table class="widefat striped"><thead><tr><th>Company</th><th>Type</th><th>Contact</th><th>Status</th><th>Permission</th><th>Last contacted</th><th>Follow-up</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $p ) {
		$pecr = LMC_CRM::is_corporate( $p->entity_type ) ? '' : ( $p->consent_marketing ? lmc_admin_badge( 'consent', 'good' ) : lmc_admin_badge( 'no email w/o consent', 'alert' ) );
		echo '<tr><td><strong><a href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&edit=' . $p->id ) ) . '">' . esc_html( $p->company ) . '</a></strong><br><span class="description">' . esc_html( $p->sector ) . '</span></td><td>' . esc_html( LMC_CRM::entity_types()[ $p->entity_type ] ?? $p->entity_type ) . ' ' . $pecr . '</td><td>' . esc_html( $p->contact_name ) . '<br>' . esc_html( $p->contact_email ) . '</td><td>' . esc_html( LMC_CRM::STATUSES[ $p->status ] ?? $p->status ) . '</td><td>' . esc_html( LMC_CRM::PERMISSIONS[ $p->permission_status ] ?? $p->permission_status ) . '</td><td>' . esc_html( $p->last_contacted ? substr( $p->last_contacted, 0, 10 ) : '–' ) . '</td><td>' . esc_html( $p->follow_up ?: '–' ) . '</td><td><a class="button button-small" href="' . esc_url( admin_url( 'admin.php?page=lmc-crm&compose=' . $p->id ) ) . '">Email</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<div class="lmc-card"><h3>Import prospects (CSV)</h3><p class="description">Columns (header row): company, entity_type, company_number, sector, website, careers_url, contact_name, contact_role, contact_email, phone, town, region, notes. A starter list is in <code>data/target-employers.csv</code>.</p><form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="lmc_admin"><input type="hidden" name="do" value="import_csv">' . wp_nonce_field( 'lmc_admin_import_csv', '_wpnonce', true, false ) . '<input type="file" name="csv" accept=".csv"> ';
	submit_button( 'Import', 'secondary', 'submit', false );
	echo '</form></div>';
	lmc_admin_footer();
}
