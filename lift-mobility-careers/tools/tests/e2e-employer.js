// Full employer journey + access control + consent, in a real browser, against the local dev site.
// Payments use the DEV-ONLY fake Stripe (tools/dev/mu-plugins/lmc-dev-fakes.php), which sends correctly
// signed webhooks through the real webhook endpoint.
// Run on a fresh site:  bash tools/dev/setup-local.sh && (PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:8080 -t /tmp/lmc-dev/wp tools/dev/router.php &) && node tools/tests/e2e-employer.js
const { chromium } = require('playwright');
const fs = require('fs'), path = require('path'), { execFileSync } = require('child_process');
const BASE = process.env.LMC_URL || 'http://127.0.0.1:8080';
const MAIL = (process.env.LMC_DEV_DIR || '/tmp/lmc-dev') + '/mail.log';
const SHOTS = process.env.LMC_SHOTS || '/tmp/lmc-shots';
const TMP = fs.mkdtempSync('/tmp/lmc-e2e-');
fs.mkdirSync(SHOTS, { recursive: true });
let pass = 0, fail = 0;
const ok = (c, n) => { c ? pass++ : fail++; console.log(`  ${c ? '✓' : '✗ FAIL:'} ${n}`); };
const section = t => console.log('\n' + t);
const cli = (...a) => JSON.parse(execFileSync('php', [path.join(__dirname, 'dev-cli.php'), ...a.map(String)]).toString() || 'null');
const mail = () => fs.readFileSync(MAIL, 'utf8');
const stamp = Date.now();
const A = { email: `alpha${stamp}@example.test`, pass: 'alpha-password-123', company: 'Alpha Lifts Ltd' };
const B = { email: `bravo${stamp}@example.test`, pass: 'bravo-password-123', company: 'Bravo Hoists Ltd' };

// Test upload files.
const goodPng = path.join(__dirname, '../../brand/favicon-32.png');
const phpAsPng = path.join(TMP, 'logo.png'); fs.writeFileSync(phpAsPng, '<?php echo "pwned"; ?>');
const svg = path.join(TMP, 'logo.svg'); fs.writeFileSync(svg, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');
const big = path.join(TMP, 'big.png'); fs.writeFileSync(big, Buffer.concat([fs.readFileSync(goodPng), Buffer.alloc(1100 * 1024)]));

async function newPage(b, w = 1280) {
	const c = await b.newContext({ viewport: { width: w, height: 900 } });
	await c.addInitScript(() => { try { localStorage.setItem('lmc_consent', 'no'); } catch (e) {} }); // keep banner out of the way
	const p = await c.newPage();
	p.on('dialog', d => d.accept());
	p.errors = []; p.on('pageerror', e => p.errors.push(e.message));
	return p;
}
const wait3 = p => p.waitForTimeout(3100); // forms reject submissions faster than a human (anti-bot)

async function register(p, u) {
	await p.goto(BASE + '/employer/register/'); await wait3(p);
	await p.fill('#lmc-first_name', u.company.split(' ')[0]); await p.fill('#lmc-last_name', 'Tester');
	await p.fill('#lmc-company', u.company); await p.selectOption('#lmc-entity_type', 'limited_company');
	await p.fill('#lmc-email', u.email); await p.fill('#lmc-password', u.pass); await p.check('input[name=agree]');
	await p.click('form.lmc-form button[type=submit]'); await p.waitForLoadState();
	const links = mail().match(/https?:\/\/[^\s\]]+lmc_verify=\d+&(?:amp;)?k=[A-Za-z0-9]+/g) || [];
	const mine = links.filter(l => mail().lastIndexOf(l) > mail().lastIndexOf('TO: ' + u.email));
	await p.goto((mine.pop() || links.pop()).replace(/&amp;/g, '&'));
}
async function fillVacancy(p, title) {
	await p.goto(BASE + '/employer/submit-vacancy/'); await wait3(p);
	await p.fill('#lmc-job_title', title);
	await p.check('input[name="sectors[]"][value="stairlifts"]');
	await p.selectOption('#lmc-role', 'service-engineer'); await p.selectOption('#lmc-emp_type', 'full-time'); await p.selectOption('#lmc-experience', 'experienced');
	await p.fill('#lmc-description', 'You will service and repair straight and curved stairlifts for customers across Kent, with a fully equipped van, manufacturer training and a supportive team. Most work is planned in advance with a fair on-call rota.');
	await p.fill('#lmc-responsibilities', 'Planned maintenance\nRepairs'); await p.fill('#lmc-requirements', 'Electrical background\nFull UK driving licence');
	await p.selectOption('#lmc-county', 'kent'); await p.fill('#lmc-town', 'Maidstone'); await p.fill('#lmc-postcode', 'ME14');
	await p.fill('#lmc-salary_min', '34000'); await p.fill('#lmc-salary_max', '38000');
	await p.fill('#lmc-apply_url', 'https://example.com/apply');
	await p.check('input[name=authorised]');
}
const submitForm = async p => { await p.click('form.lmc-form > button[type=submit]'); await p.waitForLoadState(); };
const choose = async (p, pkg) => { await wait3(p); await p.click(`input[name=package][value=${pkg}] ~ button, form:has(input[name=package][value=${pkg}]) button`); await p.waitForLoadState(); };

