# Response to independent review – changes, outstanding issues, tests

**Test totals (each suite on a freshly built site, `bash tools/tests/run-all.sh`):** integration **226/226**, browser (basic) **26/26**, browser (full employer journey) **50/50**, PHP warnings/notices **0**. Full output: `tools/tests/results/`.

**A note on Priority 1.** The previous ZIP did include `REVIEW-GUIDE.md`, all 58 PHP files, the legal drafts and 13 screenshots (in nested folders), so the reviewing tool probably didn't unpack them fully. This pack adds a single flat `SOURCE-CODE.md` containing every custom code file, so nothing depends on unpacking folders.

---

## Business identity: sole trader now, limited company later (one setting)
- New `includes/identity.php` builds the operator wording from **Settings → Business details → Business structure**. It feeds the legal pages (`[lmc_business]` shortcode), site footer, email footers, the outreach sender check and the launch checklist.
- The legal pages were merged from 8 to 3, with no DRAFT notices. Old URLs redirect with a 301. A v6 upgrade removes the old pages and refreshes the content.
- Tests (integration): sole-trader wording, the switch to limited (missing number and office block launch), Ltd wording with no personal name, footer and email details, switching back, old-URL redirects.

## Launch blockers (must be cleared before going live)
These are enforced in software: the site stays in pre-launch mode until they're done. See *Lift & Mobility → Launch checklist*.

1. **Specialist review of the employment-agency position** (EAA 1973 / Conduct Regulations 2003) and all legal pages. Record the reviewer's name and date in the Launch checklist. *(Priority 8)*
2. **Fill in Settings → Business details** so no `[NOT SET]` marker is left in the three legal pages (see `docs/legal/compliance-review.md`). *(Priority 8)*
3. **Remove demo content** – one button on the Launch checklist. *(Priority 3)*
4. **Business details** – sole trader: owner name and trading address; limited company: company number and registered office. Both need the contact and admin emails and the ICO number.
5. **Real domain over HTTPS.**
6. **Email delivery** – SMTP account with SPF/DKIM.

Recommended but not blocking: Stripe live keys (you could launch with free listings only); server cron confirmed running.

---

## Completed changes, by priority

### Priority 1 – Complete audit package ✅
- New `REVIEW-GUIDE.md` with reading order and full contents; `SOURCE-CODE.md` (all plugin and theme PHP, CSS, JS, dev tools and tests in one file); `docs/DB-SCHEMA.md` (all tables, meta keys, options, personal-data retention); all 8 legal drafts; `docs/SECURITY-REVIEW.md`; test output.
- Screenshots (desktop + phone): homepage, jobs, vacancy, employer registration, vacancy submission, pricing, job alerts, cookie policy, employer dashboard; phone consent banner; payment step; checkout; admin dashboard; launch checklist; growth page.

### Priority 2 – Security ✅ (reviewed and tested – not claimed "secure"; see residual risks)
**Found and fixed:**
- **Stored values could break out of the page `<title>` and the JSON-LD block.** Not exploitable through the employer form, which strips tags on save, but fixed for defence in depth: `<title>` now escaped; JSON-LD uses `JSON_HEX_TAG`/`JSON_HEX_AMP`.
- **The employer role had `upload_files`**, which would have allowed uploads through the WordPress REST media API. Removed – employers now have only `read`. Logos go through our validated handler.
- **No login throttling** – added (5 failures per IP+username, 20 per IP, per 15 minutes).
- **No registration rate limit** – added (5 per connection per hour).
- **Username enumeration** via author archives and the REST users list (usernames come from email addresses) – blocked.
- **Missing security headers** – added nosniff, frame protection, Referrer-Policy (stops job-alert tokens leaking via referrer) and Permissions-Policy.

**Verified by tests:** nonces, capabilities, sanitisation/escaping (hostile input in every search parameter and every employer field), prepared queries (SQL payloads), password hashing, account isolation (9 tampering attempts by a second employer), upload restrictions (PHP disguised as PNG, SVG, >1MB, missing rights confirmation), webhook signatures (forged, stale, wrong amount), duplicate payments/publishing, rate limits and spam traps, signed/unguessable links. Details: `docs/SECURITY-REVIEW.md`.

### Priority 3 – Demo content ✅
- Demo vacancies and employers are flagged. They are **never** indexed (forced noindex), never in the sitemap, the aggregator feed, job alerts or the company sitemap, carry no JobPosting data and never trigger search-engine notifications – even in live mode.
- **"Remove all demo content"** button; its presence is a launch blocker. The demo generator is excluded from release zips.
- Expired **and filled (closed)** vacancies: removed from listings, sitemap and feed; JobPosting removed; page shows a "closed" notice with noindex; Google Indexing API told the URL is gone (when connected). Refunded and unpaid vacancies are now notified too.

### Priority 4 – SEO ✅ (on the local install – see outstanding)
- **Canonicals:** a test fetches **every URL in the sitemap** and checks each has an absolute, self-referencing canonical on the site's own domain and isn't noindex. They're generated from the WordPress Site Address, so they follow the real domain automatically once set. The launch checklist blocks non-HTTPS/localhost addresses.
- **Thin/duplicate pages:** indexed by default are 6 distinct sector pages (stairlifts, home lifts, through-floor, platform lifts, ceiling track hoists, patient handling) and 5 job categories (installation, service, LOLER, trainee, apprentice), each with substantial original copy (copy for service, trainee and apprentice expanded). Overlapping or thinner pages (access lifts, wheelchair lifts, general mobility equipment, management and support roles) are noindex and out of the sitemap **until they have 3 genuine live vacancies**. Then they index automatically.
- **Priorities:** career guides (5) and genuine vacancy pages stay indexable.
- **JobPosting:** validated in tests against Google's required and recommended fields: ISO dates, validThrough after datePosted, permitted employmentType values, GBP salary with unit, GB address, HTML description, values matching the vacancy, valid JSON. Also fixed: plain-text descriptions now get proper HTML paragraphs.

