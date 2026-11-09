#!/usr/bin/env python3
"""
Lesson cover generator (1600x900 webp) in the blog's cover style.

Usage:
  python3 tools/covers/generate.py <course.json> <lesson.json>... --out themes/kmpr/content/media

course.json: {"course_title": "...", "color": "amber|sky|emerald|violet|rose"}
lesson.json: {"slug", "title", "lesson_order", "is_pillar", "difficulty", "cover_label"?}
Needs Google Chrome and cwebp (brew install webp). Not deployed: lives outside the theme folder.
"""
import html
import json
import os
import subprocess
import sys
import tempfile

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
FONTS = os.path.join(ROOT, 'themes/kmpr/public/fonts/yekanBakh')
LOGO = os.path.join(ROOT, 'themes/kmpr/public/images/kmpr-favicon.svg')
CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
COLORS = {'amber': '#f59e0b', 'sky': '#38bdf8', 'emerald': '#34d399', 'violet': '#a78bfa', 'rose': '#fb7185'}
LEVELS = {'beginner': 'مقدماتی', 'intermediate': 'متوسط', 'advanced': 'پیشرفته'}
FA = str.maketrans('0123456789', '۰۱۲۳۴۵۶۷۸۹')
BOOK = ('<svg width="170" height="170" viewBox="0 0 24 24" fill="none" stroke="{c}" stroke-width="1.6" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M2 4.5c3-1.3 6.5-1.3 10 1v14c-3.5-2.3-7-2.3-10-1z"/>'
        '<path d="M22 4.5c-3-1.3-6.5-1.3-10 1v14c3.5-2.3 7-2.3 10-1z"/></svg>')

TPL = """<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><style>
@font-face{font-family:Y;src:url('file://%(fonts)s/Yekan_Bakh_FaNum_07_Heavy.woff2');font-weight:900}
@font-face{font-family:Y;src:url('file://%(fonts)s/Yekan_Bakh_FaNum_06_Bold.woff2');font-weight:700}
@font-face{font-family:Y;src:url('file://%(fonts)s/Yekan_Bakh_FaNum_05_Medium.woff2');font-weight:500}
*{margin:0;box-sizing:border-box}
html,body{width:1600px;height:900px;overflow:hidden}
body{font-family:Y,sans-serif;color:#fff;background:#070b16;position:relative}
.bg{position:absolute;inset:0;background:
 radial-gradient(900px 600px at 0%% 0%%,%(c)s22,transparent 60%%),
 radial-gradient(800px 500px at 100%% 100%%,#f59e0b14,transparent 60%%),
 linear-gradient(160deg,#0b1224 0%%,#070b16 60%%,#0a0d18 100%%)}
.grid{position:absolute;inset:0;opacity:.35;background-image:linear-gradient(#ffffff0a 1px,transparent 1px),linear-gradient(90deg,#ffffff0a 1px,transparent 1px);background-size:64px 64px}
.card{position:absolute;inset:36px;border:1px solid #ffffff14;border-radius:44px}
.kicker{position:absolute;top:110px;right:110px;display:flex;align-items:center;gap:20px;color:%(c)s;font-weight:900;font-size:30px}
.kicker i{display:block;width:56px;height:2px;background:%(c)s}
.pills{position:absolute;top:183px;right:110px;display:flex;gap:14px}
.pill{height:58px;padding:0 26px;border-radius:30px;display:flex;align-items:center;gap:12px;font-weight:700;font-size:23px;border:1.5px solid #ffffff22;background:#ffffff08;color:#e2e8f0}
.pill.on{border-color:%(c)s;background:%(c)s1f;color:#fff}
.pill.on b{width:9px;height:9px;border-radius:50%%;background:%(c)s}
h1{position:absolute;right:110px;top:300px;width:820px;height:360px;display:flex;align-items:center;font-weight:900;font-size:%(fs)spx;line-height:1.5;text-align:right}
.ring{position:absolute;left:150px;top:190px;width:520px;height:520px;border-radius:50%%;border:1px solid %(c)s26;display:flex;align-items:center;justify-content:center}
.ring::before{content:'';position:absolute;inset:70px;border-radius:50%%;border:1px solid %(c)s40}
.ring::after{content:'';position:absolute;inset:140px;border-radius:50%%;border:1px solid %(c)s1c}
.num{position:relative;font-weight:900;font-size:300px;line-height:1;color:transparent;-webkit-text-stroke:5px %(c)s;margin-top:-30px}
.logo{position:absolute;left:115px;bottom:115px;direction:ltr;display:flex;align-items:center;gap:18px;font-family:system-ui,sans-serif;font-weight:800;font-size:32px;letter-spacing:5px}
.logo img{width:50px;height:50px}.logo em{color:#f59e0b;font-style:normal}
.by{position:absolute;right:130px;bottom:120px;display:flex;align-items:center;gap:14px;font-weight:500;font-size:24px;color:#cbd5e1}
.by b{width:8px;height:8px;border-radius:50%%;background:#f59e0b}
.line{position:absolute;right:110px;bottom:36px;width:230px;height:3px;background:linear-gradient(90deg,transparent,#f59e0b)}
</style></head><body><div class="bg"></div><div class="grid"></div><div class="card"></div>
<div class="kicker"><i></i>%(course)s</div>
<div class="pills"><span class="pill on"><b></b>%(p1)s</span><span class="pill">%(p2)s</span></div>
<h1><span>%(title)s</span></h1>
<div class="ring">%(mark)s</div>
<div class="logo"><img src="file://%(logo)s" alt=""><span>KMPR<em>.</em></span></div>
<div class="by"><b></b>کوروش محمدپور · kmpr.ir</div><div class="line"></div>
</body></html>"""


