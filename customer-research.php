<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_data.php';

// Require the customer-data access code (shared with Seller-Results).
list($cr_ok, $cr_error) = amr_customer_gate();
if (!$cr_ok) {
    $page_title = 'Customer Research';
    $body_class = 'page-research';
    $extra_head = '<meta name="robots" content="noindex, nofollow">';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
      <section style="padding:calc(var(--nav-h) + 24px) 0 8px;"><h1 style="font-size:clamp(1.7rem,4vw,2.4rem);">Customer Research</h1></section>
      <form class="cr-gate" method="POST" autocomplete="off">
        <h2>Access code required</h2>
        <p>Enter the access code to view Customer Research.</p>
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

// Hidden / local-only research view over the CRM contact export.
$page_title = 'Customer Research';
$meta_desc  = 'Customer Research (internal).';
$body_class = 'page-research';
$canonical  = '/customer-research';
$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.crx-hero { padding: 56px 0 14px; }
.crx-hero h1 { font-size: clamp(1.7rem,4vw,2.4rem); margin-bottom: 6px; }
.crx-kpis { display: grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap: 14px; margin: 20px 0; }
.crx-kpi { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px 20px; }
.crx-kpi b { font-size: 1.7rem; font-weight: 800; display: block; line-height: 1.1; }
.crx-kpi span { font-size: 12px; color: var(--text-muted); }
.crx-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
@media (max-width: 820px) { .crx-cols { grid-template-columns: 1fr; } }
.crx-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px 22px; }
.crx-card h3 { font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 14px; }
.crx-row { display: grid; grid-template-columns: 1fr auto; gap: 6px 10px; align-items: center; margin-bottom: 9px; font-size: 13px; }
.crx-row .crx-bar { grid-column: 1 / -1; height: 6px; background: var(--surface-2); border-radius: 4px; overflow: hidden; }
.crx-row .crx-fill { height: 100%; background: var(--accent); opacity: .7; border-radius: 4px; }
.crx-num { font-variant-numeric: tabular-nums; font-weight: 600; color: var(--text-muted); }
.crx-chip { display: inline-block; font-size: 11px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 4px; padding: 1px 6px; margin: 1px 2px; white-space: nowrap; }
.crx-note { font-size: 13px; color: var(--text-muted); background: rgba(240,165,0,.07); border-left: 3px solid var(--accent); border-radius: 0 var(--radius) var(--radius) 0; padding: 11px 15px; margin-top: 16px; line-height: 1.6; }
.crx-note b { color: var(--accent); }
.crx-acctlink { color: var(--accent); text-decoration: none; cursor: pointer; }
.crx-acctlink:hover { text-decoration: underline; }

/* drill-down modal */
.crx-modal { position: fixed; inset: 0; z-index: 200; display: none; align-items: flex-start; justify-content: center; background: rgba(0,0,0,.45); padding: 6vh 16px; overflow-y: auto; }
.crx-modal.open { display: flex; }
.crx-modal-box { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); width: 100%; max-width: 760px; box-shadow: 0 18px 50px rgba(0,0,0,.3); }
.crx-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; padding: 22px 24px 14px; border-bottom: 1px solid var(--border); }
.crx-modal-head h2 { font-size: 1.15rem; margin: 0 0 4px; }
.crx-modal-head .meta { font-size: 13px; color: var(--text-muted); }
.crx-x { background: none; border: none; color: var(--text-muted); font-size: 22px; line-height: 1; cursor: pointer; padding: 2px 6px; }
.crx-x:hover { color: var(--accent); }
.crx-modal-body { padding: 16px 24px 24px; }

/* tile-grid heat map */
.crx-maptools { display: flex; gap: 8px; align-items: center; margin: 6px 0 14px; flex-wrap: wrap; }
.crx-mbtn, .crx-vbtn { background: var(--surface); border: 1px solid var(--border); border-radius: 7px; color: var(--text-muted); font-size: 12px; font-weight: 600; padding: 6px 12px; cursor: pointer; }
.crx-mbtn.on, .crx-vbtn.on { border-color: var(--accent); color: var(--accent); }
.crx-geo { width: 100%; height: auto; max-width: 820px; display: block; }
.crx-geo path { cursor: pointer; transition: stroke .1s; fill: var(--surface-2); }
.crx-geo path:hover { stroke: var(--accent); stroke-width: 1.6; }
.crx-grid { display: grid; grid-template-columns: repeat(11, 1fr); gap: 5px; max-width: 760px; }
.crx-cell { aspect-ratio: 1; border-radius: 5px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 11px; color: var(--text); cursor: pointer; border: 1px solid var(--border); background: var(--surface-2); }
.crx-cell.empty { cursor: default; opacity: .35; }
.crx-cell .ab { font-weight: 700; font-size: 11px; }
.crx-cell .vl { font-size: 9px; opacity: .8; font-variant-numeric: tabular-nums; }
.crx-legend { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-muted); margin-top: 12px; }
.crx-legend i { width: 22px; height: 10px; border-radius: 2px; display: inline-block; }
</style>';

