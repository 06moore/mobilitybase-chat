const { chromium } = require('playwright'); const fs=require('fs');
const urls=fs.readFileSync(process.argv[2],'utf8').trim().split('\n').filter(u=>u.includes('/post/'));
(async()=>{const b=await chromium.launch();
for(const u of urls){const p=await b.newPage();
 await p.goto(u,{waitUntil:'load',timeout:60000});
 for(let y=0;y<20000;y+=1000){await p.evaluate(y=>scrollTo(0,y),y);await p.waitForTimeout(100);}
 await p.waitForTimeout(1500);
 const r=await p.evaluate(()=>{
  const root=document.querySelector('[data-hook="post-description"]');
  const allowed={H1:'h2',H2:'h2',H3:'h3',H4:'h4',H5:'h4',H6:'h4',P:'p',UL:'ul',OL:'ol',LI:'li',STRONG:'strong',B:'strong',EM:'em',I:'em',A:'a',TABLE:'table',THEAD:'thead',TBODY:'tbody',TR:'tr',TD:'td',TH:'th',BLOCKQUOTE:'blockquote',BR:'br',U:'u'};
  const esc=s=>s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const conv=(n)=>{
    if(n.nodeType===3) return esc(n.textContent);
    if(n.nodeType!==1) return '';
    if(n.tagName==='IMG'){ const s=n.currentSrc||n.src; if(!s||s.startsWith('data:')) return ''; return `<figure><img src="${s.split('/v1/')[0]}" alt="${esc(n.alt||'')}"></figure>`; }
    if(['SCRIPT','STYLE','SVG','BUTTON','svg'].includes(n.tagName)) return '';
    const inner=[...n.childNodes].map(conv).join('');
    const t=allowed[n.tagName];
    if(!t) return inner;
    if(t==='br') return '<br>';
    if(t==='a'){ const h=n.getAttribute('href')||''; return `<a href="${h}">${inner}</a>`; }
    if(['p','h2','h3','h4','li'].includes(t) && !inner.replace(/<br>|&nbsp;|\s|​/g,'') ) return '';
    return `<${t}>${inner}</${t}>`;
  };
  // figures may sit inside wix wrappers outside P; collect top-level
  const html=conv(root).replace(/<p>(\s*<figure>.*?<\/figure>\s*)<\/p>/g,'$1');
  const meta=n=>document.querySelector(`meta[name="${n}"],meta[property="${n}"]`)?.content||'';
  const hero=document.querySelector('[data-hook="post-hero-image"] img');
  return {url:location.pathname, title:document.querySelector('[data-hook="post-title"]')?.innerText, seo_title:document.title, desc:meta('description'), og:meta('og:image'), hero: hero&&(hero.currentSrc||hero.src).split('/v1/')[0], ld:[...document.querySelectorAll('script[type="application/ld+json"]')].map(s=>s.textContent), html};
 });
 fs.writeFileSync(process.argv[3]+'/'+r.url.split('/').pop()+'.json',JSON.stringify(r,null,1)); console.log('ok',r.url,r.html.length); await p.close();}
await b.close();})();
