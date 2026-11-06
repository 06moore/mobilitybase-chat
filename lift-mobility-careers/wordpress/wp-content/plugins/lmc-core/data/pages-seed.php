<?php
/** Pages created on activation. Content is editable afterwards in WP Admin → Pages. */
defined( 'ABSPATH' ) || exit;

/** Minimal Markdown → HTML for the bundled legal drafts (headings, lists, bold, links, paragraphs). */
function lmc_md_to_html( $md ) {
	$md    = preg_replace( '/<!--.*?-->/s', '', $md );
	$lines = preg_split( '/\r?\n/', trim( $md ) );
	$html  = '';
	$para  = array();
	$list  = null;
	$flush = function () use ( &$para, &$html ) {
		if ( $para ) {
			$html .= '<p>' . implode( ' ', $para ) . "</p>\n";
			$para  = array();
		}
	};
	$close = function () use ( &$list, &$html ) {
		if ( $list ) {
			$html .= "</$list>\n";
			$list  = null;
		}
	};
	$inline = function ( $t ) {
		$t = esc_html( $t );
		$t = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t );
		$t = preg_replace( '/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $t );
		return $t;
	};
	foreach ( $lines as $line ) {
		if ( preg_match( '/^(#{1,4})\s+(.*)$/', $line, $m ) ) {
			$flush();
			$close();
			$lvl   = max( 2, strlen( $m[1] ) );
			$html .= "<h$lvl>" . $inline( $m[2] ) . "</h$lvl>\n";
		} elseif ( preg_match( '/^\s*([-*]|\d+\.)\s+(.*)$/', $line, $m ) ) {
			$flush();
			$type = ctype_digit( rtrim( $m[1], '.' ) ) ? 'ol' : 'ul';
			if ( $list !== $type ) {
				$close();
				$html .= "<$type>\n";
				$list  = $type;
			}
			$html .= '<li>' . $inline( $m[2] ) . "</li>\n";
		} elseif ( '' === trim( $line ) ) {
			$flush();
			$close();
		} elseif ( preg_match( '/^>\s?(.*)$/', $line, $m ) ) {
			$flush();
			$close();
			$html .= '<div class="lmc-legal-flag">' . $inline( $m[1] ) . "</div>\n";
		} else {
			$close();
			$para[] = $inline( trim( $line ) );
		}
	}
	$flush();
	$close();
	return $html;
}

function lmc_legal_page_content( $file ) {
	$path = LMC_DIR . 'data/legal/' . $file . '.md';
	if ( ! file_exists( $path ) ) {
		return '<p>Draft pending.</p>';
	}
	$md = file_get_contents( $path );
	$md = preg_replace( '/^#\s+.*$/m', '', $md, 1 ); // Page title comes from WordPress.
	return lmc_md_to_html( $md );
}

