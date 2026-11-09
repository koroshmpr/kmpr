#!/usr/bin/env python3
"""
Build a theme content pack (themes/kmpr/content/packs/course-<slug>.json) from a course source folder,
and render its lesson covers into themes/kmpr/content/media/.

Source folder:
  course.json            {"slug","name","course_title","course_intro","color","description","seo_title","seo_desc"}
  <order>-<slug>.json    {"slug","title","seo_title","seo_desc","focuskw","excerpt","tldr","takeaways":[],
                          "faq":[{"question","answer"}],"difficulty","lesson_order","is_pillar","cover_label","cover_alt","content",
                          "banners":[{"type":"service|portfolio|game|course|ad","item":"<slug>","before_h2":3}]}
  Banners: 1–2 per lesson ([kmpr_banner], see AGENTS.md §6) — inserted on the server via "inject", so they survive re-applies.

Usage: python3 tools/content/build-pack.py <source dir> --date 2026-11-02T08:00 [--no-covers]
The pack is applied on the server by inc/content-packs.php after the next deploy (upsert by slug).
"""
import datetime as dt
import glob
import json
import os
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
THEME = os.path.join(ROOT, 'themes/kmpr')


def main(argv):
    src = argv[0]
    base = dt.datetime.fromisoformat(argv[argv.index('--date') + 1]) if '--date' in argv else dt.datetime.utcnow()
    course = json.load(open(os.path.join(src, 'course.json'), encoding='utf-8'))
    files = sorted(glob.glob(os.path.join(src, '[0-9]*-*.json')), key=lambda f: int(os.path.basename(f).split('-')[0]))
    lessons = [json.load(open(f, encoding='utf-8')) for f in files]
    slug = course['slug']

    pack = {
        'batch': f'course-{slug}',
        'terms': [{
            'taxonomy': 'category', 'match': slug, 'slug': slug, 'name': course['name'],
            'description': course['description'],
            'acf': {'course_title': course['course_title'], 'course_intro': course['course_intro'], 'course_color': course['color']},
            'yoast': {'title': course['seo_title'], 'desc': course['seo_desc']},
        }],
        'media': [{'file': f"{l['slug']}.webp", 'alt': l['cover_alt'], 'title': l['title']} for l in lessons],
        'posts': [],
    }
    for i, l in enumerate(lessons):
        pack['posts'].append({
            'type': 'post', 'slug': l['slug'], 'status': 'publish',
            'title': l['title'], 'excerpt': l['excerpt'], 'content': l['content'].strip(),
            'date': (base + dt.timedelta(hours=6 * i)).strftime('%Y-%m-%d %H:%M:%S'),
            'terms': {'category': [slug]},
            'meta': {'_yoast_wpseo_primary_category': f'term:category:{slug}'},
            'acf': {
                'is_pillar': bool(l.get('is_pillar')), 'lesson_order': int(l['lesson_order']), 'difficulty': l['difficulty'],
                'tldr': l['tldr'], 'key_takeaways': '\n'.join(l['takeaways']), 'post_faq': l['faq'],
            },
            'yoast': {'title': l['seo_title'], 'desc': l['seo_desc'], 'focuskw': l['focuskw']},
            'thumbnail': f"media:{l['slug']}.webp",
            'inject': [{'html': f'[kmpr_banner type="{b["type"]}" item="{b["item"]}"]', 'before_h2': int(b.get('before_h2', 3))}
                       for b in l.get('banners', [])],
        })

    # Covers first: the site applies a pack as soon as its JSON appears, so its media must already exist.
    if '--no-covers' not in argv:
        subprocess.run([sys.executable, os.path.join(ROOT, 'tools/covers/generate.py'), os.path.join(src, 'course.json'),
                        *files, '--out', os.path.join(THEME, 'content/media')], check=True)

    out = os.path.join(THEME, 'content/packs', f'course-{slug}.json')
    os.makedirs(os.path.dirname(out), exist_ok=True)
    tmp = out + '.tmp'
    with open(tmp, 'w', encoding='utf-8') as fh:
        json.dump(pack, fh, ensure_ascii=False, indent=1)
    os.replace(tmp, out)  # atomic: never a half-written pack
    print(out, os.path.getsize(out), 'bytes')



if __name__ == '__main__':
    main(sys.argv[1:])
