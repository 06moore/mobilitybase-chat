<?php
/**
 * Vacancy discovery: polite monitoring of direct employers' careers pages.
 *
 * Rules built in:
 *  - A source is only fetched after the operator ticks "terms reviewed – monitoring permitted".
 *  - robots.txt is fetched and honoured for our user agent and "*".
 *  - One page per source per run, identified user agent, timeouts, back-off after failures.
 *  - Only title / location / link / first-seen are recorded. Advert text is never stored or republished.
 *  - Pages that can't be read reliably (JavaScript-only, blocked, unparseable) are FLAGGED, not faked.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Discovery {

	const ROLE_RE   = '/\b(engineer|technician|inspector|installer|fitter|surveyor|apprentice|trainee|supervisor|service manager|technical manager|operations manager|commissioning|loler|mechanic|electromechanical|field service)\b/i';
	const SECTOR_RE = '/\b(stair\s?lifts?|home\s?lifts?|platform\s?lifts?|through[- ]floor|access\s?lifts?|wheelchair\s?lifts?|step\s?lifts?|lifts?|hoists?|ceiling track|patient handling|mobility|loler|lifting equipment|accessibility)\b/i';
	const EXCLUDE_RE = '/\b(sales (executive|advisor|consultant|representative)|telesales|accounts?|finance|marketing|customer service advisor|receptionist|administrator|hr |human resources|driver only|warehouse operative)\b/i';

	public static function user_agent() {
		return 'LiftAndMobilityCareersBot/1.0 (+' . home_url( '/about-us/' ) . ')';
	}

	/** Relevance score for a vacancy title (and optional context). 0 = not relevant. */
	public static function score( $title, $context = '', $sector_company = true ) {
		if ( ! preg_match( self::ROLE_RE, $title, $m ) || preg_match( self::EXCLUDE_RE, $title ) ) {
			return array( 0, array() );
		}
		$kw    = array( strtolower( $m[0] ) );
		$score = 50;
		if ( preg_match_all( self::SECTOR_RE, $title . ' ' . $context, $sm ) ) {
			$kw     = array_merge( $kw, array_map( 'strtolower', $sm[0] ) );
			$score += 30;
		} elseif ( ! $sector_company ) {
			return array( 0, array() );
		}
		if ( preg_match( '/\b(service|install|maintenance|breakdown|repair|loler|commissioning)\b/i', $title, $mm ) ) {
			$score += 20;
			$kw[]   = strtolower( $mm[0] );
		}
		return array( min( 100, $score ), array_values( array_unique( $kw ) ) );
	}

	/* ---------- robots.txt ---------- */

	public static function robots_allowed( $url, $robots_txt ) {
		$path   = wp_parse_url( $url, PHP_URL_PATH ) ?: '/';
		$query  = wp_parse_url( $url, PHP_URL_QUERY );
		$path  .= $query ? '?' . $query : '';
		$groups = array();
		$agents = array();
		$last_was_agent = false;
		foreach ( preg_split( '/\r?\n/', (string) $robots_txt ) as $line ) {
			$line = trim( preg_replace( '/#.*$/', '', $line ) );
			if ( '' === $line || false === strpos( $line, ':' ) ) {
				continue;
			}
			list( $k, $v ) = array_map( 'trim', explode( ':', $line, 2 ) );
			$k = strtolower( $k );
			if ( 'user-agent' === $k ) {
				if ( ! $last_was_agent ) {
					$agents = array();
				}
				$agents[]       = strtolower( $v );
				$last_was_agent = true;
				foreach ( $agents as $a ) {
					$groups[ $a ] = $groups[ $a ] ?? array();
				}
				continue;
			}
			$last_was_agent = false;
			if ( in_array( $k, array( 'allow', 'disallow' ), true ) ) {
				foreach ( $agents as $a ) {
					$groups[ $a ][] = array( $k, $v );
				}
			}
		}
		$rules = $groups['liftandmobilitycareersbot'] ?? ( $groups['*'] ?? array() );
		$best  = array( 'allow', -1 );
		foreach ( $rules as $r ) {
			if ( '' === $r[1] ) {
				continue; // "Disallow:" (empty) means allow all.
			}
			$re = '#^' . str_replace( array( '\*', '\$' ), array( '.*', '$' ), preg_quote( $r[1], '#' ) ) . '#';
			if ( preg_match( $re, $path ) && strlen( $r[1] ) > $best[1] ) {
				$best = array( $r[0], strlen( $r[1] ) );
			}
		}
		return 'allow' === $best[0];
	}

	/* ---------- Fetch & parse ---------- */

	public static function fetch( $url ) {
		$res = wp_remote_get( $url, array(
			'timeout'     => 15,
			'redirection' => 3,
			'user-agent'  => self::user_agent(),
			'headers'     => array( 'Accept' => 'text/html,application/xhtml+xml' ),
		) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = wp_remote_retrieve_response_code( $res );
		if ( $code >= 400 ) {
			return new WP_Error( 'http', 'HTTP ' . $code );
		}
		return wp_remote_retrieve_body( $res );
	}

	/**
	 * Extract candidate vacancies from careers page HTML.
	 * Returns ['items' => [[title, location, url]], 'method' => 'jsonld'|'links', 'js_required' => bool]
	 */
	public static function parse( $html, $base_url ) {
		$items = array();
		// 1) Structured data – the most reliable signal.
		if ( preg_match_all( '#<script[^>]+application/ld\+json[^>]*>(.*?)</script>#is', $html, $m ) ) {
			foreach ( $m[1] as $json ) {
				$data = json_decode( html_entity_decode( trim( $json ) ), true );
				if ( ! $data ) {
					continue;
				}
				$nodes = isset( $data['@graph'] ) ? $data['@graph'] : ( isset( $data[0] ) ? $data : array( $data ) );
				foreach ( $nodes as $n ) {
					if ( ( $n['@type'] ?? '' ) === 'JobPosting' && ! empty( $n['title'] ) ) {
						$loc = $n['jobLocation'] ?? array();
						$loc = isset( $loc[0] ) ? $loc[0] : $loc;
						$addr = $loc['address'] ?? array();
						$items[] = array(
							'title'    => wp_strip_all_tags( $n['title'] ),
							'location' => trim( implode( ', ', array_filter( array( $addr['addressLocality'] ?? '', $addr['addressRegion'] ?? '' ) ) ) ),
							'url'      => self::abs_url( $n['url'] ?? $base_url, $base_url ),
						);
					}
				}
			}
		}
		if ( $items ) {
			return array( 'items' => $items, 'method' => 'jsonld', 'js_required' => false );
		}
		// 2) Links whose text looks like a relevant job title.
		$text_len = strlen( trim( wp_strip_all_tags( preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', $html ) ) ) );
		if ( preg_match_all( '#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', $html, $links, PREG_SET_ORDER ) ) {
			foreach ( $links as $l ) {
				$title = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $l[2] ) ) ) );
				if ( strlen( $title ) < 6 || strlen( $title ) > 120 || ! preg_match( self::ROLE_RE, $title ) ) {
					continue;
				}
				$location = '';
				if ( preg_match( '/^(.*?)\s+[-–|]\s+([A-Z][A-Za-z .\']+(?:,\s*[A-Z][A-Za-z .\']+)?)$/u', $title, $tm ) ) {
					$title    = $tm[1];
					$location = $tm[2];
				}
				$items[] = array( 'title' => $title, 'location' => $location, 'url' => self::abs_url( $l[1], $base_url ) );
			}
		}
		$js = ! $items && ( $text_len < 400 || preg_match( '#(id=["\'](root|app|__next)["\'])|workday|myworkdayjobs|greenhouse\.io|lever\.co|teamtailor|bamboohr|smartrecruiters|recruitee|pinpoint|<iframe#i', $html ) );
		return array( 'items' => $items, 'method' => 'links', 'js_required' => (bool) $js );
	}

	public static function abs_url( $href, $base ) {
		$href = html_entity_decode( trim( $href ) );
		if ( preg_match( '#^https?://#i', $href ) ) {
			return $href;
		}
		$p = wp_parse_url( $base );
		$root = $p['scheme'] . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
		if ( 0 === strpos( $href, '//' ) ) {
			return $p['scheme'] . ':' . $href;
		}
		if ( 0 === strpos( $href, '/' ) ) {
			return $root . $href;
		}
		$dir = isset( $p['path'] ) ? preg_replace( '#/[^/]*$#', '/', $p['path'] ) : '/';
		return $root . $dir . $href;
	}

	/* ---------- Run ---------- */

	public static function check_source( $src ) {
		global $wpdb;
		$st  = lmc_table( 'sources' );
		$now = lmc_mysql_time();
		$upd = function ( $status, $note, $found = 0, $fail = false ) use ( $wpdb, $st, $src, $now ) {
			$wpdb->update( $st, array(
				'status'       => $status,
				'status_note'  => mb_substr( $note, 0, 500 ),
				'last_checked' => $now,
				'last_found'   => $found,
				'fail_count'   => $fail ? (int) $src->fail_count + 1 : 0,
			), array( 'id' => $src->id ) );
		};
		if ( ! $src->terms_reviewed ) {
			$upd( 'awaiting_review', 'Tick "terms reviewed" once you have checked the site\'s terms allow automated checking.' );
			return 'awaiting_review';
		}
		if ( ! wp_http_validate_url( $src->careers_url ) ) {
			$upd( 'error', 'Careers URL is missing or invalid.', 0, true );
			return 'error';
		}
		$p      = wp_parse_url( $src->careers_url );
		$robots = self::fetch( $p['scheme'] . '://' . $p['host'] . '/robots.txt' );
		if ( ! is_wp_error( $robots ) && ! self::robots_allowed( $src->careers_url, $robots ) ) {
			$upd( 'blocked_robots', 'robots.txt disallows automated access to this page. Check it manually or ask the employer for a feed.' );
			return 'blocked_robots';
		}
		$html = self::fetch( $src->careers_url );
		if ( is_wp_error( $html ) ) {
			$upd( 'error', 'Could not fetch page: ' . $html->get_error_message(), 0, true );
			return 'error';
		}
		$parsed = self::parse( $html, $src->careers_url );
		if ( ! $parsed['items'] && $parsed['js_required'] ) {
			$upd( 'js_required', 'Page loads vacancies with JavaScript or an embedded applicant tracking system, so it cannot be read reliably. Check manually, or look for the ATS\'s public job feed.' );
			return 'js_required';
		}
		$found = 0;
		$seen  = array();
		foreach ( $parsed['items'] as $it ) {
			list( $score, $kw ) = self::score( $it['title'], '', true );
			if ( ! $score ) {
				continue;
			}
			$fp     = hash( 'sha256', strtolower( $src->id . '|' . $it['title'] . '|' . $it['url'] ) );
			$seen[] = $fp;
			$found++;
			self::upsert_opportunity( $src, $it, $fp, $score, $kw );
		}
		// Anything previously live for this source that wasn't seen this time is marked gone.
		$ot   = lmc_table( 'opportunities' );
		$live = $wpdb->get_col( $wpdb->prepare( "SELECT fingerprint FROM $ot WHERE source_id = %d AND still_live = 1", $src->id ) );
		foreach ( array_diff( $live, $seen ) as $gone ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $ot SET still_live = 0, status = CASE WHEN status = 'new' THEN 'gone' ELSE status END WHERE fingerprint = %s", $gone ) );
		}
		if ( ! $parsed['items'] ) {
			$upd( 'no_parse', 'Page fetched but no vacancy links were recognised. It may have no current vacancies, or use an unusual layout – check manually once.' );
			return 'no_parse';
		}
		$upd( 'ok', 'Read via ' . ( 'jsonld' === $parsed['method'] ? 'structured data' : 'page links' ) . '; ' . $found . ' relevant of ' . count( $parsed['items'] ) . ' listed.', $found );
		if ( $src->prospect_id ) {
			$wpdb->update( lmc_table( 'prospects' ), array( 'vacancies_seen' => $found, 'updated_at' => $now ), array( 'id' => $src->prospect_id ) );
		}
		return 'ok';
	}

	private static function upsert_opportunity( $src, $it, $fp, $score, $kw ) {
		global $wpdb;
		$ot  = lmc_table( 'opportunities' );
		$now = lmc_mysql_time();
		$id  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $ot WHERE fingerprint = %s", $fp ) );
		if ( $id ) {
			$wpdb->update( $ot, array( 'last_seen' => $now, 'still_live' => 1 ), array( 'id' => $id ) );
			return;
		}
		$wpdb->insert( $ot, array(
			'source_id'   => $src->id,
			'company'     => $src->company,
			'title'       => mb_substr( $it['title'], 0, 255 ),
			'location'    => mb_substr( $it['location'], 0, 190 ),
			'url'         => esc_url_raw( $it['url'] ),
			'fingerprint' => $fp,
			'keywords'    => implode( ', ', $kw ),
			'score'       => $score,
			'first_seen'  => $now,
			'last_seen'   => $now,
			'still_live'  => 1,
			'status'      => 'new',
		) );
	}

	/** Daily run: check sources that are due, oldest first, up to the per-run cap. */
	public static function run() {
		global $wpdb;
		if ( ! lmc_opt( 'discovery_enabled' ) ) {
			return array();
		}
		$st      = lmc_table( 'sources' );
		$sources = $wpdb->get_results( "SELECT * FROM $st WHERE enabled = 1 ORDER BY (last_checked IS NOT NULL), last_checked ASC" );
		$done    = array();
		$max     = (int) lmc_opt( 'discovery_max_per_run' );
		foreach ( $sources as $s ) {
			if ( count( $done ) >= $max ) {
				break;
			}
			$due = ! $s->last_checked || strtotime( $s->last_checked . ' UTC' ) + ( max( 1, (int) $s->interval_days ) * DAY_IN_SECONDS ) - HOUR_IN_SECONDS <= lmc_now();
			// Back off: after 3 consecutive failures, only retry weekly.
			if ( $s->fail_count >= 3 && $s->last_checked && strtotime( $s->last_checked . ' UTC' ) + WEEK_IN_SECONDS > lmc_now() ) {
				$due = false;
			}
			if ( ! $due ) {
				continue;
			}
			$done[ $s->id ] = self::check_source( $s );
			if ( 'error' === $done[ $s->id ] && $s->fail_count + 1 >= 3 ) {
				lmc_log( 'error', 'discovery', "Careers page for {$s->company} has failed 3 times in a row. Check the URL." );
			}
		}
		lmc_heartbeat( 'discovery' );
		return $done;
	}

	/** Operator approved an opportunity with employer authorisation → create a draft vacancy. */
	public static function create_draft_from_opportunity( $opp_id ) {
		global $wpdb;
		$o = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . lmc_table( 'opportunities' ) . ' WHERE id = %d', $opp_id ) );
		if ( ! $o ) {
			return 0;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'lmc_job',
			'post_status'  => 'draft',
			'post_title'   => $o->title,
			'post_content' => '<p>[Write an ORIGINAL short summary of this role here – do not paste the employer\'s advert text. Send it to the employer to check before publishing.]</p>',
			'post_author'  => get_current_user_id() ?: 1,
		) );
		if ( $id ) {
			update_post_meta( $id, '_lmc_employer_name', $o->company );
			update_post_meta( $id, '_lmc_town', $o->location );
			update_post_meta( $id, '_lmc_apply_method', 'url' );
			update_post_meta( $id, '_lmc_apply_url', $o->url );
			update_post_meta( $id, '_lmc_source_opportunity', $o->id );
			update_post_meta( $id, '_lmc_tier', 'standard' );
			update_post_meta( $id, '_lmc_priority', 1 );
			$wpdb->update( lmc_table( 'opportunities' ), array( 'status' => 'authorised', 'job_id' => $id ), array( 'id' => $o->id ) );
		}
		return (int) $id;
	}
}
