<?php
require_once __DIR__ . '/config.php';

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string {
    return '/' . ltrim($path, '/');
}

function active(string $page): string {
    $current = basename($_SERVER['SCRIPT_NAME'], '.php');
    return $current === $page ? ' active' : '';
}
