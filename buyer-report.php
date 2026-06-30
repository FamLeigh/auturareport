<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_data.php';
require_once __DIR__ . '/includes/us_map.php';

// Require the shared customer-data access code.
list($cr_ok, $cr_error) = amr_customer_gate();

$page_title = 'Buyer Market Report';
$body_class = 'page-buyers';
$canonical  = '/buyer-report';

if (!$cr_ok) {
    $extra_head = '<meta name="robots" content="noindex, nofollow">';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
      <section style="padding:calc(var(--nav-h) + 24px) 0 8px;"><h1 style="font-size:clamp(1.7rem,4vw,2.4rem);">Buyer Market Report</h1></section>
      <form class="cr-gate" method="POST" autocomplete="off">
        <h2>Access code required</h2>
        <p>Enter the access code to view the Buyer Market Report.</p>
        <?php if ($cr_error): ?><div class="err"><?= h($cr_error) ?></div><?php endif; ?>
        <input class="cr-code-input" type="password" name="cr_code" inputmode="numeric" pattern="[0-9]*"
               maxlength="6" placeholder="••••••" autocomplete="off" autofocus required>
        <button type="submit">Unlock</button>
      </form>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Records are fetched + aggregated client-side so the Region/Seller/Group
// filters can re-slice without a round trip. The page itself only needs the
// seller groups, the map paths, and whether buyer data exists.
$bfile = __DIR__ . '/data/amr-buyers.json';
$has_buyer_data = is_readable($bfile);
$buyer_version  = $has_buyer_data ? filemtime($bfile) : 0;

$groups_file = __DIR__ . '/data/seller-groups.json';
$groups = file_exists($groups_file) ? (json_decode(file_get_contents($groups_file), true) ?: []) : [];

$state_paths = amr_state_paths();

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.br-hero { padding:calc(var(--nav-h) + 24px) 0 6px; }
.br-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin:14px 0 8px; }
@media (max-width:720px){ .br-kpis{ grid-template-columns:repeat(2,1fr);} }
.br-kpi { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 16px; }
.br-kpi .v { font-size:1.5rem; font-weight:800; font-variant-numeric:tabular-nums; }
.br-kpi .l { font-size:12px; color:var(--text-muted); margin-top:2px; }
.br-tabs { display:flex; gap:6px; flex-wrap:wrap; border-bottom:1px solid var(--border); margin:18px 0 0; }
.br-tab { background:none; border:none; border-bottom:2px solid transparent; color:var(--text-muted); font-size:14px; font-weight:600; padding:10px 14px; cursor:pointer; }
.br-tab.on { color:var(--text); border-bottom-color:var(--accent); }
.br-panel { display:none; padding:18px 0; } .br-panel.on { display:block; }
.br-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; margin-bottom:16px; }
.br-card-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin-bottom:12px; }
.br-g2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; } @media (max-width:860px){ .br-g2{ grid-template-columns:1fr; } }
.br-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.br-tbl th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.03em; color:var(--text-muted); padding:7px 10px; border-bottom:1px solid var(--border); cursor:pointer; white-space:nowrap; }
.br-tbl td { padding:7px 10px; border-bottom:1px solid rgba(0,0,0,.05); }
[data-theme="dark"] .br-tbl td { border-bottom-color:rgba(255,255,255,.05); }
.br-tbl td.num, .br-tbl th.num { text-align:right; font-variant-numeric:tabular-nums; }
.br-geo { width:100%; height:auto; max-width:860px; display:block; margin:0 auto; }
.br-geo path { fill:var(--surface-2); stroke:var(--bg); stroke-width:.6; transition:stroke .1s; }
.br-geo path[data-st]:hover { stroke:var(--accent); stroke-width:1.6; }
.br-maptools { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
.br-mbtn { background:var(--surface); border:1px solid var(--border); border-radius:7px; color:var(--text-muted); font-size:12px; font-weight:600; padding:6px 12px; cursor:pointer; }
.br-mbtn.on { border-color:var(--accent); color:var(--accent); }
.br-tip { position:fixed; pointer-events:none; background:var(--text); color:var(--bg); font-size:12px; padding:5px 9px; border-radius:6px; opacity:0; transition:opacity .1s; z-index:50; white-space:nowrap; }
.br-bar { height:8px; border-radius:4px; background:var(--accent); display:inline-block; vertical-align:middle; }
.br-note { font-size:12px; color:var(--text-muted); margin-top:6px; }
/* filter row (shared widget styles from the market report) */
.br-filter-row { margin:14px 0 4px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.br-filter-lbl { font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--text-muted); }
.mi-ms { position: relative; display: inline-block; }
.mi-ms-btn { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 13px; font-weight: 600; padding: 7px 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: border-color .15s; }
.mi-ms-btn:hover { border-color: var(--accent); }
.mi-ms-lbl { color: var(--text-muted); font-weight: 700; }
.mi-ms.mi-ms-on .mi-ms-btn { border-color: var(--accent); color: var(--accent); }
.mi-ms.mi-ms-on .mi-ms-lbl { color: var(--accent); }
.mi-ms-caret { font-size: 10px; color: var(--text-muted); }
.mi-ms-pop { position: absolute; top: calc(100% + 5px); left: 0; z-index: 30; width: 290px; max-width: 84vw; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 8px 28px rgba(0,0,0,.14); padding: 10px; }
.mi-ms-search { width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; color: var(--text); font-size: 13px; padding: 8px 10px; }
.mi-ms-search:focus { outline: none; border-color: var(--accent); }
.mi-ms-tools { display: flex; justify-content: flex-end; margin: 6px 0 2px; }
.mi-ms-clear { background: none; border: none; color: var(--text-muted); font-size: 12px; cursor: pointer; padding: 2px 4px; }
.mi-ms-clear:hover { color: var(--accent); }
.mi-ms-list { max-height: 260px; overflow-y: auto; }
.mi-ms-opt { display: flex; align-items: center; gap: 9px; padding: 6px; font-size: 13px; cursor: pointer; border-radius: 6px; }
.mi-ms-opt:hover { background: var(--surface-2); }
.mi-ms-opt input { accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
.mi-ms-opt-l { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mi-ms-opt-c { color: var(--text-muted); font-size: 11px; font-variant-numeric: tabular-nums; }
.mi-ms-empty { padding: 14px; text-align: center; color: var(--text-muted); font-size: 13px; }
.mi-ms-reset { background: none; border: none; color: var(--text-muted); font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: underline; }
.mi-ms-reset:hover { color: var(--accent); }
</style>';

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <section class="br-hero">
    <h1 style="font-size:clamp(1.7rem,4vw,2.4rem);margin-bottom:4px;">Buyer Market Report</h1>
    <p class="br-note" id="br-sub">Who buys Autura Marketplace inventory — by geography, segment, and account.</p>
  </section>

  <div class="br-filter-row" id="br-filter-row" hidden>
    <span class="br-filter-lbl">Filter</span>
    <div class="mi-ms" id="bms-region"></div>
    <div class="mi-ms" id="bms-seller"></div>
    <div class="mi-ms" id="bms-group"></div>
    <button class="mi-ms-reset" id="br-filter-reset" hidden>Clear all</button>
  </div>

  <div id="br-root"></div>
</div>
<div class="br-tip" id="br-tip"></div>

<script>
const PATHS = <?= json_encode($state_paths, JSON_UNESCAPED_SLASHES) ?>;
const GROUPS = <?= json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}' ?>;
const HAS_DATA = <?= $has_buyer_data ? 'true' : 'false' ?>;
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const fmtN = n => Number(n||0).toLocaleString();
const fmtD = n => '$' + Math.round(n||0).toLocaleString();
const fmtDk = n => n>=1e6 ? '$'+(n/1e6).toFixed(1)+'M' : n>=1e3 ? '$'+Math.round(n/1e3)+'K' : '$'+Math.round(n);

// Dictionaries + records (filled after fetch). Record layout:
// [month, region, seller, auction, buyer, city, state, type, make, price, reserve]
let RECORDS=[], MONTHS=[], REGIONS=[], SELLERS=[], MAKES=[], BUYERS=[], CITIES=[], STATES=[], TYPES=[];
let MONTH_FROM='', MONTH_TO='';
let AGG=null, geoMetric='n', buyerSort='n';
let msRegion, msSeller, msGroup;

// ── Aggregate a record set into the per-tab views ────────────────────────────
function aggregate(recs){
  const byState={}, byCity={}, byType={}, byBuyer={}, flow={};
  let totN=0, totSpend=0;
  for (let i=0;i<recs.length;i++){
    const r=recs[i], rg=r[1], bu=r[4], ci=r[5], st=r[6], ty=r[7], mk=r[8], price=r[9];
    totN++; totSpend+=price;
    if (st>=0){ const k=STATES[st]; let o=byState[k]; if(!o)o=byState[k]={n:0,sp:0,b:{}}; o.n++; o.sp+=price; o.b[bu]=1; }
    if (ci>=0){ const k=CITIES[ci]+(st>=0?', '+STATES[st]:''); let o=byCity[k]; if(!o)o=byCity[k]={n:0,sp:0}; o.n++; o.sp+=price; }
    const tk = ty>=0?TYPES[ty]:'Unknown'; { let o=byType[tk]; if(!o)o=byType[tk]={n:0,sp:0,b:{},mk:{}}; o.n++; o.sp+=price; o.b[bu]=1; if(mk>=0){const mn=MAKES[mk]; o.mk[mn]=(o.mk[mn]||0)+1;} }
    { let o=byBuyer[bu]; if(!o)o=byBuyer[bu]={n:0,sp:0,ty:tk,st:st>=0?STATES[st]:''}; o.n++; o.sp+=price; }
    if (rg>=0 && st>=0){ const fk=REGIONS[rg]+'|'+STATES[st]; flow[fk]=(flow[fk]||0)+1; }
  }
  const states=Object.keys(byState).map(s=>({s,n:byState[s].n,sp:byState[s].sp,b:Object.keys(byState[s].b).length})).sort((a,b)=>b.n-a.n);
  const cities=Object.keys(byCity).map(c=>({c,n:byCity[c].n,sp:byCity[c].sp})).sort((a,b)=>b.n-a.n).slice(0,40);
  const types=Object.keys(byType).map(t=>{const d=byType[t];const mk=Object.keys(d.mk).sort((a,b)=>d.mk[b]-d.mk[a]).slice(0,5);return{t,n:d.n,sp:d.sp,b:Object.keys(d.b).length,mk};}).sort((a,b)=>b.n-a.n);
  const buyersArr=Object.keys(byBuyer).map(bu=>({name:BUYERS[bu],ty:byBuyer[bu].ty,st:byBuyer[bu].st,n:byBuyer[bu].n,sp:byBuyer[bu].sp})).sort((a,b)=>b.n-a.n);
  const top20=buyersArr.slice(0,20).reduce((a,x)=>a+x.n,0);
  const flowArr=Object.keys(flow).map(k=>{const p=k.split('|');return{from:p[0],to:p[1],n:flow[k]};}).sort((a,b)=>b.n-a.n).slice(0,60);
  return { totN, totSpend, uniqueBuyers:Object.keys(byBuyer).length,
           top20share: totN?Math.round(1000*top20/totN)/10:0,
           states, cities, types, buyers:buyersArr.slice(0,200), flow:flowArr };
}

// ── Shell + per-tab render (read from AGG, rebuilt on every filter change) ────
function renderShell(){
  $('br-root').innerHTML = `
    <div class="br-kpis" id="br-kpis"></div>
    <div class="br-tabs">
      <button class="br-tab on" data-tab="geo">Geography</button>
      <button class="br-tab" data-tab="seg">Segments</button>
      <button class="br-tab" data-tab="top">Top Buyers</button>
      <button class="br-tab" data-tab="flow">Supply → Demand</button>
    </div>
    <div class="br-panel on" id="p-geo"></div>
    <div class="br-panel" id="p-seg"></div>
    <div class="br-panel" id="p-top"></div>
    <div class="br-panel" id="p-flow"></div>`;
  document.querySelectorAll('.br-tab').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.br-tab').forEach(x => x.classList.toggle('on', x===b));
    document.querySelectorAll('.br-panel').forEach(p => p.classList.toggle('on', p.id==='p-'+b.dataset.tab));
  }));
}
function renderAll(){
  $('br-kpis').innerHTML = `
    <div class="br-kpi"><div class="v">${fmtN(AGG.totN)}</div><div class="l">Total sales</div></div>
    <div class="br-kpi"><div class="v">${fmtN(AGG.uniqueBuyers)}</div><div class="l">Unique buyers</div></div>
    <div class="br-kpi"><div class="v">${fmtDk(AGG.totSpend)}</div><div class="l">Total buyer spend</div></div>
    <div class="br-kpi"><div class="v">${AGG.top20share}%</div><div class="l">Volume from top 20 buyers</div></div>`;
  renderGeo(); renderSeg(); renderTop(); renderFlow();
}

