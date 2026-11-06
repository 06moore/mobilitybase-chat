<?php
/**
 * Plugin Name: Lift and Mobility Careers – Core
 * Description: Specialist job board engine: vacancies, employer accounts, Stripe payments, automated emails and renewals, vacancy discovery, employer outreach CRM, SEO and the operator dashboard.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Lift and Mobility Careers
 * License: Proprietary
 * Text Domain: lmc
 */

defined( 'ABSPATH' ) || exit;

define( 'LMC_VERSION', '1.0.0' );
define( 'LMC_DB_VERSION', '4' );
define( 'LMC_FILE', __FILE__ );
define( 'LMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'LMC_URL', plugin_dir_url( __FILE__ ) );

$lmc_includes = array(
	'helpers', 'settings', 'install', 'post-types', 'logger', 'emails', 'jobs', 'credits', 'payments',
	'search', 'location', 'forms', 'employer-dashboard', 'cron', 'reports', 'discovery', 'crm',
	'social', 'seo', 'stats', 'analytics', 'alerts', 'google', 'employer-share', 'feeds', 'monthly-report', 'insights', 'dev-seed',
);
foreach ( $lmc_includes as $lmc_inc ) {
	require_once LMC_DIR . 'includes/' . $lmc_inc . '.php';
}
if ( is_admin() ) {
	foreach ( array( 'menu', 'dashboard', 'settings-page', 'discovery-page', 'crm-page', 'payments-page', 'employers-page', 'social-page', 'logs-page', 'job-metabox', 'growth-page' ) as $lmc_inc ) {
		require_once LMC_DIR . 'includes/admin/' . $lmc_inc . '.php';
	}
}

register_activation_hook( __FILE__, array( 'LMC_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LMC_Install', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'LMC_Install', 'maybe_upgrade' ) );
