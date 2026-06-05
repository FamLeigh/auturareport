<?php
/**
 * Run: php build-amr-data.php
 * Reads data/heckel/*.csv → writes data/amr-data.json + data/amr-meta.json
 *
 * Records are stored as compact arrays to minimize file size:
 * [make_idx, model_idx, year, price, flags, region_idx, doc_idx, odo, month_idx, seller_idx]
 * flags: bit 0 = has_key, bit 1 = no_key, bit 2 = starts
 * month_idx: -1 if month unknown, seller_idx: -1 if seller unknown
 */

$MONTHS = [
    'jan'=>'01','feb'=>'02','mar'=>'03','apr'=>'04',
    'may'=>'05','jun'=>'06','jul'=>'07','aug'=>'08',
    'sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12',
];

// Canonical make lookup — maps raw variants to clean names
const MAKE_MAP = [
    // Acura
    'AUCURA'              => 'ACURA',
    // Alfa Romeo
    'ALFA'                => 'ALFA ROMEO',
    'ALFA RMO'            => 'ALFA ROMEO',
    // BMW
    'BMW 5'               => 'BMW',
    // Buick
    'BUCK'                => 'BUICK',
    // Cadillac
    'CADIL'               => 'CADILLAC',
    'CADILAC'             => 'CADILLAC',
    'CADY'                => 'CADILLAC',
    // Chevrolet
    'CHEVR'               => 'CHEVROLET',
    'CHEVROL'             => 'CHEVROLET',
    'CHEVROT'             => 'CHEVROLET',
    'CHEVY'               => 'CHEVROLET',
    // Chrysler
    'CHRSLR'              => 'CHRYSLER',
    'CHRYS'               => 'CHRYSLER',
    'CHRYSLR'             => 'CHRYSLER',
    'CHYSLER'             => 'CHRYSLER',
    'CHYSLR'              => 'CHRYSLER',
    // Datsun
    'DATSU'               => 'DATSUN',
    // Ducati
    'DUCAT'               => 'DUCATI',
    // Ford
    'FORD E'              => 'FORD',
    'FORD F'              => 'FORD',
    'FORD F -'            => 'FORD',
    // Freightliner
    'FREIG'               => 'FREIGHTLINER',
    // Harley-Davidson
    'HARLE'               => 'HARLEY-DAVIDSON',
    'HARLEY'              => 'HARLEY-DAVIDSON',
    'HARLEY-'             => 'HARLEY-DAVIDSON',
    'HARLEY - DAVIDSON'   => 'HARLEY-DAVIDSON',
    // Hyundai
    'HYNDAI'              => 'HYUNDAI',
    'HYUND'               => 'HYUNDAI',
    'HYUNDAI TRANSLEAD'       => 'HYUNDAI',
    'HYUNDAI TRANSLEAD INC'   => 'HYUNDAI',
    // Infiniti
    'INFIN'               => 'INFINITI',
    'INFINITI 135'        => 'INFINITI',
    'INFINITY'            => 'INFINITI',
    // International
    'INTER'               => 'INTERNATIONAL',
    'INTERNATI'           => 'INTERNATIONAL',
    // Jaguar
    'JAG'                 => 'JAGUAR',
    'JAGUA'               => 'JAGUAR',
    // Kawasaki
    'KAWA'                => 'KAWASAKI',
    'KAWASA'              => 'KAWASAKI',
    // Kaufman
    'KAUF'                => 'KAUFMAN',
    // KIA
    'KIA **'              => 'KIA',
    // Land Rover
    'LAND'                => 'LAND ROVER',
    'LANDROVER'           => 'LAND ROVER',
    // Lincoln
    'LINCLN'              => 'LINCOLN',
    'LINCO'               => 'LINCOLN',
    'LNCLN'               => 'LINCOLN',
    // Maserati
    'MASER'               => 'MASERATI',
    'MASERAT'             => 'MASERATI',
    // Mercedes-Benz
    'MECEDES'             => 'MERCEDES-BENZ',
    'MERC'                => 'MERCEDES-BENZ',
    'MERCDES'             => 'MERCEDES-BENZ',
    'MERCE'               => 'MERCEDES-BENZ',
    'MERCED'              => 'MERCEDES-BENZ',
    'MERCEDES'            => 'MERCEDES-BENZ',
    'MRCDS'               => 'MERCEDES-BENZ',
    // Mercury
    'MERCRY'              => 'MERCURY',
    'MERCU'               => 'MERCURY',
    // Mitsubishi
    'MITFU'               => 'MITSUBISHI',
    'MITSU'               => 'MITSUBISHI',
    'MITSUBIS'            => 'MITSUBISHI',
    'MITSUBISH'           => 'MITSUBISHI',
    'MITSUBISHI FUSO'     => 'MITSUBISHI',
    'MITZ'                => 'MITSUBISHI',
    // Nissan
    'NISSA'               => 'NISSAN',
    'NISSAN L'            => 'NISSAN',
    // Oldsmobile
    'OLDS'                => 'OLDSMOBILE',
    'OLDSM'               => 'OLDSMOBILE',
    'OLDSMO'              => 'OLDSMOBILE',
    'OLDSMOBI'            => 'OLDSMOBILE',
    // Plymouth
    'PLYMO'               => 'PLYMOUTH',
    'PLYMOUT'             => 'PLYMOUTH',
    // Pontiac
    'PONTI'               => 'PONTIAC',
    // Porsche
    'PORCHE'              => 'PORSCHE',
    'PORSC'               => 'PORSCHE',
    // Royal Enfield
    'ROYAL ENFIELD MOTORS' => 'ROYAL ENFIELD',
    // Saturn
    'SATUR'               => 'SATURN',
    'SATURN S'            => 'SATURN',
    // Subaru
    'SUBAR'               => 'SUBARU',
    // Suzuki
    'SUZUK'               => 'SUZUKI',
    // Toyota
    'TOYOT'               => 'TOYOTA',
    // Volkswagen
    'VOLKSWA'             => 'VOLKSWAGEN',
    'VOLKSWA CC'          => 'VOLKSWAGEN',
    'VOLKSWA GTI'         => 'VOLKSWAGEN',
    'VOLSKWAGEN'          => 'VOLKSWAGEN',
    'VW'                  => 'VOLKSWAGEN',
    // Yamaha
    'YAMAH'               => 'YAMAHA',
    'YAMAHA MOTOR CORP.'  => 'YAMAHA',
    // Genuine Scooters
    'GENUINE'                    => 'GENUINE SCOOTERS',
    // Great Dane
    'GREAT DANE TRAILERS'        => 'GREAT DANE',
    // Homemade
    'HOMEMADE TRAILER'           => 'HOMEMADE',
    // Hummer
    'HUMME'                      => 'HUMMER',
    // Hyosung
    'HYOSUNG MOTORS & MACHINERY' => 'HYOSUNG',
    // Lexus
    'LEXUS ES'                   => 'LEXUS',
    // Tao Tao
    'TAOTAO'                     => 'TAO TAO',
];

