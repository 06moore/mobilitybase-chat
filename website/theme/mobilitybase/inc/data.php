<?php
/**
 * Structured content for the product, warranty and services pages.
 * Edit here to add a model, change a price or reword a card.
 */

defined( 'ABSPATH' ) || exit;

/** Product categories (Products page + home page cards). */
function mb_products() {
	return array(
		'straight-stairlift-range' => array(
			'name'     => 'Straight Stairlifts',
			'intro'    => 'Our straight stairlifts provide a safe, reliable and cost-effective solution for staircases without turns or bends. Designed for smooth operation and everyday comfort, they are ideal for improving access within your home.',
			'suitable' => 'Suitable for straight staircases in domestic properties.',
			'gallery'  => array( 'd0215bb86f4a4021.webp', '12eee1d0ab304517.webp', 'd4bdfa3388cc4843.webp', '8b332689969e4286.webp', '58d58892e8c14f82.webp' ),
		),
		'curved-stairlift-range'   => array(
			'name'     => 'Curved Stairlifts',
			'intro'    => 'Our curved stairlifts are designed for staircases with turns, corners or intermediate landings. Built to fit your staircase precisely, they provide smooth, reliable travel while maintaining comfort and safety.',
			'suitable' => 'Suitable for curved or multi-landing staircases in domestic properties.',
			'gallery'  => array( '264afb5dc6804925.webp', 'c0b7ddee9ede4a0b.webp', '1c117770724443aa.webp', '7f5928a6ff134f93.webp', 'a5e5a3e86b9040e5.webp', '6b31182ec0a54c9e.webp' ),
		),
		'homelift-range'           => array(
			'name'     => 'Homelifts',
			'intro'    => 'Our homelifts provide a smooth, safe and discreet way to move between floors without the need for a staircase. Designed for comfort and ease of use, they are ideal for improving accessibility while maintaining the look and feel of your home.',
			'suitable' => 'Suitable for residential properties.',
			'gallery'  => array( '8d0f92577e83468f.webp', 'df39ed80a7d7434d.webp', '77675b2739df4cca.webp', 'ed6998ad9dc7472f.webp', 'bac30f4d12f14711.webp', '68b3050398874e27.webp' ),
		),
		'platform-lift-range'      => array(
			'name'     => 'Platform Lifts',
			'intro'    => 'Our platform lifts provide a safe and practical solution for overcoming changes in level, both internally and externally. Designed for reliability and ease of use, they are suitable for wheelchair users and pedestrian access where step-free entry is required.',
			'suitable' => 'Suitable for residential and commercial properties.',
			'gallery'  => array( '7ac87c3ef4144be1.webp', 'edfa82ec3845483e.webp', '6d2d0d017a4b4a4e.webp', '9e5139031a1d4fdb.webp', 'a00f3c9655104cdd.webp' ),
		),
		'ceiling-hoist-range'      => array(
			'name'     => 'Ceiling Hoists',
			'intro'    => 'Our ceiling hoists provide a safe and practical solution for assisted transfers within the home or care environment. Designed for smooth, controlled lifting, they help reduce manual handling risks while improving comfort and dignity.',
			'suitable' => 'Suitable for residential and care settings.',
			'gallery'  => array( '71da667fc0a3418d.webp', '40120e0ddc6d498b.webp', '816916a9f8f34187.webp', 'de7173f4eb7448bd.webp' ),
		),
	);
}

