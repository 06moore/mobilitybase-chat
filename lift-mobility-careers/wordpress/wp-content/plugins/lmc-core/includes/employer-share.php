<?php
/**
 * Employer sharing & backlinks:
 *  - Public company pages (/companies/{slug}/) listing an employer's live vacancies – a stable page
 *    employers can link to from their own careers page.
 *  - Share kit for every vacancy: tracked links, one-click share buttons, ready-written LinkedIn post,
 *    and an "Advertised on Lift and Mobility Careers" badge snippet.
 *  - Backlink reward: when an employer adds a link to us on their website, we verify it automatically
 *    and (optionally) give a one-off free Featured upgrade credit. Links are re-checked monthly.
 */
defined( 'ABSPATH' ) || exit;

class LMC_Employer_Share {

	public static function init() {
		add_action( 'init', function () {
			add_rewrite_rule( '^companies/([^/]+)/?$', 'index.php?lmc_company=$matches[1]', 'top' );
		} );
		add_filter( 'query_vars', function ( $v ) {
			$v[] = 'lmc_company';
			return $v;
		} );
		add_filter( 'template_include', array( __CLASS__, 'template' ) );
		add_action( 'lmc_post_lmc_verify_backlink', array( __CLASS__, 'do_verify' ) );
		add_action( 'lmc_employer_registered', array( __CLASS__, 'ensure_slug' ) );
		add_action( 'lmc_job_published', function ( $job_id ) {
			LMC_Employer_Share::ensure_slug( (int) get_post_field( 'post_author', $job_id ) );
		} );
		add_action( 'wp_sitemaps_init', function ( $s ) {
			$s->registry->add_provider( 'companies', new LMC_Company_Sitemap() );
		} );
		add_filter( 'lmc_seo_context', array( __CLASS__, 'seo' ) );
		add_action( 'lmc_monthly', array( __CLASS__, 'recheck_all' ) );
	}

	public static function ensure_slug( $uid ) {
		$slug = get_user_meta( $uid, 'lmc_company_slug', true );
		if ( $slug ) {
			return $slug;
		}
		$base = sanitize_title( get_user_meta( $uid, 'lmc_company', true ) ) ?: 'employer-' . $uid;
		$slug = $base;
		$i    = 1;
		while ( self::user_by_slug( $slug ) ) {
			$slug = $base . '-' . ( ++$i );
		}
		update_user_meta( $uid, 'lmc_company_slug', $slug );
		return $slug;
	}

	public static function user_by_slug( $slug ) {
		$u = get_users( array( 'meta_key' => 'lmc_company_slug', 'meta_value' => $slug, 'number' => 1, 'fields' => 'ID' ) );
		return $u ? (int) $u[0] : 0;
	}

	public static function company_url( $uid ) {
		return home_url( '/companies/' . self::ensure_slug( $uid ) . '/' );
	}

	public static function current_company() {
		$slug = get_query_var( 'lmc_company' );
		return $slug ? self::user_by_slug( sanitize_title( $slug ) ) : 0;
	}

