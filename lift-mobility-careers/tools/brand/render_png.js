// Renders brand SVGs to PNG with Playwright (pre-installed Chromium). Usage: node tools/brand/render_png.js
const { chromium } = require('playwright');
const fs = require('fs'), path = require('path');
const root = path.resolve(__dirname, '../..');
const brand = path.join(root, 'brand'), img = path.join(root, 'wordpress/wp-content/themes/lmc/assets/img');
const jobs = [
  ['logo-primary.svg', 'logo-primary.png', 2, '#FFFFFF'],
  ['logo-primary-reversed.svg', 'logo-primary-reversed.png', 2, '#142F40'],
  ['logo-stacked.svg', 'logo-stacked.png', 2, '#FFFFFF'],
  ['social-profile.svg', 'social-profile-400.png', 1],
  ['social-profile.svg', 'social-profile-800.png', 2],
  ['linkedin-cover.svg', 'linkedin-cover-1128x191.png', 1],
  ['linkedin-cover.svg', 'linkedin-cover-2256x382.png', 2],
  ['facebook-cover.svg', 'facebook-cover-1640x624.png', 1],
  ['og-default.svg', 'og-default.png', 1],
  ['mark.svg', 'favicon-32.png', 0.5],
  ['mark.svg', 'apple-touch-icon.png', 180 / 64],
  ['mark.svg', 'icon-512.png', 8],
  ['social-profile.svg', 'logo-square.png', 1],
];
(async () => {
  const b = await chromium.launch();
  for (const [src, out, scale, bg] of jobs) {
    const svg = fs.readFileSync(path.join(brand, src), 'utf8');
    const [, w, h] = svg.match(/viewBox="0 0 ([\d.]+) ([\d.]+)"/).map(Number);
    const p = await b.newPage({ viewport: { width: Math.round(w), height: Math.round(h) }, deviceScaleFactor: scale });
    await p.setContent(`<html><body style="margin:0;background:${bg || 'transparent'}">${svg}</body></html>`);
    await p.screenshot({ path: path.join(brand, out), omitBackground: !bg, clip: { x: 0, y: 0, width: Math.round(w), height: Math.round(h) } });
    await p.close();
  }
  await b.close();
  for (const f of ['og-default.png', 'logo-square.png', 'favicon-32.png', 'apple-touch-icon.png', 'icon-512.png', 'logo-primary.png'])
    fs.copyFileSync(path.join(brand, f), path.join(img, f));
  console.log('PNG assets rendered');
})();
