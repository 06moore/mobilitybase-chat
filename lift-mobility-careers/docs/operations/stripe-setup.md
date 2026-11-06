# Stripe setup and test checklist

Payments are **off** until you complete this. No real money moves in test mode.

## 1. Account
Create a Stripe account for the business entity (not a personal account). Complete business verification and add the business bank account. Public business name: *Lift and Mobility Careers*. Statement descriptor: `LIFTMOBILITYCAREERS` (max 22 chars).

## 2. Test mode
1. Developers → API keys (test mode) → copy the **Secret key** `sk_test_…` → wp-config `LMC_STRIPE_TEST_SECRET` (or Settings → Payments).
2. Developers → Webhooks → **Add endpoint**: `https://YOUR-DOMAIN/wp-json/lmc/v1/stripe-webhook` (shown on Settings → Payments). Events: `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `charge.refunded`. Copy the **Signing secret** `whsec_…` → `LMC_STRIPE_TEST_WEBHOOK`.
3. Settings → Payments: mode **Test**, tick **Enable online payments**, save. Press **Test Stripe connection**.

## 3. Test checklist (do all of these)
- [ ] Register a test employer, confirm email, post a vacancy, choose **Standard**, pay with card `4242 4242 4242 4242` (any future date, any CVC). → Return to dashboard shows "Payment received"; vacancy is *Awaiting approval* (first advert); admin email received.
- [ ] Approve from the dashboard → vacancy live; "now live" email received.
- [ ] Second vacancy, **Featured** → goes live immediately (employer now trusted); highlighted in search.
- [ ] Buy a **Five-pack** from Credits & packs → 5 credits appear; use one on a new vacancy.
- [ ] Card `4000 0000 0000 9995` (declined) → no payment recorded, vacancy stays *Awaiting payment*.
- [ ] Cancel on the Stripe page → "Payment was cancelled" message, nothing charged.
- [ ] Renew a live vacancy → end date extended by 30 days; renewal email.
- [ ] Refund one payment in the Stripe dashboard → shows *Refunded* in Payments and an item in Automation errors.
- [ ] Stripe → Webhooks → recent deliveries all **200**.
- [ ] If VAT registered: tick VAT in Settings, confirm checkout shows a VAT line.

## 4. Go live
1. Switch Stripe to live mode, create a **restricted key** with write access to *Checkout Sessions* and read access to *Balance* (or use the secret key) → `LMC_STRIPE_LIVE_SECRET`.
2. Add the same webhook endpoint in live mode → signing secret → `LMC_STRIPE_LIVE_WEBHOOK`.
3. Settings → Payments → mode **Live** → save → Test connection.
4. Make one real £39 purchase with your own business card, then refund it.

Stripe sends card receipts automatically (Settings → Emails → "Successful payments" in Stripe). Optional Stripe invoices can be switched on in Settings → Payments (Stripe charges a small extra fee for these).
