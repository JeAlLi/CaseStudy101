<?php
/**
 * admin/import_clean_data.php
 * ---------------------------------------------------------------------------
 * Imports clean_vehicle_listings.csv (produced by preprocess_tvalue.py) into
 * the vehicle_listings table.
 *
 *  - Admin login required (same session check as the other admin pages)
 *  - "Preview" mode validates everything and writes nothing
 *  - "Import" mode runs inside ONE transaction: any failure rolls everything back
 *  - Rows already in the database are skipped, so running it twice is safe
 *  - Only columns that exist in BOTH the CSV and your table are inserted
 *
 * Place this file in the admin/ folder next to dashboard.php.
 */
session_start();
require_once '../db.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
set_time_limit(300);
$current_page = 'import_clean_data';

if (empty($_SESSION['csrf_import'])) $_SESSION['csrf_import'] = bin2hex(random_bytes(32));

// ─────────────────────────────────────────────────────────────
// Config
// ─────────────────────────────────────────────────────────────
const MAX_CSV_BYTES = 10 * 1024 * 1024;

// Columns the importer is allowed to write (anything else in the CSV is ignored)
const IMPORT_COLUMNS = [
    'vehicle_type','brand','model','year_manufactured','mileage','color','transmission','fuel_type',
    'modifications','registration_status','or_cr_ready','fully_paid','deed_of_sale_ready',
    'maintenance_history','accident_history','first_owner','flooded','asking_price','price_negotiability',
    'payment_methods','trade_in','location','city','district','barangay','reason_for_selling',
    'warranty_status','seller_notes','status','listing_type','seller_name','seller_contact',
    'verification_status',
];
const REQUIRED_COLUMNS = ['model','year_manufactured','mileage','asking_price','transmission','fuel_type'];
const NULLABLE_TEXT    = ['color','city','district','barangay','reason_for_selling','seller_notes',
                          'seller_contact','seller_name'];
const YES_NO_COLUMNS   = ['or_cr_ready','fully_paid','deed_of_sale_ready','first_owner','flooded','trade_in'];

// Fallbacks used when a column is not an ENUM in your database
const FALLBACK_ENUMS = [
    'transmission'        => ['Automatic','Manual','CVT'],
    'fuel_type'           => ['Gasoline','Diesel','Hybrid','Electric'],
    'maintenance_history' => ['Excellent','Good','Average','Poor','Excellent (Casa)'],
    'status'              => ['pending','approved','rejected'],
    'listing_type'        => ['scraped','user_sale','bidding'],
    'verification_status' => ['unverified','mechanic_verified'],
];

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function enum_values(string $type): array {
    if (stripos($type, 'enum(') !== 0) return [];
    preg_match_all("/'((?:[^']|'')*)'/", $type, $m);
    return array_map(fn($v) => str_replace("''", "'", $v), $m[1]);
}
function varchar_limit(string $type): ?int {
    return preg_match('/^varchar\((\d+)\)/i', $type, $m) ? (int)$m[1] : null;
}
function dup_key(array $r): string {
    return sha1(mb_strtolower(implode('|', [
        $r['model'] ?? '', (int)($r['year_manufactured'] ?? 0), (int)($r['mileage'] ?? 0),
        (int)round((float)($r['asking_price'] ?? 0)), $r['seller_contact'] ?? '', $r['location'] ?? '',
        $r['seller_notes'] ?? '', $r['color'] ?? '',
    ])));
}

