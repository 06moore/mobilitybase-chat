<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	$cap = 'manage_options';
	add_menu_page( 'Lift & Mobility', 'Lift & Mobility', $cap, 'lmc', 'lmc_admin_dashboard', 'dashicons-arrow-up-alt', 3 );
	add_submenu_page( 'lmc', 'Dashboard', 'Dashboard', $cap, 'lmc', 'lmc_admin_dashboard' );
	add_submenu_page( 'lmc', 'Employers', 'Employers', $cap, 'lmc-employers', 'lmc_admin_employers' );
	add_submenu_page( 'lmc', 'Payments', 'Payments', $cap, 'lmc-payments', 'lmc_admin_payments' );
	add_submenu_page( 'lmc', 'Vacancy discovery', 'Discovery', $cap, 'lmc-discovery', 'lmc_admin_discovery' );
	add_submenu_page( 'lmc', 'Employer outreach', 'Outreach CRM', $cap, 'lmc-crm', 'lmc_admin_crm' );
	add_submenu_page( 'lmc', 'Social posts', 'Social posts', $cap, 'lmc-social', 'lmc_admin_social' );
	add_submenu_page( 'lmc', 'Settings', 'Settings', $cap, 'lmc-settings', 'lmc_admin_settings' );
	add_submenu_page( 'lmc', 'Logs', 'Logs', $cap, 'lmc-logs', 'lmc_admin_logs' );
	foreach ( array( 'lmc_sector' => 'Sectors', 'lmc_role' => 'Job categories', 'lmc_location' => 'Locations' ) as $tax => $label ) {
		add_submenu_page( 'lmc', $label, $label, $cap, 'edit-tags.php?taxonomy=' . $tax . '&post_type=lmc_job' );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false !== strpos( $hook, 'lmc' ) || 'lmc_job' === get_post_type() ) {
		wp_enqueue_style( 'lmc-admin', LMC_URL . 'assets/admin.css', array(), LMC_VERSION );
	}
} );

/** Shared helpers for admin screens. */
function lmc_admin_header( $title, $sub = '' ) {
	echo '<div class="wrap lmc-admin"><h1 class="lmc-admin__title">' . esc_html( $title ) . '</h1>';
	if ( $sub ) {
		echo '<p class="lmc-admin__sub">' . wp_kses_post( $sub ) . '</p>';
	}
	if ( ! empty( $_GET['lmc_msg'] ) ) {
		echo '<div class="notice notice-' . ( ! empty( $_GET['lmc_err'] ) ? 'error' : 'success' ) . ' is-dismissible"><p>' . esc_html( wp_unslash( $_GET['lmc_msg'] ) ) . '</p></div>';
	}
}

function lmc_admin_footer() {
	echo '</div>';
}

function lmc_admin_redirect( $page, $msg, $err = false, $extra = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page, 'lmc_msg' => rawurlencode( $msg ), 'lmc_err' => $err ? 1 : 0 ), $extra ), admin_url( 'admin.php' ) ) );
	exit;
}

/** Run admin form actions posted to admin-post.php?action=lmc_admin. */
add_action( 'admin_post_lmc_admin', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	$do = sanitize_key( $_REQUEST['do'] ?? '' );
	check_admin_referer( 'lmc_admin_' . $do );
	do_action( 'lmc_admin_action_' . $do );
	wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=lmc' ) );
	exit;
} );

function lmc_admin_action_url( $do, $args = array() ) {
	return wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'lmc_admin', 'do' => $do ), $args ), admin_url( 'admin-post.php' ) ), 'lmc_admin_' . $do );
}

function lmc_admin_form_open( $do ) {
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="lmc_admin"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">' . wp_nonce_field( 'lmc_admin_' . $do, '_wpnonce', true, false );
}

function lmc_admin_badge( $text, $kind = 'neutral' ) {
	return '<span class="lmc-a-badge lmc-a-badge--' . esc_attr( $kind ) . '">' . esc_html( $text ) . '</span>';
}

// The site has no blog or comments – hide those menus to keep the admin simple.
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit-comments.php' );
}, 99 );