/** Range pages: models offered in each product category. */
function mb_ranges() {
	$survey = 'Request a free home survey to help determine the most suitable lift for your property. We’ll provide a clear, no-obligation quote based on both your current needs and what may benefit you in the future.';
	return array(
		'straight-stairlift-range' => array(
			'h1'     => 'Straight Stairlifts Supplied & Installed',
			'intro'  => 'Select the straight stairlift you’re interested in below and send us your enquiry. One of our team will review the information and contact you to arrange a survey or answer any questions.',
			'models' => array(
				array( 'HomeGlide', 'd0215bb86f4a4021.webp', 'Access HomeGlide straight stairlift', array(
					'The Access HomeGlide is a compact, easy-to-use straight stairlift that fits neatly onto your staircase. It provides a smooth and secure ride, helping you move safely and confidently around your home.',
					'The lift is fixed directly to the stairs which allows for a straightforward installation with minimal disruption. It supports users up to 25 stone and travels at a steady, comfortable speed.',
				) ),
				array( 'Brooks 130', '8b332689969e4286.webp', 'Brooks straight stairlift', array(
					'The Brooks 130 is a reliable and easy-to-use straight stairlift designed with safety and comfort in mind. It includes a seatbelt, obstruction sensors and a swivel seat for safe exiting at the top of the stairs.',
					'With simple controls and a smooth, comfortable ride, it’s a practical option for everyday use. An optional hinged rail can also help reduce trip hazards at the bottom of the staircase.',
				) ),
				array( 'Handicare 1100', '12eee1d0ab304517.webp', 'Handicare 1100 straight stairlift', array(
					'The Handicare 1100 is a slim and modern straight stairlift designed to fit neatly onto narrow staircases. Its compact rail keeps more space available on the stairs, while providing a smooth and quiet ride.',
					'With a low-maintenance design and simple controls, it offers a safe and reliable way to move comfortably around your home.',
				) ),
			),
			'cross'  => array( 'If your staircase has turns or landings, you may need a curved stairlift', '/curved-stairlift-range' ),
			'survey' => $survey,
		),
		'curved-stairlift-range'   => array(
			'h1'     => 'Curved Stairlifts Supplied & Installed',
			'intro'  => 'Select the curved stairlift you’re interested in below and send us your enquiry. One of our team will review the information and contact you to arrange a survey or answer any questions.',
			'models' => array(
				array( 'Bespoke Infinity', '34d624b112c14cf7.webp', 'Infinity curved stairlift on curved staircase', array(
					'The Bespoke stairlift is designed to follow the exact shape and layout of your staircase, providing a smooth and comfortable ride between floors. Each lift is made to measure, ensuring it fits neatly and works perfectly with the design of your home.',
					'The rail is tailored specifically to your staircase and fitted directly to the stairs, allowing for a secure installation with minimal disruption. This customised solution ensures safe, reliable travel up and down even the most complex staircases.',
				) ),
				// NOTE: the old site showed the Brooks 130 text here by mistake. Placeholder copy below — please check.
				array( 'Freecurve', 'cb2415c719fe4cd9.webp', 'Handicare Freecurve stairlift installed on curved staircase', array(
					'The Handicare Freecurve is a curved stairlift built around a slim rail that is shaped to follow the turns and landings of your staircase, giving a smooth and comfortable ride from bottom to top.',
					'Because the rail is made to fit your stairs closely, it keeps as much space as possible on the staircase for other members of the household. Simple controls, a swivel seat and safety sensors make it a practical choice for everyday use.',
				) ),
				array( 'Reconditioned 260', '264afb5dc6804925.webp', 'Stannah 260 stairlift on curved staircase', array(
					'The reconditioned Stannah 260 is a reliable curved stairlift designed to follow the shape of your staircase, providing a smooth and comfortable ride between floors. Each lift is carefully refurbished and tested to ensure safe and dependable operation.',
					'The rail is made to measure for your staircase and fitted directly to the stairs rather than the wall, allowing for a secure installation with minimal disruption. This is a cost-effective solution that still provides the quality and reliability Stannah is known for.',
				) ),
			),
			'cross'  => array( 'Straight staircase? Take a look at our straight stairlifts', '/straight-stairlift-range' ),
			'survey' => $survey,
		),
		'homelift-range'           => array(
			'h1'     => 'Home Lifts Supplied & Installed',
			'intro'  => 'Select the home lift you’re interested in below and send us your enquiry. One of our team will review the information and contact you to discuss your requirements, answer any questions, or arrange a survey if needed.',
			'models' => array(
				array( 'Pollock Residential', '12382f7f0c994f6c.webp', 'Pollock home lift installed in living room', array(
					'The Pollock Residential Lift provides a safe and convenient way to travel between floors in your home. Designed with comfort and reliability in mind, it offers smooth and quiet operation while blending neatly into your living space.',
					'The lift travels through a small floor aperture and is designed to be installed with minimal structural work, making it a practical solution for many homes. With its simple controls and compact design, the Pollock Residential Lift offers safe and dependable access between floors for everyday use.',
				) ),
				array( 'Stiltz Duo', '5c5d03734bca4239.webp', 'Stiltz home lift installed in bedroom', array(
					'The Stiltz Duo Home Lift is a compact and stylish home lift designed to provide safe and convenient travel between floors. Its self-supporting structure allows it to be installed almost anywhere in the home without the need for load-bearing walls.',
					'The lift travels through a small floor aperture and plugs into a standard household power supply, making installation straightforward with minimal disruption. The Stiltz Duo is designed for smooth, quiet operation and offers a practical solution for moving comfortably between floors.',
				) ),
				array( 'Pollock Eco', '60adcfbb5e514f34.webp', 'Pollock through floor lift installed in dining room', array(
					'The Pollock Eco Lift is a compact through-floor lift designed to provide safe and convenient access between floors in your home. It offers smooth and reliable travel while fitting neatly into your living space.',
					'The lift travels through a small floor aperture and can be installed with minimal building work, making it a practical solution for many properties. With simple controls and a clean, modern design, the Pollock Eco Lift provides safe and dependable movement between floors for everyday use.',
				) ),
			),
			'cross'  => array( 'Need step-free access for a wheelchair? See our platform lifts', '/platform-lift-range' ),
			'survey' => $survey,
		),
		'platform-lift-range'      => array(
			'h1'     => 'Platform Lifts Supplied & Installed',
			'intro'  => 'Select the platform lift you’re interested in below and send us your enquiry. One of our team will review the information and contact you to discuss your requirements, answer any questions, or arrange a survey if needed.',
			'models' => array(
				array( 'Platinum Altura', '437ec0729ae6415a.webp', 'Platinum Altura platform lift', array(
					'The Platinum Altura is a modern platform lift designed to provide safe and convenient access between levels in your home. Built with comfort, reliability, and accessibility in mind, it offers smooth and quiet operation while blending neatly into your living space.',
					'The lift is installed within a compact structure and can travel between floors with minimal building work required. With simple controls and a spacious platform design, the Platinum Altura provides safe and dependable access for wheelchair users and those with limited mobility.',
				) ),
				array( 'Pollock 65 Series', '7ac87c3ef4144be1.webp', 'Pollock 65 platform lift', array(
					'The Pollock 65 Series is a robust platform lift designed to provide safe and convenient access between levels. It is ideal for wheelchair users and those with limited mobility, offering smooth and reliable travel in both domestic and commercial environments.',
					'The lift is installed within a compact structure and can travel between floors with minimal building work required. With its simple controls, spacious platform, and durable design, the Pollock 65 Series provides safe and dependable access for everyday use.',
				) ),
				array( 'Pollock 40 Series', 'c183aa407a584b36.webp', 'Pollock 40 Series platform lift', array(
					'The Pollock 40 Series is a compact platform lift designed to provide safe and convenient access between levels in your home. It offers smooth and reliable operation while fitting neatly into a wide range of properties.',
					'The lift is installed within a compact structure and can be fitted with minimal building work, making it a practical solution where space is limited. With simple controls and a spacious platform design, the Pollock 40 Series provides safe and dependable access for wheelchair users and those with reduced mobility.',
				) ),
			),
			'cross'  => array( 'Moving between floors inside your home? See our homelifts', '/homelift-range' ),
			'survey' => $survey,
		),
		'ceiling-hoist-range'      => array(
			'h1'     => 'Ceiling Hoists Supplied & Installed',
			'intro'  => 'Select the ceiling hoist you’re interested in below and send us your enquiry. One of our team will review the information and contact you to discuss your requirements, answer any questions, or arrange a survey if needed.',
			'models' => array(
				array( 'Wispa 100', '71da667fc0a3418d.webp', 'Ceiling hoist system for safe patient transfer', array(
					'The Wispa 100 Ceiling Hoist is a reliable and discreet lifting solution designed to assist with safe and comfortable transfers around the home. Built with ease of use, safety, and durability in mind, it provides smooth and quiet operation while blending neatly into your living environment.',
					'The hoist is installed on a ceiling track system, allowing users to be safely transferred between key areas such as the bed, wheelchair, bathroom, or chair. With simple controls and a compact, lightweight design, the Wispa 100 offers a dependable solution for individuals with limited mobility while also reducing strain for carers.',
				) ),
			),
			'cross'  => null,
			'survey' => $survey,
		),
	);
}

