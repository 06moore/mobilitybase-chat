<?php
/**
 * Enquiry forms.
 *
 * Use anywhere:  [mb_form fields="name,phone,email,postcode,message" title="Arrange a survey"]
 * Optional:      options="A|B|C" options_label="Question?"  product="Stiltz Duo"  button="Send"
 *
 * Every submission is:
 *   1. emailed to the address in Settings → Mobilitybase (default: service@mobilitybase.co.uk),
 *      with Reply-To set to the customer so you can just hit reply;
 *   2. saved under "Enquiries" in the WordPress admin, so nothing is lost if an email goes astray.
 *
 * Spam protection: hidden honeypot field + signed timestamp (bots that submit instantly are dropped).
 * No cookies, no third-party scripts.
 */

defined( 'ABSPATH' ) || exit;

function mb_form_fields() {
	return array(
		'name'       => array( 'Full name', 'text', 'name', true ),
		'first_name' => array( 'First name', 'text', 'given-name', true ),
		'last_name'  => array( 'Last name', 'text', 'family-name', false ),
		'phone'      => array( 'Phone number', 'tel', 'tel', true ),
		'email'      => array( 'Email address', 'email', 'email', true ),
		'postcode'   => array( 'Postcode', 'text', 'postal-code', true ),
		'subject'    => array( 'Subject', 'text', 'off', true ),
		'message'    => array( 'Message', 'textarea', 'off', false ),
	);
}

