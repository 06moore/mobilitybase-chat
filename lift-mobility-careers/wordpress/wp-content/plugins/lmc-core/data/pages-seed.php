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
		$t = preg_replace( '/\[([^\[\]]+)\]\(([^()\s]+)\)/', '<a href="$2">$1</a>', $t );
		return $t;
	};
	$table = array();
	$flush_table = function () use ( &$table, &$html, $inline ) {
		if ( ! $table ) {
			return;
		}
		$rows  = array_values( array_filter( $table, function ( $r ) {
			return ! preg_match( '/^\|?\s*:?-{3,}/', $r );
		} ) );
		$cells = function ( $r ) {
			return array_map( 'trim', explode( '|', trim( trim( $r ), '|' ) ) );
		};
		$html .= '<div class="lmc-table-wrap"><table class="lmc-table"><thead><tr>';
		foreach ( $cells( array_shift( $rows ) ) as $c ) {
			$html .= '<th>' . $inline( $c ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$html .= '<tr>';
			foreach ( $cells( $r ) as $c ) {
				$html .= '<td>' . $inline( $c ) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= "</tbody></table></div>\n";
		$table = array();
	};
	foreach ( $lines as $line ) {
		if ( preg_match( '/^\s*\|/', $line ) ) {
			$flush();
			$close();
			$table[] = $line;
			continue;
		}
		$flush_table();
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
	$flush_table();
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

		// Growth: job alerts and career guides (original, evergreen content that earns search traffic and links).
		'alerts'   => array(
			'title'     => 'Job Alerts',
			'slug'      => 'job-alerts',
			'seo_title' => 'Free Job Alerts – Stairlift, Lift & Hoist Engineer Jobs by Email',
			'seo_desc'  => 'Get new stairlift, home lift, platform lift and hoist engineer jobs by email. Choose your sector, job type and location. Free, no account needed.',
			'content'   => "<p>Be first to hear about new vacancies. Choose the sectors, job types and area you're interested in and we'll email you when matching jobs are published – daily or weekly. It's free, and we only ask for your email address.</p>\n[lmc_job_alerts]",
		),
		'guides'   => array(
			'title'     => 'Career Guides',
			'slug'      => 'career-guides',
			'seo_title' => 'Lift & Mobility Engineering Career Guides',
			'seo_desc'  => 'Practical guides to careers in stairlift, home lift, platform lift and hoist engineering: how to get in, qualifications, LOLER inspection and salaries.',
			'content'   => "<p>Practical, independent guides for engineers already in the industry and people thinking of joining it.</p>\n[lmc_guide_list]",
		),
		'g_stairlift' => array(
			'title'     => 'How to Become a Stairlift Engineer',
			'slug'      => 'how-to-become-a-stairlift-engineer',
			'parent'    => 'guides',
			'seo_title' => 'How to Become a Stairlift Engineer (UK Guide)',
			'seo_desc'  => 'What stairlift engineers do, the skills and backgrounds employers look for, training routes and how to find your first stairlift engineer job in the UK.',
			'content'   => "<p>Stairlift engineers install, service and repair the lifts that let people stay safely in their own homes. It's skilled, practical work with a clear purpose – and it's one of the more accessible routes into lift engineering.</p>\n<h2>What the job involves</h2>\n<ul><li><strong>Installation:</strong> fitting straight and curved rails, carriages and charging points, then testing and handing over.</li><li><strong>Service and maintenance:</strong> planned visits to check safety devices, batteries, drives and controls.</li><li><strong>Repairs and breakdowns:</strong> diagnosing faults and getting the customer moving again, sometimes on an on-call rota.</li></ul>\n<p>Most roles are field-based: you'll cover a regional patch from home with a company van, tools and a tablet for job sheets.</p>\n<h2>Skills employers look for</h2>\n<ul><li>Electrical or electromechanical fault-finding</li><li>Mechanical aptitude and accurate measuring</li><li>A full UK driving licence</li><li>Patience and good manners in customers' homes – many customers are elderly or disabled</li><li>Organisation: working alone, planning your day and keeping accurate records</li></ul>\n<h2>Routes in</h2>\n<p><strong>From a related trade.</strong> Electricians, appliance and vending engineers, automotive technicians, building maintenance engineers and armed forces technicians often move across. Employers typically provide full product training.</p>\n<p><strong>Trainee roles.</strong> Some manufacturers and larger installers recruit trainees with little direct experience and train them in-house. Look for <a href=\"/jobs/role/trainee-engineer/\">trainee engineer jobs</a>.</p>\n<p><strong>Apprenticeships.</strong> Lift and engineering apprenticeships combine paid work with a recognised qualification. See our <a href=\"/career-guides/lift-engineering-apprenticeships/\">apprenticeships guide</a>.</p>\n<h2>Qualifications</h2>\n<p>There isn't a single mandatory licence for stairlift work, but employers value electrical qualifications, lift-specific NVQs or diplomas, and manufacturer training certificates. Experienced engineers often add LOLER inspection competence later – see our <a href=\"/career-guides/loler-inspector-career-guide/\">LOLER inspector guide</a>.</p>\n<h2>Find stairlift engineer jobs</h2>\n<p>Browse <a href=\"/jobs/sector/stairlifts/\">live stairlift jobs</a> – every advert shows the salary – or <a href=\"/job-alerts/\">set up a free job alert</a> for your area.</p>",
		),
		'g_loler' => array(
			'title'     => 'LOLER Inspector Career Guide',
			'slug'      => 'loler-inspector-career-guide',
			'parent'    => 'guides',
			'seo_title' => 'How to Become a LOLER Inspector – Lifts, Stairlifts & Hoists',
			'seo_desc'  => 'A guide to LOLER inspection careers in the stairlift, platform lift and patient hoist sector: what inspectors do, the regulations and how engineers progress into the role.',
			'content'   => "<p>LOLER inspectors carry out the thorough examinations that keep lifting equipment legal and safe. In the mobility sector that means stairlifts, through-floor and platform lifts, ceiling track and mobile hoists, and the slings used with them.</p>\n<h2>The regulations in brief</h2>\n<p>The Lifting Operations and Lifting Equipment Regulations 1998 (LOLER) require lifting equipment to be thoroughly examined by a competent person. Equipment used for lifting people, and lifting accessories such as slings, generally needs a thorough examination at least every six months, unless a written examination scheme drawn up by a competent person sets different intervals. Examinations must be recorded, and defects reported to the duty holder.</p>\n<p>For the official position, see the Health and Safety Executive's guidance on LOLER.</p>\n<h2>What the job involves</h2>\n<ul><li>Examining equipment against its examination scheme and the manufacturer's information</li><li>Identifying defects and deciding whether equipment is safe to continue in use</li><li>Writing clear, accurate reports and notifying serious defects promptly</li><li>Visiting homes, care settings, hospitals and public buildings</li></ul>\n<h2>Who becomes a LOLER inspector?</h2>\n<p>Most inspectors are experienced service or maintenance engineers who have developed the technical knowledge, independence and judgement to act as a competent person. Employers include manufacturers, maintenance contractors and independent inspection bodies, and most provide structured training and assessment for the role.</p>\n<h2>Skills that matter</h2>\n<ul><li>Deep knowledge of the equipment types you examine</li><li>Confidence to make – and stand by – safety decisions</li><li>Excellent written reporting</li><li>Understanding of relevant standards and the employer's examination procedures</li></ul>\n<h2>Find LOLER inspector jobs</h2>\n<p>See <a href=\"/jobs/role/loler-inspector/\">live LOLER inspector vacancies</a> or <a href=\"/job-alerts/\">get them by email</a>.</p>",
		),
		'g_apprentice' => array(
			'title'     => 'Lift Engineering Apprenticeships and Trainee Routes',
			'slug'      => 'lift-engineering-apprenticeships',
			'parent'    => 'guides',
			'seo_title' => 'Lift Engineering Apprenticeships & Trainee Jobs – UK Guide',
			'seo_desc'  => 'How to start a career in lift, stairlift and hoist engineering through an apprenticeship or paid trainee role. What to expect and how to apply.',
			'content'   => "<p>You don't need years of lift experience to start a career in this industry. Apprenticeships and trainee roles let you earn while you learn, with a clear path to skilled engineering work.</p>\n<h2>Apprenticeships</h2>\n<p>Apprenticeships combine employment with off-the-job training towards a recognised qualification. In England, relevant standards include the Level 3 lift and escalator electromechanic apprenticeship, alongside broader engineering and electrical apprenticeships offered by individual employers. Scotland, Wales and Northern Ireland run their own apprenticeship frameworks, so check with the employer which applies.</p>\n<p>You can search apprenticeship vacancies on the official government service for your nation as well as here.</p>\n<h2>Trainee engineer roles</h2>\n<p>Many stairlift and home lift companies hire trainee engineers – usually adults with a practical background – and train them on their own products. These roles are often field-based from the start, shadowing an experienced engineer before working independently.</p>\n<h2>What employers look for in new starters</h2>\n<ul><li>Practical, hands-on ability and curiosity about how things work</li><li>Reliability and a good attitude with customers</li><li>A full driving licence (for most field roles)</li><li>Basic maths and the ability to follow technical instructions</li></ul>\n<h2>Where it can lead</h2>\n<p>From apprentice or trainee, engineers typically progress to installation or service engineer, then specialise – commissioning, LOLER inspection, technical support or supervision.</p>\n<p>Browse <a href=\"/jobs/role/apprentice/\">apprenticeships</a> and <a href=\"/jobs/role/trainee-engineer/\">trainee roles</a>, or <a href=\"/job-alerts/\">set up an alert</a> for entry-level jobs near you.</p>",
		),
		'g_change' => array(
			'title'     => 'Changing Career into Lift and Mobility Engineering',
			'slug'      => 'career-change-into-lift-engineering',
			'parent'    => 'guides',
			'seo_title' => 'Career Change into Lift Engineering – Electricians, Mechanics & Ex-Forces',
			'seo_desc'  => 'Thinking of moving into stairlift, home lift or hoist engineering? How your skills transfer from electrical, appliance, automotive or forces backgrounds.',
			'content'   => "<p>Lift and mobility engineering employers regularly recruit people from other trades. If you're good with electrics and mechanics and enjoy working independently, your skills may transfer more than you think.</p>\n<h2>Backgrounds that transfer well</h2>\n<ul><li><strong>Electricians:</strong> fault-finding, safety testing and reading circuit diagrams carry straight across.</li><li><strong>Appliance, vending and white-goods engineers:</strong> already used to field work, diagnostics and customers' homes.</li><li><strong>Automotive technicians:</strong> mechanical and electrical diagnostic skills and familiarity with batteries and drives.</li><li><strong>Armed forces technicians:</strong> structured maintenance, documentation and working to procedures.</li><li><strong>Building and facilities maintenance:</strong> multi-skilled, practical and used to working in occupied buildings.</li></ul>\n<h2>Why people make the move</h2>\n<ul><li>Steady demand – installed equipment needs regular servicing and inspection</li><li>Field-based roles, often with a company van</li><li>Clear progression into commissioning, inspection and management</li><li>Work that makes an obvious difference to people's independence</li></ul>\n<h2>Making your application stand out</h2>\n<ul><li>Highlight fault-finding examples and any electrical qualifications</li><li>Mention customer-facing experience, especially in people's homes</li><li>Show you're happy to learn new products – most employers train on their own equipment</li></ul>\n<p>Start with <a href=\"/jobs/role/trainee-engineer/\">trainee engineer jobs</a> or browse <a href=\"/jobs/\">all vacancies</a>.</p>",
		),
		'g_salary' => array(
			'title'     => 'Lift & Stairlift Engineer Salary Guide',
			'slug'      => 'lift-engineer-salary-guide',
			'parent'    => 'guides',
			'seo_title' => 'Stairlift & Lift Engineer Salary Guide UK – From Real Adverts',
			'seo_desc'  => 'Salary ranges for stairlift, home lift, platform lift and hoist engineer jobs in the UK, calculated from real vacancies advertised on Lift and Mobility Careers.',
			'content'   => "<p>Every vacancy on Lift and Mobility Careers must show a salary, so we can publish salary information based on real adverts rather than estimates. The figures below update automatically as new vacancies are published.</p>\n[lmc_salary_insights]\n<h2>What affects pay</h2>\n<ul><li><strong>Role:</strong> commissioning, LOLER inspection, breakdown (with on-call) and management roles tend to pay more than entry-level installation work.</li><li><strong>Location:</strong> London and the South East typically pay more, reflecting living costs and demand.</li><li><strong>Package extras:</strong> company vans, overtime, on-call allowances and bonuses can add significantly – check the \"package extras\" line on each advert.</li><li><strong>Experience and qualifications:</strong> multi-skilled engineers and those with inspection competence are in demand.</li></ul>\n<p>See <a href=\"/jobs/?sort=salary\">live vacancies sorted by salary</a>.</p>",
		),

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
