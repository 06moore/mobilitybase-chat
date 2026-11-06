<?php
/**
 * Social post drafts. When a vacancy goes live AND the employer ticked the social permission box,
 * LinkedIn and Facebook drafts are generated automatically. Nothing is published to social
 * networks unless the operator approves the draft (or explicitly enables auto-send to a webhook).
 */
defined( 'ABSPATH' ) || exit;

class LMC_Social {

	public static function init() {
		add_action( 'lmc_job_published', array( __CLASS__, 'on_published' ) );
	}

	public static function templates() {
		return array(
			'linkedin' => array(
				"New vacancy: {title} with {employer} – {location}\n\n{salary}{extras}\n\n{hook}\n\nFull details and how to apply (direct to the employer):\n{url}\n\n{hashtags}",
				"We're featuring a new role in the {sector} sector.\n\n{title}\n{employer} · {location}\n{salary}\n\nApply directly with the employer – free, no sign-up needed:\n{url}\n\n{hashtags}",
			),
			'facebook' => array(
				"🔧 New job: {title}\n📍 {location}\n💷 {salary}\n🏢 {employer}\n\n{hook}\n\nSee the full advert and apply: {url}",
			),
		);
	}

	public static function hashtags( $job_id ) {
		$tags = array( '#EngineeringJobs', '#UKJobs' );
		foreach ( lmc_job_terms( $job_id, 'lmc_sector' ) as $t ) {
			$tags[] = '#' . str_replace( array( ' ', '&', '-' ), '', ucwords( $t->name ) ) . 'Jobs';
		}
		foreach ( lmc_job_terms( $job_id, 'lmc_role' ) as $t ) {
			if ( in_array( $t->slug, array( 'apprentice', 'trainee-engineer' ), true ) ) {
				$tags[] = '#Apprenticeships';
			}
			if ( 'loler-inspector' === $t->slug ) {
				$tags[] = '#LOLER';
			}
		}
		return implode( ' ', array_unique( $tags ) );
	}

	public static function build( $job_id, $platform, $variant = 0 ) {
		$tpls  = self::templates()[ $platform ];
		$tpl   = $tpls[ $variant % count( $tpls ) ];
		$exp   = lmc_job_terms( $job_id, 'lmc_experience' );
		$hook  = ( $exp && 'entry-level' === $exp[0]->slug ) ? 'A great opportunity to start a career in the lift and mobility engineering industry, with training provided.' : 'A specialist role for engineers who want to work in a growing, people-focused industry.';
		$extra = lmc_job_meta( $job_id, 'salary_note' );
		$vars  = array(
			'{title}'    => lmc_plain_title( $job_id ),
			'{employer}' => lmc_job_meta( $job_id, 'employer_name' ),
			'{location}' => lmc_location_text( $job_id ),
			'{salary}'   => lmc_salary_text( $job_id ),
			'{extras}'   => $extra ? ' ' . $extra : '',
			'{sector}'   => strtolower( lmc_job_terms( $job_id, 'lmc_sector' )[0]->name ?? 'mobility lift' ),
			'{hook}'     => $hook,
			'{url}'      => add_query_arg( array( 'utm_source' => $platform, 'utm_medium' => 'social', 'utm_campaign' => 'vacancy' ), get_permalink( $job_id ) ),
			'{hashtags}' => self::hashtags( $job_id ),
		);
		return strtr( $tpl, $vars );
	}

	public static function on_published( $job_id ) {
		if ( ! lmc_opt( 'social_drafts' ) || ! lmc_job_meta( $job_id, 'social_ok' ) || LMC_Launch::is_demo_job( $job_id ) ) {
			return;
		}
		global $wpdb;
		foreach ( array( 'linkedin', 'facebook' ) as $pl ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . lmc_table( 'social' ) . ' WHERE job_id = %d AND platform = %s', $job_id, $pl ) );
			if ( $exists ) {
				continue;
			}
			$wpdb->insert( lmc_table( 'social' ), array( 'job_id' => $job_id, 'platform' => $pl, 'body' => self::build( $job_id, $pl, $job_id ), 'status' => 'draft', 'created_at' => lmc_mysql_time() ) );
			if ( lmc_opt( 'social_auto_send' ) && lmc_opt( 'social_webhook_url' ) ) {
				self::send( (int) $wpdb->insert_id );
			}
		}
	}

	/** Push an approved post to the configured automation webhook (Zapier/Make/Buffer). */
	public static function send( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'social' ) . ' WHERE id = %d', $id ) );
		$url = lmc_opt( 'social_webhook_url' );
		if ( ! $row || ! $url ) {
			return false;
		}
		if ( $row->job_id && ! lmc_job_meta( $row->job_id, 'social_ok' ) ) {
			lmc_log( 'error', 'social', "Refused to send post $id: employer has not given social permission." );
			return false;
		}
		$res = wp_remote_post( $url, array( 'timeout' => 10, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'platform' => $row->platform, 'text' => $row->body, 'job_url' => $row->job_id ? get_permalink( $row->job_id ) : '', 'image' => get_stylesheet_directory_uri() . '/assets/img/og-default.png' ) ) ) );
		if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 300 ) {
			lmc_log( 'error', 'social', 'Social webhook failed for post ' . $id );
			return false;
		}
		$wpdb->update( lmc_table( 'social' ), array( 'status' => 'posted', 'posted_at' => lmc_mysql_time() ), array( 'id' => $id ) );
		return true;
	}
}
LMC_Social::init();