// ─────────────────────────────────────────────────────────────
// Handle upload
// ─────────────────────────────────────────────────────────────
$result = null;       // filled after a successful run
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_import'], $_POST['csrf'] ?? '')) {
            throw new Exception('Session expired. Reload the page and try again.');
        }
        $doImport        = ($_POST['mode'] ?? 'preview') === 'import';
        $includeBidding  = !empty($_POST['include_bidding']);

        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please choose the clean_vehicle_listings.csv file (upload error code '
                . (int)($_FILES['csv']['error'] ?? -1) . ').');
        }
        if ($_FILES['csv']['size'] > MAX_CSV_BYTES) throw new Exception('The file is larger than 10 MB.');
        if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') {
            throw new Exception('Only .csv files are accepted.');
        }

        // What does YOUR table look like?
        $dbCols = [];   // field => type
        foreach ($pdo->query('SHOW COLUMNS FROM vehicle_listings')->fetchAll() as $c) {
            $dbCols[$c['Field']] = $c['Type'];
        }
        $enums = [];
        foreach (FALLBACK_ENUMS as $col => $fallback) {
            $enums[$col] = isset($dbCols[$col]) && enum_values($dbCols[$col]) ? enum_values($dbCols[$col]) : $fallback;
        }
        $biddingAllowed = in_array('bidding', $enums['listing_type'], true);

        // Read the CSV
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$fh) throw new Exception('Could not read the uploaded file.');
        $header = fgetcsv($fh);
        if (!$header) throw new Exception('The CSV is empty.');
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);   // strip UTF-8 BOM
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);

        $missing = array_diff(REQUIRED_COLUMNS, $header);
        if ($missing) throw new Exception('Missing required column(s): ' . implode(', ', $missing));

        $useCols = array_values(array_filter($header, fn($h) => in_array($h, IMPORT_COLUMNS, true) && isset($dbCols[$h])));
        $notInDb = array_values(array_filter($header, fn($h) => in_array($h, IMPORT_COLUMNS, true) && !isset($dbCols[$h])));

        // Existing rows, for duplicate detection
        $seen = [];
        $q = $pdo->query("SELECT model, year_manufactured, mileage, asking_price, seller_contact, location, seller_notes, color
                          FROM vehicle_listings WHERE brand = 'Toyota'");
        foreach ($q->fetchAll() as $row) $seen[dup_key($row)] = true;

        $stats = ['rows' => 0, 'valid' => 0, 'inserted' => 0, 'dupes' => 0, 'bidding_skipped' => 0,
                  'invalid' => 0, 'truncated' => 0];
        $problems = [];
        $preview  = [];
        $toInsert = [];

        $lineNo = 1;
        while (($raw = fgetcsv($fh)) !== false) {
            $lineNo++;
            if (count($raw) === 1 && trim((string)$raw[0]) === '') continue;    // blank line
            $stats['rows']++;
            $raw = array_pad($raw, count($header), '');
            $r = array_combine($header, array_slice($raw, 0, count($header)));
            $r = array_map(fn($v) => trim((string)$v), $r);

            try {
                foreach (REQUIRED_COLUMNS as $rc) {
                    if ($r[$rc] === '') throw new Exception("'{$rc}' is empty");
                }
                if (!ctype_digit($r['year_manufactured']) || (int)$r['year_manufactured'] < 1990 || (int)$r['year_manufactured'] > (int)date('Y') + 1) {
                    throw new Exception("invalid year '{$r['year_manufactured']}'");
                }
                if (!ctype_digit($r['mileage']) || (int)$r['mileage'] > 1000000) throw new Exception("invalid mileage '{$r['mileage']}'");
                if (!is_numeric($r['asking_price']) || (float)$r['asking_price'] <= 0) throw new Exception("invalid price '{$r['asking_price']}'");

                foreach (['transmission','fuel_type','maintenance_history','status','listing_type','verification_status'] as $ec) {
                    if (isset($r[$ec]) && $r[$ec] !== '' && !in_array($r[$ec], $enums[$ec], true)) {
                        throw new Exception("'{$ec}' value '{$r[$ec]}' is not allowed by your table");
                    }
                }
                foreach (YES_NO_COLUMNS as $yn) {
                    if (isset($r[$yn]) && $r[$yn] !== '' && !in_array($r[$yn], ['Yes','No'], true)) {
                        throw new Exception("'{$yn}' must be Yes or No");
                    }
                }
            } catch (Exception $e) {
                $stats['invalid']++;
                if (count($problems) < 25) $problems[] = ['line' => $lineNo, 'msg' => $e->getMessage()];
                continue;
            }

            $type = $r['listing_type'] ?? 'scraped';
            if ($type === 'bidding' && !$includeBidding) { $stats['bidding_skipped']++; continue; }

            // Blank -> NULL, trim to column length
            foreach ($useCols as $c) {
                if (in_array($c, NULLABLE_TEXT, true) && ($r[$c] ?? '') === '') $r[$c] = null;
                $limit = varchar_limit($dbCols[$c]);
                if ($limit && isset($r[$c]) && mb_strlen((string)$r[$c]) > $limit) {
                    $r[$c] = mb_substr($r[$c], 0, $limit);
                    $stats['truncated']++;
                }
            }

            $k = dup_key($r);
            if (isset($seen[$k])) { $stats['dupes']++; continue; }
            $seen[$k] = true;

            $stats['valid']++;
            if (count($preview) < 10) $preview[] = $r;
            $toInsert[] = $r;
        }
        fclose($fh);

        if ($includeBidding && !$biddingAllowed && array_filter($toInsert, fn($r) => ($r['listing_type'] ?? '') === 'bidding')) {
            throw new Exception("Your listing_type column does not allow 'bidding' yet. Run this once in phpMyAdmin, then try again:\n"
                . "ALTER TABLE vehicle_listings MODIFY listing_type ENUM('scraped','user_sale','bidding') NOT NULL DEFAULT 'scraped';");
        }

        if ($doImport && $toInsert) {
            $sql = 'INSERT INTO vehicle_listings (`' . implode('`,`', $useCols) . '`) VALUES (:' . implode(',:', $useCols) . ')';
            $stmt = $pdo->prepare($sql);
            $pdo->beginTransaction();
            try {
                foreach ($toInsert as $r) {
                    $params = [];
                    foreach ($useCols as $c) $params[$c] = $r[$c] ?? null;
                    $stmt->execute($params);
                    $stats['inserted']++;
                }
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                $stats['inserted'] = 0;
                throw new Exception('Import cancelled, nothing was saved: ' . $e->getMessage());
            }
        }

        $result = ['mode' => $doImport ? 'import' : 'preview', 'stats' => $stats, 'problems' => $problems,
                   'preview' => $preview, 'notInDb' => $notInDb, 'biddingAllowed' => $biddingAllowed,
                   'includeBidding' => $includeBidding];
    } catch (Throwable $e) {
        $error = $e instanceof PDOException ? 'Database error: ' . $e->getMessage() : $e->getMessage();
    }
}