function amr_normalize_make(string $raw): string {
    return MAKE_MAP[$raw] ?? $raw;
}

// Canonical model lookup — maps raw variants to clean names ('' = skip record)
const MODEL_MAP = [
    // Garbage / encoding errors → skip
    '#NAME?'               => '',
    'FORESTER #'           => '',
    "\xC3\x82\xC2\xA1 A"  => '',
    "\xC3\x8E\xC2\xA7\xC3\x8E\xC2\x92" => '',
    "\xD0\xA5\xD0\x94"    => '',

    // Typos
    'ACCROD'               => 'ACCORD',
    'COROLA'               => 'COROLLA',
    'COROLIA'              => 'COROLLA',
    'SILVRADO'             => 'SILVERADO',
    'SILVRDO'              => 'SILVERADO',
    'SIVRADO'              => 'SILVERADO',
    'EQINOX'               => 'EQUINOX',
    'EXPEDITOON'           => 'EXPEDITION',
    'SUBURB'               => 'SUBURBAN',

    // Abbreviations
    'NAVIG'                => 'NAVIGATOR',
    'GR AM'                => 'GRAND AM',
    'GRAND MARC'           => 'GRAND MARQUIS',
    'GRAND MARQ'           => 'GRAND MARQUIS',
    'CROWN VICT'           => 'CROWN VICTORIA',
    'CRWN VIC'             => 'CROWN VICTORIA',
    'F 150'                => 'F-150',
    'RAM-1500'             => 'RAM 1500',
    'RAM2500'              => 'RAM 2500',

    // Chrysler Town & Country variants
    'T & C'                => 'TOWN & COUNTRY',
    'T&C'                  => 'TOWN & COUNTRY',
    'TOWN AND'             => 'TOWN & COUNTRY',
    'TOWN AND ..'          => 'TOWN & COUNTRY',
    'TOWN AND ...'         => 'TOWN & COUNTRY',
    'TOWN AND ....'        => 'TOWN & COUNTRY',
    'TOWN AND COUNTRY'     => 'TOWN & COUNTRY',
    'TOWN & CO ...'        => 'TOWN & COUNTRY',
    'TOWN&COUNTRY'         => 'TOWN & COUNTRY',
    'TOWN-COUNT'           => 'TOWN & COUNTRY',
    'TOWN/C'               => 'TOWN & COUNTRY',

    // Grand Cherokee truncations (400+ records)
    'GRAND CHER'           => 'GRAND CHEROKEE',
    'GRAND CHER ..'        => 'GRAND CHEROKEE',
    'GRAND CHER ...'       => 'GRAND CHEROKEE',
    'GRAND CHER ....'      => 'GRAND CHEROKEE',

    // F-series truncations (handles both "F - 250 SUPE ..." and "F 250 SUPE ...")
    'F - 250 SUPE'         => 'F-250 SUPER DUTY',
    'F - 250 SUPE ...'     => 'F-250 SUPER DUTY',
    'F - 350 SUPE ...'     => 'F-350 SUPER DUTY',
    'F - 450 SUPE ...'     => 'F-450 SUPER DUTY',
    'F 250 SUPE ...'       => 'F-250 SUPER DUTY',
    'F 350 SUPE ...'       => 'F-350 SUPER DUTY',
    'F 450 SUPE ...'       => 'F-450 SUPER DUTY',

    // Silverado / Sierra truncations
    'SILVERADO 1 ...'      => 'SILVERADO 1500',
    'SILVERADO 2 ...'      => 'SILVERADO 2500',
    'SILVERADO 3 ...'      => 'SILVERADO 3500',
    'SIERRA 1500 ...'      => 'SIERRA 1500',
    'SIERRA 3500 ...'      => 'SIERRA 3500',
    'SIERRA CLAS ...'      => 'SIERRA CLASSIC',

    // International / Freightliner / Kenworth / Peterbilt
    'INTERNATI ...'        => 'INTERNATIONAL',
    'INTERNATI...'         => 'INTERNATIONAL',
    'FREIGHTLI ...'        => 'FREIGHTLINER',
    'KENWORT ...'          => 'KENWORTH',
    'PETERBUI ...'         => 'PETERBILT',
    'VOLVO TR ...'         => 'VOLVO TRUCK',

    // Range Rover / Discovery
    'RANGE ROV ...'        => 'RANGE ROVER',
    'DISCOVERY ...'        => 'DISCOVERY',
    'DISCOVERY SPORTL'     => 'DISCOVERY SPORT',

    // Santa Fe / Elantra / Sonata / Explorer
    'SANTA FE ..'          => 'SANTA FE',
    'SANTA FE ...'         => 'SANTA FE',
    'ELANTRA ...'          => 'ELANTRA',
    'SONATA H ...'         => 'SONATA HYBRID',
    'EXPLORER SP ...'      => 'EXPLORER SPORT',

    // Wrangler / Mustang / Escalade
    'WRANGLER U ...'       => 'WRANGLER UNLIMITED',
    'MUSTANG M ...'        => 'MUSTANG MACH-E',
    'ESCALADE H ...'       => 'ESCALADE HYBRID',

    // Cooper / Accord / Corolla / Highlander / Murano
    'COOPER CLU ...'       => 'COOPER CLUBMAN',
    'COOPER CO ...'        => 'COOPER COUNTRYMAN',
    'ACCORD CRO ...'       => 'ACCORD CROSSTOUR',
    'COROLLA .'            => 'COROLLA',
    'COROLLA CRO ...'      => 'COROLLA CROSS',
    'HIGHLANDER ...'       => 'HIGHLANDER',
    'MURANO CRO ...'       => 'MURANO CROSSCABRIOLET',

    // Cutlass / Econoline / Regal / Atlas / Lancer / G37 / Mazda3
    'CUTLASS SU ...'       => 'CUTLASS SUPREME',
    'ECONOLINE ...'        => 'ECONOLINE',
    'REGAL SPORT ...'      => 'REGAL SPORTBACK',
    'ATLAS CROSS ...'      => 'ATLAS CROSS SPORT',
    'LANCER SPO ...'       => 'LANCER SPORTBACK',
    'G37 CONVE ...'        => 'G37 CONVERTIBLE',
    'MAZDA3 HA ...'        => 'MAZDA3 HATCHBACK',

    // C/K and CVK series
    'C / K 1500 S ...'     => 'C/K 1500',
    'C / K 2500 S ...'     => 'C/K 2500',
    'C / K 3500 S ...'     => 'C/K 3500',
    'CVK 1500 ...'         => 'C/K 1500',
    'CVK 2500 ...'         => 'C/K 2500',
    'CVK 3500 ...'         => 'C/K 3500',
    'CVK 10 SERIES'        => 'C/K-SERIES',

    // Eighty-Eight / Delta
    'EIGHTY - EIGH ...'    => 'EIGHTY-EIGHT',
    'EIGHTY - EIGHT'       => 'EIGHTY-EIGHT',
    'DELTA EIGHT ...'      => 'DELTA 88',

    // Misc truncations
    'CHEVY VAN ...'        => 'CHEVY VAN',
    'MITSUBIS ...'         => 'MITSUBISHI',
    'PROMASTER ...'        => 'PROMASTER',
    'FLEETWOOD ...'        => 'FLEETWOOD',
    'BOAT TRAI ...'        => 'BOAT TRAILER',
    '4 WHEELE ...'         => '4 WHEELER',
    'RAM PICKU ...'        => 'RAM PICKUP',
    'R / V 3500 S ...'     => 'R/V 3500',
    'WHITE CO . ...'       => 'WHITE CONVENTIONAL',
    'BUS MISC ...'         => 'BUS',
    'TRAILER U ...'        => 'TRAILER',
    'OTHER UN ...'         => 'OTHER',
    'NATIONAL ...'         => 'NATIONAL',
    'BERING T ...'         => 'BERING TRUCK',
    'HEAVY DUTY ...'       => 'HEAVY DUTY',
    'HEAVY MA ...'         => 'HEAVY MACHINE',
    'DODGE TR ...'         => 'DODGE TRUCK',
    'PARTS - E ...'        => '',
];

