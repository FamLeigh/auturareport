<?php
$_host = $_SERVER['HTTP_HOST'] ?? '';
$_is_local = (
    $_host === 'auturareport' ||
    strpos($_host, 'auturareport:') !== false ||
    strpos($_host, 'localhost') !== false ||
    strpos($_host, '127.0.0.1') !== false
) || php_sapi_name() === 'cli';

define('SITE_NAME',    'Autura Marketplace Report');
define('SITE_TAGLINE', 'Vehicle valuation data from Autura marketplace sales.');
define('SITE_URL',     $_is_local ? 'https://auturareport:8890' : 'https://auturareport.com');
define('CONTACT_EMAIL', 'kleigh@autura.com');

// Auto sign-out after this many seconds of inactivity.
define('AMR_IDLE_TIMEOUT', 30 * 60); // 30 minutes

// Default/bootstrap password — used once to set a personal password, then retired per user.
define('AMR_DEFAULT_PASSWORD', 'heckle');
define('AMR_MIN_PASSWORD_LEN', 8);

// ── Disclaimer (single source of truth, used on every page + sign-in + print) ──
define('AMR_DISCLAIMER_POINTS', [
    'For internal informational purposes only — <strong>not financial, investment, or appraisal advice</strong>. Autura is not a financial advisor.',
    '<strong>Private &amp; confidential Autura data.</strong> Not to be shared, distributed, or reproduced outside of Autura.',
    'Aggregated from marketplace activity and <strong>subject to change without notice</strong>.',
    'Figures are estimates provided &ldquo;as is,&rdquo; without warranty of accuracy or completeness, and should not be the sole basis for any pricing, buying, or selling decision.',
]);
define('AMR_DISCLAIMER_SHORT', 'CONFIDENTIAL — Autura internal use only &middot; Informational only, not financial advice &middot; Aggregated data, subject to change without notice &middot; Do not distribute outside Autura.');

if ($_is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('America/New_York');
