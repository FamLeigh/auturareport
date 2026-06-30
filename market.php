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
$amr_groups       = file_exists(__DIR__ . '/data/seller-groups.json') ? (json_decode(file_get_contents(__DIR__ . '/data/seller-groups.json'), true) ?: []) : [];

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.mi-hero { padding: 72px 0 36px; }
.mi-hero h1 { font-size: clamp(1.8rem,4vw,2.6rem); margin-bottom: 6px; }
.mi-period { font-size: 13px; color: var(--text-muted); margin-top: 6px; }

.mi-section { margin: 40px 0 16px; }
.mi-section h2 { font-size: 1.15rem; font-weight: 700; margin-bottom: 4px; }
.mi-section p  { font-size: 13px; color: var(--text-muted); }
/* collapsible section (buyer-premium calculators) */
.mi-collapse > summary { list-style: none; cursor: pointer; }
.mi-collapse > summary::-webkit-details-marker { display: none; }
.mi-collapse > summary h2 { display: inline; }
.mi-collapse-ico { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border: 1px solid var(--border); border-radius: 6px; font-size: 17px; font-weight: 700; line-height: 1; color: var(--accent); margin-right: 10px; vertical-align: middle; transition: border-color .15s; }
.mi-collapse-ico::before { content: "+"; }
.mi-collapse[open] .mi-collapse-ico::before { content: "\2212"; }
.mi-collapse > summary:hover .mi-collapse-ico { border-color: var(--accent); }

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
.kpi-cmp { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-size: 12px; color: var(--text-muted); margin-top: 8px; }
.kpi-cmp:first-of-type { margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border); }
.kpi-cmp b { color: var(--text); font-weight: 700; font-variant-numeric: tabular-nums; }
.kpi-cmp .kpi-d { font-size: 11px; margin: 0 0 0 6px; }

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
/* grouped-bar (period comparison) legend */
.gbar-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-bottom: 8px; font-size: 12px; color: var(--text-muted); }
.gbar-key { display: inline-flex; align-items: center; gap: 6px; }
.gbar-sw { width: 11px; height: 11px; border-radius: 2px; display: inline-block; flex-shrink: 0; }

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

/* impact calculator */
.ic-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 28px; align-items: start; }
@media (max-width: 700px) { .ic-grid { grid-template-columns: 1fr; } }
.ic-stat { display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px; }
.ic-big  { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--accent); min-width: 72px; }
.ic-desc { font-size: 13px; color: var(--text-muted); line-height: 1.4; }
.ic-slider-label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; display: flex; justify-content: space-between; align-items: baseline; }
.ic-slider-label strong { font-size: 1.1rem; color: var(--text); }
input[type=range].ic-range { width: 100%; accent-color: var(--accent); margin-bottom: 4px; cursor: pointer; }
.ic-slider-ends { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 20px; }
.ic-boxes { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
@media (max-width: 500px) { .ic-boxes { grid-template-columns: 1fr; } }
.ic-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 14px 12px; text-align: center; }
.ic-box-val { font-family: var(--font-display); font-size: 1.25rem; font-weight: 800; color: var(--text); line-height: 1.1; }
.ic-box-lbl { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted); margin-top: 5px; }
.ic-box.ic-highlight { background: var(--accent-glow); border-color: rgba(240,165,0,.35); }
.ic-box.ic-highlight .ic-box-val { font-size: 1.55rem; color: var(--accent); }
.ic-divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }
.ic-period-lbl { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--text-muted); margin: 14px 0 7px; }
.ic-period-lbl em { font-style: normal; font-weight: 400; text-transform: none; letter-spacing: 0; }

/* print button */
.mi-print-btn {
  background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px;
  color: var(--text-muted); font-size: 13px; font-weight: 600; padding: 8px 16px;
  cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
  transition: border-color .15s, color .15s;
}
.mi-print-btn:hover { border-color: var(--accent); color: var(--accent); }
.mi-print-footer { display: none; }

/* region selector */
.mi-region-row { margin-top: 16px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.mi-filter-lbl { font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); }
.mi-ms { position: relative; display: inline-block; }
.mi-ms-btn {
  background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
  color: var(--text); font-size: 13px; font-weight: 600; padding: 7px 12px;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: border-color .15s;
}
.mi-ms-btn:hover { border-color: var(--accent); }
.mi-ms-lbl { color: var(--text-muted); font-weight: 700; }
.mi-ms.mi-ms-on .mi-ms-btn { border-color: var(--accent); color: var(--accent); }
.mi-ms.mi-ms-on .mi-ms-lbl { color: var(--accent); }
.mi-ms-caret { font-size: 10px; color: var(--text-muted); }
.mi-ms-pop {
  position: absolute; top: calc(100% + 5px); left: 0; z-index: 30;
  width: 290px; max-width: 84vw; background: var(--surface); border: 1px solid var(--border);
  border-radius: 10px; box-shadow: 0 8px 28px rgba(0,0,0,.14); padding: 10px;
}
.mi-ms-search {
  width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 7px;
  color: var(--text); font-size: 13px; padding: 8px 10px;
}
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
.mi-region-hint { font-size: 12px; color: var(--text-muted); }

