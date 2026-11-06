<?php
/**
 * XML job feed for free job-search aggregators (e.g. Adzuna, Jooble, Careerjet, Talent.com).
 * Format follows the widely accepted "Indeed-style" XML schema. Each job links back to OUR vacancy page
 * (tagged utm_source=aggregator) so the traffic and apply clicks come through this site.
 * Distribution is covered by the Employer Permission and Content Licence (clause 1).
 * URL: /feeds/jobs.xml
 */
defined( 'ABSPATH' ) || exit;

class LMC_Feeds {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_serve' ), 2 );
	}

	public static function maybe_serve() {
		if ( '/feeds/jobs.xml' !== wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ) {
			return;
		}
		if ( ! lmc_opt( 'feed_enabled' ) || ! LMC_Launch::is_live() ) {
			status_header( 404 );
			exit;
		}
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo self::xml(); // phpcs:ignore
		exit;
	}

	private static function c( $v ) {
		return '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', (string) $v ) . ']]>';
	}

	public static function xml() {
		$jobs = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => 1000, 'meta_query' => array( LMC_Launch::demo_meta_query() ) ) );
		$x    = '<?xml version="1.0" encoding="utf-8"?>' . "\n<source>\n<publisher>" . self::c( lmc_opt( 'business_name' ) ) . '</publisher><publisherurl>' . self::c( home_url( '/' ) ) . '</publisherurl><lastBuildDate>' . self::c( gmdate( DATE_RFC2822 ) ) . "</lastBuildDate>\n";
		foreach ( $jobs as $j ) {
			$county = '';
			foreach ( lmc_job_terms( $j->ID, 'lmc_location' ) as $t ) {
				if ( $t->parent ) {
					$county = $t->name;
				}
			}
			$desc = get_post_field( 'post_content', $j->ID );
			foreach ( array( 'responsibilities' => 'Key responsibilities', 'requirements' => 'Requirements' ) as $k => $l ) {
				$items = lmc_lines( lmc_job_meta( $j->ID, $k ) );
				if ( $items ) {
					$desc .= '<p><strong>' . $l . '</strong></p><ul><li>' . implode( '</li><li>', array_map( 'esc_html', $items ) ) . '</li></ul>';
				}
			}
			$x .= '<job>'
				. '<title>' . self::c( lmc_plain_title( $j->ID ) ) . '</title>'
				. '<date>' . self::c( gmdate( DATE_RFC2822, (int) lmc_job_meta( $j->ID, 'published_at' ) ?: strtotime( $j->post_date_gmt . ' UTC' ) ) ) . '</date>'
				. '<referencenumber>' . self::c( 'LMC-' . $j->ID ) . '</referencenumber>'
				. '<url>' . self::c( add_query_arg( array( 'utm_source' => 'aggregator', 'utm_medium' => 'feed' ), get_permalink( $j ) ) ) . '</url>'
				. '<company>' . self::c( lmc_job_meta( $j->ID, 'employer_name' ) ) . '</company>'
				. '<city>' . self::c( lmc_job_meta( $j->ID, 'town' ) ) . '</city>'
				. '<state>' . self::c( $county ) . '</state>'
				. '<country>' . self::c( 'GB' ) . '</country>'
				. '<postalcode>' . self::c( lmc_job_meta( $j->ID, 'postcode' ) ) . '</postalcode>'
				. '<description>' . self::c( $desc ) . '</description>'
				. '<salary>' . self::c( lmc_salary_text( $j->ID ) ) . '</salary>'
				. '<jobtype>' . self::c( lmc_job_term_names( $j->ID, 'lmc_type' ) ) . '</jobtype>'
				. '<category>' . self::c( lmc_job_term_names( $j->ID, 'lmc_sector' ) ) . '</category>'
				. '<experience>' . self::c( lmc_job_term_names( $j->ID, 'lmc_experience' ) ) . '</experience>'
				. '<expirationdate>' . self::c( gmdate( 'Y-m-d', (int) lmc_job_meta( $j->ID, 'expires' ) ) ) . '</expirationdate>'
				. "</job>\n";
		}
		return $x . '</source>';
	}
}
LMC_Feeds::init();
