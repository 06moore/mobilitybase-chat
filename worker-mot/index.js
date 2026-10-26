const CLIENT_ID  = 'bef67779-8696-42ae-89ec-94bd1a98ef85';
const TOKEN_URL  = 'https://login.microsoftonline.com/a455b827-244f-4c97-b5b4-ce5d13b4d00c/oauth2/v2.0/token';
const SCOPE      = 'https://tapi.dvsa.gov.uk/.default';
const DVSA_BASE  = 'https://history.mot.api.gov.uk/v1/trade/vehicles/registration/';

let cachedToken  = null;
let tokenExpiry  = 0;

const CORS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type'
};

function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { ...CORS, 'Content-Type': 'application/json' }
  });
}

async function getToken(clientSecret) {
  if (cachedToken && Date.now() < tokenExpiry - 60000) return cachedToken;
  const res = await fetch(TOKEN_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      grant_type: 'client_credentials',
      client_id: CLIENT_ID,
      client_secret: clientSecret,
      scope: SCOPE
    }).toString()
  });
  if (!res.ok) throw new Error(`Token request failed: ${res.status}`);
  const d = await res.json();
  cachedToken = d.access_token;
  tokenExpiry = Date.now() + d.expires_in * 1000;
  return cachedToken;
}

export default {
  async fetch(request, env) {
    if (request.method === 'OPTIONS') return new Response(null, { headers: CORS });

    const url = new URL(request.url);
    if (url.pathname !== '/mot') return new Response('Not found', { status: 404, headers: CORS });

    const reg = (url.searchParams.get('reg') || '').replace(/\s/g, '').toUpperCase();
    if (!reg) return json({ error: 'reg parameter required' }, 400);

    try {
      const token = await getToken(env.DVSA_CLIENT_SECRET);
      const r = await fetch(`${DVSA_BASE}${reg}`, {
        headers: { Authorization: `Bearer ${token}`, 'X-API-Key': env.DVSA_API_KEY }
      });

      if (r.status === 404) return json({ error: 'Vehicle not found' }, 404);
      if (!r.ok) return json({ error: `DVSA error ${r.status}` }, r.status);

      const d = await r.json();
      const passed = (d.motTests || []).find(t => t.testResult === 'PASSED');

      return json({
        registration: d.registration,
        make: d.make,
        model: d.model,
        colour: d.primaryColour,
        firstUsedDate: d.firstUsedDate,
        motExpiryDate: passed?.expiryDate || null
      });
    } catch (e) {
      return json({ error: e.message }, 500);
    }
  }
};
