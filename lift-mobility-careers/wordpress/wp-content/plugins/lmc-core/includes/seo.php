<?php
/**
 * SEO: titles, meta descriptions, canonicals, robots rules, Open Graph, JobPosting / Organization
 * structured data, robots.txt, XML sitemap tuning, Search Console verification and consent-gated GA4.
 * Deliberately self-contained so no separate SEO plugin is required (avoid running two).
 */
defined( 'ABSPATH' ) || exit;

class LMC_SEO {

	public static function init() {
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ) );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 10, 2 );
		add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
			return 'users' === $name ? false : $provider;
		}, 10, 2 );
		add_filter( 'wp_sitemaps_post_types', function ( $types ) {
			unset( $types['post'], $types['attachment'] );
			return $types;
		} );
		add_filter( 'wp_sitemaps_taxonomies', function ( $t ) {
			return array_intersect_key( $t, array_flip( array( 'lmc_sector', 'lmc_role', 'lmc_location' ) ) );
		} );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( __CLASS__, 'sitemap_terms' ), 10, 2 );
		add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $type ) {
			if ( 'page' === $type ) {
				$args['meta_query'] = array( array( 'key' => '_lmc_noindex', 'compare' => 'NOT EXISTS' ) );
			}
			return $args;
		}, 10, 2 );
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_footer', array( __CLASS__, 'consent_and_analytics' ), 50 );
	}

	public static function site() {
		return lmc_opt( 'business_name' );
	}

	/** Is this taxonomy term page substantial enough to index? */
	public static function term_indexable( $term ) {
		if ( 'lmc_sector' === $term->taxonomy ) {
			return true;
		}
		if ( 'lmc_role' === $term->taxonomy && get_term_meta( $term->term_id, 'lmc_seo_title', true ) ) {
			return true;
		}
		$n = count( get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => $term->taxonomy, 'terms' => $term->term_id, 'include_children' => true ) ) ) ) );
		return $n >= (int) lmc_opt( 'index_threshold' );
	}

	public static function sitemap_terms( $args, $tax ) {
		$ids = array();
		foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'all' ) ) as $t ) {
			if ( self::term_indexable( $t ) ) {
				$ids[] = $t->term_id;
			}
		}
		$args['include']    = $ids ?: array( 0 );
		$args['hide_empty'] = false;
		return $args;
	}

	public static function context() {
		$c = array( 'title' => '', 'desc' => '', 'canonical' => '', 'noindex' => false, 'image' => get_stylesheet_directory_uri() . '/assets/img/og-default.png', 'type' => 'website' );
		if ( is_front_page() ) {
			$id             = (int) get_option( 'page_on_front' );
			$c['title']     = get_post_meta( $id, '_lmc_seo_title', true ) ?: self::site();
			$c['desc']      = get_post_meta( $id, '_lmc_seo_desc', true );
			$c['canonical'] = home_url( '/' );
			$c['full']      = true;
		} elseif ( is_singular( 'lmc_job' ) ) {
			$id             = get_queried_object_id();
			$town           = lmc_job_meta( $id, 'town' );
			$c['title']     = get_the_title( $id ) . ( $town ? ' – ' . $town : '' ) . ' | ' . lmc_job_meta( $id, 'employer_name' );
			$c['desc']      = wp_trim_words( lmc_job_meta( $id, 'employer_name' ) . ' is recruiting a ' . get_the_title( $id ) . ' in ' . lmc_location_text( $id ) . '. ' . lmc_salary_text( $id ) . '. ' . wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 30, '…' );
			$c['canonical'] = get_permalink( $id );
			$c['noindex']   = 'publish' !== get_post_status( $id );
			$c['type']      = 'article';
			$logo           = lmc_job_meta( $id, 'logo_id' );
			if ( $logo ) {
				$c['image'] = wp_get_attachment_image_url( $logo, 'medium' );
			}
		} elseif ( is_singular() ) {
			$id             = get_queried_object_id();
			$c['title']     = get_post_meta( $id, '_lmc_seo_title', true ) ?: get_the_title( $id );
			$c['desc']      = get_post_meta( $id, '_lmc_seo_desc', true ) ?: wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ), 28, '…' );
			$c['canonical'] = get_permalink( $id );
			$c['noindex']   = (bool) get_post_meta( $id, '_lmc_noindex', true );
		} elseif ( is_tax( array( 'lmc_sector', 'lmc_role', 'lmc_location' ) ) ) {
			$t              = get_queried_object();
			$c['title']     = get_term_meta( $t->term_id, 'lmc_seo_title', true ) ?: ( 'lmc_location' === $t->taxonomy ? 'Lift & Stairlift Engineer Jobs in ' . $t->name : $t->name . ' Jobs – Lifts & Mobility Engineering UK' );
			$c['desc']      = get_term_meta( $t->term_id, 'lmc_seo_desc', true ) ?: wp_trim_words( $t->description ?: ( 'Live ' . strtolower( $t->name ) . ' vacancies in the stairlift, home lift, platform lift and patient handling sector.' ), 28, '…' );
			$c['canonical'] = get_term_link( $t );
			$c['noindex']   = ! self::term_indexable( $t );
		} elseif ( is_post_type_archive( 'lmc_job' ) ) {
			$c['title']     = 'Browse Stairlift, Lift & Hoist Engineer Jobs in the UK';
			$c['desc']      = 'Search all live vacancies for stairlift, home lift, platform lift, access lift and hoist engineers across the UK. Filter by location, sector, salary and job type.';
			$c['canonical'] = get_post_type_archive_link( 'lmc_job' );
		} elseif ( is_404() || is_search() ) {
			$c['noindex'] = true;
			$c['title']   = is_404() ? 'Page not found' : 'Search';
		} else {
			$c['noindex'] = true;
		}
		// Filtered or paged listing views: canonicalise to the clean URL; noindex filter combinations.
		if ( ( is_post_type_archive( 'lmc_job' ) || is_tax() ) ) {
			if ( LMC_Search::has_filters() ) {
				$c['noindex'] = true;
			}
			$paged = (int) get_query_var( 'paged' );
			if ( $paged > 1 && ! LMC_Search::has_filters() ) {
				$c['canonical'] = trailingslashit( $c['canonical'] ) . 'page/' . $paged . '/';
				$c['title']    .= ' – Page ' . $paged;
			}
		}
		return apply_filters( 'lmc_seo_context', $c );
	}

	public static function title( $t ) {
		$c = self::context();
		if ( ! $c['title'] ) {
			return $t;
		}
		return ! empty( $c['full'] ) || false !== strpos( $c['title'], self::site() ) ? $c['title'] : $c['title'] . ' | ' . self::site();
	}

	public static function robots( $r ) {
		$c = self::context();
		if ( $c['noindex'] ) {
			$r['noindex'] = true;
			$r['follow']  = true;
			unset( $r['max-image-preview'] );
		} else {
			$r['max-image-preview'] = 'large';
			$r['max-snippet']       = '-1';
		}
		return $r;
	}

	public static function head() {
		$c = self::context();
		if ( $c['desc'] ) {
			echo '<meta name="description" content="' . esc_attr( $c['desc'] ) . '">' . "\n";
		}
		if ( $c['canonical'] && ! is_wp_error( $c['canonical'] ) && ! $c['noindex'] ) {
			echo '<link rel="canonical" href="' . esc_url( $c['canonical'] ) . '">' . "\n";
		}
		foreach ( array( 'gsc_verification' => 'google-site-verification', 'bing_verification' => 'msvalidate.01' ) as $k => $name ) {
			if ( lmc_opt( $k ) ) {
				echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( lmc_opt( $k ) ) . '">' . "\n";
			}
		}
		$og = array(
			'og:site_name'   => self::site(),
			'og:title'       => self::title( '' ),
			'og:description' => $c['desc'],
			'og:type'        => $c['type'],
			'og:url'         => is_wp_error( $c['canonical'] ) ? '' : $c['canonical'],
			'og:image'       => $c['image'],
			'og:locale'      => 'en_GB',
			'twitter:card'   => 'summary_large_image',
		);
		foreach ( $og as $k => $v ) {
			if ( $v ) {
				echo '<meta ' . ( 0 === strpos( $k, 'twitter' ) ? 'name' : 'property' ) . '="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
			}
		}
		$schema = array();
		if ( is_front_page() && ! get_query_var( 'lmc_company' ) ) {
			$schema[] = self::organization_schema();
			$schema[] = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'name'            => self::site(),
				'url'             => home_url( '/' ),
				'potentialAction' => array( '@type' => 'SearchAction', 'target' => get_post_type_archive_link( 'lmc_job' ) . '?q={search_term_string}', 'query-input' => 'required name=search_term_string' ),
			);
		}
		if ( is_singular( 'lmc_job' ) && 'publish' === get_post_status() ) {
			$schema[] = self::job_schema( get_queried_object_id() );
		}
		$crumbs = self::breadcrumbs();
		if ( count( $crumbs ) > 1 ) {
			$items = array();
			foreach ( $crumbs as $i => $c ) {
				$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
			}
			$schema[] = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items );
		}
		if ( is_page() && class_exists( 'LMC_Insights' ) && LMC_Insights::is_guide( get_queried_object_id() ) ) {
			$id       = get_queried_object_id();
			$schema[] = array(
				'@context'      => 'https://schema.org',
				'@type'         => 'Article',
				'headline'      => html_entity_decode( get_the_title( $id ), ENT_QUOTES ),
				'description'   => get_post_meta( $id, '_lmc_seo_desc', true ),
				'datePublished' => get_post_time( 'c', true, $id ),
				'dateModified'  => get_post_modified_time( 'c', true, $id ),
				'author'        => array( '@type' => 'Organization', 'name' => self::site(), 'url' => home_url( '/' ) ),
				'publisher'     => array( '@type' => 'Organization', 'name' => self::site(), 'logo' => array( '@type' => 'ImageObject', 'url' => get_stylesheet_directory_uri() . '/assets/img/logo-square.png' ) ),
				'mainEntityOfPage' => get_permalink( $id ),
			);
		}
		foreach ( $schema as $s ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
		}
	}

	/** Breadcrumb trail [[name, url], …] for the current page (used for schema and the visible trail). */
	public static function breadcrumbs() {
		$c = array( array( 'Home', home_url( '/' ) ) );
		if ( is_singular( 'lmc_job' ) ) {
			$c[] = array( 'Jobs', get_post_type_archive_link( 'lmc_job' ) );
			$s   = lmc_job_terms( get_queried_object_id(), 'lmc_sector' );
			if ( $s ) {
				$c[] = array( $s[0]->name, get_term_link( $s[0] ) );
			}
			$c[] = array( html_entity_decode( get_the_title(), ENT_QUOTES ), get_permalink() );
		} elseif ( is_tax( array( 'lmc_sector', 'lmc_role', 'lmc_location' ) ) ) {
			$t   = get_queried_object();
			$c[] = array( 'Jobs', get_post_type_archive_link( 'lmc_job' ) );
			if ( $t->parent ) {
				$p   = get_term( $t->parent );
				$c[] = array( $p->name, get_term_link( $p ) );
			}
			$c[] = array( $t->name, get_term_link( $t ) );
		} elseif ( is_post_type_archive( 'lmc_job' ) ) {
			$c[] = array( 'Jobs', get_post_type_archive_link( 'lmc_job' ) );
		} elseif ( is_page() && ! is_front_page() ) {
			foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $a ) {
				$c[] = array( get_the_title( $a ), get_permalink( $a ) );
			}
			$c[] = array( html_entity_decode( get_the_title(), ENT_QUOTES ), get_permalink() );
		}
		return $c;
	}

	public static function organization_schema() {
		$same = array_values( array_filter( array( lmc_opt( 'linkedin_url' ), lmc_opt( 'facebook_url' ) ) ) );
		$o    = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => self::site(),
			'url'      => home_url( '/' ),
			'logo'     => get_stylesheet_directory_uri() . '/assets/img/logo-square.png',
		);
		if ( $same ) {
			$o['sameAs'] = $same;
		}
		return $o;
	}

	/** Google JobPosting structured data – only for live vacancies with the required fields. */
	public static function job_schema( $id ) {
		$type_map = array( 'full-time' => 'FULL_TIME', 'part-time' => 'PART_TIME', 'contract' => 'CONTRACTOR', 'temporary' => 'TEMPORARY', 'apprenticeship' => 'FULL_TIME' );
		$types    = array();
		foreach ( lmc_job_terms( $id, 'lmc_type' ) as $t ) {
			$types[] = $type_map[ $t->slug ] ?? 'OTHER';
		}
		$county = '';
		foreach ( lmc_job_terms( $id, 'lmc_location' ) as $t ) {
			if ( $t->parent ) {
				$county = $t->name;
			}
		}
		$desc = get_post_field( 'post_content', $id );
		foreach ( array( 'responsibilities' => 'Key responsibilities', 'requirements' => 'Requirements', 'benefits' => 'Benefits' ) as $k => $label ) {
			$items = lmc_lines( lmc_job_meta( $id, $k ) );
			if ( $items ) {
				$desc .= '<h3>' . $label . '</h3><ul><li>' . implode( '</li><li>', array_map( 'esc_html', $items ) ) . '</li></ul>';
			}
		}
		$org = array( '@type' => 'Organization', 'name' => lmc_job_meta( $id, 'employer_name' ) );
		if ( lmc_job_meta( $id, 'employer_website' ) ) {
			$org['sameAs'] = lmc_job_meta( $id, 'employer_website' );
		}
		if ( lmc_job_meta( $id, 'logo_id' ) && lmc_job_meta( $id, 'logo_authorised' ) ) {
			$org['logo'] = wp_get_attachment_image_url( lmc_job_meta( $id, 'logo_id' ), 'medium' );
		}
		$period = lmc_job_meta( $id, 'salary_period' ) ?: 'YEAR';
		$value  = array( '@type' => 'QuantitativeValue', 'unitText' => $period );
		$min    = (float) lmc_job_meta( $id, 'salary_min' );
		$max    = (float) lmc_job_meta( $id, 'salary_max' );
		if ( $max > $min ) {
			$value['minValue'] = $min;
			$value['maxValue'] = $max;
		} else {
			$value['value'] = $min;
		}
		$s = array(
			'@context'           => 'https://schema.org/',
			'@type'              => 'JobPosting',
			'title'              => lmc_plain_title( $id ),
			'description'        => $desc,
			'identifier'         => array( '@type' => 'PropertyValue', 'name' => self::site(), 'value' => 'LMC-' . $id ),
			'datePosted'         => wp_date( 'c', (int) lmc_job_meta( $id, 'published_at' ) ?: get_post_time( 'U', true, $id ) ),
			'validThrough'       => wp_date( 'c', (int) lmc_job_meta( $id, 'expires' ) ),
			'employmentType'     => $types ?: array( 'FULL_TIME' ),
			'hiringOrganization' => $org,
			'jobLocation'        => array(
				'@type'   => 'Place',
				'address' => array_filter( array(
					'@type'           => 'PostalAddress',
					'addressLocality' => lmc_job_meta( $id, 'town' ),
					'addressRegion'   => $county,
					'postalCode'      => lmc_job_meta( $id, 'postcode' ),
					'addressCountry'  => 'GB',
				) ),
			),
			'baseSalary'         => array( '@type' => 'MonetaryAmount', 'currency' => 'GBP', 'value' => $value ),
			'directApply'        => false,
		);
		return $s;
	}

	public static function robots_txt( $out, $public ) {
		if ( ! $public ) {
			return $out;
		}
		$lines   = array( 'User-agent: *', 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php', 'Disallow: /employer/', 'Disallow: /email-preferences/', 'Disallow: /*?*lmc_', 'Disallow: /?s=', 'Disallow: /search/', '', 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) );
		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Analytics only loads after explicit consent (UK GDPR / PECR). With no GA4 ID configured,
	 * the site sets no non-essential cookies and no banner is shown.
	 */
	public static function consent_and_analytics() {
		$ga = lmc_opt( 'ga4_id' );
		$ga = ( $ga && preg_match( '/^G-[A-Z0-9]+$/', $ga ) ) ? $ga : '';
		if ( ! $ga && ! lmc_opt( 'track_returning' ) ) {
			return;
		}
		?>
		<div id="lmc-consent" class="lmc-consent" role="dialog" aria-live="polite" aria-label="Cookie preferences" hidden>
			<p>May we use analytics<?php echo $ga ? ' cookies' : ''; ?> to understand how the site is used – for example whether visitors come back? Nothing is stored on your device unless you accept. <a href="<?php echo esc_url( lmc_page_url( 'cookies' ) ); ?>">Cookie policy</a></p>
			<div><button type="button" class="lmc-btn lmc-btn--ghost" data-consent="no">Reject</button> <button type="button" class="lmc-btn lmc-btn--primary" data-consent="yes">Accept</button></div>
		</div>
		<script>
		(function(){var k='lmc_consent',v=null;try{v=localStorage.getItem(k)}catch(e){}
		function load(){window.lmcConsent='yes';document.dispatchEvent(new Event('lmc:consent'));if(!'<?php echo esc_js( $ga ); ?>')return;var s=document.createElement('script');s.async=1;s.src='https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $ga ); ?>';document.head.appendChild(s);window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}window.gtag=gtag;gtag('js',new Date());gtag('config','<?php echo esc_js( $ga ); ?>',{anonymize_ip:true});}
		var b=document.getElementById('lmc-consent');if(v==='yes'){load()}else if(v!=='no'){b.hidden=false}
		b.addEventListener('click',function(e){var c=e.target.getAttribute('data-consent');if(!c)return;try{localStorage.setItem(k,c)}catch(err){}b.hidden=true;if(c==='yes')load();});
		window.lmcResetConsent=function(){try{localStorage.removeItem(k)}catch(e){}b.hidden=false};})();
		</script>
		<?php
	}
}
LMC_SEO::init();