(async () => {
	cli('setup-payments');
	const b = await chromium.launch();
	const a = await newPage(b), bb = await newPage(b), admin = await newPage(b, 1400);
	await admin.goto(BASE + '/wp-login.php'); await admin.fill('#user_login', 'admin'); await admin.fill('#user_pass', 'admin-dev-password'); await admin.click('#wp-submit'); await admin.waitForLoadState();

	section('1. Register and verify');
	await register(a, A);
	ok((await a.content()).includes('your email address is confirmed'), 'Employer A registered and confirmed email');

	section('2. Submit vacancy – upload restrictions and authorisation');
	await fillVacancy(a, 'Alpha Stairlift Service Engineer');
	await a.uncheck('input[name=authorised]'); await submitForm(a);
	ok((await a.content()).includes('You must confirm you are authorised'), 'Cannot submit without the authorisation declaration');
	for (const [file, expect, label] of [[phpAsPng, 'must be a JPG, PNG or WebP', 'PHP code disguised as .png rejected'], [svg, 'must be a JPG, PNG or WebP', 'SVG (can carry scripts) rejected'], [big, 'under 1MB', 'Files over 1MB rejected']]) {
		await fillVacancy(a, 'Alpha Stairlift Service Engineer');
		await a.setInputFiles('#lmc-logo', file); await a.check('input[name=logo_rights]'); await submitForm(a);
		ok((await a.content()).includes(expect), label);
	}
	await fillVacancy(a, 'Alpha Stairlift Service Engineer');
	await a.setInputFiles('#lmc-logo', goodPng); await submitForm(a);
	ok((await a.content()).includes('confirm you have the right to use this logo'), 'Logo needs the rights confirmation');
	await fillVacancy(a, 'Alpha Stairlift Service Engineer');
	await a.setInputFiles('#lmc-logo', goodPng); await a.check('input[name=logo_rights]'); await submitForm(a);
	ok(a.url().includes('step=package'), 'Valid vacancy with a genuine PNG logo accepted → advert options');
	const jobA = +new URL(a.url()).searchParams.get('job');
	ok(cli('state', jobA).logo > 0, 'Logo stored in the media library');
	await a.screenshot({ path: SHOTS + '/package-step.png', fullPage: true });

	section('3. Payment – declined, abandoned, then paid');
	await choose(a, 'standard');
	ok(a.url().includes('lmc_fake_checkout'), 'Redirected to (fake) Stripe Checkout');
	await a.screenshot({ path: SHOTS + '/fake-checkout.png' });
	await a.click('#decline'); await a.waitForLoadState();
	ok(await a.locator('#declined').count() === 1, 'Declined card: stays on checkout, nothing charged');
	await a.click('#abandon'); await a.waitForLoadState();
	ok((await a.content()).includes('Payment was cancelled'), 'Abandoned checkout → back to dashboard with a clear message');
	let st = cli('state', jobA);
	ok(st.status === 'lmc_unpaid' && st.payments[0].status === 'expired', 'Vacancy still awaiting payment; abandoned session marked expired');
	await a.click('a.lmc-btn:has-text("Complete payment")'); await a.waitForLoadState();
	await choose(a, 'standard'); await a.click('#pay'); await a.waitForLoadState();
	ok((await a.content()).includes('Payment received'), 'Payment succeeded → dashboard confirms');
	st = cli('state', jobA);
	ok(st.status === 'pending' && st.payments.some(p => p.status === 'paid'), 'Signed webhook processed: payment paid, first vacancy held for approval');
	ok(mail().includes('Payment received – Standard advert'), 'Payment confirmation email sent');
	await a.screenshot({ path: SHOTS + '/employer-dashboard.png', fullPage: true });

	section('4. Approval and publishing');
	await admin.goto(BASE + '/wp-admin/admin.php?page=lmc');
	await admin.locator('tr', { hasText: 'Alpha Stairlift Service Engineer' }).locator('a', { hasText: 'Approve' }).click(); await admin.waitForLoadState();
	ok(cli('state', jobA).status === 'publish', 'Admin approval publishes the vacancy');
	ok(mail().includes('Your vacancy is now live: Alpha Stairlift Service Engineer'), '"Now live" email sent');
	await a.goto(BASE + '/jobs/alpha-stairlift-service-engineer/');
	ok(await a.locator('.lmc-job__logo img').count() === 1, 'Live vacancy shows the authorised logo');

	section('5. Edit');
	await a.goto(BASE + '/employer/submit-vacancy/?job=' + jobA); await wait3(a);
	await a.fill('#lmc-salary_min', '36000'); await submitForm(a);
	await a.goto(BASE + '/jobs/alpha-stairlift-service-engineer/');
	ok((await a.content()).includes('£36,000'), 'Edit saved and shown on the live advert');

	section('6. Expiry reminder, renewal, refund of the renewal');
	cli('shift-expiry', jobA, 6);
	ok(mail().includes('Alpha Stairlift Service Engineer – your advert ends in 7 days'), '7-day reminder emailed automatically');
	const before = cli('state', jobA).expires;
	await a.goto(BASE + '/employer/dashboard/?lmc_renew=' + jobA);
	await choose(a, 'standard'); await a.click('#pay'); await a.waitForLoadState();
	const after = cli('state', jobA).expires;
	ok(Math.round((after - before) / 86400) === 30, 'Renewal adds 30 days to the end of the current listing');
	ok(mail().includes('Renewed: Alpha Stairlift Service Engineer'), 'Renewal confirmation email sent');
	cli('refund', jobA);
	ok(Math.abs(cli('state', jobA).expires - before) < 5 && mail().includes('Refund processed'), 'Refunding the renewal removes the extra 30 days and emails the employer');

	section('7. Second employer: access isolation');
	await register(bb, B);
	await bb.goto(BASE + '/employer/submit-vacancy/?job=' + jobA);
	ok((await bb.content()).includes('You cannot edit this vacancy'), 'B cannot open A\'s vacancy for editing');
	await fillVacancy(bb, 'Hijacked title');
	await bb.evaluate(id => { document.querySelector('input[name=job_id]').value = id; }, jobA);
	await submitForm(bb);
	ok(cli('state', jobA).title === 'Alpha Stairlift Service Engineer', 'B cannot overwrite A\'s vacancy by tampering with the form');
	await bb.goto(BASE + '/employer/dashboard/?lmc_renew=' + jobA);
	ok((await bb.content()).includes('Vacancy not found'), 'B cannot renew (or pay for) A\'s vacancy');
	await bb.goto(BASE + '/employer/dashboard/?lmc_share=' + jobA);
	ok(!(await bb.content()).includes('Share kit'), 'B cannot open A\'s share kit');
	await bb.goto(BASE + '/employer/submit-vacancy/?step=package&job=' + jobA);
	ok((await bb.content()).includes('Vacancy not found'), 'B cannot reach A\'s payment step');
	const r1 = await bb.goto(BASE + '/wp-admin/');
	ok(bb.url().includes('/employer/dashboard/'), 'Employers are kept out of wp-admin');
	const demoBefore = cli('demo-count');
	await bb.goto(BASE + '/wp-admin/admin-post.php?action=lmc_admin&do=remove_demo');
	ok(demoBefore > 0 && cli('demo-count') === demoBefore && bb.url().includes('/employer/dashboard/'), 'Employers cannot run admin actions (tried "delete all demo content": nothing deleted)');
	const media = await bb.evaluate(async u => (await fetch(u + '/wp-json/wp/v2/media', { method: 'POST', body: 'x' })).status, BASE);
	ok(media === 401 || media === 403, 'Employers cannot upload through the REST media API (' + media + ')');

	section('8. Double payment from two tabs');
	await fillVacancy(bb, 'Bravo Hoist Engineer'); await submitForm(bb);
	const jobB = +new URL(bb.url()).searchParams.get('job');
	const tab2 = await bb.context().newPage();
	await tab2.goto(bb.url());
	await choose(bb, 'standard'); await choose(tab2, 'standard');
	await bb.click('#pay'); await bb.waitForLoadState(); await tab2.click('#pay'); await tab2.waitForLoadState();
	st = cli('state', jobB);
	ok(st.payments.filter(p => p.status === 'paid').length === 2 && (cli('credits', B.email).standard || 0) === 1, 'Second payment became a standard credit instead of being wasted');
	ok(mail().includes('already been paid for'), 'Employer is told and offered a refund');
	await admin.goto(BASE + '/wp-admin/admin.php?page=lmc');
	await admin.locator('tr', { hasText: 'Bravo Hoist Engineer' }).locator('a', { hasText: 'Approve' }).click(); await admin.waitForLoadState();
	await bb.goto(BASE + '/employer/dashboard/');
	await bb.evaluate(id => { const f = document.querySelector('form input[name=lmc_action][value=lmc_close_job]').form; f.querySelector('input[name=job_id]').value = id; f.submit(); }, jobA);
	await bb.waitForLoadState();
	ok(cli('state', jobA).status === 'publish', 'B cannot close A\'s vacancy by tampering with the close form');

	section('9. Close (filled)');
	await a.goto(BASE + '/employer/dashboard/');
	await a.click('button:has-text("Close (filled)")'); await a.waitForLoadState();
	ok(cli('state', jobA).status === 'lmc_closed', 'Employer closes their own vacancy');
	await a.goto(BASE + '/jobs/');
	ok(!(await a.content()).includes('Alpha Stairlift Service Engineer'), 'Closed vacancy removed from live listings');

	section('10. Cancel account');
	await a.goto(BASE + '/employer/dashboard/?tab=profile');
	await a.click('summary:has-text("Close my account")'); await wait3(a);
	await a.fill('#lmc-close-pass', 'wrong-password'); await a.click('button:has-text("Permanently close my account")'); await a.waitForLoadState();
	ok((await a.content()).includes('password wasn') && cli('user-exists', A.email), 'Wrong password: account not closed');
	await a.click('summary:has-text("Close my account")'); await wait3(a);
	await a.fill('#lmc-close-pass', A.pass); await a.click('button:has-text("Permanently close my account")'); await a.waitForLoadState();
	ok((await a.content()).includes('account has been closed') && !cli('user-exists', A.email), 'Account closed and deleted; employer signed out');
	ok((await a.goto(BASE + '/jobs/alpha-stairlift-service-engineer/')).status() === 404, 'Their vacancies are gone');
	ok(mail().includes('account has been closed'), 'Closure confirmation email sent');

	section('11. Consent and analytics');
	const c = await b.newContext({ viewport: { width: 390, height: 844 } });
	const p = await c.newPage();
	const gaReq = []; p.on('request', r => { if (r.url().includes('googletagmanager')) gaReq.push(r.url()); });
	await p.goto(BASE + '/jobs/');
	ok(await p.locator('#lmc-consent').isVisible(), 'Consent banner shown on first visit');
	ok(await p.evaluate(() => localStorage.length) === 0, 'Nothing stored on the device before a choice is made');
	ok(gaReq.length === 0, 'Google Analytics not loaded before consent');
	await p.click('[data-consent=no]'); await p.reload();
	ok(await p.evaluate(() => JSON.stringify(Object.keys(localStorage))) === '["lmc_consent"]' && gaReq.length === 0, 'Reject: only the choice itself is remembered; no analytics, no visit tracking');
	await p.click('button.lmc-footer__cookies');
	ok(await p.locator('#lmc-consent').isVisible(), 'Footer "Cookie settings" reopens the choice');
	await p.click('[data-consent=yes]'); await p.reload();
	ok(gaReq.length > 0 && await p.evaluate(() => !!localStorage.getItem('lmc_seen')), 'Accept: analytics loads and returning-visitor marker set');
	await p.goto(BASE + '/jobs/');
	const job1 = await p.locator('.lmc-job-card__title a').first().getAttribute('href');
	await p.goto(job1);
	const conv = await p.evaluate(() => {
		let n = 0; window.gtag = (a, ev) => { if (ev === 'sign_up') n++; };
		const f = document.querySelector('form.lmc-alert-box');
		f.addEventListener('submit', e => e.preventDefault());
		f.dispatchEvent(new Event('submit', { cancelable: true }));
		return { n, forms: window.lmcAlertFormCount };
	});
	ok(conv.n === 1 && conv.forms === 1, 'Job alert conversion fires exactly once per submission (duplicate listener fixed)');
	const cookieHref = await p.locator('.lmc-footer__legal a', { hasText: 'Cookie policy' }).getAttribute('href');
	ok(/\/privacy-policy\/#cookies$/.test(cookieHref), 'Footer Cookie policy link points to the cookies section of the Privacy and Cookie Policy');
	await p.goto(cookieHref);
	ok((await p.content()).includes('lmc_consent') && await p.locator('.lmc-prose table').count() >= 2, 'Cookie policy page lists every cookie and storage item in tables');
	await p.screenshot({ path: SHOTS + '/mobile-cookie-policy.png', fullPage: true });

	ok([a, bb, admin].every(x => !x.errors.length), 'No JavaScript errors');
	await b.close();
	console.log(`\n${pass} passed, ${fail} failed`);
	process.exit(fail ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
