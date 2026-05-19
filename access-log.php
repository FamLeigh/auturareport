<?php
require_once __DIR__ . '/includes/auth.php';

$log_file = __DIR__ . '/data/access-log.json';
$log = file_exists($log_file) ? (json_decode(file_get_contents($log_file), true) ?? []) : [];
?>
<script>(function(){var t=localStorage.getItem('amr-theme')||'light';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();</script>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AMR — Access Log</title>
<style>
  :root { --bg:#ffffff;--surface:#f6f6f4;--border:#ddddd8;--text:#111110;--muted:#666;--subtle:#999;--row-hover:#f6f6f4;--row-border:rgba(0,0,0,.06); }
  [data-theme="dark"] { --bg:#0c0c0c;--surface:#141414;--border:#2a2a2a;--text:#f0ede8;--muted:#888;--subtle:#666;--row-hover:#1a1a1e;--row-border:rgba(255,255,255,.05); }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: system-ui, sans-serif; padding: 48px 24px; }
  .wrap { max-width: 860px; margin: 0 auto; }
  .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
  .back { font-size: 13px; color: var(--muted); text-decoration: none; }
  .back:hover { color: #f0a500; }
  .theme-btn { background: none; border: 1px solid var(--border); border-radius: 6px; color: var(--muted); cursor: pointer; width: 32px; height: 32px; font-size: 15px; display: flex; align-items: center; justify-content: center; }
  .theme-btn:hover { border-color: #f0a500; color: #f0a500; }
  h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; }
  .sub { font-size: 13px; color: var(--subtle); margin-bottom: 28px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  thead th { font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border); }
  tbody td { padding: 12px 20px; border-bottom: 1px solid var(--row-border); color: var(--text); white-space: nowrap; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: var(--row-hover); }
  .email-cell { font-weight: 500; }
  .ip-cell { font-family: monospace; font-size: 13px; color: var(--muted); }
  .time-cell { color: var(--muted); font-size: 13px; }
  .empty { padding: 48px 20px; text-align: center; color: var(--subtle); font-size: 14px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top-bar">
    <a class="back" href="/">&larr; Back to AMR</a>
    <button class="theme-btn" id="theme-btn" aria-label="Toggle theme">🌙</button>
  </div>
  <h1>Access Log</h1>
  <p class="sub"><?= count($log) ?> login<?= count($log) !== 1 ? 's' : '' ?> recorded &nbsp;&middot;&nbsp; Newest first</p>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Date / Time</th>
          <th>Email</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($log)): ?>
          <tr><td colspan="3" class="empty">No logins recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($log as $entry): ?>
          <tr>
            <td class="time-cell"><?= htmlspecialchars($entry['time'] ?? '') ?></td>
            <td class="email-cell"><?= htmlspecialchars($entry['email'] ?? '') ?></td>
            <td class="ip-cell"><?= htmlspecialchars($entry['ip'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
(function(){
  var btn = document.getElementById('theme-btn');
  function sync(){ btn.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? '☀' : '🌙'; }
  sync();
  btn.addEventListener('click', function(){
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (dark) { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('amr-theme','light'); }
    else { document.documentElement.setAttribute('data-theme','dark'); localStorage.setItem('amr-theme','dark'); }
    sync();
  });
})();
</script>
</body>
</html>