function renderGeo(){
  const paths = Object.entries(PATHS).map(([ab,d]) => `<path data-st="${ab}" d="${d}"/>`).join('');
  $('p-geo').innerHTML = `
    <div class="br-card">
      <div class="br-maptools">
        <span class="br-card-title" style="margin:0 8px 0 0">Buyers by state</span>
        <button class="br-mbtn ${geoMetric==='n'?'on':''}" data-m="n">Volume</button>
        <button class="br-mbtn ${geoMetric==='sp'?'on':''}" data-m="sp">Spend</button>
      </div>
      <svg class="br-geo" viewBox="0 0 960 600">${paths}</svg>
    </div>
    <div class="br-g2">
      <div class="br-card"><div class="br-card-title">Top buyer states</div>
        <table class="br-tbl"><thead><tr><th>State</th><th class="num">Buyers</th><th class="num">Sales</th><th class="num">Spend</th><th class="num">Avg</th></tr></thead>
        <tbody>${AGG.states.slice(0,15).map(s=>`<tr><td>${s.s}</td><td class="num">${fmtN(s.b)}</td><td class="num">${fmtN(s.n)}</td><td class="num">${fmtDk(s.sp)}</td><td class="num">${fmtD(s.sp/s.n)}</td></tr>`).join('')||'<tr><td colspan="5">No matches</td></tr>'}</tbody></table>
      </div>
      <div class="br-card"><div class="br-card-title">Top buyer cities</div>
        <table class="br-tbl"><thead><tr><th>City</th><th class="num">Sales</th><th class="num">Spend</th></tr></thead>
        <tbody>${AGG.cities.slice(0,15).map(c=>`<tr><td>${esc(c.c)}</td><td class="num">${fmtN(c.n)}</td><td class="num">${fmtDk(c.sp)}</td></tr>`).join('')||'<tr><td colspan="3">No matches</td></tr>'}</tbody></table>
      </div>
    </div>`;
  $('p-geo').querySelectorAll('.br-mbtn').forEach(b => b.addEventListener('click', () => {
    geoMetric = b.dataset.m;
    $('p-geo').querySelectorAll('.br-mbtn').forEach(x=>x.classList.toggle('on',x===b));
    paintGeo();
  }));
  paintGeo();
  wireTips();
}
function paintGeo(){
  const max = Math.max(...AGG.states.map(s=>s[geoMetric]), 1);
  const byState = Object.fromEntries(AGG.states.map(s=>[s.s,s]));
  $('p-geo').querySelectorAll('.br-geo path[data-st]').forEach(p=>{
    const s = byState[p.dataset.st];
    if (!s) { p.style.fill=''; p.dataset.tip=''; return; }
    const a = 0.12 + 0.85*(s[geoMetric]/max);
    p.style.fill = `rgba(240,165,0,${a.toFixed(3)})`;
    p.dataset.tip = `${s.s}: ${fmtN(s.n)} sales · ${fmtN(s.b)} buyers · ${fmtDk(s.sp)}`;
  });
}

