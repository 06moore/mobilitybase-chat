<?php
defined( 'ABSPATH' ) || exit;

class LMC_Install {

	public static function activate() {
		self::tables();
		self::roles();
		LMC_Post_Types::register();
		self::terms();
		self::index_policy();
		self::pages();
		LMC_Cron::schedule();
		update_option( 'lmc_db_version', LMC_DB_VERSION );
		if ( ! get_option( 'lmc_settings' ) ) {
			update_option( 'lmc_settings', lmc_settings_defaults() );
		}
		flush_rewrite_rules();
	}

	public static function deactivate() {
		LMC_Cron::unschedule();
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( 'lmc_db_version' ) !== LMC_DB_VERSION ) {
			self::tables();
			self::roles();
			self::terms();
			self::index_policy();
			self::pages();
			flush_rewrite_rules( false );
			update_option( 'lmc_db_version', LMC_DB_VERSION );
		}
	}

	public static function tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$t = function ( $n ) {
			return lmc_table( $n );
		};
		$sql = array();

		$sql[] = "CREATE TABLE {$t('payments')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			job_id bigint(20) unsigned NOT NULL DEFAULT 0,
			package varchar(20) NOT NULL DEFAULT '',
			purpose varchar(20) NOT NULL DEFAULT 'new',
			net int(11) NOT NULL DEFAULT 0,
			vat int(11) NOT NULL DEFAULT 0,
			total int(11) NOT NULL DEFAULT 0,
			currency varchar(3) NOT NULL DEFAULT 'gbp',
			method varchar(20) NOT NULL DEFAULT 'stripe',
			stripe_session varchar(255) NOT NULL DEFAULT '',
			stripe_intent varchar(255) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			mode varchar(10) NOT NULL DEFAULT 'test',
			customer varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			paid_at datetime NULL,
			PRIMARY KEY  (id),
			KEY stripe_session (stripe_session),
			KEY user_id (user_id),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$t('credits')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			tier varchar(20) NOT NULL DEFAULT 'standard',
			quantity int(11) NOT NULL DEFAULT 1,
			remaining int(11) NOT NULL DEFAULT 1,
			source varchar(20) NOT NULL DEFAULT 'purchase',
			payment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			note varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			expires_at datetime NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) $c;";

		$sql[] = "CREATE TABLE {$t('log')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			level varchar(10) NOT NULL DEFAULT 'info',
			context varchar(40) NOT NULL DEFAULT '',
			message text NOT NULL,
			resolved tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY level (level)
		) $c;";

		$sql[] = "CREATE TABLE {$t('email_log')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipient varchar(190) NOT NULL,
			template varchar(40) NOT NULL,
			subject varchar(255) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(10) NOT NULL DEFAULT 'sent',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY template (template)
		) $c;";

		$sql[] = "CREATE TABLE {$t('stats')} (
			day date NOT NULL,
			job_id bigint(20) unsigned NOT NULL,
			views int(11) NOT NULL DEFAULT 0,
			clicks int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,job_id)
		) $c;";

		$sql[] = "CREATE TABLE {$t('sources')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			prospect_id bigint(20) unsigned NOT NULL DEFAULT 0,
			company varchar(190) NOT NULL,
			careers_url varchar(500) NOT NULL DEFAULT '',
			terms_reviewed tinyint(1) NOT NULL DEFAULT 0,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			interval_days int(11) NOT NULL DEFAULT 2,
			status varchar(30) NOT NULL DEFAULT 'awaiting_review',
			status_note varchar(500) NOT NULL DEFAULT '',
			last_checked datetime NULL,
			last_found int(11) NOT NULL DEFAULT 0,
			fail_count int(11) NOT NULL DEFAULT 0,
			notes text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $c;";

		$sql[] = "CREATE TABLE {$t('opportunities')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_id bigint(20) unsigned NOT NULL DEFAULT 0,
			company varchar(190) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			location varchar(190) NOT NULL DEFAULT '',
			url varchar(500) NOT NULL DEFAULT '',
			fingerprint varchar(64) NOT NULL,
			keywords varchar(255) NOT NULL DEFAULT '',
			score int(11) NOT NULL DEFAULT 0,
			first_seen datetime NOT NULL,
			last_seen datetime NOT NULL,
			still_live tinyint(1) NOT NULL DEFAULT 1,
			status varchar(20) NOT NULL DEFAULT 'new',
			job_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notes text NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY fingerprint (fingerprint),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$t('prospects')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			company varchar(190) NOT NULL,
			entity_type varchar(30) NOT NULL DEFAULT 'unknown',
			company_number varchar(20) NOT NULL DEFAULT '',
			sector varchar(190) NOT NULL DEFAULT '',
			website varchar(255) NOT NULL DEFAULT '',
			careers_url varchar(500) NOT NULL DEFAULT '',
			contact_name varchar(190) NOT NULL DEFAULT '',
			contact_role varchar(190) NOT NULL DEFAULT '',
			contact_email varchar(190) NOT NULL DEFAULT '',
			phone varchar(50) NOT NULL DEFAULT '',
			town varchar(100) NOT NULL DEFAULT '',
			region varchar(100) NOT NULL DEFAULT '',
			status varchar(30) NOT NULL DEFAULT 'researching',
			permission_status varchar(30) NOT NULL DEFAULT 'none',
			consent_marketing tinyint(1) NOT NULL DEFAULT 0,
			consent_note varchar(255) NOT NULL DEFAULT '',
			vacancies_seen int(11) NOT NULL DEFAULT 0,
			free_listing_used tinyint(1) NOT NULL DEFAULT 0,
			employer_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			last_contacted datetime NULL,
			follow_up date NULL,
			contact_count int(11) NOT NULL DEFAULT 0,
			source varchar(30) NOT NULL DEFAULT 'research',
			notes text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$t('suppression')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			reason varchar(100) NOT NULL DEFAULT 'unsubscribed',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) $c;";

		$sql[] = "CREATE TABLE {$t('social')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			job_id bigint(20) unsigned NOT NULL DEFAULT 0,
			platform varchar(20) NOT NULL,
			body text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			created_at datetime NOT NULL,
			posted_at datetime NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$t('subscribers')} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			sectors varchar(500) NOT NULL DEFAULT '',
			roles varchar(500) NOT NULL DEFAULT '',
			location varchar(100) NOT NULL DEFAULT '',
			lat decimal(9,6) NULL,
			lng decimal(9,6) NULL,
			radius int(11) NOT NULL DEFAULT 25,
			frequency varchar(10) NOT NULL DEFAULT 'daily',
			status varchar(15) NOT NULL DEFAULT 'pending',
			token varchar(64) NOT NULL,
			source varchar(40) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			confirmed_at datetime NULL,
			last_sent datetime NULL,
			sent_count int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email),
			KEY status (status)
		) $c;";

		$sql[] = "CREATE TABLE {$t('traffic')} (
			day date NOT NULL,
			metric varchar(30) NOT NULL,
			dim varchar(190) NOT NULL DEFAULT '',
			n int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,metric,dim)
		) $c;";

		foreach ( $sql as $q ) {
			dbDelta( $q );
		}
	}

	/**
	 * Employers get the bare minimum capability. They deliberately do NOT get upload_files:
	 * that would let them upload arbitrary media through the WordPress REST API. Logo uploads
	 * go through our own validated form handler instead.
	 */
	public static function roles() {
		add_role( 'lmc_employer', 'Employer', array( 'read' => true ) );
		$role = get_role( 'lmc_employer' );
		if ( $role ) {
			foreach ( array_keys( $role->capabilities ) as $cap ) {
				if ( 'read' !== $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	public static function terms() {
		require LMC_DIR . 'data/taxonomy-seed.php';
		foreach ( lmc_seed_sectors() as $slug => $s ) {
			if ( ! term_exists( $slug, 'lmc_sector' ) ) {
				$r = wp_insert_term( $s['name'], 'lmc_sector', array( 'slug' => $slug, 'description' => $s['intro'] ) );
				if ( ! is_wp_error( $r ) ) {
					update_term_meta( $r['term_id'], 'lmc_seo_title', $s['seo_title'] );
					update_term_meta( $r['term_id'], 'lmc_seo_desc', $s['seo_desc'] );
					update_term_meta( $r['term_id'], 'lmc_icon', $s['icon'] );
					update_term_meta( $r['term_id'], 'lmc_order', $s['order'] );
				}
			}
		}
		foreach ( lmc_seed_roles() as $slug => $r ) {
			if ( ! term_exists( $slug, 'lmc_role' ) ) {
				$res = wp_insert_term( $r['name'], 'lmc_role', array( 'slug' => $slug, 'description' => $r['intro'] ) );
				if ( ! is_wp_error( $res ) && ! empty( $r['seo_title'] ) ) {
					update_term_meta( $res['term_id'], 'lmc_seo_title', $r['seo_title'] );
					update_term_meta( $res['term_id'], 'lmc_seo_desc', $r['seo_desc'] );
				}
			}
		}
		foreach ( array( 'full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'temporary' => 'Temporary', 'apprenticeship' => 'Apprenticeship' ) as $slug => $n ) {
			if ( ! term_exists( $slug, 'lmc_type' ) ) {
				wp_insert_term( $n, 'lmc_type', array( 'slug' => $slug ) );
			}
		}
		foreach ( array( 'entry-level' => 'Entry level / trainee', 'experienced' => 'Experienced', 'senior' => 'Senior / specialist', 'management' => 'Management' ) as $slug => $n ) {
			if ( ! term_exists( $slug, 'lmc_experience' ) ) {
				wp_insert_term( $n, 'lmc_experience', array( 'slug' => $slug ) );
			}
		}
		require_once LMC_DIR . 'data/uk-locations.php';
		foreach ( lmc_uk_locations() as $region => $counties ) {
			$parent = term_exists( sanitize_title( $region ), 'lmc_location' );
			if ( ! $parent ) {
				$parent = wp_insert_term( $region, 'lmc_location', array( 'slug' => sanitize_title( $region ) ) );
			}
			if ( is_wp_error( $parent ) ) {
				continue;
			}
			$pid = (int) ( is_array( $parent ) ? $parent['term_id'] : $parent );
			foreach ( $counties as $county ) {
				$slug = sanitize_title( $county );
				if ( ! term_exists( $slug, 'lmc_location' ) ) {
					wp_insert_term( $county, 'lmc_location', array( 'slug' => $slug, 'parent' => $pid ) );
				}
			}
		}
	}

	/** Which landing pages are always indexable. Others need ≥ index_threshold live vacancies. */
	public static function index_policy() {
		$always = array(
			'lmc_sector' => array( 'stairlifts', 'home-lifts', 'through-floor-lifts', 'platform-lifts', 'ceiling-track-hoists', 'patient-handling' ),
			'lmc_role'   => array( 'installation-engineer', 'service-engineer', 'loler-inspector', 'trainee-engineer', 'apprentice' ),
		);
		foreach ( $always as $tax => $slugs ) {
			foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) as $t ) {
				if ( in_array( $t->slug, $slugs, true ) ) {
					update_term_meta( $t->term_id, 'lmc_index', 'always' );
				} else {
					delete_term_meta( $t->term_id, 'lmc_index' );
				}
			}
		}
	}

	/** Create the site's pages (only if they don't already exist). */
	public static function pages() {
		require_once LMC_DIR . 'data/pages-seed.php';
		foreach ( lmc_seed_pages() as $key => $p ) {
			$existing = (int) get_option( 'lmc_page_' . $key );
			if ( $existing && get_post( $existing ) ) {
				continue;
			}
			$parent = 0;
			if ( ! empty( $p['parent'] ) ) {
				$parent = (int) get_option( 'lmc_page_' . $p['parent'] );
			}
			// Adopt a page already at this address (e.g. WordPress's own draft Privacy Policy) instead of
			// creating "privacy-policy-2". Drafts get our content; published pages are left as they are.
			$path     = ( $parent ? get_page_uri( $parent ) . '/' : '' ) . $p['slug'];
			$existing = get_page_by_path( $path );
			if ( $existing ) {
				if ( 'publish' !== $existing->post_status ) {
					wp_update_post( array( 'ID' => $existing->ID, 'post_status' => 'publish', 'post_title' => $p['title'], 'post_content' => $p['content'] ) );
				}
				update_option( 'lmc_page_' . $key, $existing->ID );
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $p['title'],
					'post_name'    => $p['slug'],
					'post_content' => $p['content'],
					'post_parent'  => $parent,
					'menu_order'   => $p['order'] ?? 0,
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				update_option( 'lmc_page_' . $key, $id );
				foreach ( array( 'seo_title', 'seo_desc', 'noindex', 'template' ) as $m ) {
					if ( isset( $p[ $m ] ) ) {
						update_post_meta( $id, 'template' === $m ? '_wp_page_template' : '_lmc_' . $m, $p[ $m ] );
					}
				}
			}
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) get_option( 'lmc_page_home' ) );
		update_option( 'wp_page_for_privacy_policy', (int) get_option( 'lmc_page_privacy' ) );
		update_option( 'users_can_register', 0 ); // Employers register through our own form only.
		update_option( 'default_comment_status', 'closed' );
		update_option( 'blogdescription', 'Connecting mobility engineering talent with opportunity.' );
	}
}