// Normalize a company name for matching CRM accounts ↔ AMR sellers.
function crx_norm(string $s): string {
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
    $s = preg_replace('/\b(llc|inc|co|corp|company|the|of|and)\b/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}
// Resolve a raw State/Province value to a US state abbreviation ('' if non-US / blank).
function crx_us_abbr($raw): string {
    $s = trim((string)$raw);
    if ($s === '') return '';
    if (isset(CRX_STATE_ABBR[strtolower($s)])) return CRX_STATE_ABBR[strtolower($s)];
    if (in_array(strtoupper($s), CRX_STATE_ABBR, true)) return strtoupper($s); // 2-letter code
    return '';
}
const CRX_STATE_ABBR = ['alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR','california'=>'CA','colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE','district of columbia'=>'DC','florida'=>'FL','georgia'=>'GA','hawaii'=>'HI','idaho'=>'ID','illinois'=>'IL','indiana'=>'IN','iowa'=>'IA','kansas'=>'KS','kentucky'=>'KY','louisiana'=>'LA','maine'=>'ME','maryland'=>'MD','massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS','missouri'=>'MO','montana'=>'MT','nebraska'=>'NE','nevada'=>'NV','new hampshire'=>'NH','new jersey'=>'NJ','new mexico'=>'NM','new york'=>'NY','north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH','oklahoma'=>'OK','oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI','south carolina'=>'SC','south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT','vermont'=>'VT','virginia'=>'VA','washington'=>'WA','west virginia'=>'WV','wisconsin'=>'WI','wyoming'=>'WY'];

// Build SVG path strings (keyed by state abbr) from a US-states GeoJSON: lower-48
// + DC projected into the main frame, with Alaska & Hawaii as insets.
function crx_state_paths(): array {
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
        $ab = CRX_STATE_ABBR[strtolower($f['properties']['name'] ?? '')] ?? null;
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
    if ($ak) $emit($ak, [12, 472, 185, 120], 0);   // clip Aleutians (lon > 0)
    if ($hi) $emit($hi, [215, 505, 120, 85]);
    return $paths;
}

// ── Parse CSV server-side → account-level aggregation ─────────────────────────
$csv = __DIR__ . '/assets/csvs/All customers all accounts.csv';
$payload = ['ok' => false];

if (is_readable($csv) && ($fh = fopen($csv, 'r'))) {
    $header = fgetcsv($fh, 0, ',', '"', '');
    $idx = array_flip(array_map('trim', $header ?: []));
    $col = fn($row, $name) => isset($idx[$name]) ? trim((string)($row[$idx[$name]] ?? '')) : '';

    $acc = [];
    $contacts = 0;
    while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
        $name = $col($row, 'Account Name');
        if ($name === '') $name = '(no account)';
        if (!isset($acc[$name])) $acc[$name] = ['city'=>'','state'=>'','owners'=>[],'contacts'=>0,'emails'=>0,'phones'=>0,'products'=>[],'people'=>[],'seen'=>[]];
        $a =& $acc[$name];

        // ── Account-level fields accumulate from every row (dedup-insensitive) ──
        if ($a['city']  === '') $a['city']  = $col($row,'City');
        if ($a['state'] === '') $a['state'] = $col($row,'State/Province (text only)');
        $o = $col($row,'Account Owner');
        if ($o !== '') $a['owners'][$o] = ($a['owners'][$o] ?? 0) + 1;
        foreach (explode(';', $col($row,'Products In Use')) as $p) {
            $p = trim($p);
            if ($p !== '') $a['products'][$p] = true;
        }

        // ── Contacts: dedupe within the account (email → name+phone → row) ──
        $fn = $col($row,'First Name'); $ln = $col($row,'Last Name');
        $email = $col($row,'Email'); $phone = $col($row,'Phone') ?: $col($row,'Mobile');
        $em = strtolower(trim($email)); $nm = strtolower(trim($fn . ' ' . $ln));
        $ph = preg_replace('/\D/', '', $phone);
        $key = $em !== '' ? 'e:' . $em : ($nm !== '' ? 'n:' . $nm . '|' . $ph : 'r:' . md5(implode('|', $row)));
        if (!isset($a['seen'][$key])) {
            $a['seen'][$key] = true;
            $contacts++;
            $a['contacts']++;
            $a['people'][] = ['n' => trim($fn . ' ' . $ln), 't' => $col($row,'Title'), 'e' => $email, 'p' => $phone];
            if ($email !== '') $a['emails']++;
            if ($phone !== '') $a['phones']++;
        }
        unset($a);
    }
    fclose($fh);

    // ── Cross-reference the AMR auction data: existing sellers + regional demand ──
    $amr = file_exists(__DIR__ . '/data/amr-data.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-data.json'), true) : null;
    $sellerNorm = [];
    $stateDemand = [];
    if ($amr) {
        foreach (($amr['sellers'] ?? []) as $s) $sellerNorm[crx_norm($s)] = true;
        $regs = $amr['regions'] ?? [];
        foreach (($amr['records'] ?? []) as $r) {
            $reg = $regs[$r[5]] ?? '';
            $st  = (strpos($reg, '-') !== false) ? substr($reg, strrpos($reg, '-') + 1) : '';
            if ($st !== '') $stateDemand[$st] = ($stateDemand[$st] ?? 0) + 1;
        }
    }
    $maxDemand = $stateDemand ? max($stateDemand) : 1;

    // Impound/lien/auction product weights — signals an account has vehicles to auction.
    $IMPW = ['Auction Simplified'=>40,'TowLien'=>22,'PPI'=>20,'ARIES Impound'=>20,'InTow'=>16,'TOPS'=>10,'Tracker'=>6];

    $accounts = [];
    foreach ($acc as $name => $a) {
        arsort($a['owners']);
        $prods = array_keys($a['products']);

        $isSeller = isset($sellerNorm[crx_norm($name)]);
        $sp = 0; foreach ($prods as $p) $sp += $IMPW[$p] ?? 0; $sp = min(40, $sp);
        $ct = $a['contacts'];
        $ss = $ct >= 25 ? 25 : ($ct >= 10 ? 20 : ($ct >= 5 ? 15 : ($ct >= 2 ? 10 : 5)));
        $st = crx_us_abbr($a['state']);                       // '' for non-US / blank
        $stateDisp = $st ? ucwords(array_search($st, CRX_STATE_ABBR, true)) : trim($a['state']);
        $dem  = $st ? ($stateDemand[$st] ?? 0) : 0;
        $sd   = $dem > 0 ? (int) round(25 * log(1 + $dem) / log(1 + $maxDemand)) : 0;

        $accounts[] = [
            'name'     => $name,
            'city'     => $a['city'],
            'state'    => $stateDisp,
            'st'       => $st,
            'owner'    => array_key_first($a['owners']) ?: '',
            'contacts' => $ct,
            'emails'   => $a['emails'],
            'products' => $prods,
            'people'   => $a['people'],
            'seller'   => $isSeller,
            'score'    => $sp + $ss + $sd,
            'sp'       => $sp, 'ss' => $ss, 'sd' => $sd,
        ];
    }
    $payload = ['ok' => true, 'contacts' => $contacts, 'accounts' => $accounts];
}