/** Warranty overview cards (Aftercare & Warranty page). */
function mb_warranty_products() {
	return array(
		array( 'Straight Stairlifts', '/straight-stairlift-warranty', 'Our straight stairlift warranty and service packages are designed to keep your lift running safely and reliably. Options range from basic cover to full parts, labour and ongoing care, depending on the level of protection you’d like.', 'Suitable for straight stairlifts installed in domestic properties.', 'd4bdfa3388cc4843.webp', 'from £95' ),
		array( 'Curved Stairlifts', '/curved-stairlift-warranty', 'Our curved stairlift warranty and service packages are designed to support the more complex nature of curved systems. Cover options are available to help maintain safe operation, reduce unexpected repair costs, and provide ongoing support.', 'Suitable for curved stairlifts installed in domestic properties.', 'c0b7ddee9ede4a0b.webp', 'from £95' ),
		array( 'Homelifts', '/homelift-warranty', 'Our homelift warranty and service packages are designed to support safe, reliable day-to-day use. Cover options are available to help with ongoing maintenance, servicing, and repairs, depending on the level of protection you choose.', 'Suitable for homelifts installed in domestic properties.', '8d0f92577e83468f.webp', 'from £145' ),
		array( 'Ceiling Hoists', '/ceiling-hoist-warranty', 'Our ceiling hoist warranty and service packages are designed to support safe and reliable lifting for daily use. Cover options are available to help with ongoing servicing, maintenance, and repairs, depending on the level of cover selected.', 'Suitable for ceiling hoists installed in domestic properties.', '40120e0ddc6d498b.webp', 'from £95' ),
		array( 'Platform Lifts', '', 'Our platform lift warranty and service packages are designed to support safe, reliable operation in both domestic and commercial settings. Cover options are available to assist with servicing, maintenance, and repairs, depending on the level of protection required.', 'Suitable for platform lifts installed in domestic and commercial properties.', 'edfa82ec3845483e.webp', 'Coming soon' ),
	);
}

