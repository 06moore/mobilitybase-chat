<?php
/**
 * Built-in SEO: <title>, meta description, canonical, robots, Open Graph,
 * and structured data (LocalBusiness, BreadcrumbList, BlogPosting, page schema).
 *
 * Per-page values are stored as post meta and can be edited on every page/post
 * in the "SEO" box: _mb_seo_title, _mb_seo_desc, _mb_schema (JSON-LD), _mb_noindex.
 *
 * If Yoast SEO or Rank Math is installed later, this module steps aside.
 */

defined( 'ABSPATH' ) || exit;

function mb_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' );
}

function mb_seo_title() {
	if ( is_singular() ) {
		$t = get_post_meta( get_queried_object_id(), '_mb_seo_title', true );
		if ( $t ) {
			return $t;
		}
		if ( is_front_page() ) {
			return 'Stairlifts & Home Lifts Kent & London | Mobilitybase';
		}
		return single_post_title( '', false ) . ' | Mobilitybase';
	}
	if ( is_home() ) {
		$t = get_post_meta( (int) get_option( 'page_for_posts' ), '_mb_seo_title', true );
		$t = $t ?: 'Stairlift & Lift Advice Articles | Mobilitybase';
		return is_paged() ? $t . ' – Page ' . get_query_var( 'paged' ) : $t;
	}
	if ( is_category() || is_tag() ) {
		return single_term_title( '', false ) . ' Articles | Mobilitybase';
	}
	if ( is_search() ) {
		return 'Search results | Mobilitybase';
	}
	if ( is_404() ) {
		return 'Page not found | Mobilitybase';
	}
	return get_bloginfo( 'name' );
}

function mb_seo_desc() {
	if ( is_singular() ) {
		$id = get_queried_object_id();
		$d  = get_post_meta( $id, '_mb_seo_desc', true );
		if ( ! $d ) {
			$d = wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ), 28, '…' );
		}
		return $d;
	}
	if ( is_home() ) {
		$d = get_post_meta( (int) get_option( 'page_for_posts' ), '_mb_seo_desc', true );
		return $d ?: 'Practical advice on stairlifts, home lifts and platform lifts from the Mobilitybase engineers — costs, fault codes, servicing and more.';
	}
	return 'Stairlifts, home lifts, platform lifts and ceiling hoists supplied, installed, serviced and repaired across Kent, London and the South East.';
}

function mb_canonical() {
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	if ( is_home() ) {
		$url = get_permalink( (int) get_option( 'page_for_posts' ) );
		$p   = (int) get_query_var( 'paged' );
		return $p > 1 ? trailingslashit( $url ) . 'page/' . $p : $url;
	}
	return '';
}

add_filter( 'pre_get_document_title', function ( $title ) {
	return mb_seo_plugin_active() ? $title : mb_seo_title();
} );

// WordPress outputs its own canonical on singular pages; ours covers the blog index too.
add_action( 'init', function () {
	if ( ! mb_seo_plugin_active() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
} );

add_filter( 'wp_robots', function ( $robots ) {
	if ( is_search() || is_404() || ( is_singular() && get_post_meta( get_queried_object_id(), '_mb_noindex', true ) ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	} else {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
} );

add_action( 'wp_head', function () {
	if ( mb_seo_plugin_active() ) {
		return;
	}
	$title = mb_seo_title();
	$desc  = mb_seo_desc();
	$canon = mb_canonical();
	$image = mb_biz_image();
	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'large' );
	} elseif ( is_singular() && ( $og = get_post_meta( get_queried_object_id(), '_mb_og_image', true ) ) ) {
		$image = mb_img_url( $og );
	}
	echo "\n<!-- Mobilitybase SEO -->\n";
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	if ( $canon ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canon ) );
	}
	$og = array(
		'og:locale'      => 'en_GB',
		'og:site_name'   => 'Mobilitybase',
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $canon ?: home_url( '/' ),
		'og:image'       => $image,
	);
	foreach ( $og as $k => $v ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $k ), esc_attr( $v ) );
	}
	if ( is_singular( 'post' ) ) {
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c' ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c' ) ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	$v = get_option( 'mb_gsc_verification' );
	if ( $v ) {
		printf( '<meta name="google-site-verification" content="%s">' . "\n", esc_attr( $v ) );
	}
}, 2 );