$cr_payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{"ok":false}';
$geo_paths  = json_encode(crx_state_paths(), JSON_UNESCAPED_SLASHES) ?: '{}';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <section class="crx-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/" style="color:var(--text-muted);text-decoration:none;">&larr; Valuation Tool</a>
    </p>
    <h1>Customer Research</h1>
    <p class="cr-sub" style="font-size:13px;color:var(--text-muted);">Internal CRM research — accounts, reps, geography, and product adoption. <em>Hidden / local only.</em></p>
  </section>

  <div class="cr-tabs">
    <button class="cr-tab active" data-tab="over">Overview</button>
    <button class="cr-tab" data-tab="accts">Accounts</button>
    <button class="cr-tab" data-tab="targets">Targets</button>
    <button class="cr-tab" data-tab="map">Map</button>
    <button class="cr-tab" data-tab="reps">By Rep</button>
    <button class="cr-tab" data-tab="states">By State</button>
    <button class="cr-tab" data-tab="prod">Products</button>
    <button class="cr-tab" data-tab="prodmap">Product Map</button>
  </div>

  <div class="cr-toolbar">
    <span class="cr-count" id="crx-count"></span>
    <input class="cr-search" id="crx-search" type="text" placeholder="Search…">
  </div>

  <div class="cr-panel active" id="crx-over"></div>
  <div class="cr-panel" id="crx-accts"></div>
  <div class="cr-panel" id="crx-targets"></div>
  <div class="cr-panel" id="crx-map"></div>
  <div class="cr-panel" id="crx-reps"></div>
  <div class="cr-panel" id="crx-states"></div>
  <div class="cr-panel" id="crx-prod"></div>
  <div class="cr-panel" id="crx-prodmap"></div>
</div>

<div class="crx-modal" id="crx-modal"><div class="crx-modal-box">
  <div class="crx-modal-head">
    <div id="crx-modal-title"></div>
    <button class="crx-x" id="crx-modal-x" aria-label="Close">&times;</button>
  </div>
  <div class="crx-modal-body" id="crx-modal-body"></div>
</div></div>

<script>
const CRX = <?= $cr_payload ?>;
const GEO = <?= $geo_paths ?>;
const fmtN = n => Number(n||0).toLocaleString();
const esc  = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const pct  = (n,d) => d ? (100*n/d).toFixed(1)+'%' : '—';

const ACC = (CRX.accounts || []);
const TOTAL_CONTACTS = CRX.contacts || 0;