/** Warranty plan pages. Prices: Bronze / Silver / Gold per year. */
function mb_warranties() {
	$base = array(
		'bronze' => array( 'Essential cover with an annual service and access to breakdown support when you need it.', 'Pay as you go', array( '1 annual service visit included', 'Pay-as-you-go breakdown support', 'Reduced rates for parts', 'Office hours engineer availability', 'Automatic renewal reminder' ) ),
		'silver' => array( 'Added peace of mind with priority breakdown cover and reduced repair costs.', 'Valid for 12 months', array( '1 annual service visit included', 'Priority breakdown cover', '24/7/365 availability', 'Reduced rates for parts', 'Automatic renewal reminder' ) ),
		'gold'   => array( 'Complete cover with priority response, parts and labour included for total peace of mind.', 'Valid for 12 months', array( '1 annual service visit included', 'Priority breakdown cover', '24/7/365 availability', 'Parts and labour included', 'All essential parts supplied FOC', 'Automatic renewal reminder' ) ),
	);
	$notes = array(
		'For new customers, an initial service visit is required to assess the condition of your lift. Any parts needed to return the lift to a safe working condition will be chargeable before warranty cover begins.',
		'Bronze includes access to our 24/7 on-call receptionist to log faults, with engineer attendance normally the next working day (subject to availability).',
		'Bronze and Silver plans include reduced rates on parts.',
		'Where parts are obsolete and no longer available new, suitable second-hand parts may be supplied where possible.',
		'Lifts over 3 years old that are considered uneconomical to repair or a total loss are not covered under warranty. In these cases, a discount will be applied towards the cost of a replacement lift.',
		'Main component failures on lifts over 3 years old (such as the main drive motor or main controller PCB) may be considered uneconomical to repair.',
		'Gold cover includes unlimited call-outs and all standard parts.',
		'All accounts are payable in advance.',
	);
	$make = function ( $label, $lower, $prices ) use ( $base, $notes ) {
		$plans = array();
		foreach ( array( 'bronze', 'silver', 'gold' ) as $i => $tier ) {
			$plans[ $tier ] = array(
				'name'    => ucfirst( $tier ),
				'price'   => $prices[ $i ],
				'summary' => $base[ $tier ][0],
				'term'    => $base[ $tier ][1],
				'items'   => $base[ $tier ][2],
				'best'    => 'gold' === $tier,
			);
		}
		return array(
			'label' => $label,
			'h1'    => strtoupper( $label ) . ' WARRANTY PLANS',
			'intro' => "Choose from our warranty pricing plans designed specifically for {$lower}. Select a plan below to view the available options and submit an enquiry.",
			'plans' => $plans,
			'notes' => $notes,
		);
	};
	return array(
		'straight-stairlift-warranty' => $make( 'Straight Stairlift', 'straight stairlifts', array( 95, 245, 345 ) ),
		'curved-stairlift-warranty'   => $make( 'Curved Stairlift', 'curved stairlifts', array( 95, 345, 445 ) ),
		'homelift-warranty'           => $make( 'Homelift', 'homelifts', array( 145, 645, 795 ) ),
		'ceiling-hoist-warranty'      => $make( 'Ceiling Hoist', 'ceiling hoists', array( 95, 345, 445 ) ),
	);
}

