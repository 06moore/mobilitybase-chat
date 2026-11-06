# Legal & Compliance Review – Lift and Mobility Careers

**Status:** Research and risk review prepared for a solicitor. It is **not legal advice** and gives no legal assurances. Every item marked **[SOLICITOR]** needs professional sign-off before launch.

**Business model reviewed:** UK online job board; employers (businesses) pay fixed fees to advertise vacancies; candidates browse and apply free, directly to the employer; no CVs held; no candidate accounts; operator also researches employers' public careers pages to find leads and invites employers to advertise.

Draft documents (rendered as website pages automatically): `wordpress/wp-content/plugins/lmc-core/data/legal/*.md`.

---

## ⛔ LAUNCH BLOCKER – employment agency regulation

**Being an advertising-only job board does not automatically exempt this business from the Employment Agencies Act 1973 or the Conduct of Employment Agencies and Employment Businesses Regulations 2003.** The site is designed on the cautious assumption that it may be an employment agency (section 1 below), but **whether it is, and exactly which duties apply, must be decided by a specialist** (an employment lawyer or recruitment-compliance specialist) before launch.

This is enforced in the software, not just written down:
- The site is in **pre-launch mode** until the operator records the specialist's name and the review date in *Lift & Mobility → Launch checklist*. In pre-launch mode, every page is `noindex`, robots.txt blocks crawling, there is no sitemap or job feed, nothing is sent to search engines, and **Stripe is forced to test mode, so no real payment can be taken**.
- The "Go live" button stays disabled while any blocker remains: legal sign-off, legal-page placeholders/DRAFT notices, demo content, incomplete business details, no HTTPS domain, no email delivery.

**Questions for the specialist** (bring this document):
1. Is the business an "employment agency" under EAA s.13, given s.13(4)(a) excludes publications only if not "published wholly or mainly" to help people find work?
2. If so, which Conduct Regulations apply to an advertising-only service that never introduces or supplies candidates? In particular: reg 26 (no work-seeker fees), reg 27 (advertisement content – the agency's full name and whether it's an agency or business; nature of work, location and minimum experience where pay is stated), regs 14–16 (terms with hirers – does the Employer Advertising Agreement need specific content?), regs 18–22 (suitability/information checks – do they bite at all?), and record-keeping (reg 29).
3. Is the advert disclosure wording right? Default: *"Advertised by Lift and Mobility Careers, an employment agency providing job advertising services only. We are not the employer and do not charge candidates any fee."* (Settings → Business details). Calling the business an "employment agency" is itself a legal position; the specialist may prefer different wording.
4. Who enforces now? The Employment Agency Standards Inspectorate has historically enforced these rules. The Employment Rights Act 2025 provides for a new Fair Work Agency to take over several enforcement bodies including EAS – confirm the current position and any new obligations.
5. Northern Ireland employers: separate legislation applies (Employment (Miscellaneous Provisions) (NI) Order 1981 and the NI Conduct Regulations) – advise, or restrict NI adverts.
6. Online Safety Act 2023 scope (section 7), PECR/UK GDPR outreach approach (sections 2–3), Data (Use and Access) Act 2025 changes, and the liability/refund terms.
7. Job alerts: confirm double opt-in consent and the privacy wording.

## Remaining placeholders (all must be replaced after the review)

The Launch checklist lists these live from the published pages. At the time of writing:

| Document | Placeholders |
|---|---|
| Terms and Conditions | [DATE], [LEGAL ENTITY NAME], [NUMBER], [ADDRESS], [CONTACT EMAIL], plus the instruction "Replace every [BRACKETED] item" |
| Privacy Policy | [DATE], [LEGAL ENTITY NAME], [CONTACT EMAIL] ×2, [POSTAL ADDRESS], [ICO REFERENCE], [HOST NAME], [EMAIL PROVIDER] |
| Cookie Policy | [DATE], [LEGAL ENTITY NAME], [CONTACT EMAIL] |
| Employer Advertising Agreement | [DATE], [LEGAL ENTITY NAME] |
| Refund Policy | [DATE], [CONTACT EMAIL] |
| Employer Permission and Content Licence | [LEGAL ENTITY NAME], [CONTACT EMAIL] |
| Vacancy Authorisation Declaration | none (DRAFT notice only) |
| Website Disclaimer | none (DRAFT notice only) |

Every document also carries a "DRAFT – not yet reviewed by a solicitor" notice, which must be removed once approved. The checklist treats it as a blocker too.