include 'header.php';
?>
<style>
    .admin-container { max-width: 1000px; margin: 0 auto; padding: 24px 20px; }
    .imp-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: var(--shadow-sm); margin-bottom: 24px; }
    .imp-card h2 { font-family: var(--font-display); font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 6px; }
    .imp-sub { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }
    .imp-row { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; margin-bottom: 20px; }
    .imp-row input[type=file] { padding: 10px; border: 1px dashed var(--border-hover); border-radius: 8px; background: var(--paper); color: var(--text); flex: 1; min-width: 240px; }
    .imp-check { display: flex; gap: 8px; align-items: flex-start; font-size: 14px; color: var(--text); }
    .imp-check small { display: block; color: var(--text-muted); font-size: 12px; margin-top: 2px; }
    .imp-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-secondary { background: var(--paper); color: var(--text); border: 1px solid var(--border); padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; }
    .btn-secondary:hover { border-color: var(--blue); color: var(--blue); }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .stat { background: var(--paper); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
    .stat b { display: block; font-family: var(--font-display); font-size: 26px; color: var(--text); }
    .stat span { font-size: 12px; color: var(--text-muted); font-weight: 600; }
    .stat.good b { color: var(--teal); } .stat.bad b { color: var(--coral); }
    .imp-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 0; }
    .imp-table th, .imp-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: left; }
    .imp-table th { font-size: 11px; text-transform: uppercase; color: var(--text-muted); background: var(--paper); }
    pre.sql { background: var(--ink); color: #E2E8F0; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: 12.5px; margin-top: 10px; white-space: pre-wrap; }
</style>

<div class="view admin-container">

    <?php if ($error): ?>
        <div class="alert alert-error"><div><?php echo nl2br(e($error)); ?></div></div>
    <?php endif; ?>

    <?php if ($result): $s = $result['stats']; ?>
        <div class="imp-card">
            <h2><?php echo $result['mode'] === 'import' ? 'Import finished' : 'Preview (nothing saved yet)'; ?></h2>
            <p class="imp-sub">
                <?php if ($result['mode'] === 'import'): ?>
                    <?php echo number_format($s['inserted']); ?> listings were added to <code>vehicle_listings</code> in a single transaction.
                <?php else: ?>
                    This is what would happen. If it looks right, choose the same file again and press <strong>Import now</strong>.
                <?php endif; ?>
            </p>
            <div class="stat-grid">
                <div class="stat"><b><?php echo number_format($s['rows']); ?></b><span>Rows in file</span></div>
                <div class="stat good"><b><?php echo number_format($result['mode'] === 'import' ? $s['inserted'] : $s['valid']); ?></b><span><?php echo $result['mode'] === 'import' ? 'Inserted' : 'Ready to import'; ?></span></div>
                <div class="stat"><b><?php echo number_format($s['dupes']); ?></b><span>Already in database (skipped)</span></div>
                <div class="stat"><b><?php echo number_format($s['bidding_skipped']); ?></b><span>Auction units skipped</span></div>
                <div class="stat <?php echo $s['invalid'] ? 'bad' : ''; ?>"><b><?php echo number_format($s['invalid']); ?></b><span>Invalid rows</span></div>
            </div>

            <?php if ($s['truncated']): ?>
                <div class="alert alert-error" style="margin-bottom:16px;"><?php echo (int)$s['truncated']; ?> value(s) were longer than their column and were shortened.</div>
            <?php endif; ?>
            <?php if ($result['notInDb']): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">These CSV columns do not exist in your table and were ignored: <strong><?php echo e(implode(', ', $result['notInDb'])); ?></strong></div>
            <?php endif; ?>
            <?php if (!$result['includeBidding'] && $s['bidding_skipped']): ?>
                <p class="imp-sub">Auction (bidding) units were left out because their price is a minimum bid, not a market price, and would pull valuations down.<?php if (!$result['biddingAllowed']): ?> Importing them also needs your <code>listing_type</code> column to allow <code>'bidding'</code>.<?php endif; ?></p>
            <?php endif; ?>

            <?php if ($result['problems']): ?>
                <h3 style="font-size:15px; margin:18px 0 8px;">Invalid rows (first <?php echo count($result['problems']); ?>)</h3>
                <table class="imp-table"><thead><tr><th>CSV line</th><th>Problem</th></tr></thead><tbody>
                    <?php foreach ($result['problems'] as $p): ?>
                        <tr><td>#<?php echo (int)$p['line']; ?></td><td><?php echo e($p['msg']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>

            <?php if ($result['preview']): ?>
                <h3 style="font-size:15px; margin:18px 0 8px;">Sample of rows that will be added</h3>
                <div style="overflow-x:auto;">
                <table class="imp-table"><thead><tr><th>Toyota</th><th>Year</th><th>Mileage</th><th>Price</th><th>Color</th><th>City</th><th>Type</th></tr></thead><tbody>
                    <?php foreach ($result['preview'] as $r): ?>
                        <tr>
                            <td><?php echo e($r['model']); ?></td><td><?php echo e($r['year_manufactured']); ?></td>
                            <td><?php echo number_format((int)$r['mileage']); ?> km</td>
                            <td>₱<?php echo number_format((float)$r['asking_price']); ?></td>
                            <td><?php echo e($r['color'] ?? ''); ?></td><td><?php echo e($r['city'] ?? ''); ?></td>
                            <td><?php echo e($r['listing_type'] ?? 'scraped'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>

            <?php if ($result['mode'] === 'import' && $s['inserted']): ?>
                <p class="imp-sub" style="margin-top:18px;">
                    <strong>Next:</strong> open <code>train_model.php</code> to retrain the regression on the new data.
                    <code>ml_predict.py</code> retrains the Random Forest on every request, so it picks the new rows up automatically.
                </p>
                <div class="imp-actions" style="margin-top:12px;">
                    <a href="manage_listings.php" class="predict-btn" style="text-decoration:none;">View in Manage Listings</a>
                    <a href="../train_model.php" class="btn-secondary" style="text-decoration:none;">Retrain model</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="imp-card">
        <h2>Import clean data</h2>
        <p class="imp-sub">Upload <code>clean_vehicle_listings.csv</code> from the preprocessing script. Run a <strong>Preview</strong> first: it checks every row against your table without saving anything.</p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf_import']); ?>">
            <div class="imp-row">
                <input type="file" name="csv" accept=".csv,text/csv" required>
            </div>
            <div class="imp-row">
                <label class="imp-check">
                    <input type="checkbox" name="include_bidding" value="1">
                    <span>Also import Toyota Financial Services auction units
                        <small>Prices are minimum bids, so they are off by default. Needs <code>listing_type</code> to allow 'bidding'.</small></span>
                </label>
            </div>
            <div class="imp-actions">
                <button type="submit" name="mode" value="preview" class="btn-secondary">Preview (no changes)</button>
                <button type="submit" name="mode" value="import" class="predict-btn" onclick="return confirm('Insert the valid rows into vehicle_listings now?');">Import now</button>
            </div>
        </form>

        <p class="imp-sub" style="margin:22px 0 0;">Only needed if you tick the auction option, run this once in phpMyAdmin:</p>
        <pre class="sql">ALTER TABLE vehicle_listings
  MODIFY listing_type ENUM('scraped','user_sale','bidding') NOT NULL DEFAULT 'scraped';</pre>
    </div>
</div>

<?php include 'footer.php'; ?>