/** Services page cards. */
function mb_services() {
	return array(
		array( 'Servicing & Maintenance', '/lift-servicing-kent', 'shield', 'Regular servicing helps keep your lift running safely and reliably. We carry out routine inspections, testing and adjustments to reduce the risk of breakdowns and extend the life of your equipment.', 'Suitable for residential and commercial lifts.' ),
		array( 'Call-Out & Repairs', '/lift-repairs-kent', 'wrench', 'If your lift has stopped working or isn’t operating as it should, we can attend to diagnose and carry out repairs. This service is suitable for breakdowns, faults, and urgent issues that need investigating on site.', 'Available for stairlifts, platform lifts, homelifts and ceiling hoists' ),
		array( 'New Installations', '/lift-installations', 'star', 'We supply and install a range of mobility equipment, including stairlifts, platform lifts and homelifts. Following an initial survey, we’ll recommend the most suitable solution for your property and needs.', 'Honest advice, professional installation and full aftercare.' ),
		array( 'Stairlift & Lift Removal', '/stairlift-removal-kent', 'arrow', 'If a lift is no longer required, we can safely remove it and dispose of the equipment where appropriate. This service is commonly used following house sales, property changes or end-of-life equipment.', 'Available for all lift types.' ),
		array( 'LOLER Examinations', '/loler-examinations', 'check', 'A LOLER examination is a thorough, independent safety inspection required for many lifting devices. We carry out detailed examinations and provide the necessary certification for compliance.', 'Ideal for care homes, landlords and commercial premises.' ),
		array( 'Surveys & Advice', '/survey-advice', 'pin', 'Not sure what you need? We offer site surveys and practical advice to help you understand your options. This is ideal if you’re planning future works or need guidance before committing.', 'No obligation, clear and honest recommendations.' ),
	);
}

