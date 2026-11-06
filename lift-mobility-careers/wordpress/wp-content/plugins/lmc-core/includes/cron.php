<?php
/**
 * Scheduled automation. In production, disable WP-Cron's page-load trigger and call
 * wp-cron.php from a real server cron every 15 minutes (see docs/operations/deployment.md).
 */
defined( 'ABSPATH' ) || exit;

class LMC_Cron {

	public static function init() {
		add_filter( 'cron_schedules', function ( $s ) {
			$s['weekly'] = $s['weekly'] ?? array( 'interval' => WEEK_IN_SECONDS, 'display' => 'Once weekly' );
			return $s;
		} );
		add_action( 'lmc_hourly', array( 'LMC_Jobs', 'run_lifecycle' ) );
		add_action( 'lmc_hourly', array( 'LMC_CRM', 'refresh_followups' ) );
		add_action( 'lmc_hourly', array( 'LMC_Payments', 'cleanup_stale' ) );
		add_action( 'lmc_hourly', function () {
			global $wpdb; // Retention: the email log is kept for 2 years.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . lmc_table( 'email_log' ) . ' WHERE created_at < %s', lmc_mysql_time( lmc_now() - 730 * DAY_IN_SECONDS ) ) );
		} );
		add_action( 'lmc_daily_discovery', array( 'LMC_Discovery', 'run' ) );
		add_action( 'lmc_weekly_report', array( 'LMC_Reports', 'send_weekly' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'lmc_hourly' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'lmc_hourly' );
		}
		if ( ! wp_next_scheduled( 'lmc_daily_discovery' ) ) {
			$t = new DateTime( 'tomorrow 03:15', new DateTimeZone( 'Europe/London' ) );
			wp_schedule_event( $t->getTimestamp(), 'daily', 'lmc_daily_discovery' );
		}
		if ( ! wp_next_scheduled( 'lmc_weekly_report' ) ) {
			$t = new DateTime( 'next monday 07:30', new DateTimeZone( 'Europe/London' ) );
			wp_schedule_event( $t->getTimestamp(), 'weekly', 'lmc_weekly_report' );
		}
	}

	public static function unschedule() {
		foreach ( array( 'lmc_hourly', 'lmc_daily_discovery', 'lmc_weekly_report' ) as $h ) {
			wp_clear_scheduled_hook( $h );
		}
	}
}
LMC_Cron::init();
