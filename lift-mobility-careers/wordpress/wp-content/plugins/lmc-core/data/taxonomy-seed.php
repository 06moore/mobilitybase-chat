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
	return array(
		'installation-engineer' => array( 'name' => 'Installation engineer', 'seo_title' => 'Stairlift & Home Lift Installation Engineer Jobs UK', 'seo_desc' => 'Installation engineer jobs for stairlifts, home lifts, platform lifts and hoists across the UK.', 'intro' => 'Installation engineers survey, fit and commission new equipment – from curved stairlift rails to through-floor lifts and ceiling track hoists.' ),
		'service-engineer'      => array( 'name' => 'Service engineer', 'seo_title' => 'Lift & Stairlift Service Engineer Jobs UK', 'seo_desc' => 'Service engineer jobs in the stairlift, home lift and access equipment sector. Field-based roles with salaries shown.', 'intro' => 'Service engineers carry out planned maintenance, repairs and safety checks, usually covering a regional patch with a company vehicle.' ),
		'maintenance-engineer'  => array( 'name' => 'Maintenance engineer', 'intro' => 'Maintenance engineers keep installed equipment safe and reliable through scheduled preventative maintenance.' ),
		'breakdown-engineer'    => array( 'name' => 'Repair & breakdown engineer', 'intro' => 'Repair and breakdown engineers respond to faults, often on a rota, getting users moving again quickly.' ),
		'loler-inspector'       => array( 'name' => 'LOLER inspector', 'seo_title' => 'LOLER Inspector Jobs – Lifts, Stairlifts & Hoists UK', 'seo_desc' => 'LOLER inspector and thorough examination jobs for stairlifts, home lifts, platform lifts and patient hoists across the UK.', 'intro' => 'LOLER inspectors carry out thorough examinations of lifting equipment such as stairlifts, platform lifts and patient hoists under the Lifting Operations and Lifting Equipment Regulations 1998, reporting defects and keeping accurate records.' ),
		'commissioning-engineer'=> array( 'name' => 'Commissioning engineer', 'intro' => 'Commissioning engineers test, set up and hand over newly installed lifts and systems.' ),
		'technical-support'     => array( 'name' => 'Technical support engineer', 'intro' => 'Technical support engineers help field teams and customers diagnose faults and resolve technical issues.' ),
		'trainee-engineer'      => array( 'name' => 'Trainee engineer', 'intro' => 'Trainee roles offer a structured route into the industry, with product training and mentoring.' ),
		'apprentice'            => array( 'name' => 'Apprentice', 'intro' => 'Apprenticeships combine paid work with formal qualifications such as lift and escalator or engineering technician standards.' ),
		'supervisor'            => array( 'name' => 'Supervisor', 'intro' => 'Supervisors lead teams of engineers, plan workloads and maintain quality and safety standards.' ),
		'service-manager'       => array( 'name' => 'Service manager', 'intro' => 'Service managers run service operations, contracts and engineer teams.' ),
		'technical-manager'     => array( 'name' => 'Technical manager', 'intro' => 'Technical managers own product knowledge, standards, training and technical compliance.' ),
		'operations-manager'    => array( 'name' => 'Operations manager', 'intro' => 'Operations managers oversee installation and service delivery across regions.' ),
	);
}
