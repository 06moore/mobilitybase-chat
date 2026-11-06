# Project status – Lift and Mobility Careers

Updated: 6 Nov 2026. ✅ = built **and tested** · 🟡 = built, can only be fully tested once you connect an external account · ⬜ = needs you (or a professional).

**Test results (local WordPress 7.1.4 + PHP 8.3):** `php tools/tests/run.php` → **87/87 pass** · `node tools/tests/e2e.js` → **26/26 pass** (real browser: candidate search, registration, email confirmation, validation, submission, free credit, approval, live listing, all admin screens, mobile layout, no JS errors).

Tested locally with an SQLite database and captured (not sent) emails. Live hosting, real email delivery, real Stripe calls and the postcodes.io lookup **could not be exercised from this environment** (no accounts / outbound network blocked) – those are marked 🟡.

---

## Phase 1 – Brand and business foundation
- ✅ Business positioning, voice and principles (brand guidelines)
- ✅ Main logo, reversed logo, stacked alternative, single-colour, symbol – outlined SVG + PNG (`brand/`)
- ✅ Favicon (SVG, 32px, Apple touch, 512px), social profile image, LinkedIn & Facebook covers, OG share image
- ✅ Colour guide (with accessible contrast rules) and typography guide – `brand/brand-guidelines.html`
- ⬜ Check domain availability and **UKIPO trade mark search** for the name before buying anything
- ⬜ Decide legal entity (recommended: new limited company, independent of other interests)

## Phase 2 – Website and design
- ✅ Responsive theme: homepage (exact headline, search, sectors, latest, premium, employer section, CTAs), browse/search, sector/role/location pages, job page, all content pages, 404
- ✅ Navigation as specified (+ employer sign-in), mobile menu; no horizontal scroll at 390px
- ✅ Performance: no jQuery/page builder, one CSS + one small JS file, self-hosted fonts, core bloat removed
- ⬜ Real photography (optional, later – only with permission)

## Phase 3 – Job board functionality
- ✅ Vacancy type with all required fields; sectors (9), job categories (13), employment types, experience levels, 12 UK regions/80 counties
- ✅ Filters: location, keyword/title, sector, salary, employment type, experience; sorting; tier priority
- ✅ Region→county search; postcode/town radius search (distance maths tested)
- 🟡 Live geocoding via postcodes.io (free, no key) – works once hosted; falls back to town-name match

## Phase 4 – Employer accounts and payments
- ✅ Registration, email confirmation, sign-in, dashboard, edit, renew, close, profile, payment history
- ✅ Five packages at your prices, editable in Settings; packs as 12-month credits; free introductory listing credits
- ✅ Stripe Checkout + signed webhook: signature check, replay protection, amount check, idempotent fulfilment, refunds tracked, VAT option, manual (bank transfer) payments
- 🟡 Real Stripe test-mode checkout – **needs your Stripe account** (`docs/operations/stripe-setup.md`). Payments are OFF by default.

## Phase 5 – Automated emails and renewals
- ✅ All 10 employer templates + admin, contact, 3 outreach templates; editable in admin with test-send
- ✅ Hourly lifecycle: 7/2-day reminders, expiry (removed from search), "filled?" (one-click answer links), repeat nudge (once per employer, respects opt-out), renewal extends from end date
- ✅ Weekly report: active, new, expired, employers total/new, revenue, upcoming renewals, traffic (vacancy views/apply clicks), popular sectors, action list
- 🟡 Real delivery – needs SMTP account + DNS (SPF/DKIM/DMARC)

## Phase 6 – Vacancy discovery and monitoring
- ✅ Sources, terms-review gate, robots.txt, structured-data + link parsing, relevance scoring, first/last seen, still-live, failure/JS-only flags, review queue, permission-first drafts, employer contact template
- ✅ Starter list of 25 sector companies – **all unverified** (names only; no websites/emails were guessed)
- ⬜ Verify each company on Companies House, add careers URLs and published contacts, review site terms

## Phase 7 – SEO and social media
- ✅ Meta titles/descriptions, canonicals, JobPosting schema (closed jobs excluded), sitemap, robots.txt, noindex rules for thin/filtered pages, sector landing copy + 3 curated landing pages (stairlift service, stairlift installation, Kent)
- ✅ LinkedIn/Facebook profile copy, 12-week content plan, 8 post templates, automatic vacancy post drafts, optional Zapier/Make/Buffer webhook
- ⬜ Create the LinkedIn and Facebook pages (business accounts) and upload the brand images
- 🟡 Search Console verification + sitemap submission (needs live domain)

## Phase 8 – Legal and compliance
- ✅ Compliance review incl. **finding that the board is likely an "employment agency" under the EAA 1973** (publication exclusion probably doesn't apply) and the resulting design choices
- ✅ Drafts: T&Cs, privacy, cookies, advertising agreement, refunds, authorisation declaration, content licence, disclaimer (live as pages)
- ✅ Built-in safeguards: no candidate fees, reg 27 disclosure line, mandatory salary/location/experience, PECR outreach blocks, suppression, consent-gated analytics, content flags, report-an-advert, data retention
- ⬜ **Solicitor review** of all drafts and every [SOLICITOR] item (EAA/Conduct Regs scope, liability cap, Online Safety Act, website terms & discovery)
- ⬜ ICO registration; LIA and processing record (templates provided); insurance

## Phase 9 – Testing and launch
- ✅ Automated integration and browser test suites (113 checks)
- ⬜ Launch checklist below

### Launch checklist
1. ⬜ Approve spending: domain, hosting, SMTP, (mailbox)
2. ⬜ Deploy (docs/operations/deployment.md), set cron, complete Settings → Business details
3. ⬜ Email: SMTP + DNS records; send test of each template
4. ⬜ Stripe test checklist → live mode → one real £39 purchase and refund
5. ⬜ Replace all [BRACKETED] items in legal pages after solicitor review
6. ⬜ Search Console + sitemap; untick "Discourage search engines"
7. ⬜ Outreach: verify first 20 employers; send first 5–10 free listing offers
8. ⬜ Social pages live; first launch post
9. ⬜ Monitor first week: dashboard daily, then weekly report only

---

## What I need from you

| Needed | Why | Where it goes |
|---|---|---|
| Approval to buy domain + hosting (≈£25–50/month total) | Go live | deployment.md §1 |
| Legal entity details (name, number, registered office, postal address, VAT status) | Footer, emails, legal pages, PECR | Settings → Business details |
| Stripe account (business) – test keys + webhook secret, later live keys | Payments | wp-config.php |
| SMTP provider credentials + DNS access | Email delivery | Settings → Email + wp-config |
| Admin/report email address (a business mailbox, not personal) | Weekly report, approvals | Settings → Business details |
| Solicitor | Approve legal drafts | docs/legal |
| Optional: GA4 ID, Search Console code, Zapier/Make webhook | Analytics, social automation | Settings |

## Known limitations / decisions to revisit
- Careers pages that load jobs with JavaScript or through an ATS (Workday, Greenhouse, etc.) are **flagged, not read** – check these manually or ask the employer for a feed.
- "Traffic" in the weekly report is vacancy views/apply clicks counted on-site; full visitor analytics needs GA4 (with consent).
- Candidate job alerts by email were not built (would require collecting candidate emails – deliberately excluded at launch).
- The site makes no audience/size claims; add them only when true and evidenced.
