# Lift and Mobility Careers

*Connecting mobility engineering talent with opportunity.*

A complete, automated UK specialist job board for the stairlift, home lift, platform lift, access lift, hoist and patient handling industry. Employers register, post, pay, get published, get reminded and renew without human contact; candidates search and apply free; the operator gets one dashboard and one weekly email.

> **Independence:** this folder contains no personal names, photos or other-business branding. It currently lives inside another repository only because that was the repository available in this session. **Recommended:** move it to its own private repository under a new GitHub organisation for the business (`git subtree split --prefix=lift-mobility-careers` keeps the history), so commit history and repo name aren't associated with other business interests.

## What's here

| Path | Contents |
|---|---|
| `wordpress/wp-content/plugins/lmc-core/` | The job board engine (custom WordPress plugin) |
| `wordpress/wp-content/themes/lmc/` | Fast, lightweight, responsive theme |
| `brand/` | Logo suite (SVG + PNG), favicon, social images, **brand-guidelines.html** (colour & typography guide) |
| `docs/legal/compliance-review.md` | Legal analysis (EAA 1973, Conduct Regs, GDPR, PECR, cookies, OSA…) with solicitor flags |
| `wordpress/…/lmc-core/data/legal/` | 8 draft legal documents (published automatically as site pages) |
| `docs/marketing/` | Social profiles, 12-week content plan, post templates, outreach process, target employer list |
| `docs/operations/` | Deployment guide, Stripe setup & test checklist |
| `docs/PROJECT-STATUS.md` | **Phase-by-phase record of what's done, tested and outstanding – start here** |
| `tools/` | Local dev environment, test suites, brand asset builder, zip packager |

## Technical approach (and why it differs slightly from the brief)

**WordPress (as requested) + one custom plugin + Stripe Checkout**, instead of WP Job Manager + WooCommerce + paid add-ons.

- WP Job Manager's paid-listing route needs WooCommerce **plus** the paid "WC Paid Listings" add-on (and more add-ons for things like application deadlines), and still wouldn't cover vacancy discovery, the outreach CRM, PECR safeguards, the weekly report or a single operator dashboard – those would be custom code anyway.
- One purpose-built plugin gives one dashboard, fewer plugins to update and fewer things to break, faster pages, and zero licence fees.
- **Stripe Checkout** (hosted payment page) means card data never touches the site (lowest PCI scope), with Stripe receipts, refunds and optional invoices.
- **Prices, durations, moderation, email wording and keys are all editable in WP Admin** – no rebuild needed.
- Trade-off: you depend on this codebase rather than a large plugin community. Mitigation: plain WordPress APIs, documented, covered by 113 automated tests, and any WordPress developer can maintain it.

## Feature summary

- **Candidates:** search by keyword, UK location (region/county, or town/postcode + radius via free postcodes.io), sector, job category, salary, employment type, experience level; tiered ordering (Premium → Featured → Standard); full job pages; apply direct by link/email; no account, no CVs, no cookies.
- **Employers:** registration with email confirmation, structured submission form with validation and authorisation declaration, logo upload with rights confirmation, package choice, Stripe payment, credits/packs, dashboard (status, end dates, views, apply clicks, edit, renew, close), payment history, profile.
- **Automation:** moderation of first adverts and auto-flagged content; publish; 7-day & 2-day reminders; auto-expiry; renewal; "did you fill it?"; repeat-advertising nudge; weekly report; social post drafts; automation health warnings; all idempotent.
- **Discovery:** monitored careers pages (structured data + link parsing), robots.txt, terms-review gate, relevance scoring, first/last seen, still-live tracking, failure flags, review queue → employer permission → original-summary draft.
- **CRM & outreach:** prospect database with all requested fields, entity-type PECR rules, one-at-a-time sending, consent records, suppression list, one-click unsubscribe, follow-up tracking, free listing grants, CSV import/export.
- **SEO:** titles/descriptions, canonicals, JobPosting + Organization/WebSite schema, XML sitemap, robots.txt, thin-page prevention, Open Graph, Search Console verification, consent-gated GA4.
