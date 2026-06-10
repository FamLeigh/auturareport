<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login (like the rest of the site)
require_once __DIR__ . '/includes/functions.php';

// ── Secondary 4-digit access code for this report ─────────────────────────────
const CR_ACCESS_CODE = '2862';
$cr_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cr_code'])) {
    if (preg_replace('/\D/', '', $_POST['cr_code']) === CR_ACCESS_CODE) {
        $_SESSION['cr_auth'] = true;
        header('Location: /customer-results');
        exit;
    }
    $cr_error = 'Incorrect code. Please try again.';
}
$cr_ok = !empty($_SESSION['cr_auth']);

$page_title = 'Customer Results';
$meta_desc  = 'Customer Results — first auction, sold by month, and potential churn.';
$body_class = 'page-customer';
$canonical  = '/customer-results';

// ── Server-side aggregation (only once the code is accepted) ──────────────────
$amr_meta      = file_exists(__DIR__ . '/data/amr-meta.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-meta.json'), true) : [];
$amr_data_date = $amr_meta['data_date'] ?? '';
$cr_payload    = '{"months":[],"customers":[],"dataDate":""}';

if ($cr_ok) {
    $data = file_exists(__DIR__ . '/data/amr-data.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-data.json'), true) : null;
    if ($data && !empty($data['records'])) {
        $S = $data['sellers'] ?? [];
        $M = $data['months']  ?? [];
        $agg = [];                                  // sellerIdx => [monthStr => count]
        $monthSet = [];
        foreach ($data['records'] as $r) {
            $si = $r[9] ?? -1; $mi = $r[8] ?? -1;
            if ($si < 0 || $mi < 0) continue;
            $m = $M[$mi];
            $agg[$si][$m] = ($agg[$si][$m] ?? 0) + 1;
            $monthSet[$m] = true;
        }
        $months = array_keys($monthSet);
        sort($months);

        $customers = [];
        foreach ($agg as $si => $counts) {
            ksort($counts);
            $active = array_keys($counts);
            $customers[] = [
                'name'   => (string) ($S[$si] ?? 'Unknown'),
                'first'  => $active[0],
                'last'   => end($active),
                'total'  => array_sum($counts),
                'active' => count($counts),
                'counts' => $counts,
            ];
        }
        $cr_payload = json_encode(
            ['months' => $months, 'customers' => $customers, 'dataDate' => $amr_data_date],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ) ?: '{"months":[],"customers":[],"dataDate":""}';
    }
}

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.cr-hero { padding: 64px 0 20px; }
.cr-hero h1 { font-size: clamp(1.7rem,4vw,2.4rem); margin-bottom: 6px; }
.cr-sub { font-size: 13px; color: var(--text-muted); }

/* code gate */
.cr-gate { max-width: 380px; margin: 40px auto 80px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 32px 30px; text-align: center; }
.cr-gate h2 { font-size: 1.05rem; margin-bottom: 6px; }
.cr-gate p { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }
.cr-code-input { width: 100%; text-align: center; letter-spacing: .5em; font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; color: var(--text); padding: 14px; margin-bottom: 16px; }
.cr-code-input:focus { outline: none; border-color: var(--accent); }
.cr-gate button { width: 100%; background: var(--accent); border: none; border-radius: 8px; color: #000; font-size: 14px; font-weight: 700; padding: 13px; cursor: pointer; }
.cr-gate button:hover { opacity: .88; }
.cr-gate .err { font-size: 13px; color: #c0392b; background: rgba(192,57,43,.08); border: 1px solid rgba(192,57,43,.2); border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; }

/* tabs */
.cr-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin: 22px 0 0; flex-wrap: wrap; }
.cr-tab { background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-muted); font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 11px 16px; cursor: pointer; margin-bottom: -1px; white-space: nowrap; }
.cr-tab:hover { color: var(--text); }
.cr-tab.active { color: var(--accent); border-bottom-color: var(--accent); }

.cr-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 18px 0 12px; flex-wrap: wrap; }
.cr-count { font-size: 13px; color: var(--text-muted); }
.cr-search { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 13px; padding: 8px 12px; min-width: 240px; }
.cr-search:focus { outline: none; border-color: var(--accent); }

