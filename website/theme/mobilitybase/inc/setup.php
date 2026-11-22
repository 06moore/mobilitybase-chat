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

/** Default header menu, used until a menu is assigned in Appearance → Menus. */
function mb_default_menu() {
	return array(
		array( 'About us', '/about-us' ),
		array( 'Services', '/services' ),
		array( 'Aftercare & Warranty', '/aftercare-warranty' ),
		array( 'Products', '/products' ),
		array( 'Contact', '/contact' ),
		array( 'Articles', '/blog' ),
	);
}

/** Default footer service links. */
function mb_footer_services() {
	return array(
		array( 'Stairlifts', '/straight-stairlift-range' ),
		array( 'Home lifts & Through-Floor Lifts', '/homelift-range' ),
		array( 'Platform Lift', '/platform-lift-range' ),
		array( 'Lift Servicing & Maintenance', '/lift-servicing-kent' ),
		array( 'Lift Repairs & Fault Finding', '/lift-repairs-kent' ),
		array( 'Emergency Callouts', '/lift-repairs-kent' ),
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
	foreach ( $fallback as $item ) {
		$active = ( $current === $item[1] || ( '/blog' === $item[1] && is_singular( 'post' ) ) );
		printf(
			'<li%s><a href="%s"%s>%s</a></li>',
			$active ? ' class="current-menu-item"' : '',
			esc_url( home_url( $item[1] ) ),
			$active ? ' aria-current="page"' : '',
			esc_html( $item[0] )
		);
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
	if ( 'location' === get_post_meta( get_queried_object_id(), '_mb_template', true ) ) {
		return MB_DIR . '/templates/location.php';
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