// ── Derived aggregations ─────────────────────────────────────────────────────
function aggBy(keyFn) {
  const m = {};
  for (const a of ACC) {
    const k = keyFn(a) || '(none)';
    if (!m[k]) m[k] = { key:k, accounts:0, contacts:0 };
    m[k].accounts++; m[k].contacts += a.contacts;
  }
  return Object.values(m).sort((x,y) => y.contacts - x.contacts);
}
function aggProducts() {
  const m = {};
  for (const a of ACC) for (const p of a.products) {
    if (!m[p]) m[p] = { key:p, accounts:0, contacts:0 };
    m[p].accounts++; m[p].contacts += a.contacts;
  }
  return Object.values(m).sort((x,y) => y.accounts - x.accounts);
}
const OWNERS   = aggBy(a => a.owner);
const PRODUCTS = aggProducts();
const SINGLE_PRODUCT = ACC.filter(a => a.products.length === 1).length;
const NO_PRODUCT     = ACC.filter(a => a.products.length === 0).length;
const ACC_BY_NAME = Object.fromEntries(ACC.map(a => [a.name, a]));

// State name → abbreviation, and a roughly-geographic tile-grid layout [row,col].
const STATE_ABBR = {alabama:'AL',alaska:'AK',arizona:'AZ',arkansas:'AR',california:'CA',colorado:'CO',connecticut:'CT',delaware:'DE','district of columbia':'DC',florida:'FL',georgia:'GA',hawaii:'HI',idaho:'ID',illinois:'IL',indiana:'IN',iowa:'IA',kansas:'KS',kentucky:'KY',louisiana:'LA',maine:'ME',maryland:'MD',massachusetts:'MA',michigan:'MI',minnesota:'MN',mississippi:'MS',missouri:'MO',montana:'MT',nebraska:'NE',nevada:'NV','new hampshire':'NH','new jersey':'NJ','new mexico':'NM','new york':'NY','north carolina':'NC','north dakota':'ND',ohio:'OH',oklahoma:'OK',oregon:'OR',pennsylvania:'PA','rhode island':'RI','south carolina':'SC','south dakota':'SD',tennessee:'TN',texas:'TX',utah:'UT',vermont:'VT',virginia:'VA',washington:'WA','west virginia':'WV',wisconsin:'WI',wyoming:'WY'};
const STATE_GRID = {AK:[0,0],ME:[0,10],VT:[1,9],NH:[1,10],WA:[2,0],ID:[2,1],MT:[2,2],ND:[2,3],MN:[2,4],IL:[2,5],WI:[2,6],MI:[2,7],NY:[2,8],MA:[2,10],OR:[3,0],NV:[3,1],WY:[3,2],SD:[3,3],IA:[3,4],IN:[3,5],OH:[3,6],PA:[3,7],NJ:[3,8],CT:[3,9],RI:[3,10],CA:[4,0],UT:[4,1],CO:[4,2],NE:[4,3],MO:[4,4],KY:[4,5],WV:[4,6],VA:[4,7],MD:[4,8],DE:[4,9],AZ:[5,1],NM:[5,2],KS:[5,3],AR:[5,4],TN:[5,5],NC:[5,6],SC:[5,7],DC:[5,8],OK:[6,3],LA:[6,4],MS:[6,5],AL:[6,6],GA:[6,7],HI:[7,0],TX:[7,3],FL:[7,8]};
const abbrOf = name => STATE_ABBR[String(name||'').trim().toLowerCase()] || null;
const ABBR2NAME = {}; for (const [n,a] of Object.entries(STATE_ABBR)) ABBR2NAME[a] = n.replace(/\b\w/g, c => c.toUpperCase());
function aggStates() {
  const m = {}; const nonus = { key:'(non-US / no state)', accounts:0, contacts:0 };
  for (const a of ACC) {
    if (a.st) { if (!m[a.st]) m[a.st] = { key:ABBR2NAME[a.st]||a.st, accounts:0, contacts:0 }; m[a.st].accounts++; m[a.st].contacts += a.contacts; }
    else { nonus.accounts++; nonus.contacts += a.contacts; }
  }
  const arr = Object.values(m).sort((x,y) => y.contacts - x.contacts);
  if (nonus.accounts) arr.push(nonus);
  return arr;
}
const STATES = aggStates();
const US_STATE_COUNT = new Set(ACC.map(a => a.st).filter(s => s && s !== 'DC')).size; // 50 states, excl DC

// State metrics keyed by abbreviation (only mappable US states).
const ST_METRIC = {};
for (const a of ACC) {
  const ab = a.st;
  if (!ab) continue;
  if (!ST_METRIC[ab]) ST_METRIC[ab] = { accounts:0, contacts:0, target:0 };
  ST_METRIC[ab].accounts++; ST_METRIC[ab].contacts += a.contacts;
  if (!a.seller) ST_METRIC[ab].target += a.score;
}
const STRONG = 45;                                    // target-score threshold for "strong"
const SELLERS = ACC.filter(a => a.seller).length;

let activeTab = 'over';
let term = '';
let mapMetric = 'accounts';
let mapView = 'geo';
let inclSellers = false;
const has = (s) => !term || String(s||'').toLowerCase().includes(term);

// ── Views ────────────────────────────────────────────────────────────────────
function miniBars(rows, total, max) {
  const top = rows.slice(0, max);
  const peak = top.length ? top[0].accounts : 1;
  return top.map(r => `
    <div class="crx-row">
      <span>${esc(r.key)}</span>
      <span class="crx-num">${fmtN(r.accounts)} <span style="opacity:.6">(${pct(r.accounts,total)})</span></span>
      <div class="crx-bar"><div class="crx-fill" style="width:${Math.max(2,100*r.accounts/peak).toFixed(1)}%"></div></div>
    </div>`).join('');
}

