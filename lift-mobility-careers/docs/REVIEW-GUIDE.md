# Review guide – Lift and Mobility Careers

A UK specialist job board for stairlift, home lift, platform lift and hoist engineers. WordPress + one custom plugin (`lmc-core`) + one custom theme (`lmc`). **Nothing is live.** The site is built to stay in pre-launch mode until a specialist has reviewed the legal position.

## If you can only read three things
1. `CHANGES-AND-STATUS.md` – prioritised list of completed changes, outstanding issues and tests performed (responding to the last review).
2. `SOURCE-CODE.md` – **every custom PHP, CSS and JavaScript file in one document**, with file paths as headings.
3. `docs/SECURITY-REVIEW.md` – the 12 security areas, how each is implemented and which test proves it.

## Contents of this pack
| Path | What |
|---|---|
| `SOURCE-CODE.md` | All custom code concatenated (plugin, theme, dev tools, tests) |
| `wordpress/wp-content/plugins/lmc-core/` | Plugin source: `lmc-core.php`, `includes/*.php` (business logic), `includes/admin/*.php` (admin screens), `data/` (seed content, legal drafts), `assets/admin.css` |
| `wordpress/wp-content/themes/lmc/` | Theme PHP templates, `assets/css/main.css`, `assets/js/main.js`, fonts, images |
| `wordpress/wp-content/plugins/lmc-core/data/legal/*.md` | The 8 legal drafts exactly as published on the site |
| `docs/legal/compliance-review.md` | Legal & compliance analysis, **launch blocker**, placeholder list |
| `docs/DB-SCHEMA.md` | Database tables, post/user meta, options, personal-data retention |
| `docs/SECURITY-REVIEW.md` | Security review |
| `docs/growth/` | SEO strategy, 12-month growth plan, Google/Bing setup |
| `docs/operations/` | Deployment and Stripe setup guides |
| `docs/PROJECT-STATUS.md` | Phase-by-phase build record |
| `screenshots/` | Homepage, jobs, vacancy, registration, vacancy submission, pricing, job alerts, cookie policy, employer dashboard (desktop + mobile); consent banner; payment step; fake checkout; admin dashboard, launch checklist, growth page |
| `tools/tests/results/` | Full output of the last test run (integration, browser, PHP warnings) |
| `tools/tests/` | Test suites: `run.php` (integration), `e2e.js`, `e2e-employer.js` (browser), `dev-cli.php`, `screenshots.js`, `run-all.sh` |
| `tools/dev/` | Local dev site builder (WordPress + SQLite) and the DEV-ONLY fake Stripe used in tests |
| `brand/` | Logo suite, social images, brand guidelines |

## How it was tested
On a fresh local WordPress 7.1.4 / PHP 8.3 install (SQLite), rebuilt from scratch before each suite: `bash tools/tests/run-all.sh`. Real Stripe, real email delivery, Google APIs and postcodes.io could not be reached from the build environment. Payments were tested end to end with a fake Stripe that sends correctly signed webhooks through the real endpoint, and emails were captured to a file instead of sent.
