<?php
/**
 * Shared US choropleth helper: projects assets/us-states.geojson into SVG path
 * strings keyed by 2-letter state abbreviation (lower-48 + DC in the main frame,
 * Alaska & Hawaii as insets). Returns [] if the geojson is missing.
 */

const AMR_MAP_STATE_ABBR = ['alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR','california'=>'CA','colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE','district of columbia'=>'DC','florida'=>'FL','georgia'=>'GA','hawaii'=>'HI','idaho'=>'ID','illinois'=>'IL','indiana'=>'IN','iowa'=>'IA','kansas'=>'KS','kentucky'=>'KY','louisiana'=>'LA','maine'=>'ME','maryland'=>'MD','massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS','missouri'=>'MO','montana'=>'MT','nebraska'=>'NE','nevada'=>'NV','new hampshire'=>'NH','new jersey'=>'NJ','new mexico'=>'NM','new york'=>'NY','north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH','oklahoma'=>'OK','oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI','south carolina'=>'SC','south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT','vermont'=>'VT','virginia'=>'VA','washington'=>'WA','west virginia'=>'WV','wisconsin'=>'WI','wyoming'=>'WY'];

function amr_state_paths(): array {
    $file = __DIR__ . '/../assets/us-states.geojson';
    if (!is_readable($file)) return [];
    $geo = json_decode(file_get_contents($file), true);
    if (!$geo || empty($geo['features'])) return [];
    $cosL = cos(deg2rad(37.5));
    $ringsOf = function ($g) {
        $t = $g['type'] ?? ''; $c = $g['coordinates'] ?? [];
        if ($t === 'Polygon') return $c;
        if ($t === 'MultiPolygon') { $r = []; foreach ($c as $poly) foreach ($poly as $ring) $r[] = $ring; return $r; }
        return [];
    };
    $main = []; $ak = null; $hi = null;
    foreach ($geo['features'] as $f) {
        $ab = AMR_MAP_STATE_ABBR[strtolower($f['properties']['name'] ?? '')] ?? null;
        if (!$ab) continue;
        if ($ab === 'AK') { $ak = ['AK' => $f]; continue; }
        if ($ab === 'HI') { $hi = ['HI' => $f]; continue; }
        $main[$ab] = $f;
    }
    $paths = [];
    $emit = function ($feats, $rect, $clipLonGt = null) use (&$paths, $ringsOf, $cosL) {
        [$rx, $ry, $rw, $rh] = $rect;
        $minX = INF; $maxX = -INF; $minY = INF; $maxY = -INF;
        foreach ($feats as $f) foreach ($ringsOf($f['geometry']) as $ring) foreach ($ring as $pt) {
            if ($clipLonGt !== null && $pt[0] > $clipLonGt) continue;
            $x = $pt[0] * $cosL;
            $minX = min($minX, $x); $maxX = max($maxX, $x); $minY = min($minY, $pt[1]); $maxY = max($maxY, $pt[1]);
        }
        $w = max(1e-6, $maxX - $minX); $h = max(1e-6, $maxY - $minY);
        $s = min($rw / $w, $rh / $h);
        $ox = $rx + ($rw - $w * $s) / 2; $oy = $ry + ($rh - $h * $s) / 2;
        foreach ($feats as $ab => $f) {
            $d = '';
            foreach ($ringsOf($f['geometry']) as $ring) {
                if ($clipLonGt !== null) { $ring = array_values(array_filter($ring, fn($pt) => $pt[0] <= $clipLonGt)); if (count($ring) < 3) continue; }
                $first = true;
                foreach ($ring as $pt) {
                    $x = round($ox + ($pt[0] * $cosL - $minX) * $s, 1);
                    $y = round($oy + ($maxY - $pt[1]) * $s, 1);
                    $d .= ($first ? 'M' : 'L') . $x . ',' . $y; $first = false;
                }
                $d .= 'Z';
            }
            if ($d !== '') $paths[$ab] = ($paths[$ab] ?? '') . $d;
        }
    };
    $emit($main, [8, 8, 944, 470]);
    if ($ak) $emit($ak, [12, 472, 185, 120], 0);   // clip Aleutians (lon > 0)
    if ($hi) $emit($hi, [215, 505, 120, 85]);
    return $paths;
}
