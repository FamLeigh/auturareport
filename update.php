<?php
session_start();

// ── Auth gate (same password as AMR page) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['heckle_pass'])) {
    if ($_POST['heckle_pass'] === 'heckle') {
        $_SESSION['amr_auth'] = true;
    }
}

if (empty($_SESSION['amr_auth'])) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AMR Update</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0e0e10; color: #e8e8e0; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .gate { background: #18181b; border: 1px solid #2a2a2e; border-radius: 12px; padding: 40px 36px; width: 100%; max-width: 360px; }
  .gate h1 { font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; }
  .gate p { font-size: 13px; color: #888; margin-bottom: 24px; }
  .gate input { width: 100%; background: #0e0e10; border: 1px solid #2a2a2e; border-radius: 8px; color: #e8e8e0; font-size: 15px; padding: 11px 14px; margin-bottom: 12px; }
  .gate input:focus { outline: none; border-color: #f0a500; }
  .gate button { width: 100%; background: #f0a500; border: none; border-radius: 8px; color: #000; font-size: 14px; font-weight: 700; padding: 12px; cursor: pointer; }
  .gate .err { font-size: 12px; color: #e05a5a; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="gate">
  <h1>AMR Data Update</h1>
  <p>Enter the access password to continue.</p>
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p class="err">Incorrect password.</p>
  <?php endif; ?>
  <form method="POST">
    <input type="password" name="heckle_pass" placeholder="Password" autofocus>
    <button type="submit">Enter</button>
  </form>
</div>
</body>
</html>
<?php exit; }

// ── Handle upload ─────────────────────────────────────────────────────────
$result   = null;
$error    = null;
$output   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file      = $_FILES['csv_file'];
    $data_date = trim($_POST['data_date'] ?? '');

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed (error code ' . $file['error'] . ').';
    } elseif ($file['size'] > 52428800) {
        $error = 'File too large. Max 50 MB.';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = 'Only CSV files are accepted.';
    } else {
        $heckel_dir = __DIR__ . '/data/heckel';

        // Remove existing CSVs
        foreach (glob($heckel_dir . '/*.csv') as $old) {
            unlink($old);
        }

        // Save new file with sanitized name
        $safe_name = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($file['name']));
        $dest      = $heckel_dir . '/' . $safe_name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Failed to save uploaded file.';
        } else {
            // Run build script, pass date if provided
            $date_arg = $data_date !== '' ? ' --date=' . escapeshellarg($data_date) : '';
            $cmd      = 'cd ' . escapeshellarg(__DIR__) . ' && php build-amr-data.php' . $date_arg . ' 2>&1';
            $output   = shell_exec($cmd) ?? '';

            if (strpos($output, 'Done.') !== false) {
                $result = 'success';
            } else {
                $error = 'Build script did not complete successfully. See output below.';
            }
        }
    }
}

// ── Current meta ──────────────────────────────────────────────────────────
$meta_file = __DIR__ . '/data/amr-meta.json';
$meta      = file_exists($meta_file) ? json_decode(file_get_contents($meta_file), true) : [];
$cur_count = $meta['count']     ?? 0;
$cur_date  = $meta['data_date'] ?? '';
$cur_built = $meta['built']     ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AMR — Update Data</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0e0e10; color: #e8e8e0; font-family: system-ui, sans-serif; padding: 48px 24px; }
  .wrap { max-width: 640px; margin: 0 auto; }
  h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; }
  .back { font-size: 13px; color: #888; text-decoration: none; display: inline-block; margin-bottom: 32px; }
  .back:hover { color: #f0a500; }
  .card { background: #18181b; border: 1px solid #2a2a2e; border-radius: 12px; padding: 28px 32px; margin-bottom: 20px; }
  .card-title { font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #888; margin-bottom: 18px; }
  .meta-row { display: flex; gap: 32px; flex-wrap: wrap; }
  .meta-item label { display: block; font-size: 10px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #666; margin-bottom: 5px; }
  .meta-item span { font-size: 1rem; font-weight: 600; color: #e8e8e0; }
  .field { margin-bottom: 16px; }
  .field label { display: block; font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: #888; margin-bottom: 8px; }
  .field input[type=text], .field input[type=file] {
    width: 100%; background: #0e0e10; border: 1px solid #2a2a2e; border-radius: 8px;
    color: #e8e8e0; font-size: 14px; padding: 10px 14px;
  }
  .field input:focus { outline: none; border-color: #f0a500; }
  button[type=submit] {
    background: #f0a500; border: none; border-radius: 8px; color: #000;
    font-size: 14px; font-weight: 700; padding: 12px 28px; cursor: pointer;
    margin-top: 4px;
  }
  button[type=submit]:hover { opacity: .85; }
  .notice { padding: 14px 18px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
  .notice.success { background: rgba(94,201,124,.1); border: 1px solid rgba(94,201,124,.3); color: #5ec97c; }
  .notice.error   { background: rgba(224,90,90,.1);  border: 1px solid rgba(224,90,90,.3);  color: #e05a5a; }
  .output { background: #0e0e10; border: 1px solid #2a2a2e; border-radius: 8px; padding: 16px; font-family: monospace; font-size: 12px; color: #aaa; white-space: pre-wrap; margin-top: 16px; max-height: 300px; overflow-y: auto; }
  .note { font-size: 12px; color: #666; margin-top: 12px; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="/">&larr; Back to AMR</a>
  <h1>Update AMR Data</h1>

  <?php if ($result === 'success'): ?>
    <div class="notice success">Build complete. Data updated successfully.</div>
    <?php if ($output): ?><div class="output"><?= htmlspecialchars($output) ?></div><?php endif; ?>
  <?php elseif ($error): ?>
    <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php if ($output): ?><div class="output"><?= htmlspecialchars($output) ?></div><?php endif; ?>
  <?php endif; ?>

  <!-- Current dataset -->
  <div class="card">
    <div class="card-title">Current Dataset</div>
    <div class="meta-row">
      <div class="meta-item">
        <label>Records</label>
        <span><?= $cur_count > 0 ? number_format($cur_count) : '—' ?></span>
      </div>
      <div class="meta-item">
        <label>Data Date</label>
        <span><?= $cur_date !== '' ? htmlspecialchars($cur_date) : '—' ?></span>
      </div>
      <div class="meta-item">
        <label>Last Built</label>
        <span><?= $cur_built !== '' ? htmlspecialchars($cur_built) : '—' ?></span>
      </div>
    </div>
  </div>

  <!-- Upload form -->
  <div class="card">
    <div class="card-title">Upload New Dataset</div>
    <form method="POST" enctype="multipart/form-data">
      <div class="field">
        <label>CSV File</label>
        <input type="file" name="csv_file" accept=".csv" required>
      </div>
      <div class="field">
        <label>Data Date (e.g. May 18, 2026)</label>
        <input type="text" name="data_date" placeholder="May 18, 2026" value="<?= htmlspecialchars($_POST['data_date'] ?? '') ?>">
      </div>
      <button type="submit">Upload &amp; Rebuild</button>
    </form>
    <p class="note">
      Uploading a new file replaces the current dataset entirely.<br>
      Max file size: 50 MB. The build process may take 10–30 seconds.
    </p>
  </div>
</div>
</body>
</html>
