# -*- coding: utf-8 -*-
"""Theme-aware SVG placeholder illustrations. Every colour is a CSS custom property
(--il-*, --color-*), so the same inline SVG re-colours itself when [data-theme] changes.
Engraving hatch + film grain are applied by CSS (.media::after), not inside the SVG."""
import random, math

def _f(v): return f"style=\"fill:var(--{v})\""
def _s(v): return f"style=\"stroke:var(--{v})\""

def defs_common(u):
    return f'''<linearGradient id="sky{u}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--il-sky1)"/><stop offset="1" style="stop-color:var(--il-sky2)"/></linearGradient>
<radialGradient id="glow{u}"><stop offset="0" style="stop-color:var(--color-glow)" stop-opacity=".85"/><stop offset=".4" style="stop-color:var(--color-glow)" stop-opacity=".3"/><stop offset="1" style="stop-color:var(--color-glow)" stop-opacity="0"/></radialGradient>
<radialGradient id="door{u}" cx="50%" cy="85%" r="75%"><stop offset="0" style="stop-color:var(--color-glow)"/><stop offset=".6" style="stop-color:var(--color-glow)" stop-opacity=".45"/><stop offset="1" style="stop-color:var(--il-wall)" stop-opacity=".2"/></radialGradient>
<pattern id="brick{u}" width="44" height="22" patternUnits="userSpaceOnUse"><rect width="44" height="22" style="fill:var(--il-wall)"/><path d="M0 .5H44M0 11.5H44M22 0V11M0 11V22M44 11V22" style="stroke:var(--il-mortar)" stroke-width="1.4" opacity=".6"/></pattern>
<pattern id="ashlar{u}" width="120" height="92" patternUnits="userSpaceOnUse"><rect width="120" height="92" style="fill:var(--il-wall)"/><path d="M0 .7H120M0 46.7H120M60 0V46M0 46V92M120 46V92" style="stroke:var(--il-mortar)" stroke-width="1.6" opacity=".75"/><rect x="5" y="5" width="50" height="37" style="fill:var(--color-glow)" opacity=".07"/></pattern>
<linearGradient id="floor{u}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:#000" stop-opacity="0"/><stop offset="1" style="stop-color:#000" stop-opacity=".45"/></linearGradient>'''

def wall(u, x, y, w, h):
    """Brick for lampwright/ember, ashlar blocks for ashlar-iron (toggled via CSS .w-brick/.w-ashlar)."""
    return (f'<rect class="w-brick" x="{x}" y="{y}" width="{w}" height="{h}" fill="url(#brick{u})"/>'
            f'<rect class="w-ashlar" x="{x}" y="{y}" width="{w}" height="{h}" fill="url(#ashlar{u})"/>')

def svg_open(vb, label, u, par="xMidYMid slice", cls="ill"):
    return f'<svg class="{cls}" viewBox="{vb}" preserveAspectRatio="{par}" role="img" aria-label="{label}"><defs>{defs_common(u)}</defs>'

def candle(x, y, h=60, u="", glow=True, w=12):
    g = f'<circle cx="{x}" cy="{y-8}" r="{h*1.6:.0f}" fill="url(#glow{u})"/>' if glow else ""
    return (g + f'<rect x="{x-w/2}" y="{y}" width="{w}" height="{h}" rx="2" style="fill:var(--color-board-ink)" opacity=".92"/>'
            f'<path d="M{x} {y-2} q -{w*0.55:.1f} -12 0 -{w*2.1:.1f} q {w*0.55:.1f} 12 0 {w*2.1:.1f}z" style="fill:var(--color-glow)"/>'
            f'<path d="M{x} {y-3} q -2 -5 0 -9 q 2 4 0 9z" fill="#fff" opacity=".7"/>')

def lantern(x, y, u, s=1.0):
    return f'''<g transform="translate({x} {y}) scale({s})"><circle cx="0" cy="60" r="150" fill="url(#glow{u})"/>
<path d="M70 -20H0M70 0C40 0 30 -14 8 -20M30 -20c0 -18 -26 -18 -22 0" fill="none" {_s('il-iron')} stroke-width="5" stroke-linecap="round"/>
<path d="M0 -20V4" {_s('il-iron')} stroke-width="4"/><path d="M-18 12H18L12 4H-12Z M-16 12L-10 90H10L16 12Z" {_f('il-iron')}/>
<path d="M-11 20L-6 82H6L11 20Z" {_f('color-glow')}/><path d="M-14 90H14L8 102H-8Z" {_f('il-iron')}/></g>'''

def cobbles(r, x0, y0, W, H, rows_scale=2.2):
    out = [f'<rect x="{x0}" y="{y0}" width="{W}" height="{H}" {_f("il-cobble-bg")}/>']
    y = y0 + 6; row = 0
    while y < y0 + H:
        h = 6 + row * rows_scale; w = h * 2.1; xx = x0 - w + (row % 2) * w / 2
        while xx < x0 + W:
            ww = w * r.uniform(.8, 1.15)
            out.append(f'<rect x="{xx:.0f}" y="{y:.0f}" width="{ww-3:.0f}" height="{h-2:.0f}" rx="{h/2.4:.0f}" {_f("il-cobble")} opacity="{r.uniform(.55,1):.2f}"/>')
            xx += ww
        y += h; row += 1
    return "".join(out)

