<?php
require_once 'db.php';
$current_page = 'predict';
$catalog = get_catalog($pdo); 
$current_year = (int)date('Y');
$estimated_price = null;
$breakdown = [];
$errors = [];

// Dropdown option sets
$TRANSMISSIONS = ['Automatic', 'Manual', 'CVT'];
$FUEL_TYPES    = ['Gasoline', 'Diesel', 'Hybrid', 'Electric'];
$MODIFICATIONS = ['Stock / None', 'Minor Modifications', 'Major Modifications'];
$REGISTRATIONS = ['Updated', 'Expired', 'Unknown'];
$MAINTENANCE   = ['Excellent', 'Good', 'Average', 'Poor'];
$ACCIDENTS     = ['None', 'Minor', 'Major', 'Unknown'];

// Peso effect of each condition/history option
$REGISTRATION_ADJ = ['Updated' => 0, 'Expired' => -8000, 'Unknown' => -3000];
$MAINTENANCE_ADJ  = ['Excellent' => 15000, 'Good' => 5000, 'Average' => 0, 'Poor' => -10000];
$ACCIDENT_ADJ     = ['None' => 0, 'Minor' => -12000, 'Major' => -45000, 'Unknown' => -5000];
$MODIFICATION_ADJ = ['Stock / None' => 0, 'Minor Modifications' => -3000, 'Major Modifications' => -20000];

