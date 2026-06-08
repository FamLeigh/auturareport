<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Keep server-side session data alive at least as long as our idle window.
    ini_set('session.gc_maxlifetime', (string) AMR_IDLE_TIMEOUT);
    session_start();
}

$_auth_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['heckle_pass'])) {
    $email = strtolower(trim($_POST['heckle_email'] ?? ''));
    $pass  = $_POST['heckle_pass'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_auth_error = 'Enter a valid email address.';
    } elseif (!str_ends_with($email, '@autura.com')) {
        $_auth_error = 'Only @autura.com email addresses are allowed.';
    } elseif ($pass !== 'heckle') {
        $_auth_error = 'Incorrect password.';
    } elseif (empty($_POST['agree'])) {
        $_auth_error = 'Please read and acknowledge the disclaimer to continue.';
    } else {
        session_regenerate_id(true); // new session id on login (fixation defense)
        $_SESSION['amr_auth']  = true;
        $_SESSION['amr_email'] = $email;
        $_SESSION['amr_last']  = time();
        _amr_log($email, true);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

function _amr_log(string $email, bool $agreed = false): void {
    $raw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip  = trim(explode(',', $raw)[0]);
    $ua  = substr(trim($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
    $log_file = __DIR__ . '/../data/access-log.json';
    $entry = ['time' => date('Y-m-d H:i:s'), 'email' => $email, 'ip' => $ip, 'agreed' => $agreed, 'agent' => $ua];
    $fp = fopen($log_file, 'c+');
    if (!$fp) return;
    if (flock($fp, LOCK_EX)) {
        $size = fstat($fp)['size'] ?? 0;
        $log  = $size > 0 ? (json_decode(fread($fp, $size), true) ?? []) : [];
        array_unshift($log, $entry);
        if (count($log) > 5000) $log = array_slice($log, 0, 5000); // cap growth
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($log, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// ── Idle timeout: sign out after AMR_IDLE_TIMEOUT seconds of inactivity ────────
if (!empty($_SESSION['amr_auth'])) {
    if (isset($_SESSION['amr_last']) && (time() - $_SESSION['amr_last']) > AMR_IDLE_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cp = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cp['path'], $cp['domain'], $cp['secure'], $cp['httponly']);
        }
        session_destroy();
        session_start();
        $_auth_error = 'Your session timed out after inactivity. Please sign in again.';
    } else {
        $_SESSION['amr_last'] = time(); // activity → extend the window
        return;
    }
}

// ── Gate page ────────────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Autura Marketplace Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #f4f4f2; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .gate { background: #ffffff; border: 1px solid #ddddd8; border-radius: 14px; padding: 40px 36px; width: 100%; max-width: 380px; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
  .gate-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
  .gate-brand span { font-size: 13px; font-weight: 700; color: #111110; letter-spacing: -.01em; }
  .gate h1 { font-size: 1.05rem; font-weight: 700; color: #111110; margin-bottom: 6px; }
  .gate-sub { font-size: 13px; color: #888; margin-bottom: 24px; line-height: 1.5; }
  .err { font-size: 13px; color: #c0392b; background: rgba(192,57,43,.07); border: 1px solid rgba(192,57,43,.18); border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; }
  .field { margin-bottom: 14px; }
  .field label { display: block; font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: #999; margin-bottom: 6px; }
  .field input { width: 100%; background: #f8f8f6; border: 1px solid #ddddd8; border-radius: 8px; color: #111110; font-size: 15px; padding: 11px 14px; transition: border-color .15s; }
  .field input:focus { outline: none; border-color: #f0a500; background: #fff; }
  .gate button { width: 100%; background: #f0a500; border: none; border-radius: 8px; color: #000; font-size: 14px; font-weight: 700; padding: 13px; cursor: pointer; margin-top: 6px; transition: opacity .15s; }
  .gate button:hover { opacity: .85; }
  .disc { background: #f8f8f6; border: 1px solid #ddddd8; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; max-height: 168px; overflow-y: auto; }
  .disc-title { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #999; margin-bottom: 8px; }
  .disc ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
  .disc li { font-size: 11.5px; line-height: 1.5; color: #555; padding-left: 14px; position: relative; }
  .disc li::before { content: "•"; position: absolute; left: 0; color: #f0a500; }
  .agree { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 16px; font-size: 13px; line-height: 1.45; color: #333; cursor: pointer; }
  .agree input { margin-top: 2px; width: 16px; height: 16px; accent-color: #f0a500; flex-shrink: 0; cursor: pointer; }
</style>
</head>
<body>
<div class="gate">
  <div class="gate-brand">
    <svg width="28" height="28" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="6" fill="#f0a500"/><path d="M8 22L16 10L24 22H19L16 17L13 22H8Z" fill="#000" opacity=".85"/></svg>
    <span>Autura Marketplace Report</span>
  </div>
  <h1>Sign in to continue</h1>
  <p class="gate-sub">Use your Autura email and the access password.</p>
  <?php if ($_auth_error): ?>
    <div class="err"><?= htmlspecialchars($_auth_error) ?></div>
  <?php endif; ?>
  <form method="POST" novalidate>
    <div class="field">
      <label>Autura Email</label>
      <input type="email" name="heckle_email" placeholder="you@autura.com"
             value="<?= htmlspecialchars($_POST['heckle_email'] ?? '') ?>" autofocus required>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="heckle_pass" placeholder="Access password">
    </div>
    <div class="disc">
      <div class="disc-title">Please read &amp; acknowledge</div>
      <ul>
        <?php foreach (AMR_DISCLAIMER_POINTS as $pt): ?><li><?= $pt ?></li><?php endforeach; ?>
      </ul>
    </div>
    <label class="agree">
      <input type="checkbox" name="agree" value="1" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
      <span>I acknowledge and agree to the above — this is confidential Autura data, provided for information only and not as financial advice.</span>
    </label>
    <button type="submit">Enter</button>
  </form>
</div>
</body>
</html>
<?php exit;
