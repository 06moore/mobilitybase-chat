# Google, Bing & aggregator setup

All optional; each takes 10–20 minutes once the site is live on its domain. Nothing here costs money.

## 1. Google Search Console (essential)
1. search.google.com/search-console → **Add property → Domain** → `liftandmobilitycareers.co.uk` → add the DNS TXT record it gives you. (Alternatively a URL-prefix property verified with the HTML tag: paste the code in Settings → SEO.)
2. Sitemaps → submit `https://liftandmobilitycareers.co.uk/wp-sitemap.xml`.
3. After a few days check **Pages** (indexing) and **Enhancements → Job postings** for structured-data errors.

## 2. Google service account (for the Indexing API and Search Console data in reports)
1. console.cloud.google.com → create project "LMC website".
2. APIs & Services → Enable **Web Search Indexing API** and **Google Search Console API**.
3. IAM & Admin → Service accounts → Create (e.g. `lmc-website`) → Keys → Add key → JSON → download.
4. Upload the JSON file to the server **outside the web root** (e.g. `/home/USER/private/lmc-google.json`) and add to wp-config.php:
   `define( 'LMC_GOOGLE_SA_JSON', '/home/USER/private/lmc-google.json' );`
5. In Search Console → Settings → Users and permissions → add the service account's email as an **Owner** (needed for the Indexing API).
6. WP Admin → Lift & Mobility → Settings → **Growth**: Search Console property (`sc-domain:liftandmobilitycareers.co.uk`), tick "Notify Google Indexing API". The Traffic & growth page will then show Search Console data.

Google's Indexing API is officially intended for pages with JobPosting (and livestream) structured data – exactly this use. The default quota is ample for a job board of this size.

## 3. Google Analytics 4 (optional)
analytics.google.com → create property → web stream → copy the Measurement ID `G-…` → Settings → SEO. A consent banner appears automatically and GA loads only after "Accept". Events sent: `view_item` (job view), `apply_click`, `share`, `sign_up` (job alert). Mark `apply_click` and `sign_up` as key events in GA4.

## 4. Bing Webmaster Tools + IndexNow
bing.com/webmasters → import from Search Console. Then Settings → Growth → tick "Notify Bing (IndexNow)" – a key is generated and served automatically.

## 5. Job aggregators (free organic traffic)
Feed URL: `https://liftandmobilitycareers.co.uk/feeds/jobs.xml` (updates live; every job links back to our vacancy page).
Contact each aggregator's publisher/partner team to submit a free organic job feed – for example **Adzuna**, **Jooble**, **Careerjet**, **Talent.com**. Requirements and acceptance change, so check each site's current "submit jobs / partner" page. Indeed now focuses on direct employers and may not accept job-board feeds – check its current policy before applying.
Traffic from these sites appears as "Job aggregators" in the dashboard.