/* period legend — makes the exact date windows explicit */
.mi-periods { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
.mi-period-chip {
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 8px 14px; line-height: 1.3;
}
.mi-period-chip b {
  display: block; font-size: 10px; font-weight: 700; letter-spacing: .06em;
  text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px;
}
.mi-period-chip span { font-size: 13px; font-weight: 600; color: var(--text); }

/* loading */
.mi-load { text-align: center; padding: 80px 24px; color: var(--text-muted); }
@keyframes mi-spin { to { transform: rotate(360deg); } }

/* ── Print ────────────────────────────────────────────────────────── */
@media print {
  @page { margin: 18mm 14mm; }
  .site-header, .mi-print-btn, .site-footer, .mi-region-row { display: none !important; }
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
  .mi-period-chip { background: #fff !important; border-color: #ddd !important; }
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
    <div class="mi-region-row" id="mi-filter-row">
      <span class="mi-filter-lbl">Filter</span>
      <div class="mi-ms" id="ms-region"></div>
      <div class="mi-ms" id="ms-seller"></div>
      <div class="mi-ms" id="ms-group"></div>
      <button type="button" class="mi-ms-reset" id="mi-filter-reset" hidden>Clear all</button>
      <span class="mi-region-hint" id="mi-region-hint"></span>
    </div>
    <p class="mi-period" style="margin-top:14px;max-width:820px;line-height:1.6;">
      Valuations based on <?= $amr_record_count > 0 ? number_format($amr_record_count) : 'tens of thousands of' ?> sold auction records from the past 18 months<?= $amr_data_date ? ', as of ' . h($amr_data_date) : '' ?>. Data reflects as-is impound vehicle sales from Autura Marketplace. Beta release for internal evaluation, not for resale. Created by Kevin B. Leigh. Copyright &copy; 2026 Autura NewCo, LLC.
    </p>
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
const GROUPS = <?= json_encode($amr_groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}' ?>;
const $    = id => document.getElementById(id);
const fmtD = n  => '$' + Math.round(n).toLocaleString();
const fmtN = n  => Math.round(n).toLocaleString();
const cap  = s  => s ? s.charAt(0) + s.slice(1).toLowerCase() : s;
const esc  = s  => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const MON = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const mlbl = ym => ym ? MON[+ym.split('-')[1]] + ' ' + ym.split('-')[0] : '—';
// Compact month range: "Mar – Apr 2026" (same year) or "Dec 2025 – Jan 2026".
const rangeLbl = ([a,b]) => {
  if(!a||!b) return '—';
  const ya=a.split('-')[0], ma=+a.split('-')[1], yb=b.split('-')[0], mb=+b.split('-')[1];
  return ya===yb ? `${MON[ma]} – ${MON[mb]} ${yb}` : `${MON[ma]} ${ya} – ${MON[mb]} ${yb}`;
};

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
function daysInMonth(y,mo){ // mo = 1..12
  return [31,(y%4===0&&(y%100!==0||y%400===0))?29:28,31,30,31,30,31,31,30,31,30,31][mo-1];
}
// Source data is month-granular ("Monthly auction start date"), so 60-day windows
// are approximated as 2 calendar months. If the data date falls mid-month, that
// trailing month is partial and would understate the current window — so anchor on
// the last COMPLETE month instead, keeping all comparison windows equal-length.
function lastCompleteMonth(maxM, dataDate){
  if(!maxM) return maxM;
  const m = dataDate && dataDate.match(/([A-Za-z]{3,})\s+(\d{1,2}),\s*(\d{4})/);
  if(!m) return maxM;
  const mo = MON.indexOf(m[1].slice(0,3)); // 1..12 (0 if unrecognized)
  if(mo<1) return maxM;
  const day=+m[2], yr=+m[3], dd=`${yr}-${String(mo).padStart(2,'0')}`;
  // The latest data month equals the data-date month and the month hasn't ended → partial.
  if(dd===maxM && day<daysInMonth(yr,mo)) return addM(maxM,-1);
  return maxM;
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
const regionOpt = r => RN[r] ? `${RN[r]} (${r})` : r;

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
function groupedBarSVG(clusters, {W=680, H=200, padL=64, padR=64, padT=24, padB=36, labels=['Last 60d','Prior 60d','Year Ago'], leftFmt=null, rightFmt=null}) {
  const plotW=W-padL-padR, plotH=H-padT-padB;
  const n=clusters.length, gW=plotW/n;
  // Period color convention: this 60d (near-black) · prior 60d (blue) · last-year (orange)
  const bColors=['var(--p-cur)','var(--p-prev)','var(--p-year)'];
  // Dual axis: count metrics scale to the left axis, dollar metrics to the right,
  // so volume (~10K) doesn't dwarf price (~$1K).
  const hasRight = clusters.some(c=>c.axis==='right');
  const leftMax  = Math.max(...clusters.filter(c=>c.axis!=='right').flatMap(c=>c.vals), 1);
  const rightMax = Math.max(...clusters.filter(c=>c.axis==='right').flatMap(c=>c.vals), 1);
  const lf = leftFmt  || (v=>v>9999?Math.round(v/1000)+'K':fmtN(v));
  const rf = rightFmt || (v=>fmtN(v));

  const yGrid=[0,.25,.5,.75,1].map(f=>{
    const y=(padT+plotH-f*plotH).toFixed(1);
    return `<line x1="${padL}" y1="${y}" x2="${W-padR}" y2="${y}" class="mi-grid-l"/>
    <text x="${padL-6}" y="${(+y+4).toFixed(1)}" text-anchor="end" class="mi-lbl">${lf(Math.round(f*leftMax))}</text>
    ${hasRight?`<text x="${W-padR+6}" y="${(+y+4).toFixed(1)}" text-anchor="start" class="mi-lbl">${rf(Math.round(f*rightMax))}</text>`:''}`;
  }).join('');

  const bW=Math.floor(gW*.2), gap=Math.floor(gW*.04);

  const bars=clusters.map((cl,ci)=>{
    const mx = cl.axis==='right'?rightMax:leftMax;
    const gx=padL+ci*gW+gW/2;
    const bs=cl.vals.map((v,vi)=>{
      const bh=v?Math.round(v/mx*plotH):0;
      const bx=gx+(vi-1)*(bW+gap)-bW/2;
      const by=padT+plotH-bh;
      return `<rect x="${bx.toFixed(1)}" y="${by.toFixed(1)}" width="${bW}" height="${bh}" style="fill:${bColors[vi]}" rx="2"/>`;
    }).join('');
    const hint = !hasRight ? '' : (cl.axis==='right'?' ($)':' (#)');
    return `${bs}
    <text x="${gx.toFixed(1)}" y="${H-6}" text-anchor="middle" class="mi-lbl">${cl.label}${hint}</text>`;
  }).join('');

  // HTML legend above the chart — wraps cleanly so long date-range labels don't collide.
  const legend=`<div class="gbar-legend">${labels.map((l,i)=>
    `<span class="gbar-key"><span class="gbar-sw" style="background:${bColors[i]}"></span>${l}</span>`).join('')}</div>`;

  return `${legend}<svg viewBox="0 0 ${W} ${H}" style="width:100%;display:block;max-width:${W}px">
    ${yGrid}
    <line x1="${padL}" y1="${padT}" x2="${padL}" y2="${padT+plotH}" class="mi-axis"/>
    ${hasRight?`<line x1="${W-padR}" y1="${padT}" x2="${W-padR}" y2="${padT+plotH}" class="mi-axis"/>`:''}
    <line x1="${padL}" y1="${padT+plotH}" x2="${W-padR}" y2="${padT+plotH}" class="mi-axis"/>
    ${bars}
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

function kpiCard(title,value,sub,key,fmt,s1,s2,s3,filtered,ns1,showVsNat=true){
  const dspan=(cur,base)=>{
    if(cur==null||base==null||!base) return '';
    const d=(cur-base)/base*100; const cls=d>0?'pos':d<0?'neg':'neu';
    return `<span class="kpi-d ${cls}">${d>0?'▲':d<0?'▼':'–'}${Math.abs(d).toFixed(1)}%</span>`;
  };
  // Always show the current-vs-prior-60 and current-vs-same-period-last-year rows.
  let body=`
      <div class="kpi-cmp"><span class="p-prev">Prior 60 days</span><span><b>${s2?fmt(s2[key]):'—'}</b>${dspan(s1?.[key], s2?.[key])}</span></div>
      <div class="kpi-cmp"><span class="p-year">Same period last year</span><span><b>${s3?fmt(s3[key]):'—'}</b>${dspan(s1?.[key], s3?.[key])}</span></div>`;
  // When a filter is active, also compare to the national average — except for raw
  // counts, where "X% vs national" is just the subset size and not meaningful.
  if(filtered && showVsNat && s1 && ns1 && ns1[key]){
    const d=(s1[key]-ns1[key])/ns1[key]*100; const cls=d>0?'pos':d<0?'neg':'neu';
    body+=`<div class="kpi-cmp"><span>National avg</span><span><b>${fmt(ns1[key])}</b><span class="kpi-d ${cls}">${d>0?'▲':d<0?'▼':'–'}${Math.abs(d).toFixed(1)}%</span></span></div>`;
  }
  return `<div class="mi-card"><div class="mi-card-title">${title}</div><div class="kpi-val">${value}</div><div class="kpi-lbl">${sub}</div>${body}</div>`;
}

// ── Generic impact slider ─────────────────────────────────────────────────────
function makeImpactSlider({sliderId, pctLblId, noteId, label,
    v60Id, val60Id, rev60Id, vYrId, valYrId, revYrId,
    current, total, prem}) {

  const slider = document.getElementById(sliderId);
  if (!slider) return;
  const get = id => document.getElementById(id);
  const set = (id, txt) => { const el=get(id); if(el) el.textContent=txt; };
  const currentPct = total ? (100*current/total) : 0;

  const update = () => {
    const target     = +slider.value;
    const additional = Math.max(0, Math.round((target/100)*total) - current);
    const addlVal    = additional * prem;
    const addlRev    = addlVal * 0.115;

    set(pctLblId, target + '%');
    set(v60Id,    '+' + fmtN(additional));
    set(val60Id,  '+' + fmtD(addlVal));
    set(rev60Id,  '+' + fmtD(addlRev));
    set(vYrId,    '+' + fmtN(additional * 6));
    set(valYrId,  '+' + fmtD(addlVal * 6));
    set(revYrId,  '+' + fmtD(addlRev * 6));
    const n = get(noteId);
    if (n) n.innerHTML =
      `Each improved vehicle adds <span class="hi">${fmtD(prem)}</span> in sale value → ` +
      `<span class="hi">${fmtD(prem*0.115)}</span> in buyer premium per car. ` +
      `Moving from <strong>${currentPct.toFixed(1)}%</strong> to <strong>${target}%</strong> (${fmtN(additional)} more vehicles) ` +
      `would add <span class="hi">${fmtD(addlRev)}</span> over 60 days — ` +
      `<span class="hi">${fmtD(addlRev*6)}</span> projected annually.`;
  };

  slider.addEventListener('input', update);
  update();
}

// ── Insight helper ────────────────────────────────────────────────────────────
const _bulb = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
const insight = text => `<div class="mi-insight">${_bulb}<span>${text}</span></div>`;
const pct1 = (n,d) => d ? (100*n/d).toFixed(1)+'%' : '—';
const dFmt = (d,pos='<span class="iup">',neg='<span class="idn">') =>
  d > 0.05 ? `${pos}▲${Math.abs(d).toFixed(1)}%</span>` :
  d < -0.05 ? `${neg}▼${Math.abs(d).toFixed(1)}%</span>` : '<span>±0%</span>';

// ── Main render ────────────────────────────────────────────────────────────────
function renderPage(V, opts={}) {
  const { filtered=false, allV=V, regions=[], sellers=[], groups=[] } = opts;
  // Human-readable label for the active selection (pooled regions + sellers + groups).
  const selLabel = (()=>{
    const parts=[];
    if(groups.length)  parts.push(groups.length===1?groups[0]:`${groups.length} groups`);
    if(regions.length) parts.push(regions.length===1?regionOpt(regions[0]):`${regions.length} regions`);
    if(sellers.length) parts.push(sellers.length===1?sellers[0]:`${sellers.length} sellers`);
    return parts.join(' + ') || 'Selection';
  })();
  // Period windows are derived from the full national dataset so the "last 60 days"
  // window stays identical across regions (a thin region must not shift the calendar).
  const allM=[...new Set(allV.filter(v=>v.month).map(v=>v.month))].sort();
  const maxM=allM.at(-1)||'';
  // Anchor on the last complete month so a partial trailing month (per the data date)
  // doesn't understate the current window vs the full prior / year-ago windows.
  const anchorM=lastCompleteMonth(maxM, AMR_DATA_DATE);
  const p1=[addM(anchorM,-1),anchorM], p2=[addM(anchorM,-3),addM(anchorM,-2)], p3=[addM(anchorM,-13),addM(anchorM,-12)];
  const inP=(v,[a,b])=>v.month&&v.month>=a&&v.month<=b;
  const r1=V.filter(v=>inP(v,p1)), r2=V.filter(v=>inP(v,p2)), r3=V.filter(v=>inP(v,p3));
  const s1=stats(r1), s2=stats(r2), s3=stats(r3);

  // National baseline for the current 60-day window (only needed when a filter is active).
  const ns1 = filtered ? stats(allV.filter(v=>inP(v,p1))) : null;
  const cmpS = filtered ? ns1 : s2;
  const cmpL = filtered ? 'vs national' : 'vs prior 60d';

  $('mi-period').innerHTML =
    (AMR_DATA_DATE ? `Dataset: <strong>${AMR_DATA_DATE}</strong> &nbsp;&middot;&nbsp; ` : '') +
    (filtered ? `Filter: <strong>${esc(selLabel)}</strong> &nbsp;&middot;&nbsp; ` : '') +
    `Showing ${mlbl(p1[0])} – ${mlbl(p1[1])} &nbsp;&middot;&nbsp; ${fmtN(V.length)} ${filtered?'filtered':'total'} records`;

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
    {label:'Volume',    axis:'left',  vals:[s1?.count??0, s2?.count??0, s3?.count??0]},
    {label:'Avg Price', axis:'right', vals:[s1?.avg??0,   s2?.avg??0,   s3?.avg??0]},
    {label:'Median',    axis:'right', vals:[s1?.median??0,s2?.median??0,s3?.median??0]},
  ];
  const gBar=groupedBarSVG(periodClusters,{W:640,H:210,labels:[
    `${mlbl(p1[0])}–${mlbl(p1[1])}`,
    `${mlbl(p2[0])}–${mlbl(p2[1])}`,
    `${mlbl(p3[0])}–${mlbl(p3[1])}`
  ],
    leftFmt:v=>v>9999?Math.round(v/1000)+'K':fmtN(v),
    rightFmt:v=>'$'+(v>999?Math.round(v/1000)+'K':Math.round(v))});

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
  let tEnd=allM.indexOf(anchorM); if(tEnd<0) tEnd=allM.length-1;
  const tMonths=allM.slice(Math.max(0,tEnd-11), tEnd+1);
  const tData=tMonths.map(m=>{const r=V.filter(v=>v.month===m);return{m,cnt:r.length,avg:r.length?Math.round(r.reduce((a,v)=>a+v.price,0)/r.length):0};});
  const trend=trendSVG(tData,tMonths);

  // ── Assemble ───────────────────────────────────────────────────────────────
  const curLbl = rangeLbl(p1);

  $('mi-body').innerHTML = `

    <!-- Period windows legend -->
    <div class="mi-periods">
      <div class="mi-period-chip"><b>Current 60 days</b><span>${curLbl}</span></div>
      <div class="mi-period-chip"><b>Prior 60 days</b><span>${rangeLbl(p2)}</span></div>
      <div class="mi-period-chip"><b>Same period last year</b><span>${rangeLbl(p3)}</span></div>
    </div>

    <!-- KPIs -->
    <div class="mi-g3">
      ${kpiCard('Total Sales (60d)', fmtN(s1?.count??0), curLbl, 'count', fmtN, s1, s2, s3, filtered, ns1, false)}
      ${kpiCard('Avg Sale Price', fmtD(s1?.avg??0), curLbl, 'avg', fmtD, s1, s2, s3, filtered, ns1)}
      ${kpiCard('Median Sale Price', fmtD(s1?.median??0), curLbl, 'median', fmtD, s1, s2, s3, filtered, ns1)}
    </div>

    <!-- Period chart + table -->
    <div class="mi-section">
      <h2>60-Day Period Comparison</h2>
      <p>Current vs prior 60 days and the same period one year ago.</p>
    </div>
    ${(()=>{
      if(volChgPrior===null) return '';
      const vd=dFmt(volChgPrior), pd=dFmt(priceChgPrior??0), yd=priceChgYoY!==null?dFmt(priceChgYoY):'';
      return insight(`Volume is ${vd} and avg price is ${pd} vs the prior 60 days.${yd?` Compared to the <em>same period last year</em> (${mlbl(p3[0])}–${mlbl(p3[1])}), prices are ${yd} — seasonal effects cancel since it\'s the same calendar window.`:''}`);
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
            <th class="p-cur">${mlbl(p1[0])}–${mlbl(p1[1])}</th>
            <th class="p-prev">${mlbl(p2[0])}–${mlbl(p2[1])}</th>
            <th class="p-year">Same Period Last Year<br><span style="font-weight:400;text-transform:none;letter-spacing:0">${mlbl(p3[0])}–${mlbl(p3[1])}</span></th>
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
        ${filtered ? (()=>{
          // ── Selected filter vs national (current 60-day window) ──
          const rvnDelta=(c,n)=>{ if(c==null||n==null||!n) return '<span class="dz">—</span>'; const d=(c-n)/n*100; if(Math.abs(d)<0.05) return '<span class="dz">±0%</span>'; return `<span class="${d>0?'dp':'dn'}">${d>0?'▲':'▼'}${Math.abs(d).toFixed(1)}%</span>`; };
          const tr=(lbl,a,b,delta)=>`<tr><td>${lbl}</td><td>${a}</td><td>${b}</td><td>${delta}</td></tr>`;
          const ppRow=(lbl,key)=>{
            const rp=s1&&s1.count?100*s1[key]/s1.count:null, np=ns1&&ns1.count?100*ns1[key]/ns1.count:null;
            const d=(rp!=null&&np!=null)?rp-np:null;
            const dl=d==null?'<span class="dz">—</span>':(Math.abs(d)<0.05?'<span class="dz">±0pp</span>':`<span class="${d>0?'dp':'dn'}">${d>0?'▲':'▼'}${Math.abs(d).toFixed(1)}pp</span>`);
            return tr(lbl, rp!=null?rp.toFixed(1)+'%':'—', np!=null?np.toFixed(1)+'%':'—', dl);
          };
          const sharePct=ns1&&ns1.count?100*(s1?.count??0)/ns1.count:0;
          const priceDiff=ns1&&ns1.avg?((s1?.avg??0)-ns1.avg)/ns1.avg*100:0;
          const tbl=`<table class="mi-tbl">
            <thead><tr><th>Metric</th><th>This Region</th><th>National</th><th>Δ</th></tr></thead>
            <tbody>
              ${tr('Volume (60d)', fmtN(s1?.count??0), fmtN(ns1?.count??0), `<span class="dz">${sharePct.toFixed(1)}% of nat</span>`)}
              ${tr('Avg Price', fmtD(s1?.avg??0), fmtD(ns1?.avg??0), rvnDelta(s1?.avg, ns1?.avg))}
              ${tr('Median', fmtD(s1?.median??0), fmtD(ns1?.median??0), rvnDelta(s1?.median, ns1?.median))}
              ${ppRow('Key %','withKey')}
              ${ppRow('Starts %','starts')}
            </tbody></table>`;
          const ins=(ns1&&s1)?insight(`<span class="hi">${esc(selLabel)}</span> accounts for <span class="hi">${sharePct.toFixed(1)}%</span> of national volume. Its avg sale price of <span class="hi">${fmtD(s1.avg)}</span> is ${priceDiff>=0?`<span class="iup">▲${priceDiff.toFixed(1)}%</span> above`:`<span class="idn">▼${Math.abs(priceDiff).toFixed(1)}%</span> below`} the national average of <span class="hi">${fmtD(ns1.avg)}</span>.`):'';
          return `<div class="mi-card-title">${esc(selLabel)} vs National &nbsp;<span style="float:right;font-weight:400">Last 60 days</span></div>${tbl}${ins}`;
        })() : `
        <div class="mi-card-title">By Region &nbsp;<span style="float:right;font-weight:400">% vol &nbsp;·&nbsp; Avg $</span></div>
        <div class="mi-chart">${hBarSVG(regionData,{W:480,rowH:28,padL:100,padR:108})}</div>
        ${(()=>{
          if(!topReg||!botReg||topReg.r===botReg.r) return '';
          const spread=topReg.st.avg-botReg.st.avg;
          return insight(`<span class="hi">${rl(topReg.r)}</span> commands the highest avg price at <span class="hi">${fmtD(topReg.st.avg)}</span> vs <span class="hi">${fmtD(botReg.st.avg)}</span> in ${rl(botReg.r)} — a <span class="hi">${fmtD(spread)}</span> regional spread on identical vehicles.`);
        })()}`}
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

    <!-- Condition profile -->
    <div class="mi-section">
      <h2>Vehicle Condition Profile</h2>
      <p>How vehicles arrive at auction — and which combinations leave the most money on the table.</p>
    </div>
    <div class="mi-card" style="margin-bottom:16px">
      <div class="mi-card-title">Condition Mix — 60-Day Trend</div>
      <table class="mi-tbl" id="cond-profile-tbl"></table>
    </div>
    <div class="mi-card">
      <div class="mi-card-title">Problem Combinations — Last 60 Days</div>
      <table class="mi-tbl" id="cond-combo-tbl"></table>
    </div>

    <!-- Buyer-premium revenue calculators (collapsed by default; click + to expand) -->
    <details class="mi-collapse">
      <summary class="mi-section">
        <h2><span class="mi-collapse-ico"></span>Buyer Premium — Revenue Impact</h2>
        <p>Mileage, key &amp; starts revenue projections. Hidden by default — expand when you need it.</p>
      </summary>
      <div style="margin-top:16px">
    <div class="mi-card" style="margin-bottom:16px">
      <div class="ic-grid">

        <!-- Left: current state -->
        <div>
          <div class="mi-card-title">Current State (Last 60 Days)</div>
          <div class="ic-stat">
            <span class="ic-big">${(r1.length?(100*withOdo.length/r1.length):0).toFixed(1)}%</span>
            <span class="ic-desc">of vehicles have a confirmed odometer reading<br>(${fmtN(withOdo.length)} of ${fmtN(r1.length)})</span>
          </div>
          <div class="ic-stat">
            <span class="ic-big">${fmtD(odoPrem)}</span>
            <span class="ic-desc">avg sale premium when mileage is known vs unknown</span>
          </div>
          <div class="ic-stat">
            <span class="ic-big">11.5%</span>
            <span class="ic-desc">buyer premium rate you earn on the sale price</span>
          </div>
        </div>

        <!-- Right: scenario slider -->
        <div>
          <div class="mi-card-title">Scenario Calculator</div>
          <div class="ic-slider-label">
            Target mileage reporting rate &nbsp;
            <strong id="ic-target-pct">50%</strong>
          </div>
          <input type="range" id="odo-slider" class="ic-range"
            min="${Math.max(1,Math.ceil(r1.length?100*withOdo.length/r1.length:0))}"
            max="100" value="50" step="1">
          <div class="ic-slider-ends">
            <span>Current: ${(r1.length?(100*withOdo.length/r1.length):0).toFixed(1)}%</span>
            <span>100%</span>
          </div>

          <div class="ic-period-lbl">60-Day Impact</div>
          <div class="ic-boxes">
            <div class="ic-box">
              <div class="ic-box-val" id="ic-add-vehicles">—</div>
              <div class="ic-box-lbl">More vehicles<br>with mileage</div>
            </div>
            <div class="ic-box">
              <div class="ic-box-val" id="ic-add-value">—</div>
              <div class="ic-box-lbl">Additional sale<br>value</div>
            </div>
            <div class="ic-box ic-highlight">
              <div class="ic-box-val" id="ic-add-revenue">—</div>
              <div class="ic-box-lbl">Your buyer<br>premium</div>
            </div>
          </div>

          <div class="ic-period-lbl">Full-Year Projection <em>(× 6 periods)</em></div>
          <div class="ic-boxes">
            <div class="ic-box">
              <div class="ic-box-val" id="ic-add-vehicles-yr">—</div>
              <div class="ic-box-lbl">More vehicles<br>per year</div>
            </div>
            <div class="ic-box">
              <div class="ic-box-val" id="ic-add-value-yr">—</div>
              <div class="ic-box-lbl">Additional sale<br>value / year</div>
            </div>
            <div class="ic-box ic-highlight">
              <div class="ic-box-val" id="ic-add-revenue-yr">—</div>
              <div class="ic-box-lbl">Your premium<br>per year</div>
            </div>
          </div>

          <div class="mi-insight" style="margin-top:14px" id="ic-note"></div>
        </div>
      </div>
    </div>

    <!-- Key + Starts impact -->
    <div class="mi-card" style="margin-bottom:16px">
      <div class="ic-grid">
        <div>
          <div class="mi-card-title">Current State — Key &amp; Starts (Last 60 Days)</div>
          <div class="ic-stat">
            <span class="ic-big">${(r1.length?(100*r1.filter(v=>v.has_key&&v.starts).length/r1.length):0).toFixed(1)}%</span>
            <span class="ic-desc">of vehicles arrive with a key and able to start<br>(${fmtN(r1.filter(v=>v.has_key&&v.starts).length)} of ${fmtN(r1.length)})</span>
          </div>
          <div class="ic-stat">
            <span class="ic-big">${fullPrem!=null?fmtD(fullPrem):'—'}</span>
            <span class="ic-desc">avg premium: has key + starts vs no key + no start</span>
          </div>
          <div class="ic-stat">
            <span class="ic-big">11.5%</span>
            <span class="ic-desc">your buyer premium rate</span>
          </div>
        </div>
        <div>
          <div class="mi-card-title">Scenario Calculator</div>
          <div class="ic-slider-label">
            Target: vehicles with key that start &nbsp;
            <strong id="ks-target-pct">50%</strong>
          </div>
          <input type="range" id="ks-slider" class="ic-range"
            min="${Math.max(1,Math.ceil(r1.length?100*r1.filter(v=>v.has_key&&v.starts).length/r1.length:0))}"
            max="100" value="50" step="1">
          <div class="ic-slider-ends">
            <span>Current: ${(r1.length?(100*r1.filter(v=>v.has_key&&v.starts).length/r1.length):0).toFixed(1)}%</span>
            <span>100%</span>
          </div>

          <div class="ic-period-lbl">60-Day Impact</div>
          <div class="ic-boxes">
            <div class="ic-box">
              <div class="ic-box-val" id="ks-add-vehicles">—</div>
              <div class="ic-box-lbl">More vehicles<br>key + starts</div>
            </div>
            <div class="ic-box">
              <div class="ic-box-val" id="ks-add-value">—</div>
              <div class="ic-box-lbl">Additional sale<br>value</div>
            </div>
            <div class="ic-box ic-highlight">
              <div class="ic-box-val" id="ks-add-revenue">—</div>
              <div class="ic-box-lbl">Your buyer<br>premium</div>
            </div>
          </div>

          <div class="ic-period-lbl">Full-Year Projection <em>(× 6 periods)</em></div>
          <div class="ic-boxes">
            <div class="ic-box">
              <div class="ic-box-val" id="ks-add-vehicles-yr">—</div>
              <div class="ic-box-lbl">More vehicles<br>per year</div>
            </div>
            <div class="ic-box">
              <div class="ic-box-val" id="ks-add-value-yr">—</div>
              <div class="ic-box-lbl">Additional sale<br>value / year</div>
            </div>
            <div class="ic-box ic-highlight">
              <div class="ic-box-val" id="ks-add-revenue-yr">—</div>
              <div class="ic-box-lbl">Your premium<br>per year</div>
            </div>
          </div>

          <div class="mi-insight" style="margin-top:14px" id="ks-note"></div>
        </div>
      </div>
    </div>
      </div>
    </details>

    <!-- 12-month trend -->
    <div class="mi-section">
      <h2>12-Month Trend</h2>
      <p>Monthly volume (bars) and average sale price (line) across the most recent complete months. A partial trailing month (per the data date) is excluded.</p>
    </div>
    <div class="mi-card" style="margin-bottom:60px">
      <div class="mi-legend">
        <span><i class="leg-bar"></i> Volume</span>
        <span><i class="leg-line"></i> Avg Price</span>
      </div>
      <div class="mi-chart">${trend}</div>
    </div>
  `;

  makeImpactSlider({
    sliderId:'odo-slider', pctLblId:'ic-target-pct', noteId:'ic-note',
    label:'mileage reporting',
    v60Id:'ic-add-vehicles', val60Id:'ic-add-value', rev60Id:'ic-add-revenue',
    vYrId:'ic-add-vehicles-yr', valYrId:'ic-add-value-yr', revYrId:'ic-add-revenue-yr',
    current:withOdo.length, total:r1.length, prem:odoPrem
  });

  const ksGood = r1.filter(v=>v.has_key&&v.starts).length;
  makeImpactSlider({
    sliderId:'ks-slider', pctLblId:'ks-target-pct', noteId:'ks-note',
    label:'vehicles arriving with key that start',
    v60Id:'ks-add-vehicles', val60Id:'ks-add-value', rev60Id:'ks-add-revenue',
    vYrId:'ks-add-vehicles-yr', valYrId:'ks-add-value-yr', revYrId:'ks-add-revenue-yr',
    current:ksGood, total:r1.length, prem:fullPrem??0
  });

  // ── Condition profile tables ───────────────────────────────────────────────
  const n = r1.length || 1;
  const p = (x) => (100*x/n).toFixed(1)+'%';
  const dp = (sub,base) => {
    if(!sub||!base||!base.avg) return '—';
    const d = sub.avg-base.avg;
    return `<span class="${d>=0?'dp':'dn'}">${d>=0?'+':''}${fmtD(d)}</span>`;
  };

  // Condition definitions, scored as a share of each period (this 60d / prior 60d / last-yr 60d)
  // so the mix trend is visible. Colors follow the site convention (black / blue / orange).
  const condDefs = [
    ['Has Key',        v=>v.has_key],
    ['No Key',         v=>v.no_key],
    ['Key Unknown',    v=>!v.has_key&&!v.no_key],
    ['Starts',         v=>v.starts],
    ['Does Not Start', v=>!v.starts],
    ['Mileage Known',  v=>v.odo>0],
    ['No Mileage',     v=>!v.odo||v.odo<=0],
  ];
  const shr  = (recs,pred) => recs.length ? (100*recs.filter(pred).length/recs.length) : null;
  const trnd = (cur,base) => {
    if(cur==null||base==null) return '';
    const d=cur-base; if(Math.abs(d)<0.05) return '';
    return ` <span class="${d>0?'dp':'dn'}" style="font-size:10px">${d>0?'▲':'▼'}${Math.abs(d).toFixed(1)}</span>`;
  };

  const profTbl = document.getElementById('cond-profile-tbl');
  if (profTbl) {
    profTbl.innerHTML = `
      <thead><tr>
        <th>Condition</th>
        <th class="p-cur">This 60 days</th>
        <th class="p-prev">Prior 60 days</th>
        <th class="p-year">Same period last yr</th>
        <th>Avg Price (now)</th>
      </tr></thead>
      <tbody>${condDefs.map(([lbl,pred])=>{
        const a=shr(r1,pred), b=shr(r2,pred), c=shr(r3,pred);
        const av=stats(r1.filter(pred));
        return `<tr>
          <td>${lbl}</td>
          <td class="p-cur" style="font-weight:600">${a!=null?a.toFixed(1)+'%':'—'}</td>
          <td class="p-prev">${b!=null?b.toFixed(1)+'%':'—'}${trnd(a,b)}</td>
          <td class="p-year">${c!=null?c.toFixed(1)+'%':'—'}${trnd(a,c)}</td>
          <td>${av?fmtD(av.avg):'—'}</td>
        </tr>`;
      }).join('')}</tbody>`;
  }

  // problem combinations
  const combos = [
    ['No Mileage + No Key',             r1.filter(v=>(!v.odo||v.odo<=0)&&v.no_key)],
    ['No Mileage + Doesn\'t Start',     r1.filter(v=>(!v.odo||v.odo<=0)&&!v.starts)],
    ['No Key + Doesn\'t Start',         r1.filter(v=>v.no_key&&!v.starts)],
    ['No Mileage + No Key + No Start',  r1.filter(v=>(!v.odo||v.odo<=0)&&v.no_key&&!v.starts)],
    ['Has Mileage + Has Key + Starts',  r1.filter(v=>v.odo>0&&v.has_key&&v.starts)],
  ];

  const comboTbl = document.getElementById('cond-combo-tbl');
  if (comboTbl) {
    comboTbl.innerHTML = `
      <thead><tr>
        <th>Combination</th><th># Vehicles</th><th>% of Total</th><th>Avg Price</th><th>vs Overall</th>
      </tr></thead>
      <tbody>${combos.map(([lbl,recs])=>{
        const st=stats(recs);
        const isGood=lbl.startsWith('Has Mileage');
        return st&&st.count>=3 ? `<tr${isGood?' style="font-weight:600"':''}>
          <td>${lbl}</td>
          <td>${fmtN(st.count)}</td>
          <td>${p(st.count)}</td>
          <td>${fmtD(st.avg)}</td>
          <td>${dp(st,s1)}</td>
        </tr>` : '';
      }).join('')}</tbody>`;
  }
}

// ── Searchable multi-select widget ──────────────────────────────────────────────
function makeMultiSelect({mountId, label, items, onChange}) {
  const root = document.getElementById(mountId);
  if (!root) return { get:()=>new Set(), clear:()=>{} };
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

  // Replace the option list (e.g. sellers limited to the chosen region). Drops any
  // current selections that are no longer available.
  const update = (newItems) => {
    curItems = newItems;
    const valid = new Set(curItems.map(it=>it.value));
    [...selected].forEach(v=>{ if(!valid.has(v)) selected.delete(v); });
    renderList(search.value); updateSum();
  };

  return { get:()=>selected, clear:()=>{ selected.clear(); updateSum(); }, update };
}

// ── Boot ──────────────────────────────────────────────────────────────────────
let ALL_V = [], msRegion, msSeller, msGroup;
function applyFilters() {
  const rs = msRegion.get(), ss = msSeller.get(), gs = msGroup ? msGroup.get() : new Set();
  let groupSellers = null;
  if (gs.size>0) { groupSellers = new Set(); gs.forEach(g => (GROUPS[g]||[]).forEach(s => groupSellers.add(s))); }
  const filtered = rs.size>0 || ss.size>0 || gs.size>0;
  const V = filtered
    ? ALL_V.filter(v => (rs.size===0||rs.has(v.region)) && (ss.size===0||ss.has(v.seller)) && (!groupSellers||groupSellers.has(v.seller)))
    : ALL_V;
  const reset=$('mi-filter-reset'); if(reset) reset.hidden=!filtered;
  const h=$('mi-region-hint'); if(h) h.textContent = filtered ? 'Deltas shown vs national average' : '';
  renderPage(V, { filtered, allV:ALL_V, regions:[...rs], sellers:[...ss], groups:[...gs] });
}
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
      seller:(data.sellers&&r[9]>=0)?data.sellers[r[9]]:'',
    }));
    ALL_V = V;

    // Build filter items (sorted by volume, with record counts).
    const tally=(key)=>{const m={};V.forEach(v=>{if(v[key])m[v[key]]=(m[v[key]]||0)+1;});return m;};
    const rc=tally('region'), sc=tally('seller');
    const rItems=Object.keys(rc).sort((a,b)=>rc[b]-rc[a]).map(c=>({value:c,label:regionOpt(c),count:rc[c]}));
    // Seller list limited to the currently-selected region(s) (all sellers when no region picked).
    const sellerItems=()=>{
      const rs=msRegion.get(), m={};
      ALL_V.forEach(v=>{ if(v.seller && (rs.size===0||rs.has(v.region))) m[v.seller]=(m[v.seller]||0)+1; });
      return Object.keys(m).sort((a,b)=>m[b]-m[a]).map(s=>({value:s,label:s,count:m[s]}));
    };
    const onRegionChange=()=>{ msSeller.update(sellerItems()); applyFilters(); };
    msRegion=makeMultiSelect({mountId:'ms-region', label:'Region', items:rItems, onChange:onRegionChange});
    msSeller=makeMultiSelect({mountId:'ms-seller', label:'Seller', items:Object.keys(sc).sort((a,b)=>sc[b]-sc[a]).map(s=>({value:s,label:s,count:sc[s]})), onChange:applyFilters});

    // Group filter — only shown when seller groups have been defined (Settings → Define Seller Groups).
    const groupNames=Object.keys(GROUPS||{});
    if(groupNames.length){
      const gItems=groupNames.sort().map(g=>{ const mem=new Set(GROUPS[g]); return {value:g,label:g,count:ALL_V.reduce((n,v)=>n+(mem.has(v.seller)?1:0),0)}; });
      msGroup=makeMultiSelect({mountId:'ms-group', label:'Group', items:gItems, onChange:applyFilters});
    }

    const reset=$('mi-filter-reset');
    if(reset) reset.addEventListener('click', ()=>{ msRegion.clear(); msSeller.clear(); if(msGroup) msGroup.clear(); msSeller.update(sellerItems()); applyFilters(); });
    // Close any open popover when clicking elsewhere.
    document.addEventListener('click', ()=>document.querySelectorAll('.mi-ms-pop').forEach(p=>p.hidden=true));

    renderPage(V, { filtered:false, allV:V, regions:[], sellers:[] });
  } catch(e) {
    $('mi-body').innerHTML='<div class="mi-load" style="color:var(--text-muted)">Could not load market data. Try refreshing.</div>';
  }
})();
</script>

<div class="mi-print-footer container">
  &copy; <?= date('Y') ?> Autura NewCo, LLC. &nbsp;&middot;&nbsp; <a href="https://autura.com" style="color:inherit">autura.com</a> &nbsp;&middot;&nbsp; Autura Market Report &nbsp;&middot;&nbsp; Confidential — Internal Use Only
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