	public static function live_jobs( $uid ) {
		return get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'author' => $uid, 'numberposts' => 50, 'meta_key' => '_lmc_priority', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) );
	}

	public static function template( $tpl ) {
		if ( ! get_query_var( 'lmc_company' ) ) {
			return $tpl;
		}
		$uid = self::current_company();
		if ( ! $uid ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return get_404_template();
		}
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
		$theme = locate_template( 'company.php' );
		return $theme ?: $tpl;
	}

	public static function seo( $c ) {
		$uid = get_query_var( 'lmc_company' ) ? self::current_company() : 0;
		if ( ! $uid ) {
			return $c;
		}
		$name = get_user_meta( $uid, 'lmc_company', true );
		$n    = count( self::live_jobs( $uid ) );
		$c['title']     = $name . ' Jobs & Careers';
		$c['desc']      = $n ? "$name is recruiting: $n live engineering " . _n( 'vacancy', 'vacancies', $n ) . ' on Lift and Mobility Careers. See salaries and apply direct.' : "Engineering vacancies at $name on Lift and Mobility Careers.";
		$c['canonical'] = self::company_url( $uid );
		$c['noindex']   = 0 === $n;
		return $c;
	}

	/* ---------- Share kit ---------- */

	public static function tracked_url( $job_id, $source = 'employer' ) {
		return add_query_arg( array( 'utm_source' => $source, 'utm_medium' => 'share' ), get_permalink( $job_id ) );
	}

	public static function share_links( $url, $title ) {
		$u = rawurlencode( $url );
		$t = rawurlencode( $title );
		return array(
			'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u ),
			'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u ),
			'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $t . '%20' . $u ),
			'x'        => array( 'X', 'https://x.com/intent/post?text=' . $t . '&url=' . $u ),
			'email'    => array( 'Email', 'mailto:?subject=' . $t . '&body=' . rawurlencode( "Thought you might be interested in this job:\n\n" . $title . "\n" . $url ) ),
		);
	}

	public static function badge_html( $uid, $job_id = 0 ) {
		$href = $job_id ? self::tracked_url( $job_id, 'badge' ) : add_query_arg( array( 'utm_source' => 'badge', 'utm_medium' => 'referral' ), self::company_url( $uid ) );
		$img  = get_template_directory_uri() . '/assets/img/badge-advertised.svg';
		return '<a href="' . esc_url( $href ) . '" title="Engineering vacancies on Lift and Mobility Careers"><img src="' . esc_url( $img ) . '" alt="Advertised on Lift and Mobility Careers" width="220" height="60"></a>';
	}

	public static function text_link_html( $uid ) {
		return '<a href="' . esc_url( add_query_arg( array( 'utm_source' => 'employer', 'utm_medium' => 'referral' ), self::company_url( $uid ) ) ) . '">See our engineering vacancies on Lift and Mobility Careers</a>';
	}

	public static function render_kit( $uid, $job_id = 0 ) {
		$title = $job_id ? lmc_plain_title( $job_id ) . ' – ' . lmc_location_text( $job_id ) : get_user_meta( $uid, 'lmc_company', true ) . ' – engineering vacancies';
		$url   = $job_id ? self::tracked_url( $job_id ) : add_query_arg( 'utm_source', 'employer', self::company_url( $uid ) );
		$post  = $job_id
			? "We're hiring: " . lmc_plain_title( $job_id ) . ' – ' . lmc_location_text( $job_id ) . "\n" . lmc_salary_text( $job_id ) . ( lmc_job_meta( $job_id, 'salary_note' ) ? ' ' . lmc_job_meta( $job_id, 'salary_note' ) : '' ) . "\n\nJoin our team – full details and how to apply:\n" . $url . "\n\n" . LMC_Social::hashtags( $job_id ) . ' #Hiring'
			: "We're hiring engineers! See our current vacancies and apply directly:\n" . $url . "\n\n#Hiring #EngineeringJobs";
		$out   = '<div class="lmc-share-kit">';
		$out  .= '<h3>1. Share it</h3><p>Posts from your own company page and team reach engineers who already know your name.</p><div class="lmc-share">' . self::buttons( $url, $title, $job_id ) . '</div>';
		$out  .= '<h3>2. Ready-written LinkedIn post</h3><textarea class="lmc-copy" rows="8" readonly>' . esc_textarea( $post ) . '</textarea><button type="button" class="lmc-btn lmc-btn--ghost lmc-btn--small" data-lmc-copy>Copy text</button>';
		$out  .= '<h3>3. Add our badge to your careers page</h3><p>Paste this into your website so visitors can see all your vacancies in one place:</p><p>' . self::badge_html( $uid, 0 ) . '</p><textarea class="lmc-copy" rows="3" readonly>' . esc_textarea( self::badge_html( $uid, 0 ) ) . '</textarea><button type="button" class="lmc-btn lmc-btn--ghost lmc-btn--small" data-lmc-copy>Copy badge code</button>';
		$out  .= '<p class="lmc-muted">Prefer a plain link?</p><textarea class="lmc-copy" rows="2" readonly>' . esc_textarea( self::text_link_html( $uid ) ) . '</textarea>';
		$out  .= '<p class="lmc-muted">Your public company page: <a href="' . esc_url( self::company_url( $uid ) ) . '">' . esc_html( self::company_url( $uid ) ) . '</a></p>';
		$out  .= self::verify_form( $uid );
		return $out . '</div>';
	}

	public static function buttons( $url, $title, $job_id = 0 ) {
		$out = '';
		foreach ( self::share_links( $url, $title ) as $k => $l ) {
			$out .= '<a class="lmc-share__btn lmc-share__btn--' . esc_attr( $k ) . '" href="' . esc_url( $l[1] ) . '" target="_blank" rel="noopener nofollow" data-lmc-share="' . esc_attr( $k ) . '">' . esc_html( $l[0] ) . '</a>';
		}
		$out .= '<button type="button" class="lmc-share__btn" data-lmc-share="copy" data-url="' . esc_attr( $url ) . '">Copy link</button>';
		return $out;
	}

	/* ---------- Backlink verification ---------- */

	public static function verify_form( $uid ) {
		$links = (array) get_user_meta( $uid, 'lmc_backlinks', true );
		$out   = '<h3>4. Tell us where you added it</h3>';
		if ( lmc_opt( 'backlink_bonus' ) && ! get_user_meta( $uid, 'lmc_backlink_bonus', true ) ) {
			$out .= '<p><strong>Thank-you offer:</strong> add a link to us on your website and we\'ll give you a free upgrade to a Featured advert (one per employer).</p>';
		}
		foreach ( array_filter( $links ) as $u => $info ) {
			$out .= '<p>' . ( ! empty( $info['ok'] ) ? '✅' : '⚠️' ) . ' <a href="' . esc_url( $u ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $u ) . '</a> – ' . esc_html( ! empty( $info['ok'] ) ? 'link found ' . lmc_date( $info['checked'] ) : 'link not found when checked ' . lmc_date( $info['checked'] ) ) . '</p>';
		}
		return $out . '<form method="post" class="lmc-inline-form">' . LMC_Forms::hidden( 'lmc_verify_backlink' ) . '<label class="screen-reader-text" for="lmc-bl">Page URL</label><input id="lmc-bl" type="url" name="page_url" placeholder="https://yourcompany.co.uk/careers" required style="max-width:420px;padding:10px;border:1px solid #C5CFD5;border-radius:6px"> <button class="lmc-btn lmc-btn--primary lmc-btn--small" type="submit">Check my link</button></form>';
	}

	/** Does the page at $url contain a link to our site? */
	public static function page_links_to_us( $url ) {
		$res = wp_remote_get( $url, array( 'timeout' => 12, 'redirection' => 3, 'user-agent' => LMC_Discovery::user_agent() ) );
		if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) >= 400 ) {
			return false;
		}
		$host = preg_quote( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ), '#' );
		return (bool) preg_match( '#<a\s[^>]*href=["\']https?://(www\.)?' . $host . '(:\d+)?[/"\'?]#i', wp_remote_retrieve_body( $res ) );
	}

	public static function check( $uid, $url ) {
		$ok    = self::page_links_to_us( $url );
		$links = (array) get_user_meta( $uid, 'lmc_backlinks', true );
		$links[ $url ] = array( 'ok' => $ok, 'checked' => lmc_now() );
		update_user_meta( $uid, 'lmc_backlinks', array_filter( $links ) );
		$bonus = false;
		if ( $ok && lmc_opt( 'backlink_bonus' ) && ! get_user_meta( $uid, 'lmc_backlink_bonus', true ) ) {
			update_user_meta( $uid, 'lmc_backlink_bonus', lmc_now() );
			LMC_Credits::add( $uid, 'featured', 1, 'backlink', 0, 'Thank you for linking to us', 365 );
			$bonus = true;
		}
		if ( $ok ) {
			LMC_Analytics::bump( 'backlink_verified', (string) wp_parse_url( $url, PHP_URL_HOST ) );
		}
		return array( $ok, $bonus );
	}

	public static function do_verify() {
		if ( ! is_user_logged_in() || ! lmc_is_employer() || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ?? '' ) ), 'lmc_verify_backlink' ) ) {
			return;
		}
		$url = esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) );
		if ( ! wp_http_validate_url( $url ) && ! defined( 'LMC_TESTING' ) ) {
			lmc_flash( 'Please enter the full web address of the page, starting https://', 'error' );
		} else {
			list( $ok, $bonus ) = self::check( get_current_user_id(), $url );
			lmc_flash( $ok ? 'Thank you – we found your link.' . ( $bonus ? ' A free <strong>Featured</strong> advert credit has been added to your account.' : '' ) : 'We couldn\'t find a link to Lift and Mobility Careers on that page yet. It can take a little while for website changes to go live – try again later.', $ok ? 'success' : 'info' );
		}
		wp_safe_redirect( lmc_page_url( 'dashboard', array( 'tab' => 'share' ) ) );
		exit;
	}

	/** Monthly: re-check every recorded backlink so reports reflect links that still exist. */
	public static function recheck_all() {
		$total = 0;
		foreach ( get_users( array( 'meta_key' => 'lmc_backlinks', 'fields' => 'ID', 'number' => -1 ) ) as $uid ) {
			$links = (array) get_user_meta( $uid, 'lmc_backlinks', true );
			foreach ( array_keys( array_filter( $links ) ) as $u ) {
				$ok            = self::page_links_to_us( $u );
				$links[ $u ]   = array( 'ok' => $ok, 'checked' => lmc_now() );
				$total        += $ok ? 1 : 0;
			}
			update_user_meta( $uid, 'lmc_backlinks', $links );
		}
		update_option( 'lmc_live_backlinks', $total, false );
		return $total;
	}

	public static function live_backlink_count() {
		$n = 0;
		foreach ( get_users( array( 'meta_key' => 'lmc_backlinks', 'fields' => 'ID', 'number' => -1 ) ) as $uid ) {
			foreach ( (array) get_user_meta( $uid, 'lmc_backlinks', true ) as $info ) {
				$n += ! empty( $info['ok'] ) ? 1 : 0;
			}
		}
		return $n;
	}
}
LMC_Employer_Share::init();

if ( class_exists( 'WP_Sitemaps_Provider' ) || ( require_once ABSPATH . WPINC . '/sitemaps/class-wp-sitemaps-provider.php' ) ) {
	/** Sitemap of company pages that currently have live vacancies. */
	class LMC_Company_Sitemap extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'companies';
			$this->object_type = 'company';
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			$out = array();
			foreach ( get_users( array( 'role' => 'lmc_employer', 'fields' => 'ID', 'number' => -1 ) ) as $uid ) {
				if ( LMC_Employer_Share::live_jobs( $uid ) ) {
					$out[] = array( 'loc' => LMC_Employer_Share::company_url( $uid ) );
				}
			}
			return $out;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
}