/** Brands strip on the home page. */
function mb_brands() {
	return array(
		array( '37777dd120194d1c.webp', 'Access stairlifts logo' ),
		array( 'd44927b15530433e.webp', 'Pollock lifts homelift logo' ),
		array( '4fa8ffc0fb584eb8.webp', 'Handicare stairlift logo' ),
		array( 'a8d0ac9a27114044.webp', 'Chiltern Invadex logo' ),
	);
}

/**
 * Prices shown on the site. Leave a value empty ('') to hide it everywhere.
 * Removal prices are the ones already published on the old site.
 * New-lift "from" prices are empty until confirmed.
 */
function mb_prices() {
	return array(
		'removal_straight' => 174,
		'removal_curved'   => 270,
		'plan_from'        => 95,   // cheapest warranty & service plan per year
		'service_oneoff'   => '',   // one-off annual service visit (e.g. 125)
		'callout'          => '',   // repair call-out fee
		'straight_from'    => '',   // new straight stairlift, fitted
		'curved_from'      => '',   // new curved stairlift, fitted
		'recon_from'       => '',   // reconditioned stairlift, fitted
		'homelift_from'    => '',   // homelift, fitted
		'platform_from'    => '',   // platform lift, fitted
	);
}

function mb_price( $key, $prefix = 'from £' ) {
	$v = mb_prices()[ $key ] ?? '';
	return '' === $v ? '' : $prefix . number_format_i18n( (float) $v );
}

/** Brands we service (used on home page and servicing sections). */
function mb_serviced_brands() {
	return array( 'Stannah', 'Acorn', 'Brooks', 'Handicare / Minivator', 'Otolift', 'Thyssen / Flow', 'Platinum', 'Stiltz', 'Pollock', 'Terry', 'Wessex', 'Chiltern Invadex' );
}

/** Towns with their own pages. */
function mb_towns() {
	return array(
		'maidstone' => 'Maidstone', 'tonbridge' => 'Tonbridge', 'tunbridge-wells' => 'Tunbridge Wells', 'sevenoaks' => 'Sevenoaks',
		'paddock-wood' => 'Paddock Wood', 'kings-hill' => 'Kings Hill', 'west-malling' => 'West Malling', 'aylesford' => 'Aylesford',
		'east-peckham' => 'East Peckham', 'edenbridge' => 'Edenbridge', 'crowborough' => 'Crowborough', 'cranbrook' => 'Cranbrook',
		'staplehurst' => 'Staplehurst', 'tenterden' => 'Tenterden', 'marden' => 'Marden',
	);
}

/**
 * Hero + form settings for the "money" pages. Each gets a headline area with
 * selling points, an optional price badge and an enquiry form above the fold.
 *   ticks   – three short selling points
 *   price   – badge text (empty to hide)
 *   form    – [mb_form] attributes
 *   cross   – related links shown under the hero
 */
