"""Builds a static, clickable preview of the local dev site (forms disabled) for sharing.
Usage: python3 tools/dev/build-preview-snapshot.py <out-dir>   (dev site must be running on :8080)"""
import re, os, sys, urllib.request, collections, html
BASE='http://127.0.0.1:8080'; OUT=sys.argv[1]; LIMIT=120
def get(u):
    with urllib.request.urlopen(urllib.request.Request(u, headers={'User-Agent':'Mozilla/5.0 snapshot'})) as r: return r.read(), r.headers.get('Content-Type','')
def page_file(path):
    path=path.split('?')[0].split('#')[0]
    p=path.strip('/')
    return 'index.html' if not p else p.replace('/','--')+'.html'
skip=re.compile(r'^/(wp-admin|wp-json|wp-login|feeds|employer/(dashboard|sign-in)|email-preferences|wp-sitemap|xmlrpc|.*\.(xml|txt)$)')
seen=collections.OrderedDict(); q=['/']
priority=['/cookie-policy/','/privacy-policy/','/terms/','/advertising-agreement/','/refund-policy/','/vacancy-authorisation/','/content-licence/','/disclaimer/','/career-guides/loler-inspector-career-guide/','/career-guides/lift-engineering-apprenticeships/','/career-guides/career-change-into-lift-engineering/','/stairlift-installation-jobs/','/jobs/','/engineering-sectors/','/for-employers/','/advertise-a-vacancy/','/about-us/','/contact/','/job-alerts/','/career-guides/','/jobs/sector/stairlifts/','/jobs/role/loler-inspector/','/employer/register/','/employer/submit-vacancy/','/companies/demo-stairlifts-ltd/','/career-guides/how-to-become-a-stairlift-engineer/','/career-guides/lift-engineer-salary-guide/','/stairlift-service-engineer-jobs/','/lift-engineer-jobs-kent/','/privacy-policy/','/terms/']
q+=priority
assets=set()
while q and len(seen)<LIMIT:
    path=q.pop(0).split('?')[0].split('#')[0]
    if not path.endswith('/'): path+='/'
    if path in seen or skip.match(path): continue
    try: body,ct=get(BASE+path)
    except Exception as e: continue
    if 'html' not in ct: continue
    h=body.decode('utf-8')
    seen[path]=h
    for m in re.finditer(r'href=["\'](?:http://127\.0\.0\.1:8080)?(/[^"\'#?]*)', h):
        p=m.group(1)
        if not re.search(r'\.(css|js|png|svg|woff2|ico|xml)$',p): q.append(p)
for path,h in seen.items():
    for m in re.finditer(r'(?:href|src)=["\'](?:http://127\.0\.0\.1:8080)?(/wp-(?:content|includes)/[^"\'?]+)', h): assets.add(m.group(1))
# assets + fonts referenced in css
for a in list(assets):
    if a.endswith('.css'):
        css=get(BASE+a)[0].decode()
        for m in re.finditer(r'url\(([^)]+)\)', css):
            u=m.group(1).strip('\'"')
            if u.startswith('data:'): continue
            assets.add(os.path.normpath(os.path.dirname(a)+'/'+u))
for a in assets:
    try: data=get(BASE+a)[0]
    except Exception: continue
    fp=OUT+a; os.makedirs(os.path.dirname(fp),exist_ok=True); open(fp,'wb').write(data)
banner='<div style="background:#A7D943;color:#142F40;font:600 13px/1.4 Inter,system-ui,sans-serif;padding:8px 16px;text-align:center">Preview snapshot – demo vacancies are fictional; forms and search are switched off in this preview.</div>'
noform='<div id="lmc-snap-msg" hidden style="position:fixed;left:16px;right:16px;bottom:16px;z-index:999;background:#142F40;color:#fff;font:500 14px/1.4 Inter,system-ui,sans-serif;padding:14px 16px;border-radius:10px;text-align:center">This is a preview snapshot, so forms are switched off. On the real site this would submit.</div><script>document.addEventListener("submit",function(e){e.preventDefault();var m=document.getElementById("lmc-snap-msg");m.hidden=false;clearTimeout(window.__t);window.__t=setTimeout(function(){m.hidden=true},3500)},true);</script>'
noform_old='<script>document.addEventListener("submit",function(e){e.preventDefault();alert("Forms are switched off in this preview snapshot.")},true);</script>'
def rewrite(h):
    def link(m):
        attr,q,url=m.group(1),m.group(2),m.group(3)
        if url.startswith('/wp-content') or url.startswith('/wp-includes'):
            return f'{attr}={q}{url.lstrip("/")}'
        p=url.split('?')[0].split('#')[0]
        if not p.endswith('/') and '.' not in p.split('/')[-1]: p+='/'
        return f'{attr}={q}{page_file(p) if p in seen else "index.html"}'
    h=re.sub(r'(href|src|action)=(["\'])(?:http://127\.0\.0\.1:8080)?(/[^"\']*)', lambda m: link(m)+'', h)
    h=re.sub(r'(href|src|action)=(["\'])(/[^"\']*)', link, h)
    h=re.sub(r'<script[^>]*>[^<]*LMC = [^<]*</script>','',h)
    h=h.replace('http://127.0.0.1:8080','')
    h=re.sub(r'(<body[^>]*>)', r'\1'+banner, h, count=1)
    h=h.replace('</body>', noform+'</body>')
    return h
for path,h in seen.items():
    h=rewrite(h)
    name=page_file(path)
    if name=='index.html':
        head=re.search(r'<head>(.*?)</head>',h,re.S).group(1)
        head=re.sub(r'<meta charset[^>]*>|<meta name="viewport"[^>]*>','',head)
        body=re.search(r'<body[^>]*>(.*)</body>',h,re.S).group(1)
        h=head+'<div class="home">'+body+'</div>'
    open(os.path.join(OUT,name),'w').write(h)
print(len(seen),'pages',len(assets),'assets')
