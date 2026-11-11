<?php
/**
 * Shared helper functions used across the CarPrice Predictor system.
 */

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalize_title(?string $value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    $value = preg_replace('/\s+/', ' ', $value);
    $words = explode(' ', $value);
    foreach ($words as &$w) {
        if (preg_match('/^[A-Z0-9\-]{2,}$/', $w)) continue;
        $w = strtoupper(substr($w, 0, 1)) . strtolower(substr($w, 1));
    }
    return implode(' ', $words);
}

/**
 * Title-case a free-text color string: "red blue" → "Red Blue".
 * Used when the user picks "Other / Custom" on the color dropdown.
 */
function normalize_color(string $color): string {
    $color = trim($color);
    if ($color === '') return '';
    $color = preg_replace('/\s+/', ' ', $color);
    return ucwords(strtolower($color));
}

function get_catalog(PDO $pdo): array {
    $currentYear = (int)date('Y');
    $hasYearColumns = true;
    try {
        $stmt = $pdo->query("SELECT vehicle_type, brand, model, year_start, year_end FROM reference_weights ORDER BY vehicle_type, brand, model");
    } catch (PDOException $e) {
        $hasYearColumns = false;
        $stmt = $pdo->query("SELECT vehicle_type, brand, model FROM reference_weights ORDER BY vehicle_type, brand, model");
    }
    $catalog = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $catalog[$row['vehicle_type']][$row['brand']][$row['model']] = $hasYearColumns
            ? [
                'year_start' => (int)$row['year_start'],
                'year_end'   => $row['year_end'] !== null ? (int)$row['year_end'] : null,
              ]
            : ['year_start' => 1990, 'year_end' => null];
    }
    return $catalog;
}

function compute_model_metrics(PDO $pdo): array {
    $stmt = $pdo->query("SELECT asking_price, predicted_value FROM vehicle_listings WHERE asking_price IS NOT NULL AND predicted_value IS NOT NULL AND asking_price > 0 AND status = 'approved'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = count($rows);
    if ($n < 2) return ['r2' => null, 'mae' => null, 'n' => $n];
    $mean = array_sum(array_map(fn($r) => (float)$r['asking_price'], $rows)) / $n;
    $ssRes = 0.0; $ssTot = 0.0; $absErrSum = 0.0;
    foreach ($rows as $r) {
        $actual = (float)$r['asking_price'];
        $pred   = (float)$r['predicted_value'];
        $ssRes += ($actual - $pred) ** 2;
        $ssTot += ($actual - $mean) ** 2;
        $absErrSum += abs($actual - $pred);
    }
    $r2  = $ssTot > 0 ? max(0, 1 - ($ssRes / $ssTot)) : null;
    $mae = $absErrSum / $n;
    return ['r2' => $r2, 'mae' => $mae, 'n' => $n];
}

function option(string $value, ?string $current, ?string $label = null): string {
    $label = $label ?? $value;
    $sel = ($current !== null && strcasecmp($current, $value) === 0) ? ' selected' : '';
    return '<option value="' . e($value) . '"' . $sel . '>' . e($label) . '</option>';
}

function get_body_types(): array {
    return [
        'Toyota|Vios'     => 'Sedan',
        'Toyota|Fortuner' => 'SUV',
        'Toyota|Innova'   => 'MPV',
        'Toyota|Hilux'    => 'Pickup',
        'Toyota|Wigo'     => 'Hatchback',
        'Toyota|Avanza'   => 'MPV',
    ];
}

function get_body_type_styles(): array {
    return [
        'Sedan'     => ['indigo', 'indigo-soft'],
        'SUV'       => ['coral', 'coral-soft'],
        'Pickup'    => ['amber', 'amber-soft'],
        'Van'       => ['teal', 'teal-soft'],
        'Hatchback' => ['violet', 'violet-soft'],
        'MPV'       => ['slate', 'slate-soft'],
    ];
}

function get_body_type(string $brand, string $model): string {
    $types = get_body_types();
    return $types["{$brand}|{$model}"] ?? 'Vehicle';
}

function car_icon_path(): string {
    return 'M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18M6.5 15.5h.01M17.5 15.5h.01';
}

function body_type_badge(string $brand, string $model): string {
    $type = get_body_type($brand, $model);
    [$color, $soft] = get_body_type_styles()[$type] ?? ['text-faint', 'paper'];
    $path = car_icon_path();
    return '<span class="vbadge" style="background:var(--' . $soft . '); color:var(--' . $color . ')">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="' . $path . '"/></svg>'
        . e($type) . '</span>';
}

function get_ph_cities(): array {
    return [
        'Quezon City','Manila','Makati','Pasig','Taguig','Parañaque',
        'Las Piñas','Muntinlupa','Marikina','Caloocan','Valenzuela',
        'Malabon','Navotas','San Juan','Mandaluyong','Pasay','Pateros',
        'Bacoor','Imus','Dasmariñas','General Trias','Tagaytay',
        'Trece Martires','Cavite City','Santa Rosa','Biñan','Calamba',
        'San Pedro','Los Baños','Cabuyao','Antipolo','Cainta','Taytay',
        'Angono','Binangonan','San Jose del Monte','Meycauayan','Malolos',
        'Marilao','Bocaue','San Fernando','Angeles','Mabalacat',
        'Cebu City','Mandaue','Lapu-Lapu','Talisay','Iloilo City',
        'Passi','Davao City','Tagum','Other City',
    ];
}

function train_regression_model(PDO $pdo) {
    return [
        'intercept'      => 150000,
        'beta_base'      => 0.85,
        'beta_age'       => -12000,
        'beta_mileage'   => -0.50,
        'beta_condition' => 400000,
    ];
}