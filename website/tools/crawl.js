const { chromium } = require('playwright');
const fs = require('fs');
const [,, urlsFile, out] = process.argv;
const urls = fs.readFileSync(urlsFile,'utf8').trim().split('\n');
(async () => {
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport:{width:1440,height:1800} });
  const run = async (u) => {
    const p = await ctx.newPage();
    const slug = (new URL(u).pathname.replace(/^\/|\/$/g,'').replace(/\//g,'__')) || 'home';
    try {
      await p.goto(u, { waitUntil:'load', timeout:60000 });
      // scroll to trigger lazy content
      for (let y=0;y<30000;y+=800){ await p.evaluate(y=>window.scrollTo(0,y),y); await p.waitForTimeout(120); }
      await p.waitForTimeout(1500);
      const data = await p.evaluate(() => {
        const meta = n => document.querySelector(`meta[name="${n}"],meta[property="${n}"]`)?.content || '';
        const blocks=[];
        document.querySelectorAll('h1,h2,h3,h4,h5,h6,p,li,a,button,label,input,textarea,select,img').forEach(el=>{
          const r=el.getBoundingClientRect(); if(!r.width) return;
          const t=el.tagName.toLowerCase();
          if(t==='img'){ blocks.push({t, src:el.currentSrc||el.src, alt:el.alt, w:Math.round(r.width), h:Math.round(r.height)}); return; }
          if(['input','textarea','select'].includes(t)){ blocks.push({t, name:el.name, type:el.type, ph:el.placeholder, req:el.required, aria:el.getAttribute('aria-label')}); return; }
          if(t==='a'){ blocks.push({t, href:el.href, text:el.innerText.trim().slice(0,120)}); return; }
          if(t==='li' && el.querySelector('p')) return;
          const txt=el.innerText.trim(); if(!txt) return;
          const cs=getComputedStyle(el);
          blocks.push({t, li:!!el.closest('li'), text:txt, font:cs.fontFamily.split(',')[0], size:cs.fontSize, weight:cs.fontWeight, color:cs.color});
        });
        const ld=[...document.querySelectorAll('script[type="application/ld+json"]')].map(s=>s.textContent);
        return { title:document.title, desc:meta('description'), ogimg:meta('og:image'), canonical:document.querySelector('link[rel=canonical]')?.href, ld, blocks };
      });
      fs.writeFileSync(`${out}/text/${slug}.json`, JSON.stringify(data,null,1));
      await p.evaluate(()=>window.scrollTo(0,0)); await p.waitForTimeout(500);
      if(process.env.SHOT){ const H=await p.evaluate(()=>document.documentElement.scrollHeight); for(let i=0;i*1800<Math.min(H,16000);i++){ await p.evaluate(y=>window.scrollTo(0,y),i*1800); await p.waitForTimeout(400); await p.screenshot({path:`${out}/shots/${slug}-${i}.jpg`,type:'jpeg',quality:55}); } }
      console.log('ok', slug);
    } catch(e){ console.log('ERR', slug, e.message.split('\n')[0]); }
    await p.close();
  };
  const q=[...urls]; await Promise.all(Array.from({length:+(process.env.C||2)},async()=>{ while(q.length) await run(q.shift()); }));
  await b.close();
})();