function renderSeg(){
  const max = Math.max(...AGG.types.map(t=>t.n),1);
  $('p-seg').innerHTML = `
    <div class="br-card"><div class="br-card-title">Buyer segments by type</div>
      <table class="br-tbl">
        <thead><tr><th>Type</th><th>Share</th><th class="num">Sales</th><th class="num">% of total</th><th class="num">Buyers</th><th class="num">Avg price</th><th class="num">Spend</th><th>Top makes</th></tr></thead>
        <tbody>${AGG.types.map(t=>`<tr>
          <td><strong>${esc(t.t)}</strong></td>
          <td style="width:120px"><span class="br-bar" style="width:${Math.round(100*t.n/max)}%"></span></td>
          <td class="num">${fmtN(t.n)}</td>
          <td class="num">${(100*t.n/AGG.totN).toFixed(1)}%</td>
          <td class="num">${fmtN(t.b)}</td>
          <td class="num">${fmtD(t.sp/t.n)}</td>
          <td class="num">${fmtDk(t.sp)}</td>
          <td style="font-size:12px;color:var(--text-muted)">${t.mk.map(esc).join(', ')}</td>
        </tr>`).join('')}</tbody>
      </table>
      <p class="br-note">Buyer type is normalized from free-text into standard segments. ~half of sales carry an explicit type; the rest show as “Unknown”.</p>
    </div>`;
}