function mb_landings() {
	$p        = mb_prices();
	$removal  = array(
		'ticks' => array( 'Straight, curved & outdoor stairlifts removed', 'Staircase left clean, tidy and safe', 'Any make — even if it no longer works' ),
		'price' => $p['removal_straight'] ? sprintf( 'Straight from £%s · Curved from £%s', $p['removal_straight'], $p['removal_curved'] ) : '',
		'form'  => 'fields="name,phone,email,postcode,message" title="Stairlift removal quote" options="Straight stairlift|Curved stairlift|Other / Not sure" options_label="What type of stairlift is it?" button="Get my removal quote"',
		'form_title' => 'Get a free removal quote',
	);
	$install  = array(
		'ticks' => array( 'Straight, curved & reconditioned stairlifts', 'Free, no-obligation home survey', 'Installed & serviced by our own engineers' ),
		'price' => '',
		'form'  => 'fields="name,phone,email,postcode,message" title="Stairlift survey request" options="Straight stairlift|Curved stairlift|Not sure / Need advice" options_label="What type of stairs do you have?" button="Book my free survey"',
		'form_title' => 'Book a free home survey',
	);
	$service_form = 'fields="name,phone,email,postcode,message" title="%s" options="Stairlift|Homelift / through-floor lift|Platform lift|Ceiling hoist" options_label="What type of lift is it?" button="%s"';
	return array(
		'lift-servicing-kent' => array(
			'ticks' => array( 'All makes & models serviced', 'Clear price before any extra work', 'Annual plans with 24/7 breakdown cover' ),
			'price' => $p['plan_from'] ? 'Service plans from £' . $p['plan_from'] . ' a year' : '',
			'form'  => sprintf( $service_form, 'Service booking', 'Book my service' ),
			'form_title' => 'Book a service',
		),
		'lift-repairs-kent' => array(
			'ticks' => array( 'Stairlifts, homelifts, platform lifts & hoists', 'Fault finding on all major makes', 'Gold & Silver plans: 24/7 breakdown line' ),
			'price' => $p['callout'] ? 'Call-out from £' . $p['callout'] : '',
			'form'  => sprintf( $service_form, 'Repair request', 'Request a repair' ),
			'form_title' => 'Report a fault',
			'urgent' => true,
		),
		'loler-examinations' => array(
			'ticks' => array( 'Thorough examinations with certification', 'Care homes, landlords & commercial sites', 'Lifts and hoists of all makes' ),
			'price' => '',
			'form'  => 'fields="name,phone,email,postcode,message" title="LOLER enquiry" button="Request a LOLER quote"',
			'form_title' => 'Request a LOLER quote',
		),
		'lift-installations' => array(
			'ticks' => array( 'Stairlifts, homelifts, platform lifts & hoists', 'Free, no-obligation home survey', 'Full aftercare from local engineers' ),
			'price' => '',
			'form'  => 'fields="name,phone,email,postcode,message" title="Installation enquiry" options="Stairlift|Homelift / through-floor lift|Platform lift|Ceiling hoist|Not sure yet" options_label="What are you interested in?" button="Book my free survey"',
			'form_title' => 'Book a free home survey',
		),
		'survey-advice' => array(
			'ticks' => array( 'Engineer-led, honest advice', 'No obligation, no pressure', 'Homes, care homes & commercial sites' ),
			'price' => '',
			'form'  => 'fields="name,phone,email,postcode,message" title="Survey request" button="Book my survey"',
			'form_title' => 'Book a survey',
		),
		'lift-removals' => array_merge( $removal, array(
			'ticks' => array( 'Stairlifts, homelifts & platform lifts removed', 'Ideal for house sales & renovations', 'Equipment removed and disposed of properly' ),
			'form'  => 'fields="name,phone,email,postcode,message" title="Lift removal quote" options="Straight stairlift|Curved stairlift|Homelift / through-floor lift|Platform lift|Other" options_label="What needs removing?" button="Get my removal quote"',
		) ),
		'stairlift-removal-kent' => $removal,
		'stairlift-installation-kent' => $install,
		'_removal' => $removal,
		'_install' => $install,
	);
}

/** Landing settings for the current page, if it has any. */
function mb_landing( $slug ) {
	$l = mb_landings();
	if ( isset( $l[ $slug ] ) ) {
		return $l[ $slug ];
	}
	if ( str_starts_with( $slug, 'stairlift-removal-' ) ) {
		return $l['_removal'];
	}
	if ( str_starts_with( $slug, 'stairlift-installation-' ) ) {
		return $l['_install'];
	}
	return null;
}

