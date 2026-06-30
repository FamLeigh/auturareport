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
      <section style="padding:56px 0 8px;"><h1 style="font-size:clamp(1.7rem,4vw,2.4rem);">Buyer Market Report</h1></section>
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

// ── Aggregate the buyer dataset server-side into a compact payload ────────────
$bfile = __DIR__ . '/data/amr-buyers.json';
$B = is_readable($bfile) ? json_decode(file_get_contents($bfile), true) : null;

$payload = ['ok' => false];
if ($B && !empty($B['records'])) {
    $states = $B['states']; $cities = $B['cities']; $types = $B['types'];
    $buyers = $B['buyers']; $regions = $B['regions']; $makes = $B['makes'];

    $byState = []; $byCity = []; $byType = []; $byBuyer = []; $flow = [];
    $totN = 0; $totSpend = 0;

    foreach ($B['records'] as $r) {
        // [month, region, seller, auction, buyer, city, state, type, make, price, reserve]
        $rg=$r[1]; $bu=$r[4]; $ci=$r[5]; $st=$r[6]; $ty=$r[7]; $mk=$r[8]; $price=$r[9];
        $totN++; $totSpend += $price;

        if ($st >= 0) { $k=$states[$st]; if(!isset($byState[$k])) $byState[$k]=['n'=>0,'sp'=>0,'b'=>[]]; $byState[$k]['n']++; $byState[$k]['sp']+=$price; $byState[$k]['b'][$bu]=1; }
        if ($ci >= 0) { $k=$cities[$ci].($st>=0?', '.$states[$st]:''); if(!isset($byCity[$k])) $byCity[$k]=['n'=>0,'sp'=>0]; $byCity[$k]['n']++; $byCity[$k]['sp']+=$price; }
        $tk = $ty>=0 ? $types[$ty] : 'Unknown'; if(!isset($byType[$tk])) $byType[$tk]=['n'=>0,'sp'=>0,'b'=>[],'mk'=>[]]; $byType[$tk]['n']++; $byType[$tk]['sp']+=$price; $byType[$tk]['b'][$bu]=1; if($mk>=0)$byType[$tk]['mk'][$makes[$mk]]=($byType[$tk]['mk'][$makes[$mk]]??0)+1;
        if(!isset($byBuyer[$bu])) $byBuyer[$bu]=['n'=>0,'sp'=>0,'ty'=>$tk,'st'=>$st>=0?$states[$st]:'']; $byBuyer[$bu]['n']++; $byBuyer[$bu]['sp']+=$price;
        if ($rg>=0 && $st>=0) { $fk=$regions[$rg].'|'.$states[$st]; $flow[$fk]=($flow[$fk]??0)+1; }
    }

    // States (all)
    $statesOut = [];
    foreach ($byState as $s=>$d) $statesOut[] = ['s'=>$s,'n'=>$d['n'],'sp'=>$d['sp'],'b'=>count($d['b'])];
    usort($statesOut, fn($a,$b)=>$b['n']-$a['n']);

    // Cities (top 40)
    $citiesOut = [];
    foreach ($byCity as $c=>$d) $citiesOut[] = ['c'=>$c,'n'=>$d['n'],'sp'=>$d['sp']];
    usort($citiesOut, fn($a,$b)=>$b['n']-$a['n']);
    $citiesOut = array_slice($citiesOut, 0, 40);

    // Types
    $typesOut = [];
    foreach ($byType as $t=>$d) {
        arsort($d['mk']);
        $top = array_slice(array_keys($d['mk']), 0, 5);
        $typesOut[] = ['t'=>$t,'n'=>$d['n'],'sp'=>$d['sp'],'b'=>count($d['b']),'mk'=>$top];
    }
    usort($typesOut, fn($a,$b)=>$b['n']-$a['n']);

    // Buyers — sort all by units, keep top 200, compute top-20 concentration
    $buyersOut = [];
    foreach ($byBuyer as $bu=>$d) $buyersOut[] = ['name'=>$buyers[$bu],'ty'=>$d['ty'],'st'=>$d['st'],'n'=>$d['n'],'sp'=>$d['sp']];
    usort($buyersOut, fn($a,$b)=>$b['n']-$a['n']);
    $top20 = array_sum(array_map(fn($x)=>$x['n'], array_slice($buyersOut, 0, 20)));
    $buyersTop = array_slice($buyersOut, 0, 200);

    // Flows (top 60)
    $flowOut = [];
    foreach ($flow as $k=>$n) { [$from,$to]=explode('|',$k); $flowOut[] = ['from'=>$from,'to'=>$to,'n'=>$n]; }
    usort($flowOut, fn($a,$b)=>$b['n']-$a['n']);
    $flowOut = array_slice($flowOut, 0, 60);

    $months = $B['months']; sort($months);
    $payload = [
        'ok'=>true,
        'totN'=>$totN, 'totSpend'=>$totSpend, 'uniqueBuyers'=>count($buyers),
        'top20share'=> $totN ? round(100*$top20/$totN,1) : 0,
        'monthFrom'=>$months[0]??'', 'monthTo'=>end($months)?:'',
        'states'=>$statesOut, 'cities'=>$citiesOut, 'types'=>$typesOut,
        'buyers'=>$buyersTop, 'flow'=>$flowOut,
    ];
}

$payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{"ok":false}';
$state_paths  = amr_state_paths();

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.br-hero { padding:48px 0 6px; }
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
</style>';

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <section class="br-hero">
    <h1 style="font-size:clamp(1.7rem,4vw,2.4rem);margin-bottom:4px;">Buyer Market Report</h1>
    <p class="br-note" id="br-sub">Who buys Autura Marketplace inventory — by geography, segment, and account.</p>
  </section>

  <div id="br-root"></div>
</div>
<div class="br-tip" id="br-tip"></div>

<script>
const BR = <?= $payload_json ?>;
const PATHS = <?= json_encode($state_paths, JSON_UNESCAPED_SLASHES) ?>;
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const fmtN = n => Number(n||0).toLocaleString();
const fmtD = n => '$' + Math.round(n||0).toLocaleString();
const fmtDk = n => n>=1e6 ? '$'+(n/1e6).toFixed(1)+'M' : n>=1e3 ? '$'+Math.round(n/1e3)+'K' : '$'+Math.round(n);

function renderShell(){
  $('br-root').innerHTML = `
    <div class="br-kpis">
      <div class="br-kpi"><div class="v">${fmtN(BR.totN)}</div><div class="l">Total sales</div></div>
      <div class="br-kpi"><div class="v">${fmtN(BR.uniqueBuyers)}</div><div class="l">Unique buyers</div></div>
      <div class="br-kpi"><div class="v">${fmtDk(BR.totSpend)}</div><div class="l">Total buyer spend</div></div>
      <div class="br-kpi"><div class="v">${BR.top20share}%</div><div class="l">Volume from top 20 buyers</div></div>
    </div>
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
  renderGeo(); renderSeg(); renderTop(); renderFlow();
}

// ── Tab 1: Geography ─────────────────────────────────────────────────────────
let geoMetric = 'n';
function renderGeo(){
  const paths = Object.entries(PATHS).map(([ab,d]) => `<path data-st="${ab}" d="${d}"/>`).join('');
  $('p-geo').innerHTML = `
    <div class="br-card">
      <div class="br-maptools">
        <span class="br-card-title" style="margin:0 8px 0 0">Buyers by state</span>
        <button class="br-mbtn on" data-m="n">Volume</button>
        <button class="br-mbtn" data-m="sp">Spend</button>
      </div>
      <svg class="br-geo" viewBox="0 0 960 600">${paths}</svg>
    </div>
    <div class="br-g2">
      <div class="br-card"><div class="br-card-title">Top buyer states</div>
        <table class="br-tbl"><thead><tr><th>State</th><th class="num">Buyers</th><th class="num">Sales</th><th class="num">Spend</th><th class="num">Avg</th></tr></thead>
        <tbody>${BR.states.slice(0,15).map(s=>`<tr><td>${s.s}</td><td class="num">${fmtN(s.b)}</td><td class="num">${fmtN(s.n)}</td><td class="num">${fmtDk(s.sp)}</td><td class="num">${fmtD(s.sp/s.n)}</td></tr>`).join('')}</tbody></table>
      </div>
      <div class="br-card"><div class="br-card-title">Top buyer cities</div>
        <table class="br-tbl"><thead><tr><th>City</th><th class="num">Sales</th><th class="num">Spend</th></tr></thead>
        <tbody>${BR.cities.slice(0,15).map(c=>`<tr><td>${esc(c.c)}</td><td class="num">${fmtN(c.n)}</td><td class="num">${fmtDk(c.sp)}</td></tr>`).join('')}</tbody></table>
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
  const max = Math.max(...BR.states.map(s=>s[geoMetric]), 1);
  const byState = Object.fromEntries(BR.states.map(s=>[s.s,s]));
  $('p-geo').querySelectorAll('.br-geo path[data-st]').forEach(p=>{
    const s = byState[p.dataset.st];
    if (!s) { p.style.fill=''; p.dataset.tip=''; return; }
    const a = 0.12 + 0.85*(s[geoMetric]/max);
    p.style.fill = `rgba(240,165,0,${a.toFixed(3)})`;
    p.dataset.tip = `${s.s}: ${fmtN(s.n)} sales · ${fmtN(s.b)} buyers · ${fmtDk(s.sp)}`;
  });
}

