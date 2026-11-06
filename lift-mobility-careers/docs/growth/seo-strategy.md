# SEO strategy – Lift and Mobility Careers

**Goal:** be the website Google shows, and engineers recognise, for mobility lift engineering jobs in the UK.

**Why we can win:** generalist job boards (Indeed, Reed, Totaljobs) rank on sheer volume, but their pages for niche searches are thin, mixed with unrelated "lift" jobs (forklift, passenger lifts, "lifting" warehouse work) and rarely explain the work. A specialist site with (a) every relevant vacancy in one place, (b) genuinely useful sector and career content and (c) clean job structured data can outrank them on long-tail and mid-tail searches and appear in Google's job search experience.

> **On search volumes:** no keyword tool was available in this environment, so this plan doesn't quote volumes. Within the first 4–6 weeks, check real numbers with Google Search Console (impressions) and Google Keyword Planner (free with a Google Ads account – no spend needed), and re-rank the priorities below.

## 1. Keyword map (one primary page per intent – no duplicates)

| Search intent | Example queries | Target page | Status |
|---|---|---|---|
| **Stairlift jobs** | stairlift engineer jobs, stairlift jobs, stairlift technician jobs | `/jobs/sector/stairlifts/` | ✅ built, curated copy |
| Stairlift service | stairlift service engineer jobs, stairlift maintenance jobs, stairlift repair jobs | `/stairlift-service-engineer-jobs/` | ✅ |
| Stairlift install | stairlift installation jobs, stairlift fitter jobs, stairlift installer jobs | `/stairlift-installation-jobs/` | ✅ |
| **Home lifts** | home lift engineer jobs, domestic lift engineer jobs, residential lift jobs | `/jobs/sector/home-lifts/` | ✅ |
| Through-floor | through floor lift engineer jobs | `/jobs/sector/through-floor-lifts/` | ✅ |
| **Platform lifts** | platform lift engineer jobs, platform lift service engineer | `/jobs/sector/platform-lifts/` | ✅ |
| Access / wheelchair / step lifts | access lift engineer jobs, wheelchair lift engineer jobs | `/jobs/sector/access-lifts/`, `/wheelchair-lifts/` | ✅ |
| **Hoists** | hoist engineer jobs, ceiling hoist engineer jobs, patient hoist engineer jobs | `/jobs/sector/ceiling-track-hoists/` | ✅ |
| Patient handling | patient handling equipment engineer, medical equipment field service engineer | `/jobs/sector/patient-handling/` | ✅ |
| Mobility equipment | mobility equipment engineer jobs, mobility engineer jobs | `/jobs/sector/mobility-equipment/` | ✅ |
| **LOLER** | LOLER inspector jobs, LOLER engineer jobs, thorough examination engineer jobs | `/jobs/role/loler-inspector/` + guide | ✅ |
| Each job category (13) | lift maintenance engineer jobs, lift breakdown engineer jobs, lift commissioning engineer jobs, trainee lift engineer, lift engineering apprenticeship, lift service manager jobs… | `/jobs/role/{category}/` | ✅ all 13 curated |
| **Location** | lift engineer jobs Kent, stairlift engineer jobs Manchester… | `/lift-engineer-jobs-kent/` (curated); `/jobs/location/{county}/` auto-indexed once ≥3 live jobs | ✅ rule built |
| Careers info | how to become a stairlift engineer, what does a LOLER inspector do, lift engineer apprenticeship, career change electrician to lift engineer | `/career-guides/…` | ✅ 5 guides |
| Salary | stairlift engineer salary, lift engineer salary UK, LOLER inspector salary | `/career-guides/lift-engineer-salary-guide/` (live data from real adverts, ≥5 per figure) | ✅ |
| Employer brand | {company} jobs, {company} careers | `/companies/{company}/` (indexed while they have live jobs) | ✅ |
| Individual vacancies | {job title} {town} | `/jobs/{vacancy}/` + JobPosting | ✅ |

**Not targeted (deliberately):** "lift engineer jobs" on its own is dominated by passenger/escalator lift roles. We earn it gradually via relevance but don't build a page competing for it with thin content.

