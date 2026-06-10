<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/customer_data.php';

list($cr_ok, $cr_error) = amr_customer_gate();       // shared 4-digit code (2862)

$amr_meta      = file_exists(__DIR__ . '/data/amr-meta.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-meta.json'), true) : [];
$amr_data_date = $amr_meta['data_date'] ?? '';
$cr_payload    = $cr_ok ? amr_customer_payload() : '{"months":[],"customers":[],"dataDate":""}';

$page_title = '90-Day Activity';
$meta_desc  = '90-Day Activity — new customers and sales in their first three months.';
$body_class = 'page-activity';
$canonical  = '/activity-90';
$extra_head = '<meta name="robots" content="noindex, nofollow">';

include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <section class="cr-hero">
    <p style="font-size:12px;margin-bottom:10px;">
      <a href="/customer-results" style="color:var(--text-muted);text-decoration:none;">&larr; Customer Results</a>
    </p>
    <h1>90-Day Activity</h1>
    <p class="cr-sub">
      <?php if ($amr_data_date): ?>Dataset: <strong><?= h($amr_data_date) ?></strong> &nbsp;&middot;&nbsp; <?php endif; ?>
      New customers who first ran on/after <strong>Jul 1, 2025</strong> — cars sold in their first three months.
    </p>
  </section>

<?php if (!$cr_ok): ?>

  <form class="cr-gate" method="POST" autocomplete="off">
    <h2>Access code required</h2>
    <p>Enter the 4-digit code to view this report.</p>
    <?php if ($cr_error): ?><div class="err"><?= h($cr_error) ?></div><?php endif; ?>
    <input class="cr-code-input" type="text" name="cr_code" inputmode="numeric" pattern="[0-9]*"
           maxlength="4" placeholder="••••" autofocus required>
    <button type="submit">Unlock</button>
  </form>

<?php else: ?>

  <div class="cr-toolbar">
    <span class="cr-count" id="cr-count"></span>
    <input class="cr-search" id="cr-search" type="text" placeholder="Search customer…">
  </div>
  <div id="cr-panel"></div>
  <p class="cr-sub" id="cr-foot" style="margin-top:12px;"></p>

<?php endif; ?>
</div>

<?php if ($cr_ok): ?>
<script>
const CR = <?= $cr_payload ?>;
const MONNAMES = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const mlbl = ym => ym ? MONNAMES[+ym.split('-')[1]] + ' ' + ym.split('-')[0] : '—';
const fmtN = n => Number(n||0).toLocaleString();
const esc  = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const addM = (ym,n) => { let [y,m] = ym.split('-').map(Number); m += n; while(m>12){m-=12;y++;} while(m<=0){m+=12;y--;} return `${y}-${String(m).padStart(2,'0')}`; };

const START  = '2025-07';                                  // customers who first ran on/after Jul 2025
const LATEST  = CR.months[CR.months.length - 1] || '';
let term = '';

function render() {
  const cohort = CR.customers
    .filter(c => c.first >= START && (!term || c.name.toLowerCase().includes(term)))
    .map(c => {
      const m1 = c.first, m2 = addM(c.first,1), m3 = addM(c.first,2);
      const v1 = c.counts[m1]||0, v2 = c.counts[m2]||0, v3 = c.counts[m3]||0;
      const observed = [m1,m2,m3].filter(m => m <= LATEST).length; // months we actually have data for
      return { ...c, m1, m2, m3, v1, v2, v3, t90: v1+v2+v3, observed };
    })
    .sort((a,b) => b.t90 - a.t90 || a.first.localeCompare(b.first));

  let anyIncomplete = false;
  const body = cohort.map(c => {
    const inc = c.observed < 3;
    if (inc) anyIncomplete = true;
    return `<tr>
      <td>${esc(c.name)}${inc ? ' <span class="cr-badge warn" title="First-90-day window not fully elapsed yet">in progress</span>' : ''}</td>
      <td>${mlbl(c.first)}</td>
      <td>${fmtN(c.v1)}</td>
      <td>${c.observed >= 2 ? fmtN(c.v2) : '·'}</td>
      <td>${c.observed >= 3 ? fmtN(c.v3) : '·'}</td>
      <td><strong>${fmtN(c.t90)}</strong></td>
    </tr>`;
  }).join('');

  const totals = cohort.reduce((a,c) => { a.v1+=c.v1; a.v2+=c.v2; a.v3+=c.v3; a.t+=c.t90; return a; }, {v1:0,v2:0,v3:0,t:0});
  const foot = cohort.length ? `<tfoot><tr><td>All shown</td><td></td><td>${fmtN(totals.v1)}</td><td>${fmtN(totals.v2)}</td><td>${fmtN(totals.v3)}</td><td>${fmtN(totals.t)}</td></tr></tfoot>` : '';

  document.getElementById('cr-panel').innerHTML = `
    <div class="cr-scroll"><table class="cr-tbl">
      <thead><tr><th>Customer</th><th>First Auction</th><th>Month 1</th><th>Month 2</th><th>Month 3</th><th>First 90 Days</th></tr></thead>
      <tbody>${body || `<tr><td colspan="6" class="cr-empty">No customers started on/after ${mlbl(START)}${term?' for this search':''}.</td></tr>`}</tbody>
      ${foot}
    </table></div>`;
  document.getElementById('cr-count').textContent = `${fmtN(cohort.length)} customer${cohort.length===1?'':'s'} since ${mlbl(START)}`;
  document.getElementById('cr-foot').innerHTML = anyIncomplete
    ? `“In progress” customers haven’t completed their first three months yet (window extends past ${esc(CR.dataDate || mlbl(LATEST))}); Month 2 / Month 3 show “·” where there’s no data yet.`
    : '';
}

document.getElementById('cr-search').addEventListener('input', e => { term = e.target.value.trim().toLowerCase(); render(); });
render();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
