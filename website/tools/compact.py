import json,glob,os,collections,sys
files=sorted(glob.glob('text/*.json'))
def key(b): return (b['t'], b.get('text') or b.get('src','')[:60] or b.get('href',''))
cnt=collections.Counter()
data={}
for f in files:
    d=json.load(open(f)); data[os.path.basename(f)[:-5]]=d
    cnt.update(set(key(b) for b in d['blocks']))
common={k for k,v in cnt.items() if v>=len(files)*0.7}
os.makedirs('compact',exist_ok=True)
for s,d in data.items():
    out=[f"TITLE: {d['title']}",f"DESC: {d['desc']}",f"OG: {d['ogimg']}"]
    seen=set(); prevtext=None
    for b in d['blocks']:
        if key(b) in common: continue
        t=b['t']
        if t=='a':
            if not b['text']: continue
            line=f"  ->link [{b['text']}]({b['href'].replace('https://www.mobilitybase.co.uk','')})"
        elif t=='img':
            if b['w']<40: continue
            src=b['src'].split('/v1/')[0]
            line=f"[IMG {b['w']}x{b['h']} alt='{b['alt']}'] {src}"
        elif t in('input','textarea','select'):
            line=f"[FIELD {t} type={b.get('type')} name={b.get('name')} ph={b.get('ph')} aria={b.get('aria')} req={b.get('req')}]"
        else:
            if b['text']==prevtext: continue
            prevtext=b['text']
            line=f"<{t} {b['size']}/{b['weight']}> {b['text']}"
        if line in seen: continue
        seen.add(line); out.append(line)
    open(f'compact/{s}.txt','w').write('\n'.join(out))
print(len(common)); 