### Priority 5 – Analytics & consent ✅
- **Duplicate job-alert conversion listener – confirmed and fixed.** The alert box matched both selectors, so GA would have recorded two sign-ups. A browser test now proves exactly one event per submission.
- **Server-side analytics:** stores daily totals only (no IP, no identifiers, no cookies). Rate limits use a salted IP hash held for ≤1 hour. Documented in `DB-SCHEMA.md` and the cookie policy.
- **Consent:** browser tests prove that before any choice nothing is stored and Google Analytics doesn't load; Reject stores only the choice itself; Accept loads analytics and sets the returning-visitor marker; footer "Cookie settings" reopens the choice.
- **Cookie Policy link:** in the published preview it pointed to the homepage because that static preview hadn't captured the cookie page. On the real site the link is correct (now tested). The preview is regenerated with all pages.
- **Cookie Policy rewritten** with tables of every cookie and storage item, analytics without cookies, third parties and how to change your choice.
- **Fixed formatting bug in legal pages:** any link following a [PLACEHOLDER] in the same paragraph was garbled (e.g. Cookie Policy → Privacy Policy). All legal-page links now resolve, and a test covers this.

### Priority 6 – Employer experience ✅ (tested end to end in a browser)
New in this round: **failed payment** handling (email, nothing published), **abandoned checkout** handling (marked expired, clear message), **automatic refund reversal** (new advert taken down; renewal days removed; unused credits cancelled; employer emailed; partial refunds recorded for review only), **double payment protection** (second payment becomes a credit, employer told) and **self-service account closure** (password-confirmed; removes adverts, account and CRM data; keeps payment records with a customer snapshot for tax).

Browser-tested journey: register → verify → authorisation required → upload checks → package → declined card → abandoned checkout → pay → admin approval → published → edit → 7-day reminder → renew (+30 days from end date) → refund of renewal → second employer's 9 access attempts → double payment → close (filled) → wrong-password closure refused → account closed → vacancies gone.

### Priority 7 – Low maintenance ✅
- **Removed every package promise that needed manual work.** Featured and Premium previously promised social media posts. Now all benefits are automatic: highlighted / listed first in job alert emails, ranking, homepage section.
- Social post drafts are **off by default** (Settings → Automation), so nothing appears that needs posting.
- Standard adverts: one-off 30-day payments. Renewal is always the employer's choice, never automatic.
- Automated: expiry, 7/2-day reminders, receipts (Stripe + our confirmation), failed-payment and refund emails, stale checkout clean-up, weekly and monthly reports.
- Discovered vacancies are **never** published automatically: they become drafts only after you confirm you hold written authorisation.
- Remaining human touch-points (all optional or minimal): approving each new employer's **first** advert (switchable to automatic in Settings), and the optional outreach CRM.

### Priority 8 – Legal ✅ (process) / ⛔ (approval outstanding)
- Compliance review updated: the EAA/Conduct Regulations issue is a formal **launch blocker** with a question list for the specialist. It covers the advert disclosure wording, which regulations apply, the possible move of EAS enforcement to the Fair Work Agency under the Employment Rights Act 2025, Northern Ireland, the Online Safety Act, PECR/UK GDPR and job alerts.
- Placeholders identified page by page (table in the compliance review; live list in the Launch checklist).
- Privacy ↔ Cookie policy references fixed and tested.

---

## Outstanding issues (prioritised)
1. ⛔ Specialist legal sign-off (blocker).
2. ⛔ Fill in business details so the legal pages have no [NOT SET] markers (blocker).
3. ⛔ Business entity, addresses, emails; domain + HTTPS; SMTP (blockers – need your accounts and decisions).
4. Real Stripe test-mode run with your account (`docs/operations/stripe-setup.md`). Only the fake Stripe has been tested so far.
5. Verify on the live domain: canonicals, Google Rich Results Test for one vacancy, Search Console coverage, security headers scan (Mozilla Observatory), WPScan.
6. Check real keyword volumes (Keyword Planner export – seed list in `docs/growth/keyword-seed-list.txt`).
7. Enable 2FA on the admin account; confirm the host blocks PHP execution in uploads.
8. Not tested here (external services unreachable): live email deliverability, postcodes.io geocoding, Google Indexing/Search Console APIs, IndexNow.

## Tests performed
| Suite | Checks | Covers |
|---|---|---|
| `tools/tests/run.php` | 226 | Launch control, settings, payments/webhooks, duplicates/failures/refunds, credits, lifecycle (time travel), search, content checks, SEO (canonicals for every sitemap URL, JobPosting validation, index policy), discovery, CRM/PECR, reports, analytics, job alerts, sharing/backlinks, feed, Google JWT, security (roles, hashing, enumeration, headers, CSRF, honeypot, rate limits, login lockout, SQL/XSS payloads, signed links) |
| `tools/tests/e2e.js` | 26 | Candidate search/apply, registration, validation, free-credit flow, approval, admin screens, mobile layout |
| `tools/tests/e2e-employer.js` | 50 | Full paid employer journey with signed webhooks, uploads, access isolation, double payment, closure, consent/analytics |
