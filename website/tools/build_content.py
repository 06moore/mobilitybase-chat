#!/usr/bin/env python3
"""Convert the crawl of the old Wix site into content files the WordPress theme imports.

Usage: python3 -I build_content.py <crawl_dir> <raw_image_dir> <theme_dir>

  crawl_dir/text/*.json   page block dumps from crawl.js
  crawl_dir/posts/*.json  blog post dumps from posts.js
  raw_image_dir/*         original images downloaded from static.wixstatic.com

Writes theme_dir/content/{pages,posts}/*.html, content/manifest.json and
optimised images into theme_dir/assets/img/content/.
"""
import collections, html, json, os, re, sys

from PIL import Image

CRAWL, RAW, THEME = sys.argv[1:4]
OUT = os.path.join(THEME, 'content')
IMGDIR = os.path.join(THEME, 'assets', 'img', 'content')
os.makedirs(os.path.join(OUT, 'pages'), exist_ok=True)
os.makedirs(os.path.join(OUT, 'posts'), exist_ok=True)
os.makedirs(IMGDIR, exist_ok=True)

# Pages that have a hand-built template in the theme (content file still written for reference).
CUSTOM = {
    'home', 'products', 'services', 'aftercare-warranty', 'contact', 'book-your-visit', 'blog',
    'straight-stairlift-range', 'curved-stairlift-range', 'homelift-range', 'platform-lift-range',
    'ceiling-hoist-range', 'straight-stairlift-warranty', 'curved-stairlift-warranty',
    'homelift-warranty', 'ceiling-hoist-warranty', 'inquiry-services-page',
}

BAD_ALT = re.compile(r'(\.png|\.jpe?g|\.webp|chatgpt|gemini|hero for wix|^image|^$)', re.I)


def esc(s):
    return html.escape(s, quote=True)


# ---------------------------------------------------------------- images
_img_cache = {}


def local_image(url):
    """Optimise a wixstatic image into the theme; return its theme-relative name."""
    url = url.split('/v1/')[0]
    if url in _img_cache:
        return _img_cache[url]
    fname = url.rsplit('/', 1)[-1]
    src = os.path.join(RAW, fname)
    if not os.path.exists(src):
        _img_cache[url] = None
        return None
    base = re.sub(r'~mv2.*$', '', fname).replace('b55e4e_', '')[:16]
    im = Image.open(src)
    has_alpha = im.mode in ('RGBA', 'LA', 'P')
    im = im.convert('RGBA' if has_alpha else 'RGB')
    im.thumbnail((1400, 1400))
    name = base + '.webp'
    im.save(os.path.join(IMGDIR, name), 'WEBP', quality=80, method=6)
    _img_cache[url] = (name, im.size)
    return _img_cache[url]


def img_tag(url, alt, cls='mb-figure'):
    li = local_image(url)
    if not li:
        return ''
    name, (w, h) = li
    return (f'<figure class="{cls}"><img src="{{{{IMG}}}}/{name}" alt="{esc(alt)}" '
            f'width="{w}" height="{h}" loading="lazy" decoding="async"></figure>')


# ---------------------------------------------------------------- pages
def slug_of(path):
    return os.path.basename(path)[:-5]


files = sorted(f for f in os.listdir(os.path.join(CRAWL, 'text')) if f.endswith('.json') and not f.startswith('post__'))
data = {slug_of(f): json.load(open(os.path.join(CRAWL, 'text', f))) for f in files}


def key(b):
    return (b['t'], b.get('text') or (b.get('src') or '')[:60] or b.get('href', ''))


cnt = collections.Counter()
for d in data.values():
    cnt.update(set(key(b) for b in d['blocks']))
COMMON = {k for k, v in cnt.items() if v >= len(data) * 0.7}

SITE = 'https://www.mobilitybase.co.uk'


def rel(href):
    href = href.replace(SITE, '') or '/'
    return href


def linkify(text, anchors):
    """Return HTML for a text line, wrapping it in a link if an anchor covers it."""
    t = text.strip()
    for a in anchors:
        at = a['text'].strip()
        if at and (t == at or (len(t) > 3 and t in at)) and a['href']:
            return f'<a href="{esc(rel(a["href"]))}">{esc(t)}</a>'
    return esc(t)


def clean(t):
    return t.replace('​', '').replace('\xa0', ' ').strip()


def faqs_from_ld(lds):
    for raw in lds:
        try:
            j = json.loads(raw)
        except Exception:
            continue
        if isinstance(j, dict) and j.get('@type') == 'FAQPage':
            return [(q['name'], q['acceptedAnswer']['text']) for q in j.get('mainEntity', [])]
    return []


def faq_html(faqs):
    items = ''.join(f'<details class="mb-faq"><summary>{esc(q)}</summary><p>{esc(a)}</p></details>' for q, a in faqs)
    return f'<div class="mb-faqs">{items}</div>'