$f = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    
    $type  = 'Car'; 
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year_raw = trim($_POST['year'] ?? '');
    $mileage_raw = trim($_POST['mileage'] ?? '');
    $asking_price_raw = trim($_POST['asking_price'] ?? '');
    
    $transmission  = in_array($_POST['transmission'] ?? '', $TRANSMISSIONS, true) ? $_POST['transmission'] : $TRANSMISSIONS[0];
    $fuel_type     = in_array($_POST['fuel_type'] ?? '', $FUEL_TYPES, true) ? $_POST['fuel_type'] : $FUEL_TYPES[0];
    $modifications = in_array($_POST['modifications'] ?? '', $MODIFICATIONS, true) ? $_POST['modifications'] : $MODIFICATIONS[0];
    $registration  = in_array($_POST['registration'] ?? '', $REGISTRATIONS, true) ? $_POST['registration'] : $REGISTRATIONS[0];
    $maintenance   = in_array($_POST['maintenance'] ?? '', $MAINTENANCE, true) ? $_POST['maintenance'] : $MAINTENANCE[2];
    $accidents     = in_array($_POST['accidents'] ?? '', $ACCIDENTS, true) ? $_POST['accidents'] : $ACCIDENTS[0];
    $color = normalize_title($_POST['color'] ?? '');

    // ---- Validation ----
    $modelInfo = null; 

    if ($brand === '') {
        $errors['brand'] = 'Please select a brand.';
    } elseif (isset($catalog[$type]) && !isset($catalog[$type][$brand])) {
        $errors['brand'] = 'Please choose a brand from the list.';
    }

    if ($model === '') {
        $errors['model'] = 'Please select a model.';
    } elseif (isset($catalog[$type][$brand]) && !array_key_exists($model, $catalog[$type][$brand])) {
        $errors['model'] = 'Please choose a model from the list.';
    } elseif (isset($catalog[$type][$brand][$model])) {
        $modelInfo = $catalog[$type][$brand][$model];
    }

    if ($year_raw === '' || !ctype_digit($year_raw)) {
        $errors['year'] = 'Please select a year.';
    } elseif ($modelInfo !== null) {
        $yearMin = $modelInfo['year_start'];
        $yearMax = $modelInfo['year_end'] ?? $current_year;
        if ((int)$year_raw < $yearMin || (int)$year_raw > $yearMax) {
            $errors['year'] = "Please select a year between {$yearMin} and {$yearMax} for this model.";
        }
    }

    // New Mileage Constraint (Max 999,999)
    if ($mileage_raw === '' || !ctype_digit($mileage_raw) || (int)$mileage_raw < 0 || (int)$mileage_raw > 999999) {
        $errors['mileage'] = 'Please enter a valid mileage (0 to 999,999 km).';
    }

    // New Real-World Specs Constraints
    if ($year_raw !== '') {
        $y = (int)$year_raw;
        if ($fuel_type === 'Electric' && $y < 2010) {
            $errors['fuel_type'] = 'Electric engines were not widely available before 2010.';
        }
        if ($fuel_type === 'Hybrid' && $y < 2005) {
            $errors['fuel_type'] = 'Hybrid engines were not widely available before 2005.';
        }
        if (in_array($transmission, ['Automatic', 'CVT']) && $y < 1995) {
            $errors['transmission'] = 'Automatic/CVT transmissions are not selectable for vehicles before 1995.';
        }
    }

    if ($asking_price_raw !== '' && (!is_numeric($asking_price_raw) || (float)$asking_price_raw < 0)) {
        $errors['asking_price'] = 'Asking price must be a positive number.';
    }

    if (empty($errors)) {
        $year = (int)$year_raw;
        $mileage = (int)$mileage_raw;

        $stmt = $pdo->prepare("SELECT * FROM reference_weights WHERE vehicle_type = ? AND LOWER(brand) = LOWER(?) AND LOWER(model) = LOWER(?) LIMIT 1");
        $stmt->execute([$type, $brand, $model]);
        $vehicle_data = $stmt->fetch();

        if ($vehicle_data) {
            $age = max(0, $current_year - $year);
            $base = (float)$vehicle_data['base_price'];
            
            $year_adj    = $age * (float)$vehicle_data['yearly_depreciation'];
            $mileage_adj = $mileage * (float)$vehicle_data['mileage_penalty'];
            
            $cond_adj = $REGISTRATION_ADJ[$registration]
                      + $MAINTENANCE_ADJ[$maintenance]
                      + $ACCIDENT_ADJ[$accidents]
                      + $MODIFICATION_ADJ[$modifications];
                      
            $math_price = $base - $year_adj - $mileage_adj + $cond_adj;
            $math_price = max(1000, $math_price);

            // Dynamic Market Blend
            $marketStmt = $pdo->prepare("SELECT AVG(asking_price) as avg_market_price, COUNT(*) as listing_count FROM vehicle_listings WHERE LOWER(brand) = LOWER(?) AND LOWER(model) = LOWER(?) AND status = 'approved' AND asking_price > 0");
            $marketStmt->execute([$brand, $model]);
            $marketData = $marketStmt->fetch();

            $listing_count = (int)$marketData['listing_count'];
            $market_data_used = false;

            if ($listing_count > 0) {
                $avg_market_price = (float)$marketData['avg_market_price'];
                $adjusted_market_price = $avg_market_price + $cond_adj;
                $final_price = ($math_price + $adjusted_market_price) / 2;
                $market_data_used = true;
            } else {
                $final_price = $math_price;
            }
            
            $estimated_price = max(1000, $final_price);
            $range_low  = $estimated_price * 0.947;
            $range_high = $estimated_price * 1.053;
            
            $breakdown = [
                'base' => $base,
                'year_adj' => $year_adj,
                'mileage_adj' => $mileage_adj,
                'cond_adj' => $cond_adj,
                'market_used' => $market_data_used,
                'listing_count' => $listing_count
            ];
        } else {
            $errors['model'] = "We don't have pricing data for this exact brand and model yet.";
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
      <div class="panel-sub">Fill in the vehicle's details below. Fields marked <span class="req">*</span> are required.</div>
      
      <div class="vehicle-preview hidden" id="vehiclePreview">
        <div class="vp-icon" id="vpIcon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="<?php echo car_icon_path(); ?>"/></svg></div>
        <div>
          <div class="vp-name" id="vpName"></div>
          <div class="vp-meta" id="vpMeta"></div>
        </div>
      </div>
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16v.01"/></svg>
          <span>Please fix the highlighted field<?php echo count($errors) > 1 ? 's' : ''; ?> below before predicting a price.</span>
        </div>
      <?php endif; ?>
      
      <form method="POST" id="predictForm" novalidate>
        
        <!-- Section: Vehicle Information -->
        <div class="form-section">
          <div class="form-section-title">Vehicle Information</div>
          <div class="form-grid">
            <div class="field"><label>Brand <span class="req">*</span></label>
              <select name="brand" id="brand"><option value="">Select brand</option></select>
              <div class="field-error" data-for="brand"><?php echo e($errors['brand'] ?? ''); ?></div>
            </div>
            
            <div class="field"><label>Model <span class="req">*</span></label>
              <select name="model" id="model" disabled><option value="">Select model</option></select>
              <div class="field-error" data-for="model"><?php echo e($errors['model'] ?? ''); ?></div>
            </div>
            
            <div class="field"><label>Year of Manufacture <span class="req">*</span></label>
              <select name="year" id="year" disabled><option value="">Select year</option></select>
              <div class="field-error" data-for="year"><?php echo e($errors['year'] ?? ''); ?></div>
            </div>
            
            <div class="field"><label>Mileage <span class="req">*</span></label>
              <div class="input-suffix">
                <!-- Added maxlength slice and standard max attribute to cap numbers visually and logically -->
                <input type="number" name="mileage" id="mileage" min="0" max="999999" step="1" inputmode="numeric" 
                       oninput="this.value = this.value.slice(0, 6)"
                       value="<?php echo e($f['mileage'] ?? ''); ?>">
                <span>km</span>
              </div>
              <div class="field-error" data-for="mileage"><?php echo e($errors['mileage'] ?? ''); ?></div>
            </div>
          </div>
        </div>
        
        <!-- Section: Specifications -->
        <div class="form-section">
          <div class="form-section-title">Specifications</div>
          <div class="form-grid">
            <div class="field"><label>Transmission</label>
              <!-- Added ID for JavaScript manipulation -->
              <select name="transmission" id="transmission">
                <?php foreach ($TRANSMISSIONS as $opt): echo option($opt, $f['transmission'] ?? null); endforeach; ?>
              </select>
              <div class="field-error" data-for="transmission"><?php echo e($errors['transmission'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Fuel Type</label>
              <!-- Added ID for JavaScript manipulation -->
              <select name="fuel_type" id="fuel_type">
                <?php foreach ($FUEL_TYPES as $opt): echo option($opt, $f['fuel_type'] ?? null); endforeach; ?>
              </select>
              <div class="field-error" data-for="fuel_type"><?php echo e($errors['fuel_type'] ?? ''); ?></div>
            </div>
            <div class="field"><label>Color <span class="opt">(optional)</span></label>
              <input type="text" name="color" placeholder="e.g., Pearl White" value="<?php echo e($f['color'] ?? ''); ?>">
            </div>
            <div class="field"><label>Modifications</label>
              <select name="modifications">
                <?php foreach ($MODIFICATIONS as $opt): echo option($opt, $f['modifications'] ?? null); endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Section: Condition & History -->
        <div class="form-section">
          <div class="form-section-title">Condition &amp; History</div>
          <div class="form-grid">
            <div class="field"><label>Registration Status (LTO)</label>
              <select name="registration"><?php foreach ($REGISTRATIONS as $opt): echo option($opt, $f['registration'] ?? null); endforeach; ?></select>
            </div>
            <div class="field"><label>Maintenance History</label>
              <select name="maintenance"><?php foreach ($MAINTENANCE as $opt): echo option($opt, $f['maintenance'] ?? 'Average'); endforeach; ?></select>
            </div>
            <div class="field" style="grid-column: span 2;"><label>Accident History</label>
              <select name="accidents"><?php foreach ($ACCIDENTS as $opt): echo option($opt, $f['accidents'] ?? null); endforeach; ?></select>
            </div>
          </div>
        </div>
        
        <!-- Section: Pricing -->
        <div class="form-section">
          <div class="form-section-title">Pricing <span class="opt">(optional)</span></div>
          <div class="form-grid">
            <div class="field" style="grid-column: span 2;"><label>Your Asking Price <span class="opt">(optional - for your reference)</span></label>
              <input type="number" name="asking_price" min="0" step="1" placeholder="e.g., 700000" value="<?php echo e($f['asking_price'] ?? ''); ?>">
              <div class="field-error" data-for="asking_price"><?php echo e($errors['asking_price'] ?? ''); ?></div>
            </div>
          </div>
        </div>
        
        <div class="btn-row">
          <input type="hidden" name="predict" value="1">
          <button type="submit" class="predict-btn" id="predictBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>
            <span id="predictBtnLabel">Predict Price</span>
          </button>
          <button type="reset" class="reset-btn" id="resetBtn">Reset</button>
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
              <div class="row"><span>Base price (similar listings)</span><span class="amt">₱<?php echo number_format($breakdown['base']); ?></span></div>
              <div class="row"><span>Age depreciation</span><span class="amt neg">-₱<?php echo number_format($breakdown['year_adj']); ?></span></div>
              <div class="row"><span>Mileage adjustment</span><span class="amt neg">-₱<?php echo number_format($breakdown['mileage_adj']); ?></span></div>
              
              <?php if ($breakdown['market_used']): ?>
              <div class="row" style="background: var(--indigo-soft); margin: -4px -14px; padding: 10px 14px; border-radius: 6px;">
                  <span style="color: var(--indigo-dark); font-weight: 500;">Market adjustment (<?php echo $breakdown['listing_count']; ?> live listing<?php echo $breakdown['listing_count'] > 1 ? 's' : ''; ?>)</span>
                  <span class="amt pos" style="color: var(--indigo-dark);">Applied</span>
              </div>
              <?php endif; ?>

              <div class="row">
                  <span>Condition &amp; history impact</span>
                  <?php if ($breakdown['cond_adj'] >= 0): ?>
                      <span class="amt pos">+₱<?php echo number_format($breakdown['cond_adj']); ?></span>
                  <?php else: ?>
                      <span class="amt neg">-₱<?php echo number_format(abs($breakdown['cond_adj'])); ?></span>
                  <?php endif; ?>
              </div>
              <div class="row total"><span>Estimated price</span><span class="amt">₱<?php echo number_format($estimated_price); ?></span></div>
            </div>
            
            <div class="note">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v.01M12 11v5"/></svg>
              <span>This is a data-based estimate. Actual selling price may vary with real-world condition and negotiation.</span>
            </div>
        <?php else: ?>
            <div class="empty-state">
                Fill out the form and click <b>Predict Price</b> to generate a valuation.
            </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
const CATALOG = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?>;
const BODY_TYPES = <?php echo json_encode(get_body_types(), JSON_UNESCAPED_UNICODE); ?>;
const BODY_STYLES = <?php echo json_encode(get_body_type_styles(), JSON_UNESCAPED_UNICODE); ?>;

const INITIAL = {
  brand: <?php echo json_encode($f['brand'] ?? ''); ?>,
  model: <?php echo json_encode($f['model'] ?? ''); ?>,
  year: <?php echo json_encode($f['year'] ?? ''); ?>
};
const CURRENT_YEAR = <?php echo $current_year; ?>;

const brandSelect = document.getElementById('brand');
const modelSelect = document.getElementById('model');
const yearSelect  = document.getElementById('year');
const transSelect = document.getElementById('transmission');
const fuelSelect  = document.getElementById('fuel_type');
const vehiclePreview = document.getElementById('vehiclePreview');
const vpIcon = document.getElementById('vpIcon');
const vpName = document.getElementById('vpName');
const vpMeta = document.getElementById('vpMeta');

function updatePreview() {
  const brand = brandSelect.value;
  const model = modelSelect.value;
  if (!brand || !model) {
    vehiclePreview.classList.add('hidden');
    return;
  }
  const bodyType = BODY_TYPES[brand + '|' + model] || 'Vehicle';
  const style = BODY_STYLES[bodyType] || ['text-faint', 'paper'];
  
  vpIcon.style.background = 'var(--' + style[1] + ')';
  vpIcon.style.color = 'var(--' + style[0] + ')';
  vpName.textContent = brand + ' ' + model;
  vpMeta.textContent = bodyType + (yearSelect.value ? ' • ' + yearSelect.value : '');
  vehiclePreview.classList.remove('hidden');
}

function updateSpecsBasedOnYear() {
    const year = parseInt(yearSelect.value);
    if (!year) return;

    // Fuel Type Logic (Disable Electric pre-2010, Hybrid pre-2005)
    Array.from(fuelSelect.options).forEach(opt => {
        if (opt.value === 'Electric') opt.disabled = (year < 2010);
        if (opt.value === 'Hybrid') opt.disabled = (year < 2005);
    });
    // If the currently selected option is now disabled, fallback to Gasoline
    if (fuelSelect.options[fuelSelect.selectedIndex].disabled) {
        fuelSelect.value = 'Gasoline';
    }

    // Transmission Logic (Disable Automatic/CVT pre-1995)
    Array.from(transSelect.options).forEach(opt => {
        if (opt.value === 'Automatic' || opt.value === 'CVT') {
            opt.disabled = (year < 1995);
        }
    });
    // Fallback to Manual if the current selection is invalid
    if (transSelect.options[transSelect.selectedIndex].disabled) {
        transSelect.value = 'Manual';
    }
}

function resetSelect(select, placeholder, disabled) {
  select.innerHTML = '';
  const opt = document.createElement('option');
  opt.value = '';
  opt.textContent = placeholder;
  select.appendChild(opt);
  select.disabled = disabled;
}

function fillSelect(select, values, selected, placeholder) {
  resetSelect(select, placeholder, false);
  values.forEach(function(v) {
    const opt = document.createElement('option');
    opt.value = v;
    opt.textContent = v;
    if (String(v) === String(selected)) opt.selected = true;
    select.appendChild(opt);
  });
}

function refreshBrands(selectedBrand) {
  const type = 'Car';
  if (!CATALOG[type]) {
    resetSelect(brandSelect, 'Select brand', true);
    return;
  }
  fillSelect(brandSelect, Object.keys(CATALOG[type]), selectedBrand, 'Select brand');
}

function refreshModels(selectedModel) {
  const type = 'Car'; 
  const brand = brandSelect.value;
  if (!brand || !CATALOG[type] || !CATALOG[type][brand]) {
    resetSelect(modelSelect, 'Select model', true);
    return;
  }
  fillSelect(modelSelect, Object.keys(CATALOG[type][brand]), selectedModel, 'Select model');
}

function refreshYears(selectedYear) {
  const type = 'Car';
  const brand = brandSelect.value;
  const model = modelSelect.value;
  const info = (brand && model && CATALOG[type] && CATALOG[type][brand]) ? CATALOG[type][brand][model] : null;
  
  if (!info) {
    resetSelect(yearSelect, 'Select year', true);
    return;
  }
  
  const minYear = info.year_start;
  const maxYear = info.year_end || CURRENT_YEAR;
  const years = [];
  for (let y = maxYear; y >= minYear; y--) years.push(y);
  
  fillSelect(yearSelect, years, selectedYear, 'Select year');
}

brandSelect.addEventListener('change', function() {
  refreshModels(null);
  resetSelect(yearSelect, 'Select year', true);
  updatePreview();
});

modelSelect.addEventListener('change', function() {
  refreshYears(null);
  updatePreview();
});

yearSelect.addEventListener('change', function() {
    updatePreview();
    updateSpecsBasedOnYear(); // Fire logic when user changes year
});

(function initCascade() {
  refreshBrands(INITIAL.brand);
  if (INITIAL.brand) {
    refreshModels(INITIAL.model);
    if (INITIAL.model) {
      refreshYears(INITIAL.year);
      updateSpecsBasedOnYear(); // Fire logic on load if year is pre-filled
    }
  } else {
    resetSelect(modelSelect, 'Select model', true);
    resetSelect(yearSelect, 'Select year', true);
  }
  updatePreview();
})();

// ---- Client-side validation ----
const form = document.getElementById('predictForm');
const predictBtn = document.getElementById('predictBtn');
const predictBtnLabel = document.getElementById('predictBtnLabel');

function setError(fieldName, message) {
  const el = form.querySelector('.field-error[data-for="' + fieldName + '"]');
  if (el) el.textContent = message || '';
}

function validateForm() {
  let valid = true;
  ['brand','model','year','mileage','asking_price'].forEach(function(f){ setError(f, ''); });
  
  if (!brandSelect.value) { setError('brand', 'Please select a brand.'); valid = false; }
  if (!modelSelect.value) { setError('model', 'Please select a model.'); valid = false; }
  if (!yearSelect.value)  { setError('year', 'Please select a year.'); valid = false; }
  
  const mileageVal = form.mileage.value;
  if (mileageVal === '' || isNaN(mileageVal) || parseInt(mileageVal, 10) < 0) {
    setError('mileage', 'Please enter a valid mileage (0 or greater).');
    valid = false;
  }
  
  const askingVal = form.asking_price.value;
  if (askingVal !== '' && (isNaN(askingVal) || parseFloat(askingVal) < 0)) {
    setError('asking_price', 'Asking price must be a positive number.');
    valid = false;
  }
  
  return valid;
}

form.addEventListener('submit', function(evt) {
  if (!validateForm()) {
    evt.preventDefault();
    return;
  }
  predictBtn.disabled = true;
  predictBtn.classList.add('loading');
  predictBtnLabel.textContent = 'Predicting...';
});

document.getElementById('resetBtn').addEventListener('click', function() {
  window.setTimeout(function() {
    resetSelect(brandSelect, 'Select brand', true);
    refreshBrands(null);
    resetSelect(modelSelect, 'Select model', true);
    resetSelect(yearSelect, 'Select year', true);
    
    // Reset disabled states on fuel and transmission
    Array.from(fuelSelect.options).forEach(opt => opt.disabled = false);
    Array.from(transSelect.options).forEach(opt => opt.disabled = false);
    
    ['brand','model','year','mileage','asking_price'].forEach(function(f){ setError(f, ''); });
    updatePreview();
  }, 0);
});
</script>

<?php include 'footer.php'; ?>