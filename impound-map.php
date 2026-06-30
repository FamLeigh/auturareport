<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login
require_once __DIR__ . '/includes/functions.php';

// ── Per-account marks (durable, server-side): already-sold + removed ─────────
$sold_file    = __DIR__ . '/data/impound-sold.json';
$removed_file = __DIR__ . '/data/impound-removed.json';
$nt_file      = __DIR__ . '/data/impound-nottarget.json';
$alias_file   = __DIR__ . '/data/impound-aliases.json';   // { originalImpoundName: mergedToName }
$memo_file    = __DIR__ . '/data/impound-memos.json';     // { "Account Name": "short memo" }
function _im_toggle(string $file, string $name): bool {
    $on = false;
    $fp = fopen($file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $size = fstat($fp)['size'] ?? 0;
        $map  = $size > 0 ? (json_decode(fread($fp, $size), true) ?: []) : [];
        if (isset($map[$name])) unset($map[$name]);
        elseif ($name !== '') { $map[$name] = true; $on = true; }
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($map, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN); fclose($fp);
    }
    return $on;
}
function _im_load(string $file): array {
    return file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_sold'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'sold' => _im_toggle($sold_file, trim((string) $_POST['toggle_sold']))]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_removed'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'removed' => _im_toggle($removed_file, trim((string) $_POST['toggle_removed']))]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_nt'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'nt' => _im_toggle($nt_file, trim((string) $_POST['toggle_nt']))]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alias_from'])) {
    $from = trim((string) $_POST['alias_from']);
    $to   = trim((string) ($_POST['alias_to'] ?? ''));
    $fp = fopen($alias_file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $size = fstat($fp)['size'] ?? 0;
        $map  = $size > 0 ? (json_decode(fread($fp, $size), true) ?: []) : [];
        if ($from !== '' && $to !== '' && $to !== $from) $map[$from] = $to;
        else unset($map[$from]);
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($map, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN); fclose($fp);
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['memo_name'])) {
    $name = trim((string) $_POST['memo_name']);
    $text = substr(trim((string) ($_POST['memo_text'] ?? '')), 0, 280);
    $fp = fopen($memo_file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $size = fstat($fp)['size'] ?? 0;
        $map  = $size > 0 ? (json_decode(fread($fp, $size), true) ?: []) : [];
        if ($name !== '' && $text !== '') $map[$name] = $text;
        else unset($map[$name]);
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($map, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN); fclose($fp);
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
$sold_json    = json_encode(_im_load($sold_file),    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$removed_json = json_encode(_im_load($removed_file), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$nt_json      = json_encode(_im_load($nt_file),      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$alias_json   = json_encode(_im_load($alias_file),   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$memo_json    = json_encode(_im_load($memo_file),    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

// Hidden / local-only map of last-known impound auction volume (deduped, top 100).
$page_title = 'Impound Auction Volume';
$body_class = 'page-impound';
$canonical  = '/impound-map';

const IM_STATE_ABBR = ['alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR','california'=>'CA','colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE','district of columbia'=>'DC','florida'=>'FL','georgia'=>'GA','hawaii'=>'HI','idaho'=>'ID','illinois'=>'IL','indiana'=>'IN','iowa'=>'IA','kansas'=>'KS','kentucky'=>'KY','louisiana'=>'LA','maine'=>'ME','maryland'=>'MD','massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS','missouri'=>'MO','montana'=>'MT','nebraska'=>'NE','nevada'=>'NV','new hampshire'=>'NH','new jersey'=>'NJ','new mexico'=>'NM','new york'=>'NY','north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH','oklahoma'=>'OK','oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI','south carolina'=>'SC','south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT','vermont'=>'VT','virginia'=>'VA','washington'=>'WA','west virginia'=>'WV','wisconsin'=>'WI','wyoming'=>'WY'];

// Project the US-states GeoJSON to SVG paths (lower-48 + DC, AK/HI insets).
function im_state_paths(): array {
    $file = __DIR__ . '/assets/us-states.geojson';
    if (!is_readable($file)) return [];
    $geo = json_decode(file_get_contents($file), true);
    if (!$geo || empty($geo['features'])) return [];
    $cosL = cos(deg2rad(37.5));
    $ringsOf = function ($g) {
        $t = $g['type'] ?? ''; $c = $g['coordinates'] ?? [];
        if ($t === 'Polygon') return $c;
        if ($t === 'MultiPolygon') { $r = []; foreach ($c as $poly) foreach ($poly as $ring) $r[] = $ring; return $r; }
        return [];
    };
    $main = []; $ak = null; $hi = null;
    foreach ($geo['features'] as $f) {
        $ab = IM_STATE_ABBR[strtolower($f['properties']['name'] ?? '')] ?? null;
        if (!$ab) continue;
        if ($ab === 'AK') { $ak = ['AK' => $f]; continue; }
        if ($ab === 'HI') { $hi = ['HI' => $f]; continue; }
        $main[$ab] = $f;
    }
    $paths = [];
    $emit = function ($feats, $rect, $clipLonGt = null) use (&$paths, $ringsOf, $cosL) {
        [$rx, $ry, $rw, $rh] = $rect;
        $minX = INF; $maxX = -INF; $minY = INF; $maxY = -INF;
        foreach ($feats as $f) foreach ($ringsOf($f['geometry']) as $ring) foreach ($ring as $pt) {
            if ($clipLonGt !== null && $pt[0] > $clipLonGt) continue;
            $x = $pt[0] * $cosL;
            $minX = min($minX, $x); $maxX = max($maxX, $x); $minY = min($minY, $pt[1]); $maxY = max($maxY, $pt[1]);
        }
        $w = max(1e-6, $maxX - $minX); $h = max(1e-6, $maxY - $minY);
        $s = min($rw / $w, $rh / $h);
        $ox = $rx + ($rw - $w * $s) / 2; $oy = $ry + ($rh - $h * $s) / 2;
        foreach ($feats as $ab => $f) {
            $d = '';
            foreach ($ringsOf($f['geometry']) as $ring) {
                if ($clipLonGt !== null) { $ring = array_values(array_filter($ring, fn($pt) => $pt[0] <= $clipLonGt)); if (count($ring) < 3) continue; }
                $first = true;
                foreach ($ring as $pt) {
                    $x = round($ox + ($pt[0] * $cosL - $minX) * $s, 1);
                    $y = round($oy + ($maxY - $pt[1]) * $s, 1);
                    $d .= ($first ? 'M' : 'L') . $x . ',' . $y; $first = false;
                }
                $d .= 'Z';
            }
            if ($d !== '') $paths[$ab] = ($paths[$ab] ?? '') . $d;
        }
    };
    $emit($main, [8, 8, 944, 470]);
    if ($ak) $emit($ak, [12, 472, 185, 120], 0);
    if ($hi) $emit($hi, [215, 505, 120, 85]);
    return $paths;
}

$im = file_exists(__DIR__ . '/assets/csvs/impound-combined.json') ? json_decode(file_get_contents(__DIR__ . '/assets/csvs/impound-combined.json'), true) : ['accounts' => []];
$im_json  = json_encode($im, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{"accounts":[]}';
$geo_json = json_encode(im_state_paths(), JSON_UNESCAPED_SLASHES) ?: '{}';

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
body.page-impound .container { max-width:none; padding:0 28px; }   /* full-page width for the list */
.crx-geo { width:100%; height:auto; max-width:820px; display:block; }
.crx-geo path { cursor:pointer; transition:stroke .1s; fill:var(--surface-2); }
.crx-geo path:hover { stroke:var(--accent); stroke-width:1.6; }
.crx-legend { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-muted); margin-top:12px; }
.crx-legend i { width:22px; height:10px; border-radius:2px; display:inline-block; }
.crx-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin:20px 0; }
.crx-kpi { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px 20px; }
.crx-kpi b { font-size:1.7rem; font-weight:800; display:block; line-height:1.1; }
.crx-kpi span { font-size:12px; color:var(--text-muted); }
.im-name { color:#000; font-weight:700; }
[data-theme="dark"] .im-name { color:var(--text); }
.im-edit { color:#000; font-weight:700; cursor:pointer; }
[data-theme="dark"] .im-edit { color:var(--text); }
.im-edit:hover { text-decoration:underline; text-decoration-style:dotted; }
.im-editbox { font-size:13px; font-weight:600; padding:4px 8px; border:1px solid var(--accent); border-radius:6px; background:var(--bg); color:var(--text); min-width:220px; }
.im-memo { color:var(--text-muted); cursor:pointer; font-size:12px; }
.im-memo:hover { color:var(--accent); }
.im-memo-empty { font-style:italic; opacity:.55; }
.im-memobox { font-size:13px; padding:4px 8px; border:1px solid var(--accent); border-radius:6px; background:var(--bg); color:var(--text); width:200px; }
.im-act.im-soldbtn:hover { border-color:#2e8a4c; color:#2e8a4c; }
.im-act.im-soldbtn.on { border-color:#2e8a4c; color:#2e8a4c; background:rgba(46,138,76,.14); }
.im-act:disabled { cursor:default; }
.cr-tbl tr.im-sold td, .cr-tbl tr.im-sold td:first-child { background:rgba(46,138,76,.16) !important; }
[data-theme="dark"] .cr-tbl tr.im-sold td, [data-theme="dark"] .cr-tbl tr.im-sold td:first-child { background:rgba(94,201,124,.15) !important; }
.im-badge { display:inline-block; font-size:10px; font-weight:700; padding:1px 7px; border-radius:999px; background:rgba(46,138,76,.15); color:#2e8a4c; margin-left:7px; }
[data-theme="dark"] .im-badge { color:#5ec97c; }
.im-act { background:none; border:1px solid var(--border); border-radius:6px; color:var(--text-muted); font-size:12px; line-height:1; padding:4px 8px; cursor:pointer; }
.im-act:hover { border-color:#c0392b; color:#c0392b; }
.im-act.im-restore:hover { border-color:var(--accent); color:var(--accent); }
.im-act.im-nt:hover { border-color:#888; color:var(--text); }
.im-act.im-nt.on { border-color:#888; color:var(--text); background:var(--surface-2); }
.im-badge.im-ntb { background:rgba(120,120,120,.16); color:var(--text-muted); }
.cr-tbl tr.im-nt-row td, .cr-tbl tr.im-nt-row td:first-child { background:rgba(192,57,43,.13) !important; }
[data-theme="dark"] .cr-tbl tr.im-nt-row td, [data-theme="dark"] .cr-tbl tr.im-nt-row td:first-child { background:rgba(224,90,90,.16) !important; }
.cr-tbl th.im-sort { cursor:pointer; user-select:none; }
.cr-tbl th.im-sort:hover { color:var(--text); }
</style>';

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <section class="cr-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/customer-research" style="color:var(--text-muted);text-decoration:none;">&larr; Customer Research</a>
    </p>
    <h1>Impound Volume &amp; Marketplace Sellers</h1>
    <p class="cr-sub">Impound accounts (last-known LTM auction volume) combined with Autura marketplace sellers (sales volume). <em>Hidden / local only.</em></p>
  </section>

  <div class="crx-kpis" id="im-kpis"></div>

  <div style="display:flex;gap:18px;align-items:center;margin:6px 0 4px;flex-wrap:wrap;font-size:13px;">
    <span style="font-weight:700;letter-spacing:.05em;text-transform:uppercase;font-size:11px;color:var(--text-muted);">Show</span>
    <label style="display:flex;gap:7px;align-items:center;cursor:pointer;"><input type="checkbox" id="im-showimp" checked> Impound list (LTM auction volume)</label>
    <label style="display:flex;gap:7px;align-items:center;cursor:pointer;"><input type="checkbox" id="im-showsel" checked> Marketplace sellers (sales volume)</label>
  </div>

  <div class="cr-toolbar" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin:8px 0 12px;">
    <span class="cr-count" id="im-count"></span>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <select class="cr-search" id="im-soldfilter" style="min-width:auto;cursor:pointer;">
        <option value="all">All accounts</option>
        <option value="sold">Already sold</option>
        <option value="not">Not sold</option>
        <option value="nt">Not a target</option>
        <option value="removed">Removed (restore)</option>
      </select>
      <input class="cr-search" id="im-search" type="text" placeholder="Search account or state…">
    </div>
  </div>
  <p class="cr-sub" style="margin:-4px 0 12px;">Tip: click an impound <strong>name</strong> to rename / merge it to a selling customer · use the row buttons for <strong>✓ sold</strong>, <strong>⊘ not a target</strong>, <strong>✕ remove</strong> · click <strong>Memo</strong> to add a note.</p>

  <div id="im-map"></div>
  <div id="im-table" style="margin-top:18px;"></div>
</div>

<script>
const IM  = <?= $im_json ?>;
const GEO = <?= $geo_json ?>;
const ABBR2NAME = {};
<?php foreach (IM_STATE_ABBR as $n => $a): ?>ABBR2NAME["<?= $a ?>"] = "<?= ucwords($n) ?>";<?php endforeach; ?>
const fmtN = n => Number(n||0).toLocaleString();
const esc  = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const ALL = IM.accounts || [];
const SOLD    = <?= $sold_json ?>;                   // manual { "Account Name": true }
const REMOVED = <?= $removed_json ?>;                // { "Account Name": true }
const NT      = <?= $nt_json ?>;                     // not-a-target { "Account Name": true }
const ALIASES = <?= $alias_json ?>;                  // { originalImpoundName: mergedToName }
const MEMOS   = <?= $memo_json ?>;                   // { "Account Name": "short memo" }
let term = '';
let soldFilter = 'all';
let sortKey = 'imp';    // name | imp | sales | loc
let sortDir = -1;       // 1 asc, -1 desc
let showImp = true, showSel = true;  // which source list(s) to show
let editing = null;     // impoundSrc name being edited (merge)
let editingMemo = null; // account name whose memo is being edited

// Selling-customer names for the merge autocomplete
const SELLER_NAMES = [...new Set(ALL.filter(a => a.isSeller).map(a => a.name))].sort();

// Effective rows: apply name aliases (impound → selling customer) then merge by name.
function accounts() {
  const by = {};
  for (const a of ALL) {
    const name = (a.isImpound && ALIASES[a.name]) ? ALIASES[a.name] : a.name;
    let t = by[name];
    if (!t) t = by[name] = { name, impoundVol:null, salesVol:null, st:null, city:'', isImpound:false, isSeller:false, impoundSrc:null };
    if (a.impoundVol != null) t.impoundVol = Math.max(t.impoundVol||0, a.impoundVol);
    if (a.salesVol  != null) t.salesVol  = Math.max(t.salesVol||0,  a.salesVol);
    t.isImpound = t.isImpound || a.isImpound;
    t.isSeller  = t.isSeller  || a.isSeller;
    if (!t.st && a.st) { t.st = a.st; t.city = a.city; }
    if (a.isImpound) t.impoundSrc = a.name;   // original impound name (alias key for editing)
  }
  return Object.values(by);
}

// An account counts as sold if manually marked OR it has marketplace sales.
const isSold = a => !!SOLD[a.name] || (a.salesVol != null && a.salesVol > 0);

const locKey = a => a.st ? (ABBR2NAME[a.st]||a.st) + '|' + (a.city||'') : 'zzzzz';
const SORTERS = {
  name:  (a,b) => a.name.localeCompare(b.name),
  imp:   (a,b) => (a.impoundVol ?? -1) - (b.impoundVol ?? -1),
  sales: (a,b) => (a.salesVol ?? -1) - (b.salesVol ?? -1),
  loc:   (a,b) => locKey(a).localeCompare(locKey(b)),
};

const inSource = a => (showImp && a.isImpound) || (showSel && a.isSeller);
const visible  = () => accounts().filter(a => !REMOVED[a.name] && inSource(a));

// Map shades by impound LTM volume (impound accounts, not removed), independent of toggles.
function mapST() {
  const ST = {};
  for (const a of accounts()) {
    if (REMOVED[a.name] || !a.impoundVol || !a.st) continue;
    if (!ST[a.st]) ST[a.st] = {vol:0, n:0};
    ST[a.st].vol += a.impoundVol; ST[a.st].n++;
  }
  return ST;
}

function renderKpis() {
  const vis = visible();
  const k = [
    [fmtN(vis.length), 'In list'],
    [fmtN(vis.reduce((s,a) => s + (a.impoundVol||0), 0)), 'Impound LTM vol (shown)'],
    [fmtN(vis.reduce((s,a) => s + (a.salesVol||0), 0)), 'Sales volume (shown)'],
    [fmtN(vis.filter(isSold).length), 'Sold (incl. sellers)'],
    [fmtN(vis.filter(a => NT[a.name]).length), 'Not a target'],
    [fmtN(accounts().filter(a => REMOVED[a.name]).length), 'Removed'],
  ];
  document.getElementById('im-kpis').innerHTML = k.map(([v,l]) => `<div class="crx-kpi"><b>${v}</b><span>${l}</span></div>`).join('');
}

function renderMap() {
  const ST = mapST();
  const max = Math.max(1, ...Object.values(ST).map(s => s.vol));
  let p = '';
  for (const ab in GEO) {
    const m = ST[ab];
    const style = m ? ` style="fill:rgba(240,165,0,${(0.12 + 0.88 * m.vol / max).toFixed(2)})"` : '';
    const title = m ? `${ABBR2NAME[ab]||ab}: ${fmtN(m.vol)} LTM vol · ${m.n} accounts` : `${ABBR2NAME[ab]||ab}: none in list`;
    p += `<path data-ab="${ab}" d="${GEO[ab]}"${style} stroke="var(--border)" stroke-width="0.7" fill-rule="evenodd"><title>${esc(title)}</title></path>`;
  }
  document.getElementById('im-map').innerHTML =
    `<svg viewBox="0 0 960 600" class="crx-geo">${p}</svg>
     <div class="crx-legend"><span>Low</span><i style="background:rgba(240,165,0,.18)"></i><i style="background:rgba(240,165,0,.5)"></i><i style="background:rgba(240,165,0,.9)"></i><span>High (${fmtN(max)} LTM vol)</span> &nbsp;·&nbsp; <span>click a state to filter the list</span></div>`;
}

function renderTable() {
  const removedView = soldFilter === 'removed';
  const base = removedView ? accounts().filter(a => REMOVED[a.name]) : visible();
  const rows = base
    .filter(a => !term || a.name.toLowerCase().includes(term) || (a.st||'').toLowerCase().includes(term) || (ABBR2NAME[a.st]||'').toLowerCase().includes(term))
    .filter(a => {
      if (removedView) return true;
      if (soldFilter === 'sold') return isSold(a);
      if (soldFilter === 'not')  return !isSold(a);
      if (soldFilter === 'nt')   return NT[a.name];
      return true; // all
    })
    .sort((a,b) => SORTERS[sortKey](a,b) * sortDir);
  const num = v => v == null ? '<span style="color:var(--text-muted)">—</span>' : fmtN(v);
  const body = rows.map((a,i) => {
    const sold = isSold(a), isNT = !!NT[a.name], hasSales = a.salesVol != null && a.salesVol > 0;
    const badges = (sold ? `<span class="im-badge">✓ sold${hasSales && !SOLD[a.name] ? ' (sales)' : ''}</span>` : '')
                 + (isNT ? '<span class="im-badge im-ntb">⊘ not a target</span>' : '');
    // Name cell: impound rows are editable (rename → merge to a selling customer).
    let nameCell;
    if (!removedView && editing && editing === a.impoundSrc) {
      nameCell = `<input class="im-editbox" list="im-sellerlist" value="${esc(a.name)}" data-editing="${esc(a.impoundSrc)}"> `
               + `<button class="im-act im-savealias" data-save="${esc(a.impoundSrc)}">save</button> `
               + `<button class="im-act" data-canceledit="1">cancel</button>`;
    } else {
      const label = (!removedView && a.impoundSrc)
        ? `<a class="im-edit" data-edit="${esc(a.impoundSrc)}" title="Click to rename / merge to a selling customer">${esc(a.name)}</a>`
        : `<span class="im-name">${esc(a.name)}</span>`;
      nameCell = label + badges;
    }
    let action;
    if (removedView) action = `<button class="im-act im-restore" data-restore="${esc(a.name)}">↩ restore</button>`;
    else {
      const soldBtn = hasSales
        ? `<button class="im-act im-soldbtn on" title="Sold — has marketplace sales" disabled>✓ sold</button>`
        : `<button class="im-act im-soldbtn ${SOLD[a.name]?'on':''}" data-sold="${esc(a.name)}" title="Toggle already sold">✓ sold</button>`;
      action = `${soldBtn} <button class="im-act im-nt ${isNT?'on':''}" data-nt="${esc(a.name)}" title="Toggle 'not a target'">⊘</button> <button class="im-act" data-remove="${esc(a.name)}" title="Remove from list">✕</button>`;
    }
    const memo = MEMOS[a.name] || '';
    let memoCell;
    if (!removedView && editingMemo === a.name) {
      memoCell = `<input class="im-memobox" value="${esc(memo)}" data-memoname="${esc(a.name)}" maxlength="280"> <button class="im-act im-savememo" data-savememo="${esc(a.name)}">save</button>`;
    } else {
      memoCell = memo
        ? `<span class="im-memo" data-memo="${esc(a.name)}" title="Click to edit">${esc(memo)}</span>`
        : `<span class="im-memo im-memo-empty" data-memo="${esc(a.name)}" title="Click to add a note">+ note</span>`;
    }
    const rowClass = removedView ? '' : (sold ? 'im-sold' : (isNT ? 'im-nt-row' : ''));
    return `<tr class="${rowClass}">
    <td class="cr-rank" style="color:var(--text-muted)">${i+1}</td>
    <td>${nameCell}</td>
    <td>${num(a.impoundVol)}</td>
    <td>${num(a.salesVol)}</td>
    <td>${a.st ? esc((a.city ? a.city + ', ' : '') + (ABBR2NAME[a.st]||a.st)) : '<span style="color:var(--text-muted)">— unknown —</span>'}</td>
    <td style="white-space:normal;max-width:260px">${memoCell}</td>
    <td style="text-align:right;white-space:nowrap">${action}</td>
  </tr>`;
  }).join('');
  const arr = k => sortKey === k ? (sortDir > 0 ? ' ▲' : ' ▼') : '';
  const th  = (k, label, extra = '') => `<th class="im-sort" data-sort="${k}" ${extra}>${label}${arr(k)}</th>`;
  const head = `<tr><th style="text-align:right">#</th>${th('name','Account')}${th('imp','LTM Auction Vol')}${th('sales','Sales Volume')}${th('loc','Location')}<th>Memo</th><th></th></tr>`;
  document.getElementById('im-table').innerHTML = `
    <datalist id="im-sellerlist">${SELLER_NAMES.map(n => `<option value="${esc(n)}">`).join('')}</datalist>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead>${head}</thead>
      <tbody>${body || `<tr><td colspan="7" class="cr-empty">${removedView ? 'Nothing removed.' : 'No accounts match.'}</td></tr>`}</tbody>
    </table></div>`;
  const remCount = accounts().filter(a => REMOVED[a.name]).length;
  document.getElementById('im-count').textContent = removedView
    ? `${fmtN(rows.length)} removed`
    : `${fmtN(rows.length)} shown · ${fmtN(rows.filter(isSold).length)} sold · ${fmtN(remCount)} removed (hidden)`;
  if (editing) { const inp = document.querySelector('.im-editbox'); if (inp) { inp.focus(); inp.select(); } }
  else if (editingMemo) { const inp = document.querySelector('.im-memobox'); if (inp) { inp.focus(); inp.select(); } }
}

function render() { renderKpis(); renderMap(); renderTable(); }

function postToggle(field, name, after) {
  fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: field + '=' + encodeURIComponent(name) })
    .then(r => r.json()).then(d => { if (d && d.ok) after(d); }).catch(() => {});
}
function toggleSold(name)    { postToggle('toggle_sold', name, d => { if (d.sold) SOLD[name] = true; else delete SOLD[name]; renderTable(); renderKpis(); }); }
function toggleRemoved(name) { postToggle('toggle_removed', name, d => { if (d.removed) REMOVED[name] = true; else delete REMOVED[name]; render(); }); }
function toggleNT(name)      { postToggle('toggle_nt', name, d => { if (d.nt) NT[name] = true; else delete NT[name]; renderTable(); renderKpis(); }); }

function setAlias(from, to) {
  fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'alias_from=' + encodeURIComponent(from) + '&alias_to=' + encodeURIComponent(to) })
    .then(r => r.json()).then(d => { if (d && d.ok) { if (to && to !== from) ALIASES[from] = to; else delete ALIASES[from]; editing = null; render(); } }).catch(() => {});
}
function commitEdit() { const inp = document.querySelector('.im-editbox'); if (!inp) { editing = null; return; } setAlias(inp.dataset.editing, inp.value.trim()); }
function setMemo(name, text) {
  fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'memo_name=' + encodeURIComponent(name) + '&memo_text=' + encodeURIComponent(text) })
    .then(r => r.json()).then(d => { if (d && d.ok) { if (text) MEMOS[name] = text; else delete MEMOS[name]; editingMemo = null; renderTable(); } }).catch(() => {});
}
function commitMemo() { const inp = document.querySelector('.im-memobox'); if (!inp) { editingMemo = null; return; } setMemo(inp.dataset.memoname, inp.value.trim()); }

document.getElementById('im-search').addEventListener('input', e => { term = e.target.value.trim().toLowerCase(); renderTable(); });
document.getElementById('im-soldfilter').addEventListener('change', e => { soldFilter = e.target.value; renderTable(); });
document.getElementById('im-showimp').addEventListener('change', e => { showImp = e.target.checked; render(); });
document.getElementById('im-showsel').addEventListener('change', e => { showSel = e.target.checked; render(); });
document.addEventListener('click', e => {
  const sh = e.target.closest('th.im-sort');
  if (sh) { const k = sh.dataset.sort; if (sortKey === k) sortDir = -sortDir; else { sortKey = k; sortDir = (k === 'imp' || k === 'sales') ? -1 : 1; } renderTable(); return; }
  const sv = e.target.closest('[data-save]');     if (sv) { commitEdit(); return; }
  if (e.target.closest('[data-canceledit]'))      { editing = null; renderTable(); return; }
  const ed = e.target.closest('.im-edit[data-edit]'); if (ed) { editing = ed.dataset.edit; editingMemo = null; renderTable(); return; }
  const mv = e.target.closest('[data-savememo]');  if (mv) { commitMemo(); return; }
  const me = e.target.closest('.im-memo[data-memo]'); if (me) { editingMemo = me.dataset.memo; editing = null; renderTable(); return; }
  const sd = e.target.closest('[data-sold]');     if (sd) { toggleSold(sd.dataset.sold); return; }
  const rm = e.target.closest('[data-remove]');   if (rm) { toggleRemoved(rm.dataset.remove); return; }
  const rs = e.target.closest('[data-restore]');  if (rs) { toggleRemoved(rs.dataset.restore); return; }
  const nt = e.target.closest('[data-nt]');       if (nt) { toggleNT(nt.dataset.nt); return; }
  const cell = e.target.closest('path[data-ab]');
  if (cell) { const ab = cell.dataset.ab; term = (ABBR2NAME[ab]||ab).toLowerCase(); document.getElementById('im-search').value = ABBR2NAME[ab]||ab; renderTable(); }
});
document.addEventListener('keydown', e => {
  if (editing) {
    if (e.key === 'Enter') { e.preventDefault(); commitEdit(); }
    else if (e.key === 'Escape') { editing = null; renderTable(); }
  } else if (editingMemo) {
    if (e.key === 'Enter') { e.preventDefault(); commitMemo(); }
    else if (e.key === 'Escape') { editingMemo = null; renderTable(); }
  }
});
render();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
