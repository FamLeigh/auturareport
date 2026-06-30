<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Dashboard';
$meta_desc  = 'Autura Marketplace Report — internal dashboard.';
$body_class = 'page-dash';
$canonical  = '/';

// ── Data + headline aggregates ───────────────────────────────────────────────
$amr_data_file = __DIR__ . '/data/amr-data.json';
$amr_meta_file = __DIR__ . '/data/amr-meta.json';
$amr_meta = file_exists($amr_meta_file) ? json_decode(file_get_contents($amr_meta_file), true) : [];
$amr_data_date = $amr_meta['data_date'] ?? '';

$D = ['ok' => false];
if (file_exists($amr_data_file) && ($data = json_decode(file_get_contents($amr_data_file), true)) && !empty($data['records'])) {
    $sumMi=0; $cMi=0; $sumNoMi=0; $cNoMi=0;
    $sumKey=0; $cKey=0; $sumNoKey=0; $cNoKey=0;
    $sumSt=0; $cSt=0; $sumNoSt=0; $cNoSt=0;
    foreach ($data['records'] as $r) {
        $price=$r[3]; $flags=$r[4]; $odo=$r[7];
        if ($odo > 0) { $sumMi+=$price; $cMi++; } else { $sumNoMi+=$price; $cNoMi++; }
        if ($flags & 1) { $sumKey+=$price; $cKey++; } elseif ($flags & 2) { $sumNoKey+=$price; $cNoKey++; }
        if ($flags & 4) { $sumSt+=$price; $cSt++; } else { $sumNoSt+=$price; $cNoSt++; }
    }
    $avg = fn($s,$c) => $c ? round($s/$c) : 0;

    // Winners (unique buyers): from meta, else count the buyer dataset, else null.
    $winners = $amr_meta['unique_buyers'] ?? null;
    if ($winners === null && file_exists(__DIR__ . '/data/amr-buyers.json')) {
        $bj = json_decode(file_get_contents(__DIR__ . '/data/amr-buyers.json'), true);
        if (!empty($bj['buyers'])) $winners = count($bj['buyers']);
    }

    $D = [
        'ok'        => true,
        'sales'     => count($data['records']),
        'sellers'   => count($data['sellers'] ?? []),
        'winners'   => $winners,
        'avgMi'     => $avg($sumMi,$cMi),     'avgNoMi'  => $avg($sumNoMi,$cNoMi),
        'avgKey'    => $avg($sumKey,$cKey),   'avgNoKey' => $avg($sumNoKey,$cNoKey),
        'avgSt'     => $avg($sumSt,$cSt),     'avgNoSt'  => $avg($sumNoSt,$cNoSt),
    ];
}

$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.dash-hero { padding:calc(var(--nav-h) + 28px) 0 8px; }
.dash-hero h1 { font-size:clamp(1.8rem,4vw,2.5rem); margin-bottom:6px; }
.dash-sub { color:var(--text-muted); font-size:14px; }
.dash-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:22px 0 28px; }
@media (max-width:820px){ .dash-grid{ grid-template-columns:repeat(2,1fr);} }
@media (max-width:520px){ .dash-grid{ grid-template-columns:1fr;} }
.dash-box { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:20px 22px; }
.dash-box .lbl { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); }
.dash-box .big { font-size:2.1rem; font-weight:800; font-variant-numeric:tabular-nums; line-height:1.1; margin-top:8px; }
.dash-box .big .unit { font-size:.95rem; font-weight:600; color:var(--text-muted); margin-left:4px; }
.dash-box .splitlbl { font-size:11px; color:var(--text-muted); margin-top:8px; }
.dash-box .sub { font-size:1.15rem; font-weight:700; color:var(--text-muted); font-variant-numeric:tabular-nums; margin-top:2px; }
.dash-box .sub small { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
.dash-links { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:36px; }
.dash-link { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 18px; text-decoration:none; color:var(--text); font-weight:600; font-size:14px; }
.dash-link:hover { border-color:var(--accent); color:var(--accent); }
</style>';

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <section class="dash-hero">
    <h1>Autura Marketplace Report</h1>
    <p class="dash-sub">Internal dashboard<?= $amr_data_date ? ' · data as of ' . h($amr_data_date) : '' ?> · past 18 months of Autura Marketplace auction sales.</p>
  </section>

  <?php if (!$D['ok']): ?>
    <div class="dash-box" style="margin:20px 0;">No data loaded yet. Add sales data via <a href="/update">Update Data</a>.</div>
  <?php else:
    $n  = fn($v) => number_format((int)$v);
    $d  = fn($v) => '$' . number_format((int)$v);
  ?>
  <div class="dash-grid">
    <div class="dash-box">
      <div class="lbl">Total Sales</div>
      <div class="big"><?= $n($D['sales']) ?></div>
      <div class="splitlbl">Last 18 months</div>
    </div>
    <div class="dash-box">
      <div class="lbl">Total Sellers</div>
      <div class="big"><?= $n($D['sellers']) ?></div>
      <div class="splitlbl">Past 18 months</div>
    </div>
    <div class="dash-box">
      <div class="lbl">Total Winners</div>
      <div class="big"><?= $D['winners'] !== null ? $n($D['winners']) : '—' ?></div>
      <div class="splitlbl">Unique buyers, past 18 months</div>
    </div>

    <div class="dash-box">
      <div class="lbl">Avg Sale Price — Mileage Known</div>
      <div class="big"><?= $d($D['avgMi']) ?></div>
      <div class="sub"><?= $d($D['avgNoMi']) ?> <small>without mileage</small></div>
    </div>
    <div class="dash-box">
      <div class="lbl">Avg Sale Price — Has Keys</div>
      <div class="big"><?= $d($D['avgKey']) ?></div>
      <div class="sub"><?= $d($D['avgNoKey']) ?> <small>without keys</small></div>
    </div>
    <div class="dash-box">
      <div class="lbl">Avg Sale Price — Starts</div>
      <div class="big"><?= $d($D['avgSt']) ?></div>
      <div class="sub"><?= $d($D['avgNoSt']) ?> <small>does not start</small></div>
    </div>
  </div>

  <div class="dash-links">
    <a class="dash-link" href="/autura-valuation-tool">Autura Valuation Tool →</a>
    <a class="dash-link" href="/autura-market-report">Market Report →</a>
    <a class="dash-link" href="/customer-results">Seller-Results →</a>
    <a class="dash-link" href="/buyer-report">Buyer Report →</a>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
