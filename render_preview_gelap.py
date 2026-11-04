import pandas as pd, matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
plt.rcParams.update({'figure.facecolor':'#1e293b','axes.facecolor':'#1e293b','savefig.facecolor':'#1e293b','text.color':'#cbd5e1','axes.labelcolor':'#cbd5e1','xtick.color':'#cbd5e1','ytick.color':'#cbd5e1','axes.edgecolor':'#475569'})
from PIL import Image, ImageDraw, ImageFont
import io

df = pd.read_csv('data.csv')
def fmt(n): return f"{int(n):,}".replace(",", ".")
perYear = df.groupby('Tahun').size().sort_index()
perEvent = df['Event'].value_counts()
topProv = df['Province'].value_counts().head(10)
top = df.nlargest(10, 'Deaths')
kpi = [("Total Kejadian", len(df), "#2563eb"), ("Meninggal", int(df['Deaths'].sum()), "#dc2626"),
       ("Luka-luka", int(df['Injured'].sum()), "#f97316"), ("Terdampak", int(df['Affected'].sum()), "#f59e0b"),
       ("Rumah Rusak/Hancur", int(df['Houses Destroyed'].sum()+df['Houses Damaged'].sum()), "#059669")]

def chart_png(draw_fn, w, h):
    fig = plt.figure(figsize=(w/100, h/100), dpi=100)
    draw_fn(fig)
    buf = io.BytesIO(); fig.savefig(buf, format='png', bbox_inches='tight', facecolor='#1e293b'); plt.close(fig)
    return Image.open(buf).convert('RGB')

def d_line(fig):
    ax = fig.add_subplot(111)
    ax.plot(perYear.index, perYear.values, color='#2563eb', lw=1.6)
    ax.fill_between(perYear.index, perYear.values, color='#2563eb', alpha=.15)
    ax.set_ylabel('Kejadian'); ax.grid(alpha=.25); ax.tick_params(labelsize=8)
def d_dough(fig):
    ax = fig.add_subplot(111)
    colors = ['#2563eb','#dc2626','#f59e0b','#10b981','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b','#84cc16','#06b6d4','#a855f7','#eab308','#0ea5e9','#f43f5e','#22c55e','#6366f1','#d946ef','#78716c','#fb7185']
    ax.pie(perEvent.values, colors=colors[:len(perEvent)], startangle=90, wedgeprops=dict(width=.45, edgecolor='#1e293b'))
    ax.legend(perEvent.index, loc='center left', bbox_to_anchor=(1.0,.5), fontsize=6.5, frameon=False)
def d_bar(fig):
    ax = fig.add_subplot(111)
    tp = topProv.iloc[::-1]
    ax.barh(tp.index, tp.values, color='#0ea5e9')
    ax.tick_params(labelsize=8); ax.grid(axis='x', alpha=.25)
    for i, v in enumerate(tp.values): ax.text(v+60, i, fmt(v), va='center', fontsize=8)

W = 1560
img = Image.new('RGB', (W, 2350), '#020617')
dr = ImageDraw.Draw(img)
def F(sz, bold=False):
    return ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans%s.ttf' % ('-Bold' if bold else ''), sz)
def card(x, y, w, h):
    dr.rounded_rectangle([x, y, x+w, y+h], 14, fill='#1e293b', outline='#334155', width=2)

dr.rectangle([0, 0, W, 120], fill='#0f172a')
dr.text((40, 26), 'Dasbor Bencana Indonesia', font=F(38, True), fill='white')
dr.text((40, 76), 'Data kejadian bencana BNPB/DIBI tahun 1815-2014 · total dataset 17.878 kejadian', font=F(19), fill='#cbd5e1')

y = 145
card(30, y, W-60, 74)
dr.text((55, y+24), 'Filter:   Jenis bencana [ Semua ]      Provinsi [ Semua ]      Tahun [ 1815 ] - [ 2014 ]      [ Terapkan ]  [ Reset ]', font=F(18), fill='#e2e8f0')
y += 100
cw = (W-60-4*16)//5
for i, (label, val, color) in enumerate(kpi):
    x = 30 + i*(cw+16)
    card(x, y, cw, 118)
    dr.rounded_rectangle([x+16, y+16, x+cw-16, y+23], 3, fill=color)
    dr.text((x+16, y+34), fmt(val), font=F(31, True), fill='#f1f5f9')
    dr.text((x+16, y+80), label, font=F(16), fill='#94a3b8')
y += 144
card(30, y, W-60, 430)
dr.text((55, y+16), 'Tren Kejadian per Tahun', font=F(20, True), fill='#f1f5f9')
img.paste(chart_png(d_line, 1440, 350), (60, y+55)); y += 456
half = (W-60-16)//2
card(30, y, half, 470); card(46+half, y, half, 470)
dr.text((55, y+16), 'Kejadian per Jenis Bencana', font=F(20, True), fill='#f1f5f9')
dr.text((71+half, y+16), '10 Provinsi Terbanyak Kejadian', font=F(20, True), fill='#f1f5f9')
img.paste(chart_png(d_dough, 700, 390), (45, y+55))
img.paste(chart_png(d_bar, 700, 390), (61+half, y+55)); y += 496
card(30, y, W-60, 480)
dr.text((55, y+16), '10 Kejadian dengan Korban Jiwa Terbesar', font=F(20, True), fill='#f1f5f9')
cols = [(55, 'Tanggal'), (200, 'Jenis'), (560, 'Provinsi'), (850, 'Kab/Kota'), (1120, 'Meninggal'), (1280, 'Luka'), (1400, 'Terdampak')]
ty = y + 58
for x, t in cols: dr.text((x, ty), t, font=F(16, True), fill='#e2e8f0')
dr.line([55, ty+26, W-55, ty+26], fill='#e2e8f0', width=2)
for _, r in top.iterrows():
    ty += 38
    dist = '-' if pd.isna(r['District']) else str(r['District']).title()[:22]
    vals = [str(r['Date (YMD)']), str(r['Event'])[:38], str(r['Province']).title()[:24], dist, fmt(r['Deaths']), fmt(r['Injured']), fmt(r['Affected'])]
    for (x, _), v in zip(cols, vals): dr.text((x, ty), v, font=F(16), fill='#e2e8f0')
y += 500
dr.text((30, y+6), 'Sumber data: BNPB/DIBI (dataset sesudah pembersihan: 17.878 baris, 26 kolom). Nilai 0 pada kolom dampak dapat berarti nol atau tidak tercatat.', font=F(15), fill='#94a3b8')
img = img.crop((0, 0, W, y+46))
img.save('dasbor-preview-gelap.png')
print('OK', img.size)
