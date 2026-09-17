<?php
require_once 'db.php';
$current_page = 'predict';
$current_year = (int)date('Y');
$estimated_price = null;
$breakdown = [];
$errors = [];

// ---- DYNAMIC CATALOG MERGE (Reference + Scraped Data) ----
$catalog = [];
// 1. Load Reference Weights
$ref_stmt = $pdo->query("SELECT vehicle_type, brand, model, year_start, year_end FROM reference_weights");
while($row = $ref_stmt->fetch(PDO::FETCH_ASSOC)) {
    $catalog[$row['vehicle_type']][$row['brand']][$row['model']] = [
        'year_start' => (int)$row['year_start'],
        'year_end' => $row['year_end'] ? (int)$row['year_end'] : $current_year
    ];
}
// 2. Merge Live Market Data (Allows users to predict scraped cars)
$mkt_stmt = $pdo->query("SELECT vehicle_type, brand, model, MIN(year_manufactured) as min_y, MAX(year_manufactured) as max_y FROM vehicle_listings WHERE status='approved' GROUP BY vehicle_type, brand, model");
while($row = $mkt_stmt->fetch(PDO::FETCH_ASSOC)) {
    $type = $row['vehicle_type']; $brand = $row['brand']; $model = $row['model'];
    if (!isset($catalog[$type][$brand][$model])) {
        $catalog[$type][$brand][$model] = [
            'year_start' => (int)$row['min_y'],
            'year_end' => (int)$row['max_y']
        ];
    } else {
        $catalog[$type][$brand][$model]['year_start'] = min($catalog[$type][$brand][$model]['year_start'], (int)$row['min_y']);
        $catalog[$type][$brand][$model]['year_end'] = max($catalog[$type][$brand][$model]['year_end'], (int)$row['max_y']);
    }
}

$TRANSMISSIONS = ['Automatic', 'Manual', 'CVT'];
$FUEL_TYPES    = ['Gasoline', 'Diesel', 'Hybrid', 'Electric'];
$MODIFICATIONS = ['Stock / None', 'Minor Modifications', 'Major Modifications'];
$REGISTRATIONS = ['Updated', 'Expired', 'Unknown'];
$MAINTENANCE   = ['Excellent', 'Good', 'Average', 'Poor'];
$ACCIDENTS     = ['None', 'Minor', 'Major', 'Unknown'];

// Academic Percentage Multipliers
$REGISTRATION_PCT = ['Updated' => 0.0, 'Expired' => -0.075, 'Unknown' => -0.03];
$MAINTENANCE_PCT  = ['Excellent' => 0.0, 'Good' => -0.015, 'Average' => -0.075, 'Poor' => -0.20];
$ACCIDENT_PCT     = ['None' => 0.0, 'Minor' => -0.10, 'Major' => -0.175, 'Unknown' => -0.05];
$MODIFICATION_PCT = ['Stock / None' => 0.0, 'Minor Modifications' => -0.04, 'Major Modifications' => -0.125];

