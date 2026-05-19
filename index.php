<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Autura Marketplace Report';
$meta_desc  = 'Beta vehicle valuation tool built from 18 months of Autura auction marketplace sales data.';
$body_class = 'page-amr';
$canonical  = '/';

// ── Pre-built data metadata ───────────────────────────────────────────────────

$amr_meta_file = __DIR__ . '/data/amr-meta.json';
$amr_data_file = __DIR__ . '/data/amr-data.json';
$amr_record_count = 0;
$amr_data_date    = '';
$amr_data_version = file_exists($amr_data_file) ? filemtime($amr_data_file) : 0;
if (file_exists($amr_meta_file)) {
    $amr_meta = json_decode(file_get_contents($amr_meta_file), true);
    $amr_record_count = $amr_meta['count']     ?? 0;
    $amr_data_date    = $amr_meta['data_date'] ?? '';
}

// ── Page meta ─────────────────────────────────────────────────────────────────

$extra_head = '
<meta name="robots" content="noindex, nofollow">
<style>
/* ── Heckle tool styles ──────────────────────────────────────────────────── */

.hk-hero { padding: 80px 0 48px; }
.hk-hero .badge { margin-bottom: 20px; }
.hk-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); margin-bottom: 16px; }
.hk-hero .lead { color: var(--text-muted); line-height: 1.7; }

.hk-layout {
  display: flex;
  gap: 28px;
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 24px 96px;
  align-items: flex-start;
}
.hk-sidebar {
  width: 260px;
  flex-shrink: 0;
  position: sticky;
  top: 24px;
  max-height: calc(100vh - 48px);
  overflow-y: auto;
}
.hk-content { flex: 1; min-width: 0; }
@media (max-width: 860px) {
  .hk-layout { flex-direction: column; }
  .hk-sidebar { width: 100%; position: static; max-height: none; overflow-y: visible; }
}

/* Sidebar accordion */
.hk-acc { border-bottom: 1px solid var(--border); }
.hk-acc:first-child { border-top: 1px solid var(--border); }
.hk-acc-head {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%; background: none; border: none;
  color: var(--text-muted); font-family: var(--font-body);
  font-size: 13px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  padding: 13px 0; cursor: pointer; text-align: left;
  transition: color var(--trans);
}
.hk-acc-head:hover { color: var(--text); }
.hk-acc.open .hk-acc-head { color: var(--text); }
.hk-acc-head svg { transition: transform .2s; flex-shrink: 0; }
.hk-acc.open .hk-acc-head svg { transform: rotate(180deg); }
.hk-acc-body { display: none; padding-bottom: 16px; }
.hk-acc.open .hk-acc-body { display: block; }
.hk-sidebar .hk-selectors { grid-template-columns: 1fr; gap: 10px; margin-bottom: 0; }

/* Cards */
.hk-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  margin-bottom: 20px;
}
.hk-card-title {
  font-family: var(--font-display);
  font-size: .95rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 20px;
}

/* VIN decoder */
.hk-vin-row {
  display: flex;
  gap: 10px;
  align-items: stretch;
}
.hk-vin-input {
  flex: 1;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  font-family: var(--font-body);
  font-size: 15px;
  padding: 11px 14px;
  letter-spacing: .04em;
  text-transform: uppercase;
  transition: border-color var(--trans);
}
.hk-vin-input:focus { outline: none; border-color: var(--accent); }
.hk-vin-input::placeholder { text-transform: none; letter-spacing: 0; }
.hk-vin-btn {
  background: var(--accent);
  border: none;
  border-radius: var(--radius);
  color: #000;
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 700;
  padding: 0 22px;
  cursor: pointer;
  transition: opacity var(--trans);
  white-space: nowrap;
}
.hk-vin-btn:hover { opacity: .85; }
.hk-vin-btn:disabled { opacity: .4; cursor: not-allowed; }
.hk-vin-result {
  margin-top: 14px;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
}
.hk-vin-result.error { color: #e05a5a; }
.hk-vin-chip {
  display: inline-block;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 3px 10px;
  font-size: 12px;
  margin: 2px 3px 2px 0;
}

/* Vehicle selectors */
.hk-selectors {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
  margin-bottom: 28px;
}
@media (max-width: 580px) { .hk-selectors { grid-template-columns: 1fr; } }

.hk-field label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
}
.hk-select {
  width: 100%;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  font-family: var(--font-body);
  font-size: 15px;
  padding: 11px 40px 11px 14px;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'8\' viewBox=\'0 0 12 8\'%3E%3Cpath d=\'M1 1l5 5 5-5\' stroke=\'%23888\' stroke-width=\'1.5\' fill=\'none\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  cursor: pointer;
  transition: border-color var(--trans);
}
.hk-select:focus   { outline: none; border-color: var(--accent); }
.hk-select:disabled { opacity: .38; cursor: not-allowed; }
.hk-select option  { background: var(--surface-2); }

