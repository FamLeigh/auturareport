<?php
require_once __DIR__ . '/includes/auth.php';

$ENTRIES = [
    ['v1.1 Beta', 'May 2026', 'Light/dark theme toggle added to all pages. Defaults to light (white) background. Preference persists across sessions via localStorage with no flash on load.'],
    ['v1.0 Beta', 'May 2026', 'Moved to standalone site at auturareport.com. Separated from kevinleigh.com, new Autura-branded header and footer, independent deployment pipeline via GitHub and Hostinger.'],
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
<script>(function(){var t=localStorage.getItem('amr-theme')||'light';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();</script>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AMR Changelog</title>
<style>
  :root { --bg:#ffffff;--surface:#f6f6f4;--border:#ddddd8;--text:#111110;--muted:#666;--subtle:#999;--desc:#555;--ver-old:#111110; }
  [data-theme="dark"] { --bg:#0c0c0c;--surface:#141414;--border:#2a2a2a;--text:#f0ede8;--muted:#888;--subtle:#666;--desc:#aaa;--ver-old:#e8e8e0; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: system-ui, sans-serif; padding: 48px 24px; }
  .wrap { max-width: 860px; margin: 0 auto; }
  .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
  .back { font-size: 13px; color: var(--muted); text-decoration: none; }
  .back:hover { color: #f0a500; }
  .theme-btn { background: none; border: 1px solid var(--border); border-radius: 6px; color: var(--muted); cursor: pointer; width: 32px; height: 32px; font-size: 15px; display: flex; align-items: center; justify-content: center; }
  .theme-btn:hover { border-color: #f0a500; color: #f0a500; }
  h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; }
  .sub { font-size: 13px; color: var(--subtle); margin-bottom: 36px; }
  .entry { border-bottom: 1px solid var(--border); padding: 20px 0; display: grid; grid-template-columns: 100px 90px 1fr; gap: 0 20px; align-items: baseline; }
  .entry:last-child { border-bottom: none; }
  .ver { font-size: 13px; font-weight: 700; color: #f0a500; white-space: nowrap; }
  .entry:not(:first-child) .ver { color: var(--ver-old); }
  .date { font-size: 12px; color: var(--subtle); white-space: nowrap; }
  .desc { font-size: 13px; color: var(--desc); line-height: 1.65; }
  @media (max-width: 500px) {
    .entry { grid-template-columns: 1fr; gap: 4px; }
    .date { display: none; }
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="top-bar">
    <a class="back" href="/">&larr; Back to AMR</a>
    <button class="theme-btn" id="theme-btn" aria-label="Toggle theme">🌙</button>
  </div>
  <h1>Heckle Changelog</h1>
  <p class="sub">Autura Marketplace Report &nbsp;·&nbsp; All 11 versions</p>
  <?php foreach ($ENTRIES as $i => $e): ?>
  <div class="entry">
    <span class="ver"><?= htmlspecialchars($e[0]) ?></span>
    <span class="date"><?= htmlspecialchars($e[1]) ?></span>
    <span class="desc"><?= htmlspecialchars($e[2]) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<script>
(function(){
  var btn=document.getElementById('theme-btn');
  function sync(){var d=document.documentElement.getAttribute('data-theme')==='dark';btn.textContent=d?'☀':'🌙';}
  sync();
  btn.addEventListener('click',function(){
    var d=document.documentElement.getAttribute('data-theme')==='dark';
    if(d){document.documentElement.removeAttribute('data-theme');localStorage.setItem('amr-theme','light');}
    else{document.documentElement.setAttribute('data-theme','dark');localStorage.setItem('amr-theme','dark');}
    sync();
  });
})();
</script>
</body>
</html>