$f = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    
    $type  = 'Car'; 
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year_raw = trim($_POST['year'] ?? '');
    $mileage_raw = trim($_POST['mileage'] ?? '');
    
    $transmission  = in_array($_POST['transmission'] ?? '', $TRANSMISSIONS, true) ? $_POST['transmission'] : $TRANSMISSIONS[0];
    $fuel_type     = in_array($_POST['fuel_type'] ?? '', $FUEL_TYPES, true) ? $_POST['fuel_type'] : $FUEL_TYPES[0];
    $modifications = in_array($_POST['modifications'] ?? '', $MODIFICATIONS, true) ? $_POST['modifications'] : $MODIFICATIONS[0];
    $registration  = in_array($_POST['registration'] ?? '', $REGISTRATIONS, true) ? $_POST['registration'] : $REGISTRATIONS[0];
    $maintenance   = in_array($_POST['maintenance'] ?? '', $MAINTENANCE, true) ? $_POST['maintenance'] : $MAINTENANCE[2];
    $accidents     = in_array($_POST['accidents'] ?? '', $ACCIDENTS, true) ? $_POST['accidents'] : $ACCIDENTS[0];

    // ---- Validation ----
    if ($brand === '') { $errors['brand'] = 'Required.'; }
    if ($model === '') { $errors['model'] = 'Required.'; } 
    if ($year_raw === '' || !ctype_digit($year_raw)) { $errors['year'] = 'Required.'; } 
    if ($mileage_raw === '' || !ctype_digit($mileage_raw) || (int)$mileage_raw < 0 || (int)$mileage_raw > 999999) {
        $errors['mileage'] = 'Invalid mileage.';
    }

    if (empty($errors)) {
        $year = (int)$year_raw;
        $mileage = (int)$mileage_raw;

        // Fetch Reference SRP
        $stmt = $pdo->prepare("SELECT * FROM reference_weights WHERE vehicle_type = ? AND LOWER(brand) = LOWER(?) AND LOWER(model) = LOWER(?) LIMIT 1");
        $stmt->execute([$type, $brand, $model]);
        $vehicle_data = $stmt->fetch();

        // Fetch Live Market Data
        $marketStmt = $pdo->prepare("SELECT AVG(asking_price) as avg_market_price, COUNT(*) as listing_count FROM vehicle_listings WHERE LOWER(brand) = LOWER(?) AND LOWER(model) = LOWER(?) AND status = 'approved' AND asking_price > 0");
        $marketStmt->execute([$brand, $model]);
        $marketData = $marketStmt->fetch();
        $listing_count = (int)$marketData['listing_count'];

        if ($vehicle_data || $listing_count > 0) {
            $cond_pct = $REGISTRATION_PCT[$registration] + $MAINTENANCE_PCT[$maintenance] + $ACCIDENT_PCT[$accidents] + $MODIFICATION_PCT[$modifications];
            
            $math_price = null;
            $has_ref = false;

            // Phase 1: Mathematical Baseline (If Reference Data Exists)
            if ($vehicle_data) {
                $has_ref = true;
                $age = max(0, $current_year - $year);
                $base = (float)$vehicle_data['base_price'];
                
                $year_adj = $base * min(0.80, $age * 0.09);
                $mileage_adj = $base * min(0.20, ($mileage / 10000) * 0.015);
                $cond_adj = $base * $cond_pct;
                
                $math_price = max(1000, $base - $year_adj - $mileage_adj + $cond_adj);
            }

            // Phase 2: Dynamic Fallback Valuation
            if ($has_ref && $listing_count > 0) {
                // Scenario A: HYBRID (Math + Market)
                $avg_market = (float)$marketData['avg_market_price'];
                $adj_market = $avg_market + ($avg_market * $cond_pct);
                $final_price = ($math_price + $adj_market) / 2;
                
                $breakdown = [
                    'has_ref' => true, 'base' => $base, 'year_adj' => $year_adj, 'mileage_adj' => $mileage_adj, 
                    'cond_adj' => $cond_adj, 'market_used' => true, 'listing_count' => $listing_count
                ];
            } elseif ($has_ref) {
                // Scenario B: PURE MATH (No live market data available)
                $final_price = $math_price;
                $breakdown = [
                    'has_ref' => true, 'base' => $base, 'year_adj' => $year_adj, 'mileage_adj' => $mileage_adj, 
                    'cond_adj' => $cond_adj, 'market_used' => false, 'listing_count' => 0
                ];
            } else {
                // Scenario C: PURE MARKET (No SRP available, highly reliant on scraped data)
                $avg_market = (float)$marketData['avg_market_price'];
                $cond_adj = $avg_market * $cond_pct;
                $final_price = $avg_market + $cond_adj;
                
                $breakdown = [
                    'has_ref' => false, 'base' => $avg_market, 'cond_adj' => $cond_adj, 
                    'market_used' => true, 'listing_count' => $listing_count
                ];
            }
            
            $estimated_price = max(1000, $final_price);
            $range_low  = $estimated_price * 0.947;
            $range_high = $estimated_price * 1.053;
        } else {
            $errors['model'] = "No market data or reference data exists for this specific model yet.";
        }
    }
}
include 'header.php';
?>