def convert(slug, d):
    faqs = faqs_from_ld(d.get('ld', []))
    blocks = [b for b in d['blocks'] if key(b) not in COMMON]
    anchors = [b for b in blocks if b['t'] == 'a' and b.get('text')]
    texts = [clean(b.get('text', '')) for b in blocks if b['t'] not in ('a', 'img')]
    h1 = next((clean(b['text']) for b in blocks if b['t'] == 'h1'), d['title'].split('|')[0].strip())
    out, seen, used_h1 = [], set(), False
    form_pending = None

    def flush_form():
        nonlocal form_pending
        if form_pending is not None:
            f = form_pending
            attrs = f'fields="{",".join(f["fields"])}"'
            if f['options']:
                attrs += f' options="{"|".join(f["options"])}" options_label="What type of stairlift do you have?"'
            attrs += f' title="{esc(f["title"])}"'
            out.append(f'[mb_form {attrs}]')
            form_pending = None

    last_heading = h1
    for b in blocks:
        t = b['t']
        if t == 'button' and form_pending is None:
            continue  # accordion toggles (FAQ questions) — answers come from the FAQ schema below
        if t in ('label', 'input', 'textarea', 'select', 'button'):
            if form_pending is None:
                form_pending = {'fields': [], 'options': [], 'title': last_heading}
            if t == 'label':
                lab = clean(b['text']).lower()
                for k, fld in (('name', 'name'), ('phone', 'phone'), ('email', 'email'), ('post', 'postcode'), ('message', 'message')):
                    if k in lab and fld not in form_pending['fields']:
                        form_pending['fields'].append(fld)
            elif t == 'input' and b.get('type') == 'radio':
                form_pending['options'].append(b.get('aria') or '')
            continue
        if t == 'a':
            txt = clean(b['text'])
            if not txt or b['href'].startswith('tel:') and any(txt in x for x in texts):
                continue
            if any(txt and txt in x for x in texts):
                continue
            if txt.upper() in ('SUBMIT',):
                continue
            flush_form()
            line = f'<p class="mb-cta"><a class="mb-btn" href="{esc(rel(b["href"]))}">{esc(txt)}</a></p>'
            if line not in seen:
                seen.add(line)
                out.append(line)
            continue
        if t == 'img':
            if b['w'] < 150 or b['h'] < 100:
                continue
            alt = b.get('alt') or ''
            if BAD_ALT.search(alt):
                alt = h1
            tag = img_tag(b['src'], alt, 'mb-figure mb-figure--icon' if b['w'] <= 200 else 'mb-figure')
            if tag and tag not in seen:
                flush_form()
                seen.add(tag)
                out.append(tag)
            continue
        txt = clean(b.get('text', ''))
        if not txt or txt in seen:
            continue
        if form_pending is not None and txt.startswith('Prefer to speak'):
            flush_form()
        else:
            flush_form()
        seen.add(txt)
        size = float(b.get('size', '16px').rstrip('px') or 16)
        if (t == 'li' or b.get('li')) and t not in ('h1', 'h2'):
            if out and out[-1].endswith('</ul>') and out[-1].startswith('<ul>'):
                out[-1] = out[-1][:-5] + f'<li>{linkify(txt, anchors)}</li></ul>'
            else:
                out.append(f'<ul><li>{linkify(txt, anchors)}</li></ul>')
            continue
        if txt.startswith('Prefer to speak'):
            out.append('<p class="mb-call">Prefer to speak to us? Call <a href="tel:02089363060">0208 936 3060</a></p>')
            continue
        if t == 'h1' and not used_h1:
            used_h1 = True
            out.append(f'<h1>{esc(txt)}</h1>')
            last_heading = txt
            continue
        if t in ('h1', 'h2') or (t == 'p' and size >= 24 and len(txt) < 120):
            lines = [l.strip() for l in txt.split('\n') if l.strip()]
            if len(lines) > 1 and t == 'h2' and size < 40:
                out.append('<ul class="mb-ticks">' + ''.join(f'<li>{esc(l)}</li>' for l in lines) + '</ul>')
                continue
            out.append(f'<h2>{esc(" ".join(lines))}</h2>')
            if 'FAQ' in txt and faqs:
                out.append(faq_html(faqs))
                faqs.clear()
            last_heading = txt
            continue
        if t in ('h3', 'h4', 'h5', 'h6') or (t == 'p' and size >= 18 and len(txt) < 70 and not txt.endswith('.') and '\n' not in txt and not txt.startswith('•')):
            out.append(f'<h3>{esc(txt)}</h3>')
            last_heading = txt
            continue
        lines = [l.strip() for l in txt.split('\n') if l.strip()]
        bullets = [l for l in lines if l.startswith('•') or l.startswith('- ')]
        if bullets and len(bullets) >= len(lines) - 1:
            pre = [l for l in lines if l not in bullets]
            for p in pre:
                out.append(f'<p>{linkify(p, anchors)}</p>')
            items = ''.join(f'<li>{linkify(l.lstrip("•- ").strip(), anchors)}</li>' for l in bullets)
            out.append(f'<ul>{items}</ul>')
            continue
        if len(lines) > 1:
            out.append('<p>' + '<br>'.join(linkify(l, anchors) for l in lines) + '</p>')
        else:
            out.append(f'<p>{linkify(txt, anchors)}</p>')
    flush_form()
    return h1, '\n'.join(out)


