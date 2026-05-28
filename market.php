<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Autura Market Report';
$meta_desc  = 'Autura Market Report — marketplace pricing trends, volume, regional breakdown, and 60-day comparisons.';
$body_class = 'page-market';
$canonical  = '/autura-market-report';
$amr_data_version = file_exists(__DIR__ . '/data/amr-data.json') ? filemtime(__DIR__ . '/data/amr-data.json') : 0;
$amr_meta         = file_exists(__DIR__ . '/data/amr-meta.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-meta.json'), true) : [];
$amr_data_date    = $amr_meta['data_date'] ?? '';
$amr_record_count = (int)($amr_meta['count'] ?? 0);

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.mi-hero { padding: 72px 0 36px; }
.mi-hero h1 { font-size: clamp(1.8rem,4vw,2.6rem); margin-bottom: 6px; }
.mi-period { font-size: 13px; color: var(--text-muted); margin-top: 6px; }

.mi-section { margin: 40px 0 16px; }
.mi-section h2 { font-size: 1.15rem; font-weight: 700; margin-bottom: 4px; }
.mi-section p  { font-size: 13px; color: var(--text-muted); }

.mi-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 24px 28px;
}
.mi-card-title {
  font-size: .72rem; font-weight: 700; letter-spacing: .07em;
  text-transform: uppercase; color: var(--text-muted); margin-bottom: 16px;
}

/* grids */
.mi-g3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 16px; }
.mi-g2 { display: grid; grid-template-columns: repeat(2,1fr); gap: 16px; margin-bottom: 16px; }
.mi-g21 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
@media (max-width: 900px) { .mi-g2,.mi-g21 { grid-template-columns: 1fr; } }
@media (max-width: 640px) { .mi-g3  { grid-template-columns: 1fr; } }

