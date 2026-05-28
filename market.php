<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Market Insights';
$meta_desc  = 'Autura marketplace pricing trends — volume, avg price, regional breakdown, and 60-day comparisons.';
$body_class = 'page-market';
$canonical  = '/market';
$amr_data_version = file_exists(__DIR__ . '/data/amr-data.json') ? filemtime(__DIR__ . '/data/amr-data.json') : 0;

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

/* grid */
.mi-g3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 16px; }
.mi-g2 { display: grid; grid-template-columns: repeat(2,1fr); gap: 16px; margin-bottom: 16px; }
@media (max-width: 860px) { .mi-g2 { grid-template-columns: 1fr; } }
@media (max-width: 640px) { .mi-g3 { grid-template-columns: 1fr; } }

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
.mi-tbl td:first-child { text-align: left; padding-left: 0; color: var(--text); font-weight: 500; }
.mi-tbl tr:last-child td { border-bottom: none; }
.mi-tbl tr:hover td { background: var(--surface-2); }
.mi-tbl .rank { color: var(--text-muted); font-size: 11px; }

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
.doc-fill { height: 100%; border-radius: 4px; background: var(--accent); opacity: .65; transition: width .4s; }
.doc-pct  { font-size: 11px; color: var(--text-muted); min-width: 38px; text-align: right; }

/* chart */
.mi-chart { overflow-x: auto; }
.mi-chart svg { display: block; width: 100%; }
.mi-bar-fill { fill: var(--accent); opacity: .75; }
.mi-line     { fill: none; stroke: var(--accent); stroke-width: 2; stroke-dasharray: none; }
.mi-dot      { fill: var(--bg); stroke: var(--accent); stroke-width: 2; }
.mi-axis     { stroke: var(--border); stroke-width: 1; }
.mi-lbl      { font-family: var(--font-body); font-size: 11px; fill: var(--text-muted); }
.mi-lbl-val  { font-family: var(--font-body); font-size: 11px; fill: var(--text); }

.mi-legend { display: flex; gap: 18px; font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }
.mi-legend span { display: inline-flex; align-items: center; gap: 5px; }
.leg-bar  { display: inline-block; width: 12px; height: 8px; background: var(--accent); opacity: .75; border-radius: 2px; }
.leg-line { display: inline-block; width: 18px; height: 2px; background: var(--accent); }

/* loading */
.mi-load { text-align: center; padding: 80px 24px; color: var(--text-muted); }
@keyframes mi-spin { to { transform: rotate(360deg); } }
</style>';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <section class="mi-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/" style="color:var(--text-muted);text-decoration:none;">&larr; Marketplace Report</a>
    </p>
    <h1>Market Insights</h1>
    <p class="mi-period" id="mi-period">Loading…</p>
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
// ── Helpers ───────────────────────────────────────────────────────────────────
const $    = id => document.getElementById(id);
const fmtD = n  => '$' + Math.round(n).toLocaleString();
const fmtN = n  => Math.round(n).toLocaleString();
const cap  = s  => s ? s.charAt(0) + s.slice(1).toLowerCase() : s;

function med(arr) {
  if (!arr.length) return 0;
  const s = [...arr].sort((a,b)=>a-b);
  const m = s.length >> 1;
  return s.length & 1 ? s[m] : Math.round((s[m-1]+s[m])/2);
}

function stats(recs) {
  if (!recs.length) return null;
  const prices = recs.map(r=>r.price);
  const sum    = prices.reduce((a,b)=>a+b,0);
  return {
    count:   recs.length,
    avg:     Math.round(sum/recs.length),
    median:  med(prices),
    withKey: recs.filter(r=>r.has_key).length,
    starts:  recs.filter(r=>r.starts).length,
  };
}

function delta(curr, prev, key) {
  if (!curr || !prev || !prev[key]) return '<span class="dz">—</span>';
  const pct = (curr[key] - prev[key]) / prev[key] * 100;
  if (Math.abs(pct) < 0.05) return '<span class="dz">±0%</span>';
  const cls = pct > 0 ? 'dp' : 'dn';
  return `<span class="${cls}">${pct>0?'▲':'▼'}${Math.abs(pct).toFixed(1)}%</span>`;
}