function renderTop(){
  $('p-top').innerHTML = `
    <div class="br-card">
      <div class="br-card-title">Top buyers — the top 20 account for ${AGG.top20share}% of these sales</div>
      <table class="br-tbl" id="br-buyers-tbl"></table>
      <p class="br-note">Top 200 buyers in the current selection (company name where available, else individual). Click a column header to sort.</p>
    </div>`;
  paintBuyers();
}
function paintBuyers(){
  const rows = [...AGG.buyers].sort((a,b)=> buyerSort==='sp' ? b.sp-a.sp : b.n-a.n);
  $('br-buyers-tbl').innerHTML = `
    <thead><tr><th>#</th><th>Buyer</th><th>Type</th><th>State</th>
      <th class="num" data-s="n">Units${buyerSort==='n'?' ▼':''}</th>
      <th class="num" data-s="sp">Total spend${buyerSort==='sp'?' ▼':''}</th>
      <th class="num">Avg</th></tr></thead>
    <tbody>${rows.map((b,i)=>`<tr>
      <td>${i+1}</td><td>${esc(b.name)}</td><td>${esc(b.ty)}</td><td>${esc(b.st)}</td>
      <td class="num">${fmtN(b.n)}</td><td class="num">${fmtD(b.sp)}</td><td class="num">${fmtD(b.sp/b.n)}</td>
    </tr>`).join('')||'<tr><td colspan="7">No matches</td></tr>'}</tbody>`;
  $('br-buyers-tbl').querySelectorAll('th[data-s]').forEach(th=>th.addEventListener('click',()=>{ buyerSort=th.dataset.s; paintBuyers(); }));
}

