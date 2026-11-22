<?php
/**
 * Template helpers.
 */

defined( 'ABSPATH' ) || exit;

/** URL of an image shipped with the theme (assets/img/content/<name>). */
function mb_img_url( $name ) {
	return MB_URI . '/assets/img/content/' . $name;
}

/** <img> tag for a theme image with intrinsic size so the layout never jumps. */
function mb_img( $name, $alt = '', $attrs = array() ) {
	$file = MB_DIR . '/assets/img/content/' . $name;
	$size = is_readable( $file ) ? wp_getimagesize( $file ) : false;
	$out  = '<img src="' . esc_url( mb_img_url( $name ) ) . '" alt="' . esc_attr( $alt ) . '"';
	if ( $size ) {
		$out .= ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"';
	}
	$attrs = wp_parse_args( $attrs, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	foreach ( $attrs as $k => $v ) {
		if ( false !== $v ) {
			$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
	}
	return $out . '>';
}

/** Telephone link. */
function mb_tel( $label = null, $class = '' ) {
	$label = $label ?? mb_biz( 'phone' );
	return '<a href="tel:' . esc_attr( mb_biz( 'phone_e164' ) ) . '"' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . esc_html( $label ) . '</a>';
}

/** Inline SVG icons (stroke style, matches the old site). */
function mb_icon( $name ) {
	$paths = array(
		'pin'    => '<path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/>',
		'pound'  => '<path d="M16 6.5A4 4 0 0 0 8.5 8v4.5c0 2.5-1 4.5-2.5 5.5h12M6 12.5h8"/>',
		'star'   => '<path d="m12 3 2.8 5.8 6.2.9-4.5 4.4 1.1 6.2L12 17.4l-5.6 2.9 1.1-6.2L3 9.7l6.2-.9z"/>',
		'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 6 9-6"/>',
		'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'check'  => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'wrench' => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-8.4 8.4a2.1 2.1 0 0 1-3-3z"/><path d="M14.7 6.3 17 4a4 4 0 0 0-5 5"/>',
		'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'  => '<path d="M6 6l12 12M18 6 6 18"/>',
		'arrow'  => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
	);
	return '<svg class="mb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Social icon (solid, white-on-circle like the old footer). */
function mb_social_icon( $name ) {
	$paths = array(
		'facebook'  => '<path d="M13.5 21v-7.5h2.5l.4-3h-2.9V8.6c0-.9.3-1.5 1.5-1.5h1.5V4.4c-.3 0-1.2-.1-2.2-.1-2.2 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21z"/>',
		'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.6" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="16.9" cy="7.1" r="1.2"/>',
		'linkedin'  => '<path d="M7.2 9.5H4.6V19h2.6zM5.9 5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM19.4 13.6c0-2.6-.6-4.3-3.4-4.3-1.3 0-2.2.7-2.6 1.4V9.5h-2.5V19h2.6v-4.7c0-1.2.2-2.4 1.8-2.4 1.5 0 1.5 1.4 1.5 2.5V19h2.6z"/>',
	);
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="currentColor">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Current page slug (empty on the front page). */
function mb_slug() {
	if ( is_front_page() ) {
		return '';
	}
	$obj = get_queried_object();
	return $obj instanceof WP_Post ? $obj->post_name : '';
}