// ── Month helpers ─────────────────────────────────────────────────────────────
function addM(ym, n) {
  if (!ym) return '';
  let [y,m] = ym.split('-').map(Number);
  m += n;
  while (m > 12) { m -= 12; y++; }
  while (m <= 0)  { m += 12; y--; }
  return `${y}-${String(m).padStart(2,'0')}`;
}
const MON = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const mlbl = ym => ym ? MON[+ym.split('-')[1]] + ' ' + ym.split('-')[0] : '—';

// ── Vehicle classification ────────────────────────────────────────────────────
const MOTO = new Set(['HARLEY-DAVIDSON','KAWASAKI','DUCATI','ROYAL ENFIELD','HYOSUNG','GENUINE SCOOTERS']);
const HVYW = new Set(['FREIGHTLINER','INTERNATIONAL','KENWORTH','PETERBILT','MACK','VOLVO TRUCK','HINO','ISUZU','SPARTAN']);
const TRUK = ['F-150','F-250','F-350','F-450','F-550','SILVERADO 1500','SILVERADO 2500','SILVERADO 3500',
  'SILVERADO','RAM 1500','RAM 2500','RAM 3500','RAM PICKUP','SIERRA 1500','SIERRA 2500','SIERRA 3500','SIERRA',
  'TUNDRA','TACOMA','RANGER','COLORADO','CANYON','FRONTIER','TITAN','RIDGELINE','MAVERICK','C/K 1500',
  'C/K 2500','C/K 3500','PICKUP'];
const SUVS = ['EXPLORER','EXPEDITION','TAHOE','SUBURBAN','EQUINOX','TRAVERSE','BLAZER','TRAILBLAZER',
  'HIGHLANDER','4RUNNER','RAV4','CR-V','PILOT','PASSPORT','MDX','RDX','PATHFINDER','MURANO','ROGUE',
  'ARMADA','XTERRA','ESCAPE','EDGE','NAVIGATOR','ESCALADE','YUKON','ENVOY','ENCLAVE','ACADIA','TERRAIN',
  'SANTA FE','TUCSON','SPORTAGE','SORENTO','TELLURIDE','PALISADE','KONA','CX-5','CX-7','CX-9',
  'GRAND CHEROKEE','CHEROKEE','WRANGLER','DURANGO','COMMANDER','RENEGADE','COMPASS',
  'GLE','GLC','GLS','ML','GLK','RX','LX','GX','NX','UX','Q5','Q7','Q8','X3','X5','X6','X1',
  'OUTBACK','FORESTER','CROSSTREK','ASCENT','TOUAREG','TIGUAN','ATLAS','DISCOVERY',
  'RANGE ROVER','LAND CRUISER','SEQUOIA','VERACRUZ','BRAVADA','AZTEC','QX'];
const OTHR = ['TRAILER','BOAT','ATV','GOLF CART','GOLF CAR','BUS','HEAVY DUTY','HEAVY MACHINE','PARTS'];

function classify(make, model) {
  if (MOTO.has(make)) return 'Motorcycles';
  if (HVYW.has(make)) return 'Commercial';
  const m = model.toUpperCase();
  if (OTHR.some(w => m.includes(w))) return 'Other';
  if (TRUK.some(w => m === w || m.startsWith(w+' '))) return 'Trucks';
  if (SUVS.some(w => m === w || m.startsWith(w+' ') || m.includes(w))) return 'SUVs';
  return 'Cars';
}

// ── Region display ────────────────────────────────────────────────────────────
const RN = {
  'LAX-CA':'Los Angeles','CHI-IL':'Chicago','DL-TX':'Dallas','NSH-TN':'Nashville',
  'EP-TX':'El Paso','RDU-NC':'Raleigh','SA-TX':'San Antonio','PHX-AZ':'Phoenix',
  'SBC-CA':'San Bernardino','KC-MO':'Kansas City','DET-MI':'Detroit','SJ-CA':'San Jose',
  'OC-CA':'Orange County','SF-CA':'San Francisco','IN-IN':'Indianapolis',
};
const rl = r => RN[r] || r;

// ── Render ────────────────────────────────────────────────────────────────────
function kpiCard(title, value, sub, s1, s2, key) {
  let dHtml = '';
  if (s1 && s2 && s2[key]) {
    const d = (s1[key]-s2[key])/s2[key]*100;
    const cls = d>0?'pos':d<0?'neg':'neu';
    dHtml = `<div class="kpi-d ${cls}">${d>0?'▲':d<0?'▼':'–'}${Math.abs(d).toFixed(1)}% vs prior 60d</div>`;
  }
  return `<div class="mi-card">
    <div class="mi-card-title">${title}</div>
    <div class="kpi-val">${value}</div>
    <div class="kpi-lbl">${sub}</div>
    ${dHtml}
  </div>`;
}