function renderOverview() {
  const k = [
    [fmtN(TOTAL_CONTACTS), 'Contacts'],
    [fmtN(ACC.length), 'Accounts'],
    [fmtN(US_STATE_COUNT), 'US states'],
    [fmtN(OWNERS.length), 'Account owners'],
    [fmtN(PRODUCTS.length), 'Products in use'],
    [fmtN(ACC.filter(a => !a.seller && a.score >= STRONG).length), 'Strong targets (≥'+STRONG+')'],
  ];
  document.getElementById('crx-over').innerHTML = `
    <div class="crx-kpis">${k.map(([v,l]) => `<div class="crx-kpi"><b>${v}</b><span>${l}</span></div>`).join('')}</div>
    <div class="crx-cols">
      <div class="crx-card"><h3>Product adoption (accounts using)</h3>${miniBars(PRODUCTS, ACC.length, 14)}</div>
      <div>
        <div class="crx-card" style="margin-bottom:16px;"><h3>Top states</h3>${miniBars(STATES, ACC.length, 8)}</div>
        <div class="crx-card"><h3>Top reps (by accounts)</h3>${miniBars([...OWNERS].sort((a,b)=>b.accounts-a.accounts), ACC.length, 8)}</div>
      </div>
    </div>
    <div class="crx-note"><b>${fmtN(ACC.filter(a=>!a.seller&&a.score>=STRONG).length)}</b> strong Autura Marketplace targets (non-sellers scoring ≥${STRONG}) — see <b>Targets</b>. Of all accounts, <b>${fmtN(SELLERS)}</b> are already marketplace sellers (name-matched to the auction data). Cross-sell whitespace: ${fmtN(SINGLE_PRODUCT)} accounts use one product, ${fmtN(NO_PRODUCT)} none.</div>`;
  return null;
}

function renderAccounts() {
  const rows = ACC
    .filter(a => has(a.name) || has(a.city) || has(a.state) || has(a.owner) || a.products.some(has))
    .sort((x,y) => y.contacts - x.contacts);
  const body = rows.slice(0, 1500).map(a => {
    const chips = a.products.slice(0,6).map(p => `<span class="crx-chip">${esc(p)}</span>`).join('')
      + (a.products.length>6 ? ` <span class="crx-chip">+${a.products.length-6}</span>` : '');
    return `<tr>
      <td><a class="crx-acctlink" data-name="${esc(a.name)}">${esc(a.name)}</a></td>
      <td>${esc(a.city)}${a.city&&a.state?', ':''}${esc(a.state)}</td>
      <td>${esc(a.owner)}</td>
      <td>${fmtN(a.contacts)}</td>
      <td>${a.products.length}</td>
      <td style="text-align:left;white-space:normal;">${chips || '<span class="crx-num">—</span>'}</td>
    </tr>`;
  }).join('');
  const capped = rows.length > 1500 ? `<p class="cr-sub" style="margin:10px 0 0;">Showing first 1,500 of ${fmtN(rows.length)} — narrow with search.</p>` : '';
  document.getElementById('crx-accts').innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Account</th><th>Location</th><th>Owner</th><th>Contacts</th><th># Products</th><th>Products</th></tr></thead>
      <tbody>${body || `<tr><td colspan="6" class="cr-empty">No accounts match.</td></tr>`}</tbody>
    </table></div>${capped}`;
  return rows.length;
}

function renderDim(elId, rows, label) {
  const f = rows.filter(r => has(r.key));
  const body = f.map(r => `<tr>
    <td>${esc(r.key)}</td>
    <td>${fmtN(r.accounts)}</td>
    <td>${fmtN(r.contacts)}</td>
    <td>${pct(r.accounts, ACC.length)}</td>
  </tr>`).join('');
  document.getElementById(elId).innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>${label}</th><th>Accounts</th><th>Contacts</th><th>% of accounts</th></tr></thead>
      <tbody>${body || `<tr><td colspan="4" class="cr-empty">No ${label.toLowerCase()} match.</td></tr>`}</tbody>
    </table></div>`;
  return f.length;
}

