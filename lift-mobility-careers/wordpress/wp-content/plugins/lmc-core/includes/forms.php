<?php
/**
 * Front-end forms and shortcodes: employer registration, sign-in, email verification,
 * vacancy submission/editing, package selection, contact form and public listing widgets.
 * All POST handling happens on template_redirect so we can redirect cleanly (PRG pattern).
 */
defined( 'ABSPATH' ) || exit;

class LMC_Forms {

	public static $errors = array();
	public static $old    = array();

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle_post' ), 5 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_verify' ), 6 );
		foreach ( array( 'employer_register', 'employer_login', 'submit_job', 'contact_form', 'pricing', 'sector_grid', 'job_list' ) as $sc ) {
			add_shortcode( 'lmc_' . $sc, array( __CLASS__, 'sc_' . $sc ) );
		}
		add_filter( 'login_redirect', function ( $to, $req, $user ) {
			return ( $user instanceof WP_User && in_array( 'lmc_employer', (array) $user->roles, true ) ) ? lmc_page_url( 'dashboard' ) : $to;
		}, 10, 3 );
		// Keep employers out of wp-admin and hide the admin bar for them.
		add_action( 'admin_init', function () {
			if ( wp_doing_ajax() || ! is_user_logged_in() ) {
				return;
			}
			$u = wp_get_current_user();
			if ( in_array( 'lmc_employer', (array) $u->roles, true ) && ! current_user_can( 'edit_posts' ) && 'async-upload.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
				wp_safe_redirect( lmc_page_url( 'dashboard' ) );
				exit;
			}
		} );
		add_filter( 'show_admin_bar', function ( $show ) {
			return current_user_can( 'edit_posts' ) ? $show : false;
		} );
	}

	public static function old( $k, $default = '' ) {
		return self::$old[ $k ] ?? $default;
	}

	public static function err( $k ) {
		return isset( self::$errors[ $k ] ) ? '<span class="lmc-field-error">' . esc_html( self::$errors[ $k ] ) . '</span>' : '';
	}

	public static function errors_summary() {
		if ( ! self::$errors ) {
			return '';
		}
		return lmc_notice( '<strong>Please correct the highlighted fields.</strong> ' . esc_html( self::$errors['_form'] ?? '' ) );
	}

	/** Basic spam protection: nonce, honeypot, minimum fill time. */
	private static function human_check( $action ) {
		if ( ! isset( $_POST['_lmc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_lmc_nonce'] ) ), $action ) ) {
			self::$errors['_form'] = 'Your session expired. Please try again.';
			return false;
		}
		if ( ! empty( $_POST['lmc_website_hp'] ) ) {
			self::$errors['_form'] = 'Submission rejected.';
			return false;
		}
		$t = (int) ( $_POST['_lmc_t'] ?? 0 );
		if ( $t && ( time() - $t ) < 3 && ! defined( 'LMC_TESTING' ) ) {
			self::$errors['_form'] = 'That was very quick – please try again.';
			return false;
		}
		return true;
	}

	public static function hidden( $action ) {
		return wp_nonce_field( $action, '_lmc_nonce', true, false )
			. '<input type="hidden" name="lmc_action" value="' . esc_attr( $action ) . '">'
			. '<input type="hidden" name="_lmc_t" value="' . time() . '">'
			. '<div class="lmc-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="lmc_website_hp" tabindex="-1" autocomplete="off"></label></div>';
	}

	public static function handle_post() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['lmc_action'] ) ) {
			return;
		}
		$action = sanitize_key( $_POST['lmc_action'] );
		self::$old = array_map( function ( $v ) {
			return is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : wp_unslash( $v );
		}, $_POST );
		switch ( $action ) {
			case 'lmc_register':
				self::do_register();
				break;
			case 'lmc_submit_job':
				self::do_submit_job();
				break;
			case 'lmc_choose_package':
				self::do_choose_package();
				break;
			case 'lmc_contact':
				self::do_contact();
				break;
			case 'lmc_resend_verify':
				self::do_resend_verify();
				break;
			default:
				do_action( 'lmc_post_' . $action );
		}
	}

	/* ===================== Registration ===================== */

	public static function entity_types() {
		return array(
			'limited_company' => 'Limited company',
			'plc'             => 'PLC',
			'llp'             => 'Limited liability partnership (LLP)',
			'partnership'     => 'Partnership',
			'sole_trader'     => 'Sole trader',
			'public_body'     => 'Charity, NHS or public body',
			'other'           => 'Other',
		);
	}

	private static function do_register() {
		if ( ! self::human_check( 'lmc_register' ) ) {
			return;
		}
		$p     = wp_unslash( $_POST );
		$email = sanitize_email( $p['email'] ?? '' );
		$req   = array( 'first_name' => 'First name', 'last_name' => 'Last name', 'company' => 'Company name', 'email' => 'Email', 'password' => 'Password' );
		foreach ( $req as $k => $label ) {
			if ( empty( trim( $p[ $k ] ?? '' ) ) ) {
				self::$errors[ $k ] = $label . ' is required.';
			}
		}
		if ( $email && ! is_email( $email ) ) {
			self::$errors['email'] = 'Please enter a valid email address.';
		} elseif ( $email && email_exists( $email ) ) {
			self::$errors['email'] = 'An account already exists for this email. Please sign in instead.';
		}
		if ( ! empty( $p['password'] ) && strlen( $p['password'] ) < 10 ) {
			self::$errors['password'] = 'Use at least 10 characters.';
		}
		$entity = sanitize_key( $p['entity_type'] ?? '' );
		if ( ! isset( self::entity_types()[ $entity ] ) ) {
			self::$errors['entity_type'] = 'Please choose your business type.';
		}
		if ( empty( $p['agree'] ) ) {
			self::$errors['agree'] = 'Please confirm you accept the terms to continue.';
		}
		if ( self::$errors ) {
			return;
		}
		$login = sanitize_user( strstr( $email, '@', true ), true ) ?: 'employer';
		$base  = $login;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . ( ++$i );
		}
		$uid = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $p['password'],
			'first_name'   => sanitize_text_field( $p['first_name'] ),
			'last_name'    => sanitize_text_field( $p['last_name'] ),
			'display_name' => sanitize_text_field( $p['first_name'] . ' ' . $p['last_name'] ),
			'role'         => 'lmc_employer',
		) );
		if ( is_wp_error( $uid ) ) {
			self::$errors['_form'] = $uid->get_error_message();
			return;
		}
		update_user_meta( $uid, 'lmc_company', sanitize_text_field( $p['company'] ) );
		update_user_meta( $uid, 'lmc_company_website', esc_url_raw( $p['website'] ?? '' ) );
		update_user_meta( $uid, 'lmc_phone', sanitize_text_field( $p['phone'] ?? '' ) );
		update_user_meta( $uid, 'lmc_job_title', sanitize_text_field( $p['job_title'] ?? '' ) );
		update_user_meta( $uid, 'lmc_entity_type', $entity );
		update_user_meta( $uid, 'lmc_terms_accepted', lmc_now() );
		update_user_meta( $uid, 'lmc_marketing_opt_out', empty( $p['marketing_opt_out'] ) ? 0 : 1 );
		LMC_CRM::link_registration( $uid );

		self::send_verification( $uid );
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true );
		lmc_flash( 'Your account has been created. We\'ve sent a confirmation link to <strong>' . esc_html( $email ) . '</strong> – please click it to start posting vacancies.' );
		do_action( 'lmc_employer_registered', $uid );
		wp_safe_redirect( lmc_page_url( 'dashboard' ) );
		exit;
	}

	public static function send_verification( $uid ) {
		$token = wp_generate_password( 32, false );
		update_user_meta( $uid, 'lmc_verify_hash', wp_hash_password( $token ) );
		$user = get_userdata( $uid );
		lmc_send_email( 'employer_registration', $user->user_email, array(
			'first_name' => $user->first_name ?: 'there',
			'company'    => get_user_meta( $uid, 'lmc_company', true ),
			'verify_url' => add_query_arg( array( 'lmc_verify' => $uid, 'k' => $token ), home_url( '/' ) ),
		), $uid );
		return $token;
	}

	public static function is_verified( $uid ) {
		return user_can( $uid, 'manage_options' ) || (bool) get_user_meta( $uid, 'lmc_email_verified', true ) || ! lmc_opt( 'require_verified' );
	}

	public static function handle_verify() {
		if ( empty( $_GET['lmc_verify'] ) || empty( $_GET['k'] ) ) {
			return;
		}
		$uid  = absint( $_GET['lmc_verify'] );
		$hash = get_user_meta( $uid, 'lmc_verify_hash', true );
		if ( $hash && wp_check_password( sanitize_text_field( wp_unslash( $_GET['k'] ) ), $hash ) ) {
			update_user_meta( $uid, 'lmc_email_verified', lmc_now() );
			delete_user_meta( $uid, 'lmc_verify_hash' );
			if ( get_current_user_id() === $uid ) {
				lmc_flash( 'Thanks – your email address is confirmed. You can now post vacancies.' );
				wp_safe_redirect( lmc_page_url( 'dashboard' ) );
			} else {
				wp_safe_redirect( add_query_arg( 'verified', 1, lmc_page_url( 'login' ) ) );
			}
			exit;
		}
		wp_die( 'This confirmation link is invalid or has already been used. Sign in to request a new one.', 'Link invalid', array( 'response' => 400 ) );
	}

	private static function do_resend_verify() {
		if ( ! is_user_logged_in() || ! self::human_check( 'lmc_resend_verify' ) ) {
			return;
		}
		$uid  = get_current_user_id();
		$last = (int) get_user_meta( $uid, 'lmc_verify_sent', true );
		if ( lmc_now() - $last > 120 ) {
			update_user_meta( $uid, 'lmc_verify_sent', lmc_now() );
			self::send_verification( $uid );
		}
		lmc_flash( 'We\'ve sent a new confirmation link. It can take a minute or two to arrive – please check your spam folder too.' );
		wp_safe_redirect( lmc_page_url( 'dashboard' ) );
		exit;
	}

	public static function sc_employer_register() {
		if ( is_user_logged_in() ) {
			return '<p>You are signed in. <a href="' . esc_url( lmc_page_url( 'dashboard' ) ) . '">Go to your dashboard</a>.</p>';
		}
		ob_start();
		echo self::errors_summary();
		?>
		<form method="post" class="lmc-form lmc-form--narrow" novalidate>
			<?php echo self::hidden( 'lmc_register' ); ?>
			<div class="lmc-grid-2">
				<?php self::input( 'first_name', 'First name', array( 'required' => 1, 'autocomplete' => 'given-name' ) ); ?>
				<?php self::input( 'last_name', 'Last name', array( 'required' => 1, 'autocomplete' => 'family-name' ) ); ?>
			</div>
			<?php self::input( 'job_title', 'Your job title', array( 'autocomplete' => 'organization-title' ) ); ?>
			<?php self::input( 'company', 'Company name', array( 'required' => 1, 'autocomplete' => 'organization' ) ); ?>
			<?php self::select( 'entity_type', 'Business type', self::entity_types(), array( 'required' => 1, 'placeholder' => 'Choose…' ) ); ?>
			<?php self::input( 'website', 'Company website', array( 'type' => 'url', 'placeholder' => 'https://' ) ); ?>
			<?php self::input( 'phone', 'Phone (optional)', array( 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?>
			<?php self::input( 'email', 'Work email', array( 'type' => 'email', 'required' => 1, 'autocomplete' => 'email' ) ); ?>
			<?php self::input( 'password', 'Password', array( 'type' => 'password', 'required' => 1, 'autocomplete' => 'new-password', 'help' => 'At least 10 characters.', 'no_old' => 1 ) ); ?>
			<div class="lmc-check">
				<label><input type="checkbox" name="agree" value="1" <?php checked( self::old( 'agree' ), '1' ); ?>> I accept the <a href="<?php echo esc_url( lmc_page_url( 'terms' ) ); ?>" target="_blank">Terms and Conditions</a> and <a href="<?php echo esc_url( lmc_page_url( 'agreement' ) ); ?>" target="_blank">Employer Advertising Agreement</a>, and I have read the <a href="<?php echo esc_url( lmc_page_url( 'privacy' ) ); ?>" target="_blank">Privacy Policy</a>.</label>
				<?php echo self::err( 'agree' ); ?>
			</div>
			<div class="lmc-check">
				<label><input type="checkbox" name="marketing_opt_out" value="1" <?php checked( self::old( 'marketing_opt_out' ), '1' ); ?>> I'd prefer <strong>not</strong> to receive occasional emails about advertising offers. (Service emails about your adverts are always sent.)</label>
			</div>
			<button class="lmc-btn lmc-btn--primary" type="submit">Create account</button>
			<p class="lmc-muted">Already registered? <a href="<?php echo esc_url( lmc_page_url( 'login' ) ); ?>">Sign in</a>.</p>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function sc_employer_login() {
		if ( is_user_logged_in() ) {
			return '<p>You are signed in. <a href="' . esc_url( lmc_page_url( 'dashboard' ) ) . '">Go to your dashboard</a>.</p>';
		}
		$out = '';
		if ( ! empty( $_GET['verified'] ) ) {
			$out .= lmc_notice( 'Your email address is confirmed. Please sign in.', 'success' );
		}
		if ( ! empty( $_GET['login'] ) && 'failed' === $_GET['login'] ) {
			$out .= lmc_notice( 'That email and password combination was not recognised.' );
		}
		$out .= '<div class="lmc-form lmc-form--narrow">' . wp_login_form( array(
			'echo'           => false,
			'redirect'       => lmc_page_url( 'dashboard' ),
			'label_username' => 'Email address',
			'remember'       => true,
			'value_remember' => true,
		) ) . '<p class="lmc-muted"><a href="' . esc_url( wp_lostpassword_url( lmc_page_url( 'login' ) ) ) . '">Forgotten your password?</a> · <a href="' . esc_url( lmc_page_url( 'register' ) ) . '">Create an employer account</a></p></div>';
		return $out;
	}

	/* ===================== Form helpers ===================== */

	public static function input( $name, $label, $o = array() ) {
		$type  = $o['type'] ?? 'text';
		$val   = empty( $o['no_old'] ) ? self::old( $name, $o['value'] ?? '' ) : '';
		$req   = ! empty( $o['required'] );
		$id    = 'lmc-' . $name;
		echo '<div class="lmc-field' . ( isset( self::$errors[ $name ] ) ? ' has-error' : '' ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $req ? ' <span class="req">*</span>' : '' ) . '</label>';
		if ( 'textarea' === $type ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) ( $o['rows'] ?? 6 ) . '"' . ( $req ? ' required' : '' ) . '>' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '"' . ( $req ? ' required' : '' )
				. ( isset( $o['autocomplete'] ) ? ' autocomplete="' . esc_attr( $o['autocomplete'] ) . '"' : '' )
				. ( isset( $o['placeholder'] ) ? ' placeholder="' . esc_attr( $o['placeholder'] ) . '"' : '' )
				. ( isset( $o['min'] ) ? ' min="' . esc_attr( $o['min'] ) . '"' : '' )
				. ( 'number' === $type ? ' step="any"' : '' )
				. ( isset( $o['list'] ) ? ' list="' . esc_attr( $o['list'] ) . '"' : '' ) . '>';
		}
		if ( ! empty( $o['help'] ) ) {
			echo '<span class="lmc-help">' . esc_html( $o['help'] ) . '</span>';
		}
		echo self::err( $name ) . '</div>';
	}

	public static function select( $name, $label, $options, $o = array() ) {
		$val = self::old( $name, $o['value'] ?? '' );
		$req = ! empty( $o['required'] );
		echo '<div class="lmc-field' . ( isset( self::$errors[ $name ] ) ? ' has-error' : '' ) . '"><label for="lmc-' . esc_attr( $name ) . '">' . esc_html( $label ) . ( $req ? ' <span class="req">*</span>' : '' ) . '</label><select id="lmc-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . ( $req ? ' required' : '' ) . '>';
		if ( isset( $o['placeholder'] ) ) {
			echo '<option value="">' . esc_html( $o['placeholder'] ) . '</option>';
		}
		foreach ( $options as $k => $v ) {
			if ( is_array( $v ) ) {
				echo '<optgroup label="' . esc_attr( $k ) . '">';
				foreach ( $v as $kk => $vv ) {
					echo '<option value="' . esc_attr( $kk ) . '"' . selected( (string) $val, (string) $kk, false ) . '>' . esc_html( $vv ) . '</option>';
				}
				echo '</optgroup>';
			} else {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( (string) $val, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
		}
		echo '</select>' . ( ! empty( $o['help'] ) ? '<span class="lmc-help">' . esc_html( $o['help'] ) . '</span>' : '' ) . self::err( $name ) . '</div>';
	}

	public static function term_options( $tax ) {
		$out = array();
		foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'orderby' => 'term_id' ) ) as $t ) {
			$out[ $t->slug ] = $t->name;
		}
		return $out;
	}

	public static function county_options() {
		$out = array();
		foreach ( get_terms( array( 'taxonomy' => 'lmc_location', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'term_id' ) ) as $r ) {
			$group = array();
			foreach ( get_terms( array( 'taxonomy' => 'lmc_location', 'hide_empty' => false, 'parent' => $r->term_id, 'orderby' => 'name' ) ) as $c ) {
				$group[ $c->slug ] = $c->name;
			}
			$out[ $r->name ] = $group;
		}
		return $out;
	}

	/* ===================== Vacancy submission ===================== */

	/** Simple automated content checks. Flags force a human review; they never silently reject. */
	public static function content_flags( $text ) {
		$flags    = array();
		$patterns = array(
			'possible age discrimination'      => '/\b(young|youthful|under\s?\d{2}|aged\s?\d{2}\s?-\s?\d{2}|recent graduates? only|mature person only)\b/i',
			'possible sex discrimination'      => '/\b(he will|she will|male only|female only|man for the job|handyman|salesman|no women|no men)\b/i',
			'possible race/nationality issue'  => '/\b(native english|british (citizens|nationals) only|no foreigners)\b/i',
			'fee charged to candidates'        => '/\b(registration fee|training fee payable|pay (a|the) fee|deposit required)\b/i',
			'contains possible scam signals'   => '/\b(whatsapp only|telegram|crypto|bitcoin|work from home earn)\b/i',
		);
		foreach ( $patterns as $label => $re ) {
			if ( preg_match( $re, $text, $m ) ) {
				$flags[] = $label . ' ("' . $m[0] . '")';
			}
		}
		return $flags;
	}

	private static function do_submit_job() {
		if ( ! is_user_logged_in() || ! lmc_is_employer() ) {
			self::$errors['_form'] = 'Please sign in as an employer.';
			return;
		}
		if ( ! self::human_check( 'lmc_submit_job' ) ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! self::is_verified( $uid ) ) {
			self::$errors['_form'] = 'Please confirm your email address first.';
			return;
		}
		$p      = wp_unslash( $_POST );
		$job_id = absint( $p['job_id'] ?? 0 );
		if ( $job_id && ( 'lmc_job' !== get_post_type( $job_id ) || ( (int) get_post_field( 'post_author', $job_id ) !== $uid && ! current_user_can( 'edit_others_posts' ) ) ) ) {
			self::$errors['_form'] = 'You cannot edit this vacancy.';
			return;
		}

		$title = sanitize_text_field( $p['job_title'] ?? '' );
		$desc  = wp_kses( $p['description'] ?? '', array( 'p' => array(), 'br' => array(), 'strong' => array(), 'em' => array(), 'ul' => array(), 'ol' => array(), 'li' => array() ) );
		$e     = array();
		if ( strlen( $title ) < 5 || strlen( $title ) > 100 ) {
			$e['job_title'] = 'Please enter a clear job title (5–100 characters).';
		}
		if ( strlen( wp_strip_all_tags( $desc ) ) < 200 ) {
			$e['description'] = 'Please describe the role in at least 200 characters – candidates want to know what the job involves.';
		}
		$sectors = array_filter( array_map( 'sanitize_title', (array) ( $p['sectors'] ?? array() ) ) );
		if ( ! $sectors ) {
			$e['sectors'] = 'Choose at least one sector.';
		}
		foreach ( array( 'role' => 'lmc_role', 'emp_type' => 'lmc_type', 'experience' => 'lmc_experience', 'county' => 'lmc_location' ) as $k => $tax ) {
			if ( empty( $p[ $k ] ) || ! term_exists( sanitize_title( $p[ $k ] ), $tax ) ) {
				$e[ $k ] = 'Please choose an option.';
			}
		}
		foreach ( LMC_Post_Types::fields() as $k => $f ) {
			if ( ! empty( $f['required'] ) && '' === trim( (string) ( $p[ $k ] ?? '' ) ) ) {
				$e[ $k ] = $f['label'] . ' is required.';
			}
		}
		$smin = (float) ( $p['salary_min'] ?? 0 );
		$smax = (float) ( $p['salary_max'] ?? 0 );
		$per  = in_array( $p['salary_period'] ?? '', array( 'YEAR', 'HOUR', 'DAY' ), true ) ? $p['salary_period'] : 'YEAR';
		if ( $smin <= 0 ) {
			$e['salary_min'] = 'Every advert must show a genuine salary.';
		} elseif ( 'YEAR' === $per && $smin < 1000 ) {
			$e['salary_min'] = 'For an annual salary, enter the full figure (e.g. 32000).';
		}
		if ( $smax && $smax < $smin ) {
			$e['salary_max'] = '"Salary to" must be higher than "Salary from".';
		}
		$closing = sanitize_text_field( $p['closing_date'] ?? '' );
		if ( $closing && ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $closing ) || strtotime( $closing . ' 23:59:59' ) < lmc_now() ) ) {
			$e['closing_date'] = 'Closing date must be today or later.';
		}
		$method = 'email' === ( $p['apply_method'] ?? '' ) ? 'email' : 'url';
		if ( 'url' === $method && ! preg_match( '#^https?://[^\s]+\.[^\s]+#i', $p['apply_url'] ?? '' ) ) {
			$e['apply_url'] = 'Enter the full application link, starting https://';
		}
		if ( 'email' === $method && ! is_email( $p['apply_email'] ?? '' ) ) {
			$e['apply_email'] = 'Enter a valid application email address.';
		}
		if ( empty( $p['authorised'] ) ) {
			$e['authorised'] = 'You must confirm you are authorised to advertise this vacancy.';
		}
		if ( empty( trim( $p['authoriser_name'] ?? '' ) ) ) {
			$e['authoriser_name'] = 'Please enter your full name for the declaration.';
		}
		$logo_id = 0;
		if ( ! empty( $_FILES['logo']['name'] ) ) {
			if ( empty( $p['logo_rights'] ) ) {
				$e['logo'] = 'Please confirm you have the right to use this logo.';
			} else {
				$logo_id = self::handle_logo_upload();
				if ( is_wp_error( $logo_id ) ) {
					$e['logo'] = $logo_id->get_error_message();
					$logo_id   = 0;
				}
			}
		}
		if ( $e ) {
			self::$errors = $e;
			return;
		}

		$is_new = ! $job_id;
		$data   = array(
			'post_type'    => 'lmc_job',
			'post_title'   => $title,
			'post_content' => wpautop( $desc ),
			'post_author'  => $job_id ? (int) get_post_field( 'post_author', $job_id ) : $uid,
		);
		if ( $is_new ) {
			$data['post_status'] = 'lmc_unpaid';
			$job_id              = wp_insert_post( $data, true );
			if ( is_wp_error( $job_id ) ) {
				self::$errors['_form'] = 'Could not save the vacancy. Please try again.';
				lmc_log( 'error', 'submit', $job_id->get_error_message() );
				return;
			}
		} else {
			$data['ID'] = $job_id;
			wp_update_post( $data );
		}

		foreach ( LMC_Post_Types::fields() as $k => $f ) {
			$v = $p[ $k ] ?? '';
			switch ( $f['type'] ?? 'text' ) {
				case 'url':
					$v = esc_url_raw( $v );
					break;
				case 'email':
					$v = sanitize_email( $v );
					break;
				case 'textarea':
					$v = sanitize_textarea_field( $v );
					break;
				case 'number':
					$v = '' === $v ? '' : (float) $v;
					break;
				default:
					$v = sanitize_text_field( $v );
			}
			update_post_meta( $job_id, '_lmc_' . $k, $v );
		}
		update_post_meta( $job_id, '_lmc_salary_period', $per );
		update_post_meta( $job_id, '_lmc_apply_method', $method );
		update_post_meta( $job_id, '_lmc_postcode', strtoupper( sanitize_text_field( $p['postcode'] ?? '' ) ) );
		update_post_meta( $job_id, '_lmc_salary_annual', LMC_Search::annualise( $smax ?: $smin, $per ) );
		update_post_meta( $job_id, '_lmc_authorised', array( 'name' => sanitize_text_field( $p['authoriser_name'] ), 'user' => $uid, 'time' => lmc_now(), 'version' => '1.0' ) );
		update_post_meta( $job_id, '_lmc_social_ok', empty( $p['social_ok'] ) ? 0 : 1 );
		if ( $logo_id ) {
			update_post_meta( $job_id, '_lmc_logo_id', $logo_id );
			update_post_meta( $job_id, '_lmc_logo_authorised', lmc_now() );
		} elseif ( ! empty( $p['remove_logo'] ) ) {
			delete_post_meta( $job_id, '_lmc_logo_id' );
		}
		wp_set_object_terms( $job_id, $sectors, 'lmc_sector' );
		wp_set_object_terms( $job_id, sanitize_title( $p['role'] ), 'lmc_role' );
		wp_set_object_terms( $job_id, sanitize_title( $p['emp_type'] ), 'lmc_type' );
		wp_set_object_terms( $job_id, sanitize_title( $p['experience'] ), 'lmc_experience' );
		$county = get_term_by( 'slug', sanitize_title( $p['county'] ), 'lmc_location' );
		wp_set_object_terms( $job_id, array_filter( array( $county->term_id, $county->parent ) ), 'lmc_location' );

		$flags = self::content_flags( $title . ' ' . $desc . ' ' . ( $p['requirements'] ?? '' ) . ' ' . ( $p['responsibilities'] ?? '' ) );
		update_post_meta( $job_id, '_lmc_flags', $flags );
		if ( $flags ) {
			lmc_log( 'warning', 'moderation', 'Vacancy ' . $job_id . ' flagged: ' . implode( '; ', $flags ) );
			update_user_meta( (int) get_post_field( 'post_author', $job_id ), 'lmc_trusted', 0 );
			if ( 'publish' === get_post_status( $job_id ) ) {
				wp_update_post( array( 'ID' => $job_id, 'post_status' => 'pending' ) );
				lmc_send_admin_email( 'admin_review', lmc_job_email_vars( $job_id ), $job_id );
			}
		}
		if ( 'publish' === get_post_status( $job_id ) ) {
			LMC_Location::geocode_job( $job_id );
		}

		if ( $is_new || 'lmc_unpaid' === get_post_status( $job_id ) ) {
			if ( $is_new ) {
				lmc_send_job_email( 'vacancy_submitted', $job_id, array( 'status_line' => 'Your vacancy is saved. To publish it, choose an advert option and complete payment (or use an advert credit) – you can do this from your dashboard at any time.' ) );
			}
			wp_safe_redirect( lmc_page_url( 'submit', array( 'step' => 'package', 'job' => $job_id ) ) );
		} else {
			lmc_flash( 'Your changes to "' . esc_html( $title ) . '" have been saved.' );
			wp_safe_redirect( lmc_page_url( 'dashboard' ) );
		}
		exit;
	}

	private static function handle_logo_upload() {
		$f = $_FILES['logo'];
		if ( $f['size'] > 1024 * 1024 ) {
			return new WP_Error( 'lmc', 'Logo must be under 1MB.' );
		}
		$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
		if ( empty( $check['type'] ) ) {
			return new WP_Error( 'lmc', 'Logo must be a JPG, PNG or WebP image.' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$id = media_handle_upload( 'logo', 0, array(), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
		return $id;
	}

	public static function sc_submit_job() {
		if ( ! is_user_logged_in() ) {
			return '<div class="lmc-panel"><h2>Sign in to post a vacancy</h2><p>Employers need a free account to post and manage vacancies.</p><p><a class="lmc-btn lmc-btn--primary" href="' . esc_url( lmc_page_url( 'register' ) ) . '">Create an employer account</a> <a class="lmc-btn lmc-btn--ghost" href="' . esc_url( lmc_page_url( 'login' ) ) . '">Sign in</a></p></div>';
		}
		if ( ! lmc_is_employer() ) {
			return lmc_notice( 'This area is for employer accounts.' );
		}
		$uid = get_current_user_id();
		if ( ! self::is_verified( $uid ) ) {
			return self::verify_prompt();
		}
		if ( 'package' === ( $_GET['step'] ?? '' ) && ! empty( $_GET['job'] ) ) {
			return self::package_step( absint( $_GET['job'] ), 'new' );
		}
		$job_id = absint( $_GET['job'] ?? 0 );
		if ( $job_id && ! self::$old ) {
			if ( (int) get_post_field( 'post_author', $job_id ) !== $uid && ! current_user_can( 'edit_others_posts' ) ) {
				return lmc_notice( 'You cannot edit this vacancy.' );
			}
			self::$old = array(
				'job_title'   => get_the_title( $job_id ),
				'description' => wp_strip_all_tags( str_replace( '</p>', "\n\n", get_post_field( 'post_content', $job_id ) ) ),
				'sectors'     => wp_list_pluck( lmc_job_terms( $job_id, 'lmc_sector' ), 'slug' ),
				'role'        => wp_list_pluck( lmc_job_terms( $job_id, 'lmc_role' ), 'slug' )[0] ?? '',
				'emp_type'    => wp_list_pluck( lmc_job_terms( $job_id, 'lmc_type' ), 'slug' )[0] ?? '',
				'experience'  => wp_list_pluck( lmc_job_terms( $job_id, 'lmc_experience' ), 'slug' )[0] ?? '',
				'social_ok'   => lmc_job_meta( $job_id, 'social_ok' ) ? '1' : '',
			);
			foreach ( lmc_job_terms( $job_id, 'lmc_location' ) as $t ) {
				if ( $t->parent ) {
					self::$old['county'] = $t->slug;
				}
			}
			foreach ( array_keys( LMC_Post_Types::fields() ) as $k ) {
				self::$old[ $k ] = lmc_job_meta( $job_id, $k );
			}
			self::$old['authoriser_name'] = lmc_job_meta( $job_id, 'authorised' )['name'] ?? '';
		}
		if ( ! $job_id && ! self::$old ) {
			self::$old = array(
				'employer_name'    => get_user_meta( $uid, 'lmc_company', true ),
				'employer_website' => get_user_meta( $uid, 'lmc_company_website', true ),
				'authoriser_name'  => wp_get_current_user()->display_name,
				'social_ok'        => '1',
			);
		}
		ob_start();
		echo self::errors_summary();
		$fields = LMC_Post_Types::fields();
		?>
		<form method="post" enctype="multipart/form-data" class="lmc-form" novalidate>
			<?php echo self::hidden( 'lmc_submit_job' ); ?>
			<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>">
			<fieldset><legend>The role</legend>
				<?php self::input( 'job_title', 'Job title', array( 'required' => 1, 'placeholder' => 'e.g. Stairlift Service Engineer' ) ); ?>
				<div class="lmc-field<?php echo isset( self::$errors['sectors'] ) ? ' has-error' : ''; ?>"><label>Sector(s) <span class="req">*</span></label><div class="lmc-checks">
				<?php foreach ( self::term_options( 'lmc_sector' ) as $slug => $name ) : ?>
					<label><input type="checkbox" name="sectors[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) self::old( 'sectors', array() ), true ) ); ?>> <?php echo esc_html( $name ); ?></label>
				<?php endforeach; ?>
				</div><?php echo self::err( 'sectors' ); ?></div>
				<div class="lmc-grid-3">
					<?php self::select( 'role', 'Job category', self::term_options( 'lmc_role' ), array( 'required' => 1, 'placeholder' => 'Choose…' ) ); ?>
					<?php self::select( 'emp_type', 'Employment type', self::term_options( 'lmc_type' ), array( 'required' => 1, 'placeholder' => 'Choose…' ) ); ?>
					<?php self::select( 'experience', 'Experience level', self::term_options( 'lmc_experience' ), array( 'required' => 1, 'placeholder' => 'Choose…' ) ); ?>
				</div>
				<?php self::input( 'description', 'Job description', array( 'type' => 'textarea', 'required' => 1, 'rows' => 8, 'help' => 'An overview of the role, team and company. Minimum 200 characters. Blank lines start new paragraphs.' ) ); ?>
				<?php self::input( 'responsibilities', $fields['responsibilities']['label'], array( 'type' => 'textarea', 'required' => 1, 'help' => $fields['responsibilities']['help'] ) ); ?>
				<?php self::input( 'requirements', $fields['requirements']['label'], array( 'type' => 'textarea', 'required' => 1, 'help' => $fields['requirements']['help'] ) ); ?>
				<?php self::input( 'benefits', $fields['benefits']['label'], array( 'type' => 'textarea', 'rows' => 4, 'help' => $fields['benefits']['help'] ) ); ?>
			</fieldset>
			<fieldset><legend>Location</legend>
				<div class="lmc-grid-3">
					<?php self::select( 'county', 'County / area', self::county_options(), array( 'required' => 1, 'placeholder' => 'Choose…' ) ); ?>
					<?php self::input( 'town', 'Town / city', array( 'required' => 1 ) ); ?>
					<?php self::input( 'postcode', 'Postcode or outward code', array( 'help' => 'e.g. ME14 – used for distance search.' ) ); ?>
				</div>
				<?php self::input( 'location_note', 'Location detail (optional)', array( 'help' => $fields['location_note']['help'] ) ); ?>
			</fieldset>
			<fieldset><legend>Salary</legend>
				<p class="lmc-help">Every advert on Lift and Mobility Careers shows a salary – adverts with pay information attract more applicants.</p>
				<div class="lmc-grid-3">
					<?php self::input( 'salary_min', 'Salary from (£)', array( 'type' => 'number', 'required' => 1, 'min' => 0 ) ); ?>
					<?php self::input( 'salary_max', 'Salary to (£, optional)', array( 'type' => 'number', 'min' => 0 ) ); ?>
					<?php self::select( 'salary_period', 'Period', $fields['salary_period']['options'] ); ?>
				</div>
				<?php self::input( 'salary_note', 'Package extras (optional)', array( 'help' => $fields['salary_note']['help'] ) ); ?>
			</fieldset>
			<fieldset><legend>Applications</legend>
				<?php self::select( 'apply_method', 'How should candidates apply?', $fields['apply_method']['options'] ); ?>
				<div class="lmc-grid-2">
					<?php self::input( 'apply_url', 'Application link', array( 'type' => 'url', 'placeholder' => 'https://' ) ); ?>
					<?php self::input( 'apply_email', 'Application email', array( 'type' => 'email' ) ); ?>
				</div>
				<?php self::input( 'closing_date', 'Closing date (optional)', array( 'type' => 'date', 'help' => 'If earlier than the end of the listing period, the advert will close on this date.' ) ); ?>
			</fieldset>
			<fieldset><legend>Employer</legend>
				<div class="lmc-grid-2">
					<?php self::input( 'employer_name', 'Employer name shown on the advert', array( 'required' => 1 ) ); ?>
					<?php self::input( 'employer_website', 'Employer website', array( 'type' => 'url' ) ); ?>
				</div>
				<div class="lmc-field<?php echo isset( self::$errors['logo'] ) ? ' has-error' : ''; ?>">
					<label for="lmc-logo">Employer logo (optional – JPG, PNG or WebP, max 1MB)</label>
					<input id="lmc-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
					<?php if ( $job_id && lmc_job_meta( $job_id, 'logo_id' ) ) : ?>
						<label class="lmc-inline"><input type="checkbox" name="remove_logo" value="1"> Remove the current logo</label>
					<?php endif; ?>
					<label class="lmc-inline"><input type="checkbox" name="logo_rights" value="1"> I confirm the employer owns this logo or has permission to use it, and authorises Lift and Mobility Careers to display it with this vacancy.</label>
					<?php echo self::err( 'logo' ); ?>
				</div>
			</fieldset>
			<fieldset class="lmc-declaration"><legend>Declaration</legend>
				<div class="lmc-check<?php echo isset( self::$errors['authorised'] ) ? ' has-error' : ''; ?>"><label><input type="checkbox" name="authorised" value="1" <?php checked( self::old( 'authorised' ), '1' ); ?>> I confirm that I am authorised by the employer to advertise this vacancy, that it is a genuine, current vacancy, that the information is accurate and lawful, and that I accept the <a href="<?php echo esc_url( lmc_page_url( 'authorisation' ) ); ?>" target="_blank">Vacancy Authorisation Declaration</a> and <a href="<?php echo esc_url( lmc_page_url( 'licence' ) ); ?>" target="_blank">Content Licence</a>.</label><?php echo self::err( 'authorised' ); ?></div>
				<?php self::input( 'authoriser_name', 'Your full name', array( 'required' => 1 ) ); ?>
				<div class="lmc-check"><label><input type="checkbox" name="social_ok" value="1" <?php checked( self::old( 'social_ok' ), '1' ); ?>> Lift and Mobility Careers may promote this vacancy on its LinkedIn and Facebook pages.</label></div>
			</fieldset>
			<button class="lmc-btn lmc-btn--primary" type="submit"><?php echo $job_id ? 'Save changes' : 'Continue to advert options'; ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function verify_prompt() {
		return '<div class="lmc-panel"><h2>Please confirm your email address</h2><p>We sent a confirmation link to <strong>' . esc_html( wp_get_current_user()->user_email ) . '</strong>. Click the link in that email to start posting vacancies.</p><form method="post">' . self::hidden( 'lmc_resend_verify' ) . '<button class="lmc-btn lmc-btn--ghost" type="submit">Send the link again</button></form></div>';
	}

	/* ===================== Package / payment step ===================== */

	public static function package_step( $job_id, $purpose = 'new' ) {
		$uid = get_current_user_id();
		if ( 'lmc_job' !== get_post_type( $job_id ) || ( (int) get_post_field( 'post_author', $job_id ) !== $uid && ! current_user_can( 'edit_others_posts' ) ) ) {
			return lmc_notice( 'Vacancy not found.' );
		}
		$status = get_post_status( $job_id );
		if ( 'new' === $purpose && 'lmc_unpaid' !== $status ) {
			return lmc_notice( 'This vacancy has already been paid for.', 'info' ) . '<p><a href="' . esc_url( lmc_page_url( 'dashboard' ) ) . '">Back to dashboard</a></p>';
		}
		$credits = LMC_Credits::balance( $uid );
		$pk      = lmc_packages();
		ob_start();
		echo self::errors_summary();
		?>
		<div class="lmc-package-step">
			<p class="lmc-eyebrow"><?php echo 'renew' === $purpose ? 'Renew advert' : 'Step 2 of 2 – choose your advert'; ?></p>
			<h2><?php echo esc_html( get_the_title( $job_id ) ); ?></h2>
			<?php if ( 'renew' === $purpose ) : ?>
				<p>Renewing adds <?php echo (int) lmc_opt( 'listing_days' ); ?> days. <?php echo 'publish' === $status ? 'The extra time is added after the current end date (' . esc_html( lmc_date( lmc_job_meta( $job_id, 'expires' ) ) ) . ').' : 'The advert goes live again as soon as the renewal is complete.'; ?></p>
			<?php endif; ?>
			<?php if ( array_sum( $credits ) ) : ?>
				<div class="lmc-panel lmc-panel--credit">
					<h3>Use an advert credit</h3>
					<?php foreach ( $credits as $tier => $n ) : ?>
						<form method="post" class="lmc-inline-form"><?php echo self::hidden( 'lmc_choose_package' ); ?>
							<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>"><input type="hidden" name="purpose" value="<?php echo esc_attr( $purpose ); ?>">
							<input type="hidden" name="package" value="credit_<?php echo esc_attr( $tier ); ?>">
							<button class="lmc-btn lmc-btn--primary" type="submit">Use 1 <?php echo esc_html( $tier ); ?> credit</button> <span class="lmc-muted">You have <?php echo (int) $n; ?> available.</span>
						</form>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! LMC_Payments::enabled() ) : ?>
				<?php echo lmc_notice( 'Online card payment is not available just yet. Your vacancy is saved – please <a href="' . esc_url( lmc_page_url( 'contact' ) ) . '">contact us</a> and we\'ll arrange your advert.', 'info' ); ?>
			<?php endif; ?>
			<div class="lmc-pricing">
				<?php foreach ( array( 'standard', 'featured', 'premium' ) as $k ) : ?>
					<?php echo self::package_card( $k, $pk[ $k ], $job_id, $purpose ); ?>
				<?php endforeach; ?>
			</div>
			<?php if ( 'new' === $purpose ) : ?>
				<h3>Recruiting regularly? Buy a pack</h3>
				<p class="lmc-muted">Packs give you standard advert credits – one is used for this vacancy, the rest stay in your account for 12 months.</p>
				<div class="lmc-pricing lmc-pricing--packs">
					<?php foreach ( array( 'pack5', 'pack10' ) as $k ) : ?>
						<?php echo self::package_card( $k, $pk[ $k ], $job_id, $purpose ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function package_card( $key, $pkg, $job_id = 0, $purpose = 'new', $cta = true ) {
		$out  = '<div class="lmc-price-card lmc-price-card--' . esc_attr( $key ) . '">';
		$out .= '<h3>' . esc_html( $pkg['label'] ) . '</h3><p class="lmc-price">' . esc_html( lmc_money( $pkg['price'], true ) ) . '<span>' . esc_html( lmc_price_suffix() ) . ( $pkg['credits'] > 1 ? '' : ' / ' . (int) lmc_opt( 'listing_days' ) . ' days' ) . '</span></p>';
		if ( $pkg['credits'] > 1 ) {
			$out .= '<p class="lmc-muted">' . esc_html( lmc_money( $pkg['price'] / $pkg['credits'], true ) ) . ' per advert</p>';
		}
		$out .= '<ul>';
		foreach ( $pkg['features'] as $f ) {
			$out .= '<li>' . esc_html( $f ) . '</li>';
		}
		$out .= '</ul>';
		if ( $cta && null === $job_id ) {
			$out .= '<a class="lmc-btn lmc-btn--ghost" href="' . esc_url( lmc_page_url( 'dashboard', array( 'tab' => 'credits' ) ) ) . '">Buy from your dashboard</a>';
		}
		if ( $cta && $job_id !== null ) {
			if ( $job_id || $purpose === 'credits' ) {
				$disabled = LMC_Payments::enabled() ? '' : ' disabled';
				$out     .= '<form method="post">' . self::hidden( 'lmc_choose_package' ) . '<input type="hidden" name="job_id" value="' . esc_attr( $job_id ) . '"><input type="hidden" name="purpose" value="' . esc_attr( $purpose ) . '"><input type="hidden" name="package" value="' . esc_attr( $key ) . '"><button class="lmc-btn lmc-btn--primary" type="submit"' . $disabled . '>Choose and pay ' . esc_html( lmc_money( $pkg['price'] + lmc_vat_amount( $pkg['price'] ) ) ) . '</button></form>';
			} else {
				$out .= '<a class="lmc-btn lmc-btn--primary" href="' . esc_url( lmc_page_url( 'submit' ) ) . '">Post a vacancy</a>';
			}
		}
		return $out . '</div>';
	}

	private static function do_choose_package() {
		if ( ! is_user_logged_in() || ! self::human_check( 'lmc_choose_package' ) ) {
			return;
		}
		$uid     = get_current_user_id();
		$job_id  = absint( $_POST['job_id'] ?? 0 );
		$purpose = in_array( $_POST['purpose'] ?? '', array( 'new', 'renew', 'credits' ), true ) ? $_POST['purpose'] : 'new';
		$package = sanitize_key( $_POST['package'] ?? '' );
		if ( $job_id && ( 'lmc_job' !== get_post_type( $job_id ) || (int) get_post_field( 'post_author', $job_id ) !== $uid ) && ! current_user_can( 'edit_others_posts' ) ) {
			self::$errors['_form'] = 'Vacancy not found.';
			return;
		}
		if ( 'new' === $purpose && $job_id && 'lmc_unpaid' !== get_post_status( $job_id ) ) {
			wp_safe_redirect( lmc_page_url( 'dashboard' ) );
			exit;
		}
		if ( 0 === strpos( $package, 'credit_' ) ) {
			$tier = substr( $package, 7 );
			if ( $job_id && LMC_Credits::consume( $uid, $tier ) ) {
				$res = LMC_Jobs::activate( $job_id, $tier, $purpose );
				lmc_flash( 'pending' === $res ? 'Thanks – your vacancy is with our team for a quick check and will go live shortly. We\'ll email you when it\'s published.' : ( 'renewed' === $res ? 'Your advert has been renewed.' : 'Your vacancy is now live.' ) );
				wp_safe_redirect( lmc_page_url( 'dashboard' ) );
				exit;
			}
			self::$errors['_form'] = 'No credit of that type is available.';
			return;
		}
		$url = LMC_Payments::create_checkout( $uid, $package, $job_id, $purpose );
		if ( is_wp_error( $url ) ) {
			self::$errors['_form'] = 'We could not start the payment: ' . $url->get_error_message();
			return;
		}
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect -- Stripe-hosted checkout.
		exit;
	}

	/* ===================== Contact form ===================== */

	private static function do_contact() {
		if ( ! self::human_check( 'lmc_contact' ) ) {
			return;
		}
		$p = wp_unslash( $_POST );
		foreach ( array( 'name' => 'Name', 'email' => 'Email', 'message' => 'Message' ) as $k => $l ) {
			if ( empty( trim( $p[ $k ] ?? '' ) ) ) {
				self::$errors[ $k ] = $l . ' is required.';
			}
		}
		if ( ! empty( $p['email'] ) && ! is_email( $p['email'] ) ) {
			self::$errors['email'] = 'Please enter a valid email address.';
		}
		$ipkey = 'lmc_rl_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
		$count = (int) get_transient( $ipkey );
		if ( $count >= 5 ) {
			self::$errors['_form'] = 'Too many messages – please try again later.';
		}
		if ( self::$errors ) {
			return;
		}
		set_transient( $ipkey, $count + 1, HOUR_IN_SECONDS );
		lmc_send_admin_email( 'admin_contact', array(
			'sender_name'    => sanitize_text_field( $p['name'] ),
			'sender_email'   => sanitize_email( $p['email'] ),
			'sender_company' => sanitize_text_field( $p['company'] ?? '' ),
			'topic'          => sanitize_text_field( $p['topic'] ?? '' ),
			'message'        => sanitize_textarea_field( $p['message'] ),
		) );
		wp_safe_redirect( add_query_arg( 'sent', 1, get_permalink() ) );
		exit;
	}

	public static function sc_contact_form() {
		if ( ! empty( $_GET['sent'] ) ) {
			return lmc_notice( 'Thank you – your message has been sent. We\'ll reply by email, usually within one working day.', 'success' );
		}
		ob_start();
		echo self::errors_summary();
		?>
		<form method="post" class="lmc-form lmc-form--narrow" novalidate>
			<?php echo self::hidden( 'lmc_contact' ); ?>
			<div class="lmc-grid-2">
				<?php self::input( 'name', 'Your name', array( 'required' => 1, 'autocomplete' => 'name' ) ); ?>
				<?php self::input( 'email', 'Email', array( 'type' => 'email', 'required' => 1, 'autocomplete' => 'email' ) ); ?>
			</div>
			<?php self::input( 'company', 'Company (optional)', array( 'autocomplete' => 'organization' ) ); ?>
			<?php self::select( 'topic', 'Topic', array( 'Advertising a vacancy' => 'Advertising a vacancy', 'My employer account' => 'My employer account', 'Report a problem with an advert' => 'Report a problem with an advert', 'Something else' => 'Something else' ) ); ?>
			<?php self::input( 'message', 'Message', array( 'type' => 'textarea', 'required' => 1 ) ); ?>
			<p class="lmc-muted">We use your details only to reply to your message. See our <a href="<?php echo esc_url( lmc_page_url( 'privacy' ) ); ?>">Privacy Policy</a>.</p>
			<button class="lmc-btn lmc-btn--primary" type="submit">Send message</button>
		</form>
		<?php
		return ob_get_clean();
	}

	/* ===================== Public widgets ===================== */

	public static function sc_pricing() {
		$pk  = lmc_packages();
		$out = '<div class="lmc-pricing">';
		foreach ( array( 'standard', 'featured', 'premium' ) as $k ) {
			$out .= self::package_card( $k, $pk[ $k ], 0, 'new' );
		}
		$out .= '</div><div class="lmc-pricing lmc-pricing--packs">';
		foreach ( array( 'pack5', 'pack10' ) as $k ) {
			$out .= self::package_card( $k, $pk[ $k ], null );
		}
		$note = lmc_opt( 'vat_registered' ) ? 'Prices exclude VAT, which is added at checkout.' : 'No VAT is currently charged.';
		return $out . '</div><p class="lmc-muted lmc-center">' . esc_html( $note ) . ' Pack credits can be used for 12 months. See our <a href="' . esc_url( lmc_page_url( 'refunds' ) ) . '">refund policy</a>.</p>';
	}

	public static function sc_sector_grid( $atts ) {
		$atts   = shortcode_atts( array( 'detailed' => '0' ), $atts );
		$counts = LMC_Search::sector_counts();
		$terms  = get_terms( array( 'taxonomy' => 'lmc_sector', 'hide_empty' => false, 'meta_key' => 'lmc_order', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) );
		$out    = '<div class="lmc-sectors' . ( '1' === $atts['detailed'] ? ' lmc-sectors--detailed' : '' ) . '">';
		foreach ( $terms as $t ) {
			$n    = $counts[ $t->slug ] ?? 0;
			$icon = function_exists( 'lmc_theme_icon' ) ? lmc_theme_icon( get_term_meta( $t->term_id, 'lmc_icon', true ) ) : '';
			$out .= '<a class="lmc-sector" href="' . esc_url( get_term_link( $t ) ) . '">' . $icon . '<span class="lmc-sector__name">' . esc_html( $t->name ) . '</span><span class="lmc-sector__count">' . ( $n ? $n . ' live ' . _n( 'vacancy', 'vacancies', $n ) : 'View sector' ) . '</span>';
			if ( '1' === $atts['detailed'] ) {
				$out .= '<span class="lmc-sector__intro">' . esc_html( wp_trim_words( $t->description, 28 ) ) . '</span>';
			}
			$out .= '</a>';
		}
		return $out . '</div>';
	}

	public static function sc_job_list( $atts ) {
		$a = shortcode_atts( array( 'sector' => '', 'role' => '', 'location' => '', 'limit' => 10, 'tier' => '', 'empty' => '' ), $atts );
		$f = array_merge( array_fill_keys( array( 'q', 'type', 'experience', 'sort' ), '' ), array( 'salary_min' => 0, 'radius' => 30, 'sector' => $a['sector'], 'role' => $a['role'], 'location' => $a['location'] ) );
		$args = LMC_Search::build_args( $f, array( 'posts_per_page' => (int) $a['limit'], 'no_found_rows' => true ) );
		if ( $a['tier'] ) {
			$args['meta_query'][] = array( 'key' => '_lmc_tier', 'value' => explode( ',', $a['tier'] ), 'compare' => 'IN' );
		}
		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			$msg = $a['empty'] ?: 'There are no live vacancies matching this right now. New roles are added regularly – <a href="' . esc_url( get_post_type_archive_link( 'lmc_job' ) ) . '">browse all vacancies</a>.';
			return '<p class="lmc-empty">' . wp_kses_post( $msg ) . '</p>';
		}
		ob_start();
		echo '<div class="lmc-job-list">';
		while ( $q->have_posts() ) {
			$q->the_post();
			lmc_render_job_card( get_the_ID() );
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
}
LMC_Forms::init();

/** Job card renderer (the theme can override with template-parts/job-card.php). */
function lmc_render_job_card( $job_id ) {
	$tpl = locate_template( 'template-parts/job-card.php' );
	if ( $tpl ) {
		include $tpl;
		return;
	}
	echo '<article class="lmc-job-card"><h3><a href="' . esc_url( get_permalink( $job_id ) ) . '">' . esc_html( get_the_title( $job_id ) ) . '</a></h3><p>' . esc_html( lmc_job_meta( $job_id, 'employer_name' ) ) . ' · ' . esc_html( lmc_location_text( $job_id ) ) . ' · ' . esc_html( lmc_salary_text( $job_id ) ) . '</p></article>';
}