function pRow(lbl, v1, v2, v3, fn) {
  function dd(curr, ref) {
    if (curr==null||ref==null||ref===0) return '';
    const d=(curr-ref)/ref*100;
    if (Math.abs(d)<0.05) return ' <span class="dz">±0%</span>';
    return ` <span class="${d>0?'dp':'dn'}">${d>0?'▲':'▼'}${Math.abs(d).toFixed(1)}%</span>`;
  }
  return `<tr>
    <td>${lbl}</td>
    <td>${v1!=null?fn(v1):'—'}</td>
    <td>${v2!=null?fn(v2):'—'}${dd(v2,v1)}</td>
    <td>${v3!=null?fn(v3):'—'}${dd(v3,v1)}</td>
  </tr>`;
}

function strRow(lbl, v1, v2, v3) {
  return `<tr><td>${lbl}</td><td>${v1??'—'}</td><td>${v2??'—'}</td><td>${v3??'—'}</td></tr>`;
}

function tblRows(map, map2, sortKey, topN, cols) {
  return Object.entries(map)
    .map(([k,recs])=>({k, st:stats(recs), st2:stats(map2[k]||[])}))
    .filter(x=>x.st && x.st.count>=5)
    .sort((a,b)=>b.st[sortKey]-a.st[sortKey])
    .slice(0,topN)
    .map((x,i)=>cols(x,i))
    .join('');
}