/* Multi-select model dropdown */
.hk-multi { position: relative; }
.hk-multi-trigger {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  width: 100%; background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius); color: var(--text); font-size: 15px;
  padding: 11px 14px; cursor: pointer; user-select: none;
  transition: border-color var(--trans);
}
.hk-multi-trigger:hover:not(.disabled) { border-color: #b0b0ab; }
.hk-multi-trigger.open  { border-color: var(--accent); }
.hk-multi-trigger.disabled { opacity: .38; pointer-events: none; }
.hk-multi-trigger svg { flex-shrink: 0; transition: transform .15s; }
.hk-multi-trigger.open svg { transform: rotate(180deg); }
.hk-multi-label { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.hk-multi-dropdown {
  display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 200;
  background: #f6f6f4; border: 1px solid var(--border); border-radius: var(--radius);
  box-shadow: 0 8px 24px rgba(0,0,0,.12); overflow: hidden;
}
.hk-multi-dropdown.open { display: block; }
.hk-multi-actions {
  display: flex; gap: 6px; padding: 8px 10px 6px;
  border-bottom: 1px solid #e0e0db;
}
.hk-multi-actions button {
  font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  background: none; border: 1px solid var(--border); border-radius: 4px;
  color: var(--text-muted); padding: 3px 10px; cursor: pointer;
  transition: border-color var(--trans), color var(--trans);
}
.hk-multi-actions button:hover { border-color: var(--accent); color: var(--accent); }
.hk-multi-list { max-height: 300px; overflow-y: auto; }
.hk-multi-item {
  display: flex; align-items: center; gap: 14px;
  padding: 10px 16px; cursor: pointer; font-size: 16px; color: #111110;
  transition: background .1s;
}
.hk-multi-item:hover { background: #e8e8e4; }
.hk-multi-item input { accent-color: var(--accent); flex-shrink: 0; cursor: pointer; }
.hk-multi-badge {
  display: inline-block; background: var(--accent); color: #000;
  font-size: 10px; font-weight: 700; border-radius: 10px;
  padding: 0 5px; margin-left: 4px; vertical-align: middle; line-height: 16px;
}

/* Filter toggle */
.hk-filter-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
  white-space: nowrap;
}
.hk-filter-col + .hk-filter-col { margin-top: 14px; }
.hk-pills { display: flex; gap: 5px; flex-wrap: wrap; }
.hk-pill {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 100px;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  padding: 5px 14px;
  cursor: pointer;
  transition: all var(--trans);
  user-select: none;
}
.hk-pill:hover { border-color: var(--accent); color: var(--accent); }
.hk-pill.active {
  background: var(--accent-glow);
  border-color: rgba(240,165,0,.4);
  color: var(--accent);
}

/* Empty state */
.hk-empty {
  text-align: center;
  padding: 56px 24px;
  color: var(--text-muted);
}
.hk-empty svg { margin: 0 auto 20px; opacity: .25; display: block; }

/* Results header */
.hk-results-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}
.hk-results-title {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 700;
}
.hk-count-btn {
  background: none; border: none; padding: 0; cursor: pointer;
  font-size: 13px; color: var(--text-muted); font-family: var(--font-body);
  text-decoration: underline; text-decoration-style: dotted; text-underline-offset: 3px;
}
.hk-count-btn:hover { color: var(--accent); }
.hk-count-btn.warn {
  font-size: 12px; color: var(--accent);
  background: var(--accent-glow);
  border: 1px solid rgba(240,165,0,.25);
  border-radius: 100px; padding: 3px 10px;
  text-decoration: none;
}
.hk-count-btn.warn:hover { opacity: .8; }
.hk-raw-data { margin-top: 12px; }
.hk-raw-table { width: 100%; border-collapse: collapse; }
.hk-raw-table th {
  font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
  color: var(--text-muted); padding: 0 12px 10px; text-align: left; white-space: nowrap;
}
.hk-raw-table th.num, .hk-raw-table td.num { text-align: right; }
.hk-raw-table td {
  padding: 8px 12px; font-size: 14px; color: #555;
  border-top: 1px solid #e0e0db; white-space: nowrap;
}
.hk-raw-table tr:hover td { background: #f0f0ee; }

/* Stat grid */
.hk-stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 14px;
}
@media (max-width: 580px) { .hk-stat-grid { grid-template-columns: repeat(2, 1fr); } }
.hk-stat {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 12px;
  text-align: center;
}
.hk-stat-label {
  font-size: 12px; font-weight: 600;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--text-muted); margin-bottom: 8px;
}
.hk-stat-value {
  font-family: var(--font-display);
  font-size: 1.35rem; font-weight: 700;
  color: var(--text); line-height: 1.1;
}
.hk-stat-sub { font-size: 14px; color: var(--text-subtle); margin-top: 5px; }

/* Reserve */
.hk-reserve {
  background: var(--accent-glow);
  border: 1px solid rgba(240,165,0,.3);
  border-radius: var(--radius);
  padding: 20px 24px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}
.hk-reserve-left { flex: 1; min-width: 140px; }
.hk-reserve-label {
  font-size: 13px; font-weight: 600;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--accent); margin-bottom: 6px;
}
.hk-reserve-value {
  font-family: var(--font-display);
  font-size: 2.2rem; font-weight: 800;
  color: var(--accent); line-height: 1;
}
.hk-reserve-note { font-size: 14px; color: var(--text-muted); line-height: 1.5; }
.hk-reserve-right { flex: 0 0 auto; }

/* Condition score dots */
.hk-score-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.hk-score-dots { display: flex; gap: 5px; }
.hk-score-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--border);
  transition: background .2s;
}
.hk-score-dot.filled { background: var(--accent); }
.hk-score-label {
  font-size: 14px; font-weight: 600;
  color: var(--accent);
}

