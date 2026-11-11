<?php
/**
 * HEURISTIC RISK VALIDATION ENGINE — CAR PRICE PREDICTOR
 *
 * FLOOD RISK DATA SOURCES:
 *   [1] DENR-MGB (July 2025) — Barangay-level flood advisories
 *   [2] Project NOAH (UP Diliman / DOST) — LiDAR flood hazard maps
 *   [3] PAGASA — Rainfall warning system
 *   [4] JURP (2014) — Metro Manila flood risk assessment
 *   [5] Local PDRRMO reports (Bulacan, Pampanga, Cavite, Laguna, Rizal)
 *   [6] MMDA flood-prone area advisories
 */

// ═══════════════════════════════════════════════════════════════════════
// TIER 1 — VERY HIGH BARANGAYS (+35 pts)
// Confirmed by 2+ official sources (MGB + NOAH or local PDRRMO)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_BARANGAYS_VERY_HIGH = [
    // ---- Manila — Sampaloc (along Dapitan, Tayuman, España flood plain) ----
    'barangay 395','barangay 396','barangay 397','barangay 405','barangay 407',
    'barangay 409','barangay 424','barangay 425','barangay 459','barangay 460',
    'barangay 461','barangay 462','barangay 463','barangay 464','barangay 465',
    'barangay 470','barangay 471','barangay 472','barangay 473','barangay 474',
    'barangay 475','barangay 484','barangay 485','barangay 486','barangay 495',
    'barangay 496','barangay 497','barangay 508','barangay 511','barangay 535',
    'barangay 545','barangay 562','barangay 597','barangay 606','barangay 612',
    'barangay 615','barangay 619','barangay 620','barangay 621','barangay 622',
    // ---- Manila — Tondo (coastal + near pumping stations) ----
    'barangay 1','barangay 2','barangay 3','barangay 4','barangay 5',
    'barangay 9','barangay 10','barangay 12','barangay 13','barangay 14',
    'barangay 20','barangay 28','barangay 34','barangay 51','barangay 60',
    'barangay 68','barangay 76','barangay 86','barangay 92','barangay 98',
    'barangay 105','barangay 112','barangay 118','barangay 123','barangay 128',
    'barangay 130','barangay 134','barangay 140','barangay 151','barangay 161',
    'barangay 176','barangay 188','barangay 196','barangay 205','barangay 210',
    'baseco','sunog apog','vitas','parola','smokey mountain',
    // ---- Manila — Sta. Cruz ----
    'barangay 268','barangay 269','barangay 271','barangay 272','barangay 275',
    'barangay 276','barangay 280','barangay 281','barangay 285','barangay 290',
    'barangay 292','barangay 298','barangay 299','barangay 300','barangay 305',
    // ---- Manila — Binondo ----
    'barangay 655','barangay 656','barangay 657','barangay 660','barangay 663',
    'barangay 665','barangay 668','barangay 670','barangay 672',
    // ---- Manila — Quiapo ----
    'barangay 703','barangay 704','barangay 705','barangay 706','barangay 707',
    'barangay 708','barangay 709','barangay 710','barangay 711',
    // ---- Manila — Paco / Pandacan ----
    'barangay 733','barangay 734','barangay 735','barangay 738','barangay 740',
    'barangay 742','barangay 745','barangay 746','barangay 748','barangay 750',
    'barangay 753','barangay 760','barangay 765','barangay 770','barangay 772',
    // ---- Manila — Sta. Mesa / San Miguel / San Andres ----
    'barangay 586','barangay 590','barangay 596','barangay 600','barangay 605',
    'barangay 712','barangay 715','barangay 720','barangay 728','barangay 732',
    'barangay 836','barangay 840','barangay 850','barangay 860','barangay 870',
    // ---- Quezon City — confirmed QC hotspots ----
    'bagong silangan','batasan hills','tatalon','roxas district',
    'commonwealth','san bartolome','talayan','sto. domingo','nagkakaisang nayon',
    'sta. lucia','bagong pagasa','dona imelda','matandang balara','culiat',
    // ---- Caloocan ----
    'barangay 164','barangay 176','barangay 188','barangay 12','barangay 14',
    'barangay 18','barangay 24','barangay 28','barangay 30','barangay 34',
    'barangay 40','barangay 44','barangay 48','barangay 52','barangay 56',
    'barangay 60','barangay 65','barangay 70','barangay 76','barangay 80',
    // ---- Malabon ----
    'concepcion-ibaba','concepcion-baritan','tonsuya','tugatog','hulong duhat',
    'longos','catmon','acacia','niugan','muzon','potrero',
    // ---- Navotas ----
    'tanza uno','tanza dos','sipac-almacen','navotas east','navotas west',
    'north bay boulevard','san roque',
    // ---- Valenzuela ----
    'coloong','wawang pulo','isla','tagalag',
    // ---- Marikina — barangays near the river ----
    'barangka','concepcion uno','concepcion dos','industrial valley',
    'jesus dela peña','kalumpang','malanday','nangka','parang',
    'santa elena','santo niño','tañong','tumana',
    // ---- Pasig ----
    'bagong ilog','dela paz','pinagbuhatan','maybunga','santolan','rosario',
    // ---- Mandaluyong ----
    'namayan','hulo','plainview','mabini-jrizal','daang bakal',
    'addition hills','pleasant hills',
    // ---- Las Piñas / Muntinlupa ----
    'almanza uno','almanza dos','talon uno','talon dos','talon tres',
    'talon kuatro','talon singko','putatan',
    // ---- Cavite ----
    'zapote 1','zapote 2','zapote 3','talaba 1','talaba 2','talaba 3',
    'aniban 1','aniban 2','aniban 3','ligas 1','ligas 2',
    'maliksi 1','maliksi 2','panapaan 1','panapaan 2','panapaan 3',
    'panapaan 4','panapaan 5','pulong gubat','mabolo','dulong bayan',
    'habay 1','habay 2','sinalhan 1',
    'san rafael i','san rafael ii','san rafael iii','salcedo ii',
    // ---- Bulacan ----
    'longos','bulihan','sapang bayan','gatbuca','calizon',
    'san miguel','frances','iba o\' este','san jose',
    'saluysoy','malis','panginay','masuso',
    // ---- Pampanga ----
    'alauli','bagang','balibago','nigui','sagrada','sapang kawayan',
    // ---- Laguna ----
    'landayan','aplaya','caingin','sinalhan','ibaba','malusak','pooc',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 2 — HIGH BARANGAYS (+30 pts)