function amr_normalize_model(string $raw): string {
    if (array_key_exists($raw, MODEL_MAP)) {
        return MODEL_MAP[$raw];
    }
    // Collapse "CR - V" / "F - 150" style spaced hyphens to "CR-V" / "F-150"
    return str_replace(' - ', '-', $raw);
}

function amr_normalize_doc(string $raw): string {
    $r = strtolower(trim($raw));
    if (strpos($r, 'salvage') !== false || strpos($r, 'rebuilt') !== false) return 'Salvage';
    if (strpos($r, 'abandon') !== false) return 'Abandoned';
    if (strpos($r, 'court')   !== false) return 'Court Order';
    if (strpos($r, 'junk')    !== false) return 'Junk';
    if (strpos($r, 'lien')    !== false) return 'Lien';
    if (strpos($r, 'e-title') !== false) return 'Title';
    if (strpos($r, 'title')   !== false) return 'Title';
    if (strpos($r, 'bond')    !== false) return 'Title';
    return 'Other';
}

// Returns the index for a value in a dictionary, inserting if new
function intern(string $val, array &$dict): int {
    if (!isset($dict[$val])) {
        $dict[$val] = count($dict);
    }
    return $dict[$val];
}

// Optional --date="May 18, 2026" argument (used by CLI and web upload)
$data_date = '';
foreach (($argv ?? []) as $arg) {
    if (strpos($arg, '--date=') === 0) {
        $data_date = trim(substr($arg, 7));
    }
}

