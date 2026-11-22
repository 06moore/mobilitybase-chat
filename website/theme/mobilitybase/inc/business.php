<?php
/**
 * Company details used across the site and in schema markup.
 * Change them here and every page updates.
 */

defined( 'ABSPATH' ) || exit;

function mb_business() {
	return array(
		'name'         => 'Mobilitybase',
		'legal_name'   => 'Mobilitybase Ltd',
		'phone'        => '0208 936 3060',
		'phone_e164'   => '+442089363060',
		'email'        => 'info@mobilitybase.co.uk',
		// Where enquiry forms are sent. Can be overridden in Settings → Mobilitybase.
		'form_to'      => 'service@mobilitybase.co.uk',
		'hours'        => 'Mon - Fri: 9am - 5pm',
		'street'       => 'Unit 29, Branbridges Industrial Estate, Branbridges Road',
		'locality'     => 'East Peckham',
		'town'         => 'Tonbridge',
		'region'       => 'Kent',
		'postcode'     => 'TN12 5HF',
		'founded'      => '2016',
		'area_served'  => array( 'Kent', 'London', 'East Sussex', 'South East England' ),
		'social'       => array(
			'facebook'  => 'https://www.facebook.com/mobilitybase',
			'instagram' => 'https://www.instagram.com/mobilitybase_uk/',
			'linkedin'  => 'https://uk.linkedin.com/company/mobilitybase',
		),
	);
}

function mb_biz( $key ) {
	$b = mb_business();
	return $b[ $key ] ?? '';
}
