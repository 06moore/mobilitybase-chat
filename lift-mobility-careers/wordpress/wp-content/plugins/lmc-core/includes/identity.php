<?php
/**
 * Business identity – ONE source of truth for who operates the site.
 *
 * Settings → Business details → "Business structure" switches everything at once:
 *   sole_trader: "[Owner name], trading as Lift and Mobility Careers", of [trading address]
 *   limited:     "Lift & Mobility Careers Ltd, a company registered in England and Wales
 *                 (company number …), registered office …"
 *
 * Used by the legal pages (via the [lmc_business] shortcode), the site footer, every email
 * footer, outreach sender identification and the launch checklist. Moving from sole trader to
 * limited company is a settings change only – no page or template edits.
 */
defined( 'ABSPATH' ) || exit;

function lmc_is_limited() {
	return 'limited' === lmc_opt( 'business_structure' );
}

/** Normalise names so "Lift & Mobility Careers Ltd" matches the trading name "Lift and Mobility Careers". */
function lmc_name_key( $s ) {
	$s = strtolower( str_replace( '&', 'and', (string) $s ) );
	$s = preg_replace( '/\b(ltd|limited)\b\.?/', '', $s );
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
}

/**
 * Returns the operator identity and a list of anything still missing.
 * Keys: structure, operator (short legal name line), operator_full (with number/address),
 *       footer (one line for site/email footers), address (for correspondence), registered_office,
 *       company_number, contact_email, ico, missing[]
 */
function lmc_identity() {
	$trading = lmc_opt( 'business_name' );
	$one     = function ( $s ) {
		return trim( preg_replace( '/\s*\n\s*/', ', ', trim( (string) $s ) ) );
	};
	$missing = array();
	$address = $one( lmc_opt( 'postal_address' ) );
	if ( lmc_is_limited() ) {
		$company = trim( (string) lmc_opt( 'company_name' ) );
		$number  = trim( (string) lmc_opt( 'company_number' ) );
		$office  = $one( lmc_opt( 'registered_office' ) );
		foreach ( array( 'company name' => $company, 'company number' => $number, 'registered office' => $office ) as $l => $v ) {
			if ( '' === $v ) {
				$missing[] = $l;
			}
		}
		$operator = $company . ( lmc_name_key( $company ) !== lmc_name_key( $trading ) ? ', trading as ' . $trading : '' );
		$full     = $operator . ', a company registered in England and Wales (company number ' . $number . '), registered office: ' . $office;
		if ( $address && $address !== $office ) {
			$full .= '; correspondence address: ' . $address;
		}
		$footer   = implode( ' · ', array_filter( array( $company, $number ? 'Registered in England and Wales no. ' . $number : '', $office ? 'Registered office: ' . $office : '', lmc_opt( 'vat_number' ) ? 'VAT no. ' . lmc_opt( 'vat_number' ) : '' ) ) );
		$address  = $address ?: $office;
	} else {
		$owner = trim( (string) lmc_opt( 'owner_name' ) );
		if ( '' === $owner ) {
			$missing[] = 'owner name';
		}
		if ( '' === $address ) {
			$missing[] = 'trading address';
		}
		$operator = $owner . ', trading as ' . $trading;
		$full     = $operator . ', of ' . $address;
		$footer   = implode( ' · ', array_filter( array( $owner ? $trading . ' is a trading name of ' . $owner : '', $address, lmc_opt( 'vat_number' ) ? 'VAT no. ' . lmc_opt( 'vat_number' ) : '' ) ) );
	}
	$email = (string) lmc_opt( 'contact_email' );
	if ( '' === trim( $email ) || false !== strpos( $email, 'example.' ) ) {
		$missing[] = 'contact email';
	}
	return array(
		'structure'         => lmc_is_limited() ? 'limited' : 'sole_trader',
		'trading_name'      => $trading,
		'operator'          => $operator,
		'operator_full'     => $full,
		'footer'            => $footer,
		'address'           => $address,
		'registered_office' => lmc_is_limited() ? $one( lmc_opt( 'registered_office' ) ) : '',
		'company_number'    => lmc_is_limited() ? (string) lmc_opt( 'company_number' ) : '',
		'contact_email'     => $email,
		'ico'               => (string) lmc_opt( 'ico_number' ),
		'missing'           => $missing,
	);
}

/**
 * [lmc_business show=operator_full] – used inside the legal pages so they always match the settings.
 * Anything not yet set renders as a visible "[NOT SET: …]" marker, which the launch checklist detects.
 */
add_shortcode( 'lmc_business', function ( $atts ) {
	$a  = shortcode_atts( array( 'show' => 'operator' ), $atts );
	$id = lmc_identity();
	$v  = array(
		'operator'       => in_array( 'owner name', $id['missing'], true ) || in_array( 'company name', $id['missing'], true ) ? '' : $id['operator'],
		'operator_full'  => array_diff( $id['missing'], array( 'contact email' ) ) ? '' : $id['operator_full'],
		'trading_name'   => $id['trading_name'],
		'address'        => $id['address'],
		'contact_email'  => in_array( 'contact email', $id['missing'], true ) ? '' : $id['contact_email'],
		'ico'            => $id['ico'],
		'hosting'        => (string) lmc_opt( 'hosting_provider' ),
		'email_provider' => (string) lmc_opt( 'email_provider' ),
		'updated'        => lmc_opt( 'legal_updated' ) ? wp_date( 'j F Y', strtotime( lmc_opt( 'legal_updated' ) ) ) : '',
	);
	$labels = array( 'operator' => 'business owner name', 'operator_full' => 'business details – ' . implode( ', ', array_diff( $id['missing'], array( 'contact email' ) ) ), 'address' => 'trading address', 'contact_email' => 'contact email', 'ico' => 'ICO registration number', 'hosting' => 'hosting provider', 'email_provider' => 'email provider', 'updated' => 'legal pages date' );
	$key = sanitize_key( $a['show'] );
	if ( ! isset( $v[ $key ] ) ) {
		return '';
	}
	if ( '' === trim( $v[ $key ] ) ) {
		return '<mark class="lmc-not-set">[NOT SET: ' . esc_html( $labels[ $key ] ?? $key ) . ']</mark>';
	}
	return 'contact_email' === $key ? '<a href="mailto:' . esc_attr( antispambot( $v[ $key ] ) ) . '">' . esc_html( antispambot( $v[ $key ] ) ) . '</a>' : esc_html( $v[ $key ] );
} );
