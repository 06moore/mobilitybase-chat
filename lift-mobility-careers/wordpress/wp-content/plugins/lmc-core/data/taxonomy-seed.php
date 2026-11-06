<?php
/**
 * Sector and job-category seed content. Each sector page carries genuinely useful,
 * original copy so it is not a thin page – this is what the SEO landing pages are built on.
 */
defined( 'ABSPATH' ) || exit;

function lmc_seed_sectors() {
	return array(
		'stairlifts'             => array(
			'name'      => 'Stairlifts',
			'order'     => 1,
			'icon'      => 'stairs',
			'seo_title' => 'Stairlift Engineer Jobs in the UK – Service & Installation',
			'seo_desc'  => 'Stairlift engineer jobs across the UK: installation, service, repair and breakdown roles with salaries shown on every advert. Apply direct to the employer.',
			'intro'     => "Stairlift engineers keep people living safely and independently in their own homes. The work is varied: surveying staircases, installing straight and curved rail systems, carrying out planned maintenance and responding to breakdowns – often as a field-based engineer with a company van covering a regional patch.\n\nEmployers range from national manufacturers with large service networks to independent family firms supplying new and reconditioned lifts. Most roles suit engineers from an electrical, electromechanical, lift or white-goods background, and many companies offer full product training for the right candidate.\n\nTypical requirements include a full UK driving licence, good fault-finding ability and a customer-friendly manner – you will be working in people's homes. Experienced stairlift engineers who can handle curved rail installations and LOLER-related servicing are consistently in demand.",
		),
		'through-floor-lifts'    => array(
			'name'      => 'Through-floor lifts',
			'order'     => 2,
			'icon'      => 'throughfloor',
			'seo_title' => 'Through-Floor Lift Engineer Jobs – UK Vacancies',
			'seo_desc'  => 'Through-floor and vertical home lift engineer vacancies across the UK. Installation, commissioning and service roles with direct employers.',
			'intro'     => "Through-floor lifts move a user – seated, standing or in a wheelchair – between two floors through an aperture cut in the ceiling. Engineering roles in this sector combine building work awareness, mechanical installation, electrical commissioning and safety testing.\n\nInstallation engineers often work in pairs alongside builders and occupational therapists' recommendations, while service engineers carry out scheduled maintenance, LOLER-related examinations support and breakdown visits. Many installers in this sector are also trained on domestic home lifts and platform lifts.\n\nEmployers look for strong mechanical and electrical skills, an understanding of safety circuits and an eye for a tidy finish in a customer's home.",
		),
		'home-lifts'             => array(
			'name'      => 'Home lifts',
			'order'     => 3,
			'icon'      => 'house',
			'seo_title' => 'Home Lift Engineer Jobs – Domestic & Residential Lifts UK',
			'seo_desc'  => 'Home lift engineer jobs in the UK: domestic and residential lift installation, service and commissioning roles. Salaries listed, apply direct.',
			'intro'     => "Domestic and residential home lifts are one of the fastest-developing parts of the accessibility market, from compact two-stop lifts to multi-floor platform lifts in private homes and apartment buildings.\n\nEngineers in this sector install, commission and maintain lifts that combine traditional lift engineering principles with modern drive systems, controllers and safety features. Service engineers handle planned preventative maintenance, fault-finding and customer handovers.\n\nBackgrounds from passenger lifts, platform lifts, electrical installation or electromechanical maintenance transfer well. NVQ qualifications in lift installation or maintenance are valued but not always essential.",
		),
		'platform-lifts'         => array(
			'name'      => 'Platform lifts',
			'order'     => 4,
			'icon'      => 'platform',
			'seo_title' => 'Platform Lift Engineer Jobs – UK Service & Install',
			'seo_desc'  => 'Platform lift engineer jobs across the UK including enclosed and open platform lifts in public and commercial buildings. Apply direct to employers.',
			'intro'     => "Platform lifts provide step-free access in schools, offices, shops, healthcare buildings and homes. Roles cover enclosed vertical platform lifts, open platform lifts and inclined platforms, so engineers need to be comfortable with a range of drive systems and building types.\n\nService engineers typically manage a portfolio of maintenance contracts, carrying out routine servicing and breakdown response, while installation teams handle new builds and refurbishments. Commissioning and LOLER-related inspection work is common at senior levels.\n\nEmployers value lift or electromechanical experience, CSCS/site safety cards for commercial work and confident customer liaison with facilities managers.",
		),
		'wheelchair-lifts'       => array(
			'name'      => 'Wheelchair lifts',
			'order'     => 5,
			'icon'      => 'step',
			'seo_title' => 'Wheelchair Lift Engineer Jobs – UK Vacancies',
			'seo_desc'  => 'Engineer jobs servicing and installing wheelchair lifts, step lifts and short-rise lifts across the UK. Salary shown on every vacancy.',
			'intro'     => "Wheelchair lifts, step lifts and short-rise lifts overcome changes in level where a ramp is not practical – at building entrances, stages, mezzanines and domestic thresholds. Engineers in this area are often multi-skilled across several types of access equipment.\n\nThe work includes installation, scheduled maintenance, repairs and safety checks, frequently for public-facing buildings where reliability matters and downtime affects access for disabled visitors.\n\nGood candidates bring electromechanical fault-finding skills, a methodical approach to safety checks and the confidence to work independently across a region.",
		),
		'access-lifts'           => array(
			'name'      => 'Access lifts',
			'order'     => 6,
			'icon'      => 'door',
			'seo_title' => 'Access Lift Engineer Jobs – Commercial & Public Buildings',
			'seo_desc'  => 'Access lift engineer jobs in the UK for service, maintenance and installation of access lifts in public and commercial buildings.',
			'intro'     => "Access lift specialists maintain the lifts that make public and commercial buildings usable for everyone. Contract maintenance companies in this sector look after mixed portfolios of platform lifts, low-speed lifts and small passenger lifts across schools, councils, healthcare estates and retail sites.\n\nRoles include service and breakdown engineers, installation and modernisation engineers, and supervisors managing teams and contracts. Some positions involve on-call rotas with additional allowances.\n\nEmployers look for lift or electromechanical experience, a good understanding of safety regulations and documentation, and reliable, well-organised field engineers.",
		),
		'ceiling-track-hoists'   => array(
			'name'      => 'Ceiling track hoists',
			'order'     => 7,
			'icon'      => 'track',
			'seo_title' => 'Hoist Engineer Jobs – Ceiling Track Hoists UK',
			'seo_desc'  => 'Hoist engineer jobs across the UK: ceiling track hoist installation, servicing and LOLER thorough examination roles. Apply direct.',
			'intro'     => "Ceiling track hoist engineers install and maintain overhead hoist systems in homes, care settings and hospitals. Installation work involves surveying structures, fitting straight and H-frame or room-covering track systems and commissioning motors and charging units.\n\nService engineers carry out planned maintenance and repairs, and many roles include LOLER thorough examinations of hoists and slings – so experience with lifting equipment regulations is a real advantage.\n\nBackgrounds in electromechanical maintenance, medical equipment servicing or construction with fixings experience all transfer well.",
		),
		'patient-handling'       => array(
			'name'      => 'Patient handling equipment',
			'order'     => 8,
			'icon'      => 'hook',
			'seo_title' => 'Patient Handling Equipment Engineer Jobs – UK',
			'seo_desc'  => 'Field service engineer jobs for patient handling equipment: mobile hoists, standing aids, profiling beds and bathing equipment across the UK.',
			'intro'     => "Patient handling equipment includes mobile hoists, standing and transfer aids, profiling beds, bathing systems and associated slings. Field service engineers in this sector support hospitals, care homes, community equipment services and private clients.\n\nThe work centres on planned preventative maintenance, repairs, safety testing and LOLER examinations of lifting equipment, with accurate service records being as important as technical skill.\n\nEmployers recruit from medical equipment, electromechanical and mobility backgrounds, and many provide product training and a structured route into inspection work.",
		),
		'mobility-equipment'     => array(
			'name'      => 'Mobility & accessibility equipment',
			'order'     => 9,
			'icon'      => 'gear',
			'seo_title' => 'Mobility Equipment Engineer Jobs – UK Vacancies',
			'seo_desc'  => 'Mobility and accessibility equipment engineer jobs across the UK. Service, repair and technical roles with direct employers – salaries shown.',
			'intro'     => "Beyond lifts and hoists, the wider accessibility equipment sector covers specialist products that help people live and work independently – from powered chairs and riser recliners to door automation, bathing adaptations and assistive technology.\n\nField and workshop engineers repair, service and install equipment, while technical support roles help colleagues and customers diagnose faults remotely. Many engineers move between product types as they gain experience.\n\nThis category also includes multi-discipline roles where the employer supplies a mixed range of mobility products.",
		),
	);
}