**Thin-page protection (built in):** county/region pages are `noindex` and out of the sitemap until they have ≥3 live jobs (setting); filtered searches are `noindex` with canonicals to the clean URL; closed vacancies keep a page (with a "closed" notice and links to similar jobs) but are `noindex`, removed from the sitemap and sent to Google's Indexing API as deleted.

## 2. Technical SEO (all built)

- Unique `<title>` and meta description for every page type, auto-generated for vacancies: *"{Job title} – {Town} | {Employer}"* plus a description containing employer, location and salary.
- **JobPosting structured data** on every live vacancy: title, full description (incl. responsibilities/requirements), datePosted, validThrough, employmentType, hiringOrganization (+ authorised logo), jobLocation (town, county, postcode, GB), baseSalary (GBP, min/max, unit), identifier, directApply=false. Removed automatically when a vacancy closes.
- **Google Indexing API** (officially supported for job pages): new vacancies notified the moment they go live; closed ones notified as deleted. **IndexNow** for Bing.
- BreadcrumbList on jobs, sector/role/location and guide pages; Article schema on guides; Organization + WebSite (sitelinks search box) on the homepage.
- XML sitemaps (pages, vacancies, indexable taxonomy pages, company pages with live jobs); robots.txt; canonicals; Open Graph/Twitter cards.
- Speed: no page builder, no jQuery, self-hosted fonts, one CSS + one small JS file, no third-party scripts unless analytics is accepted. Mobile-first layout (tested at 390px).
- Internal linking: homepage → sectors/roles; sector & role pages → guides and salary guide; guides → relevant sector/role pages and job alerts; job pages → sector, company page, similar vacancies.

## 3. Content plan (evergreen, original)

Launch set (built): 9 sector pages, 13 job-category pages, 3 curated combination pages, 5 career guides, salary guide.

Add **one guide per month** in months 2–12 (≈600–900 words, written for engineers, with links to live jobs). Priority list:
1. What does a home lift engineer do?
2. Platform lift vs passenger lift engineering – what's different?
3. Ceiling track hoist engineer: a day in the job
4. Interview questions for stairlift and lift engineers (and how to answer)
5. Your first 90 days as a field service engineer
6. On-call and breakdown work explained (allowances, rotas)
7. Electrical qualifications that help in lift engineering
8. Working in patient handling: hospitals, care homes and community equipment
9. How to write a CV for a lift engineering job
10. Regional guide: lift engineering jobs in the North West / Scotland (only once there are regular jobs there)
11. Annual "State of the market" post from our own vacancy data (month 12 – a linkable asset)

Rules: never publish thin location pages; write from real industry knowledge; update the salary guide link in posts; every guide ends with a job alert sign-up.

## 4. Off-site authority (links and mentions)

Highest value, lowest cost – in order:
1. **Employer backlinks** (built in): every employer gets a share kit and an "Advertised on" badge; a verified link earns a free Featured upgrade. Target 1 new verified link per month from month 3.
2. **Free job aggregators**: submit `/feeds/jobs.xml` to Adzuna, Jooble, Careerjet, Talent.com (and others accepting job-board feeds). They send applicants to *our* vacancy pages. (See google-setup.md.)
3. **Google for Jobs** via structured data + Indexing API.
4. Industry bodies and trade media – offer a short data-led piece (e.g. "what employers are asking for") to trade publications and association newsletters; ask associations if they list careers resources.
5. Colleges and training providers running electrical/engineering courses – offer the career guides as a free resource for learners.
6. Forces resettlement and career-change organisations – the career-change guide is written for them.
7. LinkedIn company page + engineering groups and relevant Facebook groups (follow each group's rules; share only real vacancies and guides).

Avoid paid link schemes, directory spam and mass guest posting – they risk penalties.

## 5. Measurement

- **Built-in (no cookies):** visits, page views, traffic sources, landing pages, referrers, job views by source, Apply clicks, shares, job alert sign-ups/confirmations/unsubscribes, alert emails sent, backlinks.
- **Consent-based:** returning visitors (built-in) and GA4 (optional).
- **Search Console:** clicks, impressions, CTR, average position, top queries and pages – pulled into the dashboard and monthly report once the service account is connected.
- **Monthly report** (1st of each month) compares every KPI against the 12-month targets and suggests actions, including "striking distance" queries (positions 8–20 with impressions).
