#!/usr/bin/env python3
"""Turn src/<order>-<slug>.html + meta.py into the source JSON files that tools/content/build-pack.py reads.

- Escapes everything inside <pre><code>…</code></pre> (code is written raw in the .html files).
- Banners: 1st before the 3rd <h2>; 2nd before the last-but-one <h2> when the lesson has 6+ <h2> (AGENTS.md §6).
- Validates Yoast lengths and that every internal /blog/<slug>/ link exists (this course or the live site list).
Usage: python3 assemble.py [--known slug1,slug2,...]
"""
import html
import json
import os
import re
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from meta import COURSE, LESSONS  # noqa: E402

known = set(sys.argv[sys.argv.index('--known') + 1].split(',')) if '--known' in sys.argv else set()
known |= {l['slug'] for l in LESSONS}
problems = []

for l in LESSONS:
    src = os.path.join(HERE, 'src', f"{l['lesson_order']}-{l['slug']}.html")
    raw = open(src, encoding='utf-8').read()
    content = re.sub(r'<pre><code>(.*?)</code></pre>', lambda m: '<pre><code>' + html.escape(m.group(1), quote=False) + '</code></pre>', raw, flags=re.S)
    h2 = content.count('<h2>')
    banners = [{'type': l['banners'][0][0], 'item': l['banners'][0][1], 'before_h2': 3}]
    if h2 >= 6 and len(l['banners']) > 1:
        banners.append({'type': l['banners'][1][0], 'item': l['banners'][1][1], 'before_h2': h2 - 1})

    # checks
    if len(l['seo_title']) > 60: problems.append(f"{l['slug']}: seo_title {len(l['seo_title'])} > 60")
    if not 130 <= len(l['seo_desc']) <= 155: problems.append(f"{l['slug']}: seo_desc {len(l['seo_desc'])} not in 130–155")
    if l['focuskw'] not in content: problems.append(f"{l['slug']}: focus keyword missing from content")
    for s in re.findall(r'href="/blog/([^/"]+)/"', content):
        if s not in known: problems.append(f"{l['slug']}: unknown link /blog/{s}/")
    if not l.get('is_pillar') and '/blog/nextjs-business-guide/' not in content: problems.append(f"{l['slug']}: no pillar link")
    if l.get('is_pillar'):
        for o in LESSONS[1:]:
            if f"/blog/{o['slug']}/" not in content: problems.append(f"pillar does not link {o['slug']}")
    if '<pre><code>' in raw and re.search(r'<pre><code>[^<]*&lt;', raw): problems.append(f"{l['slug']}: code already escaped in source")

    out = {k: v for k, v in l.items() if k != 'banners'}
    out['content'] = content
    out['banners'] = banners
    json.dump(out, open(os.path.join(HERE, f"{l['lesson_order']}-{l['slug']}.json"), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print(f"{l['lesson_order']} {l['slug']}: {len(re.sub('<[^>]+>', ' ', content).split())} words · {h2} h2 · banners {[(b['item'], b['before_h2']) for b in banners]}")

json.dump(COURSE, open(os.path.join(HERE, 'course.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
if len(COURSE['seo_title']) > 60: problems.append(f"course seo_title {len(COURSE['seo_title'])}")
if not 130 <= len(COURSE['seo_desc']) <= 155: problems.append(f"course seo_desc {len(COURSE['seo_desc'])}")
print('\n'.join(problems) or 'all checks passed')
sys.exit(1 if problems else 0)