// Confirmed by 1 source (usually a single PDRRMO or MGB report)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_BARANGAYS_HIGH = [
    // ---- Manila — additional barangays ----
    'barangay 630','barangay 640','barangay 650','barangay 660','barangay 670',
    'barangay 680','barangay 690','barangay 700','barangay 720','barangay 730',
    'barangay 745','barangay 755','barangay 765','barangay 775','barangay 785',
    'barangay 795','barangay 805','barangay 815','barangay 825','barangay 835',
    // ---- Quezon City ----
    'apolonio samson','fairview','novaliches','cubao','project 4','project 6',
    'project 7','project 8','bagong pagasa','mariblo','pasong putik',
    // ---- Makati ----
    'pembo','rizal','bangkal','magallanes','la paz','palanan','tejeros','urduja',
    // ---- San Juan ----
    'rivera','corazon de jesus','ermitaño','addition hills','pasadena',
    // ---- Taguig ----
    'bagumbayan','ibayo-tipas','ligid-tipas','napindan','signal village',
    'western bicutan','lower bicutan',
    // ---- Parañaque ----
    'baclaran','la huerta','san dionisio','tambo','moonwalk','san antonio',
    // ---- Pasay ----
    'barangay 1','barangay 76','barangay 183','malibay','san isidro','san roque',
    // ---- Pasig additional ----
    'caniogan','manggahan','kapitolyo','oranbo','sagad','san joaquin','santa lucia',
    // ---- Cainta / Taytay / Angono / Antipolo ----
    'san andres','san isidro','san juan','san roque','sto. domingo','sto. niño',
    'dolores','muzon','sta. ana','bagumbayan','kalayaan','poblacion ibaba',
    'poblacion itaas','san pedro','san vicente','beverly hills','cupang','dalig',
    'dela paz','mambugan','mayamot','san luis','santa cruz',
    // ---- Cebu ----
    'basak san nicolas','basak pardo','bulacao','calamba','cogon pardo',
    'duljo','inayawan','kinasang-an','labangon','mambaling','pardo',
    'pasil','poblacion pardo','punta princesa','quiot','tisa','to-ong','tinago',
    // ---- Iloilo ----
    'bakhaw','benedicto','bolilao','buhang','buntatala','caingin','calahunan',
    'calaparan','dungon a','dungon b','gustilo','hibao-an norte','hibao-an sur',
    'jalandoni','kauswagan','lopez jaena','pale','san isidro','san nicolas',
    'tanza baybay','ticud',
    // ---- Davao ----
    'bago aplaya','bago gallera','bucana','catalunan grande','catalunan pequeño',
    'matina aplaya','matina crossing','matina pangi','talomo proper','talomo river',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 3 — HIGH DISTRICTS (+25 pts)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_DISTRICTS_HIGH = [
    'tondo','sta. cruz','sampaloc','quiapo','binondo','paco','malate',
    'pandacan','intramuros','ermita','port area','san andres','san miguel',
    'caloocan north','caloocan south','la paz','molo','mandurriao',
    'agdao','buhangin','talomo','poblacion (davao)',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 4 — MEDIUM DISTRICTS (+18 pts)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_DISTRICTS_MEDIUM = [
    'sta. mesa','san nicolas','san miguel',
    'district 1 (makati)','district 2 (makati)',
    'district 1 (pasay)','district 2 (pasay)',
    'north (cebu)','south (cebu)',
    'jaro','arevalo','city proper',
    'poblacion (davao)','talomo','agdao',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 5 — HIGH CITIES (+15 pts)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_CITIES_HIGH = [
    'manila','marikina','navotas','malabon','valenzuela','caloocan','pasig',
    'meycauayan','malolos','hagonoy','paombong','marilao','bocaue','calumpit',
    'san fernando','floridablanca','angeles','lubao','sasmuan',
    'macabebe','porac','masantol','candaba','guagua','arayat','apalit','san simon',
    'bacoor','cavite city','imus','kawit','noveleta','rosario','tanza',
    'cainta','san mateo','taytay','angono','antipolo',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 6 — MEDIUM CITIES (+10 pts)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_CITIES_MEDIUM = [
    'quezon city','taguig','paranaque','las pinas','mandaluyong','san juan',
    'pasay','makati','pateros','muntinlupa',
    'san pedro','calamba','santa rosa','binan','los banos',
    'pakil','victoria','santa cruz','bay','pila','lumban','cavinti',
];

// ═══════════════════════════════════════════════════════════════════════
// TIER 7 — LOW CITIES (+5 pts)
// ═══════════════════════════════════════════════════════════════════════
const RISK_FLOOD_CITIES_LOW = [
    'baguio','bagong silang','santa maria','meycauayan (bulacan)',
    'bacolod','iloilo city','dumaguete','tagbilaran','davao city',
    'cagayan de oro','zamboanga city','general santos',
];

function normalize_str(?string $s): string { return strtolower(trim((string)$s)); }

/**
 * 7-tier flood risk lookup with detailed tier output.
 * Returns ['level' => 'Barangay/District/City/None', 'tier' => 'VeryHigh/High/Medium/Low/None', 'match' => '...', 'pts' => int]
 */
function get_flood_risk(array $input): array {
    $city     = normalize_str($input['city'] ?? '');
    $district = normalize_str($input['district'] ?? '');
    $barangay = normalize_str($input['barangay'] ?? '');
    $location = normalize_str($input['location'] ?? '');
    $combined = $city . ' ' . $district . ' ' . $barangay . ' ' . $location;

    // ── TIER 1: Very High Barangays (multi-source) ──
    if ($barangay !== '') {
        foreach (RISK_FLOOD_BARANGAYS_VERY_HIGH as $b) {
            if (strpos($barangay, $b) !== false) {
                return ['level'=>'Barangay', 'tier'=>'VeryHigh', 'match'=>$b, 'pts'=>35];
            }
        }
    }
    // Also check combined for landmark hints
    foreach (RISK_FLOOD_BARANGAYS_VERY_HIGH as $b) {
        if (strpos($combined, $b) !== false) {
            return ['level'=>'Barangay', 'tier'=>'VeryHigh', 'match'=>$b, 'pts'=>35];
        }
    }

    // ── TIER 2: High Barangays (single-source) ──
    if ($barangay !== '') {
        foreach (RISK_FLOOD_BARANGAYS_HIGH as $b) {
            if (strpos($barangay, $b) !== false) {
                return ['level'=>'Barangay', 'tier'=>'High', 'match'=>$b, 'pts'=>30];
            }
        }
    }

    // ── TIER 3: High Districts ──
    if ($district !== '') {
        foreach (RISK_FLOOD_DISTRICTS_HIGH as $d) {
            if (strpos($district, $d) !== false) {
                return ['level'=>'District', 'tier'=>'High', 'match'=>$d, 'pts'=>25];
            }
        }
    }

    // ── TIER 4: Medium Districts ──
    if ($district !== '') {
        foreach (RISK_FLOOD_DISTRICTS_MEDIUM as $d) {
            if (strpos($district, $d) !== false) {
                return ['level'=>'District', 'tier'=>'Medium', 'match'=>$d, 'pts'=>18];
            }
        }
    }

    // ── TIER 5: High Cities ──
    if ($city !== '') {
        foreach (RISK_FLOOD_CITIES_HIGH as $c) {
            if (strpos($city, $c) !== false) {
                return ['level'=>'City', 'tier'=>'High', 'match'=>$c, 'pts'=>15];
            }
        }
    }

    // ── TIER 6: Medium Cities ──
    if ($city !== '') {
        foreach (RISK_FLOOD_CITIES_MEDIUM as $c) {
            if (strpos($city, $c) !== false) {
                return ['level'=>'City', 'tier'=>'Medium', 'match'=>$c, 'pts'=>10];
            }
        }
    }

    // ── TIER 7: Low Cities ──
    if ($city !== '') {
        foreach (RISK_FLOOD_CITIES_LOW as $c) {
            if (strpos($city, $c) !== false) {
                return ['level'=>'City', 'tier'=>'Low', 'match'=>$c, 'pts'=>5];
            }
        }
    }

    return ['level'=>'None', 'tier'=>'None', 'match'=>'', 'pts'=>0];
}

function estimate_market_value(PDO $pdo, array $l): array {
    $brand = $l['brand'] ?? '';
    $model = $l['model'] ?? '';
    $year  = (int)($l['year_manufactured'] ?? $l['year'] ?? 0);
    $loc   = $l['location'] ?? $l['city'] ?? '';

    $q = $pdo->prepare("SELECT AVG(asking_price) a, COUNT(*) c FROM vehicle_listings WHERE status='approved' AND brand=? AND model=? AND year_manufactured=? AND location LIKE ? AND asking_price>0");
    $q->execute([$brand, $model, $year, '%'.explode(',', $loc)[0].'%']);
    $r = $q->fetch();
    if ($r['c'] >= 3 && $r['a'] > 0) return ['value'=>(float)$r['a'], 'source'=>'regional_comparables', 'sample'=>(int)$r['c']];

    $q = $pdo->prepare("SELECT AVG(asking_price) a, COUNT(*) c FROM vehicle_listings WHERE status='approved' AND brand=? AND model=? AND asking_price>0 AND location LIKE ?");
    $q->execute([$brand, $model, '%'.explode(',', $loc)[0].'%']);
    $r = $q->fetch();
    if ($r['c'] >= 3 && $r['a'] > 0) return ['value'=>(float)$r['a'], 'source'=>'regional_model_avg', 'sample'=>(int)$r['c']];

    $q = $pdo->prepare("SELECT AVG(asking_price) a, COUNT(*) c FROM vehicle_listings WHERE status='approved' AND brand=? AND model=? AND asking_price>0");
    $q->execute([$brand, $model]);
    $r = $q->fetch();
    if ($r['c'] >= 1 && $r['a'] > 0) return ['value'=>(float)$r['a'], 'source'=>'national_model_avg', 'sample'=>(int)$r['c']];

    $q = $pdo->prepare("SELECT base_price, yearly_depreciation FROM reference_weights WHERE brand=? AND model=? LIMIT 1");
    $q->execute([$brand, $model]);
    $ref = $q->fetch();
    if ($ref) {
        $age = max(1, (int)date('Y') - $year);
        return ['value'=>max(50000, (float)$ref['base_price'] - ((float)$ref['yearly_depreciation'] * $age)), 'source'=>'reference_weights', 'sample'=>1];
    }
    return ['value'=>0, 'source'=>'none', 'sample'=>0];
}

function compute_risk_score(PDO $pdo, array $l): array {
    $flags = []; $score = 0;
    $brand   = $l['brand']  ?? '';
    $model   = $l['model']  ?? '';
    $year    = (int)($l['year_manufactured'] ?? $l['year'] ?? 0);
    $mileage = (int)($l['mileage'] ?? $l['mileage_km'] ?? 0);
    $price   = (float)($l['asking_price'] ?? $l['listed_price'] ?? 0);
    $accident = $l['accident_history'] ?? '';
    $flood_flag = !empty($l['is_flood_flagged']);

    $est = isset($l['estimated_value']) && $l['estimated_value'] > 0
        ? ['value'=>(float)$l['estimated_value'], 'source'=>'provided', 'sample'=>0]
        : estimate_market_value($pdo, $l);

    if ($est['value'] > 0 && $price > 0) {
        $diff = (($price - $est['value']) / $est['value']) * 100;
        if ($diff <= -30)      { $score += 40; $flags[] = sprintf("Price %.1f%% below regional market value (possible scam/damage)", abs($diff)); }
        elseif ($diff <= -15)  { $score += 20; $flags[] = sprintf("Price %.1f%% below regional market value", abs($diff)); }
        elseif ($diff >= 25)   { $score += 15; $flags[] = sprintf("Price %.1f%% above regional market value (overpricing)", $diff); }
    } elseif ($price > 0 && $est['value'] == 0) {
        $score += 5; $flags[] = "No comparable listings for this model — market unverifiable";
    }

    $age = max(1, (int)date('Y') - $year);
    $kpy = $mileage / $age;
    if ($mileage > 0) {
        if ($mileage < 100 && $age >= 1) { $score += 5;  $flags[] = "Suspiciously low odometer (<100 km) on a {$age}-yr-old vehicle"; }
        elseif ($kpy < 500)               { $score += 15; $flags[] = sprintf("Extreme low usage: %.0f km/yr (possible rollback)", $kpy); }
        elseif ($kpy > 40000)             { $score += 25; $flags[] = sprintf("Extreme high usage: %.0f km/yr (>40,000)", $kpy); }
        elseif ($kpy > 25000)             { $score += 10; $flags[] = sprintf("Above-average usage: %.0f km/yr", $kpy); }
        if ($mileage > 500000)            { $score += 10; $flags[] = "Odometer >500,000 km — verify for tampering"; }
    }

    // ── 7-tier flood scoring ──
    $flood = get_flood_risk([
        'city'     => $l['city']     ?? '',
        'district' => $l['district'] ?? '',
        'barangay' => $l['barangay'] ?? '',
        'location' => $l['location'] ?? '',
    ]);
    if ($flood['pts'] > 0) {
        $score += $flood['pts'];
        $tier_label = [
            'VeryHigh' => 'VERY HIGH-RISK',
            'High'     => 'HIGH-RISK',
            'Medium'   => 'MEDIUM-RISK',
            'Low'      => 'LOW-RISK',
        ][$flood['tier']] ?? 'RISK';
        $flags[] = sprintf(
            "%s %s flood zone: \"%s\" matched (source: MGB/NOAH/PAGASA) — %s",
            $tier_label,
            strtolower($flood['level']),
            $flood['match'],
            in_array($flood['tier'], ['VeryHigh','High'])
                ? 'verify for water damage before purchase'
                : 'monitor for any water damage'
        );
    }
    if ($flood_flag) { $score += 15; $flags[] = "Locally flagged as flood-damaged"; }

    if ($age >= 20)   { $score += 15; $flags[] = "Vehicle age ≥20 yrs — re-registration risk"; }
    elseif ($age >= 15) { $score += 10; $flags[] = "Vehicle age ≥15 yrs — elevated maintenance risk"; }

    $acc = normalize_str($accident);
    if (preg_match('/major|severe|structural|frame|chassis/i', $accident)) { $score += 15; $flags[] = "Major accident indicators"; }
    elseif (!empty($accident) && $acc !== 'none')                          { $score += 5;  $flags[] = "Accident history noted"; }

    $ref_q = $pdo->prepare("SELECT year_start, year_end FROM reference_weights WHERE brand=? AND model=? LIMIT 1");
    $ref_q->execute([$brand, $model]);
    $ref = $ref_q->fetch();
    if ($ref) {
        $ys = (int)$ref['year_start']; $ye = $ref['year_end'] !== null ? (int)$ref['year_end'] : (int)date('Y');
        if ($year > 0 && ($year < $ys || $year > $ye)) { $score += 15; $flags[] = "Year {$year} outside known range ({$ys}-{$ye}) for {$brand} {$model}"; }
    } else {
        $score += 5; $flags[] = "{$brand} {$model} not in reference catalog";
    }
    if ($year > (int)date('Y') + 1) { $score += 10; $flags[] = "Manufacture year is in the future"; }

    $score = max(0, min(100, $score));
    if ($score < 30)      { $level = 'Low';    $rec = 'Proceed to ML Model'; }
    elseif ($score < 60)  { $level = 'Medium'; $rec = 'Flag for Manual Review'; }
    else                  { $level = 'High';   $rec = 'Reject Listing'; }

    return [
        'risk_score'     => $score,
        'risk_level'     => $level,
        'flags'          => $flags,
        'recommendation' => $rec,
        'flood_analysis' => $flood,
        'data_sources'   => ['market_estimate_source'=>$est['source'], 'market_estimate_value'=>round($est['value'],2), 'comparable_samples'=>$est['sample'] ?? 0],
    ];
}