/* KPI */
.kpi-val { font-family: var(--font-display); font-size: 2rem; font-weight: 800; line-height: 1.1; }
.kpi-lbl { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.kpi-d   { font-size: 12px; font-weight: 600; margin-top: 10px; }
.kpi-d.pos { color: #2e8a4c; } [data-theme="dark"] .kpi-d.pos { color: #5ec97c; }
.kpi-d.neg { color: #b83232; } [data-theme="dark"] .kpi-d.neg { color: #e05a5a; }
.kpi-d.neu { color: var(--text-muted); }

/* tables */
.mi-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.mi-tbl th {
  font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
  color: var(--text-muted); padding: 0 12px 10px; text-align: right;
  border-bottom: 1px solid var(--border); white-space: nowrap;
}
.mi-tbl th:first-child { text-align: left; padding-left: 0; }
.mi-tbl td {
  padding: 9px 12px; border-bottom: 1px solid rgba(0,0,0,.05);
  text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;
}
[data-theme="dark"] .mi-tbl td { border-bottom-color: rgba(255,255,255,.04); }
.mi-tbl td:first-child { text-align: left; padding-left: 0; font-weight: 500; }
.mi-tbl tr:last-child td { border-bottom: none; }
.mi-tbl tr:hover td { background: var(--surface-2); }
.mi-tbl .rank { color: var(--text-muted); font-size: 11px; font-weight: 400; }

/* deltas */
.dp { color: #2e8a4c; font-size: 11px; font-weight: 600; }
.dn { color: #b83232; font-size: 11px; font-weight: 600; }
.dz { color: var(--text-muted); font-size: 11px; }
[data-theme="dark"] .dp { color: #5ec97c; }
[data-theme="dark"] .dn { color: #e05a5a; }

/* condition */
.cond-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
.cond-item { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; }
.cond-lbl  { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.cond-val  { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: var(--accent); }
.cond-sub  { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

/* doc bars */
.doc-row  { display: flex; align-items: center; gap: 10px; margin-bottom: 9px; }
.doc-lbl  { font-size: 12px; min-width: 100px; color: var(--text); }
.doc-bar  { flex: 1; background: var(--surface-2); border-radius: 4px; height: 7px; overflow: hidden; }
.doc-fill { height: 100%; border-radius: 4px; background: var(--accent); opacity: .65; }
.doc-pct  { font-size: 11px; color: var(--text-muted); min-width: 38px; text-align: right; }

/* chart shared */
.mi-chart { overflow-x: auto; }
.mi-chart svg,.mi-chart-svg { display: block; width: 100%; }
.mi-lbl     { font-family: system-ui,sans-serif; font-size: 11px; fill: var(--text-muted); }
.mi-lbl-val { font-family: system-ui,sans-serif; font-size: 11px; fill: var(--text); font-weight: 600; }
.mi-axis    { stroke: var(--border); stroke-width: 1; }
.mi-grid-l  { stroke: var(--border); stroke-width: .5; stroke-dasharray: 3,3; }

/* trend chart */
.mi-bar-fill  { fill: var(--accent); opacity: .75; }
.mi-line      { fill: none; stroke: var(--accent); stroke-width: 2; }
.mi-dot       { fill: var(--bg); stroke: var(--accent); stroke-width: 2; }

/* donut */
.donut-wrap { display: flex; align-items: center; gap: 20px; }
.donut-legend { flex: 1; }
.donut-key { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; }
.donut-swatch { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* period bar chart */
.pbar-legend { display: flex; gap: 16px; font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
.pbar-swatch { display: inline-block; width: 12px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }

/* mi-legend */
.mi-legend { display: flex; gap: 18px; font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }
.mi-legend span { display: inline-flex; align-items: center; gap: 5px; }
.leg-bar  { display: inline-block; width: 12px; height: 8px; background: var(--accent); opacity: .75; border-radius: 2px; }
.leg-line { display: inline-block; width: 18px; height: 2px; background: var(--accent); }

/* insights */
.mi-insight {
  display: flex; gap: 10px; align-items: flex-start;
  background: rgba(240,165,0,.07); border-left: 3px solid var(--accent);
  border-radius: 0 var(--radius) var(--radius) 0;
  padding: 12px 16px; margin-top: 14px;
  font-size: 13px; line-height: 1.6; color: var(--text-muted);
}
.mi-insight svg { flex-shrink: 0; color: var(--accent); margin-top: 1px; }
.hi  { color: var(--accent); font-weight: 700; }
.iup { color: #2e8a4c; font-weight: 600; } [data-theme="dark"] .iup { color: #5ec97c; }
.idn { color: #b83232; font-weight: 600; } [data-theme="dark"] .idn { color: #e05a5a; }

/* print button */
.mi-print-btn {
  background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px;
  color: var(--text-muted); font-size: 13px; font-weight: 600; padding: 8px 16px;
  cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
  transition: border-color .15s, color .15s;
}
.mi-print-btn:hover { border-color: var(--accent); color: var(--accent); }
.mi-print-footer { display: none; }

/* loading */
.mi-load { text-align: center; padding: 80px 24px; color: var(--text-muted); }
@keyframes mi-spin { to { transform: rotate(360deg); } }

/* ── Print ────────────────────────────────────────────────────────── */
@media print {
  @page { margin: 18mm 14mm; }
  .site-header, .mi-print-btn, .site-footer { display: none !important; }
  body { background: #fff !important; color: #111 !important; font-size: 11pt; }
  .mi-hero { padding: 0 0 16px; }
  .mi-card { border: 1px solid #ddd !important; background: #fff !important; break-inside: avoid; margin-bottom: 12pt; }
  .mi-card-title { color: #555 !important; }
  .mi-g3,.mi-g2,.mi-g21 { display: grid !important; gap: 10pt; }
  .mi-lbl,.mi-lbl-val { fill: #333 !important; }
  .mi-axis,.mi-grid-l  { stroke: #ccc !important; }
  .mi-bar-fill { fill: #f0a500 !important; opacity: .85 !important; }
  .mi-line { stroke: #f0a500 !important; }
  .mi-dot  { fill: #fff !important; stroke: #f0a500 !important; }
  .donut-swatch { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .doc-fill { background: #f0a500 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .kpi-val { font-size: 22pt; }
  .dp { color: #1a6e35 !important; } .dn { color: #a0201a !important; }
  .mi-section { margin: 14pt 0 8pt; }
  .mi-print-footer { display: block !important; text-align: center; font-size: 9pt; color: #777; border-top: 1px solid #ddd; padding-top: 8pt; margin-top: 20pt; }
  svg { max-width: 100% !important; }
}
</style>';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <section class="mi-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/" style="color:var(--text-muted);text-decoration:none;">&larr; Valuation Tool</a>
    </p>
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h1>Autura Market Report</h1>
        <p class="mi-period" id="mi-period">Loading…</p>
      </div>
      <button class="mi-print-btn" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print / Save PDF
      </button>
    </div>
  </section>
  <div id="mi-body">
    <div class="mi-load">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
           style="margin:0 auto 14px;display:block;animation:mi-spin 1s linear infinite">
        <path d="M12 2a10 10 0 1 1-10 10" stroke-linecap="round"/>
      </svg>
      Loading market data…
    </div>
  </div>
</div>

<script>
const AMR_DATA_DATE  = '<?= h($amr_data_date) ?>';
const AMR_RECORD_CNT = <?= $amr_record_count ?>;
const $    = id => document.getElementById(id);
const fmtD = n  => '$' + Math.round(n).toLocaleString();
const fmtN = n  => Math.round(n).toLocaleString();
const cap  = s  => s ? s.charAt(0) + s.slice(1).toLowerCase() : s;

const MON = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const mlbl = ym => ym ? MON[+ym.split('-')[1]] + ' ' + ym.split('-')[0] : '—';

const CHART_COLORS = ['#f0a500','#3b82f6','#22c55e','#a855f7','#ef4444','#f97316','#06b6d4','#84cc16'];

function med(arr) {
  if (!arr.length) return 0;
  const s = [...arr].sort((a,b)=>a-b), m = s.length>>1;
  return s.length&1 ? s[m] : Math.round((s[m-1]+s[m])/2);
}
function stats(recs) {
  if (!recs.length) return null;
  const prices = recs.map(r=>r.price), sum = prices.reduce((a,b)=>a+b,0);
  return { count:recs.length, avg:Math.round(sum/recs.length), median:med(prices),
           withKey:recs.filter(r=>r.has_key).length, starts:recs.filter(r=>r.starts).length };
}
function delta(s1,s2,key) {
  if (!s1||!s2||!s2[key]) return '<span class="dz">—</span>';
  const p=(s1[key]-s2[key])/s2[key]*100;
  if (Math.abs(p)<0.05) return '<span class="dz">±0%</span>';
  return `<span class="${p>0?'dp':'dn'}">${p>0?'▲':'▼'}${Math.abs(p).toFixed(1)}%</span>`;
}
function addM(ym,n) {
  if(!ym) return '';
  let [y,m]=ym.split('-').map(Number); m+=n;
  while(m>12){m-=12;y++;} while(m<=0){m+=12;y--;}
  return `${y}-${String(m).padStart(2,'0')}`;
}

// ── Classification ─────────────────────────────────────────────────────────────
const MOTO = new Set(['HARLEY-DAVIDSON','KAWASAKI','DUCATI','ROYAL ENFIELD','HYOSUNG','GENUINE SCOOTERS']);
const HVYW = new Set(['FREIGHTLINER','INTERNATIONAL','KENWORTH','PETERBILT','MACK','VOLVO TRUCK','HINO','ISUZU','SPARTAN']);
const TRUK = ['F-150','F-250','F-350','F-450','F-550','SILVERADO 1500','SILVERADO 2500','SILVERADO 3500','SILVERADO','RAM 1500','RAM 2500','RAM 3500','RAM PICKUP','SIERRA 1500','SIERRA 2500','SIERRA 3500','SIERRA','TUNDRA','TACOMA','RANGER','COLORADO','CANYON','FRONTIER','TITAN','RIDGELINE','MAVERICK','C/K 1500','C/K 2500','C/K 3500','PICKUP'];
const SUVS = ['EXPLORER','EXPEDITION','TAHOE','SUBURBAN','EQUINOX','TRAVERSE','BLAZER','TRAILBLAZER','HIGHLANDER','4RUNNER','RAV4','CR-V','PILOT','PASSPORT','MDX','RDX','PATHFINDER','MURANO','ROGUE','ARMADA','XTERRA','ESCAPE','EDGE','NAVIGATOR','ESCALADE','YUKON','ENVOY','ENCLAVE','ACADIA','TERRAIN','SANTA FE','TUCSON','SPORTAGE','SORENTO','TELLURIDE','PALISADE','KONA','CX-5','CX-7','CX-9','GRAND CHEROKEE','CHEROKEE','WRANGLER','DURANGO','COMMANDER','RENEGADE','COMPASS','GLE','GLC','GLS','ML','GLK','RX','LX','GX','NX','UX','Q5','Q7','Q8','X3','X5','X6','X1','OUTBACK','FORESTER','CROSSTREK','ASCENT','TOUAREG','TIGUAN','ATLAS','DISCOVERY','RANGE ROVER','LAND CRUISER','SEQUOIA','VERACRUZ','BRAVADA','AZTEC','QX'];
const OTHR = ['TRAILER','BOAT','ATV','GOLF CART','GOLF CAR','BUS','HEAVY DUTY','HEAVY MACHINE','PARTS'];
function classify(make,model) {
  if (MOTO.has(make)) return 'Motorcycles';
  if (HVYW.has(make)) return 'Commercial';
  const m=model.toUpperCase();
  if (OTHR.some(w=>m.includes(w))) return 'Other';
  if (TRUK.some(w=>m===w||m.startsWith(w+' '))) return 'Trucks';
  if (SUVS.some(w=>m===w||m.startsWith(w+' ')||m.includes(w))) return 'SUVs';
  return 'Cars';
}

const RN = {'LAX-CA':'Los Angeles','CHI-IL':'Chicago','DL-TX':'Dallas','NSH-TN':'Nashville','EP-TX':'El Paso','RDU-NC':'Raleigh','SA-TX':'San Antonio','PHX-AZ':'Phoenix','SBC-CA':'San Bernardino','KC-MO':'Kansas City','DET-MI':'Detroit','SJ-CA':'San Jose','OC-CA':'Orange County','SF-CA':'San Francisco','IN-IN':'Indianapolis'};
const rl = r => RN[r]||r;

// ── SVG chart helpers ──────────────────────────────────────────────────────────

// Donut chart
function donutSVG(items, size=160) {
  const CX=size/2, CY=size/2, R=size*.39, IR=size*.22;
  const total=items.reduce((a,b)=>a+b.val,0)||1;
  let angle=-Math.PI/2;
  const paths=items.map((d,i)=>{
    if(!d.val) return '';
    const sw=(d.val/total)*2*Math.PI;
    const x1=CX+R*Math.cos(angle), y1=CY+R*Math.sin(angle);
    angle+=sw;
    const x2=CX+R*Math.cos(angle), y2=CY+R*Math.sin(angle);
    const ix1=CX+IR*Math.cos(angle-sw), iy1=CY+IR*Math.sin(angle-sw);
    const ix2=CX+IR*Math.cos(angle),    iy2=CY+IR*Math.sin(angle);
    const lg=sw>Math.PI?1:0;
    return `<path d="M${x1.toFixed(1)},${y1.toFixed(1)} A${R},${R},0,${lg},1,${x2.toFixed(1)},${y2.toFixed(1)} L${ix2.toFixed(1)},${iy2.toFixed(1)} A${IR},${IR},0,${lg},0,${ix1.toFixed(1)},${iy1.toFixed(1)} Z" fill="${CHART_COLORS[i%CHART_COLORS.length]}"/>`;
  }).join('');
  return `<svg viewBox="0 0 ${size} ${size}" width="${size}" height="${size}" style="flex-shrink:0">${paths}</svg>`;
}

// Horizontal bar chart: items = [{label, val, sub?}]
function hBarSVG(items, {W=520, rowH=30, padL=120, padR=85, title2='', maxVal}) {
  maxVal = maxVal||Math.max(...items.map(d=>d.val),1);
  const plotW=W-padL-padR, H=items.length*rowH+8;
  const rows=items.map((d,i)=>{
    const bw=Math.round((d.val/maxVal)*plotW);
    const y=i*rowH+4;
    const midY=(y+rowH/2+4.5).toFixed(1);
    return `
      <text x="${padL-8}" y="${midY}" text-anchor="end" class="mi-lbl" style="font-size:12px;font-weight:500">${d.label}</text>
      <rect x="${padL}" y="${(y+4).toFixed(1)}" width="${Math.max(bw,3)}" height="${rowH-8}" fill="${CHART_COLORS[0]}" opacity=".72" rx="3"/>
      <text x="${padL+Math.max(bw,3)+6}" y="${midY}" class="mi-lbl" style="font-size:11px">${fmtN(d.val)}</text>
      ${d.sub!=null?`<text x="${W}" y="${midY}" text-anchor="end" class="mi-lbl-val" style="font-size:11px">${d.sub}</text>`:''}`;
  }).join('');
  return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;display:block;max-width:${W}px">
    ${title2?`<text x="${W}" y="0" text-anchor="end" class="mi-lbl" style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase">${title2}</text>`:''}
    ${rows}
  </svg>`;
}

// Grouped bar chart: 3 clusters (for period comparison)
function groupedBarSVG(clusters, {W=680, H=200, padL=70, padR=16, padT=24, padB=36, labels=['Last 60d','Prior 60d','Year Ago']}) {
  const plotW=W-padL-padR, plotH=H-padT-padB;
  const n=clusters.length, gW=plotW/n;
  const bColors=['#f0a500','rgba(240,165,0,.48)','rgba(240,165,0,.22)'];
  const bStroke=['none','none','#f0a500'];
  const maxVal=Math.max(...clusters.flatMap(c=>c.vals),1);

  // y-axis grid
  const yTicks=[0,.25,.5,.75,1].map(f=>{
    const v=Math.round(f*maxVal), y=(padT+plotH-f*plotH).toFixed(1);
    return `<line x1="${padL}" y1="${y}" x2="${W-padR}" y2="${y}" class="mi-grid-l"/>
    <text x="${padL-5}" y="${(+y+4).toFixed(1)}" text-anchor="end" class="mi-lbl">${v>9999?Math.round(v/1000)+'K':fmtN(v)}</text>`;
  }).join('');

  const bW=Math.floor(gW*.2), gap=Math.floor(gW*.04);

  const bars=clusters.map((cl,ci)=>{
    const gx=padL+ci*gW+gW/2;
    const bs=cl.vals.map((v,vi)=>{
      const bh=v?Math.round(v/maxVal*plotH):0;
      const bx=gx+(vi-1)*(bW+gap)-bW/2;
      const by=padT+plotH-bh;
      return `<rect x="${bx.toFixed(1)}" y="${by.toFixed(1)}" width="${bW}" height="${bh}" fill="${bColors[vi]}" stroke="${bStroke[vi]}" stroke-width="1" rx="2"/>`;
    }).join('');
    return `${bs}
    <text x="${gx.toFixed(1)}" y="${H-6}" text-anchor="middle" class="mi-lbl">${cl.label}</text>`;
  }).join('');

  const legend=labels.map((l,i)=>
    `<rect x="${padL+i*110}" y="${padT-16}" width="10" height="10" fill="${bColors[i]}" stroke="${bStroke[i]}" stroke-width="1" rx="2"/>
     <text x="${padL+i*110+14}" y="${padT-7}" class="mi-lbl">${l}</text>`).join('');

  return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;display:block;max-width:${W}px">
    ${yTicks}
    <line x1="${padL}" y1="${padT}" x2="${padL}" y2="${padT+plotH}" class="mi-axis"/>
    <line x1="${padL}" y1="${padT+plotH}" x2="${W-padR}" y2="${padT+plotH}" class="mi-axis"/>
    ${legend}${bars}
  </svg>`;
}

// 12-month trend: volume bars + avg price line
function trendSVG(tData, allMonths) {
  const W=740,H=200,PL=54,PR=16,PT=24,PB=38;
  const PW=W-PL-PR, PHP=H-PT-PB, slot=PW/tData.length, bw=Math.max(6,slot-6);
  const maxCnt=Math.max(...tData.map(d=>d.cnt),1);
  const pAvgs=tData.filter(d=>d.avg>0).map(d=>d.avg);
  const minAvg=Math.min(...pAvgs), maxAvg=Math.max(...pAvgs,1);

  const svgBars=tData.map((d,i)=>{
    const bh=d.cnt?Math.max(3,Math.round(d.cnt/maxCnt*PHP)):0;
    const bx=PL+i*slot+(slot-bw)/2, by=PT+PHP-bh;
    return `<rect x="${bx.toFixed(1)}" y="${by.toFixed(1)}" width="${bw.toFixed(1)}" height="${bh}" class="mi-bar-fill" rx="2"/>`;
  }).join('');

  const pts=tData.filter(d=>d.avg>0).map(d=>{
    const i=allMonths.indexOf(d.m);
    const x=PL+i*slot+slot/2;
    const y=maxAvg===minAvg?PT+PHP/2:PT+PHP-Math.round((d.avg-minAvg)/(maxAvg-minAvg)*PHP);
    return `${x.toFixed(1)},${y.toFixed(1)}`;
  }).join(' ');

  const dots=tData.filter(d=>d.avg>0).map(d=>{
    const i=allMonths.indexOf(d.m);
    const x=PL+i*slot+slot/2;
    const y=maxAvg===minAvg?PT+PHP/2:PT+PHP-Math.round((d.avg-minAvg)/(maxAvg-minAvg)*PHP);
    return `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="3" class="mi-dot"/>`;
  }).join('');

  const xLbls=tData.map((d,i)=>{
    const x=PL+i*slot+slot/2;
    return `<text x="${x.toFixed(1)}" y="${H-6}" text-anchor="middle" class="mi-lbl">${MON[+d.m.split('-')[1]]?.slice(0,3)??''}</text>`;
  }).join('');

  const yTicks=[0,.5,1].map(f=>{
    const v=Math.round(f*maxCnt), y=(PT+PHP-f*PHP).toFixed(1);
    return `<line x1="${PL}" y1="${y}" x2="${W-PR}" y2="${y}" class="mi-grid-l"/>
    <text x="${PL-5}" y="${(+y+4).toFixed(1)}" text-anchor="end" class="mi-lbl">${v>999?Math.round(v/1000)+'K':v}</text>`;
  }).join('');

  return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;display:block;max-width:${W}px">
    ${yTicks}
    <line x1="${PL}" y1="${PT}" x2="${PL}" y2="${PT+PHP}" class="mi-axis"/>
    <line x1="${PL}" y1="${PT+PHP}" x2="${W-PR}" y2="${PT+PHP}" class="mi-axis"/>
    ${svgBars}${xLbls}
    <polyline points="${pts}" class="mi-line"/>
    ${dots}
  </svg>`;
}

// ── Row helpers ────────────────────────────────────────────────────────────────
function pRow(lbl,v1,v2,v3,fn) {
  function dd(c,r){if(c==null||r==null||r===0)return '';const d=(c-r)/r*100;if(Math.abs(d)<0.05)return ' <span class="dz">±0%</span>';return ` <span class="${d>0?'dp':'dn'}">${d>0?'▲':'▼'}${Math.abs(d).toFixed(1)}%</span>`;}
  return `<tr><td>${lbl}</td><td>${v1!=null?fn(v1):'—'}</td><td>${v2!=null?fn(v2):'—'}${dd(v2,v1)}</td><td>${v3!=null?fn(v3):'—'}${dd(v3,v1)}</td></tr>`;
}
function sRow(lbl,v1,v2,v3){return `<tr><td>${lbl}</td><td>${v1??'—'}</td><td>${v2??'—'}</td><td>${v3??'—'}</td></tr>`;}

function kpiCard(title,value,sub,s1,s2,key){
  let dh='';
  if(s1&&s2&&s2[key]){const d=(s1[key]-s2[key])/s2[key]*100;const cls=d>0?'pos':d<0?'neg':'neu';dh=`<div class="kpi-d ${cls}">${d>0?'▲':d<0?'▼':'–'}${Math.abs(d).toFixed(1)}% vs prior 60d</div>`;}
  return `<div class="mi-card"><div class="mi-card-title">${title}</div><div class="kpi-val">${value}</div><div class="kpi-lbl">${sub}</div>${dh}</div>`;
}

// ── Insight helper ────────────────────────────────────────────────────────────
const _bulb = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
const insight = text => `<div class="mi-insight">${_bulb}<span>${text}</span></div>`;
const pct1 = (n,d) => d ? (100*n/d).toFixed(1)+'%' : '—';
const dFmt = (d,pos='<span class="iup">',neg='<span class="idn">') =>
  d > 0.05 ? `${pos}▲${Math.abs(d).toFixed(1)}%</span>` :
  d < -0.05 ? `${neg}▼${Math.abs(d).toFixed(1)}%</span>` : '<span>±0%</span>';

// ── Main render ────────────────────────────────────────────────────────────────
function renderPage(V) {
  const allM=[...new Set(V.filter(v=>v.month).map(v=>v.month))].sort();
  const maxM=allM.at(-1)||'';
  const p1=[addM(maxM,-1),maxM], p2=[addM(maxM,-3),addM(maxM,-2)], p3=[addM(maxM,-13),addM(maxM,-12)];
  const inP=(v,[a,b])=>v.month&&v.month>=a&&v.month<=b;
  const r1=V.filter(v=>inP(v,p1)), r2=V.filter(v=>inP(v,p2)), r3=V.filter(v=>inP(v,p3));
  const s1=stats(r1), s2=stats(r2), s3=stats(r3);

  $('mi-period').innerHTML =
    (AMR_DATA_DATE ? `Dataset: <strong>${AMR_DATA_DATE}</strong> &nbsp;&middot;&nbsp; ` : '') +
    `Showing ${mlbl(p1[0])} – ${mlbl(p1[1])} &nbsp;&middot;&nbsp; ${fmtN(V.length)} total records`;

  const group=(recs,key)=>{const m={};recs.forEach(v=>{const k=v[key]||'Unknown';if(!m[k])m[k]=[];m[k].push(v);});return m;};

  // ── Vehicle types ──────────────────────────────────────────────────────────
  const typeOrder=['Cars','Trucks','SUVs','Motorcycles','Commercial','Other'];
  const tg1={},tg2={};
  r1.forEach(v=>{const t=classify(v.make,v.model);if(!tg1[t])tg1[t]=[];tg1[t].push(v);});
  r2.forEach(v=>{const t=classify(v.make,v.model);if(!tg2[t])tg2[t]=[];tg2[t].push(v);});

  const typeStats=typeOrder.map(t=>({t,st1:stats(tg1[t]||[]),st2:stats(tg2[t]||[])})).filter(x=>x.st1&&x.st1.count>=5);

  const donutItems=typeStats.map((x,i)=>({label:x.t,val:x.st1.count,color:CHART_COLORS[i]}));
  const donut=donutSVG(donutItems,160);
  const donutLegend=donutItems.map((d,i)=>{
    const p=s1?.count?(100*d.val/s1.count).toFixed(1):'0';
    return `<div class="donut-key"><div class="donut-swatch" style="background:${d.color}"></div><span style="flex:1">${d.label}</span><span style="color:var(--text-muted);font-size:12px;min-width:38px;text-align:right">${p}%</span><span style="font-weight:600;font-size:12px;margin-left:10px;min-width:46px;text-align:right">${fmtN(d.val)}</span><span style="color:var(--text-muted);font-size:12px;margin-left:10px">${fmtD(typeStats[i].st1.avg)}</span></div>`;
  }).join('');

  // ── Regions ────────────────────────────────────────────────────────────────
  const rg1=group(r1,'region'), rg2=group(r2,'region');
  const regionData=Object.entries(rg1)
    .map(([k,recs])=>{const p=s1?.count?(100*recs.length/s1.count).toFixed(1):'0';return{label:rl(k),val:recs.length,sub:`${p}% · ${fmtD(stats(recs)?.avg??0)}`,st2:stats(rg2[k]||[])};})
    .sort((a,b)=>b.val-a.val).slice(0,12);

  // ── Makes ──────────────────────────────────────────────────────────────────
  const mg1=group(r1,'make'), mg2=group(r2,'make');
  const makeData=Object.entries(mg1)
    .map(([k,recs])=>{const p=s1?.count?(100*recs.length/s1.count).toFixed(1):'0';return{label:cap(k),val:recs.length,sub:`${p}% · ${fmtD(stats(recs)?.avg??0)}`,st2:stats(mg2[k]||[])};})
    .sort((a,b)=>b.val-a.val).slice(0,15);

  // ── Period grouped bar chart ───────────────────────────────────────────────
  const periodClusters=[
    {label:'Volume',    vals:[s1?.count??0, s2?.count??0, s3?.count??0]},
    {label:'Avg Price', vals:[s1?.avg??0,   s2?.avg??0,   s3?.avg??0]},
    {label:'Median',    vals:[s1?.median??0,s2?.median??0,s3?.median??0]},
  ];
  const gBar=groupedBarSVG(periodClusters,{W:620,H:200,labels:[
    `${mlbl(p1[0])}–${mlbl(p1[1])}`,
    `${mlbl(p2[0])}–${mlbl(p2[1])}`,
    `${mlbl(p3[0])}–${mlbl(p3[1])}`
  ]});

  // ── Odometer ───────────────────────────────────────────────────────────────
  const withOdo=r1.filter(v=>v.odo>0), noOdo=r1.filter(v=>!v.odo||v.odo<=0);
  const stWithOdo=stats(withOdo), stNoOdo=stats(noOdo);
  const noOdoPct=(r1.length?(100*noOdo.length/r1.length):0);
  const odoPrem=(stWithOdo&&stNoOdo)?stWithOdo.avg-stNoOdo.avg:0;
  const odoPremPct=(stNoOdo&&stNoOdo.avg)?(odoPrem/stNoOdo.avg*100):0;

  const bands=[['Under 25K',0,25000],['25K–50K',25000,50000],['50K–75K',50000,75000],['75K–100K',75000,100000],['100K–150K',100000,150000],['150K+',150000,Infinity],['No Reading',-1,-1]];
  const odoData=bands.map(([lbl,lo,hi])=>{
    const recs=lo===-1?r1.filter(v=>!v.odo||v.odo<=0):r1.filter(v=>v.odo>0&&v.odo>=lo&&v.odo<hi);
    const st=stats(recs);
    if(!st||st.count<5) return null;
    const p=(r1.length?(100*recs.length/r1.length):0).toFixed(1);
    return {label:lbl, val:st.count, sub:`${p}% · ${fmtD(st.avg)}`};
  }).filter(Boolean);

  // ── Condition premiums ─────────────────────────────────────────────────────
  const sKey=stats(r1.filter(v=>v.has_key)), sNoKey=stats(r1.filter(v=>v.no_key));
  const sSt=stats(r1.filter(v=>v.starts)),   sNoSt=stats(r1.filter(v=>!v.starts&&(v.has_key||v.no_key)));
  const kPrem=(sKey&&sNoKey)?sKey.avg-sNoKey.avg:null;
  const sPrem=(sSt&&sNoSt)?sSt.avg-sNoSt.avg:null;
  const stBest=stats(r1.filter(v=>v.has_key&&v.starts));
  const stWorst=stats(r1.filter(v=>v.no_key&&!v.starts));
  const fullPrem=(stBest&&stWorst)?stBest.avg-stWorst.avg:null;

  // ── Doc mix ────────────────────────────────────────────────────────────────
  const dmap={};
  r1.forEach(v=>{if(v.doc)dmap[v.doc]=(dmap[v.doc]||0)+1;});
  const dtot=Object.values(dmap).reduce((a,b)=>a+b,0);
  const docBars=Object.entries(dmap).sort((a,b)=>b[1]-a[1]).map(([d,n])=>{
    const p=dtot?(100*n/dtot):0;
    return `<div class="doc-row"><span class="doc-lbl">${d}</span><div class="doc-bar"><div class="doc-fill" style="width:${p.toFixed(1)}%"></div></div><span class="doc-pct">${p.toFixed(1)}%</span></div>`;
  }).join('');

  // ── Insight data ───────────────────────────────────────────────────────────
  // Period
  const volChgPrior  = s1&&s2&&s2.count?((s1.count-s2.count)/s2.count*100):null;
  const priceChgPrior= s1&&s2&&s2.avg?  ((s1.avg  -s2.avg  )/s2.avg  *100):null;
  const priceChgYoY  = s1&&s3&&s3.avg?  ((s1.avg  -s3.avg  )/s3.avg  *100):null;

  // Documentation
  const stTitle=stats(r1.filter(v=>v.doc==='Title')), stSalv=stats(r1.filter(v=>v.doc==='Salvage'));
  const stAband=stats(r1.filter(v=>v.doc==='Abandoned'));
  const docPrem=(stTitle&&stSalv)?stTitle.avg-stSalv.avg:null;

  // Regional spread
  const regionStats=Object.entries(rg1).map(([k,recs])=>({r:k,st:stats(recs)})).filter(x=>x.st&&x.st.count>=15).sort((a,b)=>b.st.avg-a.st.avg);
  const topReg=regionStats[0], botReg=regionStats.at(-1);

  // Make liquidity
  const top2vol=(makeData[0]?.val??0)+(makeData[1]?.val??0);
  const top2pct=s1?.count?(100*top2vol/s1.count):0;

  // Vehicle type
  const carSt=stats(tg1['Cars']||[]), truckSt=stats(tg1['Trucks']||[]), suvSt=stats(tg1['SUVs']||[]);
  const lightAvg=s1?.avg??0;

  // ── 12-month trend ─────────────────────────────────────────────────────────
  const tMonths=allM.slice(-12);
  const tData=tMonths.map(m=>{const r=V.filter(v=>v.month===m);return{m,cnt:r.length,avg:r.length?Math.round(r.reduce((a,v)=>a+v.price,0)/r.length):0};});
  const trend=trendSVG(tData,tMonths);

  // ── Assemble ───────────────────────────────────────────────────────────────
  $('mi-body').innerHTML = `

    <!-- KPIs -->
    <div class="mi-g3">
      ${kpiCard('Total Sales (60d)', fmtN(s1?.count??0), `${mlbl(p1[0])} – ${mlbl(p1[1])}`, s1, s2, 'count')}
      ${kpiCard('Avg Sale Price', fmtD(s1?.avg??0), 'Last 60 days', s1, s2, 'avg')}
      ${kpiCard('Median Sale Price', fmtD(s1?.median??0), 'Last 60 days', s1, s2, 'median')}
    </div>

    <!-- Period chart + table -->
    <div class="mi-section">
      <h2>60-Day Period Comparison</h2>
      <p>Current vs prior 60 days and the same period one year ago.</p>
    </div>
    ${(()=>{
      if(volChgPrior===null) return '';
      const vd=dFmt(volChgPrior), pd=dFmt(priceChgPrior??0), yd=priceChgYoY!==null?dFmt(priceChgYoY):'';
      return insight(`Volume is ${vd} and avg price is ${pd} vs the prior 60 days.${yd?' Year-over-year, prices are '+yd+'.':''}`);
    })()}
    <div class="mi-g2">
      <div class="mi-card">
        <div class="mi-card-title">Volume &amp; Price by Period</div>
        <div class="mi-chart">${gBar}</div>
      </div>
      <div class="mi-card" style="overflow-x:auto">
        <div class="mi-card-title">Detail</div>
        <table class="mi-tbl">
          <thead><tr>
            <th>Metric</th>
            <th>${mlbl(p1[0])}–${mlbl(p1[1])}</th>
            <th>${mlbl(p2[0])}–${mlbl(p2[1])}</th>
            <th>YoY ${mlbl(p3[1])}</th>
          </tr></thead>
          <tbody>
            ${pRow('Volume',   s1?.count, s2?.count, s3?.count, fmtN)}
            ${pRow('Avg $',    s1?.avg,   s2?.avg,   s3?.avg,   fmtD)}
            ${pRow('Median',   s1?.median,s2?.median,s3?.median,fmtD)}
            ${sRow('Key %',    s1?((100*s1.withKey/s1.count).toFixed(1)+'%'):'—', s2?((100*s2.withKey/s2.count).toFixed(1)+'%'):'—', s3?((100*s3.withKey/s3.count).toFixed(1)+'%'):'—')}
            ${sRow('Starts %', s1?((100*s1.starts/s1.count).toFixed(1)+'%'):'—', s2?((100*s2.starts/s2.count).toFixed(1)+'%'):'—', s3?((100*s3.starts/s3.count).toFixed(1)+'%'):'—')}
          </tbody>
        </table>
      </div>
    </div>

    <!-- Vehicle type -->
    <div class="mi-section">
      <h2>By Vehicle Type</h2>
      <p>Last 60 days. Classified by make and model — edge cases may be approximate.</p>
    </div>
    <div class="mi-card" style="margin-bottom:16px">
      <div class="donut-wrap">
        ${donut}
        <div class="donut-legend">${donutLegend}</div>
      </div>
      ${(()=>{
        if(!truckSt||!suvSt||!carSt) return '';
        const truckPrem=truckSt.avg-lightAvg, suvPrem=suvSt.avg-lightAvg;
        const topType=truckSt.avg>suvSt.avg?`Trucks average <span class="hi">${fmtD(truckSt.avg)}</span> — <span class="iup">+${fmtD(Math.abs(truckPrem))}</span> above the overall avg`:`SUVs average <span class="hi">${fmtD(suvSt.avg)}</span> — <span class="iup">+${fmtD(Math.abs(suvPrem))}</span> above the overall avg`;
        return insight(`${topType}. Cars are the highest-volume category and the most liquid for quick resale.`);
      })()}
    </div>

    <!-- Makes + Regions -->
    <div class="mi-section"><h2>Top Makes &amp; Regions</h2>
      <p>Last 60 days — bars show volume, right column shows avg sale price.</p>
    </div>
    <div class="mi-g2">
      <div class="mi-card">
        <div class="mi-card-title">Top 15 Makes &nbsp;<span style="float:right;font-weight:400">% vol &nbsp;·&nbsp; Avg $</span></div>
        <div class="mi-chart">${hBarSVG(makeData,{W:480,rowH:28,padL:100,padR:108})}</div>
        ${(()=>{
          if(!makeData[0]||!makeData[1]) return '';
          return insight(`<span class="hi">${makeData[0].label}</span> and <span class="hi">${makeData[1].label}</span> account for <span class="hi">${top2pct.toFixed(0)}%</span> of volume — the highest-liquidity makes for quick resale. ${makeData[0].label} leads both volume and avg price among top makes.`);
        })()}
      </div>
      <div class="mi-card">
        <div class="mi-card-title">By Region &nbsp;<span style="float:right;font-weight:400">% vol &nbsp;·&nbsp; Avg $</span></div>
        <div class="mi-chart">${hBarSVG(regionData,{W:480,rowH:28,padL:100,padR:108})}</div>
        ${(()=>{
          if(!topReg||!botReg||topReg.r===botReg.r) return '';
          const spread=topReg.st.avg-botReg.st.avg;
          return insight(`<span class="hi">${rl(topReg.r)}</span> commands the highest avg price at <span class="hi">${fmtD(topReg.st.avg)}</span> vs <span class="hi">${fmtD(botReg.st.avg)}</span> in ${rl(botReg.r)} — a <span class="hi">${fmtD(spread)}</span> regional spread on identical vehicles.`);
        })()}
      </div>
    </div>

    <!-- Odometer + Condition -->
    <div class="mi-section"><h2>Pricing by Mileage &amp; Condition</h2></div>
    <div class="mi-g2">
      <div class="mi-card">
        <div class="mi-card-title">By Odometer Band &nbsp;<span style="float:right;font-weight:400">% of total &nbsp;·&nbsp; Avg $</span></div>
        <div class="mi-chart">${hBarSVG(odoData,{W:440,rowH:30,padL:100,padR:108})}</div>
        ${(()=>{
          if(!stWithOdo||!stNoOdo||odoPrem<=0) return '';
          return insight(`<span class="hi">${noOdoPct.toFixed(0)}%</span> of vehicles arrive with no odometer reading. Those with a readable mileage average <span class="hi">${fmtD(odoPrem)} more</span> (<span class="iup">+${odoPremPct.toFixed(0)}%</span>). <strong>Bring a jump box</strong> — starting a car lets you capture the odometer, which buyers pay a significant premium for. The reading confirms the mileage band and unlocks that value immediately.`);
        })()}
      </div>
      <div class="mi-card">
        <div class="mi-card-title">Condition Premiums (last 60d)</div>
        <div class="cond-grid">
          <div class="cond-item">
            <div class="cond-lbl">Key Present vs No Key</div>
            <div class="cond-val">${kPrem!=null?(kPrem>=0?'+':'')+fmtD(kPrem):'—'}</div>
            <div class="cond-sub">${sKey?fmtD(sKey.avg):'-'} vs ${sNoKey?fmtD(sNoKey.avg):'-'}</div>
            <div class="cond-sub" style="margin-top:4px">${sKey&&r1.length?pct1(sKey.count,r1.length)+' have key':''}${sNoKey&&r1.length?' · '+pct1(sNoKey.count,r1.length)+' no key':''}</div>
          </div>
          <div class="cond-item">
            <div class="cond-lbl">Starts vs Doesn't</div>
            <div class="cond-val">${sPrem!=null?(sPrem>=0?'+':'')+fmtD(sPrem):'—'}</div>
            <div class="cond-sub">${sSt?fmtD(sSt.avg):'-'} vs ${sNoSt?fmtD(sNoSt.avg):'-'}</div>
            <div class="cond-sub" style="margin-top:4px">${sSt&&r1.length?pct1(sSt.count,r1.length)+' start':''}${sNoSt&&r1.length?' · '+pct1(sNoSt.count,r1.length)+' don\'t':''}</div>
          </div>
        </div>
        ${(()=>{
          if(!fullPrem||!kPrem||!sPrem) return '';
          return insight(`A vehicle with a key that starts sells for <span class="hi">${fmtD(fullPrem)} more</span> than a no-key, non-starter. Key alone adds <span class="hi">+${fmtD(kPrem)}</span>; starts alone adds <span class="hi">+${fmtD(sPrem)}</span>. A spare key and a jump box are two of the cheapest ways to move vehicles up the value ladder.`);
        })()}
        <div class="mi-card-title" style="margin-top:18px">Documentation Mix (last 60d)</div>
        ${docBars}
        ${(()=>{
          if(!docPrem) return '';
          const abPrem=stTitle&&stAband?stTitle.avg-stAband.avg:null;
          return insight(`Title vehicles average <span class="hi">${fmtD(stTitle?.avg??0)}</span> — <span class="iup">+${fmtD(docPrem)}</span> more than Salvage.${abPrem?` Abandoned vehicles (often the cleanest paper) average <span class="hi">${fmtD(stAband?.avg??0)}</span>.`:''} Know your document type before bidding — it sets your ceiling.`);
        })()}
      </div>
    </div>

    <!-- 12-month trend -->
    <div class="mi-section">
      <h2>12-Month Trend</h2>
      <p>Monthly volume (bars) and average sale price (line) across all records in the dataset.</p>
    </div>
    <div class="mi-card" style="margin-bottom:60px">
      <div class="mi-legend">
        <span><i class="leg-bar"></i> Volume</span>
        <span><i class="leg-line"></i> Avg Price</span>
      </div>
      <div class="mi-chart">${trend}</div>
    </div>
  `;
}

// ── Boot ──────────────────────────────────────────────────────────────────────
(async()=>{
  try {
    const res=await fetch('/data-json?v=<?= $amr_data_version ?>');
    if(!res.ok) throw new Error('HTTP '+res.status);
    const data=await res.json();
    const V=data.records.map(r=>({
      make:String(data.makes[r[0]]),model:String(data.models[r[1]]),
      year:r[2],price:r[3],has_key:!!(r[4]&1),no_key:!!(r[4]&2),starts:!!(r[4]&4),
      region:data.regions[r[5]],doc:data.docs[r[6]],odo:r[7],
      month:r[8]>=0?data.months[r[8]]:'',
    }));
    renderPage(V);
  } catch(e) {
    $('mi-body').innerHTML='<div class="mi-load" style="color:var(--text-muted)">Could not load market data. Try refreshing.</div>';
  }
})();
</script>

<div class="mi-print-footer container">
  &copy; <?= date('Y') ?> Autura NewCo, LLC. &nbsp;&middot;&nbsp; <a href="https://autura.com" style="color:inherit">autura.com</a> &nbsp;&middot;&nbsp; Autura Market Report &nbsp;&middot;&nbsp; Confidential — Internal Use Only
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
