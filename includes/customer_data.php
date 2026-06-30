<?php
// Shared access gate + seller aggregation for the customer-facing reports
// (Customer Results, 90-Day Activity). Requires a started session + config.

const CR_ACCESS_CODE = '119607';

// Processes the 4-digit code form. Returns [bool $ok, string $error].
function amr_customer_gate(): array {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cr_code'])) {
        if (preg_replace('/\D/', '', $_POST['cr_code']) === CR_ACCESS_CODE) {
            $_SESSION['cr_auth'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        $err = 'Incorrect code. Please try again.';
    }
    return [!empty($_SESSION['cr_auth']), $err];
}

// Aggregates the dataset into per-seller monthly activity and returns a compact
// JSON payload: { months:[...], customers:[{name,first,last,total,active,counts}], dataDate }.
function amr_customer_payload(): string {
    $empty = '{"months":[],"customers":[],"dataDate":""}';
    $file  = __DIR__ . '/../data/amr-data.json';
    $data  = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
    if (!$data || empty($data['records'])) return $empty;

    $S = $data['sellers'] ?? [];
    $M = $data['months']  ?? [];
    $agg = [];
    $monthSet = [];
    foreach ($data['records'] as $r) {
        $si = $r[9] ?? -1; $mi = $r[8] ?? -1;
        if ($si < 0 || $mi < 0) continue;
        $m = $M[$mi];
        $agg[$si][$m] = ($agg[$si][$m] ?? 0) + 1;
        $monthSet[$m] = true;
    }
    $months = array_keys($monthSet);
    sort($months);

    $customers = [];
    foreach ($agg as $si => $counts) {
        ksort($counts);
        $active = array_keys($counts);
        $customers[] = [
            'name'   => (string) ($S[$si] ?? 'Unknown'),
            'first'  => $active[0],
            'last'   => end($active),
            'total'  => array_sum($counts),
            'active' => count($counts),
            'counts' => $counts,
        ];
    }

    $metaFile = __DIR__ . '/../data/amr-meta.json';
    $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [];

    return json_encode(
        ['months' => $months, 'customers' => $customers, 'dataDate' => $meta['data_date'] ?? ''],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    ) ?: $empty;
}
