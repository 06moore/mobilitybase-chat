# Deployment guide

Nothing here has been purchased or published. Each step that costs money or goes public needs your go-ahead.

## 1. Accounts you need (in your name as a sole trader for Phase 1; move them to the Ltd at Phase 2)

| Item | Suggested | Approx. cost* |
|---|---|---|
| Domain | liftandmobilitycareers.co.uk (+ .com if available) – check availability first | £10–25 / year |
| Hosting | UK/EU managed WordPress with SSH, real cron, daily backups, free SSL (e.g. Krystal, SiteGround, 20i, Hostinger Business) | £10–25 / month |
| Business mailbox | Google Workspace or Microsoft 365 (1 user), or host email | £0–6 / month |
| Transactional email (SMTP) | Brevo (free tier 300/day), Postmark or Mailgun – job alerts grow volume; upgrade as subscribers grow | £0–20 / month |
| Payments | Stripe account for the business | no monthly fee; per-transaction fees |
| Analytics (optional) | Google Search Console (free), GA4 (free, consent banner auto-shown) | £0 |
| Social automation (optional) | Zapier / Make / Buffer free tiers | £0 |
| Security & backups | Host backups + Wordfence (free) + UpdraftPlus (free) to off-site storage | £0–5 / month |

*Indicative only – check current prices. Typical total: **£25–50/month**, inside the £50–75 target.

Server requirements: PHP 8.1+ (8.3 tested), MySQL 8 / MariaDB 10.6+, HTTPS, ability to add a cron job.

## 0. Pre-launch mode

The site installs in **pre-launch mode**: hidden from search engines, no sitemap or job feed, and Stripe locked to test mode. Work through *Lift & Mobility → Launch checklist*. "Go live" only unlocks once the legal sign-off is recorded and every other blocker is cleared.

## 2. Install

