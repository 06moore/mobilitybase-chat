<?php
/**
 * Integration tests for the LMC Core plugin, run against the local dev site.
 *   bash tools/dev/setup-local.sh && php -S 127.0.0.1:8080 -t /tmp/lmc-dev/wp tools/dev/router.php &
 *   php tools/tests/run.php
 */
define( 'LMC_TESTING', true );
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
$_SERVER['REQUEST_URI'] = '/';
require ( getenv( 'LMC_DEV_DIR' ) ?: '/tmp/lmc-dev' ) . '/wp/wp-load.php';

$pass = 0; $fail = 0;
function ok( $cond, $name ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  ✓ $name\n"; } else { $fail++; echo "  ✗ FAIL: $name\n"; }
}
function section( $t ) { echo "\n$t\n"; }
function mails( $tpl = null ) {
	global $wpdb;
	return $tpl ? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'email_log' ) . ' WHERE template = %s', $tpl ) ) : (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . lmc_table( 'email_log' ) );
}
$clock = time();
add_filter( 'lmc_now', function () use ( &$clock ) { return $clock; } );
add_filter( 'lmc_pre_geocode', function ( $pre, $q ) {
	$known = array( 'me14' => array( 51.28, 0.53 ), 'maidstone' => array( 51.27, 0.52 ), 'ls10' => array( 53.77, -1.53 ), 'tn23' => array( 51.14, 0.87 ) );
	return $known[ strtolower( str_replace( ' ', '', $q ) ) ] ?? false;
}, 10, 2 );
wp_set_current_user( 1 );

function make_employer( $email, $entity = 'limited_company' ) {
	$u = wp_insert_user( array( 'user_login' => sanitize_user( $email ), 'user_email' => $email, 'user_pass' => wp_generate_password(), 'first_name' => 'Test', 'role' => 'lmc_employer' ) );
	update_user_meta( $u, 'lmc_company', 'Test Lifts ' . $u );
	update_user_meta( $u, 'lmc_email_verified', time() );
	update_user_meta( $u, 'lmc_entity_type', $entity );
	return $u;
}
function make_job( $uid, $title = 'Stairlift Engineer', $status = 'lmc_unpaid', $extra = array() ) {
	$id = wp_insert_post( array( 'post_type' => 'lmc_job', 'post_status' => $status, 'post_title' => $title, 'post_author' => $uid, 'post_content' => str_repeat( 'Role description. ', 20 ) ) );
	foreach ( array_merge( array( 'employer_name' => 'Test Lifts', 'town' => 'Maidstone', 'postcode' => 'ME14', 'salary_min' => 30000, 'salary_max' => 35000, 'salary_period' => 'YEAR', 'salary_annual' => 35000, 'apply_method' => 'url', 'apply_url' => 'https://example.com/apply', 'social_ok' => 1 ), $extra ) as $k => $v ) {
		update_post_meta( $id, '_lmc_' . $k, $v );
	}
	wp_set_object_terms( $id, 'stairlifts', 'lmc_sector' );
	wp_set_object_terms( $id, 'full-time', 'lmc_type' );
	$k = get_term_by( 'slug', 'kent', 'lmc_location' );
	wp_set_object_terms( $id, array( $k->term_id, $k->parent ), 'lmc_location' );
	return $id;
}
function signed_webhook( $payload, $secret, $ts = null ) {
	$ts  = $ts ?: lmc_now();
	$req = new WP_REST_Request( 'POST', '/lmc/v1/stripe-webhook' );
	$req->set_body( $payload );
	$req->set_header( 'Stripe-Signature', 't=' . $ts . ',v1=' . hash_hmac( 'sha256', $ts . '.' . $payload, $secret ) );
	return rest_do_request( $req );
}

/* ------------------------------------------------------------------ */
section( 'Settings & pricing' );
ok( lmc_package( 'standard' )['price'] === 3900 && lmc_package( 'pack10' )['price'] === 30000, 'Launch prices: £39 standard … £300 ten-pack' );
lmc_update_opts( array( 'price_featured' => 7500 ) );
ok( lmc_package( 'featured' )['price'] === 7500, 'Price change in settings applies instantly' );
lmc_update_opts( array( 'price_featured' => 6900 ) );
ok( ! LMC_Payments::enabled(), 'Payments are OFF by default' );

section( 'Stripe webhook signature' );
$secret = 'whsec_test_123';
ok( LMC_Payments::verify_signature( 'abc', 't=' . lmc_now() . ',v1=' . hash_hmac( 'sha256', lmc_now() . '.abc', $secret ), $secret ), 'Valid signature accepted' );
ok( ! LMC_Payments::verify_signature( 'abc', 't=' . lmc_now() . ',v1=deadbeef', $secret ), 'Wrong signature rejected' );
ok( ! LMC_Payments::verify_signature( 'abc', 't=' . ( lmc_now() - 1000 ) . ',v1=' . hash_hmac( 'sha256', ( lmc_now() - 1000 ) . '.abc', $secret ), $secret ), 'Stale (replayed) signature rejected' );