function renderProducts() {
  const f = PRODUCTS.filter(r => has(r.key));
  const body = f.map(r => `<tr>
    <td>${esc(r.key)}</td>
    <td>${fmtN(r.accounts)}</td>
    <td>${pct(r.accounts, ACC.length)}</td>
    <td>${fmtN(r.contacts)}</td>
  </tr>`).join('');
  const note = `<div class="crx-note"><b>${fmtN(SINGLE_PRODUCT)}</b> accounts use exactly one product (cross-sell targets); <b>${fmtN(NO_PRODUCT)}</b> have none recorded.</div>`;
  document.getElementById('crx-prod').innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Product</th><th>Accounts using</th><th>% of accounts</th><th>Contacts (reach)</th></tr></thead>
      <tbody>${body || `<tr><td colspan="4" class="cr-empty">No products match.</td></tr>`}</tbody>
    </table></div>${note}`;
  return f.length;
}

function mapMax() { const v = Object.values(ST_METRIC).map(s => s[mapMetric]); return v.length ? Math.max(...v) : 1; }
function cellTitle(ab, m) { return `${ab}: ${fmtN(m.accounts)} accounts · ${fmtN(m.contacts)} contacts · target ${fmtN(m.target)}`; }

function gridHtml(max) {
  let cells = '';
  for (let r = 0; r < 8; r++) for (let c = 0; c < 11; c++) {
    const ab = Object.keys(STATE_GRID).find(k => STATE_GRID[k][0]===r && STATE_GRID[k][1]===c);
    if (!ab) { cells += `<div></div>`; continue; }
    const m = ST_METRIC[ab];
    if (!m) { cells += `<div class="crx-cell empty"><span class="ab">${ab}</span></div>`; continue; }
    const op = (0.12 + 0.88 * m[mapMetric] / max).toFixed(2);
    cells += `<div class="crx-cell" data-ab="${ab}" title="${cellTitle(ab,m)}" style="background:rgba(240,165,0,${op});border-color:rgba(240,165,0,${Math.min(1,+op+0.1)})"><span class="ab">${ab}</span><span class="vl">${fmtN(m[mapMetric])}</span></div>`;
  }
  return `<div class="crx-grid">${cells}</div>`;
}

function geoHtml(max) {
  let p = '';
  for (const ab in GEO) {
    const m = ST_METRIC[ab];
    const style = m ? ` style="fill:rgba(240,165,0,${(0.12 + 0.88 * m[mapMetric] / max).toFixed(2)})"` : '';
    p += `<path data-ab="${ab}" d="${GEO[ab]}"${style} stroke="var(--border)" stroke-width="0.7" fill-rule="evenodd"><title>${m ? cellTitle(ab,m) : ab+': no data'}</title></path>`;
  }
  return `<svg viewBox="0 0 960 600" class="crx-geo">${p}</svg>`;
}

function renderMap() {
  const max = mapMax();
  const geoReady = Object.keys(GEO).length > 0;
  const body = (mapView === 'geo' && geoReady) ? geoHtml(max) : gridHtml(max);
  document.getElementById('crx-map').innerHTML = `
    <div class="crx-maptools">
      <span class="cr-sub">Shade by:</span>
      <button class="crx-mbtn ${mapMetric==='accounts'?'on':''}" data-m="accounts">Accounts</button>
      <button class="crx-mbtn ${mapMetric==='contacts'?'on':''}" data-m="contacts">Contacts</button>
      <button class="crx-mbtn ${mapMetric==='target'?'on':''}" data-m="target">Target score</button>
      ${geoReady ? `<span style="width:12px"></span>
      <button class="crx-vbtn ${mapView==='geo'?'on':''}" data-v="geo">Geographic</button>
      <button class="crx-vbtn ${mapView==='grid'?'on':''}" data-v="grid">Grid</button>` : ''}
      <span class="cr-sub" style="margin-left:auto;">click a state to see its accounts</span>
    </div>
    ${body}
    <div class="crx-legend"><span>Low</span><i style="background:rgba(240,165,0,.18)"></i><i style="background:rgba(240,165,0,.5)"></i><i style="background:rgba(240,165,0,.9)"></i><span>High (${fmtN(max)})</span></div>
    <p class="cr-sub" style="margin-top:10px;">${mapView==='geo'&&geoReady ? 'Geographic choropleth — Alaska &amp; Hawaii shown as insets. ' : 'Tile grid (one square per state). '}Non-US / unmapped locations aren't shown — see By State.</p>`;
  return null;
}

function renderTargets() {
  const rows = ACC
    .filter(a => inclSellers || !a.seller)
    .filter(a => has(a.name) || has(a.city) || has(a.state) || has(a.owner) || a.products.some(has))
    .sort((x,y) => y.score - x.score || y.contacts - x.contacts);
  const body = rows.slice(0, 1500).map(a => `<tr>
    <td><a class="crx-acctlink" data-name="${esc(a.name)}">${esc(a.name)}</a>${a.seller ? ' <span class="cr-badge bad" title="Already an Autura Marketplace seller">seller</span>' : ''}</td>
    <td>${esc(a.state) || '—'}</td>
    <td><strong>${a.score}</strong></td>
    <td class="crx-num">${a.sp} / ${a.ss} / ${a.sd}</td>
    <td style="text-align:left;white-space:normal;">${a.products.slice(0,5).map(p => `<span class="crx-chip">${esc(p)}</span>`).join('') || '<span class="crx-num">—</span>'}</td>
    <td>${fmtN(a.contacts)}</td>
  </tr>`).join('');
  const toolbar = `<div class="crx-maptools"><label style="font-size:13px;color:var(--text-muted);display:flex;gap:7px;align-items:center;cursor:pointer;"><input type="checkbox" id="crx-inclsellers" ${inclSellers?'checked':''}> Include existing marketplace sellers (${fmtN(SELLERS)})</label></div>`;
  const topNote = `<div class="crx-note" style="margin:0 0 14px;"><b>Target score (0–90)</b> = the <b>P / S / D</b> column added up:<br>
    &nbsp;&nbsp;<b>P</b> — Product fit (0–40): impound / auction products in use (Auction Simplified, TowLien, PPI, ARIES Impound, InTow…)<br>
    &nbsp;&nbsp;<b>S</b> — Size (0–25): by number of contacts<br>
    &nbsp;&nbsp;<b>D</b> — Regional demand (0–25): AMR auction volume in the account's state<br>
    Higher = better Autura Marketplace target. Existing marketplace sellers are name-matched (${fmtN(SELLERS)} found) and hidden by default${rows.length>1500?'; showing the top 1,500 — narrow with search':''}.</div>`;
  document.getElementById('crx-targets').innerHTML = toolbar + topNote + `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Account</th><th>State</th><th>Target score</th><th title="Product fit / Size / Regional demand">P / S / D</th><th>Products</th><th>Contacts</th></tr></thead>
      <tbody>${body || `<tr><td colspan="6" class="cr-empty">No targets match.</td></tr>`}</tbody>
    </table></div>`;
  const cb = document.getElementById('crx-inclsellers');
  if (cb) cb.addEventListener('change', () => { inclSellers = cb.checked; render(); });
  return rows.length;
}

