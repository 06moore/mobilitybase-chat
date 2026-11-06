<?php
/**
 * Lift and Mobility Careers theme. Lightweight classic theme: no page builder, no jQuery,
 * self-hosted fonts (no third-party requests), one CSS file and one small JS file.
 */
defined( 'ABSPATH' ) || exit;

define( 'LMC_THEME_VERSION', '1.0.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	register_nav_menus( array( 'primary' => 'Primary navigation', 'footer' => 'Footer links' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$v = LMC_THEME_VERSION . '.' . filemtime( get_template_directory() . '/assets/css/main.css' );
	wp_enqueue_style( 'lmc', get_template_directory_uri() . '/assets/css/main.css', array(), $v );
	wp_enqueue_script( 'lmc', get_template_directory_uri() . '/assets/js/main.js', array(), $v, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script( 'lmc', 'LMC', array( 'track' => rest_url( 'lmc/v1/track' ) ) );
	// Strip unneeded core front-end assets for speed.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

add_action( 'wp_head', function () {
	$u = get_template_directory_uri() . '/assets';
	echo '<link rel="preload" href="' . esc_url( $u . '/fonts/manrope-latin-800-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="icon" href="' . esc_url( $u . '/img/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $u . '/img/favicon-32.png' ) . '" sizes="32x32">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $u . '/img/apple-touch-icon.png' ) . '">' . "\n";
	echo '<meta name="theme-color" content="#142F40">' . "\n";
}, 2 );

// Remove front-end bloat.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
add_filter( 'xmlrpc_enabled', '__return_false' );

/** Primary navigation (falls back to the brief's fixed menu if no WP menu is assigned). */
function lmc_theme_nav( $location = 'primary' ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array( 'theme_location' => $location, 'container' => false, 'menu_class' => 'lmc-nav__list', 'depth' => 1 ) );
		return;
	}
	$items = 'primary' === $location ? array(
		array( 'Home', home_url( '/' ) ),
		array( 'Browse Jobs', get_post_type_archive_link( 'lmc_job' ) ),
		array( 'Engineering Sectors', lmc_page_url( 'sectors' ) ),
		array( 'For Employers', lmc_page_url( 'employers' ) ),
		array( 'About Us', lmc_page_url( 'about' ) ),
		array( 'Contact', lmc_page_url( 'contact' ) ),
	) : array();
	$cur = home_url( add_query_arg( array() ) );
	echo '<ul class="lmc-nav__list">';
	foreach ( $items as $i ) {
		$active = untrailingslashit( strtok( $cur, '?' ) ) === untrailingslashit( $i[1] ) ? ' aria-current="page"' : '';
		echo '<li><a href="' . esc_url( $i[1] ) . '"' . $active . '>' . esc_html( $i[0] ) . '</a></li>';
	}
	echo '</ul>';
}

/** Inline SVG icons (sector icons + UI). Abstract, engineering-led – no medical/wheelchair clichés. */
function lmc_theme_icon( $name, $class = 'lmc-icon' ) {
	$p = array(
		'stairs'       => '<path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M14 4l3-2 3 2" />',
		'throughfloor' => '<path d="M3 12h18"/><path d="M8 4v16M16 4v16"/><path d="M10 9l2-2 2 2"/>',
		'house'        => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/><path d="M12 8v0"/>',
		'platform'     => '<path d="M5 3v18M19 3v18"/><path d="M7 15h10"/><path d="M9 11l3-3 3 3"/>',
		'step'         => '<path d="M3 20h18"/><path d="M14 20v-6h7"/><path d="M5 16h7"/><path d="M8 13l2-2 2 2"/>',
		'door'         => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M12 3v18"/><path d="M8.5 10l1.5-1.5L11.5 10M12.5 14l1.5 1.5 1.5-1.5"/>',
		'track'        => '<path d="M2 5h20"/><path d="M12 5v6"/><rect x="9" y="11" width="6" height="4" rx="1"/><path d="M12 15v4"/><path d="M9 19h6"/>',
		'hook'         => '<path d="M12 3v7"/><path d="M12 10a4 4 0 1 0 4 4"/><path d="M8 3h8"/>',
		'gear'         => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
		'pin'          => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'pound'        => '<path d="M16 7a4 4 0 0 0-7 2.5V18M6 13h7M6 18h11"/>',
		'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'briefcase'    => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5h6v2"/>',
		'search'       => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
		'check'        => '<path d="M5 12l4 4 10-10"/>',
		'arrow'        => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'level'        => '<path d="M4 20V14M10 20V9M16 20V4"/>',
		'calendar'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
	);
	$d = $p[ $name ] ?? $p['gear'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** The main search form (homepage hero + listings sidebar). */
function lmc_theme_search_form( $variant = 'hero' ) {
	$f       = class_exists( 'LMC_Search' ) ? LMC_Search::filters() : array();
	$sectors = get_terms( array( 'taxonomy' => 'lmc_sector', 'hide_empty' => false, 'meta_key' => 'lmc_order', 'orderby' => 'meta_value_num' ) );
	$current_sector = is_tax( 'lmc_sector' ) ? get_queried_object()->slug : ( $f['sector'] ?? '' );
	?>
	<form class="lmc-search lmc-search--<?php echo esc_attr( $variant ); ?>" action="<?php echo esc_url( get_post_type_archive_link( 'lmc_job' ) ); ?>" method="get" role="search">
		<div class="lmc-search__field">
			<label for="lmc-q-<?php echo esc_attr( $variant ); ?>">Job title or keyword</label>
			<?php echo lmc_theme_icon( 'search' ); ?>
			<input id="lmc-q-<?php echo esc_attr( $variant ); ?>" type="search" name="q" value="<?php echo esc_attr( $f['q'] ?? '' ); ?>" placeholder="e.g. service engineer, LOLER">
		</div>
		<div class="lmc-search__field">
			<label for="lmc-loc-<?php echo esc_attr( $variant ); ?>">Location</label>
			<?php echo lmc_theme_icon( 'pin' ); ?>
			<input id="lmc-loc-<?php echo esc_attr( $variant ); ?>" type="text" name="location" value="<?php echo esc_attr( $f['location'] ?? '' ); ?>" placeholder="Town, county or postcode" list="lmc-locations" autocomplete="off">
		</div>
		<div class="lmc-search__field lmc-search__field--select">
			<label for="lmc-sector-<?php echo esc_attr( $variant ); ?>">Sector</label>
			<select id="lmc-sector-<?php echo esc_attr( $variant ); ?>" name="sector">
				<option value="">All sectors</option>
				<?php foreach ( $sectors as $s ) : ?>
					<option value="<?php echo esc_attr( $s->slug ); ?>" <?php selected( $current_sector, $s->slug ); ?>><?php echo esc_html( $s->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="lmc-btn lmc-btn--primary">Search jobs</button>
	</form>
	<datalist id="lmc-locations"><?php
	if ( class_exists( 'LMC_Location' ) ) {
		foreach ( LMC_Location::suggestions() as $s ) {
			echo '<option value="' . esc_attr( $s ) . '">';
		}
	}
	?></datalist>
	<?php
}

function lmc_theme_days_left( $job_id ) {
	$exp = (int) lmc_job_meta( $job_id, 'expires' );
	$closing = lmc_job_meta( $job_id, 'closing_date' );
	return $closing ? 'Closes ' . lmc_date( strtotime( $closing ) ) : ( $exp ? 'Advert ends ' . lmc_date( $exp ) : '' );
}

function lmc_theme_posted( $job_id ) {
	$ts = (int) lmc_job_meta( $job_id, 'published_at' ) ?: get_post_time( 'U', true, $job_id );
	$d  = floor( ( time() - $ts ) / DAY_IN_SECONDS );
	return $d < 1 ? 'Posted today' : ( $d < 2 ? 'Posted yesterday' : 'Posted ' . $d . ' days ago' );
}
