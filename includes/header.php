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

<header class="site-header" id="site-header">
  <nav class="nav-inner" style="justify-content:flex-start;gap:16px;">
    <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
      <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect width="32" height="32" rx="6" fill="#f0a500"/>
        <path d="M8 22L16 10L24 22H19L16 17L13 22H8Z" fill="#000" opacity=".85"/>
      </svg>
      <span style="font-size:.95rem;font-weight:700;color:var(--text,#e8e8e0);letter-spacing:-.01em;">Autura Marketplace Report</span>
    </a>
    <span style="font-size:11px;font-weight:600;background:rgba(240,165,0,.15);color:#f0a500;border:1px solid rgba(240,165,0,.3);border-radius:4px;padding:2px 8px;letter-spacing:.04em;">BETA</span>
  </nav>
</header>

<main id="main">