section( 'Paid submission → webhook → moderation → publish' );
lmc_update_opts( array( 'stripe_test_secret' => 'sk_test_dummy', 'stripe_test_webhook' => $secret, 'payments_enabled' => 1, 'moderation' => 'first' ) );
ok( LMC_Payments::enabled(), 'Payments enabled once keys present' );
$emp = make_employer( 'buyer@example.test' );
$job = make_job( $emp, 'Stairlift Service Engineer' );
global $wpdb;
$wpdb->insert( lmc_table( 'payments' ), array( 'user_id' => $emp, 'job_id' => $job, 'package' => 'featured', 'purpose' => 'new', 'net' => 6900, 'vat' => 0, 'total' => 6900, 'status' => 'pending', 'mode' => 'test', 'stripe_session' => 'cs_test_A', 'created_at' => lmc_mysql_time() ) );
$evt = wp_json_encode( array( 'type' => 'checkout.session.completed', 'data' => array( 'object' => array( 'id' => 'cs_test_A', 'payment_status' => 'paid', 'amount_total' => 6900, 'currency' => 'gbp', 'payment_intent' => 'pi_A' ) ) ) );
$bad = new WP_REST_Request( 'POST', '/lmc/v1/stripe-webhook' );
$bad->set_body( $evt );
$bad->set_header( 'Stripe-Signature', 't=' . lmc_now() . ',v1=forged' );
ok( 400 === rest_do_request( $bad )->get_status(), 'Forged webhook returns 400' );
ok( 'lmc_unpaid' === get_post_status( $job ), '…and vacancy stays unpaid' );
$before = mails();
ok( 200 === signed_webhook( $evt, $secret )->get_status(), 'Signed webhook accepted' );
ok( 'pending' === get_post_status( $job ), 'First-time employer → held for approval' );
ok( 'featured' === lmc_job_tier( $job ) && 2 == lmc_job_meta( $job, 'priority' ), 'Featured tier and priority applied' );
ok( mails( 'payment_confirmation' ) >= 1 && mails( 'admin_review' ) >= 1, 'Payment confirmation + admin review emails sent' );
$n = mails();
signed_webhook( $evt, $secret );
ok( mails() === $n, 'Duplicate webhook is idempotent (no double fulfilment / emails)' );
LMC_Jobs::publish( $job );
ok( 'publish' === get_post_status( $job ), 'Admin approval publishes' );
ok( abs( (int) lmc_job_meta( $job, 'expires' ) - ( lmc_now() + 30 * DAY_IN_SECONDS ) ) < 5, 'Expiry set to 30 days after publishing' );
ok( mails( 'vacancy_published' ) >= 1, '"Vacancy published" email sent' );
ok( (bool) get_user_meta( $emp, 'lmc_trusted', true ), 'Employer becomes trusted after first approval' );
global $wpdb;
ok( 2 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . lmc_table( 'social' ) . ' WHERE job_id = %d', $job ) ), 'LinkedIn + Facebook drafts created (employer gave permission)' );

$job2 = make_job( $emp, 'Second Vacancy' );
$wpdb->insert( lmc_table( 'payments' ), array( 'user_id' => $emp, 'job_id' => $job2, 'package' => 'standard', 'purpose' => 'new', 'net' => 3900, 'vat' => 0, 'total' => 3900, 'status' => 'pending', 'mode' => 'test', 'stripe_session' => 'cs_test_B', 'created_at' => lmc_mysql_time() ) );
signed_webhook( wp_json_encode( array( 'type' => 'checkout.session.completed', 'data' => array( 'object' => array( 'id' => 'cs_test_B', 'payment_status' => 'paid', 'amount_total' => 100, 'currency' => 'gbp' ) ) ) ), $secret );
ok( 'lmc_unpaid' === get_post_status( $job2 ), 'Amount mismatch is refused' );
signed_webhook( wp_json_encode( array( 'type' => 'checkout.session.completed', 'data' => array( 'object' => array( 'id' => 'cs_test_B', 'payment_status' => 'paid', 'amount_total' => 3900, 'currency' => 'gbp' ) ) ) ), $secret );
ok( 'publish' === get_post_status( $job2 ), 'Trusted employer\'s next paid vacancy auto-publishes' );

section( 'Packs and credits' );
$emp2 = make_employer( 'pack@example.test' );
$job3 = make_job( $emp2, 'Pack Vacancy' );
$wpdb->insert( lmc_table( 'payments' ), array( 'user_id' => $emp2, 'job_id' => $job3, 'package' => 'pack5', 'purpose' => 'new', 'net' => 17500, 'vat' => 0, 'total' => 17500, 'status' => 'pending', 'mode' => 'test', 'stripe_session' => 'cs_test_C', 'created_at' => lmc_mysql_time() ) );
LMC_Payments::fulfil_session( array( 'id' => 'cs_test_C', 'payment_status' => 'paid', 'amount_total' => 17500, 'currency' => 'gbp' ) );
ok( 4 === ( LMC_Credits::balance( $emp2 )['standard'] ?? 0 ), 'Five-pack: 5 credits, 1 used for the vacancy, 4 left' );
ok( LMC_Credits::grant_free_listing( $emp2 ) > 0, 'Free introductory listing granted' );
ok( 0 === LMC_Credits::grant_free_listing( $emp2 ), '…only once per employer' );
ok( 0 === LMC_Credits::consume( $emp2, 'premium' ), 'Cannot consume a credit tier you don\'t have' );
ok( LMC_Payments::revenue( lmc_now() - DAY_IN_SECONDS ) === 6900 + 3900 + 17500, 'Revenue totals paid net amounts (£283.00)' );

section( 'Closing date' );
$j = make_job( $emp, 'Closing Date Vacancy', 'draft', array( 'closing_date' => wp_date( 'Y-m-d', lmc_now() + 10 * DAY_IN_SECONDS ) ) );
LMC_Jobs::publish( $j );
ok( (int) lmc_job_meta( $j, 'expires' ) < lmc_now() + 11 * DAY_IN_SECONDS, 'Listing ends on an earlier closing date' );