function renderPage(V) {
  // periods
  const allM = [...new Set(V.filter(v=>v.month).map(v=>v.month))].sort();
  const maxM  = allM.at(-1) || '';
  const p1    = [addM(maxM,-1), maxM];
  const p2    = [addM(maxM,-3), addM(maxM,-2)];
  const p3    = [addM(maxM,-13), addM(maxM,-12)];
  const inP   = (v,[a,b]) => v.month && v.month>=a && v.month<=b;

  const r1=V.filter(v=>inP(v,p1)), r2=V.filter(v=>inP(v,p2)), r3=V.filter(v=>inP(v,p3));
  const s1=stats(r1), s2=stats(r2), s3=stats(r3);

  $('mi-period').textContent =
    `${mlbl(p1[0])} – ${mlbl(p1[1])}  ·  ${fmtN(V.length)} total records in dataset`;

  // helpers for grouping
  const group = (recs, key) => {
    const m={};
    recs.forEach(v=>{ const k=v[key]||'Unknown'; if(!m[k])m[k]=[]; m[k].push(v); });
    return m;
  };

  // ── vehicle type ──────────────────────────────────────────────────────────
  const typeOrder = ['Cars','Trucks','SUVs','Motorcycles','Commercial','Other'];
  const tg1={}, tg2={};
  r1.forEach(v=>{ const t=classify(v.make,v.model); if(!tg1[t])tg1[t]=[]; tg1[t].push(v); });
  r2.forEach(v=>{ const t=classify(v.make,v.model); if(!tg2[t])tg2[t]=[]; tg2[t].push(v); });

  const typeRows = typeOrder.map(t=>{
    const st1=stats(tg1[t]||[]), st2b=stats(tg2[t]||[]);
    if(!st1||st1.count<5) return '';
    return `<tr>
      <td>${t}</td>
      <td>${fmtN(st1.count)}</td>
      <td>${fmtD(st1.avg)}</td>
      <td>${fmtD(st1.median)}</td>
      <td>${(100*st1.withKey/st1.count).toFixed(0)}%</td>
      <td>${delta(st1,st2b,'avg')}</td>
    </tr>`;
  }).filter(Boolean).join('');

  // ── region ────────────────────────────────────────────────────────────────
  const rg1=group(r1,'region'), rg2=group(r2,'region');
  const regionRows = tblRows(rg1, rg2, 'count', 12,
    ({k,st,st2},i)=>`<tr>
      <td>${rl(k)}</td>
      <td>${fmtN(st.count)}</td>
      <td>${fmtD(st.avg)}</td>
      <td>${fmtD(st.median)}</td>
      <td>${delta(st,st2,'avg')}</td>
    </tr>`);

  // ── makes ─────────────────────────────────────────────────────────────────
  const mg1=group(r1,'make'), mg2=group(r2,'make');
  const makeRows = tblRows(mg1, mg2, 'count', 15,
    ({k,st,st2},i)=>`<tr>
      <td class="rank">${i+1}</td>
      <td>${cap(k)}</td>
      <td>${fmtN(st.count)}</td>
      <td>${fmtD(st.avg)}</td>
      <td>${fmtD(st.median)}</td>
      <td>${(100*st.withKey/st.count).toFixed(0)}%</td>
      <td>${delta(st,st2,'avg')}</td>
    </tr>`);

  // ── odometer bands ────────────────────────────────────────────────────────
  const bands=[
    ['Under 25K',0,25000],['25K–50K',25000,50000],['50K–75K',50000,75000],
    ['75K–100K',75000,100000],['100K–150K',100000,150000],['150K+',150000,Infinity],
    ['No Reading',-1,-1],
  ];
  const odoRows = bands.map(([lbl,lo,hi])=>{
    const recs=lo===-1?r1.filter(v=>!v.odo||v.odo<=0):r1.filter(v=>v.odo>0&&v.odo>=lo&&v.odo<hi);
    const st=stats(recs);
    if(!st||st.count<5) return '';
    return `<tr>
      <td>${lbl}</td>
      <td>${fmtN(st.count)}</td>
      <td>${fmtD(st.avg)}</td>
      <td>${fmtD(st.median)}</td>
    </tr>`;
  }).filter(Boolean).join('');

  // ── condition premiums ────────────────────────────────────────────────────
  const sKey  = stats(r1.filter(v=>v.has_key));
  const sNoKey= stats(r1.filter(v=>v.no_key));
  const sSt   = stats(r1.filter(v=>v.starts));
  const sNoSt = stats(r1.filter(v=>!v.starts&&(v.has_key||v.no_key)));
  const kPrem = (sKey&&sNoKey) ? sKey.avg-sNoKey.avg : null;
  const sPrem = (sSt&&sNoSt)  ? sSt.avg-sNoSt.avg   : null;

  // ── doc mix ───────────────────────────────────────────────────────────────
  const dmap={};
  r1.forEach(v=>{ if(v.doc) dmap[v.doc]=(dmap[v.doc]||0)+1; });
  const dtot=Object.values(dmap).reduce((a,b)=>a+b,0);
  const docBars=Object.entries(dmap).sort((a,b)=>b[1]-a[1]).map(([d,n])=>{
    const p=dtot?(100*n/dtot):0;
    return `<div class="doc-row">
      <span class="doc-lbl">${d}</span>
      <div class="doc-bar"><div class="doc-fill" style="width:${p.toFixed(1)}%"></div></div>
      <span class="doc-pct">${p.toFixed(1)}%</span>
    </div>`;
  }).join('');

  // ── 12-month trend ────────────────────────────────────────────────────────
  const tMonths = allM.slice(-12);
  const tData   = tMonths.map(m=>{ const r=V.filter(v=>v.month===m); return {m,cnt:r.length,avg:r.length?Math.round(r.reduce((a,v)=>a+v.price,0)/r.length):0}; });
  const maxCnt  = Math.max(...tData.map(d=>d.cnt),1);
  const pAvgs   = tData.filter(d=>d.avg>0).map(d=>d.avg);
  const minAvg  = Math.min(...pAvgs), maxAvg=Math.max(...pAvgs,1);
  const W=740,H=190,PL=52,PR=16,PT=20,PB=38;
  const PW=W-PL-PR, PHP=H-PT-PB, slot=PW/tData.length, bw=Math.max(6,slot-6);

  const svgBars = tData.map((d,i)=>{
    const bh=d.cnt?Math.max(3,Math.round(d.cnt/maxCnt*PHP)):0;
    const bx=PL+i*slot+(slot-bw)/2, by=PT+PHP-bh;
    return `<rect x="${bx.toFixed(1)}" y="${by.toFixed(1)}" width="${bw.toFixed(1)}" height="${bh}" class="mi-bar-fill" rx="2"/>`;
  }).join('');

  const pts = tData.filter(d=>d.avg>0).map((d,_,arr)=>{
    const i=tMonths.indexOf(d.m);
    const x=PL+i*slot+slot/2;
    const y=maxAvg===minAvg ? PT+PHP/2 : PT+PHP-Math.round((d.avg-minAvg)/(maxAvg-minAvg)*PHP);
    return `${x.toFixed(1)},${y.toFixed(1)}`;
  }).join(' ');

  const svgDots = tData.filter(d=>d.avg>0).map(d=>{
    const i=tMonths.indexOf(d.m);
    const x=PL+i*slot+slot/2;
    const y=maxAvg===minAvg ? PT+PHP/2 : PT+PHP-Math.round((d.avg-minAvg)/(maxAvg-minAvg)*PHP);
    return `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="3" class="mi-dot"/>`;
  }).join('');

  const svgXlbl = tData.map((d,i)=>{
    const x=PL+i*slot+slot/2;
    return `<text x="${x.toFixed(1)}" y="${H-6}" text-anchor="middle" class="mi-lbl">${MON[+d.m.split('-')[1]]?.slice(0,3)??''}</text>`;
  }).join('');

  const yTicks=[0,.5,1].map(f=>{
    const v=Math.round(f*maxCnt), y=PT+PHP-Math.round(f*PHP);
    return `<text x="${PL-5}" y="${y+4}" text-anchor="end" class="mi-lbl">${v>999?Math.round(v/1000)+'K':v}</text>
    <line x1="${PL}" y1="${y}" x2="${W-PR}" y2="${y}" stroke="var(--border)" stroke-width=".5" stroke-dasharray="3,3"/>`;
  }).join('');

  const trendSVG=`<svg viewBox="0 0 ${W} ${H}" style="width:100%;max-width:${W}px;display:block">
    ${yTicks}
    <line x1="${PL}" y1="${PT}" x2="${PL}" y2="${PT+PHP}" class="mi-axis"/>
    <line x1="${PL}" y1="${PT+PHP}" x2="${W-PR}" y2="${PT+PHP}" class="mi-axis"/>
    ${svgBars}${svgXlbl}
    <polyline points="${pts}" class="mi-line"/>
    ${svgDots}
  </svg>`;

  // ── assemble ──────────────────────────────────────────────────────────────
  $('mi-body').innerHTML = `

    <!-- KPIs -->
    <div class="mi-g3">
      ${kpiCard('Total Sales (60d)',   fmtN(s1?.count??0),  `${mlbl(p1[0])} – ${mlbl(p1[1])}`, s1, s2, 'count')}
      ${kpiCard('Avg Sale Price',      fmtD(s1?.avg??0),    'Last 60 days',  s1, s2, 'avg')}
      ${kpiCard('Median Sale Price',   fmtD(s1?.median??0), 'Last 60 days',  s1, s2, 'median')}
    </div>

    <!-- Period comparison -->
    <div class="mi-section">
      <h2>60-Day Period Comparison</h2>
      <p>Current vs prior 60 days and the same period one year ago.</p>
    </div>
    <div class="mi-card" style="overflow-x:auto;margin-bottom:16px">
      <table class="mi-tbl">
        <thead><tr>
          <th>Metric</th>
          <th>${mlbl(p1[0])} – ${mlbl(p1[1])}</th>
          <th>${mlbl(p2[0])} – ${mlbl(p2[1])}</th>
          <th>${mlbl(p3[0])} – ${mlbl(p3[1])} (YoY)</th>
        </tr></thead>
        <tbody>
          ${pRow('Volume',    s1?.count,  s2?.count,  s3?.count,  fmtN)}
          ${pRow('Avg Price', s1?.avg,    s2?.avg,    s3?.avg,    fmtD)}
          ${pRow('Median',    s1?.median, s2?.median, s3?.median, fmtD)}
          ${strRow('With Key %',
            s1?((100*s1.withKey/s1.count).toFixed(1)+'%'):'—',
            s2?((100*s2.withKey/s2.count).toFixed(1)+'%'):'—',
            s3?((100*s3.withKey/s3.count).toFixed(1)+'%'):'—')}
          ${strRow('Starts %',
            s1?((100*s1.starts/s1.count).toFixed(1)+'%'):'—',
            s2?((100*s2.starts/s2.count).toFixed(1)+'%'):'—',
            s3?((100*s3.starts/s3.count).toFixed(1)+'%'):'—')}
        </tbody>
      </table>
    </div>

    <!-- Type + Region -->
    <div class="mi-section">
      <h2>By Vehicle Type &amp; Region</h2>
      <p>Last 60 days. Types classified by make and model name — some edge cases may be approximate.</p>
    </div>
    <div class="mi-g2">
      <div class="mi-card" style="overflow-x:auto">
        <div class="mi-card-title">Vehicle Type</div>
        <table class="mi-tbl">
          <thead><tr><th>Type</th><th># Sold</th><th>Avg $</th><th>Median</th><th>Key%</th><th>vs Prior</th></tr></thead>
          <tbody>${typeRows||'<tr><td colspan="6" style="color:var(--text-muted);text-align:center">No data</td></tr>'}</tbody>
        </table>
      </div>
      <div class="mi-card" style="overflow-x:auto">
        <div class="mi-card-title">By Region (Top 12)</div>
        <table class="mi-tbl">
          <thead><tr><th>Region</th><th># Sold</th><th>Avg $</th><th>Median</th><th>vs Prior</th></tr></thead>
          <tbody>${regionRows||'<tr><td colspan="5" style="color:var(--text-muted);text-align:center">No regional data</td></tr>'}</tbody>
        </table>
      </div>
    </div>

    <!-- Top Makes -->
    <div class="mi-section">
      <h2>Top Makes by Volume</h2>
      <p>Highest-volume makes in the last 60 days. Key% = vehicles with a key present.</p>
    </div>
    <div class="mi-card" style="overflow-x:auto;margin-bottom:16px">
      <table class="mi-tbl">
        <thead><tr><th>#</th><th>Make</th><th># Sold</th><th>Avg $</th><th>Median</th><th>Key%</th><th>vs Prior 60d</th></tr></thead>
        <tbody>${makeRows}</tbody>
      </table>
    </div>

    <!-- Odo + Condition -->
    <div class="mi-section">
      <h2>Pricing by Mileage &amp; Condition</h2>
    </div>
    <div class="mi-g2">
      <div class="mi-card" style="overflow-x:auto">
        <div class="mi-card-title">By Odometer Band (last 60d)</div>
        <table class="mi-tbl">
          <thead><tr><th>Mileage</th><th># Sold</th><th>Avg $</th><th>Median</th></tr></thead>
          <tbody>${odoRows}</tbody>
        </table>
      </div>
      <div class="mi-card">
        <div class="mi-card-title">Condition Premiums (last 60d)</div>
        <div class="cond-grid">
          <div class="cond-item">
            <div class="cond-lbl">Key Present vs No Key</div>
            <div class="cond-val">${kPrem!=null?(kPrem>=0?'+':'')+fmtD(kPrem):'—'}</div>
            <div class="cond-sub">${sKey?fmtD(sKey.avg):'-'} vs ${sNoKey?fmtD(sNoKey.avg):'-'}</div>
          </div>
          <div class="cond-item">
            <div class="cond-lbl">Starts vs Doesn't</div>
            <div class="cond-val">${sPrem!=null?(sPrem>=0?'+':'')+fmtD(sPrem):'—'}</div>
            <div class="cond-sub">${sSt?fmtD(sSt.avg):'-'} vs ${sNoSt?fmtD(sNoSt.avg):'-'}</div>
          </div>
        </div>
        <div class="mi-card-title" style="margin-top:4px">Documentation Mix (last 60d)</div>
        ${docBars}
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
      <div class="mi-chart">${trendSVG}</div>
    </div>
  `;
}

// ── Boot ──────────────────────────────────────────────────────────────────────
(async () => {
  try {
    const res  = await fetch('/data-json?v=<?= $amr_data_version ?>');
    if (!res.ok) throw new Error('HTTP '+res.status);
    const data = await res.json();
    const V = data.records.map(r=>({
      make:    String(data.makes[r[0]]),
      model:   String(data.models[r[1]]),
      year:    r[2], price: r[3],
      has_key: !!(r[4]&1), no_key: !!(r[4]&2), starts: !!(r[4]&4),
      region:  data.regions[r[5]], doc: data.docs[r[6]],
      odo: r[7], month: r[8]>=0 ? data.months[r[8]] : '',
    }));
    renderPage(V);
  } catch(e) {
    $('mi-body').innerHTML = '<div class="mi-load" style="color:var(--text-muted)">Could not load market data. Try refreshing.</div>';
  }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