1. Install WordPress on the host (one-click is fine). Use a strong admin password and an admin username that isn't "admin".
2. Settings → General: Site title *Lift and Mobility Careers*, tagline, timezone **London**. Settings → Reading: make sure "Discourage search engines" is **unticked** at launch (ticked while you're still building).
3. Settings → Permalinks: **Post name**.
4. Build the zips: `bash tools/build-zips.sh` → upload `dist/lmc-theme.zip` (Appearance → Themes → Add New → Upload) and `dist/lmc-core.zip` (Plugins → Add New → Upload). Activate both. Activation creates all pages, sectors, locations, job categories, database tables and schedules.
5. Delete the default "Hello world" post and "Sample Page".

## 3. wp-config.php additions (keeps secrets out of the database)

```php
define( 'DISABLE_WP_CRON', true );                 // use the server cron below instead
define( 'LMC_SMTP_PASS', '…' );                    // SMTP password / API key
define( 'LMC_STRIPE_TEST_SECRET', 'sk_test_…' );
define( 'LMC_STRIPE_TEST_WEBHOOK', 'whsec_…' );
define( 'LMC_STRIPE_LIVE_SECRET', 'rk_live_…' );   // a restricted key is fine – see stripe-setup.md
define( 'LMC_STRIPE_LIVE_WEBHOOK', 'whsec_…' );
define( 'DISALLOW_FILE_EDIT', true );
```

## 4. Cron (automation heartbeat)

Add in the hosting control panel (every 15 minutes):

```
*/15 * * * * curl -s https://liftandmobilitycareers.co.uk/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

The dashboard shows a red warning if expiry/reminders haven't run for 2 hours, discovery for 2 days, or the weekly report for 8 days.

## 5. Email delivery

1. Create the SMTP account (e.g. Brevo) and authenticate the domain: add the **SPF**, **DKIM** and a **DMARC** record (`v=DMARC1; p=none; rua=mailto:you@…`) at your DNS provider.
2. Lift & Mobility → Settings → Email delivery: host, port 587, TLS, username; password in wp-config.
3. From address: e.g. `hello@liftandmobilitycareers.co.uk`. Reply-to: your business mailbox.
4. Settings → Email templates → "Send me a test" for a few templates. Check they land in the inbox, not spam.

## 6. Business details

Lift & Mobility → Settings → Business details. These values fill the three legal pages, the footer, every email footer and the outreach sender details, so nothing is typed into the pages by hand.

| Field | Phase 1 value |
|---|---|
| Business structure | Sole trader |
| Owner's full legal name | your full name |
| Trading / correspondence address | Office 1, Izabella House, 24-26 Regent Place, Birmingham, B1 3NJ |
| Contact email | your Hotmail address for now; switch to hello@liftandmobilitycareers.co.uk once the domain mailbox exists |
| Admin / report email | your own inbox |
| ICO registration number | as soon as it's issued; until then the privacy policy shows [NOT SET] |
| Hosting provider / email provider | Krystal Hosting Ltd / Brevo (Sendinblue SAS) |
| Legal pages last updated | the date you publish them |

**Phase 2:** change *Business structure* to **Limited company** and add the company number and registered office. The company name is already filled in as Lift & Mobility Careers Ltd. The pages, footer and emails update immediately.

**Brevo (SMTP), Settings → Email delivery:** host `smtp-relay.brevo.com`, port 587, TLS, and the username is the SMTP login shown in Brevo → *SMTP & API*. Put the SMTP key in wp-config.php as `LMC_SMTP_PASS`, never in the database. In Brevo → *Senders, domains* authenticate the domain and add the DNS records Brevo gives you (DKIM, Brevo code, DMARC). The free tier allows 300 emails a day.

## 7. Payments

Follow `stripe-setup.md`. Payments stay off until you complete it.

## 8. Search engines

1. Google Search Console → add property → HTML tag method → paste the code in Settings → SEO. Submit `https://…/wp-sitemap.xml`.
2. Optional: Bing Webmaster Tools (can import from GSC).
3. Optional: GA4 ID in Settings → SEO (consent banner turns on automatically).

## 9. Security & maintenance

- Enable automatic minor core updates (default) and plugin auto-updates for Wordfence/UpdraftPlus.
- Daily backups kept ≥14 days, one copy off-site.
- Use 2FA on the admin account (Wordfence provides it).
- Updating this plugin/theme: rebuild the zips and upload the new version ("Replace current with uploaded").

## 10. Local development

```
bash tools/dev/setup-local.sh                    # WordPress + SQLite, demo data
php -S 127.0.0.1:8080 -t /tmp/lmc-dev/wp tools/dev/router.php
php tools/tests/run.php                          # 146 integration tests
node tools/tests/e2e.js                          # 26 browser tests
```
Emails in dev are written to `/tmp/lmc-dev/mail.log`, never sent.


## 11. Automatic deployment from GitHub (Krystal)

`.github/workflows/deploy.yml` runs every test suite on each push. If **every** check passes with no PHP warnings, and you approve the run, it uploads the plugin and theme to Krystal over SSH. It never touches WordPress core, uploads, `wp-config.php` or the database.

One-off setup:
1. **Krystal cPanel → SSH Access:** enable SSH and note the host and port (Krystal uses **722**).
2. **On your own computer:** `ssh-keygen -t ed25519 -C "github-deploy" -f lmc_deploy` (no passphrase). In cPanel → SSH Access → *Import key*, paste the contents of `lmc_deploy.pub`, then *Authorize* it.
3. **On your own computer:** `ssh-keyscan -p 722 <your-server-host>`. Check that the fingerprint matches the one in cPanel.
4. **GitHub → repository → Settings → Secrets and variables → Actions:**
   - Secrets:
     - `KRYSTAL_HOST`
     - `KRYSTAL_PORT` (722)
     - `KRYSTAL_USER` (cPanel username)
     - `KRYSTAL_SSH_KEY` (the whole contents of the private `lmc_deploy` file)
     - `KRYSTAL_KNOWN_HOSTS` (the ssh-keyscan output)
     - `KRYSTAL_WP_PATH` (e.g. `/home/<cpanel-user>/public_html`)
   - Variable: `SITE_URL`.
5. **GitHub → Settings → Environments → New environment `production`:** add yourself under *Required reviewers*, so nothing is published without your click.
6. Install WordPress on Krystal (Softaculous) and upload the plugin and theme once with the zips (section 2). From then on, pushing to `main` and clicking *Approve* deploys.

Delete the private key file from your computer once it's stored in GitHub (keep a backup in your password manager if you prefer). Never paste it into a chat.