function lmc_seed_roles() {
	$r = function ( $name, $title, $desc, $intro ) {
		return array( 'name' => $name, 'seo_title' => $title, 'seo_desc' => $desc, 'intro' => $intro );
	};
	return array(
		'installation-engineer'  => $r( 'Installation engineer', 'Stairlift & Lift Installation Engineer Jobs UK', 'Installation engineer jobs for stairlifts, home lifts, through-floor lifts, platform lifts and ceiling hoists across the UK. Salaries on every advert.', "Installation engineers turn a survey into a working lift. Depending on the employer that might mean fitting straight and curved stairlift rails, building in a through-floor lift, installing an enclosed platform lift or mounting a ceiling track hoist system.\n\nThe work is hands-on and varied, usually in occupied homes or working buildings, so accuracy, tidiness and good communication matter as much as mechanical skill. Many installers work in pairs and progress into commissioning or supervisory roles. Electrical, mechanical, joinery or general building backgrounds all transfer well, and most employers give full product training." ),
		'service-engineer'       => $r( 'Service engineer', 'Stairlift & Lift Service Engineer Jobs UK', 'Service engineer jobs in the stairlift, home lift, platform lift and hoist sector. Field-based roles with company vans – salaries shown, apply direct.', "Service engineers keep installed equipment safe and reliable. A typical week mixes planned maintenance visits, safety checks and repairs across a regional patch, usually with a company van and digital job sheets.\n\nIt suits independent, methodical engineers who enjoy fault-finding and dealing with customers face to face. Electrical and electromechanical experience is the usual entry route, and many service engineers go on to specialise in LOLER inspection, technical support or service management.\n\nGood service engineers are trusted by customers, many of whom rely on their lift every day. Expect to explain what you've done in plain language, leave clear service records and spot problems before they cause a breakdown. Employers often look for a full driving licence, a tidy and methodical approach, and experience with batteries, drives, safety circuits and controllers." ),
		'maintenance-engineer'   => $r( 'Maintenance engineer', 'Lift Maintenance Engineer Jobs – Stairlifts, Platform & Home Lifts', 'Lift maintenance engineer vacancies across the UK for stairlifts, platform lifts, access lifts and home lifts. Apply direct to employers.', "Maintenance engineers carry out scheduled preventative maintenance on lifts and access equipment under service contracts – checking safety devices, drives, batteries, controls and wear parts, and recording everything accurately.\n\nContract maintenance companies look after mixed portfolios in schools, offices, healthcare and housing, so engineers often become multi-skilled across several makes and types of equipment." ),
		'breakdown-engineer'     => $r( 'Repair & breakdown engineer', 'Lift & Stairlift Breakdown Engineer Jobs UK', 'Repair and breakdown engineer jobs for stairlifts, platform lifts and home lifts. On-call allowances and overtime – salaries shown on every vacancy.', "Breakdown engineers respond when equipment stops – often for a user who can't get upstairs or into a building until it's fixed. Strong diagnostic skills and a calm, reassuring manner are essential.\n\nMany roles include an on-call rota with allowances and overtime, which can make breakdown work among the better-paid field roles in the sector." ),
		'loler-inspector'        => $r( 'LOLER inspector', 'LOLER Inspector Jobs – Lifts, Stairlifts & Hoists UK', 'LOLER inspector and thorough examination jobs for stairlifts, home lifts, platform lifts and patient hoists across the UK.', "LOLER inspectors carry out thorough examinations of lifting equipment under the Lifting Operations and Lifting Equipment Regulations 1998 (LOLER). Equipment used to lift people – such as stairlifts, platform lifts and patient hoists and their slings – generally needs examining at least every six months, or in line with a written examination scheme.\n\nInspectors work for manufacturers, maintenance companies and independent inspection bodies. Most come from a service or maintenance background and build up formal inspection competence, so it's a natural next step for experienced engineers." ),
		'commissioning-engineer' => $r( 'Commissioning engineer', 'Lift Commissioning Engineer Jobs UK', 'Commissioning engineer jobs for home lifts, platform lifts and through-floor lifts across the UK. Apply direct with salaries shown.', "Commissioning engineers test, adjust and hand over newly installed lifts, making sure every safety circuit, control and drive works exactly as it should before the customer uses it.\n\nThe role needs detailed electrical and control-system knowledge and careful documentation. It's often a step up from installation or service work." ),
		'technical-support'      => $r( 'Technical support engineer', 'Technical Support Engineer Jobs – Lifts & Mobility Equipment', 'Technical support engineer vacancies with stairlift, home lift and patient handling equipment companies across the UK.', "Technical support engineers are the experts behind the field team – helping engineers and customers diagnose faults by phone or remotely, maintaining technical documentation and feeding problems back to product teams.\n\nIt suits experienced engineers who want to share their knowledge, and is often office- or hybrid-based." ),
		'trainee-engineer'       => $r( 'Trainee engineer', 'Trainee Stairlift & Lift Engineer Jobs – Full Training Given', 'Trainee engineer jobs in the stairlift, lift and hoist industry. Paid roles with full product training – a practical way into engineering.', "Trainee engineer roles are one of the most practical ways into the industry. Employers provide product training and mentoring alongside experienced engineers, and many are happy to recruit people from related trades.\n\nUseful backgrounds include electrical work, appliance or vending repair, automotive, the armed forces and building maintenance. A full driving licence and a good attitude with customers are usually more important than lift experience.\n\nMost trainee programmes start with product and safety training, followed by weeks shadowing an experienced engineer on installations, servicing and repairs before you take on your own jobs. Good trainee adverts explain how long training lasts, who mentors you, whether you'll work towards a qualification and how pay increases once you're signed off. It's a realistic way to move into skilled, field-based engineering without starting again from scratch." ),
		'apprentice'             => $r( 'Apprentice', 'Lift Engineering Apprenticeships UK', 'Apprenticeships in lift, stairlift and access equipment engineering across the UK. Earn while you learn with recognised qualifications.', "Apprenticeships combine paid work with structured training towards a recognised qualification – for example the Level 3 lift and escalator electromechanic standard in England, or engineering apprenticeships offered by individual employers.\n\nThey're open to school leavers and career changers alike, and lead into skilled installation, service and inspection roles.\n\nA typical apprenticeship lasts several years and mixes time on the tools with an experienced engineer, college or training-centre study and assessment against a recognised standard. Apprentices learn electrical and mechanical principles, safe working, fault-finding and customer care. Check each advert for the qualification offered, how off-the-job training is delivered and whether a driving licence is needed – many field roles require one by the end of the programme." ),
		'supervisor'             => $r( 'Supervisor', 'Lift Engineering Supervisor Jobs UK', 'Supervisor and team leader jobs in stairlift, home lift and platform lift installation and service teams across the UK.', "Supervisors lead teams of field engineers – planning workloads, carrying out quality and safety checks, coaching newer engineers and stepping in on complex jobs.\n\nMost supervisors are experienced engineers who enjoy organising people as well as solving technical problems." ),
		'service-manager'        => $r( 'Service manager', 'Lift Service Manager Jobs – Stairlifts, Home & Platform Lifts', 'Service manager jobs in the UK mobility lift industry: run engineer teams, maintenance contracts and customer service.', "Service managers run the service operation: engineer teams, maintenance contracts, response times, parts, budgets and customer satisfaction.\n\nEmployers typically look for a technical background combined with people management and a firm grasp of safety and compliance." ),
		'technical-manager'      => $r( 'Technical manager', 'Technical Manager Jobs – Lift & Accessibility Equipment', 'Technical manager vacancies with lift, stairlift and patient handling equipment companies across the UK.', "Technical managers own product knowledge, engineering standards, training and compliance – from new product introductions to investigating complex faults and keeping procedures current with regulations and standards." ),
		'operations-manager'     => $r( 'Operations manager', 'Operations Manager Jobs – UK Mobility Lift Industry', 'Operations manager jobs overseeing installation and service delivery in the stairlift, home lift and access lift sector.', "Operations managers oversee installation and service delivery across regions or the whole business – planning capacity, managing managers, improving processes and making sure every job is delivered safely, on time and profitably." ),
	);
}
