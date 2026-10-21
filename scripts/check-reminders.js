const fs = require('fs');
const path = require('path');

const SERVICE_ID   = 'service_y4wwcjf';
const PUBLIC_KEY   = 'bpfAYzM-hUEj9nKae';
const PRIVATE_KEY  = process.env.EMAILJS_PRIVATE_KEY;
const TEMPLATE_ID  = process.env.EMAILJS_TEMPLATE_ID;
const TO_EMAIL     = process.env.TO_EMAIL || 'service@mobilitybase.co.uk';

if (!PRIVATE_KEY || !TEMPLATE_ID) {
  console.error('Missing EMAILJS_PRIVATE_KEY or EMAILJS_TEMPLATE_ID environment variables.');
  process.exit(1);
}

const vehicles = JSON.parse(
  fs.readFileSync(path.join(__dirname, '..', 'vehicles.json'), 'utf8')
);

function daysUntil(dateStr) {
  if (!dateStr) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return Math.round((new Date(dateStr) - today) / 86400000);
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric', month: 'long', year: 'numeric'
  });
}

function inWindow(days, target) {
  return days !== null && days >= target - 1 && days <= target + 1;
}

async function sendEmail(message) {
  const res = await fetch('https://api.emailjs.com/api/v1.0/email/send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      service_id:      SERVICE_ID,
      template_id:     TEMPLATE_ID,
      user_id:         PUBLIC_KEY,
      accessToken:     PRIVATE_KEY,
      template_params: { to_email: TO_EMAIL, message }
    })
  });

  if (!res.ok) {
    const body = await res.text();
    throw new Error(`EmailJS error ${res.status}: ${body}`);
  }
  console.log('Reminder email sent successfully.');
}

async function main() {
  const reminders = [];

  for (const v of vehicles) {
    const label = `${v.registration} — ${v.make} ${v.model}`;
    const motDays = daysUntil(v.motExpiry);
    const taxDays = daysUntil(v.taxExpiry);

    if (inWindow(motDays, 30)) {
      reminders.push(`${label}\n  MOT expires ${formatDate(v.motExpiry)} (approx. 1 month)`);
    } else if (inWindow(motDays, 14)) {
      reminders.push(`${label}\n  MOT expires ${formatDate(v.motExpiry)} (approx. 2 weeks)`);
    }

    if (inWindow(taxDays, 30)) {
      reminders.push(`${label}\n  Tax expires ${formatDate(v.taxExpiry)} (approx. 1 month)`);
    } else if (inWindow(taxDays, 14)) {
      reminders.push(`${label}\n  Tax expires ${formatDate(v.taxExpiry)} (approx. 2 weeks)`);
    }
  }

  if (reminders.length === 0) {
    console.log('No reminders due today.');
    return;
  }

  const message =
    `The following vehicles in your fleet have upcoming MOT or tax expiry dates:\n\n` +
    reminders.join('\n\n') +
    `\n\nPlease arrange renewal as soon as possible to keep your fleet legal.\n\nMobilitybase Vehicle Dashboard`;

  console.log(`Sending reminder for ${reminders.length} item(s)...`);
  await sendEmail(message);
}

main().catch(err => {
  console.error(err.message);
  process.exit(1);
});