function mb_biz_image() {
	return mb_img_url( '32a1699136194f51.webp' );
}

/** Structured data. */
add_action( 'wp_head', function () {
	$graph = array();
	$b     = mb_business();
	$org   = home_url( '/#business' );

	$graph[] = array(
		'@type'                     => array( 'HomeAndConstructionBusiness', 'LocalBusiness' ),
		'@id'                       => $org,
		'name'                      => $b['name'],
		'legalName'                 => $b['legal_name'],
		'url'                       => home_url( '/' ),
		'logo'                      => MB_URI . '/assets/img/logo.png',
		'image'                     => mb_biz_image(),
		'description'               => 'Stairlifts, home lifts, platform lifts and ceiling hoists supplied, installed, serviced and repaired across Kent, London and the South East.',
		'telephone'                 => $b['phone'],
		'email'                     => $b['email'],
		'foundingDate'              => $b['founded'],
		'priceRange'                => '££',
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $b['street'],
			'addressLocality' => $b['town'],
			'addressRegion'   => $b['region'],
			'postalCode'      => $b['postcode'],
			'addressCountry'  => 'GB',
		),
		'openingHoursSpecification' => array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
			'opens'     => '09:00',
			'closes'    => '17:00',
		),
		'areaServed'                => array_map( fn( $a ) => array( '@type' => 'AdministrativeArea', 'name' => $a ), $b['area_served'] ),
		'sameAs'                    => array_values( $b['social'] ),
		'knowsAbout'                => array( 'Stairlifts', 'Curved stairlifts', 'Home lifts', 'Through-floor lifts', 'Platform lifts', 'Ceiling hoists', 'Lift servicing', 'LOLER examinations' ),
	);

	if ( is_front_page() ) {
		$graph[] = array(
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'url'       => home_url( '/' ),
			'name'      => 'Mobilitybase',
			'publisher' => array( '@id' => $org ),
			'inLanguage' => 'en-GB',
		);
	}

	// Breadcrumbs.
	if ( ! is_front_page() && ( is_singular() || is_home() ) ) {
		$crumbs = array( array( 'Home', home_url( '/' ) ) );
		if ( is_singular( 'post' ) ) {
			$crumbs[] = array( 'Articles', get_permalink( (int) get_option( 'page_for_posts' ) ) );
		}
		$crumbs[] = array( is_home() ? 'Articles' : get_the_title( get_queried_object_id() ), mb_canonical() );
		$graph[]  = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array_map( fn( $c, $i ) => array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( $c[0] ),
				'item'     => $c[1],
			), $crumbs, array_keys( $crumbs ) ),
		);
	}

	// Blog posts.
	if ( is_singular( 'post' ) ) {
		$id      = get_queried_object_id();
		$graph[] = array(
			'@type'            => 'BlogPosting',
			'headline'         => get_the_title( $id ),
			'description'      => mb_seo_desc(),
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'mainEntityOfPage' => get_permalink( $id ),
			'image'            => has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : mb_biz_image(),
			'author'           => array( '@type' => 'Organization', 'name' => 'Mobilitybase', 'url' => home_url( '/' ) ),
			'publisher'        => array( '@id' => $org ),
			'inLanguage'       => 'en-GB',
		);
	}

	// Range pages: list products.
	if ( is_page() && isset( mb_ranges()[ mb_slug() ] ) ) {
		$r = mb_ranges()[ mb_slug() ];
		foreach ( $r['models'] as $m ) {
			$graph[] = array(
				'@type'       => 'Product',
				'name'        => $m[0],
				'image'       => mb_img_url( $m[1] ),
				'description' => $m[3][0],
				'category'    => mb_products()[ mb_slug() ]['name'] ?? '',
			);
		}
	}

	// Warranty pages: plans as offers.
	if ( is_page() && isset( mb_warranties()[ mb_slug() ] ) ) {
		$w       = mb_warranties()[ mb_slug() ];
		$graph[] = array(
			'@type'    => 'Service',
			'name'     => $w['label'] . ' warranty & service plans',
			'provider' => array( '@id' => $org ),
			'areaServed' => 'Kent, London and the South East',
			'offers'   => array_values( array_map( fn( $p ) => array(
				'@type'         => 'Offer',
				'name'          => $p['name'] . ' plan',
				'price'         => (string) $p['price'],
				'priceCurrency' => 'GBP',
			), $w['plans'] ) ),
		);
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";

	// Hand-written schema carried over from the old site (FAQ, HowTo, etc.).
	if ( is_singular() ) {
		$extra = get_post_meta( get_queried_object_id(), '_mb_schema', true );
		if ( $extra ) {
			$decoded = json_decode( $extra, true );
			foreach ( (array) $decoded as $item ) {
				if ( is_array( $item ) ) {
					echo '<script type="application/ld+json">' . wp_json_encode( $item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
				}
			}
		}
	}
}, 3 );