.cr-panel { display: none; }
.cr-panel.active { display: block; }

/* tables */
.cr-scroll { overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--surface); }
.cr-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.cr-tbl th { position: sticky; top: 0; background: var(--surface); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted); padding: 12px 14px; text-align: right; border-bottom: 1px solid var(--border); white-space: nowrap; }
.cr-tbl th:first-child { text-align: left; }
.cr-tbl td { padding: 9px 14px; border-bottom: 1px solid rgba(0,0,0,.05); text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
[data-theme="dark"] .cr-tbl td { border-bottom-color: rgba(255,255,255,.05); }
.cr-tbl td:first-child { text-align: left; font-weight: 500; position: sticky; left: 0; background: var(--surface); }
.cr-tbl tr:hover td { background: var(--surface-2); }
.cr-tbl tr:hover td:first-child { background: var(--surface-2); }
.cr-tbl .muted { color: var(--text-muted); }
.cr-tbl .zero { color: var(--border); }
.cr-tbl tfoot td { font-weight: 700; border-top: 2px solid var(--border); border-bottom: none; background: var(--surface-2); }
.cr-tbl tfoot td:first-child { background: var(--surface-2); }

.cr-badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
.cr-badge.warn { background: rgba(240,165,0,.14); color: var(--accent); }
.cr-badge.bad  { background: rgba(192,57,43,.12); color: #c0392b; }
[data-theme="dark"] .cr-badge.bad { color: #e05a5a; }

.cr-empty { padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px; }
</style>';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <section class="cr-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/" style="color:var(--text-muted);text-decoration:none;">&larr; Valuation Tool</a>
    </p>
    <h1>Customer Results</h1>
    <p class="cr-sub">
      <?php if ($amr_data_date): ?>Dataset: <strong><?= h($amr_data_date) ?></strong> &nbsp;&middot;&nbsp; <?php endif; ?>
      Seller-level activity: first auction, sold by month, and potential churn.
    </p>
  </section>

<?php if (!$cr_ok): ?>

  <form class="cr-gate" method="POST" autocomplete="off">
    <h2>Access code required</h2>
    <p>Enter the 4-digit code to view Customer Results.</p>
    <?php if ($cr_error): ?><div class="err"><?= h($cr_error) ?></div><?php endif; ?>
    <input class="cr-code-input" type="text" name="cr_code" inputmode="numeric" pattern="[0-9]*"
           maxlength="4" placeholder="••••" autofocus required>
    <button type="submit">Unlock</button>
  </form>

<?php else: ?>

  <div class="cr-tabs">
    <button class="cr-tab active" data-tab="first">First Action</button>
    <button class="cr-tab" data-tab="sold">Sold by Month</button>
    <button class="cr-tab" data-tab="churn">Potential Churn</button>
  </div>

  <div class="cr-toolbar">
    <span class="cr-count" id="cr-count"></span>
    <input class="cr-search" id="cr-search" type="text" placeholder="Search customer…">
  </div>

  <div class="cr-panel active" id="cr-panel-first"></div>
  <div class="cr-panel" id="cr-panel-sold"></div>
  <div class="cr-panel" id="cr-panel-churn"></div>

<?php endif; ?>
</div>

<?php if ($cr_ok): ?>
<script>
const CR = <?= $cr_payload ?>;
const MONNAMES = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const mlbl = ym => ym ? MONNAMES[+ym.split('-')[1]] + ' ' + ym.split('-')[0] : '—';
const fmtN = n => Number(n||0).toLocaleString();
const esc  = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const monIdx = ym => { const [y,m] = ym.split('-').map(Number); return y*12 + (m-1); };
const monDiff = (a,b) => monIdx(a) - monIdx(b);

const MONTHS  = CR.months;
const LATEST  = MONTHS[MONTHS.length - 1] || '';
const CHURN_THRESHOLD = 3; // months with no auctions

let activeTab = 'first';
let term = '';

const matches = c => !term || c.name.toLowerCase().includes(term);

// ── First Action ────────────────────────────────────────────────────────────
function renderFirst() {
  const rows = CR.customers.filter(matches).sort((a,b) => monIdx(a.first) - monIdx(b.first) || b.total - a.total);
  const body = rows.map(c => `<tr>
    <td>${esc(c.name)}</td>
    <td>${mlbl(c.first)}</td>
    <td>${mlbl(c.last)}</td>
    <td>${fmtN(c.active)}</td>
    <td>${fmtN(c.total)}</td>
  </tr>`).join('');
  document.getElementById('cr-panel-first').innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Customer</th><th>First Auction</th><th>Most Recent</th><th>Months Active</th><th>Total Cars</th></tr></thead>
      <tbody>${body || `<tr><td colspan="5" class="cr-empty">No customers match.</td></tr>`}</tbody>
    </table></div>`;
  return rows.length;
}

// ── Sold by Month (customer × month matrix) ─────────────────────────────────
function renderSold() {
  const rows = CR.customers.filter(matches).sort((a,b) => b.total - a.total);
  const monthTotals = MONTHS.map(() => 0);
  const head = `<tr><th>Customer</th>${MONTHS.map(m => `<th>${mlbl(m)}</th>`).join('')}<th>Total</th></tr>`;
  const body = rows.map(c => {
    const cells = MONTHS.map((m,i) => {
      const n = c.counts[m] || 0;
      monthTotals[i] += n;
      return `<td class="${n?'':'zero'}">${n ? fmtN(n) : '·'}</td>`;
    }).join('');
    return `<tr><td>${esc(c.name)}</td>${cells}<td><strong>${fmtN(c.total)}</strong></td></tr>`;
  }).join('');
  const grand = monthTotals.reduce((a,b)=>a+b,0);
  const foot = `<tr><td>All shown</td>${monthTotals.map(t => `<td>${fmtN(t)}</td>`).join('')}<td>${fmtN(grand)}</td></tr>`;
  document.getElementById('cr-panel-sold').innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead>${head}</thead>
      <tbody>${body || `<tr><td colspan="${MONTHS.length+2}" class="cr-empty">No customers match.</td></tr>`}</tbody>
      ${rows.length ? `<tfoot>${foot}</tfoot>` : ''}
    </table></div>`;
  return rows.length;
}

// ── Potential Churn (no auctions in 3+ months) ──────────────────────────────
function renderChurn() {
  const churned = CR.customers
    .map(c => ({ ...c, since: LATEST ? monDiff(LATEST, c.last) : 0 }))
    .filter(c => c.since >= CHURN_THRESHOLD)
    .filter(matches)
    .sort((a,b) => b.since - a.since || b.total - a.total);
  const body = churned.map(c => {
    const sev = c.since >= 6 ? 'bad' : 'warn';
    return `<tr>
      <td>${esc(c.name)}</td>
      <td>${mlbl(c.last)}</td>
      <td><span class="cr-badge ${sev}">${c.since} mo</span></td>
      <td>${fmtN(c.total)}</td>
      <td>${mlbl(c.first)}</td>
    </tr>`;
  }).join('');
  document.getElementById('cr-panel-churn').innerHTML = `
    <p class="cr-sub" style="margin:0 0 12px;">Customers with no auctions for <strong>${CHURN_THRESHOLD}+ months</strong> as of <strong>${mlbl(LATEST)}</strong>. Most-lapsed first.</p>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Customer</th><th>Last Auction</th><th>Months Since</th><th>Total Cars</th><th>First Auction</th></tr></thead>
      <tbody>${body || `<tr><td colspan="5" class="cr-empty">No customers are 3+ months inactive${term?' for this search':''}.</td></tr>`}</tbody>
    </table></div>`;
  return churned.length;
}

function render() {
  let n = 0;
  if (activeTab === 'first') n = renderFirst();
  else if (activeTab === 'sold') n = renderSold();
  else n = renderChurn();
  const noun = activeTab === 'churn' ? 'at-risk customer' : 'customer';
  document.getElementById('cr-count').textContent = `${fmtN(n)} ${noun}${n===1?'':'s'}`;
}

document.querySelectorAll('.cr-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.cr-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.cr-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    activeTab = btn.dataset.tab;
    document.getElementById('cr-panel-' + activeTab).classList.add('active');
    render();
  });
});
document.getElementById('cr-search').addEventListener('input', e => {
  term = e.target.value.trim().toLowerCase();
  render();
});

render();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
