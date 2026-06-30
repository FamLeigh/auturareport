<script>
(function(){var t=localStorage.getItem('amr-theme')||'light';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
</script>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? h($page_title) . ' — ' . SITE_NAME : SITE_NAME ?></title>
  <meta name="description" content="<?= isset($meta_desc) ? h($meta_desc) : h(SITE_TAGLINE) ?>">
  <meta name="robots" content="noindex, nofollow">
  <?php if (isset($canonical)): ?>
  <link rel="canonical" href="<?= SITE_URL . h($canonical) ?>">
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
  <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="<?= isset($body_class) ? h($body_class) : '' ?>">

<div class="amr-watermark" aria-hidden="true">CONFIDENTIAL</div>
<div class="amr-print-disc amr-print-top">
  <?= AMR_DISCLAIMER_SHORT ?>
  <?php if (!empty($_SESSION['amr_email'])): ?><span class="amr-print-prep">Prepared for <?= htmlspecialchars($_SESSION['amr_email']) ?> &middot; <?= date('M j, Y g:i A T') ?></span><?php endif; ?>
</div>

<header class="site-header" id="site-header">
  <nav class="nav-inner" style="justify-content:space-between;gap:16px;">
    <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
      <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect width="32" height="32" rx="6" fill="#f0a500"/>
        <path d="M8 22L16 10L24 22H19L16 17L13 22H8Z" fill="#000" opacity=".85"/>
      </svg>
      <span style="font-size:.95rem;font-weight:700;color:var(--text);letter-spacing:-.01em;">Autura Marketplace Report</span>
    </a>
    <div style="display:flex;align-items:center;gap:14px;">
      <nav style="display:flex;gap:2px;flex-wrap:wrap;" id="site-nav">
        <?php
          $current_path = strtok($_SERVER['REQUEST_URI'], '?');
          $top_links = [
            '/'                     => 'Valuation',
            '/autura-market-report' => 'Market Report',
            '/customer-results'     => 'Seller-Results',
          ];
          $menus = [
            'Cust Data' => ['/customer-research' => 'Customer Research', '/impound-map' => 'Impound Map'],
            'Settings'  => ['/seller-groups' => 'Define Seller Groups', '/changelog' => 'Changelog', '/update' => 'Update Data'],
          ];
          foreach ($top_links as $href => $label):
        ?>
        <a href="<?= $href ?>" class="nav-top<?= $current_path === $href ? ' on' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
        <?php foreach ($menus as $name => $items): $anyActive = in_array($current_path, array_keys($items), true); ?>
        <span class="nav-dd">
          <button type="button" class="nav-dd-btn<?= $anyActive ? ' on' : '' ?>"><?= $name ?> <span style="font-size:9px">▾</span></button>
          <span class="nav-dd-menu">
            <?php foreach ($items as $href => $label): ?>
            <a href="<?= $href ?>" class="<?= $current_path === $href ? 'on' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
          </span>
        </span>
        <?php endforeach; ?>
      </nav>
      <span style="font-size:11px;font-weight:600;background:var(--accent-glow);color:var(--accent);border:1px solid rgba(240,165,0,.3);border-radius:4px;padding:2px 8px;letter-spacing:.04em;">BETA</span>
      <?php if (!empty($_SESSION['amr_email'])): ?>
      <a href="/logout" title="Signed in as <?= htmlspecialchars($_SESSION['amr_email']) ?>" style="font-size:12px;font-weight:600;color:var(--text-muted);text-decoration:none;border:1px solid var(--border);border-radius:6px;padding:5px 10px;white-space:nowrap;" onmouseover="this.style.color='var(--accent)';this.style.borderColor='var(--accent)'" onmouseout="this.style.color='var(--text-muted)';this.style.borderColor='var(--border)'">Log out</a>
      <?php endif; ?>
      <button class="theme-toggle" id="theme-toggle" aria-label="Toggle light/dark mode">
        <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
        <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
    </div>
  </nav>
</header>

<script>
(function(){
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;
  btn.addEventListener('click', function(){
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('amr-theme', 'light');
    } else {
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('amr-theme', 'dark');
    }
  });
})();
(function(){
  var btns = document.querySelectorAll('.nav-dd-btn');
  btns.forEach(function(b){
    b.addEventListener('click', function(e){
      e.stopPropagation();
      var dd = b.closest('.nav-dd'), open = dd.classList.contains('open');
      document.querySelectorAll('.nav-dd').forEach(function(d){ d.classList.remove('open'); });
      if (!open) dd.classList.add('open');
    });
  });
  document.addEventListener('click', function(){ document.querySelectorAll('.nav-dd').forEach(function(d){ d.classList.remove('open'); }); });
})();
</script>

<main id="main">