**Cross-references checked:** every internal link in the legal pages resolves (Privacy → Cookie Policy; Cookie → Privacy; Terms → Advertising Agreement, Authorisation Declaration, Content Licence, Contact; Agreement → Pricing, Authorisation, Refunds; Refunds → Agreement; Declaration → Agreement, Licence; Disclaimer → Contact). An earlier formatting bug garbled any link that followed a [PLACEHOLDER] in the same paragraph (e.g. the Cookie Policy's link to the Privacy Policy). It has been fixed and covered by a test.

---

## 1. Employment Agencies Act 1973 (EAA) and Conduct Regulations 2003

**Do not assume exemption.** EAA s.13(2) defines an employment agency as a business providing services "(whether by the provision of information or otherwise)" for the purpose of finding persons employment with employers or supplying employers with persons. s.13(4)(a) excludes "publishing a newspaper or other publication **unless it is published wholly or mainly** for the purpose" of finding people work. A specialist job board exists wholly for that purpose, so on a plain reading **the exclusion is unlikely to apply and the business should be treated as an employment agency** unless a solicitor advises otherwise. Trade commentary (e.g. Recruiter, 2012) and the *BERR v Adams* (2008) decision point the same way, though no case settles it for job boards generally. The 2003 Regulations define "publication" to include electronic form.

What that means in practice, and how the build handles it:

| Requirement (summary – verify exact wording) | How the build addresses it |
|---|---|
| **No fees to work-seekers** (EAA s.6, Conduct Regs reg 26) | Candidates never pay or register. Advert text is automatically scanned for candidate-fee wording and held for review. |
| **Adverts must state the agency's full name and whether it is an employment agency or employment business** (reg 27(1)) | Every vacancy page carries a configurable disclosure line ("Advertised by Lift and Mobility Careers, an employment agency providing job advertising services only…"). **[SOLICITOR]** confirm wording. |
| **If pay rates are advertised, adverts must also state the nature of the work, location and minimum experience/qualifications** (reg 27(2)) | Salary, location, experience level, job category and requirements are all **mandatory** fields. |
| Other Conduct Regs duties (terms with hirers reg 14–16, suitability checks reg 18–22, records reg 29) | These mostly apply when an agency introduces/supplies candidates. We don't. **[SOLICITOR]** confirm which, if any, apply to an advertising-only agency – e.g. whether the Employer Advertising Agreement must contain reg 14 "terms with hirers" content. |
| No licence needed in GB (licensing abolished 1995); EAS enforces | No action. Keep a compliance file. **Northern Ireland** has separate legislation (Employment (Miscellaneous Provisions) (NI) Order 1981 / Conduct Regs (NI) 2005) – **[SOLICITOR]** if NI employers advertise. |

## 2. UK GDPR & Data Protection Act 2018

- **Data minimisation by design:** no candidate data collected; anonymous view/click counters only; no CV database.
- **Employer accounts:** contract basis; retention policy in Privacy Policy; password hashing by WordPress.
- **Researched business contacts (CRM):** legitimate interests. Complete a **Legitimate Interests Assessment** (template below) and keep it. Art. 14 information must be given within a month (first email links to Privacy Policy). Auto-deletion of stale prospects after 24 months is built in.
- **ICO data protection fee:** register and pay before processing (tier 1 for most micro businesses – check current fee on ico.org.uk). Insert reference number in the Privacy Policy.
- **Processors:** sign/accept DPAs with host, email provider, Stripe (built into their terms). Record them in a simple Record of Processing (template below).
- **Data (Use and Access) Act 2025:** amends UK GDPR/PECR (including higher PECR fines and some cookie exemptions) with phased commencement. **[SOLICITOR]** check which provisions are in force at launch.

## 3. PECR (electronic marketing)

- **Corporate subscribers** (limited companies, LLPs, PLCs, Scottish partnerships, public bodies): B2B marketing email is permitted without prior consent, but must identify the sender and give a valid address and a simple opt-out. GDPR still applies to named individuals' data.
- **Individual subscribers** (sole traders, ordinary partnerships in E/W/NI): prior consent or the "soft opt-in" is required.
- **Built-in controls:** the CRM records entity type and **blocks** marketing email to sole traders, partnerships and *unknown* entities unless consent is recorded with a note; requires a postal address before any outreach; adds identification, address and one-click unsubscribe (RFC 8058) to every marketing email; maintains a permanent suppression list; limits to one follow-up; daily cap (default 10); no bulk send feature exists.
- **Existing customers:** "repeat advertising" emails use the soft opt-in – opt-out offered at registration and in every message.
- **Service emails** (verification, payment, expiry, renewal) are not marketing and are always sent.

## 4. Cookies (PECR reg 6)

Default configuration sets **no non-essential cookies** (only WordPress login cookies for employers), so no banner is required. If a GA4 ID is entered, a consent banner appears and GA loads only after "Accept"; reject is equally prominent.

## 5. Website scraping / vacancy discovery

- Only titles, locations, URLs and dates are recorded – **advert text and logos are never copied or republished** (copyright and database right risk).
- A source is not monitored until the operator ticks "terms reviewed – monitoring permitted"; robots.txt is honoured; identified user agent; low frequency; failures flagged.
- Nothing is published without the employer's written permission; we then write an original summary.
- **[SOLICITOR]** confirm the approach to website terms that prohibit automated access, and the Computer Misuse Act position is not engaged by polite public-page fetching.

## 6. Equality Act 2010

Advertisers warrant compliance; adverts are scanned for common discriminatory wording (age, sex, nationality) and flagged for manual review. The EHRC can act on discriminatory adverts; publishers should not knowingly publish them. Keep the moderation step on for first adverts.

## 7. Online Safety Act 2023

Employers post content that other users can see – the Site may be a **user-to-user service** in scope. If so, an illegal content risk assessment and proportionate measures (reporting route, moderation, terms) are needed; the risk is low (fraud/job scams are the main one). Built-in: "Report this advert" link, first-advert moderation, scam-signal keyword flags, terms prohibiting illegal content. **[SOLICITOR]** confirm scope and record a short risk assessment.

## 8. Consumer, e-commerce and advertising law

- **Electronic Commerce Regulations 2002 / Companies Act 2006:** display legal entity, registered office, company number, VAT number, contact email. The footer and emails pull these from Settings – complete them before launch.
- **Business Names Act rules (Companies Act 2006 Part 41):** trading as "Lift and Mobility Careers" requires disclosure of the owner's legal name on the site, invoices and correspondence (handled by the footer/email identity line once entered).
- **Consumer law:** sales are B2B. Sole traders acting for business are not consumers. **[SOLICITOR]** confirm no consumer cancellation rights apply.
- **CAP Code / DMCC Act 2024 / CPRs:** our own marketing (e.g. "specialist", "UK-wide") must be truthful – the site makes **no** claims about audience size, traffic or number of employers. Don't add such claims until they're true and evidenced. Package features (e.g. "social media post") are commitments – the social queue makes them easy to fulfil.
- **VAT:** register when taxable turnover passes the threshold (£90,000 at the time of writing – check). The "VAT registered" setting adds VAT at checkout.

## 9. Payments and refunds

Stripe Checkout (hosted) keeps card data off the site (PCI DSS SAQ A scope). Refund policy drafted. Stripe's own terms apply.

## 10. Before-launch legal checklist

- [ ] Choose legal entity (a limited company keeps this independent of any other business and personal liability – **accountant/solicitor**).
- [ ] Solicitor review of all eight drafts + this review, especially items marked [SOLICITOR].
- [ ] ICO registration; insert reference.
- [ ] Complete LIA and Record of Processing (templates below).
- [ ] Online Safety Act illegal-content risk assessment (short).
- [ ] Fill Settings → Business details (entity, number, addresses, VAT).
- [ ] Professional indemnity / public liability insurance (broker).
- [ ] Trade mark search for "Lift and Mobility Careers" (UKIPO) before investing in the brand.

---

### Template – Legitimate Interests Assessment (employer outreach)

1. **Purpose:** inform relevant UK businesses about a specialist advertising service. **Necessity:** contacting a small, targeted list (50–100) by one-to-one email to published business addresses is the least intrusive effective method. **Balance:** recipients are businesses in the exact sector, using role/business contact details, would reasonably expect relevant B2B approaches; low volume; immediate opt-out; no sensitive data. **Safeguards:** PECR entity check, suppression list, one follow-up maximum, 24-month deletion. **Outcome:** [to be signed off].

### Template – Record of Processing

| Activity | Data | Basis | Processor | Retention |
|---|---|---|---|---|
| Employer accounts | name, role, company, email, phone | contract | host, email provider | account + 2 yrs |
| Payments | billing name/email, amounts | contract / legal obligation | Stripe | 6 yrs |
| Outreach CRM | business contact name/role/email | legitimate interests | host, email provider | 24 months |
| Contact form | name, email, message | legitimate interests | host, email provider | 2 yrs |
| Analytics (optional) | pseudonymous identifiers | consent | Google | 14 months (GA setting) |

### Sources consulted

- Employment Agencies Act 1973, s.13 – legislation.gov.uk/ukpga/1973/35/section/13
- Conduct of Employment Agencies and Employment Businesses Regulations 2003, reg 27 – legislation.gov.uk/uksi/2003/3319/regulation/27
- GOV.UK, Overview of the Conduct Regulations 2003 (EAS guidance)
- Recruiter, "Jobs boards warned on misconception of employment agency definition" (2012)
- BERR v Adams (t/a Juliet Adams Model Agency) [2008] EWHC 772 (Admin)
- ICO guidance on PECR, B2B marketing and cookies (ico.org.uk) – check current versions
