<?php
/**
 * Mobilitybase theme bootstrap.
 *
 * inc/business.php  – company details (phone, address, hours, form recipient)
 * inc/setup.php     – theme supports, menus, assets, URLs
 * inc/data.php      – product ranges, warranty plans, services (edited in code)
 * inc/helpers.php   – small template helpers
 * inc/seo.php       – titles, meta descriptions, canonical, Open Graph, schema
 * inc/forms.php     – enquiry forms: [mb_form] shortcode, email + stored enquiries
 * inc/importer.php  – one-off import of pages/posts from /content
 */

defined( 'ABSPATH' ) || exit;

define( 'MB_VERSION', '1.2.0' );
define( 'MB_DIR', get_template_directory() );
define( 'MB_URI', get_template_directory_uri() );

foreach ( array( 'business', 'helpers', 'setup', 'data', 'seo', 'forms', 'importer' ) as $mb_file ) {
	require_once MB_DIR . '/inc/' . $mb_file . '.php';
}
