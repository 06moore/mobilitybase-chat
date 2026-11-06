// Captures the review screenshots (desktop 1366px and phone 390px) from the local dev site.
// Usage: node tools/tests/screenshots.js <out-dir>
const { chromium } = require('playwright');
const OUT = process.argv[2] || '/tmp/lmc-review-shots';
const BASE = process.env.LMC_URL || 'http://127.0.0.1:8080';
require('fs').mkdirSync(OUT, { recursive: true });
(async () => {
	const b = await chromium.launch();
	const shot = async (ctx, path, name, opts = {}) => {
		const p = await ctx.newPage();
		await p.goto(BASE + path, { waitUntil: 'networkidle' });
		if (opts.before) await opts.before(p);
		await p.screenshot({ path: `${OUT}/${name}.png`, fullPage: opts.full !== false });
		await p.close();
	};
	const mk = async (w, h, consent = 'no') => {
		const c = await b.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: w < 500 ? 2 : 1 });
		if (consent) await c.addInitScript(v => { try { localStorage.setItem('lmc_consent', v); } catch (e) {} }, consent);
		return c;
	};
	const d = await mk(1366, 900), m = await mk(390, 844);
	const job = '/jobs/stairlift-service-engineer/';
	for (const [ctx, sfx] of [[d, 'desktop'], [m, 'mobile']]) {
		await shot(ctx, '/', `01-homepage-${sfx}`);
		await shot(ctx, '/jobs/', `02-jobs-${sfx}`);
		await shot(ctx, job, `03-vacancy-${sfx}`);
		await shot(ctx, '/employer/register/', `04-employer-registration-${sfx}`);
		await shot(ctx, '/advertise-a-vacancy/', `06-advertise-pricing-${sfx}`);
		await shot(ctx, '/job-alerts/', `07-job-alerts-${sfx}`);
		await shot(ctx, '/cookie-policy/', `08-cookie-policy-${sfx}`);
	}
	// Vacancy submission form (signed in as the demo employer).
	for (const [w, sfx] of [[1366, 'desktop'], [390, 'mobile']]) {
		const c = await mk(w, 900);
		const p = await c.newPage();
		await p.goto(BASE + '/employer/sign-in/');
		await p.fill('#user_login', 'demo-employer'); await p.fill('#user_pass', 'demo-employer-pass'); await p.click('#wp-submit'); await p.waitForLoadState();
		await shot(c, '/employer/submit-vacancy/', `05-vacancy-submission-${sfx}`);
		await shot(c, '/employer/dashboard/', `09-employer-dashboard-${sfx}`);
	}
	// Consent banner as a first-time phone visitor sees it.
	const fresh = await mk(390, 844, null);
	await shot(fresh, '/', '10-consent-banner-mobile', { full: false });
	// Admin: dashboard and launch checklist.
	const a = await mk(1400, 900);
	const ap = await a.newPage();
	await ap.goto(BASE + '/wp-login.php'); await ap.fill('#user_login', 'admin'); await ap.fill('#user_pass', 'admin-dev-password'); await ap.click('#wp-submit'); await ap.waitForLoadState();
	await shot(a, '/wp-admin/admin.php?page=lmc', '11-admin-dashboard');
	await shot(a, '/wp-admin/admin.php?page=lmc-launch', '12-admin-launch-checklist');
	await shot(a, '/wp-admin/admin.php?page=lmc-growth', '13-admin-traffic-growth');
	await b.close();
	console.log('Screenshots saved to ' + OUT);
})();
