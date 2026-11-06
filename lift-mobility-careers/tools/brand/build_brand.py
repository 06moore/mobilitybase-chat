"""
Builds the Lift and Mobility Careers brand assets as outlined SVG (text converted to paths,
so files render identically everywhere) into brand/ and the theme's assets/img/.
PNG versions are rendered afterwards by tools/brand/render_png.js.

Requires: pip install fonttools brotli
"""
import os, pathlib
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen

ROOT = pathlib.Path(__file__).resolve().parents[2]
FONTS = ROOT / "wordpress/wp-content/themes/lmc/assets/fonts"
OUT = ROOT / "brand"
THEME_IMG = ROOT / "wordpress/wp-content/themes/lmc/assets/img"
OUT.mkdir(exist_ok=True); THEME_IMG.mkdir(parents=True, exist_ok=True)

NAVY, LIME, WHITE, GREY = "#142F40", "#A7D943", "#FFFFFF", "#F4F6F7"
LIME_DARK = "#4F7A0F"  # lime shade for small text on white (contrast ≥ 4.5:1)

def text_path(text, font_file, size, x=0, y=0, tracking=0):
    """Return (svg path d, width) for text set at baseline y."""
    f = TTFont(str(FONTS / font_file))
    gs = f.getGlyphSet(); cmap = f.getBestCmap(); upm = f["head"].unitsPerEm
    hmtx = f["hmtx"]; s = size / upm; cx = x; d = []
    for ch in text:
        g = cmap.get(ord(ch))
        if g is None: continue
        pen = SVGPathPen(gs)
        gs[g].draw(TransformPen(pen, (s, 0, 0, -s, cx, y)))
        d.append(pen.getCommands())
        cx += hmtx[g][0] * s + tracking
    return " ".join(d), cx - x - (tracking if text else 0)

def mark(x=0, y=0, size=64, bg=NAVY, rail=WHITE, accent=LIME, radius=14):
    """The symbol: two lift guide rails with a platform rising between them."""
    k = size / 64
    t = lambda v: v * k
    return (f'<g transform="translate({x},{y})">'
            f'<rect width="{t(64)}" height="{t(64)}" rx="{t(radius)}" fill="{bg}"/>'
            f'<path d="M{t(19)} {t(13)}V{t(51)}M{t(45)} {t(13)}V{t(51)}" stroke="{rail}" stroke-width="{t(4.5)}" stroke-linecap="round"/>'
            f'<path d="M{t(25.5)} {t(33)}L{t(32)} {t(25)}L{t(38.5)} {t(33)}" fill="none" stroke="{accent}" stroke-width="{t(5)}" stroke-linecap="round" stroke-linejoin="round"/>'
            f'<path d="M{t(25.5)} {t(42)}H{t(38.5)}" stroke="{accent}" stroke-width="{t(5)}" stroke-linecap="round"/>'
            f'</g>')

def svg(w, h, body, title):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}" role="img" aria-label="{title}"><title>{title}</title>{body}</svg>\n'

def horizontal(text_col=NAVY, accent=LIME_DARK, mark_bg=NAVY, rail=WHITE, mark_accent=LIME):
    l1, w1 = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 30, 80, 33)
    l2, w2 = text_path("CAREERS", "manrope-latin-700-normal.woff2", 15.5, 81, 56, tracking=5.2)
    w = int(80 + max(w1, w2) + 6)
    body = mark(0, 0, 64, mark_bg, rail, mark_accent) + f'<path d="{l1}" fill="{text_col}"/><path d="{l2}" fill="{accent}"/>'
    return svg(w, 64, body, "Lift and Mobility Careers")

def stacked(text_col=NAVY, accent=LIME_DARK):
    l1, w1 = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 34, 0, 0)
    l2, w2 = text_path("CAREERS", "manrope-latin-700-normal.woff2", 17, 0, 0, tracking=6)
    W = int(max(w1, w2) + 20); mx = (W - 96) / 2
    a1, _ = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 34, (W - w1) / 2, 140)
    a2, _ = text_path("CAREERS", "manrope-latin-700-normal.woff2", 17, (W - w2) / 2, 170, tracking=6)
    body = mark(mx, 0, 96, NAVY if text_col == NAVY else "#1E4258") + f'<path d="{a1}" fill="{text_col}"/><path d="{a2}" fill="{accent}"/>'
    return svg(W, 180, body, "Lift and Mobility Careers")

