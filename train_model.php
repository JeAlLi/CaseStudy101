<?php
require_once 'db.php';
set_time_limit(300);

echo "<pre style='font-family:monospace; font-size:14px;'>";
echo "=== CarPrice Predictor: Regression Trainer ===\n\n";

$stmt = $pdo->query("SELECT asking_price, year_manufactured, mileage,
                     registration_status, maintenance_history,
                     accident_history, modifications
                     FROM vehicle_listings
                     WHERE status = 'approved' 
                       AND asking_price BETWEEN 50000 AND 3000000
                       AND year_manufactured BETWEEN 2010 AND 2025
                       AND mileage BETWEEN 1000 AND 300000
                       AND listing_type = 'scraped'");
$rows = $stmt->fetchAll();
$n = count($rows);

echo "Found $n approved listings.\n";

if ($n < 10) {
    die("\nERROR: Need at least 10 approved listings. Currently have $n.");
}

// 2. Build feature matrix
$X = []; $Y = [];
$cy = (int)date('Y');

$R = ['Updated'=>0,'Unknown'=>-0.03,'Expired'=>-0.075];
$M = ['Excellent'=>0,'Good'=>-0.015,'Average'=>-0.075,'Poor'=>-0.20];
$A = ['None'=>0,'Unknown'=>-0.05,'Minor'=>-0.10,'Major'=>-0.175];
$D = ['Stock / None'=>0,'Minor Modifications'=>-0.04,'Major Modifications'=>-0.125];

foreach ($rows as $row) {
    $age = max(0, $cy - (int)$row['year_manufactured']);
    $mileage = (int)$row['mileage'];
    $cond = ($R[$row['registration_status']] ?? 0)
          + ($M[$row['maintenance_history']] ?? 0)
          + ($A[$row['accident_history']] ?? 0)
          + ($D[$row['modifications']] ?? 0);
    $X[] = [1, $age, $mileage, $cond];
    $Y[] = (float)$row['asking_price'];
}

// 3. Build XTX and XTY
$p = 4;
$XTX = array_fill(0, $p, array_fill(0, $p, 0));
$XTY = array_fill(0, $p, 0);
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $p; $j++) {
        $XTY[$j] += $X[$i][$j] * $Y[$i];
        for ($k = 0; $k < $p; $k++) {
            $XTX[$j][$k] += $X[$i][$j] * $X[$i][$k];
        }
    }
}

// 4. Matrix inversion (Gauss-Jordan)
function inv($m, $s) {
    $a = [];
    for ($i=0; $i<$s; $i++) {
        $a[$i] = array_merge($m[$i], array_fill(0,$s,0));
        $a[$i][$s+$i] = 1;
    }
    for ($i=0; $i<$s; $i++) {
        $pv = $i;
        for ($k=$i+1; $k<$s; $k++) if (abs($a[$k][$i]) > abs($a[$pv][$i])) $pv = $k;
        if (abs($a[$pv][$i]) < 1e-10) return null;
        $t = $a[$i]; $a[$i] = $a[$pv]; $a[$pv] = $t;
        $d = $a[$i][$i];
        for ($k=0; $k<2*$s; $k++) $a[$i][$k] /= $d;
        for ($k=0; $k<$s; $k++) {
            if ($k==$i) continue;
            $f = $a[$k][$i];
            for ($mm=0; $mm<2*$s; $mm++) $a[$k][$mm] -= $f * $a[$i][$mm];
        }
    }
    $out = [];
    for ($i=0; $i<$s; $i++) $out[$i] = array_slice($a[$i], $s, $s);
    return $out;
}

$inv = inv($XTX, $p);
if (!$inv) die("\nERROR: Matrix is singular. Your data may have too many identical rows.");

// 5. Beta = (XTX)^-1 * XTY
$beta = array_fill(0, $p, 0);
for ($i=0; $i<$p; $i++)
    for ($j=0; $j<$p; $j++)
        $beta[$i] += $inv[$i][$j] * $XTY[$j];

// 6. R-squared
$ym = array_sum($Y) / $n;
$ssR = 0; $ssT = 0;
for ($i=0; $i<$n; $i++) {
    $pr = $beta[0] + $beta[1]*$X[$i][1] + $beta[2]*$X[$i][2] + $beta[3]*$X[$i][3];
    $ssR += ($Y[$i]-$pr)**2;
    $ssT += ($Y[$i]-$ym)**2;
}
$r2 = $ssT > 0 ? 1 - ($ssR/$ssT) : 0;

echo "\n=== TRAINED COEFFICIENTS ===\n";
echo "Intercept (b0):     " . number_format($beta[0],2) . "\n";
echo "Beta Age (b1):      " . number_format($beta[1],2) . "\n";
echo "Beta Mileage (b2):  " . number_format($beta[2],4) . "\n";
echo "Beta Condition (b3):" . number_format($beta[3],2) . "\n";
echo "R-squared:          " . number_format($r2,4) . "\n";
echo "Samples used:       $n\n\n";

// 7. Save to database
$pdo->prepare("INSERT INTO model_coefficients (intercept, beta_age, beta_mileage, beta_condition, r_squared, sample_size) VALUES (?,?,?,?,?,?)")
    ->execute([$beta[0], $beta[1], $beta[2], $beta[3], $r2, $n]);

echo "Model saved to database successfully!\n";
echo "Model ID: " . $pdo->lastInsertId() . "\n";
echo "</pre>";