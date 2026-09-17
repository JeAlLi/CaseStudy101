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
        
        $ml_input = [
            'brand' => $brand, 'model' => $model, 'year_manufactured' => $year,
            'mileage' => $mileage, 'transmission' => $transmission, 'fuel_type' => $fuel_type
        ];
        
        $b64_input = base64_encode(json_encode($ml_input));
        $command = "python ml_predict.py " . escapeshellarg($b64_input);
        $output = shell_exec($command);
        $result = json_decode($output, true);
        
        if ($result && $result['status'] === 'success') {
            $ml_base_price = (float)$result['predicted_price'];
            
            // Calculate exact peso penalties for the UI breakdown
            $reg_penalty = $ml_base_price * $REGISTRATION_PCT[$registration];
            $maint_penalty = $ml_base_price * $MAINTENANCE_PCT[$maintenance];
            $acc_penalty = $ml_base_price * $ACCIDENT_PCT[$accidents];
            $mod_penalty = $ml_base_price * $MODIFICATION_PCT[$modifications];
            
            $cond_adj = $reg_penalty + $maint_penalty + $acc_penalty + $mod_penalty;
            $final_price = $ml_base_price + $cond_adj;
            
            $estimated_price = max(1000, $final_price);
            $range_low  = $estimated_price * 0.92;
            $range_high = $estimated_price * 1.08;
            
            // Detailed Breakdown Array
            $breakdown = [
                'ml_base' => $ml_base_price,
                'reg_val' => $reg_penalty,
                'maint_val' => $maint_penalty,
                'acc_val' => $acc_penalty,
                'mod_val' => $mod_penalty,
                'total_adj' => $cond_adj
            ];
        } else {
            $error_msg = $result['message'] ?? 'Unknown execution error.';
            $errors['model'] = "Machine Learning Engine failed: " . htmlspecialchars($error_msg);
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
      <div class="panel" style="position: sticky; top: 20px;">
        <div class="panel-head">
          <div class="ic" style="background:var(--teal-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M12 3l2.5 5.5L20 9l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/></svg></div>
          <h3>Valuation Report</h3>
        </div>
        
        <?php if ($estimated_price !== null): ?>
            <div class="price-box" style="margin-bottom: 24px;">
              <div class="lbl">Estimated Fair Market Value</div>
              <div class="amt">₱<?php echo number_format($estimated_price); ?></div>
              <div class="range">Expected Negotiation Range<br><b>₱<?php echo number_format($range_low); ?> - ₱<?php echo number_format($range_high); ?></b></div>
            </div>
            
            <div class="breakdown" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #fafafa;">
              
              <!-- PHASE 1: Machine Learning -->
              <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px;">Phase 1: Regression Baseline</h4>
              <p style="font-size: 11px; color: #6b7280; margin-bottom: 12px; line-height: 1.4;">Based on Market Data for Year, Mileage, Transmission, and Fuel Type.</p>
              
              <div class="row" style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed #d1d5db;">
                  <span style="font-weight: 600; color: #111827;">Statistical Market Average</span>
                  <span class="amt" style="font-weight: 600;">₱<?php echo number_format($breakdown['ml_base']); ?></span>
              </div>
              
              <!-- PHASE 2: Heuristics -->
              <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px;">Phase 2: Heuristic Adjustments</h4>
              <p style="font-size: 11px; color: #6b7280; margin-bottom: 12px; line-height: 1.4;">Condition penalties applied to the statistical baseline.</p>
              
              <div class="row">
                  <span>Registration (<?php echo htmlspecialchars($f['registration'] ?? 'Updated'); ?>)</span>
                  <span class="amt <?php echo $breakdown['reg_val'] < 0 ? 'neg' : 'pos'; ?>"><?php echo $breakdown['reg_val'] < 0 ? '-' : '+'; ?>₱<?php echo number_format(abs($breakdown['reg_val'])); ?></span>
              </div>
              <div class="row">
                  <span>Maintenance (<?php echo htmlspecialchars($f['maintenance'] ?? 'Average'); ?>)</span>
                  <span class="amt <?php echo $breakdown['maint_val'] < 0 ? 'neg' : 'pos'; ?>"><?php echo $breakdown['maint_val'] < 0 ? '-' : '+'; ?>₱<?php echo number_format(abs($breakdown['maint_val'])); ?></span>
              </div>
              <div class="row">
                  <span>Accidents (<?php echo htmlspecialchars($f['accidents'] ?? 'None'); ?>)</span>
                  <span class="amt <?php echo $breakdown['acc_val'] < 0 ? 'neg' : 'pos'; ?>"><?php echo $breakdown['acc_val'] < 0 ? '-' : '+'; ?>₱<?php echo number_format(abs($breakdown['acc_val'])); ?></span>
              </div>
              <div class="row">
                  <span>Modifications (<?php echo htmlspecialchars($f['modifications'] ?? 'Stock'); ?>)</span>
                  <span class="amt <?php echo $breakdown['mod_val'] < 0 ? 'neg' : 'pos'; ?>"><?php echo $breakdown['mod_val'] < 0 ? '-' : '+'; ?>₱<?php echo number_format(abs($breakdown['mod_val'])); ?></span>
              </div>
              
              <!-- TOTALS -->
              <div class="row" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                  <span style="font-weight: 600; color: #374151;">Total Condition Impact</span>
                  <?php if ($breakdown['total_adj'] >= 0): ?>
                      <span class="amt pos" style="font-weight: 600;">+₱<?php echo number_format($breakdown['total_adj']); ?></span>
                  <?php else: ?>
                      <span class="amt neg" style="font-weight: 600;">-₱<?php echo number_format(abs($breakdown['total_adj'])); ?></span>
                  <?php endif; ?>
              </div>
              
            </div>
            
        <?php else: ?>
            <div class="empty-state">Fill out the form to generate a valuation report.</div>
        <?php endif; ?>
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