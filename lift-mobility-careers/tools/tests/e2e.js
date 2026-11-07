// Browser end-to-end test of the employer journey on the local dev site.
// Run: node tools/tests/e2e.js   (dev site at http://127.0.0.1:8080, fresh from setup-local.sh)
const { chromium } = require('playwright');
const fs = require('fs');
const BASE = process.env.LMC_URL || 'http://127.0.0.1:8080';
const MAIL = (process.env.LMC_DEV_DIR || '/tmp/lmc-dev') + '/mail.log';
const SHOTS = process.env.LMC_SHOTS || '/tmp/lmc-shots';
fs.mkdirSync(SHOTS, { recursive: true });
let pass = 0, fail = 0;
const ok = (c, n) => { c ? pass++ : fail++; console.log(`  ${c ? '✓' : '✗ FAIL:'} ${n}`); };
const email = `e2e${Date.now()}@example.test`;

(async () => {
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  const errors = [];
  p.on('pageerror', e => errors.push(e.message));

  console.log('Candidate');
  await p.goto(BASE + '/');
  ok(await p.locator('h1', { hasText: 'Find your next engineering opportunity.' }).count() === 1, 'Homepage headline');
  await p.fill('#lmc-loc-hero', 'Kent');
  await p.click('.lmc-search--hero button[type=submit]');
  await p.waitForLoadState();
  ok(p.url().includes('location=Kent') && await p.locator('.lmc-job-card').count() >= 1, 'Location search from homepage returns Kent jobs');
  await p.locator('.lmc-job-card__title a').first().click();
  await p.waitForLoadState();
  ok(await p.locator('a[data-lmc-apply]').count() === 1, 'Job page has an Apply button (no account needed)');
  await p.screenshot({ path: SHOTS + '/candidate-job.png', fullPage: true });

  console.log('Employer registration');
  await p.goto(BASE + '/employer/register/');
  await p.waitForTimeout(3100); // anti-bot minimum fill time
  await p.fill('#lmc-first_name', 'Erin'); await p.fill('#lmc-last_name', 'Tester');
  await p.fill('#lmc-company', 'E2E Platform Lifts Ltd'); await p.selectOption('#lmc-entity_type', 'limited_company');
  await p.fill('#lmc-email', email); await p.fill('#lmc-password', 'a-strong-password-123');
  await p.click('button[type=submit]');
  await p.waitForLoadState();
  ok(await p.getByText('Please confirm you accept the terms').count() === 1, 'Registration refused until terms accepted');
  await p.waitForTimeout(3100);
  await p.check('input[name=agree]');
  await p.fill('#lmc-password', 'a-strong-password-123');
  await p.click('button[type=submit]');
  await p.waitForLoadState();
  ok(p.url().includes('/employer/dashboard/'), 'Account created and signed in');
  ok(await p.getByText('Please confirm your email address').count() >= 1, 'Posting blocked until email confirmed');
  const mail = fs.readFileSync(MAIL, 'utf8');
  const m = mail.match(/https?:\/\/[^\s\]]+lmc_verify=\d+&(?:amp;)?k=[A-Za-z0-9]+/g);
  ok(!!m && mail.includes('TO: ' + email), 'Registration confirmation email sent');
  await p.goto(m[m.length - 1].replace(/&amp;/g, '&'));
  ok(await p.getByText('your email address is confirmed').count() >= 1, 'Email confirmation link works');

  console.log('Vacancy submission');
  await p.goto(BASE + '/employer/submit-vacancy/');
  await p.waitForTimeout(3100);
  await p.click('button[type=submit]');
  await p.waitForLoadState();
  ok(await p.locator('.lmc-field-error').count() >= 8, 'Required fields validated (' + await p.locator('.lmc-field-error').count() + ' errors)');
  await p.waitForTimeout(3100);
  await p.fill('#lmc-job_title', 'Platform Lift Service Engineer');
  await p.check('input[name="sectors[]"][value="platform-lifts"]');
  await p.selectOption('#lmc-role', 'service-engineer'); await p.selectOption('#lmc-emp_type', 'full-time'); await p.selectOption('#lmc-experience', 'experienced');
  await p.fill('#lmc-description', 'We maintain platform lifts and access lifts in schools, offices and public buildings across Kent and Sussex. You will join a supportive team of six engineers, with a modern van, tablet and manufacturer training on all the equipment we look after.');
  await p.fill('#lmc-responsibilities', 'Planned maintenance\nBreakdown response\nService reports');
  await p.fill('#lmc-requirements', 'Lift or electromechanical experience\nFull UK driving licence');
  await p.selectOption('#lmc-county', 'kent'); await p.fill('#lmc-town', 'Ashford'); await p.fill('#lmc-postcode', 'TN23');
  await p.fill('#lmc-salary_min', '34000'); await p.fill('#lmc-salary_max', '39000');
  await p.fill('#lmc-apply_url', 'https://example.com/careers/platform');
  await p.check('input[name=authorised]');
  await p.screenshot({ path: SHOTS + '/submit-form.png', fullPage: true });
  await p.click('button[type=submit]');
  await p.waitForLoadState();
  ok(p.url().includes('step=package'), 'Valid vacancy saved → advert options step');
  ok(await p.getByText('Online card payment is not available just yet').count() === 1, 'Payments switched off: clear message, no charge possible');
  await p.screenshot({ path: SHOTS + '/package-step.png', fullPage: true });
  const jobId = new URL(p.url()).searchParams.get('job');

  console.log('Operator grants free listing; employer uses credit');
  const admin = await ctx.browser().newContext({ viewport: { width: 1400, height: 900 } });
  const a = await admin.newPage();
  await a.goto(BASE + '/wp-login.php');
  await a.fill('#user_login', 'admin'); await a.fill('#user_pass', 'admin-dev-password'); await a.click('#wp-submit');
  await a.waitForLoadState();
  await a.goto(BASE + '/wp-admin/admin.php?page=lmc-employers');
  await a.locator('tr', { hasText: email }).locator('a', { hasText: 'Free listing' }).click();
  await a.waitForLoadState();
  ok(await a.getByText('Free listing credit granted').count() === 1, 'Admin grants free 30-day listing (Featured by default)');
  await p.goto(BASE + '/employer/submit-vacancy/?step=package&job=' + jobId);
  await p.waitForTimeout(3100);
  await p.click('text=Use 1 featured credit');
  await p.waitForLoadState();
  ok(await p.getByText('quick check').count() >= 1, 'Credit used → vacancy sent for approval (first vacancy)');
  await p.screenshot({ path: SHOTS + '/employer-dashboard.png', fullPage: true });

  console.log('Operator approves from the dashboard');
  await a.goto(BASE + '/wp-admin/admin.php?page=lmc');
  await a.screenshot({ path: SHOTS + '/admin-dashboard.png', fullPage: true });
  await a.locator('tr', { hasText: 'Platform Lift Service Engineer' }).locator('a', { hasText: 'Approve' }).click();
  await a.waitForLoadState();
  ok(await a.getByText('Vacancy approved and published').count() === 1, 'One-click approval');
  await p.goto(BASE + '/jobs/?location=Ashford&radius=10'); // town search: geocoded via postcodes.io when reachable, else town-name match
  ok(await p.locator('.lmc-job-card', { hasText: 'Platform Lift Service Engineer' }).count() === 1, 'New vacancy live and found by location search');
  await p.goto(BASE + '/employer/dashboard/');
  ok(await p.locator('.lmc-badge--live').count() >= 1, 'Employer dashboard shows the vacancy as Live with end date');
  ok(fs.readFileSync(MAIL, 'utf8').includes('Your vacancy is now live: Platform Lift Service Engineer'), '"Now live" email sent to employer');
  for (const pg of ['lmc-discovery', 'lmc-crm', 'lmc-payments', 'lmc-social', 'lmc-settings', 'lmc-logs']) {
    const r = await a.goto(BASE + '/wp-admin/admin.php?page=' + pg);
    ok(r.status() === 200 && await a.locator('.lmc-admin').count() === 1, 'Admin screen loads: ' + pg);
  }
  await a.goto(BASE + '/wp-admin/admin.php?page=lmc-crm');
  await a.screenshot({ path: SHOTS + '/admin-crm.png', fullPage: true });

  console.log('Mobile');
  const mob = await b.newPage({ viewport: { width: 390, height: 844 } });
  await mob.goto(BASE + '/');
  const sw = await mob.evaluate(() => document.documentElement.scrollWidth);
  ok(sw <= 390, 'No horizontal scroll on a phone (' + sw + 'px)');
  await mob.click('.lmc-nav-toggle');
  ok(await mob.locator('#lmc-nav.is-open').count() === 1, 'Mobile menu opens');
  await mob.screenshot({ path: SHOTS + '/mobile-menu.png' });

  ok(errors.length === 0, 'No JavaScript errors' + (errors.length ? ': ' + errors.join('; ') : ''));
  await b.close();
  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
})();