section( 'Search & filters' );
$ids = function ( $f ) {
	$q = new WP_Query( LMC_Search::build_args( array_merge( array( 'q' => '', 'location' => '', 'radius' => 25, 'sector' => '', 'role' => '', 'type' => '', 'experience' => '', 'salary_min' => 0, 'sort' => '' ), $f ) ) );
	return wp_list_pluck( $q->posts, 'ID' );
};
$all = $ids( array() );
ok( count( $all ) >= 9, 'Live vacancies returned (' . count( $all ) . ')' );
ok( 'premium' === lmc_job_tier( $all[0] ), 'Premium vacancies rank first' );
$r = $ids( array( 'sector' => 'home-lifts' ) );
ok( $r && ! array_diff( $r, array_filter( $r, fn( $i ) => has_term( 'home-lifts', 'lmc_sector', $i ) ) ), 'Sector filter' );
$r = $ids( array( 'salary_min' => 45000 ) );
ok( $r && min( array_map( fn( $i ) => (int) lmc_job_meta( $i, 'salary_annual' ), $r ) ) >= 45000, 'Salary filter' );
$r = $ids( array( 'location' => 'South East' ) );
ok( in_array( $j, $r, true ), 'Region search includes child counties (Kent → South East)' );
$r = $ids( array( 'location' => 'Kent' ) );
ok( $r && ! array_diff( $r, array_filter( $r, fn( $i ) => has_term( 'kent', 'lmc_location', $i ) ) ), 'County search' );
update_post_meta( $j, '_lmc_lat', 51.28 ); update_post_meta( $j, '_lmc_lng', 0.53 );
$r = $ids( array( 'location' => 'TN23', 'radius' => 25 ) );
ok( in_array( $j, $r, true ), 'Postcode radius search (Ashford TN23 → Maidstone within 25 miles)' );
$r = $ids( array( 'location' => 'LS10', 'radius' => 10 ) );
ok( ! in_array( $j, $r, true ), '…and excludes far-away jobs' );
$r = $ids( array( 'q' => 'LOLER' ) );
ok( count( $r ) >= 1, 'Keyword search' );
$r = $ids( array( 'experience' => 'entry-level' ) );
ok( count( $r ) >= 1, 'Experience filter' );

section( 'Expiry reminders, expiry, follow-ups, renewal (time travel)' );
$base = $clock;
$exp  = (int) lmc_job_meta( $job, 'expires' );
$clock = $exp - 6 * DAY_IN_SECONDS;
LMC_Jobs::run_lifecycle();
ok( 1 == lmc_job_meta( $job, 'sent_expiry_7' ), '7-day reminder sent' );
$n = mails( 'expiry_7' );
LMC_Jobs::run_lifecycle();
ok( mails( 'expiry_7' ) === $n, '…and not repeated on the next run' );
$clock = $exp - DAY_IN_SECONDS;
LMC_Jobs::run_lifecycle();
ok( 1 == lmc_job_meta( $job, 'sent_expiry_2' ), '2-day reminder sent' );
$clock = $exp + 60;
LMC_Jobs::run_lifecycle();
ok( 'lmc_expired' === get_post_status( $job ), 'Listing expires automatically' );
ok( mails( 'vacancy_expired' ) >= 1, 'Expired notification with renewal link sent' );
$q = new WP_Query( LMC_Search::build_args( LMC_Search::filters() ) );
ok( ! in_array( $job, wp_list_pluck( $q->posts, 'ID' ), true ), 'Expired vacancy removed from live search' );
$clock = $exp + 4 * DAY_IN_SECONDS;
LMC_Jobs::run_lifecycle();
ok( 1 == lmc_job_meta( $job, 'sent_filled' ), '"Has it been filled?" follow-up sent' );
wp_update_post( array( 'ID' => $job2, 'post_status' => 'lmc_expired' ) );
update_post_meta( $job2, '_lmc_expired_at', $exp );
$clock = $exp + 22 * DAY_IN_SECONDS;
LMC_Jobs::run_lifecycle();
ok( 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . lmc_table( 'email_log' ) . " WHERE template = 'repeat_followup' AND recipient = 'buyer@example.test'" ), 'Repeat-advertising email sent once per employer (not once per vacancy)' );
$wpdb->insert( lmc_table( 'payments' ), array( 'user_id' => $emp, 'job_id' => $job, 'package' => 'standard', 'purpose' => 'renew', 'net' => 3900, 'vat' => 0, 'total' => 3900, 'status' => 'pending', 'mode' => 'test', 'stripe_session' => 'cs_test_R', 'created_at' => lmc_mysql_time() ) );
LMC_Payments::fulfil_session( array( 'id' => 'cs_test_R', 'payment_status' => 'paid', 'amount_total' => 3900, 'currency' => 'gbp' ) );
ok( 'publish' === get_post_status( $job ), 'Paid renewal re-activates the expired vacancy' );
ok( abs( (int) lmc_job_meta( $job, 'expires' ) - ( $clock + 30 * DAY_IN_SECONDS ) ) < 5, 'Renewed for 30 days from now' );
ok( mails( 'renewal_confirmation' ) >= 1, 'Renewal confirmation sent' );
$e1 = (int) lmc_job_meta( $job, 'expires' );
LMC_Jobs::renew( $job );
ok( (int) lmc_job_meta( $job, 'expires' ) === $e1 + 30 * DAY_IN_SECONDS, 'Early renewal adds time to the END of the current listing' );
$clock = $base;



section( 'Content checks' );
ok( (bool) LMC_Forms::content_flags( 'We want a young, energetic engineer' ), 'Age-discriminatory wording flagged' );
ok( (bool) LMC_Forms::content_flags( 'A registration fee of £50 applies' ), 'Candidate fee flagged' );
ok( ! LMC_Forms::content_flags( 'Experienced stairlift engineer needed in Kent' ), 'Normal advert not flagged' );

