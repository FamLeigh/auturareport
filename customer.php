<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login (like the rest of the site)
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_data.php';

// ── Secondary 4-digit access code, then server-side aggregation ───────────────
list($cr_ok, $cr_error) = amr_customer_gate();

$amr_meta      = file_exists(__DIR__ . '/data/amr-meta.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-meta.json'), true) : [];
$amr_data_date = $amr_meta['data_date'] ?? '';
$cr_payload    = $cr_ok ? amr_customer_payload() : '{"months":[],"customers":[],"dataDate":""}';

$page_title = 'Customer Results';
$meta_desc  = 'Customer Results — first auction, sold by month, and potential churn.';
$body_class = 'page-customer';
$canonical  = '/customer-results';
$extra_head = '<meta name="robots" content="noindex, nofollow">';

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
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <select class="cr-search" id="cr-quarter" style="min-width:180px;cursor:pointer;"></select>
      <input class="cr-search" id="cr-search" type="text" placeholder="Search customer…">
    </div>
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

// Quarter helpers — cohort = the quarter of a customer's FIRST auction.
const qNum   = ym => Math.floor((+ym.split('-')[1] - 1) / 3) + 1;
const qKey   = ym => `${ym.split('-')[0]}-Q${qNum(ym)}`;
const qLabel = k  => { const [y,q] = k.split('-'); return `${q} ${y}`; };
const qStart = k  => { const [y,q] = k.split('-'); return `${y}-${String((+q.slice(1)-1)*3+1).padStart(2,'0')}`; };

// Churn = 90+ days since last auction, measured from the data date (data is monthly,
// so we use the last calendar day of the last active month — the best case for them).
const CHURN_DAYS = 90;
function parseDate(s){ const m = s && s.match(/([A-Za-z]+)\s+(\d+),\s*(\d+)/); if(!m) return null; const mi = MONNAMES.indexOf(m[1].slice(0,3)); return mi < 1 ? null : new Date(+m[3], mi-1, +m[2]); }
const REF = parseDate(CR.dataDate);
function daysInactive(lastYM){
  const [y,mo] = lastYM.split('-').map(Number);
  const lastDay = new Date(y, mo, 0); // last day of that month
  return REF ? Math.round((REF - lastDay) / 86400000) : monDiff(LATEST, lastYM) * 30;
}

let activeTab = 'first';
let term = '';
let quarter = '';

const matches = c => (!term || c.name.toLowerCase().includes(term)) && (!quarter || qKey(c.first) === quarter);

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
  // When a start quarter is chosen, show months from that quarter forward.
  const cols = quarter ? MONTHS.filter(m => m >= qStart(quarter)) : MONTHS;
  const rows = CR.customers.filter(matches).sort((a,b) => b.total - a.total);
  const monthTotals = cols.map(() => 0);
  const head = `<tr><th>Customer</th>${cols.map(m => `<th>${mlbl(m)}</th>`).join('')}<th>Total</th></tr>`;
  const body = rows.map(c => {
    let rowTot = 0;
    const cells = cols.map((m,i) => {
      const n = c.counts[m] || 0;
      monthTotals[i] += n; rowTot += n;
      return `<td class="${n?'':'zero'}">${n ? fmtN(n) : '·'}</td>`;
    }).join('');
    return `<tr><td>${esc(c.name)}</td>${cells}<td><strong>${fmtN(rowTot)}</strong></td></tr>`;
  }).join('');
  const grand = monthTotals.reduce((a,b)=>a+b,0);
  const foot = `<tr><td>All shown</td>${monthTotals.map(t => `<td>${fmtN(t)}</td>`).join('')}<td>${fmtN(grand)}</td></tr>`;
  const note = quarter ? `<p class="cr-sub" style="margin:0 0 12px;">Cohort that first ran in <strong>${qLabel(quarter)}</strong>, shown from <strong>${mlbl(qStart(quarter))}</strong> forward.</p>` : '';
  document.getElementById('cr-panel-sold').innerHTML = note + `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead>${head}</thead>
      <tbody>${body || `<tr><td colspan="${cols.length+2}" class="cr-empty">No customers match.</td></tr>`}</tbody>
      ${rows.length ? `<tfoot>${foot}</tfoot>` : ''}
    </table></div>`;
  return rows.length;
}

// ── Potential Churn (no auctions in 3+ months) ──────────────────────────────
function renderChurn() {
  const churned = CR.customers
    .map(c => ({ ...c, days: daysInactive(c.last) }))
    .filter(c => c.days >= CHURN_DAYS)
    .filter(matches)
    .sort((a,b) => b.days - a.days || b.total - a.total);
  const body = churned.map(c => {
    const sev = c.days >= 180 ? 'bad' : 'warn';
    return `<tr>
      <td>${esc(c.name)}</td>
      <td>${mlbl(c.last)}</td>
      <td><span class="cr-badge ${sev}">${fmtN(c.days)} days</span></td>
      <td>${fmtN(c.total)}</td>
      <td>${mlbl(c.first)}</td>
    </tr>`;
  }).join('');
  document.getElementById('cr-panel-churn').innerHTML = `
    <p class="cr-sub" style="margin:0 0 12px;">Customers with no auctions for <strong>${CHURN_DAYS}+ days</strong> as of <strong>${esc(CR.dataDate || mlbl(LATEST))}</strong>. Most-lapsed first.</p>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Customer</th><th>Last Auction</th><th>Days Since</th><th>Total Cars</th><th>First Auction</th></tr></thead>
      <tbody>${body || `<tr><td colspan="5" class="cr-empty">No customers are ${CHURN_DAYS}+ days inactive${term||quarter?' for this filter':''}.</td></tr>`}</tbody>
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

// Start-quarter cohort filter (quarter of each customer's first auction)
const qSel = document.getElementById('cr-quarter');
const quarters = [...new Set(CR.customers.map(c => qKey(c.first)))].sort();
qSel.innerHTML = '<option value="">All start quarters</option>' +
  quarters.map(q => `<option value="${q}">First ran ${qLabel(q)}</option>`).join('');
qSel.addEventListener('change', e => { quarter = e.target.value; render(); });

render();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