function renderFlow(){
  const max = Math.max(...AGG.flow.map(f=>f.n),1);
  $('p-flow').innerHTML = `
    <div class="br-card"><div class="br-card-title">Where inventory travels — seller region → buyer state</div>
      <table class="br-tbl">
        <thead><tr><th>Seller region</th><th>Buyer state</th><th>Flow</th><th class="num">Sales</th></tr></thead>
        <tbody>${AGG.flow.map(f=>`<tr>
          <td>${esc(f.from)}</td><td>${esc(f.to)}</td>
          <td style="width:160px"><span class="br-bar" style="width:${Math.round(100*f.n/max)}%"></span></td>
          <td class="num">${fmtN(f.n)}</td>
        </tr>`).join('')||'<tr><td colspan="4">No matches</td></tr>'}</tbody>
      </table>
      <p class="br-note">Top 60 region→state lanes by sales volume.</p>
    </div>`;
}

function wireTips(){
  const tip = $('br-tip');
  document.querySelectorAll('.br-geo path[data-st]').forEach(p=>{
    p.addEventListener('mousemove', e => { if(!p.dataset.tip) return; tip.textContent=p.dataset.tip; tip.style.opacity=1; tip.style.left=(e.clientX+12)+'px'; tip.style.top=(e.clientY+12)+'px'; });
    p.addEventListener('mouseleave', () => { tip.style.opacity=0; });
  });
}

