<?php
/** Vacancy details metabox for operators editing vacancies in WP Admin. */
defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_lmc_job', function () {
	add_meta_box( 'lmc_job_details', 'Vacancy details', 'lmc_job_metabox', 'lmc_job', 'normal', 'high' );
	add_meta_box( 'lmc_job_listing', 'Listing', 'lmc_job_listing_box', 'lmc_job', 'side', 'high' );
} );

function lmc_job_metabox( $post ) {
	wp_nonce_field( 'lmc_job_meta', 'lmc_job_meta_nonce' );
	if ( ! empty( $_GET['lmc_from_discovery'] ) || lmc_job_meta( $post->ID, 'source_opportunity' ) ) {
		echo '<div class="notice notice-warning inline"><p><strong>Created from a discovered vacancy.</strong> Write an original summary (don\'t copy the employer\'s advert), complete salary and requirements, get the employer to confirm the text, and only use their logo with written permission.</p></div>';
	}
	$flags = (array) lmc_job_meta( $post->ID, 'flags' );
	if ( array_filter( $flags ) ) {
		echo '<div class="notice notice-error inline"><p><strong>Automatic checks flagged:</strong> ' . esc_html( implode( '; ', $flags ) ) . '</p></div>';
	}
	echo '<table class="form-table"><tbody>';
	foreach ( LMC_Post_Types::fields() as $k => $f ) {
		$v    = lmc_job_meta( $post->ID, $k );
		$type = $f['type'] ?? 'text';
		echo '<tr><th><label for="lmc_' . esc_attr( $k ) . '">' . esc_html( $f['label'] ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="4" id="lmc_' . esc_attr( $k ) . '" name="lmc_meta[' . esc_attr( $k ) . ']">' . esc_textarea( $v ) . '</textarea>';
		} elseif ( 'select' === $type ) {
			echo '<select id="lmc_' . esc_attr( $k ) . '" name="lmc_meta[' . esc_attr( $k ) . ']">';
			foreach ( $f['options'] as $ok => $ol ) {
				echo '<option value="' . esc_attr( $ok ) . '" ' . selected( $v, $ok, false ) . '>' . esc_html( $ol ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input class="regular-text" type="' . ( 'number' === $type ? 'number' : ( 'date' === $type ? 'date' : 'text' ) ) . '" step="any" id="lmc_' . esc_attr( $k ) . '" name="lmc_meta[' . esc_attr( $k ) . ']" value="' . esc_attr( $v ) . '">';
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '<tr><th>Social promotion allowed</th><td><input type="checkbox" name="lmc_meta[social_ok]" value="1" ' . checked( lmc_job_meta( $post->ID, 'social_ok' ), 1, false ) . '></td></tr>';
	$auth = lmc_job_meta( $post->ID, 'authorised' );
	echo '<tr><th>Authorisation record</th><td>' . ( $auth ? esc_html( 'Declared by ' . ( $auth['name'] ?? '?' ) . ' on ' . lmc_date( $auth['time'] ?? 0, 'j M Y H:i' ) . ' (declaration v' . ( $auth['version'] ?? '1.0' ) . ')' ) : '<em>None – for operator-created vacancies, keep the employer\'s written permission (email) on file.</em>' ) . '</td></tr>';
	echo '</tbody></table>';
}

function lmc_job_listing_box( $post ) {
	$exp = (int) lmc_job_meta( $post->ID, 'expires' );
	$s   = LMC_Stats::totals( $post->ID );
	echo '<p><label>Advert tier<br><select name="lmc_tier">';
	foreach ( array( 'standard', 'featured', 'premium' ) as $t ) {
		echo '<option ' . selected( lmc_job_tier( $post->ID ), $t, false ) . '>' . esc_html( $t ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Listing ends<br><input type="datetime-local" name="lmc_expires" value="' . esc_attr( $exp ? wp_date( 'Y-m-d\TH:i', $exp ) : '' ) . '"></label><br><span class="description">Set automatically on publish.</span></p>';
	echo '<p>Published: ' . esc_html( lmc_date( lmc_job_meta( $post->ID, 'published_at' ) ) ) . '<br>Renewals: ' . (int) lmc_job_meta( $post->ID, 'renewals' ) . '<br>Views: ' . (int) $s['views'] . ' · Apply clicks: ' . (int) $s['clicks'] . '<br>Outcome: ' . esc_html( lmc_job_meta( $post->ID, 'outcome' ) ?: '–' ) . '</p>';
}

add_action( 'save_post_lmc_job', function ( $id ) {
	if ( ! isset( $_POST['lmc_job_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lmc_job_meta_nonce'] ) ), 'lmc_job_meta' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) {
		return;
	}
	$m = wp_unslash( $_POST['lmc_meta'] ?? array() );
	foreach ( LMC_Post_Types::fields() as $k => $f ) {
		$v = $m[ $k ] ?? '';
		$v = 'textarea' === ( $f['type'] ?? '' ) ? sanitize_textarea_field( $v ) : ( 'url' === ( $f['type'] ?? '' ) ? esc_url_raw( $v ) : sanitize_text_field( $v ) );
		update_post_meta( $id, '_lmc_' . $k, $v );
	}
	update_post_meta( $id, '_lmc_social_ok', empty( $m['social_ok'] ) ? 0 : 1 );
	update_post_meta( $id, '_lmc_salary_annual', LMC_Search::annualise( ( (float) ( $m['salary_max'] ?? 0 ) ) ?: (float) ( $m['salary_min'] ?? 0 ), $m['salary_period'] ?? 'YEAR' ) );
	$tier = in_array( $_POST['lmc_tier'] ?? '', array( 'standard', 'featured', 'premium' ), true ) ? $_POST['lmc_tier'] : 'standard';
	update_post_meta( $id, '_lmc_tier', $tier );
	update_post_meta( $id, '_lmc_priority', lmc_tier_priority( $tier ) );
	if ( ! empty( $_POST['lmc_expires'] ) ) {
		$ts = strtotime( sanitize_text_field( wp_unslash( $_POST['lmc_expires'] ) ) . ' Europe/London' );
		if ( $ts ) {
			update_post_meta( $id, '_lmc_expires', $ts );
		}
	}
	if ( 'publish' === get_post_status( $id ) ) {
		LMC_Location::geocode_job( $id );
	}
	update_post_meta( $id, '_lmc_flags', LMC_Forms::content_flags( get_post_field( 'post_title', $id ) . ' ' . get_post_field( 'post_content', $id ) . ' ' . ( $m['requirements'] ?? '' ) ) );
}, 5 );

/** Columns in the vacancies list. */
add_filter( 'manage_lmc_job_posts_columns', function ( $c ) {
	$c['lmc_employer'] = 'Employer';
	$c['lmc_tier']     = 'Tier';
	$c['lmc_expires']  = 'Ends';
	$c['lmc_stats']    = 'Views / clicks';
	unset( $c['date'] );
	return $c;
} );
add_action( 'manage_lmc_job_posts_custom_column', function ( $col, $id ) {
	if ( 'lmc_employer' === $col ) {
		echo esc_html( lmc_job_meta( $id, 'employer_name' ) );
	} elseif ( 'lmc_tier' === $col ) {
		echo esc_html( ucfirst( lmc_job_tier( $id ) ) );
	} elseif ( 'lmc_expires' === $col ) {
		echo esc_html( lmc_date( lmc_job_meta( $id, 'expires' ) ) );
	} elseif ( 'lmc_stats' === $col ) {
		$s = LMC_Stats::totals( $id );
		echo (int) $s['views'] . ' / ' . (int) $s['clicks'];
	}
}, 10, 2 );
