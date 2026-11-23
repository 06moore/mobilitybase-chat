<?php
/**
 * Theme setup: supports, menus, assets, template routing and performance tidy-ups.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/main.css' );
	register_nav_menus( array(
		'primary' => 'Main menu (header)',
		'footer'  => 'Footer – Our services',
	) );
	set_post_thumbnail_size( 1200, 675, true );
	add_image_size( 'mb-card', 640, 400, true );
} );

/**
 * Default header menu, used until a menu is assigned in Appearance → Menus.
 * Ordered for private customers: buying a lift, covering an existing lift, removals.
 */
function mb_default_menu() {
	return array(
		array( 'Stairlifts & Lifts', '/products', array(
			array( 'Straight stairlifts', '/straight-stairlift-range' ),
			array( 'Curved stairlifts', '/curved-stairlift-range' ),
			array( 'Homelifts', '/homelift-range' ),
			array( 'Platform lifts', '/platform-lift-range' ),
			array( 'Ceiling hoists', '/ceiling-hoist-range' ),
			array( 'Free home survey', '/survey-advice' ),
		) ),
		array( 'Servicing & Warranty', '/aftercare-warranty', array(
			array( 'Warranty & service plans', '/aftercare-warranty' ),
			array( 'Lift servicing & maintenance', '/lift-servicing-kent' ),
			array( 'Repairs & breakdowns', '/lift-repairs-kent' ),
			array( 'LOLER examinations', '/loler-examinations' ),
		) ),
		array( 'Removals', '/stairlift-removal-kent', array(
			array( 'Stairlift removal', '/stairlift-removal-kent' ),
			array( 'Homelift & platform lift removal', '/lift-removals' ),
		) ),
		array( 'About us', '/about-us' ),
		array( 'Articles', '/blog' ),
		array( 'Contact', '/contact' ),
	);
}

/** Default footer service links. */
function mb_footer_services() {
	return array(
		array( 'Lift Servicing & Maintenance', '/lift-servicing-kent' ),
		array( 'Lift Repairs & Emergency Callouts', '/lift-repairs-kent' ),
		array( 'Warranty & Service Plans', '/aftercare-warranty' ),
		array( 'Stairlifts', '/straight-stairlift-range' ),
		array( 'Home lifts & Through-Floor Lifts', '/homelift-range' ),
		array( 'Platform Lifts', '/platform-lift-range' ),
		array( 'Stairlift Removal', '/stairlift-removal-kent' ),
		array( 'LOLER Examinations', '/loler-examinations' ),
	);
}

function mb_menu( $location, $fallback, $class ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => $class,
			'depth'          => 2,
		) );
		return;
	}
	$current = '/' . trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $fallback as $i => $item ) {
		$children = $item[2] ?? array();
		$paths    = array_merge( array( $item[1] ), array_column( $children, 1 ) );
		$active   = in_array( $current, $paths, true ) || ( '/blog' === $item[1] && is_singular( 'post' ) );
		$classes  = array();
		if ( $active ) {
			$classes[] = 'current-menu-item';
		}
		if ( $children ) {
			$classes[] = 'menu-item-has-children';
		}
		printf( '<li%s><a href="%s"%s>%s</a>', $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '', esc_url( home_url( $item[1] ) ), $current === $item[1] ? ' aria-current="page"' : '', esc_html( $item[0] ) );
		if ( $children ) {
			printf( '<button class="mb-subtoggle" type="button" aria-expanded="false" aria-controls="mb-sub-%1$d"><span class="mb-sr">Show %2$s menu</span></button><ul class="sub-menu" id="mb-sub-%1$d">', (int) $i, esc_html( $item[0] ) );
			foreach ( $children as $c ) {
				printf( '<li><a href="%s"%s>%s</a></li>', esc_url( home_url( $c[1] ) ), $current === $c[1] ? ' aria-current="page"' : '', esc_html( $c[0] ) );
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

add_action( 'wp_enqueue_scripts', function () {
	$css = MB_DIR . '/assets/css/main.css';
	$js  = MB_DIR . '/assets/js/main.js';
	wp_enqueue_style( 'mobilitybase', MB_URI . '/assets/css/main.css', array(), MB_VERSION . '.' . filemtime( $css ) );
	wp_enqueue_script( 'mobilitybase', MB_URI . '/assets/js/main.js', array(), MB_VERSION . '.' . filemtime( $js ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	// Block library CSS is only needed on blog posts written in the editor.
	if ( ! is_singular( 'post' ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
} , 20 );

add_action( 'wp_head', function () {
	echo '<link rel="preload" href="' . esc_url( MB_URI . '/assets/fonts/nunito-sans-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#13aa59">' . "\n";
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( MB_URI . '/assets/img/favicon.png' ) . '" type="image/png">' . "\n";
	}
}, 1 );

/*
 * Template routing for pages whose layout comes from inc/data.php:
 * product range pages and warranty plan pages share one template each.
 */
add_filter( 'template_include', function ( $template ) {
	if ( ! is_page() ) {
		return $template;
	}
	$slug = get_queried_object()->post_name ?? '';
	if ( isset( mb_ranges()[ $slug ] ) ) {
		return MB_DIR . '/templates/range.php';
	}
	if ( isset( mb_warranties()[ $slug ] ) ) {
		return MB_DIR . '/templates/warranty.php';
	}
	if ( mb_landing( $slug ) ) {
		return MB_DIR . '/templates/landing.php';
	}
	return $template;
} );

/* Body classes for styling per page type. */
add_filter( 'body_class', function ( $classes ) {
	if ( is_page() ) {
		$classes[] = 'page-' . sanitize_html_class( get_queried_object()->post_name ?? '' );
		$tpl = get_post_meta( get_queried_object_id(), '_mb_template', true );
		if ( $tpl ) {
			$classes[] = 'mb-tpl-' . sanitize_html_class( $tpl );
		}
	}
	return $classes;
} );

/* Performance: drop features the site does not use. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'feed_links_extra', 3 );
add_filter( 'xmlrpc_enabled', '__return_false' );

/* Comments are not used on this site. */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 10 );

/* Excerpt length for article cards. */
add_filter( 'excerpt_length', fn() => 26 );
add_filter( 'excerpt_more', fn() => '…' );

/*
 * The old Wix site had a few URLs that no longer exist as separate pages.
 * Send them somewhere sensible with a permanent redirect so links keep working.
 */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	$map  = array(
		'inquiry-services-page' => '/services',
		'blog/categories'       => '/blog',
		'home'                  => '/',
	);
	foreach ( $map as $from => $to ) {
		if ( $path === $from || ( str_starts_with( $from, 'blog/' ) && str_starts_with( $path, $from ) ) ) {
			wp_safe_redirect( home_url( $to ), 301 );
			exit;
		}
	}
} , 1 );