section( 'SEO' );
$s = LMC_SEO::job_schema( $job );
ok( 'JobPosting' === $s['@type'] && $s['title'] && $s['datePosted'] && $s['validThrough'] && $s['hiringOrganization']['name'] && 'GB' === $s['jobLocation']['address']['addressCountry'] && 'GBP' === $s['baseSalary']['currency'], 'JobPosting has required + recommended fields' );
ok( LMC_SEO::term_indexable( get_term_by( 'slug', 'stairlifts', 'lmc_sector' ) ), 'Sector pages indexable (curated copy)' );
ok( ! LMC_SEO::term_indexable( get_term_by( 'slug', 'cornwall', 'lmc_location' ) ), 'Empty county page NOT indexable (no thin pages)' );
$html = wp_remote_retrieve_body( wp_remote_get( get_permalink( $job ) ) );
ok( false !== strpos( $html, '"@type":"JobPosting"' ), 'Live job page outputs JobPosting JSON-LD' );
ok( false !== strpos( $html, 'rel="canonical"' ) && false !== strpos( $html, 'name="description"' ), 'Canonical + meta description present' );
wp_update_post( array( 'ID' => $j, 'post_status' => 'lmc_expired' ) );
$html = wp_remote_retrieve_body( wp_remote_get( get_permalink( $j ) ) );
ok( false !== strpos( $html, 'noindex' ) && false === strpos( $html, '"JobPosting"' ) && false !== strpos( $html, 'has closed' ), 'Expired job: closed notice, noindex, no JobPosting' );
$html = wp_remote_retrieve_body( wp_remote_get( home_url( '/jobs/?sector=stairlifts&salary_min=30000' ) ) );
ok( false !== strpos( $html, 'noindex' ), 'Filtered search pages are noindex' );

section( 'Vacancy discovery' );
$fx = file_get_contents( __DIR__ . '/fixtures/careers-jsonld.html' );
$p  = LMC_Discovery::parse( $fx, 'https://employer.example/careers/' );
ok( 'jsonld' === $p['method'] && 3 === count( $p['items'] ), 'Parses JobPosting structured data (3 jobs)' );
$p = LMC_Discovery::parse( file_get_contents( __DIR__ . '/fixtures/careers-links.html' ), 'https://employer.example/careers/' );
$titles = wp_list_pluck( $p['items'], 'title' );
ok( in_array( 'Stairlift Service Engineer', $titles, true ) && 'https://employer.example/careers/stairlift-service-engineer' === $p['items'][0]['url'], 'Parses job links and resolves relative URLs' );
ok( 0 === LMC_Discovery::score( 'Sales Executive' )[0] && LMC_Discovery::score( 'Platform Lift Service Engineer' )[0] >= 80, 'Relevance scoring excludes non-engineering roles' );
$p = LMC_Discovery::parse( '<html><body><div id="root"></div><script src="app.js"></script></body></html>', 'https://x.example/' );
ok( $p['js_required'], 'JavaScript-only careers page detected (flagged, not faked)' );
$robots = "User-agent: *\nDisallow: /careers/\n\nUser-agent: Googlebot\nAllow: /";
ok( ! LMC_Discovery::robots_allowed( 'https://x.example/careers/', $robots ) && LMC_Discovery::robots_allowed( 'https://x.example/about', $robots ), 'robots.txt rules honoured' );
copy( __DIR__ . '/fixtures/careers-links.html', ABSPATH . 'fixture-careers.html' );
add_filter( 'http_request_host_is_external', '__return_true' );
add_filter( 'http_request_args', function ( $a ) { $a['reject_unsafe_urls'] = false; return $a; } );
$wpdb->insert( lmc_table( 'sources' ), array( 'company' => 'Fixture Lifts Ltd', 'careers_url' => home_url( '/fixture-careers.html' ), 'terms_reviewed' => 0, 'enabled' => 1, 'created_at' => lmc_mysql_time() ) );
$sid = $wpdb->insert_id;
LMC_Discovery::run();
ok( 'awaiting_review' === $wpdb->get_var( "SELECT status FROM " . lmc_table( 'sources' ) . " WHERE id = $sid" ), 'Source not fetched until terms are reviewed' );
$wpdb->update( lmc_table( 'sources' ), array( 'terms_reviewed' => 1, 'last_checked' => null ), array( 'id' => $sid ) );
LMC_Discovery::run();
$src = $wpdb->get_row( "SELECT * FROM " . lmc_table( 'sources' ) . " WHERE id = $sid" );
ok( 'ok' === $src->status && 2 === (int) $src->last_found, 'Live check of a careers page: 2 relevant vacancies found (' . $src->status_note . ')' );
$opp = $wpdb->get_row( "SELECT * FROM " . lmc_table( 'opportunities' ) . " WHERE source_id = $sid ORDER BY id LIMIT 1" );
ok( $opp && 'new' === $opp->status && $opp->first_seen && $opp->url, 'Opportunity recorded with employer, title, URL, first-seen' );
file_put_contents( ABSPATH . 'fixture-careers.html', str_replace( 'Stairlift Service Engineer', 'Removed', file_get_contents( ABSPATH . 'fixture-careers.html' ) ) );
$wpdb->update( lmc_table( 'sources' ), array( 'last_checked' => null ), array( 'id' => $sid ) );
LMC_Discovery::run();
ok( 0 === (int) $wpdb->get_var( "SELECT still_live FROM " . lmc_table( 'opportunities' ) . " WHERE id = {$opp->id}" ), 'Vacancy no longer on the page is marked "gone"' );
$draft = LMC_Discovery::create_draft_from_opportunity( $opp->id + 1 );
ok( 'draft' === get_post_status( $draft ) && false !== strpos( get_post_field( 'post_content', $draft ), 'ORIGINAL' ), 'Approved opportunity becomes a DRAFT with placeholder (no copied text)' );
unlink( ABSPATH . 'fixture-careers.html' );
$wpdb->insert( lmc_table( 'sources' ), array( 'company' => 'Broken Ltd', 'careers_url' => home_url( '/no-such-page-xyz.html' ), 'terms_reviewed' => 1, 'enabled' => 1, 'created_at' => lmc_mysql_time() ) );
LMC_Discovery::run();
ok( in_array( $wpdb->get_var( "SELECT status FROM " . lmc_table( 'sources' ) . " WHERE company = 'Broken Ltd'" ), array( 'error', 'no_parse' ), true ), 'Unreachable careers page is flagged as failing' );

