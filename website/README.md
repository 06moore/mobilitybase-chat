# Mobilitybase website (WordPress)

The new mobilitybase.co.uk, rebuilt from the Wix site as a custom WordPress theme. It looks the same as before, and every old address still works.

```
website/
  theme/mobilitybase/      ← the WordPress theme (this is what goes on the server)
    inc/business.php       company details: phone, email, address, hours, form recipient
    inc/data.php           products, models, warranty prices, services (edit here)
    inc/forms.php          enquiry forms → email + "Enquiries" list in WP admin
    inc/seo.php            titles, descriptions, canonical, schema, sitemap
    inc/importer.php       one-off import of all pages and articles
    content/               page and article content carried over from Wix
    templates/             range, warranty and town-page layouts
  tools/                   scripts used to crawl the Wix site and build content/
```

## What's included

- **Same design:** header, green bands, grey sections, dark footer, fonts and colours.
- **Same URLs:** pages stay at `/about-us`, articles at `/post/stairlift-cost-uk`, and `/sitemap.xml` still works. Nothing needs redirecting except `/inquiry-services-page` → `/services`.
- **SEO carried over:** every page's Google title and description, plus the FAQ/HowTo schema from the articles.
- **New schema:** LocalBusiness on every page, Product on range pages, Service + Offer prices on warranty pages, and BreadcrumbList.
- **Forms:**
  - Every form is emailed to `service@mobilitybase.co.uk` (changeable in **Settings → Mobilitybase**). Reply-To is set to the customer.
  - Every form is also saved under **Enquiries** in the admin.
  - Spam is blocked without captchas.
- **Selling improvements:**
  - "Free Quote" button in the header.
  - Call / Free quote bar fixed to the bottom on phones.
  - "Enquire about this model" buttons that pre-fill the form.
  - Plan buttons on the warranty pages.
  - Product cards and latest articles on the home page.
  - Town pages with the form in the first screen.
- **Speed:** no page builder, self-hosted fonts, WebP images, no jQuery.

## Going live on Krystal (step by step)

1. **Krystal:** create the hosting account and install WordPress (Softaculous, one click). Use the temporary URL Krystal gives you for now.
2. **Theme:**
   - Zip the `theme/mobilitybase` folder (or use the ready-made `mobilitybase-theme.zip`).
   - In WordPress go to **Appearance → Themes → Add New → Upload** and upload the zip, then click **Activate**.
3. **Import:** **Tools → Mobilitybase import → Run import**. This creates all 56 pages and 9 articles, sets the home page and blog page, and sets the permalinks.
   - Or over SSH: `wp mobilitybase import`
4. **Email:** install the free **WP Mail SMTP** plugin and connect it to a real mailbox, so enquiries don't land in spam.
   - A Krystal mailbox works, or keep the hostit mailbox: use its SMTP details.
   - Then press **Settings → Mobilitybase → Send a test email**.
5. **Check everything** on the temporary URL.
6. **Switch the domain:**
   - Point mobilitybase.co.uk at Krystal.
   - **Important:** copy the existing **MX records** (email, currently hostit) across exactly, so email keeps working.
   - Then turn on Krystal's free SSL and set the WordPress URL to `https://www.mobilitybase.co.uk`.
7. **Google:**
   - Add the site in Search Console and paste the verification code into **Settings → Mobilitybase**.
   - Submit `https://www.mobilitybase.co.uk/sitemap.xml`.
8. **Cancel Wix** only after the new site has been live and checked for a week or two.

## Automatic updates from GitHub

`.github/workflows/deploy-website.yml` uploads the theme to Krystal every time it changes on `main`. It needs these secrets in the repo settings (Krystal gives SSH access on port 722):

- `KRYSTAL_HOST`
- `KRYSTAL_PORT`
- `KRYSTAL_USER`
- `KRYSTAL_SSH_KEY`
- `KRYSTAL_THEME_DIR`

Until those are set, the workflow skips itself.

## Rebuilding content from the old site

Only needed if the Wix site changes before the switch-over:

```
node tools/crawl.js urls.txt crawl/          # page text + images list
node tools/posts.js urls.txt crawl/posts     # article HTML
python3 -I tools/build_content.py crawl/ img_raw/ theme/mobilitybase
```
