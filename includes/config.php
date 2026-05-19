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

if ($_is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('America/New_York');