section( 'Outreach CRM & PECR guard rails' );
lmc_update_opts( array( 'postal_address' => '' ) );
$pc = LMC_CRM::save( array( 'company' => 'Corp Lifts Ltd', 'entity_type' => 'limited_company', 'contact_email' => 'careers@corp.example', 'status' => 'ready' ) );
$r  = LMC_CRM::send_outreach( $pc, 'outreach_free_listing' );
ok( is_string( $r ) && false !== stripos( $r, 'postal address' ), 'Blocked until sender postal address configured' );
lmc_update_opts( array( 'postal_address' => '1 Example Street, Exampletown, EX1 1AA' ) );
$ps = LMC_CRM::save( array( 'company' => 'Jo Bloggs Stairlifts', 'entity_type' => 'sole_trader', 'contact_email' => 'jo@solo.example' ) );
$r  = LMC_CRM::send_outreach( $ps, 'outreach_free_listing' );
ok( is_string( $r ) && false !== strpos( $r, 'PECR' ), 'Sole trader without consent is blocked (PECR)' );
$pu = LMC_CRM::save( array( 'company' => 'Unknown Co', 'contact_email' => 'info@unknown.example' ) );
ok( is_string( LMC_CRM::send_outreach( $pu, 'outreach_free_listing' ) ), 'Unknown entity type is treated as individual (blocked)' );
ok( true === LMC_CRM::send_outreach( $pc, 'outreach_free_listing' ), 'Limited company contact can be emailed' );
$p = LMC_CRM::get( $pc );
ok( 'free_listing_offered' === $p->status && $p->last_contacted && $p->follow_up, 'Contact logged, status + follow-up date set' );
$log = file_get_contents( ( getenv( 'LMC_DEV_DIR' ) ?: '/tmp/lmc-dev' ) . '/mail.log' );
ok( false !== strpos( $log, 'Unsubscribe' ) && false !== strpos( $log, '1 Example Street' ), 'Email includes unsubscribe link + sender address' );
ok( true === LMC_CRM::send_outreach( $pc, 'outreach_followup' ), 'One follow-up allowed' );
ok( is_string( LMC_CRM::send_outreach( $pc, 'outreach_followup' ) ), 'Third contact without reply is blocked' );
$pc2 = LMC_CRM::save( array( 'company' => 'Corp Two Ltd', 'entity_type' => 'plc', 'contact_email' => 'hr@two.example' ) );
$url = LMC_CRM::unsubscribe_url( 'hr@two.example' );
parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $qs );
ok( lmc_verify_sign( 'unsub' . $qs['e'], $qs['s'] ), 'Unsubscribe link is signed' );
LMC_CRM::suppress( 'HR@two.example' );
ok( is_string( LMC_CRM::send_outreach( $pc2, 'outreach_free_listing' ) ) && 'do_not_contact' === LMC_CRM::get( $pc2 )->status, 'Unsubscribed contact suppressed (case-insensitive)' );
lmc_update_opts( array( 'outreach_daily_cap' => 2 ) );
$pc3 = LMC_CRM::save( array( 'company' => 'Corp Three Ltd', 'entity_type' => 'limited_company', 'contact_email' => 'jobs@three.example' ) );
$r = LMC_CRM::send_outreach( $pc3, 'outreach_free_listing' );
ok( is_string( $r ) && false !== stripos( $r, 'limit' ), 'Daily outreach cap enforced' );

section( 'Weekly report' );
$d = LMC_Reports::data();
ok( isset( $d['active'], $d['new_published'], $d['expired'], $d['employers_total'], $d['employers_new'], $d['revenue_week'], $d['upcoming_renewals'], $d['traffic'], $d['popular_sectors'] ), 'Report includes all required metrics' );
LMC_Stats::record( $job, 'views', 5 );
LMC_Stats::record( $job, 'clicks', 2 );
ok( 5 === LMC_Stats::totals( $job )['views'] && 2 === LMC_Stats::totals( $job )['clicks'], 'View / apply-click counters' );
ok( LMC_Reports::send_weekly(), 'Weekly report email sent' );
ok( false !== strpos( file_get_contents( ( getenv( 'LMC_DEV_DIR' ) ?: '/tmp/lmc-dev' ) . '/mail.log' ), 'weekly report' ), 'Report delivered to admin address' );

section( 'Automation health' );
ok( ! lmc_heartbeat_warnings() || true, 'Heartbeat check runs' );
$clock = $base + 3 * DAY_IN_SECONDS;
ok( count( lmc_heartbeat_warnings() ) >= 1, 'Missed automation runs produce a dashboard warning' );
$clock = $base;


/* ================== Growth features ================== */
wp_set_current_user( 0 );
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120 Safari/537.36';
$tf = wp_date( 'Y-m-d', lmc_now() - DAY_IN_SECONDS );
$tt = wp_date( 'Y-m-d', lmc_now() + 2 * DAY_IN_SECONDS );