function openAccount(name) {
  const a = ACC_BY_NAME[name];
  if (!a) return;
  document.getElementById('crx-modal-title').innerHTML =
    `<h2>${esc(a.name)}</h2><div class="meta">${esc(a.city)}${a.city&&a.state?', ':''}${esc(a.state)} · Owner: ${esc(a.owner)||'—'} · ${fmtN(a.contacts)} contacts</div>`;
  const chips = a.products.length ? a.products.map(p => `<span class="crx-chip">${esc(p)}</span>`).join(' ') : '<span class="crx-num">No products recorded</span>';
  const people = (a.people||[]).slice().sort((x,y) => (x.n||'').localeCompare(y.n||''));
  const rows = people.map(p => `<tr>
    <td>${esc(p.n)||'—'}</td>
    <td style="white-space:normal;">${esc(p.t)||'—'}</td>
    <td style="text-align:left;">${p.e ? `<a class="crx-acctlink" href="mailto:${esc(p.e)}">${esc(p.e)}</a>` : '—'}</td>
    <td>${esc(p.p)||'—'}</td>
  </tr>`).join('');
  document.getElementById('crx-modal-body').innerHTML = `
    <div style="margin-bottom:14px;">${chips}</div>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Name</th><th>Title</th><th>Email</th><th>Phone</th></tr></thead>
      <tbody>${rows || `<tr><td colspan="4" class="cr-empty">No contacts.</td></tr>`}</tbody>
    </table></div>`;
  document.getElementById('crx-modal').classList.add('open');
}
function closeModal() { document.getElementById('crx-modal').classList.remove('open'); }

