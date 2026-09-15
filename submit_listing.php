<?php
require_once 'db.php';
$current_page = 'submit_listing';
$catalog = get_catalog($pdo); 
$current_year = (int)date('Y');
$success = false;
$errors = [];

$TRANSMISSIONS = ['Automatic', 'Manual', 'CVT'];
$FUEL_TYPES    = ['Gasoline', 'Diesel', 'Hybrid', 'Electric'];
$MODIFICATIONS = ['Stock / None', 'Minor Modifications', 'Major Modifications'];
$REGISTRATIONS = ['Updated', 'Expired', 'Unknown'];
$MAINTENANCE   = ['Excellent', 'Good', 'Average', 'Poor'];
$ACCIDENTS     = ['None', 'Minor', 'Major', 'Unknown'];

$REGISTRATION_ADJ = ['Updated' => 0, 'Expired' => -8000, 'Unknown' => -3000];
$MAINTENANCE_ADJ  = ['Excellent' => 15000, 'Good' => 5000, 'Average' => 0, 'Poor' => -10000];
$ACCIDENT_ADJ     = ['None' => 0, 'Minor' => -12000, 'Major' => -45000, 'Unknown' => -5000];
$MODIFICATION_ADJ = ['Stock / None' => 0, 'Minor Modifications' => -3000, 'Major Modifications' => -20000];

