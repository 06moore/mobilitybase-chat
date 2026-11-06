# Data model & database schema

The plugin uses standard WordPress storage wherever possible (posts, post meta, users, user meta, options) plus 12 custom tables for high-volume or relational data. Tables are created and upgraded by `LMC_Install::tables()` (`includes/install.php`) using `dbDelta`. The schema version is `LMC_DB_VERSION` (currently **5**). `wp_` stands for the site's table prefix.

## Vacancies – custom post type `lmc_job`

| Status | Meaning |
|---|---|
| `lmc_unpaid` | Submitted, awaiting payment or credit (not public) |
| `pending` | Paid, awaiting operator approval (not public) |
| `publish` | Live |
| `lmc_expired` | Listing period ended – page viewable with a "closed" notice, noindex, no JobPosting |
| `lmc_closed` | Closed early by the employer (filled) – as expired |
| `draft` | Rejected, or created from a discovered vacancy awaiting a written summary |

**Taxonomies:** `lmc_sector`, `lmc_role` (job category), `lmc_location` (region › county, hierarchical), `lmc_type` (employment type), `lmc_experience`.
Term meta: `lmc_seo_title`, `lmc_seo_desc`, `lmc_icon`, `lmc_order`, `lmc_index` (`always` = always indexable; otherwise needs ≥3 live vacancies).

**Post meta** (all prefixed `_lmc_`):

| Key | Content |
|---|---|
| employer_name, employer_website | Shown on the advert |
| town, postcode, location_note, lat, lng | Location (lat/lng from postcodes.io for radius search) |
| salary_min, salary_max, salary_period (YEAR/HOUR/DAY), salary_note, salary_annual | Pay; `salary_annual` is the annualised maximum, used for filtering |
| responsibilities, requirements, benefits | One item per line |
| closing_date | Optional, YYYY-MM-DD |
| apply_method (url/email), apply_url, apply_email | Direct application route |
| logo_id, logo_authorised | Attachment ID + time rights were confirmed |
| authorised | Declaration record: name, user ID, time, declaration version |
| social_ok | Employer allowed social promotion |
| tier (standard/featured/premium), priority (1–3) | Advert level and search ordering |
| paid, published_at, expires, expired_at, renewals | Lifecycle timestamps (UTC unix) |
| sent_expiry_7, sent_expiry_2, sent_expired, sent_filled, sent_repeat, notified_published, renewing | Idempotency flags for automated emails |
| flags | Automated content-check warnings |
| outcome | filled / not_filled (from the follow-up email) |
| source_opportunity | Discovered-vacancy ID it came from |
| demo | Set only on local demo content – never indexed, removable in one click |

## Employers – WordPress users with role `lmc_employer` (capability: `read` only)

User meta: `lmc_company`, `lmc_company_website`, `lmc_company_slug` (public company page), `lmc_phone`, `lmc_job_title`, `lmc_entity_type`, `lmc_terms_accepted`, `lmc_marketing_opt_out`, `lmc_email_verified`, `lmc_verify_hash` (hashed token), `lmc_verify_sent`, `lmc_trusted` (has had a vacancy approved), `lmc_free_listing_granted`, `lmc_last_repeat_nudge`, `lmc_backlinks` (URL → {ok, checked}), `lmc_backlink_bonus`, `lmc_demo`.

## Custom tables

| Table | Purpose | Key columns |
|---|---|---|
| `wp_lmc_payments` | Every checkout | id, user_id, job_id, package, purpose (new/renew/credits), net, vat, total (pence), method (stripe/manual), stripe_session, stripe_intent, status (pending/paid/failed/expired/refunded/part_refunded), mode (test/live), **customer** (company + email snapshot, kept after account closure for tax records), created_at, paid_at |
| `wp_lmc_credits` | Advert credits (packs, free listings, rewards) | user_id, tier, quantity, remaining, source (purchase/free/admin/backlink), payment_id, expires_at |
| `wp_lmc_log` | Operational log; errors show on the dashboard until resolved | level, context, message, resolved |
| `wp_lmc_email_log` | Every email sent | recipient, template, subject, object_id, status |
| `wp_lmc_stats` | Daily vacancy views & Apply clicks (counts only) | day, job_id, views, clicks |
| `wp_lmc_traffic` | Daily site analytics counters (no personal data) | day, metric (pageview/visit/source/landing/referrer/share/…), dim, n |
| `wp_lmc_subscribers` | Candidate job alerts | email, sectors, roles, location, lat, lng, radius, frequency, status (pending/active), token, source, created/confirmed/last_sent |
| `wp_lmc_sources` | Monitored careers pages | company, careers_url, terms_reviewed, enabled, interval_days, status, status_note, last_checked, fail_count |
| `wp_lmc_opportunities` | Discovered vacancies (leads only) | source_id, company, title, location, url, fingerprint (unique), score, first_seen, last_seen, still_live, status, job_id |
| `wp_lmc_prospects` | Employer outreach CRM | company, entity_type, contact details, status, permission_status, consent_marketing + note, follow_up, contact_count, employer_user_id |
| `wp_lmc_suppression` | Never-email list | email (unique), reason |
| `wp_lmc_social` | Optional social post drafts | job_id, platform, body, status |

## Options
`lmc_settings` (all settings – see `includes/settings.php`), `lmc_email_templates` (template overrides), `lmc_page_{key}` (page IDs), `lmc_heartbeats` (last run of each automation), `lmc_installed_at`, `lmc_db_version`, `lmc_monthly_sent`, `lmc_live_backlinks`.

## Personal data held, and how long
| Data | Where | Retention |
|---|---|---|
| Employer account details | users / user meta | Until the employer closes the account (self-service), then deleted |
| Payment records | wp_lmc_payments | Kept (normally 6 years) for tax; contains company + email snapshot only |
| Candidate email + alert preferences | wp_lmc_subscribers | Until unsubscribe (deleted immediately); unconfirmed deleted after 7 days |
| Business contacts (CRM) | wp_lmc_prospects | Auto-deleted after 24 months with no relationship |
| Suppression list | wp_lmc_suppression | Indefinitely (email only, to honour opt-outs) |
| Email log | wp_lmc_email_log | Recipient + subject; deleted automatically after 2 years |
| IP addresses | **Not stored.** Rate limits use a salted hash held ≤1 hour. (The web host's own access logs are outside the plugin.) | – |
