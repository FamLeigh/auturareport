<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login (like the rest of the site)
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_data.php';

// ── Secondary 4-digit access code, then server-side aggregation ───────────────
list($cr_ok, $cr_error) = amr_customer_gate();

$amr_meta      = file_exists(__DIR__ . '/data/amr-meta.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-meta.json'), true) : [];
$amr_data_date = $amr_meta['data_date'] ?? '';
$cr_payload    = $cr_ok ? amr_customer_payload() : '{"months":[],"customers":[],"dataDate":""}';

$page_title = 'Seller-Results';
$meta_desc  = 'Seller-Results — first auction, sold by month, and potential churn.';
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
    <h1>Seller-Results</h1>
    <p class="cr-sub">
      <?php if ($amr_data_date): ?>Dataset: <strong><?= h($amr_data_date) ?></strong> &nbsp;&middot;&nbsp; <?php endif; ?>
      Seller-level activity: first auction, sold by month, 90-day activity, and potential churn.
    </p>
  </section>

<?php if (!$cr_ok): ?>

  <form class="cr-gate" method="POST" autocomplete="off">
    <h2>Access code required</h2>
    <p>Enter the 4-digit code to view Seller-Results.</p>
    <?php if ($cr_error): ?><div class="err"><?= h($cr_error) ?></div><?php endif; ?>
    <input class="cr-code-input" type="password" name="cr_code" inputmode="numeric" pattern="[0-9]*"
           maxlength="4" placeholder="••••" autocomplete="off" autofocus required>
    <button type="submit">Unlock</button>
  </form>

<?php else: ?>

  <div class="cr-tabs">
    <button class="cr-tab active" data-tab="first">First Action</button>
    <button class="cr-tab" data-tab="sold">Sold by Month</button>
    <button class="cr-tab" data-tab="ninety">90-Day Activity</button>
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
  <div class="cr-panel" id="cr-panel-ninety"></div>
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
const addM = (ym,n) => { let [y,m] = ym.split('-').map(Number); m += n; while(m>12){m-=12;y++;} while(m<=0){m+=12;y--;} return `${y}-${String(m).padStart(2,'0')}`; };

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

// ── 90-Day Activity (new customers since Jul 1 2025; sales in first 3 months) ──
const NINETY_START = '2025-07';
function renderNinety() {
  const cohort = CR.customers
    .filter(c => c.first >= NINETY_START && (!term || c.name.toLowerCase().includes(term)))
    .map(c => {
      const m1 = c.first, m2 = addM(c.first,1), m3 = addM(c.first,2);
      const v1 = c.counts[m1]||0, v2 = c.counts[m2]||0, v3 = c.counts[m3]||0;
      const observed = [m1,m2,m3].filter(m => m <= LATEST).length;
      return { ...c, v1, v2, v3, t90: v1+v2+v3, observed };
    })
    .sort((a,b) => b.t90 - a.t90 || a.first.localeCompare(b.first));
  let anyInc = false;
  const body = cohort.map(c => {
    const inc = c.observed < 3; if (inc) anyInc = true;
    return `<tr>
      <td>${esc(c.name)}${inc ? ' <span class="cr-badge warn" title="First-90-day window not fully elapsed yet">in progress</span>' : ''}</td>
      <td>${mlbl(c.first)}</td>
      <td>${fmtN(c.v1)}</td>
      <td>${c.observed >= 2 ? fmtN(c.v2) : '·'}</td>
      <td>${c.observed >= 3 ? fmtN(c.v3) : '·'}</td>
      <td><strong>${fmtN(c.t90)}</strong></td>
    </tr>`;
  }).join('');
  const tot = cohort.reduce((a,c) => { a.v1+=c.v1; a.v2+=c.v2; a.v3+=c.v3; a.t+=c.t90; return a; }, {v1:0,v2:0,v3:0,t:0});
  const foot = cohort.length ? `<tfoot><tr><td>All shown</td><td></td><td>${fmtN(tot.v1)}</td><td>${fmtN(tot.v2)}</td><td>${fmtN(tot.v3)}</td><td>${fmtN(tot.t)}</td></tr></tfoot>` : '';
  document.getElementById('cr-panel-ninety').innerHTML = `
    <p class="cr-sub" style="margin:0 0 12px;">New customers who first ran on/after <strong>${mlbl(NINETY_START)}</strong> — cars sold in their first three months.</p>
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Customer</th><th>First Auction</th><th>Month 1</th><th>Month 2</th><th>Month 3</th><th>First 90 Days</th></tr></thead>
      <tbody>${body || `<tr><td colspan="6" class="cr-empty">No customers started on/after ${mlbl(NINETY_START)}${term?' for this search':''}.</td></tr>`}</tbody>
      ${foot}
    </table></div>
    ${anyInc ? `<p class="cr-sub" style="margin-top:10px;">“In progress” = the first-90-day window hasn’t fully elapsed (extends past ${esc(CR.dataDate || mlbl(LATEST))}); Month 2 / Month 3 show “·” where there’s no data yet.</p>` : ''}`;
  return cohort.length;
}

function render() {
  // The start-quarter filter only applies to First Action / Sold by Month / Churn.
  const qSel = document.getElementById('cr-quarter');
  if (qSel) qSel.style.display = (activeTab === 'ninety') ? 'none' : '';
  let n = 0;
  if (activeTab === 'first') n = renderFirst();
  else if (activeTab === 'sold') n = renderSold();
  else if (activeTab === 'ninety') n = renderNinety();
  else n = renderChurn();
  const noun = activeTab === 'churn' ? 'at-risk customer' : (activeTab === 'ninety' ? 'new customer' : 'customer');
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