files = {
    "logo-primary.svg": horizontal(),
    "logo-primary-reversed.svg": horizontal(WHITE, LIME, "#1E4258", WHITE),
    "logo-stacked.svg": stacked(),
    "logo-stacked-reversed.svg": stacked(WHITE, LIME),
    "logo-mono-navy.svg": horizontal(NAVY, NAVY, NAVY, WHITE).replace(LIME, NAVY).replace(f'stroke="{NAVY}" stroke-width="5', f'stroke="{WHITE}" stroke-width="5'),
    "mark.svg": svg(64, 64, mark(), "Lift and Mobility Careers"),
    "mark-reversed.svg": svg(64, 64, mark(0, 0, 64, LIME, NAVY, NAVY), "Lift and Mobility Careers"),
    "favicon.svg": svg(64, 64, mark(0, 0, 64, NAVY, WHITE, LIME, 12), "LMC"),
}
# Social / cover artwork (rendered to PNG later).
def social_profile():
    return svg(400, 400, f'<rect width="400" height="400" fill="{NAVY}"/>' + mark(80, 80, 240, NAVY, WHITE, LIME, 0), "Lift and Mobility Careers")

def cover(w, h, scale, title, left=0.08):
    l1, w1 = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 46 * scale, 0, 0)
    tx = w * left
    a1, _ = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 46 * scale, tx, h * 0.47)
    a2, _ = text_path("CAREERS", "manrope-latin-700-normal.woff2", 22 * scale, tx + 2, h * 0.47 + 36 * scale, tracking=8 * scale)
    a3, _ = text_path("Connecting mobility engineering talent with opportunity.", "inter-latin-500-normal.woff2", 19 * scale, tx, h * 0.47 + 84 * scale)
    rails = "".join(f'<path d="M{w*0.66 + i*w*0.06} 0V{h}" stroke="#1d4258" stroke-width="{10*scale}"/>' for i in range(6))
    chev = "".join(f'<path d="M{w*0.66 + i*w*0.06 + w*0.012} {h*0.62 - i*h*0.07}l{w*0.018} -{h*0.06} {w*0.018} {h*0.06}" fill="none" stroke="{LIME}" stroke-width="{6*scale}" stroke-linecap="round" stroke-linejoin="round" opacity="{0.35 + i*0.13:.2f}"/>' for i in range(5))
    body = f'<rect width="{w}" height="{h}" fill="{NAVY}"/>{rails}{chev}<path d="{a1}" fill="{WHITE}"/><path d="{a2}" fill="{LIME}"/><path d="{a3}" fill="#c9d6de"/>'
    return svg(w, h, body, title)

files["social-profile.svg"] = social_profile()
files["linkedin-cover.svg"] = cover(1128, 191, 0.72, "LinkedIn cover", 0.26)
files["facebook-cover.svg"] = cover(1640, 624, 1.45, "Facebook cover")
files["og-default.svg"] = cover(1200, 630, 1.25, "Lift and Mobility Careers")

def badge():
    a, _ = text_path("ADVERTISED ON", "manrope-latin-700-normal.woff2", 9.5, 58, 22, tracking=1.4)
    b, _ = text_path("Lift and Mobility", "manrope-latin-800-normal.woff2", 15, 58, 39)
    c, _ = text_path("CAREERS", "manrope-latin-700-normal.woff2", 9.5, 58, 51, tracking=3)
    return svg(220, 60, f'<rect width="220" height="60" rx="8" fill="{NAVY}"/>' + mark(10, 10, 40, "#1E4258", WHITE, LIME, 9) + f'<path d="{a}" fill="#AFC0CA"/><path d="{b}" fill="{WHITE}"/><path d="{c}" fill="{LIME}"/>', "Advertised on Lift and Mobility Careers")

files["badge-advertised.svg"] = badge()

for name, data in files.items():
    (OUT / name).write_text(data)
for name in ["logo-primary.svg", "logo-primary-reversed.svg", "mark.svg", "favicon.svg", "badge-advertised.svg"]:
    (THEME_IMG / name).write_text(files[name])
print("Wrote", len(files), "SVGs to", OUT)