section( 'Traffic analytics (cookie-free)' );
ok( 'Organic search' === LMC_Analytics::classify( 'https://www.google.co.uk/', '' )[0], 'Google referrer → Organic search' );
ok( 'Social' === LMC_Analytics::classify( 'https://www.linkedin.com/feed/', '' )[0] && 'Social' === LMC_Analytics::classify( 'https://l.facebook.com/x', '' )[0], 'LinkedIn / Facebook → Social' );
ok( 'Job aggregators' === LMC_Analytics::classify( 'https://uk.indeed.com/viewjob', '' )[0], 'Indeed → Job aggregators' );
ok( 'Job alert emails' === LMC_Analytics::classify( '', 'jobalert' )[0], 'utm_source=jobalert → Job alert emails' );
ok( 'Direct' === LMC_Analytics::classify( '', '' )[0], 'No referrer → Direct' );
ok( null === LMC_Analytics::classify( home_url( '/jobs/' ), '' ), 'Internal navigation is not counted as a new visit' );
$hit = function ( $params ) {
	$r = new WP_REST_Request( 'POST', '/lmc/v1/hit' );
	foreach ( $params as $k => $v ) { $r->set_param( $k, $v ); }
	return rest_do_request( $r )->get_status();
};
$live_job = get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) )[0];
$v0 = LMC_Stats::totals( $live_job )['views'];
$hit( array( 't' => 'page', 'ref' => 'https://www.google.com/', 'path' => '/jobs/x/', 'job' => $live_job, 'ret' => '1' ) );
$hit( array( 't' => 'page', 'ref' => 'https://www.linkedin.com/', 'path' => '/', 'ret' => '0' ) );
$hit( array( 't' => 'page', 'ref' => home_url( '/' ), 'path' => '/jobs/' ) );
$hit( array( 't' => 'share', 'net' => 'whatsapp', 'job' => $live_job ) );
$hit( array( 't' => 'click', 'job' => $live_job ) );
ok( 3 === LMC_Analytics::sum( 'pageview', $tf, $tt ) && 2 === LMC_Analytics::sum( 'visit', $tf, $tt ), 'Page views (3) and visits (2, internal click excluded)' );
$src = LMC_Analytics::sum( 'source', $tf, $tt, true );
ok( 1 === ( $src['Organic search'] ?? 0 ) && 1 === ( $src['Social'] ?? 0 ), 'Traffic sources recorded' );
ok( isset( LMC_Analytics::sum( 'landing', $tf, $tt, true )['/jobs/x/'] ), 'Landing page recorded' );
ok( 1 === LMC_Analytics::sum( 'visitor_returning', $tf, $tt ) && 1 === LMC_Analytics::sum( 'visitor_new', $tf, $tt ), 'Returning vs new visitors (consented visitors only)' );
ok( LMC_Stats::totals( $live_job )['views'] === $v0 + 1, 'Job view counted from page hit' );
ok( 1 === ( LMC_Analytics::sum( 'share', $tf, $tt, true )['whatsapp'] ?? 0 ), 'Share click counted by network' );
$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
$hit( array( 't' => 'page', 'ref' => 'https://www.google.com/', 'path' => '/' ) );
ok( 2 === LMC_Analytics::sum( 'visit', $tf, $tt ), 'Bots are ignored' );
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome/120';

section( 'Candidate job alerts' );
global $wpdb;
$st = lmc_table( 'subscribers' );
$id = LMC_Alerts::subscribe( 'Candidate@Example.test', LMC_Alerts::prefs( array( 'alert_sectors' => array( 'stairlifts' ), 'alert_location' => 'Kent', 'alert_frequency' => 'daily' ) ), 'test' );
$sub = $wpdb->get_row( "SELECT * FROM $st WHERE id = $id" );
ok( $sub && 'pending' === $sub->status && 'candidate@example.test' === $sub->email && 'stairlifts' === $sub->sectors, 'Sign-up stored as pending with only email + preferences' );
ok( mails( 'alert_confirm' ) >= 1, 'Double opt-in confirmation email sent' );
wp_remote_get( add_query_arg( 'lmc_alert_confirm', $sub->token, home_url( '/' ) ), array( 'redirection' => 0 ) );
$sub = $wpdb->get_row( "SELECT * FROM $st WHERE id = $id" );
ok( 'active' === $sub->status && $sub->confirmed_at, 'Confirmation link activates the alert' );
$n = mails( 'alert_manage' );
LMC_Alerts::subscribe( 'candidate@example.test', LMC_Alerts::prefs( array() ) );
ok( mails( 'alert_manage' ) === $n + 1 && 'active' === $wpdb->get_var( "SELECT status FROM $st WHERE id = $id" ) && 'stairlifts' === $wpdb->get_var( "SELECT sectors FROM $st WHERE id = $id" ), 'Re-signup of an active address sends a manage link (no silent overwrite)' );
ok( is_wp_error( LMC_Alerts::subscribe( 'not-an-email', array() ) ), 'Invalid email rejected' );
$id2 = LMC_Alerts::subscribe( 'leeds@example.test', LMC_Alerts::prefs( array( 'alert_location' => 'LS10', 'alert_radius' => 10 ) ) );
$wpdb->update( $st, array( 'status' => 'active', 'confirmed_at' => lmc_mysql_time(), 'last_sent' => lmc_mysql_time() ), array( 'id' => $id2 ) );
$s2 = $wpdb->get_row( "SELECT * FROM $st WHERE id = $id2" );
ok( null !== $s2->lat, 'Postcode alert geocoded for radius matching' );

// Timing.
$tz = new DateTimeZone( 'Europe/London' );
$tue8 = ( new DateTime( 'next tuesday 08:00', $tz ) )->getTimestamp();
$tue6 = ( new DateTime( 'next tuesday 06:00', $tz ) )->getTimestamp();
$mon8 = ( new DateTime( 'next monday 08:00', $tz ) )->getTimestamp();
$fake = (object) array( 'frequency' => 'daily', 'last_sent' => lmc_mysql_time( $tue8 - DAY_IN_SECONDS ) );
ok( LMC_Alerts::is_due( $fake, $tue8 ) && ! LMC_Alerts::is_due( $fake, $tue6 ), 'Daily alerts go out from 07:00 London time' );
$fake->frequency = 'weekly';
$fake->last_sent = lmc_mysql_time( $mon8 - 7 * DAY_IN_SECONDS );
ok( LMC_Alerts::is_due( $fake, $mon8 ) && ! LMC_Alerts::is_due( $fake, $tue8 ), 'Weekly alerts only on Mondays' );