# ------------------------------------------------------------------ HERO STREET 16:9
def street(u, label="Hero image placeholder: lantern-lit old-town street at dusk"):
    r = random.Random(7)
    s = [svg_open("0 0 1600 900", label, u, "xMaxYMid slice")]
    s.append(f'<rect width="1600" height="900" fill="url(#sky{u})"/>')
    s.append(f'<circle cx="1180" cy="120" r="34" {_f("color-glow")} opacity=".18"/>')
    x = 420
    while x < 1600:
        w = r.randint(80, 140); h = r.randint(220, 380); top = 650 - h
        s.append(f'<path d="M{x} 660V{top+34}L{x+w/2:.0f} {top}L{x+w} {top+34}V660Z" {_f("il-stone-far")}/>')
        for wy in range(top + 56, 620, 42):
            for wx in range(x + 16, x + w - 20, 28):
                if r.random() < .42: s.append(f'<rect x="{wx}" y="{wy}" width="10" height="17" rx="5" {_f("color-glow")} opacity="{r.choice([.3,.5,.75])}"/>')
        x += w + r.randint(-6, 10)
    s.append(f'<path d="M880 660V250L898 150L916 250V660Z" {_f("il-stone-far")}/><circle cx="898" cy="290" r="13" fill="none" {_s("color-glow")} stroke-width="2" opacity=".6"/>')
    # near building
    s.append(wall(u, 1130, 0, 470, 700))
    s.append(f'<rect x="1120" y="0" width="18" height="700" {_f("il-stone")}/><rect x="1130" y="400" width="470" height="16" {_f("il-stone")}/>')
    s.append(f'<path d="M1250 700V560A82 82 0 0 1 1414 560V700Z" {_f("il-stone")}/><path d="M1264 700V562A68 68 0 0 1 1400 562V700Z" fill="url(#door{u})"/>')
    s.append(f'<path d="M1332 494V700M1264 590H1400" {_s("il-iron")} stroke-width="3"/>')
    for i in range(7):
        a = math.pi * (i + 1) / 8
        s.append(f'<path d="M1332 562L{1332-68*math.cos(a):.1f} {562-68*math.sin(a):.1f}" {_s("il-iron")} stroke-width="1.6"/>')
    for wx in (1185, 1450):
        s.append(f'<rect x="{wx}" y="150" width="84" height="150" rx="42" {_f("il-stone")}/><rect x="{wx+9}" y="159" width="66" height="134" rx="33" {_f("color-glow")} opacity=".55"/><path d="M{wx+42} 159V293M{wx+9} 222H{wx+75}" {_s("il-iron")} stroke-width="3"/>')
    # lampwright + ashlar: wrought-iron lantern ; ember: hanging pub sign
    s.append(f'<g class="v-lantern">{lantern(1050, 300, u, 1.15)}</g>')
    s.append(f'''<g class="v-sign"><circle cx="1030" cy="360" r="230" fill="url(#glow{u})"/>
<path d="M1130 220H930M1130 250C1070 250 1050 232 980 220M1000 220c0 -26 -34 -26 -30 0" fill="none" {_s('il-iron')} stroke-width="6" stroke-linecap="round"/>
<path d="M950 220V252M1110 220V252" {_s('il-iron')} stroke-width="3" stroke-dasharray="5 3"/>
<rect x="926" y="252" width="208" height="150" rx="10" style="fill:var(--color-board);stroke:var(--color-board-frame)" stroke-width="7"/>
<rect x="940" y="266" width="180" height="122" rx="5" fill="none" {_s('color-glow')} stroke-width="1.3" opacity=".7"/>
<text x="1030" y="330" text-anchor="middle" font-size="40" style="fill:var(--color-glow);font-family:var(--font-display);font-style:var(--em-style)" data-t="sign1">L</text>
<text x="1030" y="364" text-anchor="middle" font-size="12" letter-spacing="3.5" style="fill:var(--color-board-ink);font-family:var(--font-sans);font-weight:700" data-t="sign2">EST · 1888</text></g>''')
    s.append(cobbles(r, 0, 650, 1600, 250))
    s.append(f'<ellipse cx="1120" cy="700" rx="320" ry="40" {_f("color-glow")} opacity=".16"/><ellipse cx="1332" cy="702" rx="140" ry="18" {_f("color-glow")} opacity=".25"/>')
    s.append(f'<rect width="1600" height="900" fill="url(#floor{u})"/></svg>')
    return "".join(s)