/**
 * Customer reviews, copied word-for-word from the Google Business Profile.
 * The reviews section stays hidden until at least one review is added here.
 * Format: array( 'Name as shown on Google', stars 1-5, 'Review text', 'Month Year', 'what we did (optional)' ),
 */
function mb_reviews() {
	return apply_filters( 'mb_reviews', array(
		// Copied word for word from Google. Texts ending in … are Google's shortened preview –
		// replace with the full review text when available. Last value = topics, used to
		// show the most relevant reviews first on each page (install, service, repair, removal).
		array( 'Mus Mustafa', 5, 'Tom and the team at Mobility Base are outstanding. They recently installed a stairlift for my mum and also maintain our son’s through-floor lift. Their service is consistently 5-star — responsive, professional, reliable, and very competitively priced. It’s rare to find a company that genuinely cares and delivers this level of quality. I highly recommend them without hesitation.', 'May 2026', 'Stairlift installation & homelift servicing', array( 'install', 'service' ) ),
		array( 'Sybylla Scarlett', 5, 'New through floor lift installed by Tom and his team last week. All work was carried out professionally and within timeframe allocated. Carpets were protected throughout and rooms were thoroughly vacuumed before the men left. Handover given. I’m very pleased with the lift and wholeheartedly recommend Mobilitybase to anyone in the south-east who is looking for help to get upstairs. Tom and his team are really good people.', 'January 2026', 'Through-floor lift installation', array( 'install' ) ),
		array( 'Judith Anderson', 5, 'Removed my Mum’s stair lift after her death. Very easy to deal with, polite operative and good notification re time of arrival. Would definitely recommend. This is a company who say “YES, when can we help?”.', 'March 2026', 'Stairlift removal', array( 'removal' ) ),
		array( 'Robert Clarke', 5, 'Great service and good advice. They even promised to remind when the next service is due and what needs doing.', 'May 2026', 'Servicing', array( 'service' ) ),
		array( 'Adrian Hanstock', 5, 'We recently needed extensive diagnostic and repair work to resolve a fault, and Tom at Mobility Base was exceptional…', 'June 2026', 'Lift repair', array( 'repair' ) ),
		array( 'Craig Matthews', 5, 'Mobilitybase service 2 hoists and a lift for us. The team of people from office staff to engineers are professional and…', 'August 2026', 'Hoist & lift servicing', array( 'service' ) ),
		array( 'Wendi Austin-Joseph', 5, 'Excellent Customer Service & Care Always!! I highly recommend this company.', 'August 2026', '', array() ),
		array( 'Janet', 5, 'I cannot tell you how impressed we were with service wehad from your technician that can out on Sunday evening to repair a 93 year old neighbours chair lift. His name was Tom. There was no hesitation about coming out on a weekend. He was polite and so patient with our neighbour who may I add is deaf. The service was excellent we would recommend the company 100%.', 'August 2026', 'Out-of-hours call-out', array( 'repair' ) ),
		array( 'Maureen Kelly', 5, 'I’m so pleased with the service I received from MobilityBase. The team were kind, knowledgeable, and incredibly…', 'May 2026', '', array() ),
	) );
}

/** Which reviews to show first on a landing/town page. */
function mb_review_topic( $slug ) {
	foreach ( array( 'removal' => 'removal', 'repair' => 'repair', 'servicing' => 'service', 'loler' => 'service', 'install' => 'install', 'survey' => 'install' ) as $needle => $topic ) {
		if ( str_contains( $slug, $needle ) ) {
			return $topic;
		}
	}
	return '';
}

/** Overall Google rating. Leave 'rating' empty to hide the summary. */
function mb_review_summary() {
	return apply_filters( 'mb_review_summary', array(
		'rating' => '5.0',
		'count'  => '28',
		'url'    => 'https://share.google/gGAWaCYnCdwy9BHJo',   // link to the Google reviews, e.g. https://g.page/r/XXXX
		'write'  => '',   // "write a review" link to text to customers, e.g. https://g.page/r/XXXX/review
	) );
}