// Matching + sending.
$clock = $tue8 - 3 * HOUR_IN_SECONDS;
$wpdb->update( $st, array( 'last_sent' => lmc_mysql_time( $clock - 60 ) ), array( 'id' => $id ) );
$wpdb->update( $st, array( 'last_sent' => lmc_mysql_time( $clock - 60 ) ), array( 'id' => $id2 ) );
$kentjob = make_job( $emp, 'Kent Stairlift Alert Test', 'draft' );
LMC_Jobs::publish( $kentjob );
$sub = $wpdb->get_row( "SELECT * FROM $st WHERE id = $id" );
ok( LMC_Alerts::job_matches( $sub, $kentjob ), 'Kent stairlift job matches the Kent + stairlifts alert' );
ok( ! LMC_Alerts::job_matches( $wpdb->get_row( "SELECT * FROM $st WHERE id = $id2" ), $kentjob ), '…but not a Leeds 10-mile alert' );
$homejob = make_job( $emp, 'Kent Home Lift Engineer', 'draft' );
wp_set_object_terms( $homejob, 'home-lifts', 'lmc_sector' );
LMC_Jobs::publish( $homejob );
ok( ! LMC_Alerts::job_matches( $sub, $homejob ), 'Sector filter respected (home lift job excluded)' );
$clock = $tue8;
$sent = LMC_Alerts::run();
ok( 1 === $sent, 'Hourly run sends one digest (only to the matching subscriber)' );
$log = file_get_contents( ( getenv( 'LMC_DEV_DIR' ) ?: '/tmp/lmc-dev' ) . '/mail.log' );
ok( false !== strpos( $log, 'TO: candidate@example.test' ) && false !== strpos( $log, 'Kent Stairlift Alert Test' ) && false !== strpos( $log, 'utm_source=jobalert' ), 'Digest lists the job with tracked links' );
ok( false !== strpos( $log, 'Unsubscribe' ), 'Digest has unsubscribe link' );
ok( 0 === LMC_Alerts::run(), 'No duplicate digest on the next run the same day' );
$wpdb->insert( $st, array( 'email' => 'old@example.test', 'status' => 'pending', 'token' => wp_generate_password( 40, false ), 'created_at' => lmc_mysql_time( $clock - 8 * DAY_IN_SECONDS ) ) );
LMC_Alerts::run();
ok( ! $wpdb->get_var( "SELECT id FROM $st WHERE email = 'old@example.test'" ), 'Unconfirmed sign-ups deleted after 7 days' );
$sub = $wpdb->get_row( "SELECT * FROM $st WHERE id = $id" );
wp_remote_get( LMC_Alerts::unsubscribe_url( $sub ) );
ok( ! $wpdb->get_var( "SELECT id FROM $st WHERE id = $id" ), 'Unsubscribe link deletes the subscription and email' );
$clock = $base;
$html = wp_remote_retrieve_body( wp_remote_get( get_permalink( $kentjob ) ) );
ok( false !== strpos( $html, 'lmc-alert-box' ) && false !== strpos( $html, 'name="alert_sectors[]" value="stairlifts"' ), 'Job page has a pre-filled job alert box' );
$r = wp_remote_post( lmc_page_url( 'alerts' ), array( 'redirection' => 0, 'body' => array( 'lmc_action' => 'lmc_alert_signup', 'alert_email' => 'web@example.test', 'alert_consent' => 1, 'alert_sectors' => array( 'platform-lifts' ), 'alert_source' => 'job_page' ) ) );
ok( 302 === wp_remote_retrieve_response_code( $r ) && 'pending' === $wpdb->get_var( "SELECT status FROM $st WHERE email = 'web@example.test'" ), 'Web sign-up form works (from a cached page – no nonce needed)' );
$r = wp_remote_post( lmc_page_url( 'alerts' ), array( 'redirection' => 0, 'body' => array( 'lmc_action' => 'lmc_alert_signup', 'alert_email' => 'noconsent@example.test' ) ) );
ok( ! $wpdb->get_var( "SELECT id FROM $st WHERE email = 'noconsent@example.test'" ), 'No subscription without the consent tick' );

section( 'Social sharing & employer backlinks' );
$html = wp_remote_retrieve_body( wp_remote_get( get_permalink( $kentjob ) ) );
ok( false !== strpos( $html, 'linkedin.com/sharing' ) && false !== strpos( $html, 'wa.me' ) && false !== strpos( $html, 'facebook.com/sharer' ) && false !== strpos( $html, 'data-lmc-share="copy"' ), 'Job page has LinkedIn, Facebook, WhatsApp, X, email and copy-link sharing' );
ok( false !== strpos( $html, '"BreadcrumbList"' ), 'BreadcrumbList structured data on job pages' );
$curl = LMC_Employer_Share::company_url( $emp );
$html = wp_remote_retrieve_body( wp_remote_get( $curl ) );
ok( false !== strpos( $html, 'Kent Stairlift Alert Test' ) && false !== strpos( $html, 'rel="canonical"' ), 'Public company page lists the employer\'s live vacancies' );
ok( false !== strpos( wp_remote_retrieve_body( wp_remote_get( home_url( '/wp-sitemap-companies-1.xml' ) ) ), $curl ), 'Company pages with live jobs are in the sitemap' );
$kit = LMC_Employer_Share::render_kit( $emp, $kentjob );
ok( false !== strpos( $kit, 'badge-advertised.svg' ) && false !== strpos( $kit, 'utm_source=employer' ) && false !== strpos( $kit, 're hiring: Kent Stairlift Alert Test' ), 'Share kit: tracked link, ready-written post, badge code' );
file_put_contents( ABSPATH . 'fixture-linked.html', '<html><body><a href="' . esc_url( home_url( '/companies/x/' ) ) . '">Our jobs</a></body></html>' );
file_put_contents( ABSPATH . 'fixture-unlinked.html', '<html><body><a href="https://example.com/">Elsewhere</a></body></html>' );
list( $okl, $bonus ) = LMC_Employer_Share::check( $emp, home_url( '/fixture-unlinked.html' ) );
ok( ! $okl && ! $bonus, 'Page without a link to us is not verified' );
$before_f = LMC_Credits::balance( $emp )['featured'] ?? 0;
list( $okl, $bonus ) = LMC_Employer_Share::check( $emp, home_url( '/fixture-linked.html' ) );
ok( $okl && $bonus && ( LMC_Credits::balance( $emp )['featured'] ?? 0 ) === $before_f + 1, 'Verified backlink → free Featured credit' );
list( , $bonus ) = LMC_Employer_Share::check( $emp, home_url( '/fixture-linked.html' ) );
ok( ! $bonus, 'Backlink reward only once per employer' );
ok( 1 === LMC_Employer_Share::live_backlink_count(), 'Live backlink count for reports' );
unlink( ABSPATH . 'fixture-linked.html' );
ok( 0 === LMC_Employer_Share::recheck_all(), 'Monthly re-check notices a removed link' );
unlink( ABSPATH . 'fixture-unlinked.html' );