# ------------------------------------------------------------------ DISHES (4:5 friendly, 800x1000)
def dish(kind, u, label=None):
    label = label or f"Dish photo placeholder: {kind} by candlelight"
    r = random.Random(hash(kind) % 1000)
    s = [svg_open("0 0 800 1000", label, u)]
    s.append(f'''<defs><linearGradient id="tb{u}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--il-art2)"/><stop offset="1" style="stop-color:var(--il-art1)"/></linearGradient>
<radialGradient id="pl{u}" cx="45%" cy="40%" r="62%"><stop offset="0" style="stop-color:var(--il-plate)"/><stop offset=".84" style="stop-color:var(--il-plate)"/><stop offset="1" style="stop-color:var(--il-plate-rim);stop-opacity:.6"/></radialGradient></defs>
<rect width="800" height="1000" fill="url(#tb{u})"/>''')
    # table boards
    for i in range(0, 1000, 125):
        s.append(f'<path d="M0 {i}H800" {_s("il-iron")} stroke-width="2" opacity=".25"/>')
    s.append(f'<circle cx="680" cy="120" r="300" fill="url(#glow{u})"/>')
    s.append(candle(680, 120, 120, u, glow=False, w=26))
    cx, cy = 390, 560
    if kind in ("plate", "duck", "dessert", "board", "pie"):
        rr = 300 if kind != "dessert" else 250
        s.append(f'<ellipse cx="{cx+14}" cy="{cy+18}" rx="{rr+6}" ry="{rr+6}" fill="#000" opacity=".28"/><circle cx="{cx}" cy="{cy}" r="{rr}" fill="url(#pl{u})"/><circle cx="{cx}" cy="{cy}" r="{rr*0.74:.0f}" fill="none" {_s("il-plate-rim")} stroke-width="2" opacity=".6"/>')
    if kind == "plate":
        s.append(f'<path d="M290 540 C 310 450, 470 430, 520 500 S 540 640, 440 655 S 270 620, 290 540Z" {_f("il-food1")}/>')
        for (x, y) in [(470, 470), (330, 500), (420, 610)]:
            s.append(f'<ellipse cx="{x}" cy="{y}" rx="34" ry="24" {_f("il-food2")} transform="rotate(-20 {x} {y})"/><ellipse cx="{x-6}" cy="{y-6}" rx="14" ry="8" fill="#fff" opacity=".25"/>')
        s.append(f'<g {_f("il-food3")}><path d="M380 470q20-30 46-16q-18 26-46 16z"/><path d="M450 560q26-12 40 12q-26 14-40-12z"/><circle cx="490" cy="530" r="5"/><circle cx="350" cy="560" r="4"/><circle cx="410" cy="520" r="3"/></g>')
    elif kind == "duck":
        for i in range(6):
            ang = -40 + i * 16
            s.append(f'<g transform="rotate({ang} 390 700)"><path d="M372 420 q 18 -10 36 0 l 6 170 q -24 10 -48 0 z" {_f("il-food1")}/><path d="M374 424 q 16 -8 32 0" fill="none" {_s("il-food2")} stroke-width="6"/></g>')
        s.append(f'<path d="M290 640 q 100 40 200 0" fill="none" {_s("il-food1")} stroke-width="12" stroke-linecap="round" opacity=".7"/>')
        for (x, y) in [(300, 600), (480, 600), (390, 660)]:
            s.append(f'<circle cx="{x}" cy="{y}" r="15" {_f("il-food1")}/><circle cx="{x-5}" cy="{y-5}" r="5" fill="#fff" opacity=".4"/>')
        s.append(f'<g {_f("il-food3")}><path d="M500 520q30-20 50 6q-30 18-50-6z"/><path d="M270 540q-24-20-6-44q22 18 6 44z"/></g>')
    elif kind == "dessert":
        s.append(f'<ellipse cx="{cx}" cy="{cy+40}" rx="120" ry="34" fill="#000" opacity=".2"/><path d="M290 590 Q 290 460 390 450 Q 490 460 490 590 Z" {_f("il-food2")}/><path d="M290 590 H490" {_s("il-food1")} stroke-width="10" stroke-linecap="round"/>')
        s.append(f'<path d="M320 520 q 70 -40 140 0" fill="none" {_s("il-food1")} stroke-width="8" opacity=".75"/><ellipse cx="350" cy="500" rx="22" ry="10" fill="#fff" opacity=".35"/>')
        s.append(f'<path d="M520 650 l 60 -16 l -6 30 z" {_f("il-food2")} opacity=".9"/><path d="M230 650 q 40 30 100 20" fill="none" {_s("il-food1")} stroke-width="6" stroke-linecap="round"/><g {_f("il-food3")}><circle cx="410" cy="440" r="8"/><path d="M380 436q-6-22 14-26q4 22-14 26z"/></g>')
    elif kind == "pie":
        s.append(f'<circle cx="{cx}" cy="{cy}" r="200" {_f("il-food2")}/><circle cx="{cx}" cy="{cy}" r="200" fill="none" {_s("il-food1")} stroke-width="22" stroke-dasharray="18 10" opacity=".85"/>')
        for k in range(-3, 4):
            s.append(f'<path d="M{cx-200} {cy+k*48} H{cx+200}" {_s("il-food1")} stroke-width="16" opacity=".55" clip-path="circle(190px at {cx}px {cy}px)"/>')
        s.append(f'<path d="M{cx} {cy} L{cx+200} {cy-40} A200 200 0 0 1 {cx+170} {cy+105} Z" {_f("il-plate")}/><path d="M{cx} {cy} L{cx+200} {cy-40}M{cx} {cy} L{cx+170} {cy+105}" {_s("il-food1")} stroke-width="6"/>')
        s.append(f'<g {_f("il-food3")}><path d="M330 470q20-26 44-12q-18 24-44 12z"/><path d="M420 640q24-10 36 12q-24 12-36-12z"/></g>')
    elif kind == "board":
        s.append(f'<rect x="140" y="380" width="520" height="340" rx="40" {_f("il-wall")} transform="rotate(-8 400 550)"/><rect x="140" y="380" width="520" height="340" rx="40" fill="none" {_s("il-iron")} stroke-width="3" opacity=".4" transform="rotate(-8 400 550)"/>')
        s.append(f'<path d="M220 520 L360 470 L370 600 Z" {_f("il-food2")}/><path d="M220 520 L360 470 L370 600 Z" fill="none" {_s("il-food1")} stroke-width="3" opacity=".4"/><circle cx="480" cy="520" r="70" {_f("il-plate")}/><circle cx="480" cy="520" r="70" fill="none" {_s("il-food2")} stroke-width="8"/>')
        s.append(f'<rect x="380" y="610" width="150" height="70" rx="10" {_f("il-food2")} transform="rotate(-8 450 640)"/>')
        for i in range(7):
            s.append(f'<circle cx="{560+ (i%3)*22}" cy="{610+(i//3)*22}" r="11" {_f("il-food1")}/>')
        s.append(f'<g {_f("il-food3")}><path d="M260 640q30-24 54 0q-30 20-54 0z"/></g>')
    s.append('</svg>')
    return "".join(s)