function lmc_seed_pages() {
	return array(
		'home'          => array( 'title' => 'Home', 'slug' => 'home', 'content' => '', 'seo_title' => 'Lift and Mobility Careers – Stairlift, Home Lift & Hoist Engineer Jobs UK', 'seo_desc' => 'The UK job board for stairlift, home lift, platform lift and hoist engineers. Browse installation, service and LOLER inspector jobs free and apply direct to employers.' ),
		'sectors'       => array(
			'title'     => 'Engineering Sectors',
			'slug'      => 'engineering-sectors',
			'order'     => 2,
			'seo_title' => 'Engineering Sectors – Stairlifts, Home Lifts, Platform Lifts & Hoists',
			'seo_desc'  => 'Explore engineering careers across stairlifts, through-floor lifts, home lifts, platform lifts, access lifts, ceiling track hoists and patient handling equipment.',
			'content'   => "<p>Lift and Mobility Careers focuses on one industry: the engineering that keeps people moving safely at home, at work and in public buildings. Choose a sector to see live vacancies and what the work involves.</p>\n[lmc_sector_grid detailed=\"1\"]",
		),
		'employers'     => array(
			'title'     => 'For Employers',
			'slug'      => 'for-employers',
			'order'     => 3,
			'seo_title' => 'Recruit Stairlift, Lift & Hoist Engineers – For Employers',
			'seo_desc'  => 'Advertise engineering vacancies to a specialist audience of stairlift, home lift, platform lift and hoist engineers. Fixed prices, no placement fees.',
			'content'   => "<h2>Reach engineers who already know the industry</h2>\n<p>Generalist job sites bury specialist roles among thousands of unrelated engineering adverts. Lift and Mobility Careers is built only for the stairlift, home lift, platform lift, access lift and patient handling sector, so your vacancy is seen by people looking for exactly this kind of work.</p>\n<h2>How it works</h2>\n<ol><li><strong>Create a free employer account</strong> – it takes a couple of minutes.</li><li><strong>Post your vacancy</strong> using our structured form. Salary, location and requirements are shown clearly to candidates.</li><li><strong>Choose an advert</strong> and pay securely by card.</li><li><strong>Go live</strong> – candidates apply directly to you by your own link or email. We never charge a placement fee.</li><li><strong>Stay in control</strong> – edit, close or renew adverts from your dashboard. We remind you before an advert expires.</li></ol>\n<h2>What we do not do</h2>\n<p>We are an advertising platform, not a recruitment agency. We do not screen candidates, hold CVs or take a fee when you hire. Applications go straight to you.</p>\n[lmc_pricing]\n<p><a class=\"lmc-btn lmc-btn--primary\" href=\"/employer/register/\">Create an employer account</a></p>",
		),
		'advertise'     => array(
			'title'     => 'Advertise a Vacancy',
			'slug'      => 'advertise-a-vacancy',
			'order'     => 4,
			'seo_title' => 'Advertise a Vacancy – Stairlift & Lift Engineer Job Adverts from £39',
			'seo_desc'  => 'Post a stairlift, home lift, platform lift or hoist engineer vacancy from £39 for 30 days. Featured and premium options, multi-advert packs available.',
			'content'   => "<p>Simple, fixed-price adverts for 30 days. No subscriptions, no placement fees and no long-term contract.</p>\n[lmc_pricing]\n<h2>Before you post</h2>\n<ul><li>You will need to confirm you are authorised to advertise the vacancy on behalf of the employer.</li><li>Every advert must show a genuine salary or salary range.</li><li>Adverts must comply with our <a href=\"/advertising-agreement/\">Employer Advertising Agreement</a>, including equality law.</li></ul>\n<p><a class=\"lmc-btn lmc-btn--primary\" href=\"/employer/submit-vacancy/\">Post a vacancy</a> <a class=\"lmc-btn lmc-btn--ghost\" href=\"/employer/register/\">Create an account</a></p>",
		),
		'about'         => array(
			'title'     => 'About Us',
			'slug'      => 'about-us',
			'order'     => 5,
			'seo_title' => 'About Lift and Mobility Careers',
			'seo_desc'  => 'Lift and Mobility Careers is an independent UK job board dedicated to engineering careers in the stairlift, home lift, platform lift and patient handling industry.',
			'content'   => "<p><strong>Lift and Mobility Careers is an independent UK job board dedicated to one specialist industry: the lifts, hoists and accessibility equipment that help people move safely and live independently.</strong></p>\n<p>Engineers in this sector do skilled, important work – yet their vacancies are often scattered across generalist job sites, mixed in with unrelated roles. We bring them together in one place, with clear information about salary, location and what each role involves.</p>\n<h2>Our principles</h2>\n<ul><li><strong>Specialist only.</strong> Every vacancy relates to stairlifts, home and platform lifts, access lifts, hoists, patient handling or accessibility equipment.</li><li><strong>Free for candidates.</strong> Browsing and applying is always free, and you never need an account.</li><li><strong>Direct to employer.</strong> You apply to the employer, not to us. We don't hold CVs or act as a recruitment agency.</li><li><strong>Salary shown.</strong> Every advert includes a salary or salary range.</li><li><strong>Authorised adverts only.</strong> We only publish vacancies that employers have authorised us to show.</li></ul>\n<h2>Independent</h2>\n<p>We are not owned by, and do not favour, any manufacturer or service company. Employers of all sizes advertise on the same terms.</p>",
		),
		'contact'       => array( 'title' => 'Contact', 'slug' => 'contact', 'order' => 6, 'seo_title' => 'Contact Lift and Mobility Careers', 'seo_desc' => 'Get in touch with Lift and Mobility Careers about advertising a vacancy, your employer account or anything else.', 'content' => "<p>Questions about advertising, your account or a vacancy you've seen? Send us a message and we'll reply by email, usually within one working day.</p>\n<p>Candidates: please apply to vacancies using the application button on each advert – we can't pass on CVs or applications.</p>\n[lmc_contact_form]" ),
		'account'       => array( 'title' => 'Employer area', 'slug' => 'employer', 'content' => '[lmc_employer_dashboard]', 'noindex' => 1 ),
		'register'      => array( 'title' => 'Create an employer account', 'slug' => 'register', 'parent' => 'account', 'content' => '[lmc_employer_register]', 'noindex' => 1 ),
		'login'         => array( 'title' => 'Employer sign in', 'slug' => 'sign-in', 'parent' => 'account', 'content' => '[lmc_employer_login]', 'noindex' => 1 ),
		'dashboard'     => array( 'title' => 'Employer dashboard', 'slug' => 'dashboard', 'parent' => 'account', 'content' => '[lmc_employer_dashboard]', 'noindex' => 1 ),
		'submit'        => array( 'title' => 'Post a vacancy', 'slug' => 'submit-vacancy', 'parent' => 'account', 'content' => '[lmc_submit_job]', 'noindex' => 1 ),
		'preferences'   => array( 'title' => 'Email preferences', 'slug' => 'email-preferences', 'content' => '[lmc_email_preferences]', 'noindex' => 1 ),
		'terms'         => array( 'title' => 'Terms and Conditions', 'slug' => 'terms', 'content' => lmc_legal_page_content( 'terms-and-conditions' ) ),
		'privacy'       => array( 'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => lmc_legal_page_content( 'privacy-policy' ) ),
		'cookies'       => array( 'title' => 'Cookie Policy', 'slug' => 'cookie-policy', 'content' => lmc_legal_page_content( 'cookie-policy' ) ),
		'agreement'     => array( 'title' => 'Employer Advertising Agreement', 'slug' => 'advertising-agreement', 'content' => lmc_legal_page_content( 'employer-advertising-agreement' ) ),
		'refunds'       => array( 'title' => 'Refund Policy', 'slug' => 'refund-policy', 'content' => lmc_legal_page_content( 'refund-policy' ) ),
		'authorisation' => array( 'title' => 'Vacancy Authorisation Declaration', 'slug' => 'vacancy-authorisation', 'content' => lmc_legal_page_content( 'vacancy-authorisation-declaration' ) ),
		'licence'       => array( 'title' => 'Employer Permission and Content Licence', 'slug' => 'content-licence', 'content' => lmc_legal_page_content( 'employer-content-licence' ) ),
		'disclaimer'    => array( 'title' => 'Website Disclaimer', 'slug' => 'disclaimer', 'content' => lmc_legal_page_content( 'website-disclaimer' ) ),

		// Curated SEO landing pages (only combinations with real search demand – no thin pages).
		'lp_stairlift_service' => array(
			'title'     => 'Stairlift Service Engineer Jobs',
			'slug'      => 'stairlift-service-engineer-jobs',
			'seo_title' => 'Stairlift Service Engineer Jobs UK – Salaries Shown',
			'seo_desc'  => 'Stairlift service engineer jobs across the UK. Field-based servicing, repair and breakdown roles with company vehicles – apply direct to employers.',
			'content'   => "<p>Stairlift service engineers are the backbone of the industry. Each installed stairlift needs regular servicing, safety checks and the occasional emergency repair – and customers rely on a quick, friendly response.</p>\n<h2>What the job involves</h2>\n<ul><li>Planned maintenance visits on straight and curved stairlifts</li><li>Fault-finding on electrical, battery, drive and safety circuits</li><li>Breakdown call-outs, sometimes on a rota with an allowance</li><li>Completing digital service records and advising customers</li></ul>\n<h2>Who employers look for</h2>\n<p>Most employers recruit from electrical, electromechanical, lift, appliance repair or automotive backgrounds and provide manufacturer training. A full UK driving licence is almost always essential, and a calm, respectful manner in customers' homes matters as much as technical skill.</p>\n<h2>Live stairlift service vacancies</h2>\n[lmc_job_list sector=\"stairlifts\" role=\"service-engineer,maintenance-engineer,breakdown-engineer\" limit=\"20\"]",
		),
		'lp_stairlift_install' => array(
			'title'     => 'Stairlift Installation Jobs',
			'slug'      => 'stairlift-installation-jobs',
			'seo_title' => 'Stairlift Installation Engineer Jobs UK',
			'seo_desc'  => 'Stairlift installation jobs: fit straight and curved stairlifts in customers\' homes across the UK. Salaries shown on every vacancy.',
			'content'   => "<p>Stairlift installers fit new straight and curved stairlifts, working to tight tolerances in occupied homes. It's practical, satisfying work where you see the difference straight away.</p>\n<h2>Typical duties</h2>\n<ul><li>Fitting rails, carriages and charging points</li><li>Setting up, testing and commissioning lifts</li><li>Demonstrating safe use to customers and family members</li><li>Leaving the home clean and tidy</li></ul>\n<h2>Skills that help</h2>\n<p>Mechanical aptitude, accurate measuring, basic electrical knowledge and good customer care. Experience on curved rail systems is particularly valued.</p>\n<h2>Live stairlift installation vacancies</h2>\n[lmc_job_list sector=\"stairlifts\" role=\"installation-engineer,commissioning-engineer\" limit=\"20\"]",
		),
		'lp_kent'              => array(
			'title'     => 'Lift Engineer Jobs in Kent',
			'slug'      => 'lift-engineer-jobs-kent',
			'seo_title' => 'Lift Engineer Jobs in Kent – Stairlift, Home & Platform Lifts',
			'seo_desc'  => 'Lift engineer jobs in Kent: stairlift, home lift, platform lift and hoist engineer vacancies in Maidstone, Canterbury, Ashford, Medway and across the county.',
			'content'   => "<p>Kent has a large and growing population of older homeowners, plenty of public buildings with access lifts, and good transport links into London and the South East – which makes it a busy patch for stairlift, home lift and platform lift engineers.</p>\n<p>Field-based roles here often cover Kent alongside East Sussex, Surrey or south-east London, with engineers based around Maidstone, Medway, Ashford, Canterbury, Tunbridge Wells and Thanet.</p>\n<h2>Live vacancies in Kent and nearby</h2>\n[lmc_job_list location=\"kent\" limit=\"20\"]",
		),
	);
}
