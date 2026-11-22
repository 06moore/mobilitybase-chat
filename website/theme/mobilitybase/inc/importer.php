<?php
/**
 * One-off content import from the theme's /content folder (built from the old Wix site).
 *
 *   wp mobilitybase import            create anything missing
 *   wp mobilitybase import --force    also overwrite pages/posts that already exist
 *
 * Or: WordPress admin → Tools → Mobilitybase import.
 *
 * Sets permalinks to match the old Wix URLs exactly:
 *   pages  /about-us            posts  /post/stairlift-cost-uk
 */

defined( 'ABSPATH' ) || exit;

const MB_SKIP_PAGES = array( 'inquiry-services-page' );

function mb_import( $force = false, $log = null ) {
	$log = $log ?: function ( $m ) {};
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$dir      = MB_DIR . '/content';
	$manifest = json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true );
	if ( ! $manifest ) {
		$log( 'manifest.json missing or invalid' );
		return false;
	}
	// Root-relative image paths keep working if the domain changes.
	$img_base = wp_make_link_relative( MB_URI . '/assets/img/content' );
	$fix      = fn( $html ) => str_replace( '{{IMG}}', $img_base, $html );

	// ---- site settings
	update_option( 'blogname', 'Mobilitybase' );
	update_option( 'blogdescription', 'Stairlifts, home lifts & platform lifts — Kent & London' );
	update_option( 'timezone_string', 'Europe/London' );
	update_option( 'date_format', 'j F Y' );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	update_option( 'posts_per_page', 12 );
	update_option( 'blog_public', get_option( 'blog_public', 1 ) );
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/post/%postname%' );

	// ---- default WordPress sample content
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p ) {
			wp_delete_post( $p->ID, true );
		}
	}

	// ---- pages
	$ids = array();
	foreach ( $manifest['pages'] as $pg ) {
		$slug = $pg['slug'] ?: 'home';
		if ( in_array( $slug, MB_SKIP_PAGES, true ) ) {
			continue;
		}
		$html     = $fix( (string) file_get_contents( $dir . '/pages/' . $pg['file'] ) );
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$is_custom = 'custom' === $pg['template'];
		$postarr  = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $pg['title'],
			'post_name'    => $slug,
			// Hand-built pages keep their layout in the theme; content stays empty to avoid confusion.
			'post_content' => $is_custom ? '' : $html,
			'menu_order'   => 0,
		);
		if ( $existing && ! $force ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
		}
		$id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $id ) ) {
			$log( 'Page failed: ' . $slug . ' — ' . $id->get_error_message() );
			continue;
		}
		$ids[ $slug ] = $id;
		update_post_meta( $id, '_mb_seo_title', $pg['seo_title'] );
		update_post_meta( $id, '_mb_seo_desc', $pg['seo_desc'] );
		update_post_meta( $id, '_mb_template', $pg['template'] );
		update_post_meta( $id, '_mb_h1', $pg['h1'] );
		if ( ! empty( $pg['schema'] ) ) {
			update_post_meta( $id, '_mb_schema', wp_slash( wp_json_encode( $pg['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
		}
		$log( ( $existing ? 'Updated' : 'Created' ) . " page /{$pg['slug']}" );
	}

	// ---- privacy policy (new — the Wix site did not have one)
	$privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
	if ( ! $privacy || $force || 'publish' !== $privacy->post_status ) {
		$pid = wp_insert_post( wp_slash( array(
			'ID'           => $privacy ? $privacy->ID : 0,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Privacy Policy',
			'post_name'    => 'privacy-policy',
			'post_content' => (string) file_get_contents( $dir . '/privacy-policy.html' ),
		) ) );
		update_option( 'wp_page_for_privacy_policy', $pid );
		update_post_meta( $pid, '_mb_seo_title', 'Privacy Policy | Mobilitybase' );
		update_post_meta( $pid, '_mb_seo_desc', 'How Mobilitybase Ltd collects, uses and protects your personal information.' );
		$log( 'Privacy policy page ready' );
	}

	if ( isset( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}
	if ( isset( $ids['blog'] ) ) {
		update_option( 'page_for_posts', $ids['blog'] );
	}

	// ---- posts
	foreach ( $manifest['posts'] as $po ) {
		$existing = get_page_by_path( $po['slug'], OBJECT, 'post' );
		if ( $existing && ! $force ) {
			continue;
		}
		$date = $po['date'] ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $po['date'] ) ) ) : current_time( 'mysql' );
		$id   = wp_insert_post( wp_slash( array(
			'ID'            => $existing ? $existing->ID : 0,
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $po['title'],
			'post_name'     => $po['slug'],
			'post_content'  => $fix( (string) file_get_contents( $dir . '/posts/' . $po['file'] ) ),
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
		) ), true );
		if ( is_wp_error( $id ) ) {
			$log( 'Post failed: ' . $po['slug'] );
			continue;
		}
		update_post_meta( $id, '_mb_seo_title', $po['seo_title'] );
		update_post_meta( $id, '_mb_seo_desc', $po['seo_desc'] );
		if ( ! empty( $po['schema'] ) ) {
			update_post_meta( $id, '_mb_schema', wp_slash( wp_json_encode( $po['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
		}
		if ( ! empty( $po['featured'] ) && ! has_post_thumbnail( $id ) ) {
			$src = MB_DIR . '/assets/img/content/' . $po['featured'];
			if ( is_readable( $src ) ) {
				$tmp = wp_tempnam( $po['featured'] );
				copy( $src, $tmp );
				$att = media_handle_sideload( array( 'name' => $po['slug'] . '.webp', 'tmp_name' => $tmp ), $id, $po['title'] );
				if ( ! is_wp_error( $att ) ) {
					set_post_thumbnail( $id, $att );
					update_post_meta( $att, '_wp_attachment_image_alt', $po['title'] );
				}
			}
		}
		$log( ( $existing ? 'Updated' : 'Created' ) . " post /post/{$po['slug']}" );
	}

	flush_rewrite_rules( false );
	$log( 'Import finished.' );
	return true;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'mobilitybase import', function ( $args, $assoc ) {
		mb_import( ! empty( $assoc['force'] ), fn( $m ) => WP_CLI::log( $m ) );
		WP_CLI::success( 'Done' );
	} );
}

/* Admin screen: Tools → Mobilitybase import. */
add_action( 'admin_menu', function () {
	add_management_page( 'Mobilitybase import', 'Mobilitybase import', 'manage_options', 'mb-import', function () {
		$log = get_transient( 'mb_import_log' );
		delete_transient( 'mb_import_log' );
		?>
		<div class="wrap">
			<h1>Mobilitybase content import</h1>
			<p>Creates every page and article from the old Wix site, with the same web addresses, SEO titles and descriptions. Safe to run more than once.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mb_import">
				<?php wp_nonce_field( 'mb_import' ); ?>
				<p><label><input type="checkbox" name="force" value="1"> Overwrite pages and articles that already exist</label></p>
				<?php submit_button( 'Run import' ); ?>
			</form>
			<?php if ( $log ) : ?>
				<pre style="background:#fff;padding:12px;max-height:400px;overflow:auto"><?php echo esc_html( implode( "\n", $log ) ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	} );
} );

add_action( 'admin_post_mb_import', function () {
	check_admin_referer( 'mb_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	$lines = array();
	mb_import( ! empty( $_POST['force'] ), function ( $m ) use ( &$lines ) {
		$lines[] = $m;
	} );
	set_transient( 'mb_import_log', $lines, 300 );
	wp_safe_redirect( admin_url( 'tools.php?page=mb-import' ) );
	exit;
} );