def render(course, lesson, total, out_dir):
    c = COLORS.get(course.get('color'), COLORS['amber'])
    title = lesson.get('cover_label') or lesson['title']
    if lesson.get('is_pillar'):
        p1, p2, mark = 'راهنمای جامع دوره', f'{total} درس'.translate(FA), BOOK.format(c=c)
    else:
        n = int(lesson['lesson_order'])
        p1 = f'درس {n} از {total}'.translate(FA)
        p2 = LEVELS.get(lesson.get('difficulty'), 'آموزشی')
        mark = f'<span class="num">{str(n).translate(FA)}</span>'
    fs = 76 if len(title) <= 34 else 66 if len(title) <= 48 else 58
    page = TPL % {'fonts': FONTS, 'logo': LOGO, 'c': c, 'course': html.escape(course['course_title']),
                  'p1': p1, 'p2': html.escape(p2), 'title': html.escape(title), 'mark': mark, 'fs': fs}
    with tempfile.TemporaryDirectory() as tmp:
        src, png = os.path.join(tmp, 'c.html'), os.path.join(tmp, 'c.png')
        open(src, 'w', encoding='utf-8').write(page)
        subprocess.run([CHROME, '--headless=new', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=1',
                        '--allow-file-access-from-files', '--virtual-time-budget=2000', '--window-size=1600,900',
                        f'--screenshot={png}', f'file://{src}'], check=True, capture_output=True)
        out = os.path.join(out_dir, lesson['slug'] + '.webp')
        subprocess.run(['cwebp', '-quiet', '-q', '82', png, '-o', out], check=True)
    return out


def main(argv):
    out_dir = argv[argv.index('--out') + 1] if '--out' in argv else '.'
    files = [a for a in argv if a.endswith('.json')]
    course = json.load(open(files[0], encoding='utf-8'))
    lessons = [json.load(open(f, encoding='utf-8')) for f in files[1:]]
    total = sum(1 for l in lessons if not l.get('is_pillar'))
    os.makedirs(out_dir, exist_ok=True)
    for l in lessons:
        print(render(course, l, total, out_dir))


if __name__ == '__main__':
    main(sys.argv[1:])