<div class="view">
  <div class="two-col">
    <!-- FORM PANEL -->
    <div class="panel">
      <div class="panel-head">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></div>
        <h3>Vehicle Price Estimator</h3>
      </div>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><span>Please fix the highlighted errors below.</span></div>
      <?php endif; ?>
      
      <form method="POST" id="predictForm" novalidate>
        <div class="form-section">
          <div class="form-section-title">Vehicle Information</div>
          <div class="form-grid">
            <div class="field"><label>Brand <span class="req">*</span></label>
              <select name="brand" id="brand"><option value="">Select brand</option></select>
            </div>
            <div class="field"><label>Model <span class="req">*</span></label>
              <select name="model" id="model" disabled><option value="">Select model</option></select>
              <div class="field-error"><?php echo e($errors['model'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Year of Manufacture <span class="req">*</span></label>
              <select name="year" id="year" disabled><option value="">Select year</option></select>
            </div>
            <div class="field"><label>Mileage <span class="req">*</span></label>
              <div class="input-suffix">
                <input type="number" name="mileage" id="mileage" min="0" max="999999" oninput="this.value = this.value.slice(0, 6)" value="<?php echo e($f['mileage'] ?? ''); ?>">
                <span>km</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <div class="form-section-title">Specifications</div>
          <div class="form-grid">
            <div class="field"><label>Transmission</label><select name="transmission" id="transmission"><?php foreach ($TRANSMISSIONS as $opt): echo option($opt, $f['transmission'] ?? null); endforeach; ?></select></div>
            <div class="field"><label>Fuel Type</label><select name="fuel_type" id="fuel_type"><?php foreach ($FUEL_TYPES as $opt): echo option($opt, $f['fuel_type'] ?? null); endforeach; ?></select></div>
            <div class="field"><label>Color</label><input type="text" name="color" value="<?php echo e($f['color'] ?? ''); ?>"></div>
            <div class="field"><label>Modifications</label><select name="modifications"><?php foreach ($MODIFICATIONS as $opt): echo option($opt, $f['modifications'] ?? null); endforeach; ?></select></div>
          </div>
        </div>
        
        <div class="form-section">
          <div class="form-section-title">Condition &amp; History</div>
          <div class="form-grid">
            <div class="field"><label>Registration Status</label><select name="registration"><?php foreach ($REGISTRATIONS as $opt): echo option($opt, $f['registration'] ?? null); endforeach; ?></select></div>
            <div class="field"><label>Maintenance</label><select name="maintenance"><?php foreach ($MAINTENANCE as $opt): echo option($opt, $f['maintenance'] ?? 'Average'); endforeach; ?></select></div>
            <div class="field" style="grid-column: span 2;"><label>Accident History</label><select name="accidents"><?php foreach ($ACCIDENTS as $opt): echo option($opt, $f['accidents'] ?? null); endforeach; ?></select></div>
          </div>
        </div>
        
        <div class="btn-row">
          <input type="hidden" name="predict" value="1">
          <button type="submit" class="predict-btn">Predict Price</button>
        </div>
      </form>
    </div>
    
    <!-- RESULT PANEL -->
    <div>
      <div class="panel">
        <div class="panel-head">
          <div class="ic" style="background:var(--teal-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M12 3l2.5 5.5L20 9l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/></svg></div>
          <h3>Estimated Price</h3>
        </div>
        
        <?php if ($estimated_price !== null): ?>
            <div class="price-box">
              <div class="lbl">Estimated Market Value</div>
              <div class="amt">₱<?php echo number_format($estimated_price); ?></div>
              <div class="range">Possible Range<br><b>₱<?php echo number_format($range_low); ?> - ₱<?php echo number_format($range_high); ?></b></div>
            </div>
            
            <div class="breakdown">
              <?php if ($breakdown['has_ref']): ?>
                  <div class="row"><span>Base price (Reference SRP)</span><span class="amt">₱<?php echo number_format($breakdown['base']); ?></span></div>
                  <div class="row"><span>Age depreciation</span><span class="amt neg">-₱<?php echo number_format($breakdown['year_adj']); ?></span></div>
                  <div class="row"><span>Mileage adjustment</span><span class="amt neg">-₱<?php echo number_format($breakdown['mileage_adj']); ?></span></div>
              <?php else: ?>
                  <div class="row" style="background: var(--coral-soft); margin: -4px -14px; padding: 10px 14px; border-radius: 6px;">
                      <span style="color: var(--coral); font-weight: 500;">Market Baseline Used (No SRP on file)</span>
                      <span class="amt">₱<?php echo number_format($breakdown['base']); ?></span>
                  </div>
                  <p style="font-size: 12px; color: var(--gray); line-height: 1.4; margin-bottom: 12px;">*Age and mileage depreciation are organically factored into live market averages.</p>
              <?php endif; ?>
              
              <?php if ($breakdown['market_used']): ?>
                  <div class="row" style="background: var(--indigo-soft); margin: -4px -14px; padding: 10px 14px; border-radius: 6px;">
                      <span style="color: var(--indigo-dark); font-weight: 500;">Market adjustment (<?php echo $breakdown['listing_count']; ?> live listings)</span>
                      <span class="amt pos" style="color: var(--indigo-dark);">Applied</span>
                  </div>
              <?php endif; ?>

              <div class="row">
                  <span>Condition impact</span>
                  <?php if ($breakdown['cond_adj'] >= 0): ?>
                      <span class="amt pos">+₱<?php echo number_format($breakdown['cond_adj']); ?></span>
                  <?php else: ?>
                      <span class="amt neg">-₱<?php echo number_format(abs($breakdown['cond_adj'])); ?></span>
                  <?php endif; ?>
              </div>
              <div class="row total"><span>Estimated price</span><span class="amt">₱<?php echo number_format($estimated_price); ?></span></div>
            </div>
        <?php else: ?>
            <div class="empty-state">Fill out the form to generate a valuation.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
const CATALOG = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?>;
const INITIAL = { brand: <?php echo json_encode($f['brand'] ?? ''); ?>, model: <?php echo json_encode($f['model'] ?? ''); ?>, year: <?php echo json_encode($f['year'] ?? ''); ?> };
const CURRENT_YEAR = <?php echo $current_year; ?>;

const brandSelect = document.getElementById('brand');
const modelSelect = document.getElementById('model');
const yearSelect  = document.getElementById('year');
const transSelect = document.getElementById('transmission');
const fuelSelect  = document.getElementById('fuel_type');

function updateSpecsBasedOnYear() {
    const year = parseInt(yearSelect.value);
    if (!year) return;
    Array.from(fuelSelect.options).forEach(opt => {
        if (opt.value === 'Electric') opt.disabled = (year < 2010);
        if (opt.value === 'Hybrid') opt.disabled = (year < 2005);
    });
    if (fuelSelect.options[fuelSelect.selectedIndex].disabled) { fuelSelect.value = 'Gasoline'; }
    Array.from(transSelect.options).forEach(opt => {
        if (opt.value === 'Automatic' || opt.value === 'CVT') opt.disabled = (year < 1995);
    });
    if (transSelect.options[transSelect.selectedIndex].disabled) { transSelect.value = 'Manual'; }
}

function resetSelect(select, placeholder, disabled) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = disabled;
}

function fillSelect(select, values, selected, placeholder) {
  resetSelect(select, placeholder, false);
  values.forEach(v => {
    const opt = document.createElement('option'); opt.value = v; opt.textContent = v;
    if (String(v) === String(selected)) opt.selected = true;
    select.appendChild(opt);
  });
}

function refreshBrands(selectedBrand) {
  if (!CATALOG['Car']) { resetSelect(brandSelect, 'Select brand', true); return; }
  fillSelect(brandSelect, Object.keys(CATALOG['Car']), selectedBrand, 'Select brand');
}

function refreshModels(selectedModel) {
  const brand = brandSelect.value;
  if (!brand || !CATALOG['Car'] || !CATALOG['Car'][brand]) { resetSelect(modelSelect, 'Select model', true); return; }
  fillSelect(modelSelect, Object.keys(CATALOG['Car'][brand]), selectedModel, 'Select model');
}

function refreshYears(selectedYear) {
  const brand = brandSelect.value; const model = modelSelect.value;
  const info = (brand && model && CATALOG['Car'][brand]) ? CATALOG['Car'][brand][model] : null;
  if (!info) { resetSelect(yearSelect, 'Select year', true); return; }
  const years = [];
  for (let y = info.year_end; y >= info.year_start; y--) years.push(y);
  fillSelect(yearSelect, years, selectedYear, 'Select year');
}

brandSelect.addEventListener('change', function() { refreshModels(null); resetSelect(yearSelect, 'Select year', true); });
modelSelect.addEventListener('change', function() { refreshYears(null); });
yearSelect.addEventListener('change', updateSpecsBasedOnYear);

(function initCascade() {
  refreshBrands(INITIAL.brand);
  if (INITIAL.brand) { refreshModels(INITIAL.model); if (INITIAL.model) refreshYears(INITIAL.year); }
})();
</script>
<?php include 'footer.php'; ?>