$dir = __DIR__ . '/data/heckel';
$files = glob($dir . '/*.csv') ?: [];

if (empty($files)) {
    fwrite(STDERR, "No CSV files found in {$dir}\n");
    exit(1);
}

$makes   = [];
$models  = [];
$regions = [];
$docs    = [];
$months  = [];
$sellers = [];
$records = [];
$skipped = 0;
$total_rows = 0;

foreach ($files as $file) {
    echo "Parsing: " . basename($file) . "\n";

    $handle = fopen($file, 'r');
    if (!$handle) {
        echo "  Could not open file, skipping.\n";
        continue;
    }

    // Scan for the header row (strip UTF-8 BOM if present)
    $header = null;
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (empty(array_filter($row))) continue;
        $first = strtolower(trim(ltrim($row[0] ?? '', "\xEF\xBB\xBF")));
        if ($first === 'monthly auction start date') {
            $row[0] = ltrim($row[0], "\xEF\xBB\xBF");
            $header = array_map(
                fn($h) => str_replace('odomeer', 'odometer', strtolower(trim($h))),
                $row
            );
            break;
        }
    }

    if (!$header) {
        echo "  No header row found, skipping.\n";
        fclose($handle);
        continue;
    }

    $idx = array_flip($header);

    $price_i  = $idx['total sale price excl fees'] ?? null;
    $key_i    = $idx['vehicle key status']         ?? null;
    $start_i  = $idx['vehicle start status']       ?? null;
    $year_i   = $idx['vehicle year']               ?? null;
    $make_i   = $idx['vehicle make']               ?? null;
    $model_i  = $idx['vehicle model']              ?? null;
    $region_i = $idx['auction region']             ?? null;
    $doc_i    = $idx['vehicle documentation type'] ?? null;
    $odo_i    = $idx['vehicle odometer']           ?? null;
    $seller_i = $idx['seller name']                ?? null;
    $month_i  = 0;

    if ($price_i === null || $year_i === null || $make_i === null || $model_i === null) {
        echo "  Missing required columns (price/year/make/model), skipping.\n";
        fclose($handle);
        continue;
    }

    $file_rows = 0;
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (empty(array_filter($row))) continue;
        $total_rows++;
        $file_rows++;

        $price = (float)($row[$price_i] ?? 0);
        if ($price < 100) { $skipped++; continue; }

        $make  = amr_normalize_make(strtoupper(trim($row[$make_i]  ?? '')));
        $model = amr_normalize_model(strtoupper(trim($row[$model_i] ?? '')));
        $year  = (int)trim($row[$year_i] ?? '0');

        if (!$year || !$make || !$model) { $skipped++; continue; }

        $key_raw   = strtoupper(trim($row[$key_i   ?? -1] ?? ''));
        $start_raw = strtoupper(trim($row[$start_i ?? -1] ?? ''));
        $region    = strtoupper(trim($row[$region_i ?? -1] ?? ''));
        $doc_raw   = trim($row[$doc_i ?? -1] ?? '');
        $odo_raw   = trim($row[$odo_i ?? -1] ?? '');
        $seller    = trim($row[$seller_i ?? -1] ?? '');

        $has_key = in_array($key_raw, [
            'HAS KEYS', 'KEY MADE', 'MADE KEY',
            'KEY_IN_OFFICE', 'KEY_IN_VEHICLE', 'KEY IN OFFICE', 'KEY IN VEHICLE',
        ]);
        $no_key = in_array($key_raw, ['NO KEY', 'NO_KEY']);
        $starts = in_array($start_raw, ['STARTS', 'RUNS AND DRIVES', 'STARTS WITH A JUMP']);

        $odo = ($odo_raw !== '' && is_numeric($odo_raw)) ? (int)$odo_raw : 0;

        $month_str = strtolower(trim($row[$month_i] ?? ''));
        global $MONTHS;
        // Handles "Apr 2026" (space) and "Apr-26" (hyphen, 2-digit year)
        $sep     = strpos($month_str, '-') !== false ? '-' : ' ';
        $m_parts = explode($sep, $month_str);
        if (count($m_parts) === 2 && isset($MONTHS[$m_parts[0]])) {
            $yr    = $m_parts[1];
            $yr    = strlen($yr) === 2 ? '20' . $yr : $yr;
            $month = $yr . '-' . $MONTHS[$m_parts[0]];
        } else {
            $month = '';
        }

        $doc   = amr_normalize_doc($doc_raw);
        $flags = ($has_key ? 1 : 0) | ($no_key ? 2 : 0) | ($starts ? 4 : 0);

        $records[] = [
            intern($make,   $makes),
            intern($model,  $models),
            $year,
            (int)round($price),
            $flags,
            intern($region, $regions),
            intern($doc,    $docs),
            $odo,
            $month !== '' ? intern($month, $months) : -1,
            $seller !== '' ? intern($seller, $sellers) : -1,
        ];
    }

    fclose($handle);
    echo "  Rows: {$file_rows}\n";
}