$f = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_listing'])) {
    
    $type  = 'Car'; 
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year_raw = trim($_POST['year'] ?? '');
    $mileage_raw = trim($_POST['mileage'] ?? '');
    $asking_price_raw = trim($_POST['asking_price'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    $seller_name = trim($_POST['seller_name'] ?? '');
    $seller_contact = trim($_POST['seller_contact'] ?? '');
    
    $transmission  = in_array($_POST['transmission'] ?? '', $TRANSMISSIONS) ? $_POST['transmission'] : $TRANSMISSIONS[0];
    $fuel_type     = in_array($_POST['fuel_type'] ?? '', $FUEL_TYPES) ? $_POST['fuel_type'] : $FUEL_TYPES[0];
    $modifications = in_array($_POST['modifications'] ?? '', $MODIFICATIONS) ? $_POST['modifications'] : $MODIFICATIONS[0];
    $registration  = in_array($_POST['registration'] ?? '', $REGISTRATIONS) ? $_POST['registration'] : $REGISTRATIONS[0];
    $maintenance   = in_array($_POST['maintenance'] ?? '', $MAINTENANCE) ? $_POST['maintenance'] : $MAINTENANCE[2];
    $accidents     = in_array($_POST['accidents'] ?? '', $ACCIDENTS) ? $_POST['accidents'] : $ACCIDENTS[0];
    $color = normalize_title($_POST['color'] ?? '');

    // ---- STRICT VALIDATION ----
    if ($brand === '') { $errors['brand'] = 'Required.'; }
    if ($model === '') { $errors['model'] = 'Required.'; }
    if ($year_raw === '' || !ctype_digit($year_raw)) { $errors['year'] = 'Required.'; }
    
    // Mileage Constraint
    if ($mileage_raw === '' || !ctype_digit($mileage_raw) || (int)$mileage_raw < 0 || (int)$mileage_raw > 999999) { 
        $errors['mileage'] = 'Valid mileage required (0 - 999,999 km).'; 
    }
    
    // Asking Price Constraint (Min 20k, Max 50m)
    if ($asking_price_raw === '' || !is_numeric($asking_price_raw) || (float)$asking_price_raw < 20000 || (float)$asking_price_raw > 50000000) { 
        $errors['asking_price'] = 'Asking price must be between ₱20,000 and ₱50,000,000.'; 
    }

    // Historical Spec Constraints
    if ($year_raw !== '') {
        $y = (int)$year_raw;
        if ($fuel_type === 'Electric' && $y < 2010) { $errors['fuel_type'] = 'Electric engines not available for this year.'; }
        if ($fuel_type === 'Hybrid' && $y < 2005) { $errors['fuel_type'] = 'Hybrid engines not available for this year.'; }
        if (in_array($transmission, ['Automatic', 'CVT']) && $y < 1995) { $errors['transmission'] = 'Automatic/CVT not available for this year.'; }
    }
    
    // String Length Constraints
    if ($seller_name === '') { $errors['seller_name'] = 'Name is required.'; }
    elseif (strlen($seller_name) > 100) { $errors['seller_name'] = 'Name is too long (max 100 chars).'; }

    if ($seller_contact === '') { $errors['seller_contact'] = 'Contact info is required.'; }
    elseif (strlen($seller_contact) > 100) { $errors['seller_contact'] = 'Contact info is too long (max 100 chars).'; }

    if (strlen($location) > 100) { $errors['location'] = 'Location is too long (max 100 chars).'; }
    if (strlen($color) > 50) { $errors['color'] = 'Color is too long (max 50 chars).'; }

    // ---- DATABASE INSERTION ----
    if (empty($errors)) {
        $year = (int)$year_raw;
        $mileage = (int)$mileage_raw;
        $asking_price = (float)$asking_price_raw;

        $stmt = $pdo->prepare("SELECT * FROM reference_weights WHERE vehicle_type = ? AND LOWER(brand) = LOWER(?) AND LOWER(model) = LOWER(?) LIMIT 1");
        $stmt->execute([$type, $brand, $model]);
        $vehicle_data = $stmt->fetch();

        $estimated_price = null;
        if ($vehicle_data) {
            $age = max(0, $current_year - $year);
            $base = (float)$vehicle_data['base_price'];
            $year_adj = $age * (float)$vehicle_data['yearly_depreciation'];
            $mileage_adj = $mileage * (float)$vehicle_data['mileage_penalty'];
            $cond_adj = $REGISTRATION_ADJ[$registration] + $MAINTENANCE_ADJ[$maintenance] + $ACCIDENT_ADJ[$accidents] + $MODIFICATION_ADJ[$modifications];
            $final_price = max(1000, $base - $year_adj - $mileage_adj + $cond_adj);
            
            // Blend with market data
            $marketStmt = $pdo->prepare("SELECT AVG(asking_price) as avg_market_price, COUNT(*) as c FROM vehicle_listings WHERE LOWER(brand)=LOWER(?) AND LOWER(model)=LOWER(?) AND status='approved'");
            $marketStmt->execute([$brand, $model]);
            $marketData = $marketStmt->fetch();
            if ($marketData['c'] > 0) {
                $final_price = ($final_price + ((float)$marketData['avg_market_price'] + $cond_adj)) / 2;
            }
            $estimated_price = max(1000, $final_price);
        }

        $insert = $pdo->prepare("INSERT INTO vehicle_listings
            (vehicle_type, brand, model, year_manufactured, mileage, color, transmission, fuel_type,
             modifications, registration_status, maintenance_history, accident_history,
             asking_price, predicted_value, location, status, listing_type, seller_name, seller_contact)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending','user_sale',?,?)");
        
        $insert->execute([
            $type, normalize_title($brand), normalize_title($model), $year, $mileage, 
            $color !== '' ? $color : null, $transmission, $fuel_type, $modifications, 
            $registration, $maintenance, $accidents, $asking_price, $estimated_price, 
            $location !== '' ? $location : null, $seller_name, $seller_contact
        ]);
        
        $success = true;
        $f = []; // clear form
    }
}
include 'header.php';
?>

<div class="view" style="max-width: 800px; margin: 0 auto;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic" style="background:var(--teal-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
      <h3>Sell Your Car</h3>
    </div>
    <div class="panel-sub" style="max-width: 100%;">Submit your vehicle to our public marketplace.</div>
    
    <?php if ($success): ?>
      <div class="alert alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        <span><b>Listing Submitted!</b> Your vehicle has been sent to our admin team for review.</span>
      </div>
      <div style="margin-top: 20px;"><a href="submit_listing.php" class="predict-btn" style="text-decoration:none; display:inline-flex;">Submit Another</a></div>
    <?php else: ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16v.01"/></svg>
          <span>Please fix the highlighted field<?php echo count($errors) > 1 ? 's' : ''; ?> below.</span>
        </div>
      <?php endif; ?>
      
      <form method="POST" id="submitForm" novalidate>
        <!-- Seller Info -->
        <div class="form-section">
          <div class="form-section-title">Seller Information</div>
          <div class="form-grid">
            <div class="field"><label>Your Name <span class="req">*</span></label>
              <input type="text" name="seller_name" maxlength="100" placeholder="e.g., Juan Dela Cruz" value="<?php echo htmlspecialchars($f['seller_name'] ?? ''); ?>">
              <div class="field-error"><?php echo htmlspecialchars($errors['seller_name'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Contact Info <span class="req">*</span></label>
              <input type="text" name="seller_contact" maxlength="100" placeholder="e.g., 0912 345 6789 or juan@email.com" value="<?php echo htmlspecialchars($f['seller_contact'] ?? ''); ?>">
              <div class="field-error"><?php echo htmlspecialchars($errors['seller_contact'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Location / City</label>
              <input type="text" name="location" maxlength="100" placeholder="e.g., Manila" value="<?php echo htmlspecialchars($f['location'] ?? ''); ?>">
              <div class="field-error"><?php echo htmlspecialchars($errors['location'] ?? ''); ?></div>
            </div>
          </div>
        </div>

        <!-- Vehicle Info -->
        <div class="form-section">
          <div class="form-section-title">Vehicle Information</div>
          <div class="form-grid">
            <div class="field"><label>Brand <span class="req">*</span></label>
              <select name="brand" id="brand"><option value="">Select brand</option></select>
              <div class="field-error"><?php echo htmlspecialchars($errors['brand'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Model <span class="req">*</span></label>
              <select name="model" id="model" disabled><option value="">Select model</option></select>
              <div class="field-error"><?php echo htmlspecialchars($errors['model'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Year <span class="req">*</span></label>
              <select name="year" id="year" disabled><option value="">Select year</option></select>
              <div class="field-error"><?php echo htmlspecialchars($errors['year'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Mileage <span class="req">*</span></label>
              <div class="input-suffix">
                <input type="number" name="mileage" id="mileage" min="0" max="999999" step="1" 
                       oninput="this.value = this.value.slice(0, 6)"
                       value="<?php echo htmlspecialchars($f['mileage'] ?? ''); ?>">
                <span>km</span>
              </div>
              <div class="field-error"><?php echo htmlspecialchars($errors['mileage'] ?? ''); ?></div>
            </div>
          </div>
        </div>
        
        <!-- Specs -->
        <div class="form-section">
          <div class="form-section-title">Specifications & Condition</div>
          <div class="form-grid">
            <div class="field"><label>Transmission</label>
                <select name="transmission" id="transmission"><?php foreach ($TRANSMISSIONS as $opt): echo option($opt, $f['transmission'] ?? null); endforeach; ?></select>
                <div class="field-error"><?php echo htmlspecialchars($errors['transmission'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Fuel Type</label>
                <select name="fuel_type" id="fuel_type"><?php foreach ($FUEL_TYPES as $opt): echo option($opt, $f['fuel_type'] ?? null); endforeach; ?></select>
                <div class="field-error"><?php echo htmlspecialchars($errors['fuel_type'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Color</label><input type="text" name="color" maxlength="50" value="<?php echo htmlspecialchars($f['color'] ?? ''); ?>"></div>
            <div class="field"><label>Modifications</label><select name="modifications"><?php foreach ($MODIFICATIONS as $opt): echo option($opt, $f['modifications'] ?? null); endforeach; ?></select></div>
            <div class="field"><label>Registration (LTO)</label><select name="registration"><?php foreach ($REGISTRATIONS as $opt): echo option($opt, $f['registration'] ?? null); endforeach; ?></select></div>
            <div class="field"><label>Maintenance</label><select name="maintenance"><?php foreach ($MAINTENANCE as $opt): echo option($opt, $f['maintenance'] ?? 'Average'); endforeach; ?></select></div>
            <div class="field" style="grid-column: span 2;"><label>Accident History</label><select name="accidents"><?php foreach ($ACCIDENTS as $opt): echo option($opt, $f['accidents'] ?? null); endforeach; ?></select></div>
          </div>
        </div>
        
        <!-- Pricing -->
        <div class="form-section">
          <div class="form-section-title">Pricing</div>
          <div class="form-grid">
            <div class="field"><label>Your Asking Price (₱) <span class="req">*</span></label>
              <input type="number" name="asking_price" min="20000" max="50000000" step="1" 
                     oninput="if(this.value.length > 8) this.value = this.value.slice(0,8);"
                     value="<?php echo htmlspecialchars($f['asking_price'] ?? ''); ?>">
              <div class="field-error"><?php echo htmlspecialchars($errors['asking_price'] ?? ''); ?></div>
            </div>
          </div>
        </div>
        
        <div class="btn-row" style="margin-top: 30px;">
          <input type="hidden" name="submit_listing" value="1">
          <button type="submit" class="predict-btn" style="background: var(--teal);"><span style="color:white;">Submit Listing</span></button>
        </div>
      </form>
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

    // Fuel Type Constraints
    Array.from(fuelSelect.options).forEach(opt => {
        if (opt.value === 'Electric') opt.disabled = (year < 2010);
        if (opt.value === 'Hybrid') opt.disabled = (year < 2005);
    });
    if (fuelSelect.options[fuelSelect.selectedIndex].disabled) { fuelSelect.value = 'Gasoline'; }

    // Transmission Constraints
    Array.from(transSelect.options).forEach(opt => {
        if (opt.value === 'Automatic' || opt.value === 'CVT') { opt.disabled = (year < 1995); }
    });
    if (transSelect.options[transSelect.selectedIndex].disabled) { transSelect.value = 'Manual'; }
}

function resetSelect(select, placeholder) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = true;
}
function fillSelect(select, values, selected, placeholder) {
  resetSelect(select, placeholder, false);
  values.forEach(function(v) {
    const opt = document.createElement('option'); opt.value = v; opt.textContent = v;
    if (String(v) === String(selected)) opt.selected = true;
    select.appendChild(opt);
  });
  select.disabled = false;
}
function refreshBrands(selectedBrand) {
  const type = 'Car'; 
  if (!CATALOG[type]) { resetSelect(brandSelect, 'Select brand'); return; }
  fillSelect(brandSelect, Object.keys(CATALOG[type]), selectedBrand, 'Select brand');
}
function refreshModels(selectedModel) {
  const brand = brandSelect.value;
  if (!brand || !CATALOG['Car'] || !CATALOG['Car'][brand]) { resetSelect(modelSelect, 'Select model'); return; }
  fillSelect(modelSelect, Object.keys(CATALOG['Car'][brand]), selectedModel, 'Select model');
}
function refreshYears(selectedYear) {
  const brand = brandSelect.value; const model = modelSelect.value;
  const info = (brand && model && CATALOG['Car'] && CATALOG['Car'][brand]) ? CATALOG['Car'][brand][model] : null;
  if (!info) { resetSelect(yearSelect, 'Select year'); return; }
  const years = [];
  for (let y = (info.year_end || CURRENT_YEAR); y >= info.year_start; y--) years.push(y);
  fillSelect(yearSelect, years, selectedYear, 'Select year');
}

brandSelect.addEventListener('change', function() { refreshModels(null); resetSelect(yearSelect, 'Select year'); });
modelSelect.addEventListener('change', function() { refreshYears(null); });
yearSelect.addEventListener('change', updateSpecsBasedOnYear);

(function initCascade() {
  refreshBrands(INITIAL.brand);
  if (INITIAL.brand) { 
      refreshModels(INITIAL.model); 
      if (INITIAL.model) { 
          refreshYears(INITIAL.year); 
          updateSpecsBasedOnYear(); 
      } 
  } else { 
      resetSelect(modelSelect, 'Select model'); 
      resetSelect(yearSelect, 'Select year'); 
  }
})();
</script>
<?php include 'footer.php'; ?>