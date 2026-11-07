# Business plan summary – Lift and Mobility Careers

## 1. Concept
An independent UK online job board dedicated only to engineering jobs in the mobility lift and accessibility equipment industry: stairlifts, through-floor and home lifts, platform/access/wheelchair lifts, ceiling track hoists and patient handling equipment. Roles: installation, service, maintenance, breakdown and commissioning engineers, LOLER inspectors, technical support, trainees, apprentices, supervisors and managers.
Tagline: "Connecting mobility engineering talent with opportunity."

## 2. Business model
- Employers pay to advertise; candidates browse and apply free, directly to the employer (link or email). No CV database, no candidate fees, no placement fees.
- Prices (ex VAT, 30-day listings, one-off payments, renewal optional): Standard £39; Featured £69 (highlighted, ranked higher, highlighted in job alert emails); Premium £99 (homepage section, top position, listed first in alerts). Packs: 5 standard adverts £175, 10 for £300 (valid 12 months).
- Launch plan: free listings only for the first few months, to build vacancies and traffic; then switch on Stripe payments. Direct employers only at first, not recruitment agencies.
- Structure: sole trader under the founder's own name, moving to a limited company once revenue is proven. The founder also runs another company and has limited time, so the business must be very low-maintenance.

## 3. Market and competition (desk research, Nov 2026 – see docs/growth/competitor-analysis.md)
- Steady demand: major employers (Stannah, Acorn, Arjo, Handicare/Savaria, Stiltz, Millbrook Healthcare, Gartec) and independents (e.g. Halton Stairlifts, Senior Stairlifts) advertised engineer roles in 2025–26. Typical pay is £26k–£50k; LOLER and specialist roles pay more.
- Search results for "stairlift engineer jobs" etc. are dominated by generic aggregators (ZipRecruiter, Jobijoba, Jobbird, Indeed, DWP Find a Job), often padded with irrelevant "lift" roles. No dedicated specialist job board was found.
- Competition for employer budget comes from lift-specialist recruitment agencies charging placement fees. Pitch: fixed price from £39, no placement fee, applications go straight to the employer.
- Target: relationships with 50–100 direct UK employers (starter list: docs/marketing/target-employers.csv).

## 4. What is built (tested locally, not yet live)
WordPress with a custom plugin and theme. Features:

- job search (location/radius, sector, role, salary, type, experience)
- employer accounts and structured vacancy submission with an authorisation declaration
- Stripe Checkout with signed webhooks; credits and packs
- automated expiry, 7- and 2-day reminders, renewals, refunds and failed-payment handling
- weekly and monthly reports, and an admin dashboard
- Google JobPosting structured data, SEO landing pages and career guides
- free candidate job alerts (double opt-in), social sharing, and an employer share kit with a backlink reward
- aggregator XML job feed, cookie-free analytics, and a GDPR/PECR-compliant outreach CRM
- vacancy discovery that monitors employer careers pages but never republishes without written permission
- a launch checklist that keeps the site hidden and payments in test mode until legal sign-off

283 automated checks pass.

## 5. Growth plan (organic, no paid advertising – see docs/growth/)
Channels: Google organic search and Google for Jobs, free job aggregators via the feed, job alert emails, employer sharing/backlinks, LinkedIn/Facebook and career guides.

12-month base-case targets:

| Measure | Month 1 | Month 6 | Month 12 |
|---|---|---|---|
| Visits / month | 300 | 1,800 | 4,200 |
| Job views / month | 400 | 2,600 | 6,000 |
| Job-alert subscribers | 15 | 160 | 450 |
| Paying employers (cumulative) | 0 | 10 | 28 |
| Live vacancies | 15 | 40 | 70 |

## 6. Costs (estimates – check current prices)
- **One-off before launch:** about £370–890 (legal review £300–800, ICO fee ~£50, domains £20–40).
- **Monthly:** about £20–55 (hosting £10–25, virtual office £10–30); £30–90 with a mailbox and insurance. Target running costs: £50–75/month.
- Stripe charges only per payment.

## 7. Legal and risks
- Under the Employment Agencies Act 1973, a specialist job board is probably an "employment agency" (the publication exemption likely doesn't apply). Safeguards built in: no candidate fees, an advert disclosure line, and mandatory salary/location/experience fields. A specialist legal review is required before launch.
- Other considerations: UK GDPR, PECR (marketing rules differ for sole traders and companies), Online Safety Act scope, personal liability as a sole trader, and a possible perceived link to the founder's other business in a related sector.
- Main commercial risks:
    - chicken-and-egg: vacancies are needed to attract engineers, and engineers to attract paying employers
    - a small niche market
    - employers continuing to use free channels (Indeed, DWP) or agencies