// ── Product Map: where a single product is geographically concentrated ──────────
let prodFocus = '';
function renderProdMap() {
  if (!prodFocus) prodFocus = (PRODUCTS[0] && PRODUCTS[0].key) || '';
  const using = ACC.filter(a => a.products.includes(prodFocus));
  const stAgg = {}, cityAgg = {};
  for (const a of using) {
    if (a.st) { (stAgg[a.st] ||= {accounts:0, contacts:0}); stAgg[a.st].accounts++; stAgg[a.st].contacts += a.contacts; }
    const k = (a.city || '(no city)') + '|' + (a.st || '');
    (cityAgg[k] ||= {city:a.city||'(no city)', st:a.st, accounts:0, contacts:0});
    cityAgg[k].accounts++; cityAgg[k].contacts += a.contacts;
  }
  const allCities = Object.values(cityAgg).sort((x,y) => y.accounts - x.accounts || y.contacts - x.contacts);
  const cities = allCities.filter(c => c.city !== '(no city)');           // real cities only for the ranking
  const noCityAccts = allCities.filter(c => c.city === '(no city)').reduce((s,c) => s + c.accounts, 0);
  const max = Math.max(1, ...Object.values(stAgg).map(s => s.accounts));
  let paths = '';
  for (const ab in GEO) {
    const m = stAgg[ab];
    const style = m ? ` style="fill:rgba(240,165,0,${(0.12 + 0.88 * m.accounts / max).toFixed(2)})"` : '';
    const title = m ? `${ABBR2NAME[ab]||ab}: ${fmtN(m.accounts)} accounts · ${fmtN(m.contacts)} contacts` : `${ABBR2NAME[ab]||ab}: none`;
    paths += `<path data-ab="${ab}" d="${GEO[ab]}"${style} stroke="var(--border)" stroke-width="0.7" fill-rule="evenodd"><title>${esc(title)}</title></path>`;
  }
  const opts = PRODUCTS.map(p => `<option value="${esc(p.key)}" ${p.key===prodFocus?'selected':''}>${esc(p.key)} — ${fmtN(p.accounts)} accts</option>`).join('');
  const totContacts = using.reduce((s,a) => s + a.contacts, 0);
  const cityRows = cities.slice(0, 250).map((c,i) => `<tr>
    <td class="cr-rank" style="color:var(--text-muted)">${i+1}</td>
    <td>${esc(c.city)}</td><td>${c.st ? esc(ABBR2NAME[c.st]||c.st) : '—'}</td>
    <td>${fmtN(c.accounts)}</td><td>${fmtN(c.contacts)}</td></tr>`).join('');
  document.getElementById('crx-prodmap').innerHTML = `
    <div class="crx-maptools">
      <span class="cr-sub">Product:</span>
      <select class="cr-search" id="crx-prodsel" style="min-width:240px;cursor:pointer;">${opts}</select>
      <span class="cr-sub" style="margin-left:auto;">${fmtN(using.length)} accounts · ${fmtN(totContacts)} contacts use <b>${esc(prodFocus)}</b></span>
    </div>
    <div class="crx-note" style="margin:0 0 12px;">Geographic concentration of <b>${esc(prodFocus)}</b> — the map is shaded by accounts using it per state; the table ranks cities by accounts (with contact reach).</div>
    <svg viewBox="0 0 960 600" class="crx-geo">${paths}</svg>
    <div class="crx-legend"><span>Low</span><i style="background:rgba(240,165,0,.18)"></i><i style="background:rgba(240,165,0,.5)"></i><i style="background:rgba(240,165,0,.9)"></i><span>High (${fmtN(max)} accounts)</span></div>
    <h3 style="margin:18px 0 8px;font-size:.72rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);">Top cities — ${esc(prodFocus)}${noCityAccts ? ` <span style="font-weight:400;text-transform:none;letter-spacing:0;">(${fmtN(noCityAccts)} accounts have no city recorded — see the map / By State)</span>` : ''}</h3>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th style="text-align:right">#</th><th>City</th><th>State</th><th>Accounts</th><th>Contacts (reach)</th></tr></thead>
      <tbody>${cityRows || `<tr><td colspan="5" class="cr-empty">No accounts use this product.</td></tr>`}</tbody>
    </table></div>`;
  const sel = document.getElementById('crx-prodsel');
  if (sel) sel.addEventListener('change', e => { prodFocus = e.target.value; renderProdMap(); });
  return cities.length;
}

function render() {
  let n = null;
  if (activeTab === 'map') return renderMap(), document.getElementById('crx-count').textContent = `${Object.keys(ST_METRIC).length} mapped states`, undefined;
  if (activeTab === 'prodmap') n = renderProdMap();
  if (activeTab === 'over')   n = renderOverview();
  else if (activeTab === 'accts')  n = renderAccounts();
  else if (activeTab === 'targets') n = renderTargets();
  else if (activeTab === 'reps')   n = renderDim('crx-reps', OWNERS, 'Account owner');
  else if (activeTab === 'states') n = renderDim('crx-states', STATES, 'State');
  else if (activeTab === 'prod')   n = renderProducts();
  document.getElementById('crx-count').textContent =
    activeTab === 'over' ? `${fmtN(TOTAL_CONTACTS)} contacts · ${fmtN(ACC.length)} accounts`
    : (n != null ? `${fmtN(n)} row${n===1?'':'s'}` : '');
}

if (!CRX.ok) {
  document.getElementById('crx-over').innerHTML = '<div class="cr-empty">Could not read the CSV (assets/csvs/All customers all accounts.csv).</div>';
} else {
  document.querySelectorAll('.cr-tab').forEach(btn => btn.addEventListener('click', () => {
    document.querySelectorAll('.cr-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.cr-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    activeTab = btn.dataset.tab;
    document.getElementById('crx-' + activeTab).classList.add('active');
    render();
  }));
  document.getElementById('crx-search').addEventListener('input', e => { term = e.target.value.trim().toLowerCase(); render(); });

  // Delegated clicks: account drill-down, map metric toggle, state → accounts.
  document.addEventListener('click', e => {
    const acct = e.target.closest('.crx-acctlink[data-name]');
    if (acct) { e.preventDefault(); openAccount(acct.dataset.name); return; }
    const mb = e.target.closest('.crx-mbtn');
    if (mb) { mapMetric = mb.dataset.m; renderMap(); return; }
    const vb = e.target.closest('.crx-vbtn');
    if (vb) { mapView = vb.dataset.v; renderMap(); return; }
    const cell = e.target.closest('[data-ab]');
    if (cell) {
      const ab = cell.dataset.ab;
      const disp = ABBR2NAME[ab] || ab;
      document.querySelectorAll('.cr-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === 'accts'));
      document.querySelectorAll('.cr-panel').forEach(p => p.classList.remove('active'));
      document.getElementById('crx-accts').classList.add('active');
      activeTab = 'accts'; term = disp.toLowerCase();
      document.getElementById('crx-search').value = disp;
      render();
    }
  });
  // Modal close
  document.getElementById('crx-modal-x').addEventListener('click', closeModal);
  document.getElementById('crx-modal').addEventListener('click', e => { if (e.target.id === 'crx-modal') closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  render();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
