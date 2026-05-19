<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['heckle_pass'])) {
    if ($_POST['heckle_pass'] === 'heckle') $_SESSION['amr_auth'] = true;
}
if (empty($_SESSION['amr_auth'])) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AMR Changelog</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{background:#0e0e10;color:#e8e8e0;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}.gate{background:#18181b;border:1px solid #2a2a2e;border-radius:12px;padding:40px 36px;width:100%;max-width:360px}.gate h1{font-size:1.1rem;font-weight:700;margin-bottom:6px}.gate p{font-size:13px;color:#888;margin-bottom:24px}.gate input{width:100%;background:#0e0e10;border:1px solid #2a2a2e;border-radius:8px;color:#e8e8e0;font-size:15px;padding:11px 14px;margin-bottom:12px}.gate input:focus{outline:none;border-color:#f0a500}.gate button{width:100%;background:#f0a500;border:none;border-radius:8px;color:#000;font-size:14px;font-weight:700;padding:12px;cursor:pointer}.gate .err{font-size:12px;color:#e05a5a;margin-bottom:10px}</style>
</head><body><div class="gate"><h1>AMR Changelog</h1><p>Enter the access password to continue.</p>
<?php if ($_SERVER['REQUEST_METHOD']==='POST'): ?><p class="err">Incorrect password.</p><?php endif; ?>
<form method="POST"><input type="password" name="heckle_pass" placeholder="Password" autofocus><button type="submit">Enter</button></form></div></body></html>
<?php exit; }

$ENTRIES = [
    ['v0.9 Beta', 'May 2026', 'Sidebar layout with accordions. Filters (Vehicle, Condition, Odometer, Documentation, Location) moved to a persistent 260px left sidebar. Results take full remaining width. Filter toggle removed — all options always visible.'],
    ['v0.8 Beta', 'May 2026', 'Model name normalization: 2,029 raw variants collapsed to 1,914 canonical models (spaced-dash variants CR - V → CR-V, truncations GRAND CHER ... → GRAND CHEROKEE, typos SILVRADO → SILVERADO, T&C variants → TOWN & COUNTRY). Multi-select model picker — select multiple models to pool their records (e.g. all BMW 330 variants or all Ford E-series). Sale count in results is now a link to the full raw data table. Trim threshold raised from n≥3 to n≥6 to avoid misleading averages on tiny samples.'],
    ['v0.7 Beta', 'May 2026', 'Data date shown in header. Weekly CSV upload tool added — upload a new CSV at /update to replace the dataset and trigger a rebuild. Changelog page added (this page).'],
    ['v0.6 Beta', 'May 2026', 'Make name normalization: ~95 raw CSV variants collapsed to 183 canonical makes (e.g. CHEVY/CHEVR/CHEVROT → CHEVROLET, 6 Chrysler variants → CHRYSLER). Full 18-month production dataset loaded (83,074 records). Test data removed.'],
    ['v0.5 Beta', 'May 2026', 'Password protection added. Session-based gate on / and /update.'],
    ['v0.4 Beta', 'May 2026', 'Complete data pipeline overhaul. CSVs now processed offline by build-amr-data.php into a compact dictionary-compressed JSON (2.25 MB). Page no longer inlines all records — fetches JSON async on load. Supports datasets of 80,000+ rows without page slowdown.'],
    ['v0.3 Beta', 'May 2026', 'Renamed from AMMR to Autura Marketplace Report (AMR). Internal codename established: Heckle. Clean URL updated to / .'],
    ['v0.2 Beta', 'May 2026', 'Filter UX improvement: key, condition, odometer, documentation, and region filters collapsed behind a toggle button. Active filter chips show current selections in the filter bar.'],
    ['v0.1 Beta', 'May 2026', 'Initial launch. Features: make/model/year selectors, VIN decoder (NHTSA API), price stats (range, average, trimmed average, median), recommended reserve (trimmed avg − 10%), condition score dots, 18-month price trend chart, regional breakdown table, condition signals (key premium, starts premium).'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AMR Changelog</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0e0e10; color: #e8e8e0; font-family: system-ui, sans-serif; padding: 48px 24px; }
  .wrap { max-width: 860px; margin: 0 auto; }
  .back { font-size: 13px; color: #888; text-decoration: none; display: inline-block; margin-bottom: 32px; }
  .back:hover { color: #f0a500; }
  h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; }
  .sub { font-size: 13px; color: #666; margin-bottom: 36px; }
  .entry { border-bottom: 1px solid #1e1e22; padding: 20px 0; display: grid; grid-template-columns: 100px 90px 1fr; gap: 0 20px; align-items: baseline; }
  .entry:last-child { border-bottom: none; }
  .ver { font-size: 13px; font-weight: 700; color: #f0a500; white-space: nowrap; }
  .entry:not(:first-child) .ver { color: #e8e8e0; }
  .date { font-size: 12px; color: #666; white-space: nowrap; }
  .desc { font-size: 13px; color: #aaa; line-height: 1.65; }
  @media (max-width: 500px) {
    .entry { grid-template-columns: 1fr; gap: 4px; }
    .date { display: none; }
  }
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="/">&larr; Back to AMR</a>
  <h1>Heckle Changelog</h1>
  <p class="sub">Autura Marketplace Report &nbsp;·&nbsp; All 9 versions</p>
  <?php foreach ($ENTRIES as $i => $e): ?>
  <div class="entry">
    <span class="ver"><?= htmlspecialchars($e[0]) ?></span>
    <span class="date"><?= htmlspecialchars($e[1]) ?></span>
    <span class="desc"><?= htmlspecialchars($e[2]) ?></span>
  </div>
  <?php endforeach; ?>
</div>
</body>
</html>