// ── Filters (Region / Seller / Group), client-side like the market report ────
function makeMultiSelect({mountId, label, items, onChange}) {
  const root = document.getElementById(mountId);
  if (!root) return { get:()=>new Set(), clear:()=>{}, update:()=>{} };
  const selected = new Set();
  let curItems = items;
  root.classList.add('mi-ms');
  root.innerHTML = `
    <button type="button" class="mi-ms-btn"><span class="mi-ms-lbl">${esc(label)}:</span> <span class="mi-ms-sum">All</span> <span class="mi-ms-caret">▾</span></button>
    <div class="mi-ms-pop" hidden>
      <input type="text" class="mi-ms-search" placeholder="Search ${esc(label.toLowerCase())}…">
      <div class="mi-ms-tools"><button type="button" class="mi-ms-clear">Clear</button></div>
      <div class="mi-ms-list"></div>
    </div>`;
  const btn=root.querySelector('.mi-ms-btn'), pop=root.querySelector('.mi-ms-pop'),
        sum=root.querySelector('.mi-ms-sum'), search=root.querySelector('.mi-ms-search'),
        list=root.querySelector('.mi-ms-list'), clearBtn=root.querySelector('.mi-ms-clear');
  const labelOf = v => { const f=curItems.find(it=>it.value===v); return f?f.label:v; };
  const renderList = (q='') => {
    const ft=q.trim().toLowerCase();
    const rows=curItems.map((it,i)=>({it,i})).filter(({it})=>!ft||it.label.toLowerCase().includes(ft)).slice(0,400);
    list.innerHTML = rows.length ? rows.map(({it,i})=>
      `<label class="mi-ms-opt"><input type="checkbox" data-i="${i}" ${selected.has(it.value)?'checked':''}><span class="mi-ms-opt-l">${esc(it.label)}</span><span class="mi-ms-opt-c">${fmtN(it.count)}</span></label>`
    ).join('') : `<div class="mi-ms-empty">No matches</div>`;
  };
  const updateSum = () => {
    sum.textContent = selected.size===0 ? 'All' : selected.size===1 ? labelOf([...selected][0]) : `${selected.size} selected`;
    root.classList.toggle('mi-ms-on', selected.size>0);
  };
  btn.addEventListener('click', e=>{
    e.stopPropagation();
    const willOpen = pop.hidden;
    document.querySelectorAll('.mi-ms-pop').forEach(p=>p.hidden=true);
    pop.hidden = !willOpen;
    if (willOpen) { search.value=''; renderList(); search.focus(); }
  });
  pop.addEventListener('click', e=>e.stopPropagation());
  search.addEventListener('input', ()=>renderList(search.value));
  list.addEventListener('change', e=>{
    const cb=e.target.closest('input[type=checkbox]'); if(!cb) return;
    const it=curItems[+cb.dataset.i];
    if(cb.checked) selected.add(it.value); else selected.delete(it.value);
    updateSum(); onChange();
  });
  clearBtn.addEventListener('click', ()=>{ selected.clear(); renderList(search.value); updateSum(); onChange(); });
  const update = (newItems) => {
    curItems = newItems;
    const valid = new Set(curItems.map(it=>it.value));
    [...selected].forEach(v=>{ if(!valid.has(v)) selected.delete(v); });
    renderList(search.value); updateSum();
  };
  return { get:()=>selected, clear:()=>{ selected.clear(); updateSum(); }, update };
}

function sellerItems(){
  const rs=msRegion.get(), m={};
  for (let i=0;i<RECORDS.length;i++){ const r=RECORDS[i], sel=SELLERS[r[2]]; if (sel && (rs.size===0||rs.has(REGIONS[r[1]]))) m[sel]=(m[sel]||0)+1; }
  return Object.keys(m).sort((a,b)=>m[b]-m[a]).map(s=>({value:s,label:s,count:m[s]}));
}