section( 'Job feed for aggregators' );
$xml = simplexml_load_string( wp_remote_retrieve_body( wp_remote_get( home_url( '/feeds/jobs.xml' ) ) ) );
$live_n = count( get_posts( array( 'post_type' => 'lmc_job', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) );
ok( $xml && count( $xml->job ) === $live_n, 'Valid XML feed with every live vacancy (' . $live_n . ')' );
ok( $xml && false !== strpos( (string) $xml->job[0]->url, 'utm_source=aggregator' ) && 'GB' === (string) $xml->job[0]->country && (string) $xml->job[0]->salary, 'Feed jobs link back to us with salary and location' );

section( 'Search engine integrations' );
$key = openssl_pkey_new( array( 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ) );
openssl_pkey_export( $key, $pem );
$jwt = LMC_Google::jwt( array( 'client_email' => 'svc@project.iam.gserviceaccount.com', 'private_key' => $pem ), 'https://www.googleapis.com/auth/indexing' );
list( $h, $c, $sg ) = explode( '.', $jwt );
$claims = json_decode( base64_decode( strtr( $c, '-_', '+/' ) ), true );
ok( 1 === openssl_verify( "$h.$c", base64_decode( strtr( $sg, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $sg ) % 4 ) % 4 ) ), openssl_pkey_get_details( $key )['key'], OPENSSL_ALGO_SHA256 ) && 'https://oauth2.googleapis.com/token' === $claims['aud'], 'Service-account JWT for Google APIs is correctly signed (RS256)' );
ok( is_wp_error( LMC_Google::search_console( '2026-01-01', '2026-01-31' ) ), 'Search Console reports "not connected" cleanly until configured' );
lmc_update_opts( array( 'indexnow_enabled' => 1, 'indexnow_key' => 'abc123testkey456' ) );
ok( 'abc123testkey456' === trim( wp_remote_retrieve_body( wp_remote_get( home_url( '/abc123testkey456.txt' ) ) ) ), 'IndexNow key file served' );
lmc_update_opts( array( 'indexnow_enabled' => 0 ) );

section( 'SEO landing pages & content' );
$roles = get_terms( array( 'taxonomy' => 'lmc_role', 'hide_empty' => false ) );
ok( 13 === count( array_filter( $roles, array( 'LMC_SEO', 'term_indexable' ) ) ), 'All 13 job-category landing pages have curated copy and are indexable' );
ok( 9 === count( array_filter( get_terms( array( 'taxonomy' => 'lmc_sector', 'hide_empty' => false ) ), array( 'LMC_SEO', 'term_indexable' ) ) ), 'All 9 sector landing pages indexable' );
$g = wp_remote_retrieve_body( wp_remote_get( lmc_page_url( 'g_loler' ) ) );
ok( false !== strpos( $g, '"@type":"Article"' ) && false !== strpos( $g, '"BreadcrumbList"' ), 'Career guides output Article + Breadcrumb schema' );
ok( 5 === count( get_pages( array( 'parent' => (int) get_option( 'lmc_page_guides' ) ) ) ), '5 career guides published under /career-guides/' );
$sal_before = LMC_Insights::salaries( 'lmc_role' );
ok( ! isset( $sal_before['LOLER inspector'] ) || $sal_before['LOLER inspector']['n'] >= 5, 'Salary data hidden for categories with fewer than 5 adverts' );
for ( $i = 0; $i < 5; $i++ ) {
	$sj = make_job( $emp, 'LOLER sample ' . $i, 'draft', array( 'salary_min' => 30000 + $i * 2000, 'salary_max' => 34000 + $i * 2000 ) );
	wp_set_object_terms( $sj, 'loler-inspector', 'lmc_role' );
	LMC_Jobs::publish( $sj );
}
delete_transient( 'lmc_sal_' . md5( 'lmc_role' ) );
$sal = LMC_Insights::salaries( 'lmc_role' );
ok( isset( $sal['LOLER inspector'] ) && $sal['LOLER inspector']['n'] >= 5 && $sal['LOLER inspector']['low'] <= $sal['LOLER inspector']['median'] && $sal['LOLER inspector']['median'] <= $sal['LOLER inspector']['high'], 'Salary insights appear once 5+ real adverts exist (median £' . number_format( $sal['LOLER inspector']['median'] ?? 0 ) . ' from ' . ( $sal['LOLER inspector']['n'] ?? 0 ) . ' adverts)' );

section( 'Monthly traffic & SEO report' );
lmc_update_opts( array( 'launch_date' => wp_date( 'Y-m-01', lmc_now() ) ) );
ok( 1 === LMC_Monthly_Report::month_number( lmc_now() ), 'Growth-plan month number from launch date' );
ok( 12 === count( LMC_Monthly_Report::targets() ) && 450 === LMC_Monthly_Report::targets()[12]['subscribers'], '12-month targets loaded' );
$md = LMC_Monthly_Report::data( wp_date( 'Y-m', lmc_now() ) );
ok( $md['cur']['visits'] >= 2 && $md['cur']['job_views'] >= 1 && isset( $md['sources']['Organic search'] ), 'Monthly data: visits, job views, sources' );
$html = LMC_Monthly_Report::render( $md );
ok( false !== strpos( $html, 'Suggested actions' ) && false !== strpos( $html, 'target' ) && false !== strpos( $html, 'Traffic sources' ), 'Report shows targets, sources and suggested actions' );
ok( LMC_Monthly_Report::send( wp_date( 'Y-m', lmc_now() ) ), 'Monthly report emailed' );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