/* Condition signals */
.hk-signals {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 14px;
}
.hk-signal {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 16px;
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.5;
}
.hk-compare-wrap { margin-top: 12px; }
.hk-compare-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #999; margin-bottom: 10px; }
.hk-compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.hk-compare-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 20px; }
.hk-compare-year { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.hk-compare-n { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
.hk-compare-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
.hk-compare-row span:first-child { color: var(--text-muted); }
.hk-compare-row span:last-child { font-weight: 600; }
.hk-compare-reserve { margin-top: 10px; font-size: 14px; font-weight: 700; color: var(--accent); }
@media (max-width: 480px) { .hk-compare-grid { grid-template-columns: 1fr; } }
.hk-signal strong { color: var(--text); display: block; margin-bottom: 2px; }
.hk-signal .pos { color: #5ec97c; }
.hk-signal .neg { color: #e05a5a; }

/* Trend chart */
.hk-trend-chart {
  width: 100%;
  overflow-x: auto;
}
.hk-trend-chart svg { display: block; }
.hk-bar-label {
  font-family: var(--font-body);
  font-size: 12px;
  fill: var(--text-muted);
}
.hk-bar-value {
  font-family: var(--font-body);
  font-size: 12px;
  fill: var(--text);
}
.hk-axis-line { stroke: var(--border); stroke-width: 1; }

/* Regional table */
.hk-table-wrap { overflow-x: auto; }
.hk-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.hk-table th {
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 0 12px 10px;
  border-bottom: 1px solid var(--border);
}
.hk-table th:first-child { padding-left: 0; }
.hk-table td {
  padding: 10px 12px;
  border-bottom: 1px solid rgba(0,0,0,.06);
  color: var(--text);
  white-space: nowrap;
}
.hk-table td:first-child { padding-left: 0; }
.hk-table tr:last-child td { border-bottom: none; }
.hk-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.hk-delta-pos { color: #5ec97c; font-size: 13px; }
.hk-delta-neg { color: #e05a5a; font-size: 13px; }
.hk-delta-neu { color: var(--text-muted); font-size: 13px; }

.hk-credit {
  font-size: 13px; color: var(--text-subtle);
  text-align: center; margin-top: 20px;
}
@keyframes hk-spin { to { transform: rotate(360deg); } }

/* ── Dark theme overrides for hardcoded values ── */
[data-theme="dark"] .hk-multi-trigger:hover:not(.disabled) { border-color: #3a3a3e; }
[data-theme="dark"] .hk-multi-dropdown { background: #18181b; box-shadow: 0 8px 24px rgba(0,0,0,.5); }
[data-theme="dark"] .hk-multi-actions  { border-bottom-color: #1e1e22; }
[data-theme="dark"] .hk-multi-item     { color: #e8e8e0; }
[data-theme="dark"] .hk-multi-item:hover { background: #1e1e22; }
[data-theme="dark"] .hk-raw-table td   { color: #bbb; border-top-color: #1e1e22; }
[data-theme="dark"] .hk-raw-table tr:hover td { background: #1a1a1e; }
[data-theme="dark"] .hk-table td       { border-bottom-color: rgba(255,255,255,.04); }
[data-theme="dark"] .hk-compare-label  { color: #666; }
</style>';

include __DIR__ . '/includes/header.php';
?>

<section class="hk-hero container">
  <h1>Autura Marketplace Report <span style="font-size:.55em;font-weight:600;color:var(--accent);letter-spacing:.01em;">(AKA Heckle)</span></h1>
  <p class="lead">
    Inspired by the ThoughtSpot version from Erin Hankins, Spencer Bauman, and Jason Berman. Valuation estimates drawn from <?= $amr_record_count > 0 ? number_format($amr_record_count) : 'tens of thousands of' ?> sold auction records over the last 18 months, reflecting as-is impound vehicle data from Autura marketplace sales. Beta, for internal evaluation. Created by Kevin B. Leigh. &copy; 2026 Autura NowCo, LLC.
  </p>
  <p style="font-size:13px;color:var(--text-subtle);margin-top:12px;">
    <?php if ($amr_data_date): ?>Dataset: <?= htmlspecialchars($amr_data_date) ?> &nbsp;·&nbsp; <?php endif; ?>
    <a href="/update" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:3px;">Update data</a>
    &nbsp;·&nbsp;
    <a href="#hk-changelog" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:3px;">Changelog</a>
  </p>
</section>

<div class="hk-layout">

  <!-- ══ Sidebar ══════════════════════════════════════════════════ -->
  <aside class="hk-sidebar">

    <!-- Vehicle -->
    <div class="hk-acc open">
      <button class="hk-acc-head" type="button">Vehicle
        <svg width="10" height="7" viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
      </button>
      <div class="hk-acc-body">
        <div class="hk-selectors">
          <div class="hk-field">
            <label for="hk-make">Make</label>
            <select id="hk-make" class="hk-select">
              <option value="">Select make…</option>
            </select>
          </div>
          <div class="hk-field">
            <label>Model</label>
            <div class="hk-multi" id="hk-model-wrap">
              <div class="hk-multi-trigger disabled" id="hk-model-trigger" tabindex="-1">
                <span class="hk-multi-label" id="hk-model-label">Select model…</span>
                <svg width="12" height="8" viewBox="0 0 12 8" fill="none" stroke="#888" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
              </div>
              <div class="hk-multi-dropdown" id="hk-model-dropdown">
                <div class="hk-multi-actions">
                  <button type="button" id="hk-model-all">All</button>
                  <button type="button" id="hk-model-clear">Clear</button>
                </div>
                <div id="hk-model-list"></div>
              </div>
            </div>
          </div>
          <div class="hk-field">
            <label for="hk-year">Year</label>
            <select id="hk-year" class="hk-select" disabled>
              <option value="all">All years</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Condition -->
    <div class="hk-acc open">
      <button class="hk-acc-head" type="button">Condition
        <svg width="10" height="7" viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
      </button>
      <div class="hk-acc-body">
        <div class="hk-filter-col">
          <span class="hk-filter-label">Key</span>
          <div class="hk-pills">
            <span class="hk-pill active" data-g="key" data-v="all">All</span>
            <span class="hk-pill" data-g="key" data-v="has_key">Has Key</span>
            <span class="hk-pill" data-g="key" data-v="no_key">No Key</span>
          </div>
        </div>
        <div class="hk-filter-col">
          <span class="hk-filter-label">Starts</span>
          <div class="hk-pills">
            <span class="hk-pill active" data-g="start" data-v="all">All</span>
            <span class="hk-pill" data-g="start" data-v="starts">Verified Starts</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Odometer -->
    <div class="hk-acc open">
      <button class="hk-acc-head" type="button">Odometer
        <svg width="10" height="7" viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
      </button>
      <div class="hk-acc-body">
        <div class="hk-pills">
          <span class="hk-pill active" data-g="odo" data-v="all">All</span>
          <span class="hk-pill" data-g="odo" data-v="u100">Under 100k</span>
          <span class="hk-pill" data-g="odo" data-v="100_150">100k–150k</span>
          <span class="hk-pill" data-g="odo" data-v="o150">Over 150k</span>
        </div>
      </div>
    </div>

    <!-- Documentation -->
    <div class="hk-acc open">
      <button class="hk-acc-head" type="button">Documentation
        <svg width="10" height="7" viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
      </button>
      <div class="hk-acc-body">
        <div class="hk-pills" id="hk-doc-pills">
          <span class="hk-pill active" data-g="doc" data-v="all">All</span>
          <span class="hk-pill" data-g="doc" data-v="Title">Title</span>
          <span class="hk-pill" data-g="doc" data-v="Lien">Lien</span>
          <span class="hk-pill" data-g="doc" data-v="Salvage">Salvage</span>
          <span class="hk-pill" data-g="doc" data-v="Abandoned">Abandoned</span>
          <span class="hk-pill" data-g="doc" data-v="Court Order">Court Order</span>
          <span class="hk-pill" data-g="doc" data-v="Junk">Junk</span>
          <span class="hk-pill" data-g="doc" data-v="Other">Other</span>
        </div>
      </div>
    </div>

    <!-- Location (shown only after vehicle selected) -->
    <div class="hk-acc open" id="hk-region-filter" style="display:none">
      <button class="hk-acc-head" type="button">Location
        <svg width="10" height="7" viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l5 5 5-5"/></svg>
      </button>
      <div class="hk-acc-body">
        <div class="hk-pills" id="hk-region-pills">
          <span class="hk-pill active" data-g="region" data-v="all">All</span>
        </div>
      </div>
    </div>

  </aside>

  <!-- ══ Main content ══════════════════════════════════════════════ -->
  <div class="hk-content">

  <!-- ── VIN Decoder ───────────────────────────────────────────── -->
  <div class="hk-card">
    <div class="hk-card-title">Decode a VIN <span style="font-weight:400;text-transform:none;letter-spacing:0;">(or select a vehicle below)</span></div>
    <div class="hk-vin-row">
      <input id="hk-vin" class="hk-vin-input" type="text" maxlength="17"
             placeholder="Enter 17-character VIN to auto-fill make / model / year" autocomplete="off" spellcheck="false">
      <button id="hk-vin-btn" class="hk-vin-btn">Decode</button>
    </div>
    <div id="hk-vin-result" class="hk-vin-result" style="display:none;"></div>
  </div>

  <!-- ── Results ───────────────────────────────────────────────── -->
  <div id="hk-results">
    <div class="hk-card hk-empty" id="hk-loading">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="animation:hk-spin 1s linear infinite;margin:0 auto 16px;display:block;opacity:.4;">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
      </svg>
      <p>Loading auction data…</p>
    </div>
  </div>

  <!-- ── Trend chart ───────────────────────────────────────────── -->
  <div id="hk-trend" style="display:none;" class="hk-card">
    <div class="hk-card-title">18-Month Price Trend</div>
    <div class="hk-trend-chart" id="hk-trend-chart"></div>
  </div>

  <!-- ── Regional breakdown ────────────────────────────────────── -->
  <div id="hk-regional" style="display:none;" class="hk-card">
    <div class="hk-card-title">Regional Breakdown</div>
    <div class="hk-table-wrap">
      <table class="hk-table" id="hk-regional-table"></table>
    </div>
  </div>

  <!-- ── Changelog (recent 4) ─────────────────────────────────── -->
  <div class="hk-card" id="hk-changelog" style="margin-top:32px;">
    <div class="hk-card-title">Changelog</div>
    <div style="overflow-x:auto;">
      <table class="hk-table" style="font-size:14px;">
        <thead><tr>
          <th style="width:90px;white-space:nowrap;">Version</th>
          <th style="width:90px;white-space:nowrap;">Date</th>
          <th style="white-space:normal;min-width:200px;">Changes</th>
        </tr></thead>
        <tbody>
          <tr>
            <td style="white-space:nowrap;"><strong style="color:var(--accent);">v0.9 Beta</strong></td>
            <td style="color:var(--text-muted);white-space:nowrap;">May 2026</td>
            <td style="white-space:normal;">Sidebar layout with accordions — filters moved to a persistent left panel (Vehicle, Condition, Odometer, Documentation, Location).</td>
          </tr>
          <tr>
            <td style="white-space:nowrap;"><strong>v0.8 Beta</strong></td>
            <td style="color:var(--text-muted);white-space:nowrap;">May 2026</td>
            <td style="white-space:normal;">Model normalization (2,029 → 1,914 canonical models). Multi-select model picker. Raw sales data table. Trim threshold fix.</td>
          </tr>
          <tr>
            <td style="white-space:nowrap;"><strong>v0.7 Beta</strong></td>
            <td style="color:var(--text-muted);white-space:nowrap;">May 2026</td>
            <td style="white-space:normal;">Data date shown in header. Weekly CSV upload tool added for admins. Changelog added.</td>
          </tr>
          <tr>
            <td style="white-space:nowrap;"><strong>v0.6 Beta</strong></td>
            <td style="color:var(--text-muted);white-space:nowrap;">May 2026</td>
            <td style="white-space:normal;">Make name normalization — ~95 raw variants collapsed to 183 canonical names. Full 18-month dataset loaded (83,074 records). Test data removed.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p style="margin-top:14px;font-size:14px;">
      <a href="/changelog" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:3px;">View all 9 versions &rarr;</a>
    </p>
  </div>

  <p class="hk-credit">Autura Marketplace Report &nbsp;·&nbsp; Heckle v0.9 Beta</p>

  </div><!-- /.hk-content -->
</div><!-- /.hk-layout -->

<script>
let VEHICLES = [];

// ── Decode compact record format from build-amr-data.php ──────────────────
function decodeRecords(data) {
  return data.records.map(r => ({
    make:    String(data.makes[r[0]]),
    model:   String(data.models[r[1]]),
    year:    r[2],
    price:   r[3],
    has_key: !!(r[4] & 1),
    no_key:  !!(r[4] & 2),
    starts:  !!(r[4] & 4),
    region:  data.regions[r[5]],
    doc:     data.docs[r[6]],
    odo:     r[7],
    month:   r[8] >= 0 ? data.months[r[8]] : '',
  }));
}

// ── State ──────────────────────────────────────────────────────────────────
const S = { key: 'all', start: 'all', doc: 'all', region: 'all', odo: 'all' };

// ── Region display names ───────────────────────────────────────────────────
const REGION_NAMES = {
  'LAX-CA':'Los Angeles, CA', 'CHI-IL':'Chicago, IL',   'DL-TX':'Dallas, TX',
  'NSH-TN':'Nashville, TN',   'EP-TX':'El Paso, TX',    'RDU-NC':'Raleigh, NC',
  'SA-TX':'San Antonio, TX',  'PHX-AZ':'Phoenix, AZ',   'SBC-CA':'San Bernardino, CA',
  'KC-MO':'Kansas City, MO',  'DET-MI':'Detroit, MI',   'SJ-CA':'San Jose, CA',
  'OC-CA':'Orange County, CA','SF-CA':'San Francisco, CA','IN-IN':'Indianapolis, IN',
};
const regionLabel = (r) => REGION_NAMES[r] || r;

// ── DOM refs ───────────────────────────────────────────────────────────────
const selMake      = document.getElementById('hk-make');
const selYear      = document.getElementById('hk-year');
const modelWrap    = document.getElementById('hk-model-wrap');
const modelTrigger = document.getElementById('hk-model-trigger');
const modelLabelEl = document.getElementById('hk-model-label');
const modelDrop    = document.getElementById('hk-model-dropdown');
const modelList    = document.getElementById('hk-model-list');
const modelBtnAll  = document.getElementById('hk-model-all');
const modelBtnClr  = document.getElementById('hk-model-clear');
let   selectedModels = new Set();
const results      = document.getElementById('hk-results');
const trendWrap    = document.getElementById('hk-trend');
const trendChart   = document.getElementById('hk-trend-chart');
const regWrap      = document.getElementById('hk-regional');
const regTable     = document.getElementById('hk-regional-table');
const regFilter    = document.getElementById('hk-region-filter');
const regPills     = document.getElementById('hk-region-pills');

// ── Helpers ────────────────────────────────────────────────────────────────
const uniq     = (arr) => [...new Set(arr)].sort();
const getMakes  = ()       => uniq(VEHICLES.map(v => v.make));
const getModels = (mk)     => uniq(VEHICLES.filter(v => v.make === mk).map(v => v.model));
const getYears  = (mk, md) => uniq(VEHICLES.filter(v => v.make === mk && v.model === md).map(v => v.year));
const titleCase = (s)      => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
const fmt       = (n)      => '$' + Math.round(n).toLocaleString();

function median(sorted) {
  const n = sorted.length;
  return n % 2 === 1
    ? sorted[Math.floor(n / 2)]
    : (sorted[n / 2 - 1] + sorted[n / 2]) / 2;
}

function avg(arr) { return arr.reduce((s, v) => s + v, 0) / arr.length; }

function conditionScore(keyF, startF) {
  let s = 3;
  if (keyF === 'has_key') s = 4;
  if (keyF === 'no_key')  s = 2;
  if (startF === 'starts' && keyF === 'has_key') s = 5;
  if (startF === 'starts' && keyF !== 'no_key')  s = Math.max(s, 4);
  return Math.max(1, Math.min(5, s));
}

const SCORE_LABELS = ['', 'Below Market', 'Below Average', 'Average', 'Above Average', 'Premium'];

function scoreDots(score) {
  return Array.from({length: 5}, (_, i) =>
    `<span class="hk-score-dot${i < score ? ' filled' : ''}"></span>`
  ).join('');
}

// ── Apply filters ──────────────────────────────────────────────────────────
function applyFilters(pool) {
  if (S.key === 'has_key') pool = pool.filter(v => v.has_key);
  if (S.key === 'no_key')  pool = pool.filter(v => v.no_key);
  if (S.start === 'starts') pool = pool.filter(v => v.starts);
  if (S.doc !== 'all')     pool = pool.filter(v => v.doc === S.doc);
  if (S.region !== 'all')  pool = pool.filter(v => v.region === S.region);
  if (S.odo === 'u100')    pool = pool.filter(v => v.odo > 0 && v.odo < 100000);
  if (S.odo === '100_150') pool = pool.filter(v => v.odo >= 100000 && v.odo <= 150000);
  if (S.odo === 'o150')    pool = pool.filter(v => v.odo > 150000);
  return pool;
}

// ── Populate dropdowns ─────────────────────────────────────────────────────
function populateMakes() {
  getMakes().forEach(m => {
    const o = document.createElement('option');
    o.value = m; o.textContent = titleCase(m);
    selMake.appendChild(o);
  });
}

function updateModelLabel() {
  const n = selectedModels.size;
  if (n === 0) {
    modelLabelEl.innerHTML = 'Select model…';
  } else if (n === 1) {
    modelLabelEl.textContent = titleCase([...selectedModels][0]);
  } else {
    modelLabelEl.innerHTML = `${n} models selected <span class="hk-multi-badge">${n}</span>`;
  }
}

function onModelChange() {
  selectedModels.clear();
  modelList.querySelectorAll('input:checked').forEach(cb => selectedModels.add(cb.value));
  updateModelLabel();
  populateYears(selMake.value, [...selectedModels]);
  S.region = 'all';
  calculate();
}

function populateModels(make) {
  selectedModels.clear();
  modelList.innerHTML = '';
  modelLabelEl.innerHTML = 'Select model…';
  if (!make) {
    modelTrigger.classList.add('disabled');
    modelTrigger.tabIndex = -1;
    modelDrop.classList.remove('open');
    modelTrigger.classList.remove('open');
    return;
  }
  modelTrigger.classList.remove('disabled');
  modelTrigger.tabIndex = 0;
  getModels(make).forEach(m => {
    const id   = 'hkm-' + m.replace(/[^a-z0-9]/gi, '_');
    const item = document.createElement('label');
    item.className = 'hk-multi-item';
    item.htmlFor   = id;
    const cb = document.createElement('input');
    cb.type = 'checkbox'; cb.id = id; cb.value = m;
    cb.addEventListener('change', onModelChange);
    item.appendChild(cb);
    item.appendChild(document.createTextNode(titleCase(m)));
    modelList.appendChild(item);
  });
}

function populateYears(make, models) {
  selYear.innerHTML = '<option value="all">All years</option>';
  const arr = Array.isArray(models) ? models : [];
  selYear.disabled = !make || arr.length === 0;
  if (!make || arr.length === 0) return;
  uniq(VEHICLES.filter(v => v.make === make && arr.includes(v.model)).map(v => v.year))
    .forEach(y => {
      const o = document.createElement('option');
      o.value = y; o.textContent = y;
      selYear.appendChild(o);
    });
}

function updateRegionFilter(basePool) {
  const regions = uniq(basePool.map(v => v.region).filter(Boolean));
  regPills.innerHTML = '<span class="hk-pill active" data-g="region" data-v="all">All</span>';
  regions.forEach(r => {
    const sp = document.createElement('span');
    sp.className = 'hk-pill';
    sp.dataset.g = 'region';
    sp.dataset.v = r;
    sp.textContent = regionLabel(r);
    regPills.appendChild(sp);
  });
  regFilter.style.display = regions.length > 1 ? '' : 'none';
  // re-attach pill listeners
  regPills.querySelectorAll('.hk-pill').forEach(pill => {
    pill.addEventListener('click', pillClick);
  });
}

// ── Trend chart ────────────────────────────────────────────────────────────
function renderTrend(pool) {
  // Build month buckets from pool (use all records for trend, ignore region/doc/odo filters)
  const byMonth = {};
  pool.forEach(v => {
    if (!v.month) return;
    if (!byMonth[v.month]) byMonth[v.month] = [];
    byMonth[v.month].push(v.price);
  });

  const months = Object.keys(byMonth).sort();
  if (months.length < 2) {
    trendWrap.style.display = 'none';
    return;
  }
  trendWrap.style.display = '';

  const W = 820, H = 180, PAD_L = 48, PAD_R = 16, PAD_T = 20, PAD_B = 48;
  const chartW = W - PAD_L - PAD_R;
  const chartH = H - PAD_T - PAD_B;
  const barW   = Math.max(8, Math.floor(chartW / months.length) - 4);

  const avgs = months.map(m => avg(byMonth[m]));
  const maxV = Math.max(...avgs);
  const minV = Math.min(...avgs);
  const scale = v => chartH - ((v - minV) / (maxV - minV || 1)) * (chartH * 0.85);

  let bars = '', labels = '', values = '', axis = '';

  months.forEach((m, i) => {
    const x = PAD_L + (i / (months.length - 1 || 1)) * chartW - barW / 2;
    const a = avgs[i];
    const barH = Math.max(4, chartH - scale(a));
    const y = PAD_T + scale(a);
    const dim = byMonth[m].length < 3;
    const fill = dim ? 'rgba(240,165,0,.25)' : 'rgba(240,165,0,.75)';
    bars   += `<rect x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${barW}" height="${barH.toFixed(1)}" rx="3" fill="${fill}"/>`;

    // x-axis label: show every Nth label to avoid crowding
    const step = months.length > 12 ? 3 : months.length > 6 ? 2 : 1;
    if (i % step === 0 || i === months.length - 1) {
      const [yr, mo] = m.split('-');
      const mon = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][parseInt(mo,10)];
      labels += `<text class="hk-bar-label" x="${(x + barW/2).toFixed(1)}" y="${H - 8}" text-anchor="middle">${mon} '${yr.slice(2)}</text>`;
    }

    // value on hover — skip for crowded charts, just show on tallest
    if (months.length <= 8) {
      values += `<text class="hk-bar-value" x="${(x + barW/2).toFixed(1)}" y="${(y - 5).toFixed(1)}" text-anchor="middle">${fmt(a)}</text>`;
    }
  });

  axis = `<line class="hk-axis-line" x1="${PAD_L}" y1="${PAD_T + chartH}" x2="${W - PAD_R}" y2="${PAD_T + chartH}"/>`;

  trendChart.innerHTML = `
    <svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;min-width:360px;">
      ${axis}${bars}${labels}${values}
    </svg>
    <p style="font-size:13px;color:var(--text-muted);margin-top:8px;">
      Muted bars = fewer than 3 sales that month. Monthly average price shown.
    </p>`;
}

// ── Regional breakdown table ───────────────────────────────────────────────
function renderRegional(basePool, overallTrimAvg) {
  // Group by region using the base pool (key+start+doc+odo filters, but all regions)
  const byRegion = {};
  basePool.forEach(v => {
    if (!v.region) return;
    if (!byRegion[v.region]) byRegion[v.region] = [];
    byRegion[v.region].push(v.price);
  });

  const regions = Object.keys(byRegion).filter(r => byRegion[r].length >= 3).sort();
  if (regions.length < 2) {
    regWrap.style.display = 'none';
    return;
  }
  regWrap.style.display = '';

  const rows = regions.map(r => {
    const prices  = byRegion[r].sort((a,b) => a - b);
    const n       = prices.length;
    const rAvg    = avg(prices);
    const rTrimmed= n >= 3 ? prices.slice(1, -1) : prices;
    const rTrimAvg= avg(rTrimmed);
    const rMed    = median(prices);
    const delta   = rTrimAvg - overallTrimAvg;
    const pct     = ((delta / overallTrimAvg) * 100).toFixed(0);
    const dClass  = delta > 0 ? 'hk-delta-pos' : delta < 0 ? 'hk-delta-neg' : 'hk-delta-neu';
    const dSign   = delta >= 0 ? '+' : '';
    return `<tr>
      <td>${regionLabel(r)}</td>
      <td class="num">${n}</td>
      <td class="num">${fmt(rTrimAvg)}</td>
      <td class="num">${fmt(rMed)}</td>
      <td class="num"><span class="${dClass}">${dSign}${pct}%</span></td>
    </tr>`;
  }).join('');

  regTable.innerHTML = `
    <thead><tr>
      <th>Region</th>
      <th class="num">Sales</th>
      <th class="num">Trimmed Avg</th>
      <th class="num">Median</th>
      <th class="num">vs Overall</th>
    </tr></thead>
    <tbody>${rows}</tbody>`;
}

// ── Condition signals ──────────────────────────────────────────────────────
function conditionSignals(basePool) {
  const pAll   = basePool.map(v => v.price);
  if (pAll.length < 4) return '';

  const allAvg = avg(pAll);

  const keyPool   = basePool.filter(v => v.has_key).map(v => v.price);
  const noKeyPool = basePool.filter(v => v.no_key).map(v => v.price);
  const startsPool= basePool.filter(v => v.starts).map(v => v.price);

  let html = '';

  if (keyPool.length >= 3 && noKeyPool.length >= 3) {
    const kAvg  = avg(keyPool);
    const nkAvg = avg(noKeyPool);
    const diff  = ((kAvg - nkAvg) / nkAvg * 100).toFixed(0);
    html += `<div class="hk-signal">
      <strong>Key vs No Key</strong>
      Has Key avg: ${fmt(kAvg)} &nbsp;·&nbsp; No Key avg: ${fmt(nkAvg)}<br>
      <span class="pos">Key premium: +${diff}%</span>
    </div>`;
  }

  if (startsPool.length >= 3) {
    const sAvg = avg(startsPool);
    const diff = ((sAvg - allAvg) / allAvg * 100).toFixed(0);
    const cls  = diff >= 0 ? 'pos' : 'neg';
    const sign = diff >= 0 ? '+' : '';
    html += `<div class="hk-signal">
      <strong>Verified Starts</strong>
      Starts avg: ${fmt(sAvg)} &nbsp;·&nbsp; All avg: ${fmt(allAvg)}<br>
      <span class="${cls}">${sign}${diff}% vs all records</span>
    </div>`;
  }

  return html;
}

// ── Main calculate & render ────────────────────────────────────────────────
function hkToggleRawData() {
  const el = document.getElementById('hk-raw-data');
  if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
}

function buildRawRows(pool) {
  return [...pool]
    .sort((a, b) => b.price - a.price)
    .map(v => {
      const key    = v.has_key ? '✓' : v.no_key ? '✗' : '—';
      const starts = v.starts  ? '✓' : '—';
      const odo    = v.odo > 0 ? v.odo.toLocaleString() : '—';
      const model  = titleCase(v.model);
      const region = regionLabel(v.region) || '—';
      const month  = v.month || '—';
      return `<tr>
        <td>${v.year}</td>
        <td>${model}</td>
        <td class="num">${fmt(v.price)}</td>
        <td>${region}</td>
        <td class="num">${odo}</td>
        <td>${key}</td>
        <td>${starts}</td>
        <td>${v.doc || '—'}</td>
        <td>${month}</td>
      </tr>`;
    }).join('');
}

function buildYearCompareCard(yr, make, models) {
  const pool = applyFilters(VEHICLES.filter(v =>
    v.make === make && models.includes(v.model) && v.year === yr
  ));
  if (pool.length === 0) return '';
  const prices  = pool.map(v => v.price).sort((a, b) => a - b);
  const n       = prices.length;
  const trimmed = n >= 6 ? prices.slice(1, -1) : prices;
  const trimAvg = avg(trimmed);
  const reserve = trimAvg * 0.90;
  return `
    <div class="hk-compare-card">
      <div class="hk-compare-year">${yr}</div>
      <div class="hk-compare-n">${n} sale${n !== 1 ? 's' : ''}</div>
      <div class="hk-compare-row"><span>Range</span><span>${fmt(prices[0])} &ndash; ${fmt(prices[n-1])}</span></div>
      <div class="hk-compare-row"><span>Trimmed Avg</span><span>${fmt(trimAvg)}</span></div>
      <div class="hk-compare-reserve">${fmt(reserve)} reserve</div>
    </div>`;
}

function buildCompareSection(yr, make, models) {
  const prev = buildYearCompareCard(yr - 1, make, models);
  const next = buildYearCompareCard(yr + 1, make, models);
  if (!prev && !next) return '';
  return `
    <div class="hk-compare-wrap">
      <div class="hk-compare-label">Neighboring Years</div>
      <div class="hk-compare-grid">${prev}${next}</div>
    </div>`;
}

function calculate() {
  const make   = selMake.value;
  const models = [...selectedModels];
  const year   = selYear.value;

  if (!make || models.length === 0) {
    results.innerHTML = `
      <div class="hk-card hk-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
        </svg>
        <p>Select a make and model to see pricing data.</p>
      </div>`;
    trendWrap.style.display  = 'none';
    regWrap.style.display    = 'none';
    regFilter.style.display  = 'none';
    return;
  }

  // Base pool: make + model(s) + year only (for trend + region filter + signals)
  let basePool = VEHICLES.filter(v => v.make === make && models.includes(v.model));
  if (year !== 'all') basePool = basePool.filter(v => v.year === parseInt(year, 10));

  // Update region filter based on base pool
  updateRegionFilter(basePool);

  // Filtered pool: all filters applied
  const pool = applyFilters([...basePool]);

  if (pool.length === 0) {
    results.innerHTML = `
      <div class="hk-card hk-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/><path d="M12 16h.01M12 8v4"/>
        </svg>
        <p>No sales match those filters. Try broadening the selection.</p>
      </div>`;
    trendWrap.style.display = 'none';
    regWrap.style.display   = 'none';
    return;
  }

  const prices   = pool.map(v => v.price).sort((a, b) => a - b);
  const n        = prices.length;
  const rawAvg   = avg(prices);
  const trimmed  = n >= 6 ? prices.slice(1, -1) : prices;
  const trimAvg  = avg(trimmed);
  const med      = median(prices);
  const reserve  = trimAvg * 0.90;

  const allYrs = uniq(VEHICLES.filter(v => v.make === make && models.includes(v.model)).map(v => v.year));
  const yearLabel = year !== 'all'
    ? String(year)
    : (allYrs.length > 1 ? allYrs[0] + '–' + allYrs[allYrs.length - 1] : allYrs[0]);

  const lowSample  = n < 10;
  const score      = conditionScore(S.key, S.start);
  const scoreLabel = SCORE_LABELS[score];
  const signals    = conditionSignals(basePool);

  results.innerHTML = `
    <div class="hk-card">
      <div class="hk-results-header">
        <span class="hk-results-title">${yearLabel} ${titleCase(make)} ${models.length === 1 ? titleCase(models[0]) : models.length + ' models'}</span>
        <button class="hk-count-btn${lowSample ? ' warn' : ''}" onclick="hkToggleRawData()">
          ${lowSample ? '⚠ ' : ''}${n} sale${n !== 1 ? 's' : ''}${lowSample ? ' — small sample' : ''}
        </button>
      </div>

      <div class="hk-stat-grid">
        <div class="hk-stat">
          <div class="hk-stat-label">Price Range</div>
          <div class="hk-stat-value" style="font-size:1.05rem;">${fmt(prices[0])}&nbsp;&ndash;&nbsp;${fmt(prices[n-1])}</div>
          <div class="hk-stat-sub">low &rarr; high</div>
        </div>
        <div class="hk-stat">
          <div class="hk-stat-label">Average</div>
          <div class="hk-stat-value">${fmt(rawAvg)}</div>
          <div class="hk-stat-sub">all ${n} sales</div>
        </div>
        <div class="hk-stat">
          <div class="hk-stat-label">Trimmed Avg</div>
          <div class="hk-stat-value">${fmt(trimAvg)}</div>
          <div class="hk-stat-sub">hi + lo excluded</div>
        </div>
        <div class="hk-stat">
          <div class="hk-stat-label">Median</div>
          <div class="hk-stat-value">${fmt(med)}</div>
          <div class="hk-stat-sub">midpoint</div>
        </div>
      </div>

      <div class="hk-reserve">
        <div class="hk-reserve-left">
          <div class="hk-reserve-label">Recommended Reserve</div>
          <div class="hk-reserve-value">${fmt(reserve)}</div>
          <div class="hk-score-row">
            <div class="hk-score-dots">${scoreDots(score)}</div>
            <span class="hk-score-label">${scoreLabel}</span>
          </div>
        </div>
        <div class="hk-reserve-note">
          10% below trimmed average of ${fmt(trimAvg)}<br>
          Sets a floor the market regularly clears
        </div>
      </div>

      ${signals ? `<div class="hk-signals">${signals}</div>` : ''}
    </div>

    <div class="hk-raw-data" id="hk-raw-data" style="display:none">
      <div class="hk-card">
        <div class="hk-card-title" style="margin-bottom:16px">All ${n} Sales</div>
        <div style="overflow-x:auto">
          <table class="hk-raw-table">
            <thead><tr>
              <th>Year</th>
              <th>Model</th>
              <th class="num">Price</th>
              <th>Region</th>
              <th class="num">Odometer</th>
              <th>Key</th>
              <th>Starts</th>
              <th>Doc</th>
              <th>Month</th>
            </tr></thead>
            <tbody>${buildRawRows(pool)}</tbody>
          </table>
        </div>
      </div>
    </div>
    ${lowSample && year !== 'all' ? buildCompareSection(parseInt(year, 10), make, models) : ''}`;

  // Trend chart uses base pool (unfiltered by doc/region/odo) for full history
  renderTrend(basePool);

  // Regional table uses pool with key/start/doc/odo but ignores region filter
  const poolForRegions = applyFiltersExcept(basePool, 'region');
  renderRegional(poolForRegions, trimAvg);
}

function applyFiltersExcept(pool, skip) {
  let p = [...pool];
  if (skip !== 'key') {
    if (S.key === 'has_key') p = p.filter(v => v.has_key);
    if (S.key === 'no_key')  p = p.filter(v => v.no_key);
  }
  if (skip !== 'start' && S.start === 'starts') p = p.filter(v => v.starts);
  if (skip !== 'doc'   && S.doc !== 'all')       p = p.filter(v => v.doc === S.doc);
  if (skip !== 'odo') {
    if (S.odo === 'u100')    p = p.filter(v => v.odo > 0 && v.odo < 100000);
    if (S.odo === '100_150') p = p.filter(v => v.odo >= 100000 && v.odo <= 150000);
    if (S.odo === 'o150')    p = p.filter(v => v.odo > 150000);
  }
  return p;
}

// ── Pill click handler ─────────────────────────────────────────────────────
function pillClick() {
  const pill = this;
  const g    = pill.dataset.g;
  document.querySelectorAll(`.hk-pill[data-g="${g}"]`).forEach(p => p.classList.remove('active'));
  pill.classList.add('active');
  S[g] = pill.dataset.v;
  updateActiveChips();
  calculate();
}

// ── VIN decoder ────────────────────────────────────────────────────────────
const vinInput  = document.getElementById('hk-vin');
const vinBtn    = document.getElementById('hk-vin-btn');
const vinResult = document.getElementById('hk-vin-result');

const VIN_RE = /^[A-HJ-NPR-Z0-9]{17}$/i;

function setDropdown(sel, value) {
  for (const opt of sel.options) {
    if (opt.value.toUpperCase() === value.toUpperCase()) {
      sel.value = opt.value;
      return true;
    }
  }
  return false;
}

async function decodeVin() {
  const vin = vinInput.value.trim().toUpperCase();
  if (!VIN_RE.test(vin)) {
    vinResult.className = 'hk-vin-result error';
    vinResult.textContent = 'VIN must be exactly 17 characters (no I, O, or Q).';
    vinResult.style.display = '';
    return;
  }

  vinBtn.disabled   = true;
  vinBtn.textContent = 'Decoding…';
  vinResult.style.display = 'none';

  try {
    const res  = await fetch(`https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/${vin}?format=json`);
    const data = await res.json();
    const vals = {};
    (data.Results || []).forEach(r => { vals[r.Variable] = r.Value; });

    const make  = (vals['Make']        || '').toUpperCase();
    const model = (vals['Model']       || '').toUpperCase();
    const year  = (vals['Model Year']  || '');
    const body  = vals['Body Class']   || '';
    const eng   = vals['Displacement (L)'] ? vals['Displacement (L)'] + 'L' : '';
    const cyl   = vals['Engine Number of Cylinders'] || '';
    const plant = vals['Plant City']   || '';

    if (!make || !model) {
      vinResult.className = 'hk-vin-result error';
      vinResult.textContent = 'Could not decode this VIN. Verify and try again.';
      vinResult.style.display = '';
      vinBtn.disabled    = false;
      vinBtn.textContent = 'Decode';
      return;
    }

    // Try to auto-fill dropdowns
    if (setDropdown(selMake, make)) {
      selMake.dispatchEvent(new Event('change'));
      await new Promise(r => setTimeout(r, 50));
      // Find and check the matching model checkbox
      const modelUpper = model.toUpperCase();
      const cb = [...modelList.querySelectorAll('input')].find(
        i => i.value.toUpperCase() === modelUpper
      );
      if (cb) {
        cb.checked = true;
        onModelChange();
        await new Promise(r => setTimeout(r, 50));
        if (year) setDropdown(selYear, year);
        selYear.dispatchEvent(new Event('change'));
      }
    }

    const chips = [make, model, year, body, eng ? eng + (cyl ? ' ' + cyl + '-cyl' : '') : '', plant]
      .filter(Boolean)
      .map(v => `<span class="hk-vin-chip">${v}</span>`)
      .join('');

    vinResult.className    = 'hk-vin-result';
    vinResult.innerHTML    = chips;
    vinResult.style.display = '';

  } catch (e) {
    vinResult.className    = 'hk-vin-result error';
    vinResult.textContent  = 'Decode failed. Check your network and try again.';
    vinResult.style.display = '';
  }

  vinBtn.disabled    = false;
  vinBtn.textContent = 'Decode';
}

vinBtn.addEventListener('click', decodeVin);
vinInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') decodeVin(); });

// ── Events ─────────────────────────────────────────────────────────────────
selMake.addEventListener('change', () => {
  populateModels(selMake.value);
  populateYears('', []);
  S.region = 'all';
  calculate();
});
selYear.addEventListener('change', calculate);

// Multi-select model dropdown open/close
modelTrigger.addEventListener('click', () => {
  const open = modelDrop.classList.toggle('open');
  modelTrigger.classList.toggle('open', open);
});
document.addEventListener('click', (e) => {
  if (!modelWrap.contains(e.target)) {
    modelDrop.classList.remove('open');
    modelTrigger.classList.remove('open');
  }
});
modelBtnAll.addEventListener('click', (e) => {
  e.stopPropagation();
  modelList.querySelectorAll('input').forEach(cb => { cb.checked = true; });
  onModelChange();
});
modelBtnClr.addEventListener('click', (e) => {
  e.stopPropagation();
  modelList.querySelectorAll('input').forEach(cb => { cb.checked = false; });
  onModelChange();
});

document.querySelectorAll('.hk-pill').forEach(pill => {
  pill.addEventListener('click', pillClick);
});

// ── Filter toggle ──────────────────────────────────────────────────────────
const FILTER_LABELS = {
  key:    { has_key: 'Has Key', no_key: 'No Key' },
  start:  { starts: 'Verified Starts' },
  doc:    { Title: 'Title', Lien: 'Lien', Salvage: 'Salvage', Abandoned: 'Abandoned',
            'Court Order': 'Court Order', Junk: 'Junk', Other: 'Other' },
  odo:    { u100: 'Under 100k mi', '100_150': '100k–150k mi', o150: 'Over 150k mi' },
  region: {},
};

function updateActiveChips() {} // filters always visible in sidebar — no-op

// Accordion toggle
document.querySelectorAll('.hk-acc-head').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.hk-acc').classList.toggle('open'));
});

// ── Init: fetch pre-built data ─────────────────────────────────────────────
(async () => {
  const loadingEl = document.getElementById('hk-loading');
  selMake.disabled = true;

  try {
    const res = await fetch('/data/amr-data.json?v=<?= $amr_data_version ?>');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    VEHICLES = decodeRecords(data);
  } catch (e) {
    if (loadingEl) {
      loadingEl.innerHTML = `
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.4;">
          <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
        </svg>
        <p style="color:#e05a5a;">Data not available. Run <code>php build-amr-data.php</code> to generate it.</p>`;
    }
    return;
  }

  selMake.disabled = false;
  if (loadingEl) {
    loadingEl.innerHTML = `
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
      </svg>
      <p>Select a make and model to see pricing data.</p>`;
  }

  populateMakes();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