/* ------------------------------------------------------------------ storage */
add_action( 'init', function () {
	register_post_type( 'mb_enquiry', array(
		'labels'          => array(
			'name'          => 'Enquiries',
			'singular_name' => 'Enquiry',
			'all_items'     => 'All enquiries',
			'edit_item'     => 'Enquiry',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 3,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

add_filter( 'manage_mb_enquiry_posts_columns', function () {
	return array( 'cb' => '<input type="checkbox">', 'title' => 'Enquiry', 'mb_contact' => 'Contact', 'mb_page' => 'Sent from', 'date' => 'Received' );
} );
add_action( 'manage_mb_enquiry_posts_custom_column', function ( $col, $id ) {
	$d = (array) get_post_meta( $id, '_mb_data', true );
	if ( 'mb_contact' === $col ) {
		echo esc_html( trim( ( $d['Phone number'] ?? '' ) . '  ' . ( $d['Email address'] ?? '' ) ) );
	}
	if ( 'mb_page' === $col ) {
		echo esc_html( get_post_meta( $id, '_mb_page', true ) );
	}
}, 10, 2 );

add_action( 'add_meta_boxes_mb_enquiry', function () {
	add_meta_box( 'mb_enquiry_data', 'Details', function ( $post ) {
		$d = (array) get_post_meta( $post->ID, '_mb_data', true );
		echo '<table class="widefat striped"><tbody>';
		foreach ( $d as $k => $v ) {
			echo '<tr><th style="width:180px">' . esc_html( $k ) . '</th><td>' . nl2br( esc_html( $v ) ) . '</td></tr>';
		}
		echo '<tr><th>Sent from</th><td>' . esc_html( get_post_meta( $post->ID, '_mb_page', true ) ) . '</td></tr>';
		echo '<tr><th>Email delivered</th><td>' . ( get_post_meta( $post->ID, '_mb_mailed', true ) ? 'Yes' : '<strong style="color:#b32d2e">No – check email settings</strong>' ) . '</td></tr>';
		echo '</tbody></table>';
	}, 'mb_enquiry', 'normal', 'high' );
} );

/* ------------------------------------------------------------------ settings */
add_action( 'admin_menu', function () {
	add_options_page( 'Mobilitybase', 'Mobilitybase', 'manage_options', 'mobilitybase', 'mb_settings_page' );
} );
add_action( 'admin_init', function () {
	register_setting( 'mobilitybase', 'mb_form_to', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'mobilitybase', 'mb_gsc_verification', array( 'sanitize_callback' => 'sanitize_text_field' ) );
} );

function mb_form_recipients() {
	$to = get_option( 'mb_form_to' ) ?: mb_biz( 'form_to' );
	return array_filter( array_map( 'trim', explode( ',', $to ) ), 'is_email' );
}

function mb_settings_page() {
	?>
	<div class="wrap">
		<h1>Mobilitybase settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mobilitybase' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="mb_form_to">Send form enquiries to</label></th>
					<td><input type="text" class="regular-text" id="mb_form_to" name="mb_form_to" value="<?php echo esc_attr( get_option( 'mb_form_to' ) ); ?>" placeholder="<?php echo esc_attr( mb_biz( 'form_to' ) ); ?>">
					<p class="description">One or more addresses, separated by commas. Leave blank to use <?php echo esc_html( mb_biz( 'form_to' ) ); ?>.</p></td></tr>
				<tr><th scope="row"><label for="mb_gsc">Google Search Console code</label></th>
					<td><input type="text" class="regular-text" id="mb_gsc" name="mb_gsc_verification" value="<?php echo esc_attr( get_option( 'mb_gsc_verification' ) ); ?>">
					<p class="description">The <code>content="…"</code> value from Search Console's HTML-tag verification.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mb_test_email">
			<?php wp_nonce_field( 'mb_test_email' ); ?>
			<?php submit_button( 'Send a test email', 'secondary' ); ?>
		</form>
		<?php if ( isset( $_GET['mb_test'] ) ) : ?>
			<div class="notice notice-<?php echo '1' === $_GET['mb_test'] ? 'success' : 'error'; ?>"><p><?php echo '1' === $_GET['mb_test'] ? 'Test email sent. Check the inbox (and spam folder).' : 'The test email could not be sent. Check the SMTP plugin settings.'; ?></p></div>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_mb_test_email', function () {
	check_admin_referer( 'mb_test_email' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	$ok = wp_mail( mb_form_recipients(), 'Mobilitybase website: test email', "This is a test from your website's enquiry system.\n\nIf you can read this, form enquiries will reach you." );
	wp_safe_redirect( admin_url( 'options-general.php?page=mobilitybase&mb_test=' . ( $ok ? '1' : '0' ) ) );
	exit;
} );

/* ------------------------------------------------------------------ shortcode */
add_shortcode( 'mb_form', 'mb_form_render' );

function mb_form_render( $atts = array() ) {
	static $n = 0;
	$n++;
	$a = shortcode_atts( array(
		'fields'        => 'name,phone,email,postcode,message',
		'title'         => 'Enquiry',
		'options'       => '',
		'options_label' => '',
		'product'       => '',
		'button'        => 'Submit',
		'id'            => 'enquiry-form-' . $n,
		'note'          => '',
	), $atts, 'mb_form' );

	$defs   = mb_form_fields();
	$fields = array_filter( array_map( 'trim', explode( ',', $a['fields'] ) ), fn( $f ) => isset( $defs[ $f ] ) );
	$fid    = sanitize_html_class( $a['id'] );
	$status = isset( $_GET['mb_sent'] ) && sanitize_key( $_GET['mb_form'] ?? '' ) === $fid ? sanitize_key( $_GET['mb_sent'] ) : '';
	$ts     = time();

	ob_start();
	?>
	<form class="mb-form" id="<?php echo esc_attr( $fid ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<?php if ( '1' === $status ) : ?>
			<div class="mb-form__msg mb-form__msg--ok" role="status" tabindex="-1">Thank you — your enquiry has been sent. A member of the Mobilitybase team will be in touch shortly. If it’s urgent, call <?php echo mb_tel(); // phpcs:ignore ?>.</div>
		<?php elseif ( '0' === $status ) : ?>
			<div class="mb-form__msg mb-form__msg--err" role="alert" tabindex="-1">Sorry, something went wrong sending your enquiry. Please check the form and try again, or call us on <?php echo mb_tel(); // phpcs:ignore ?>.</div>
		<?php endif; ?>
		<input type="hidden" name="action" value="mb_form">
		<input type="hidden" name="mb_form_id" value="<?php echo esc_attr( $fid ); ?>">
		<input type="hidden" name="mb_title" value="<?php echo esc_attr( $a['title'] ); ?>">
		<input type="hidden" name="mb_fields" value="<?php echo esc_attr( implode( ',', $fields ) ); ?>">
		<input type="hidden" name="mb_ts" value="<?php echo esc_attr( $ts . '.' . wp_hash( 'mb' . $ts ) ); ?>">
		<input type="hidden" name="mb_options_label" value="<?php echo esc_attr( $a['options_label'] ); ?>">
		<input type="hidden" name="mb_product" value="<?php echo esc_attr( $a['product'] ); ?>" data-mb-product>
		<div class="mb-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="mb_website" tabindex="-1" autocomplete="off"></label></div>
		<div class="mb-form__grid">
			<?php foreach ( $fields as $f ) :
				list( $label, $type, $ac, $req ) = $defs[ $f ];
				$id   = $fid . '-' . $f;
				$wide = in_array( $f, array( 'message', 'subject' ), true ) ? ' mb-field--wide' : '';
				?>
				<?php if ( 'message' === $f && $a['options'] ) : ?>
					<fieldset class="mb-field mb-field--wide mb-field--radios">
						<legend><?php echo esc_html( $a['options_label'] ?: 'Please choose' ); ?></legend>
						<?php foreach ( explode( '|', $a['options'] ) as $i => $opt ) : ?>
							<label><input type="radio" name="mb_option" value="<?php echo esc_attr( $opt ); ?>"<?php checked( 0, $i ); ?>> <?php echo esc_html( $opt ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>
				<div class="mb-field<?php echo esc_attr( $wide ); ?>">
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?><?php echo $req ? ' <span class="mb-req" aria-hidden="true">*</span>' : ''; ?></label>
					<?php if ( 'textarea' === $type ) : ?>
						<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $f ); ?>" rows="4"<?php echo $req ? ' required' : ''; ?>></textarea>
					<?php else : ?>
						<input id="<?php echo esc_attr( $id ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $f ); ?>" autocomplete="<?php echo esc_attr( $ac ); ?>"<?php echo $req ? ' required' : ''; ?><?php echo 'postcode' === $f ? ' autocapitalize="characters"' : ''; ?>>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<?php if ( $a['options'] && ! in_array( 'message', $fields, true ) ) : ?>
				<fieldset class="mb-field mb-field--wide mb-field--radios">
					<legend><?php echo esc_html( $a['options_label'] ?: 'Please choose' ); ?></legend>
					<?php foreach ( explode( '|', $a['options'] ) as $i => $opt ) : ?>
						<label><input type="radio" name="mb_option" value="<?php echo esc_attr( $opt ); ?>"<?php checked( 0, $i ); ?>> <?php echo esc_html( $opt ); ?></label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>
		</div>
		<p class="mb-form__foot">
			<button type="submit" class="mb-btn mb-btn--block"><?php echo esc_html( $a['button'] ); ?></button>
		</p>
		<p class="mb-form__privacy">We’ll only use your details to respond to this enquiry. See our <a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>">privacy policy</a>.</p>
	</form>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ handler */
add_action( 'admin_post_nopriv_mb_form', 'mb_form_handle' );
add_action( 'admin_post_mb_form', 'mb_form_handle' );

function mb_form_handle() {
	$ajax    = ! empty( $_SERVER['HTTP_X_MB_AJAX'] );
	$fid     = sanitize_html_class( wp_unslash( $_POST['mb_form_id'] ?? 'enquiry' ) );
	$back    = wp_get_referer() ?: home_url( '/' );
	$back    = remove_query_arg( array( 'mb_sent', 'mb_form' ), $back );
	$respond = function ( $ok, $errors = array() ) use ( $ajax, $back, $fid ) {
		if ( $ajax ) {
			wp_send_json( array( 'ok' => $ok, 'errors' => $errors ), $ok ? 200 : 422 );
		}
		wp_safe_redirect( add_query_arg( array( 'mb_sent' => $ok ? '1' : '0', 'mb_form' => $fid ), $back ) . '#' . $fid );
		exit;
	};

	// Spam checks: honeypot filled, or form submitted faster than a human could.
	$ts_parts = explode( '.', (string) wp_unslash( $_POST['mb_ts'] ?? '' ) );
	$ts       = (int) ( $ts_parts[0] ?? 0 );
	$valid_ts = isset( $ts_parts[1] ) && hash_equals( wp_hash( 'mb' . $ts ), $ts_parts[1] );
	if ( ! empty( $_POST['mb_website'] ) || ! $valid_ts || ( time() - $ts ) < 3 ) {
		// Pretend success so bots learn nothing.
		$respond( true );
	}

	$defs   = mb_form_fields();
	$fields = array_filter( explode( ',', sanitize_text_field( wp_unslash( $_POST['mb_fields'] ?? '' ) ) ), fn( $f ) => isset( $defs[ $f ] ) );
	$data   = array();
	$errors = array();
	foreach ( $fields as $f ) {
		list( $label, $type, , $req ) = $defs[ $f ];
		$raw = wp_unslash( $_POST[ $f ] ?? '' );
		$val = 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		if ( 'email' === $type && $val && ! is_email( $val ) ) {
			$errors[ $f ] = 'Please enter a valid email address.';
		}
		if ( 'tel' === $type && $val && strlen( preg_replace( '/\D/', '', $val ) ) < 10 ) {
			$errors[ $f ] = 'Please enter a valid phone number.';
		}
		if ( $req && '' === $val ) {
			$errors[ $f ] = 'This field is required.';
		}
		$data[ $label ] = $val;
	}
	if ( $errors ) {
		$respond( false, $errors );
	}

	$product = sanitize_text_field( wp_unslash( $_POST['mb_product'] ?? '' ) );
	$option  = sanitize_text_field( wp_unslash( $_POST['mb_option'] ?? '' ) );
	if ( $option ) {
		$data[ sanitize_text_field( wp_unslash( $_POST['mb_options_label'] ?? '' ) ) ?: 'Option' ] = $option;
	}
	if ( $product ) {
		$data['Interested in'] = $product;
	}
	$title = sanitize_text_field( wp_unslash( $_POST['mb_title'] ?? 'Enquiry' ) );
	$page  = esc_url_raw( wp_get_referer() ?: '' );
	$who   = $data['Full name'] ?? trim( ( $data['First name'] ?? '' ) . ' ' . ( $data['Last name'] ?? '' ) );

	$post_id = wp_insert_post( array(
		'post_type'   => 'mb_enquiry',
		'post_status' => 'private',
		'post_title'  => wp_strip_all_tags( $title . ' — ' . $who . ( $product ? ' — ' . $product : '' ) ),
	) );
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_mb_data', $data );
		update_post_meta( $post_id, '_mb_page', $page );
	}

	$body = "New website enquiry: {$title}\n\n";
	foreach ( $data as $k => $v ) {
		$body .= $k . ":\n" . ( '' === $v ? '–' : $v ) . "\n\n";
	}
	$body .= "Sent from: {$page}\nReceived: " . wp_date( 'D j M Y, H:i' ) . "\n";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$email   = $data['Email address'] ?? '';
	if ( is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', ',' ), '', $who ) . ' <' . $email . '>';
	}
	$subject = 'Website enquiry: ' . $title . ( $who ? ' — ' . $who : '' );
	$mailed  = wp_mail( mb_form_recipients(), $subject, $body, $headers );
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_mb_mailed', $mailed ? '1' : '' );
	}

	// Saved even if email fails, so we still tell the customer it went through.
	$respond( (bool) $post_id );
}