function applyFilters(){
  const rs=msRegion.get(), ss=msSeller.get(), gs=msGroup?msGroup.get():new Set();
  let groupSellers=null;
  if (gs.size){ groupSellers=new Set(); gs.forEach(g=>(GROUPS[g]||[]).forEach(s=>groupSellers.add(s))); }
  const filtered = rs.size>0 || ss.size>0 || gs.size>0;
  const recs = !filtered ? RECORDS : RECORDS.filter(r=>{
    const reg=REGIONS[r[1]], sel=SELLERS[r[2]];
    return (rs.size===0||rs.has(reg)) && (ss.size===0||ss.has(sel)) && (!groupSellers||groupSellers.has(sel));
  });
  AGG = aggregate(recs);
  renderAll();
  const reset=$('br-filter-reset'); if(reset) reset.hidden=!filtered;
  // Subtitle reflects the active selection.
  const parts=[];
  if (gs.size) parts.push(gs.size===1?[...gs][0]:`${gs.size} groups`);
  if (rs.size) parts.push(rs.size===1?[...rs][0]:`${rs.size} regions`);
  if (ss.size) parts.push(ss.size===1?[...ss][0]:`${ss.size} sellers`);
  $('br-sub').textContent = (filtered?`${parts.join(' + ')} — `:'') +
    `${fmtN(AGG.totN)} sales · ${fmtN(AGG.uniqueBuyers)} buyers${MONTH_FROM?` · ${MONTH_FROM} – ${MONTH_TO}`:''}`;
}

(async () => {
  if (!HAS_DATA) {
    $('br-root').innerHTML = `<div class="br-card"><p>No buyer data yet. Upload the latest <strong>MP_Vehicle_Pricing_Tool</strong> export via <a href="/update">Update Data</a> and run the import — the buyer dataset is generated automatically.</p></div>`;
    return;
  }
  let data;
  try {
    const res = await fetch('/buyer-data?v=<?= $buyer_version ?>');
    if (!res.ok) throw new Error('HTTP '+res.status);
    data = await res.json();
  } catch (e) {
    $('br-root').innerHTML = `<div class="br-card"><p>Could not load buyer data (${esc(String(e.message||e))}).</p></div>`;
    return;
  }
  RECORDS=data.records; MONTHS=data.months; REGIONS=data.regions; SELLERS=data.sellers;
  MAKES=data.makes; BUYERS=data.buyers; CITIES=data.cities; STATES=data.states; TYPES=data.types;
  const sm=[...MONTHS].sort(); MONTH_FROM=sm[0]||''; MONTH_TO=sm[sm.length-1]||'';

  renderShell();

  // Build filter widgets.
  const rc={}; for(let i=0;i<RECORDS.length;i++){ const reg=REGIONS[RECORDS[i][1]]; if(reg) rc[reg]=(rc[reg]||0)+1; }
  const regionItems=Object.keys(rc).sort((a,b)=>rc[b]-rc[a]).map(c=>({value:c,label:c,count:rc[c]}));
  msRegion = makeMultiSelect({mountId:'bms-region', label:'Region', items:regionItems, onChange:()=>{ msSeller.update(sellerItems()); applyFilters(); }});
  msSeller = makeMultiSelect({mountId:'bms-seller', label:'Seller', items:sellerItems(), onChange:applyFilters});
  const groupNames=Object.keys(GROUPS);
  if (groupNames.length){
    const gItems=groupNames.sort().map(g=>{ const mem=new Set(GROUPS[g]); let c=0; for(let i=0;i<RECORDS.length;i++) if(mem.has(SELLERS[RECORDS[i][2]])) c++; return {value:g,label:g,count:c}; });
    msGroup = makeMultiSelect({mountId:'bms-group', label:'Group', items:gItems, onChange:applyFilters});
  }
  $('br-filter-row').hidden = false;
  const reset=$('br-filter-reset');
  if (reset) reset.addEventListener('click', ()=>{ msRegion.clear(); msSeller.clear(); if(msGroup) msGroup.clear(); msSeller.update(sellerItems()); applyFilters(); });
  document.addEventListener('click', ()=>document.querySelectorAll('.mi-ms-pop').forEach(p=>p.hidden=true));

  applyFilters();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