/* SEO box on the page/post editor. */
add_action( 'add_meta_boxes', function () {
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'mb_seo', 'SEO (Google)', 'mb_seo_box', $type, 'normal', 'high' );
	}
} );

function mb_seo_box( $post ) {
	wp_nonce_field( 'mb_seo', 'mb_seo_nonce' );
	$t = get_post_meta( $post->ID, '_mb_seo_title', true );
	$d = get_post_meta( $post->ID, '_mb_seo_desc', true );
	$n = get_post_meta( $post->ID, '_mb_noindex', true );
	echo '<p><label><strong>Title shown in Google</strong> <small>(aim for under 60 characters)</small><br><input type="text" name="mb_seo_title" value="' . esc_attr( $t ) . '" style="width:100%"></label></p>';
	echo '<p><label><strong>Description shown in Google</strong> <small>(aim for 120–155 characters)</small><br><textarea name="mb_seo_desc" rows="3" style="width:100%">' . esc_textarea( $d ) . '</textarea></label></p>';
	echo '<p><label><input type="checkbox" name="mb_noindex" value="1"' . checked( $n, '1', false ) . '> Hide this page from Google</label></p>';
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['mb_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mb_seo_nonce'] ), 'mb_seo' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_mb_seo_title', sanitize_text_field( wp_unslash( $_POST['mb_seo_title'] ?? '' ) ) );
	update_post_meta( $post_id, '_mb_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['mb_seo_desc'] ?? '' ) ) );
	update_post_meta( $post_id, '_mb_noindex', empty( $_POST['mb_noindex'] ) ? '' : '1' );
} );

/* Sitemap (WordPress core /wp-sitemap.xml): only pages and posts, skip hidden pages. */
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', fn() => array() );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args ) {
	$args['meta_query'] = array(
		'relation' => 'OR',
		array( 'key' => '_mb_noindex', 'compare' => 'NOT EXISTS' ),
		array( 'key' => '_mb_noindex', 'value' => '1', 'compare' => '!=' ),
	);
	return $args;
} );

/* /sitemap.xml (the address Google already knows from Wix) → core sitemap. */
add_action( 'init', function () {
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?sitemap=index', 'top' );
} );
add_filter( 'redirect_canonical', function ( $redirect, $requested ) {
	return str_ends_with( wp_parse_url( $requested, PHP_URL_PATH ) ?? '', '/sitemap.xml' ) ? false : $redirect;
}, 10, 2 );
