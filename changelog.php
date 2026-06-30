<?php
require_once __DIR__ . '/includes/auth.php';

$ENTRIES = [
    ['v1.20 Beta', 'June 30, 2026', 'Customer Research and Impound Volume now require the customer-data access code (same one as Seller-Results). Added Login Activity to the Settings menu. Seller Groups save is hardened so an interrupted save can no longer wipe groups (with backups + confirmation before clearing all).'],
    ['v1.19 Beta', 'June 30, 2026', 'Seller Groups. New Settings → Define Seller Groups page to create named groups and assign sellers to them. The Market Report gains a Group filter (appears once groups are defined) that filters to the pooled sellers of the selected group(s), alongside Region and Seller.'],
    ['v1.18 Beta', 'June 30, 2026', 'Navigation reorg + two reports promoted. "Customer Results" renamed to Seller-Results. New "Cust Data" menu (Customer Research, Impound Map) and "Settings" menu (Changelog, Update Data). The access-code (PIN) field is now masked. Market Report KPI pills show current / prior-60 / same-period-last-year values.'],
    ['v1.17 Beta', 'June 16, 2026', 'Market Report: the Seller filter now lists only sellers from the selected Region(s) — pick a region and the Seller dropdown narrows to that region\'s sellers (and resets to all when the region is cleared).'],
    ['v1.16 Beta', 'June 12, 2026', 'Per-user passwords. The shared access password is now a one-time bootstrap: on first sign-in you set your own password (stored hashed, server-side) and use it from then on — the default no longer works for you afterward. Existing users are prompted to set a password on their next login.'],
    ['v1.15 Beta', 'June 12, 2026', 'Moved 90-Day Activity into the Customer Results report as a tab (alongside First Action, Sold by Month, and Potential Churn). Removed the separate nav item; the old /activity-90 link redirects to Customer Results.'],
    ['v1.14 Beta', 'June 10, 2026', 'New 90-Day Activity report at /activity-90 (behind login + the 4-digit code). Lists customers who first ran on/after Jul 1, 2025 and how many cars they sold in their first three months — per-month ramp (Month 1/2/3) plus a first-90-day total, with cohort totals and an "in progress" flag for customers whose window has not fully elapsed. Searchable by customer.'],
    ['v1.13 Beta', 'June 8, 2026', 'New Customer Results report at /customer-results (behind login plus a 4-digit access code). Three views of seller activity: First Action (each customer\'s first auction month), Sold by Month (customer × month grid of cars sold with monthly totals), and Potential Churn (no auctions in 90+ days, measured from the data date, most-lapsed first). Filter by start quarter — pick the quarter a customer first ran (e.g. Q3 2025) to see that cohort with monthly columns from the quarter forward. Searchable by customer.'],
    ['v1.12 Beta', 'June 8, 2026', 'Manual Log out added to the header and every page. Removed the outdated hard-coded changelog from the home page — version history now lives only at /changelog.'],
    ['v1.11 Beta', 'June 8, 2026', 'Access & session controls. Automatic sign-out after 30 minutes of inactivity. The access log (/access-log) now records the disclaimer acknowledgment and device for each login, and login regenerates the session id. Printouts carry a faint diagonal CONFIDENTIAL watermark on every page plus a "Prepared for <email> · date/time" attribution line so any exported PDF is traceable.'],
    ['v1.10 Beta', 'June 8, 2026', 'Site-wide confidentiality disclaimer: acknowledged via a required checkbox at sign-in, shown on every page footer, and printed at the top and bottom of every PDF. Covers not-financial-advice, private/internal-only data, subject-to-change, and no-warranty terms.'],
    ['v1.9 Beta', 'June 8, 2026', 'Region + Seller filters on the Market Report — searchable multi-selects. Pick multiple regions and/or sellers and the whole report recomputes for that pooled set, with KPI deltas shown vs the national average and a Selection-vs-National comparison card. 60-day comparison windows now anchor on the last complete month (a partial trailing month is excluded) so current volume is not understated; explicit month ranges shown on each KPI card plus a period legend (current / prior / same period last year). Seller Name captured in the data build; JSON build hardened against malformed UTF-8.'],
    ['v1.8 Beta', 'May 28, 2026', 'Revenue Impact Calculators. Two interactive sliders: (1) Mileage Reporting — drag target % to see additional vehicles, added sale value, and buyer premium gain for last 60 days plus full-year projection. (2) Has Key + Starts — same model for the combined condition premium. Both sliders compute live from the actual dataset.'],
    ['v1.7 Beta', 'May 28, 2026', 'Vehicle Condition Profile section added to Autura Market Report. Condition Mix table: has key / no key / key unknown / starts / no start / mileage known / no mileage, each with count, % of total, avg price, and delta vs overall. Problem Combinations table: No Mileage + No Key, No Mileage + No Start, No Key + No Start, and the triple — No Mileage + No Key + No Start. Best-case baseline (key + starts + mileage) shown for comparison.'],
    ['v1.6 Beta', 'May 28, 2026', 'Data-driven insights added throughout Autura Market Report. Six callout panels computed from live data: period trend (vol/price vs prior and YoY), vehicle type premium, top-make liquidity share, regional price spread, odometer/jump-box ROI, and combined key+starts premium. Odometer bands now show % of total alongside avg price.'],
    ['v1.5 Beta', 'May 28, 2026', 'Percentages added throughout the report: vehicle type donut legend (% of 60d volume), top makes and regions (% vol · avg $), condition cards (% with key / % that start). Dataset date from amr-meta.json displayed in the report hero. Print support added — Print / Save PDF button with @media print CSS; print footer shows copyright and autura.com.'],
    ['v1.4 Beta', 'May 28, 2026', 'Charts added to Autura Market Report: donut chart for vehicle type mix, grouped bar chart for 60-day period comparison (volume / avg price / median), horizontal bar charts for top 15 makes and top 12 regions (volume with % and avg price). Enhanced 12-month trend: volume bars + avg price line with gridlines.'],
    ['v1.3 Beta', 'May 28, 2026', 'Autura Market Report launched at /autura-market-report. Manheim-style analytics page: 3 KPI cards with 60-day deltas, period comparison table (last 60d / prior 60d / year-ago 60d), vehicle type breakdown, regional table, top 15 makes, price by odometer band, condition premiums (key / starts), documentation mix, and 12-month trend chart. All computed client-side from the existing dataset.'],
    ['v1.2 Beta', 'May 19, 2026', 'Login now requires an @autura.com email address plus the access password. Non-Autura emails are rejected with a specific error. Each successful login is recorded (email, IP, timestamp) to data/access-log.json. Access log viewable at /access-log (auth-gated). Auth logic centralized in includes/auth.php; duplicate gate HTML removed from all three pages.'],
    ['v1.1 Beta', 'May 19, 2026', 'Light/dark theme toggle added to all pages. Defaults to light (white) background. Preference persists across sessions via localStorage with no flash on load. Toggle button in nav: moon to go dark, sun to return to light.'],
    ['v1.0 Beta', 'May 19, 2026', 'Moved to standalone site at auturareport.com. Separated from kevinleigh.com, new Autura-branded header and footer, clean URL routing, independent GitHub repo (FamLeigh/auturareport) and Hostinger deployment pipeline.'],
    ['v0.9 Beta', 'May 19, 2026', 'Sidebar layout with accordions. Filters (Vehicle, Condition, Odometer, Documentation, Location) moved to a persistent 260px left sidebar. Results take full remaining width. Filter toggle removed — all options always visible.'],
    ['v0.8 Beta', 'May 19, 2026', 'Model name normalization: 2,029 raw variants collapsed to 1,914 canonical models (spaced-dash variants CR - V → CR-V, truncations GRAND CHER ... → GRAND CHEROKEE, typos SILVRADO → SILVERADO, T&C variants → TOWN & COUNTRY). Multi-select model picker. Sale count in results links to full raw data table. Trim threshold raised from n≥3 to n≥6.'],
    ['v0.7 Beta', 'May 19, 2026', 'Dataset date shown in header. CSV upload tool added at /update — upload a new CSV to replace the dataset and trigger an automated rebuild. Changelog page added.'],
    ['v0.6 Beta', 'May 19, 2026', 'Make name normalization: ~95 raw CSV variants collapsed to 183 canonical makes (e.g. CHEVY/CHEVR/CHEVROT → CHEVROLET, 6 Chrysler variants → CHRYSLER). Full 18-month production dataset loaded (83,074 records). Test data removed.'],
    ['v0.5 Beta', 'May 19, 2026', 'Password protection added. Session-based gate on / and /update. Login required to view any part of the tool.'],
    ['v0.4 Beta', 'May 19, 2026', 'Complete data pipeline overhaul. CSVs processed offline by build-amr-data.php into a compact dictionary-compressed JSON (2.25 MB). Page no longer inlines all records — fetches JSON async on load. Supports 80,000+ rows without page slowdown.'],
    ['v0.3 Beta', 'May 19, 2026', 'Renamed from AMMR to Autura Marketplace Report (AMR). Internal codename: Heckle. Inspired by the ThoughtSpot version from Erin Hankins, Spencer Bauman, and Jason Berman.'],
    ['v0.2 Beta', 'May 19, 2026', 'Filter UX improvement: key, condition, odometer, documentation, and region filters collapsed behind a toggle button. Active filter chips show current selections.'],
    ['v0.1 Beta', 'May 19, 2026', 'Initial launch. Features: make/model/year selectors, VIN decoder (NHTSA API), price stats (range, average, trimmed average, median), recommended reserve (trimmed avg − 10%), condition score dots, 18-month price trend chart, regional breakdown table, condition signals (key premium, starts premium).'],
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
  .entry { border-bottom: 1px solid var(--border); padding: 20px 0; display: grid; grid-template-columns: 100px 110px 1fr; gap: 0 20px; align-items: baseline; }
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
    <span style="display:flex;align-items:center;gap:12px;">
      <a href="/logout" style="font-size:12px;font-weight:600;color:var(--muted);text-decoration:none;">Log out</a>
      <button class="theme-btn" id="theme-btn" aria-label="Toggle theme">🌙</button>
    </span>
  </div>
  <h1>AMR Changelog</h1>
  <p class="sub">Autura Marketplace Report &nbsp;·&nbsp; All <?= count($ENTRIES) ?> versions</p>
  <?php foreach ($ENTRIES as $i => $e): ?>
  <div class="entry">
    <span class="ver"><?= htmlspecialchars($e[0]) ?></span>
    <span class="date"><?= htmlspecialchars($e[1]) ?></span>
    <span class="desc"><?= htmlspecialchars($e[2]) ?></span>
  </div>
  <?php endforeach; ?>
  <div style="margin-top:36px;padding-top:16px;border-top:1px solid var(--border);font-size:11px;line-height:1.5;color:var(--subtle);">
    <?= AMR_DISCLAIMER_SHORT ?>
  </div>
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