$kept = count($records);
echo "\nTotal rows scanned : {$total_rows}\n";
echo "Kept               : {$kept}\n";
echo "Skipped (bad/cheap): {$skipped}\n";
echo "Unique makes       : " . count($makes) . "\n";
echo "Unique models      : " . count($models) . "\n";

// Convert dicts (value → index) to indexed arrays (index → value)
function dict_to_array(array $dict): array {
    $out = array_flip($dict); // [index => value]
    ksort($out);
    // array_flip loses string type on numeric keys (e.g. "626" → 626)
    // — cast back to strings so json_encode produces "626" not 626
    return array_map('strval', array_values($out));
}

$output = [
    'makes'   => dict_to_array($makes),
    'models'  => dict_to_array($models),
    'regions' => dict_to_array($regions),
    'docs'    => dict_to_array($docs),
    'months'  => dict_to_array($months),
    'sellers' => dict_to_array($sellers),
    'records' => $records,
];

$data_file = __DIR__ . '/data/amr-data.json';
$meta_file = __DIR__ . '/data/amr-meta.json';

file_put_contents($data_file, json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($meta_file, json_encode([
    'count'     => $kept,
    'built'     => date('Y-m-d H:i:s'),
    'data_date' => $data_date,
], JSON_PRETTY_PRINT));

$size_mb = round(filesize($data_file) / 1048576, 2);
echo "\nWrote: {$data_file} ({$size_mb} MB)\n";
echo "Wrote: {$meta_file}\n";
echo "Done.\n";