def custom_ld(lds):
    """Keep JSON-LD the owner added by hand; drop what Wix generates automatically."""
    keep = []
    for s in lds:
        try:
            j = json.loads(s)
        except Exception:
            continue
        typ = j.get('@type') if isinstance(j, dict) else None
        if typ in ('WebSite', 'BlogPosting', 'Organization') and 'wix' in s.lower():
            continue
        if typ == 'BlogPosting':  # Wix auto-generates this; the theme outputs its own.
            continue
        keep.append(j)
    return keep


NAV_TITLES = {
    'home': 'Home', 'about-us': 'About us', 'services': 'Services', 'aftercare-warranty': 'Aftercare & Warranty',
    'products': 'Products', 'contact': 'Contact', 'blog': 'Articles',
}

manifest = {'pages': [], 'posts': []}
for slug, d in sorted(data.items()):
    h1, body = convert(slug, d)
    title = NAV_TITLES.get(slug) or re.sub(r'\s*\|.*$', '', d['title']).strip()
    template = 'custom' if slug in CUSTOM else 'content'
    if slug.startswith('stairlift-installation-') or slug.startswith('stairlift-removal-'):
        template = 'location'
    with open(os.path.join(OUT, 'pages', slug + '.html'), 'w') as f:
        f.write(body + '\n')
    manifest['pages'].append({
        'slug': '' if slug == 'home' else slug, 'file': slug + '.html', 'title': title, 'h1': h1,
        'seo_title': d['title'], 'seo_desc': d['desc'], 'template': template,
        'schema': custom_ld(d.get('ld', [])),
    })

# ---------------------------------------------------------------- posts
for f in sorted(os.listdir(os.path.join(CRAWL, 'posts'))):
    p = json.load(open(os.path.join(CRAWL, 'posts', f)))
    slug = p['url'].rsplit('/', 1)[-1]
    body = p['html']
    body = re.sub(r'^<section>|</section>$', '', body)

    def repl(m):
        tag = img_tag(m.group(1), html.unescape(m.group(2)) or p['title'], 'mb-figure')
        return tag

    body = re.sub(r'<figure><img src="([^"]+)" alt="([^"]*)"></figure>', repl, body)
    body = re.sub(r'href="https://www\.mobilitybase\.co\.uk', 'href="', body)
    body = body.replace('href=""', 'href="/"')
    body = re.sub(r'<(p|h2|h3|li)>\s*</\1>', '', body)
    # Wix sometimes split a word across two paragraphs (e.g. 'capacit' / 'ies').
    body = re.sub(r'([a-z])</p><p>([a-z])', r'\1\2', body)
    published = modified = None
    for s in p['ld']:
        try:
            j = json.loads(s)
        except Exception:
            continue
        if isinstance(j, dict) and j.get('@type') == 'BlogPosting':
            published, modified = j.get('datePublished'), j.get('dateModified')
    og = p.get('og') or ''
    featured = local_image(og)[0] if og and local_image(og) else None
    with open(os.path.join(OUT, 'posts', slug + '.html'), 'w') as fh:
        fh.write(body + '\n')
    manifest['posts'].append({
        'slug': slug, 'file': slug + '.html', 'title': p['title'], 'seo_title': p['seo_title'],
        'seo_desc': p['desc'], 'date': published, 'modified': modified, 'featured': featured,
        'schema': custom_ld(p['ld']),
    })

# Images the hand-built templates use, keyed by their wix media id.
template_imgs = {}
for slug in CUSTOM:
    for b in data.get(slug, {}).get('blocks', []):
        if b['t'] == 'img' and 'wixstatic' in (b.get('src') or '') and b['w'] >= 60:
            li = local_image(b['src'])
            if li:
                template_imgs[b['src'].split('/v1/')[0].rsplit('/', 1)[-1]] = li[0]
manifest['images'] = template_imgs

with open(os.path.join(OUT, 'manifest.json'), 'w') as f:
    json.dump(manifest, f, indent=1, ensure_ascii=False)
print(len(manifest['pages']), 'pages,', len(manifest['posts']), 'posts,', len(os.listdir(IMGDIR)), 'images')