# ------------------------------------------------------------------ INTERIORS (centre-weighted, crop to any ratio)
def room(u, label="Photo placeholder: candlelit dining room with arched windows"):
    s = [svg_open("0 0 1000 1000", label, u)]
    s.append(wall(u, 0, 0, 1000, 700))
    s.append(f'<rect width="1000" height="700" {_f("il-sky1")} opacity=".35"/>')
    for wx in (150, 640):
        s.append(f'<path d="M{wx} 560V250A105 105 0 0 1 {wx+210} 250V560Z" {_f("il-stone")}/><path d="M{wx+16} 560V254A89 89 0 0 1 {wx+194} 254V560Z" fill="url(#sky{u})"/>')
        s.append(f'<path d="M{wx+105} 165V560M{wx+16} 360H{wx+194}M{wx+16} 460H{wx+194}" {_s("il-iron")} stroke-width="5"/><circle cx="{wx+150}" cy="230" r="12" {_f("color-glow")} opacity=".5"/>')
    # chandelier
    s.append(f'<circle cx="500" cy="230" r="230" fill="url(#glow{u})"/><path d="M500 0V160" {_s("il-iron")} stroke-width="4"/><path d="M400 200 Q500 270 600 200" fill="none" {_s("il-iron")} stroke-width="5"/>')
    for x in (400, 450, 500, 550, 600):
        s.append(candle(x, 170 + abs(x-500)//5, 30, u, glow=False, w=8))
    # floor + table
    s.append(f'<rect y="700" width="1000" height="300" {_f("il-cobble-bg")}/>')
    for i in range(0, 1000, 70): s.append(f'<path d="M{i} 700L{(i-500)*1.8+500:.0f} 1000" {_s("il-cobble")} stroke-width="2" opacity=".6"/>')
    s.append(f'<path d="M140 760H860L920 900H80Z" {_f("il-plate")}/><path d="M80 900H920V960H80Z" {_f("il-plate")} opacity=".85"/><path d="M80 900H920" {_s("il-plate-rim")} stroke-width="2"/>')
    for x in (300, 700):
        s.append(f'<ellipse cx="{x}" cy="840" rx="70" ry="18" {_f("il-plate-rim")} opacity=".35"/>')
        s.append(f'<path d="M{x+90} 830v-40q-16-6-14-30h28q2 24-14 30" fill="none" {_s("il-plate-rim")} stroke-width="3"/>')
    s.append(candle(500, 760, 60, u, w=14))
    for x in (190, 810):
        s.append(f'<path d="M{x-60} 1000V720Q{x} 690 {x+60} 720V1000" fill="none" {_s("il-iron")} stroke-width="14"/>')
    s.append(f'<rect width="1000" height="1000" fill="url(#floor{u})"/></svg>')
    return "".join(s)

def cellar(u, label="Photo placeholder: vaulted wine cellar with barrels"):
    s = [svg_open("0 0 1000 1000", label, u)]
    s.append(f'<rect width="1000" height="1000" {_f("il-sky1")}/>')
    for i in range(6):
        k = 1 - i * 0.14; w = 900 * k; h = 860 * k; x = 500 - w / 2; y = 960 - h
        s.append(f'<path d="M{x:.0f} 960V{y+w/2:.0f}A{w/2:.0f} {w/2:.0f} 0 0 1 {x+w:.0f} {y+w/2:.0f}V960" fill="none" {_s("il-wall")} stroke-width="{46*k:.0f}" opacity="{1-i*0.12:.2f}"/>')
    s.append(f'<circle cx="500" cy="640" r="220" fill="url(#glow{u})"/>')
    s.append(f'<rect y="860" width="1000" height="140" {_f("il-cobble-bg")}/>')
    for side in (0, 1):
        for j in range(2):
            bx = 120 + j * 150 if side == 0 else 880 - j * 150
            s.append(f'<g transform="translate({bx} 800)"><ellipse rx="85" ry="85" {_f("il-wall")}/><ellipse rx="85" ry="85" fill="none" {_s("il-iron")} stroke-width="6"/><ellipse rx="60" ry="60" fill="none" {_s("il-iron")} stroke-width="3" opacity=".7"/><circle r="10" {_f("il-iron")}/></g>')
    for row in range(5):
        for col in range(5):
            s.append(f'<circle cx="{410+col*45}" cy="{560+row*45}" r="16" {_f("il-iron")}/><circle cx="{410+col*45}" cy="{560+row*45}" r="7" {_f("color-glow")} opacity="{.25+((row+col)%3)*.15:.2f}"/>')
    s.append(candle(500, 500, 40, u, w=12))
    s.append('</svg>')
    return "".join(s)

def bar(u, label="Photo placeholder: back bar with bottles and brass taps"):
    r = random.Random(11)
    s = [svg_open("0 0 1000 1000", label, u)]
    s.append(wall(u, 0, 0, 1000, 1000))
    s.append(f'<rect x="80" y="120" width="840" height="470" {_f("il-sky1")} opacity=".85"/><circle cx="500" cy="300" r="380" fill="url(#glow{u})" opacity=".8"/>')
    for sy in (260, 420, 580):
        s.append(f'<rect x="80" y="{sy}" width="840" height="12" {_f("il-stone")}/>')
        x = 100
        while x < 890:
            w = r.randint(22, 40); h = r.randint(70, 130)
            s.append(f'<path d="M{x} {sy}V{sy-h*0.6:.0f}Q{x} {sy-h*0.7:.0f} {x+w*0.35:.0f} {sy-h*0.78:.0f}V{sy-h}H{x+w*0.65:.0f}V{sy-h*0.78:.0f}Q{x+w} {sy-h*0.7:.0f} {x+w} {sy-h*0.6:.0f}V{sy}Z" {_f("il-iron")}/>'
                     f'<rect x="{x+4}" y="{sy-h*0.45:.0f}" width="{w-8}" height="{h*0.2:.0f}" {_f("color-glow")} opacity="{r.choice([.25,.4,.6])}"/>')
            x += w + r.randint(6, 14)
    s.append(f'<rect y="700" width="1000" height="300" {_f("il-art2")}/><rect y="690" width="1000" height="28" {_f("il-plate-rim")}/>')
    for i in range(6):
        x = 260 + i * 96
        s.append(f'<rect x="{x}" y="600" width="18" height="90" rx="4" {_f("il-plate-rim")}/><rect x="{x-12}" y="560" width="42" height="50" rx="8" {_f("il-iron")}/><path d="M{x+9} 690v20" {_s("il-plate-rim")} stroke-width="8"/>')
    s.append(f'<path d="M0 940H1000" {_s("il-plate-rim")} stroke-width="10" opacity=".8"/>')
    for x in (180, 500, 820):
        s.append(f'<ellipse cx="{x}" cy="840" rx="70" ry="22" {_f("il-iron")}/><path d="M{x} 860V1000M{x-50} 1000L{x} 900L{x+50} 1000" {_s("il-iron")} stroke-width="10"/>')
    s.append('</svg>')
    return "".join(s)

def hearth(u, label="Photo placeholder: open brick hearth with fire"):
    s = [svg_open("0 0 1000 1000", label, u)]
    s.append(wall(u, 0, 0, 1000, 1000))
    s.append(f'<rect width="1000" height="1000" {_f("il-sky1")} opacity=".3"/>')
    s.append(f'<rect x="120" y="230" width="760" height="50" {_f("il-stone")}/><path d="M220 860V520A280 240 0 0 1 780 520V860Z" {_f("il-stone")}/><path d="M260 860V530A240 205 0 0 1 740 530V860Z" {_f("il-sky1")}/>')
    s.append(f'<circle cx="500" cy="760" r="300" fill="url(#glow{u})"/>')
    for i, (x, h) in enumerate([(400, 180), (460, 250), (520, 220), (580, 160), (440, 130)]):
        s.append(f'<path d="M{x-50} 820 Q{x-40} {820-h*0.6} {x} {820-h} Q{x+40} {820-h*0.6} {x+50} 820Z" {_f("color-glow")} opacity="{.55+0.1*(i%3)}"/>')
    s.append(f'<path d="M340 830L660 800M330 800L670 835" {_s("il-iron")} stroke-width="30" stroke-linecap="round"/>')
    s.append(f'<rect y="860" width="1000" height="140" {_f("il-cobble-bg")}/>')
    s.append(f'<path d="M500 280V420" {_s("il-iron")} stroke-width="5"/><path d="M450 420H550L540 500H460Z" {_f("il-iron")}/>')
    for x in (100, 900):
        s.append(f'<path d="M{x-70} 1000V820Q{x-70} 760 {x} 760Q{x+70} 760 {x+70} 820V1000" {_f("il-iron")} opacity=".9"/>')
    s.append('</svg>')
    return "".join(s)

def facade(u, closed=False, label="Photo placeholder: historic restaurant front with lantern"):
    r = random.Random(5)
    s = [svg_open("0 0 1000 1000", label, u)]
    s.append(f'<rect width="1000" height="1000" fill="url(#sky{u})"/>')
    s.append(f'<path d="M150 820V260L500 60L850 260V820Z" {_f("il-stone-far")}/>')
    s.append(f'<path d="M180 820V280L500 100L820 280V820Z" fill="none"/>')
    s.append(f'<clipPath id="fc{u}"><path d="M180 820V280L500 100L820 280V820Z"/></clipPath><g clip-path="url(#fc{u})">{wall(u,150,60,700,780)}</g>')
    s.append(f'<path d="M150 262L500 60L850 262" fill="none" {_s("il-stone")} stroke-width="22"/>')
    for wx in (250, 450, 650):
        lit = .15 if closed else .6
        s.append(f'<rect x="{wx}" y="300" width="100" height="150" rx="50" {_f("il-stone")}/><rect x="{wx+10}" y="310" width="80" height="132" rx="40" {_f("color-glow")} opacity="{lit}"/><path d="M{wx+50} 310V442M{wx+10} 380H{wx+90}" {_s("il-iron")} stroke-width="4"/>')
    s.append(f'<path d="M400 820V640A100 100 0 0 1 600 640V820Z" {_f("il-stone")}/><path d="M416 820V644A84 84 0 0 1 584 644V820Z" ' + (f'{_f("il-iron")}/>' if closed else f'fill="url(#door{u})"/>'))
    if closed:
        s.append(f'<path d="M416 700H584M416 760H584M500 644V820" {_s("il-wall")} stroke-width="4" opacity=".7"/><rect x="450" y="560" width="100" height="44" rx="4" {_f("color-board")}/><text x="500" y="588" text-anchor="middle" font-size="18" letter-spacing="3" style="fill:var(--color-board-ink);font-family:var(--font-sans);font-weight:700">CLOSED</text>')
    s.append(f'<g class="v-lantern">{lantern(320, 560, u, .9)}</g>' if not closed else f'<g>{lantern(320, 560, u, .9)}</g>')
    s.append(f'<g class="v-sign"><path d="M720 520H860M760 520V546M840 520V546" {_s("il-iron")} stroke-width="5"/><rect x="730" y="546" width="140" height="100" rx="8" style="fill:var(--color-board);stroke:var(--color-board-frame)" stroke-width="5"/><text x="800" y="606" text-anchor="middle" font-size="30" style="fill:var(--color-glow);font-family:var(--font-display);font-style:var(--em-style)" data-t="sign1">L</text></g>')
    s.append(cobbles(r, 0, 820, 1000, 180, 3))
    s.append('</svg>')
    return "".join(s)

def chef(u, label="Portrait placeholder: the chef at the kitchen pass, back-lit"):
    s = [svg_open("0 0 800 1000", label, u)]
    s.append(wall(u, 0, 0, 800, 1000))
    s.append(f'<rect width="800" height="1000" {_f("il-sky1")} opacity=".5"/>')
    s.append(f'<path d="M40 130H760" {_s("il-iron")} stroke-width="8" stroke-linecap="round"/>')
    for i, x in enumerate((110, 215, 585, 690)):
        rr = 44 + (i % 2) * 16; cy = 150 + 70 + rr
        s.append(f'<path d="M{x} 130V150M{x} 150L{x} {cy-rr}" {_s("il-plate-rim")} stroke-width="7" stroke-linecap="round"/><circle cx="{x}" cy="{cy}" r="{rr}" {_f("il-plate-rim")}/><circle cx="{x}" cy="{cy}" r="{rr-8}" fill="none" {_s("il-iron")} stroke-width="2" opacity=".35"/><ellipse cx="{x-rr*0.35:.0f}" cy="{cy-rr*0.3:.0f}" rx="{rr*0.25:.0f}" ry="{rr*0.4:.0f}" {_f("color-glow")} opacity=".35"/>')
    s.append(f'<circle cx="400" cy="560" r="380" fill="url(#glow{u})"/>')
    # pass shelf + heat lamps
    s.append(f'<rect x="0" y="880" width="800" height="120" {_f("il-plate-rim")} opacity=".9"/><rect x="0" y="872" width="800" height="12" {_f("il-iron")}/>')
    # back-lit figure: dark silhouette with warm rim light
    fig = "M200 880 C 205 760, 280 700, 360 688 L 362 640 C 318 612, 300 560, 304 512 C 306 440, 350 400, 400 400 C 450 400, 494 440, 496 512 C 500 560, 482 612, 438 640 L 440 688 C 520 700, 595 760, 600 880 Z"
    s.append(f'<path d="{fig}" {_f("il-iron")}/><path d="{fig}" fill="none" {_s("color-glow")} stroke-width="5" opacity=".75"/>')
    s.append(f'<path d="M318 724 L 400 800 L 482 724 L 470 880 L 330 880Z" {_f("il-plate")} opacity=".55"/>')
    s.append(f'<path d="M330 360 Q 400 300 470 360 L 466 404 Q 400 386 334 404Z" {_f("il-plate")} opacity=".9"/>')
    # plate held at the pass
    s.append(f'<ellipse cx="400" cy="872" rx="150" ry="26" {_f("il-plate")}/><ellipse cx="400" cy="866" rx="70" ry="12" {_f("il-food1")}/><circle cx="380" cy="860" r="8" {_f("il-food3")}/>')
    s.append('</svg>')
    return "".join(s)

def table(u, label="Photo placeholder: long candlelit feast table"):
    s = [svg_open("0 0 1600 900", label, u)]
    s.append(wall(u, 0, 0, 1600, 520))
    s.append(f'<rect width="1600" height="520" {_f("il-sky1")} opacity=".4"/>')
    for wx in (260, 700, 1140):
        s.append(f'<path d="M{wx} 470V200A100 100 0 0 1 {wx+200} 200V470Z" {_f("il-stone")}/><path d="M{wx+14} 470V204A86 86 0 0 1 {wx+186} 204V470Z" fill="url(#sky{u})"/>')
    s.append(f'<rect y="520" width="1600" height="380" {_f("il-cobble-bg")}/>')
    s.append(f'<path d="M640 520H960L1400 900H200Z" {_f("il-plate")}/><path d="M640 520H960L1400 900H200Z" fill="none" {_s("il-plate-rim")} stroke-width="3"/>')
    for i in range(5):
        t = i / 4; y = 560 + t * 280; half = 160 + t * 440 - 60
        for sx in (-1, 1):
            x = 800 + sx * half * 0.75
            s.append(f'<ellipse cx="{x:.0f}" cy="{y:.0f}" rx="{20+t*40:.0f}" ry="{6+t*12:.0f}" {_f("il-plate-rim")} opacity=".5"/>')
        if i % 2 == 0:
            s.append(candle(800, y - 40 - t * 30, 40 + t * 40, u, w=8 + t * 8))
    s.append(f'<rect width="1600" height="900" fill="url(#floor{u})"/></svg>')
    return "".join(s)

# ------------------------------------------------------------------ MAP
def map_svg(u, names):
    pins = [(150, 140), (330, 250), (390, 100)]
    r = random.Random(3)
    out = [f'''<svg class="map-svg" viewBox="0 0 560 360" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Engraved map of the old town showing three locations">
<defs><pattern id="mh{u}" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(-30)"><path d="M0 0V5" style="stroke:var(--color-text)" stroke-width=".7" opacity=".35"/></pattern></defs>
<rect width="560" height="360" style="fill:var(--il-map)"/>
<path d="M-10 288 C 100 250, 180 318, 300 286 S 480 250, 570 280 L570 330 C 460 300, 360 344, 260 332 S 70 316, -10 338Z" fill="url(#mh{u})"/>
<path d="M-10 288 C 100 250, 180 318, 300 286 S 480 250, 570 280 M-10 338 C 70 316, 160 346, 260 332 S 460 300, 570 330" fill="none" style="stroke:var(--color-text)" opacity=".5"/>
<circle cx="295" cy="170" r="140" fill="none" style="stroke:var(--color-text)" stroke-width="1.2" stroke-dasharray="2 4" opacity=".45"/>''']
    roads = ["M40 50 C 140 84, 220 110, 295 170 S 440 240, 540 230", "M100 280 C 160 220, 240 196, 295 170 S 390 84, 440 20", "M295 170 C 300 110, 280 60, 250 10"]
    out.append('<g fill="none" style="stroke:var(--color-text)" opacity=".42" stroke-width="7" stroke-linecap="round">' + "".join(f'<path d="{p}"/>' for p in roads) + '</g>')
    out.append('<g fill="none" style="stroke:var(--il-map)" stroke-width="4" stroke-linecap="round">' + "".join(f'<path d="{p}"/>' for p in roads) + '</g>')
    out.append('<g style="stroke:var(--color-text)" fill="none" opacity=".26" stroke-width=".8">')
    for i in range(60):
        x = r.randint(20, 530); y = r.randint(14, 260); w = r.randint(10, 28); h = r.randint(8, 18)
        out.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" transform="rotate({r.randint(-20,20)} {x} {y})"/>')
    out.append('</g><g transform="translate(500 60)" style="fill:var(--color-text)" opacity=".7"><circle r="22" fill="none" style="stroke:var(--color-text)" stroke-width=".8"/><path d="M0 -28L5 0L0 28L-5 0Z"/><path d="M-28 0L0 4L28 0L0 -4Z" opacity=".5"/><text y="-33" text-anchor="middle" font-size="10" style="font-family:var(--font-display)">N</text></g>')
    for i, ((x, y), n) in enumerate(zip(pins, names)):
        out.append(f'<g class="pin pin-{i}" transform="translate({x} {y})"><circle class="pin-c" r="12" stroke-width="2"/><text class="pin-n" y="4" text-anchor="middle" font-size="12" font-weight="700" style="font-family:var(--font-sans)">{i+1}</text><text x="20" y="5" font-size="14" style="fill:var(--color-text);font-family:var(--font-display);font-style:var(--em-style)" data-t="locs.{i}.name">{n}</text></g>')
    out.append('<text x="16" y="350" font-size="9" letter-spacing="2" style="fill:var(--color-text);font-family:var(--font-sans)" opacity=".75">STATIC MAP · LINKS TO DIRECTIONS</text></svg>')
    return "".join(out)

# ------------------------------------------------------------------ BRAND MARKS
def crest(u, size=56, cls="crest"):
    return f'''<svg class="{cls}" viewBox="0 0 80 80" width="{size}" height="{size}" aria-hidden="true">
<defs><path id="arc{u}" d="M13 45 A27 27 0 0 0 67 45"/></defs>
<circle cx="40" cy="40" r="38" fill="none" style="stroke:currentColor" stroke-width="1.5"/>
<circle cx="40" cy="40" r="33.5" fill="none" style="stroke:currentColor" stroke-width=".7" stroke-dasharray="1 2.2"/>
<circle cx="40" cy="40" r="21" style="fill:currentColor" opacity=".12"/>
<text class="crest-mono" x="40" y="45" text-anchor="middle" style="fill:currentColor;font-family:var(--font-display);font-style:var(--em-style)" data-t="mono">L</text>
<text font-size="6.4" letter-spacing="2.2" style="fill:currentColor;font-family:var(--font-sans);font-weight:700"><textPath href="#arc{u}" startOffset="50%" text-anchor="middle" data-t="sign2">EST · 1888</textPath></text>
<path d="M21 30l3 2-3 2-3-2zM59 30l3 2-3 2-3-2z" style="fill:currentColor"/></svg>'''

def ornament(cls="orn", w=240):
    return f'''<svg class="{cls}" viewBox="0 0 240 24" width="{w}" height="{w/10:.0f}" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
<path d="M2 12H86M154 12H238M86 12c6 0 8-7 14-7s6 7 0 7M154 12c-6 0-8-7-14-7s-6 7 0 7M86 12c6 0 8 7 14 7s6-7 0-7M154 12c-6 0-8 7-14 7s-6-7 0-7"/>
<path d="M120 3L128 12L120 21L112 12Z" fill="currentColor"/><circle cx="104" cy="12" r="1.6" fill="currentColor"/><circle cx="136" cy="12" r="1.6" fill="currentColor"/><path d="M2 9V15M238 9V15"/></g></svg>'''

# ------------------------------------------------------------------ ICON SPRITE (24px, 1.6 stroke, Lucide-style, original paths)
ICONS = {
 "pin": '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
 "clock": '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 "phone": '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
 "calendar": '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
 "bag": '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
 "nav": '<path d="M3 11l18-8-8 18-2-8z"/>',
 "menu": '<path d="M4 7h16M4 12h16M4 17h16"/>',
 "close": '<path d="M6 6l12 12M18 6L6 18"/>',
 "chev-down": '<path d="M6 9l6 6 6-6"/>',
 "chev-right": '<path d="M9 6l6 6-6 6"/>',
 "chev-left": '<path d="M15 6l-6 6 6 6"/>',
 "arrow": '<path d="M4 12h15M13 6l6 6-6 6"/>',
 "check": '<path d="M5 12.5l4.5 4.5L19 7"/>',
 "star": '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.8L12 16.8 6.8 19.6l1-5.8-4.3-4.1 5.9-.8z"/>',
 "users": '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
 "mail": '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6l8.5 7 8.5-7"/>',
 "leaf": '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19l8-8"/>',
 "wheat": '<path d="M12 21V8M12 12l-3-3m3 3 3-3M12 16l-3-3m3 3 3-3M12 8 9 5m3 3 3-3"/>',
 "chili": '<path d="M7 10c3 0 9 1 11 8-6 1-11-2-12-7"/><path d="M7 10c0-3 2-4 4-4M11 6c0-1.5 1-2.5 2-3"/>',
 "download": '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
 "glass": '<path d="M7 3h10l-1 6a4 4 0 0 1-8 0zM12 13v7M8 21h8"/>',
 "car": '<path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3zM7 19v1.5M17 19v1.5M5 11h14"/>',
 "train": '<rect x="6" y="3" width="12" height="13" rx="3"/><path d="M6 10h12M9 20l-2 2M15 20l2 2M9 13h.01M15 13h.01"/>',
 "access": '<circle cx="12" cy="4.5" r="1.8"/><path d="M5 8l7 1 7-1M12 9v5l-3 7M12 14l3 7"/>',
 "info": '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>',
 "insta": '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><path d="M17 7h.01"/>',
 "fb": '<path d="M14 21v-7h3l.5-3.5H14V8.5c0-1 .4-1.6 1.7-1.6h1.9V3.8A22 22 0 0 0 15 3.6c-2.6 0-4.4 1.6-4.4 4.5v2.4H7.7V14h2.9v7"/>',
 "plus": '<path d="M12 5v14M5 12h14"/>',
 "palette": '<path d="M12 3a9 9 0 1 0 0 18c1.2 0 1.8-.8 1.8-1.7 0-1.4-1.3-1.6-1.3-2.8 0-.9.7-1.5 1.7-1.5h2.3A4.5 4.5 0 0 0 21 10.5C21 6.4 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.3" r="1"/><circle cx="14.5" cy="7.3" r="1"/>',
 "expand": '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
 "filter": '<path d="M4 5h16l-6 8v6l-4-2v-4z"/>',
 "flame": '<path d="M12 21c4 0 7-2.6 7-6.5 0-4-3-6-4-9-1.5 2-2 3.5-2 5-1.3-1-2-2.4-2-4-3 2.3-6 5.4-6 8C5 18.4 8 21 12 21z"/>',
}
def sprite():
    syms = "".join(f'<symbol id="i-{k}" viewBox="0 0 24 24">{v}</symbol>' for k, v in ICONS.items())
    return f'<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>{syms}</defs></svg>'
def icon(name, cls="i"):
    return f'<svg class="{cls}" aria-hidden="true" focusable="false"><use href="#i-{name}"/></svg>'