// ── Tab 2: Segments ──────────────────────────────────────────────────────────
function renderSeg(){
  const max = Math.max(...BR.types.map(t=>t.n),1);
  $('p-seg').innerHTML = `
    <div class="br-card"><div class="br-card-title">Buyer segments by type</div>
      <table class="br-tbl">
        <thead><tr><th>Type</th><th>Share</th><th class="num">Sales</th><th class="num">% of total</th><th class="num">Buyers</th><th class="num">Avg price</th><th class="num">Spend</th><th>Top makes</th></tr></thead>
        <tbody>${BR.types.map(t=>`<tr>
          <td><strong>${esc(t.t)}</strong></td>
          <td style="width:120px"><span class="br-bar" style="width:${Math.round(100*t.n/max)}%"></span></td>
          <td class="num">${fmtN(t.n)}</td>
          <td class="num">${(100*t.n/BR.totN).toFixed(1)}%</td>
          <td class="num">${fmtN(t.b)}</td>
          <td class="num">${fmtD(t.sp/t.n)}</td>
          <td class="num">${fmtDk(t.sp)}</td>
          <td style="font-size:12px;color:var(--text-muted)">${t.mk.map(esc).join(', ')}</td>
        </tr>`).join('')}</tbody>
      </table>
      <p class="br-note">Buyer type is normalized from free-text into standard segments. ~half of sales carry an explicit type; the rest show as “Unknown”.</p>
    </div>`;
}

// ── Tab 3: Top Buyers ────────────────────────────────────────────────────────
let buyerSort = 'n';
function renderTop(){
  $('p-top').innerHTML = `
    <div class="br-card">
      <div class="br-card-title">Top buyers — the top 20 account for ${BR.top20share}% of all sales</div>
      <table class="br-tbl" id="br-buyers-tbl"></table>
      <p class="br-note">Showing the top 200 buyers (company name where available, otherwise individual). Click a column header to sort.</p>
    </div>`;
  paintBuyers();
}
function paintBuyers(){
  const rows = [...BR.buyers].sort((a,b)=> buyerSort==='sp' ? b.sp-a.sp : b.n-a.n);
  $('br-buyers-tbl').innerHTML = `
    <thead><tr><th>#</th><th>Buyer</th><th>Type</th><th>State</th>
      <th class="num" data-s="n">Units${buyerSort==='n'?' ▼':''}</th>
      <th class="num" data-s="sp">Total spend${buyerSort==='sp'?' ▼':''}</th>
      <th class="num">Avg</th></tr></thead>
    <tbody>${rows.map((b,i)=>`<tr>
      <td>${i+1}</td><td>${esc(b.name)}</td><td>${esc(b.ty)}</td><td>${esc(b.st)}</td>
      <td class="num">${fmtN(b.n)}</td><td class="num">${fmtD(b.sp)}</td><td class="num">${fmtD(b.sp/b.n)}</td>
    </tr>`).join('')}</tbody>`;
  $('br-buyers-tbl').querySelectorAll('th[data-s]').forEach(th=>th.addEventListener('click',()=>{ buyerSort=th.dataset.s; paintBuyers(); }));
}

// ── Tab 4: Supply → Demand ───────────────────────────────────────────────────
function renderFlow(){
  const max = Math.max(...BR.flow.map(f=>f.n),1);
  $('p-flow').innerHTML = `
    <div class="br-card"><div class="br-card-title">Where inventory travels — seller region → buyer state</div>
      <table class="br-tbl">
        <thead><tr><th>Seller region</th><th>Buyer state</th><th>Flow</th><th class="num">Sales</th></tr></thead>
        <tbody>${BR.flow.map(f=>`<tr>
          <td>${esc(f.from)}</td><td>${esc(f.to)}</td>
          <td style="width:160px"><span class="br-bar" style="width:${Math.round(100*f.n/max)}%"></span></td>
          <td class="num">${fmtN(f.n)}</td>
        </tr>`).join('')}</tbody>
      </table>
      <p class="br-note">Top 60 region→state lanes by sales volume. A short list means demand is regional; long lanes mean inventory travels.</p>
    </div>`;
}

// ── Shared map tooltip ───────────────────────────────────────────────────────
function wireTips(){
  const tip = $('br-tip');
  document.querySelectorAll('.br-geo path[data-st]').forEach(p=>{
    p.addEventListener('mousemove', e => { if(!p.dataset.tip) return; tip.textContent=p.dataset.tip; tip.style.opacity=1; tip.style.left=(e.clientX+12)+'px'; tip.style.top=(e.clientY+12)+'px'; });
    p.addEventListener('mouseleave', () => { tip.style.opacity=0; });
  });
}

// ── Bootstrap (after all declarations so let-vars are initialized) ────────────
if (!BR.ok) {
  $('br-root').innerHTML = `<div class="br-card"><p>No buyer data yet. Upload the latest <strong>MP_Vehicle_Pricing_Tool</strong> export via <a href="/update">Update Data</a> and run the import — the buyer dataset is generated automatically.</p></div>`;
} else {
  $('br-sub').textContent = `${fmtN(BR.totN)} sales · ${fmtN(BR.uniqueBuyers)} unique buyers · ${BR.monthFrom} – ${BR.monthTo}`;
  renderShell();
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
