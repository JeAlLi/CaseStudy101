<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

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

$f = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_listing'])) {
    
    $type  = 'Car'; 
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year_raw = trim($_POST['year'] ?? '');
    $mileage_raw = trim($_POST['mileage'] ?? '');
    $asking_price_raw = trim($_POST['asking_price'] ?? '');
    $location = trim($_POST['location'] ?? 'Admin Scraped Data');
    
    $transmission  = in_array($_POST['transmission'] ?? '', $TRANSMISSIONS) ? $_POST['transmission'] : $TRANSMISSIONS[0];
    $fuel_type     = in_array($_POST['fuel_type'] ?? '', $FUEL_TYPES) ? $_POST['fuel_type'] : $FUEL_TYPES[0];
    $modifications = in_array($_POST['modifications'] ?? '', $MODIFICATIONS) ? $_POST['modifications'] : $MODIFICATIONS[0];
    $registration  = in_array($_POST['registration'] ?? '', $REGISTRATIONS) ? $_POST['registration'] : $REGISTRATIONS[0];
    $maintenance   = in_array($_POST['maintenance'] ?? '', $MAINTENANCE) ? $_POST['maintenance'] : $MAINTENANCE[2];
    $accidents     = in_array($_POST['accidents'] ?? '', $ACCIDENTS) ? $_POST['accidents'] : $ACCIDENTS[0];
    $color = normalize_title($_POST['color'] ?? '');

    // STRICT VALIDATION
    if ($brand === '') { $errors['brand'] = 'Required'; }
    if ($model === '') { $errors['model'] = 'Required'; }
    if ($year_raw === '') { $errors['year'] = 'Required'; }
    
    if ($mileage_raw === '' || !ctype_digit($mileage_raw) || (int)$mileage_raw < 0 || (int)$mileage_raw > 999999) { 
        $errors['mileage'] = 'Invalid (0 - 999,999)'; 
    }
    
    if ($asking_price_raw === '' || !is_numeric($asking_price_raw) || (float)$asking_price_raw < 20000 || (float)$asking_price_raw > 50000000) { 
        $errors['asking_price'] = 'Invalid (20k - 50M)'; 
    }

    if ($year_raw !== '') {
        $y = (int)$year_raw;
        if ($fuel_type === 'Electric' && $y < 2010) { $errors['fuel_type'] = 'Not valid for this year.'; }
        if ($fuel_type === 'Hybrid' && $y < 2005) { $errors['fuel_type'] = 'Not valid for this year.'; }
        if (in_array($transmission, ['Automatic', 'CVT']) && $y < 1995) { $errors['transmission'] = 'Not valid for this year.'; }
    }

    if (empty($errors)) {
        $insert = $pdo->prepare("INSERT INTO vehicle_listings
            (vehicle_type, brand, model, year_manufactured, mileage, color, transmission, fuel_type,
             modifications, registration_status, maintenance_history, accident_history,
             asking_price, predicted_value, location, status, listing_type)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'approved','scraped')");
        
        $insert->execute([
            $type, normalize_title($brand), normalize_title($model), (int)$year_raw, (int)$mileage_raw, 
            $color !== '' ? $color : null, $transmission, $fuel_type, $modifications, 
            $registration, $maintenance, $accidents, (float)$asking_price_raw, null, 
            $location !== '' ? $location : null
        ]);
        
        $success = true;
        $f = []; 
    }
}
include 'header.php';
?>

<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic" style="background:var(--indigo-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></div>
      <h3>Add External Listing</h3>
    </div>
    <div class="panel-sub">Manually encode verified listings. These go live instantly to the Gathered Data tab.</div>
    
    <?php if ($success): ?>
      <div class="alert alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        <span><b>Listing Added!</b> The dataset has been updated.</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <span>Please fix the highlighted errors below.</span>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-grid">
        <div class="field"><label>Brand</label>
          <select name="brand" id="brand"><option value="">Select brand</option></select>
          <div class="field-error"><?php echo htmlspecialchars($errors['brand'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Model</label>
          <select name="model" id="model" disabled><option value="">Select model</option></select>
          <div class="field-error"><?php echo htmlspecialchars($errors['model'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Year</label>
          <select name="year" id="year" disabled><option value="">Select year</option></select>
          <div class="field-error"><?php echo htmlspecialchars($errors['year'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Mileage (km)</label>
          <input type="number" name="mileage" min="0" max="999999" oninput="this.value = this.value.slice(0, 6)" value="<?php echo htmlspecialchars($f['mileage'] ?? ''); ?>">
          <div class="field-error"><?php echo htmlspecialchars($errors['mileage'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Listed Price (₱)</label>
          <input type="number" name="asking_price" min="20000" max="50000000" oninput="if(this.value.length > 8) this.value = this.value.slice(0,8);" value="<?php echo htmlspecialchars($f['asking_price'] ?? ''); ?>">
          <div class="field-error"><?php echo htmlspecialchars($errors['asking_price'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Data Source (URL or Site)</label>
          <input type="text" name="location" maxlength="100" placeholder="e.g., AutoDeal, FB Marketplace" value="<?php echo htmlspecialchars($f['location'] ?? ''); ?>">
        </div>
        <div class="field"><label>Transmission</label>
          <select name="transmission" id="transmission"><?php foreach ($TRANSMISSIONS as $opt): echo option($opt, $f['transmission'] ?? null); endforeach; ?></select>
          <div class="field-error"><?php echo htmlspecialchars($errors['transmission'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Fuel Type</label>
          <select name="fuel_type" id="fuel_type"><?php foreach ($FUEL_TYPES as $opt): echo option($opt, $f['fuel_type'] ?? null); endforeach; ?></select>
          <div class="field-error"><?php echo htmlspecialchars($errors['fuel_type'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Condition Summary</label>
          <select name="maintenance"><?php foreach ($MAINTENANCE as $opt): echo option($opt, $f['maintenance'] ?? 'Average'); endforeach; ?></select>
        </div>
      </div>
      
      <div style="margin-top: 20px;">
        <input type="hidden" name="add_listing" value="1">
        <button type="submit" class="predict-btn">Add to Database</button>
      </div>
    </form>
  </div>
</div>

<script>
const CATALOG = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?>;
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
        if (opt.value === 'Automatic' || opt.value === 'CVT') { opt.disabled = (year < 1995); }
    });
    if (transSelect.options[transSelect.selectedIndex].disabled) { transSelect.value = 'Manual'; }
}

function resetSelect(select, placeholder) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = true;
}

function fillSelect(select, values, placeholder) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = false;
  values.forEach(v => {
    const o = document.createElement('option'); o.value = v; o.textContent = v;
    select.appendChild(o);
  });
}

fillSelect(brandSelect, Object.keys(CATALOG['Car'] || {}), 'Select brand');

brandSelect.addEventListener('change', function() {
  const brand = this.value;
  if (!brand || !CATALOG['Car'][brand]) { resetSelect(modelSelect, 'Select model'); resetSelect(yearSelect, 'Select year'); return; }
  fillSelect(modelSelect, Object.keys(CATALOG['Car'][brand]), 'Select model');
  resetSelect(yearSelect, 'Select year');
});

modelSelect.addEventListener('change', function() {
  const brand = brandSelect.value; const model = this.value;
  const info = CATALOG['Car'][brand][model];
  if (!info) { resetSelect(yearSelect, 'Select year'); return; }
  const years = [];
  for (let y = (info.year_end || CURRENT_YEAR); y >= info.year_start; y--) years.push(y);
  fillSelect(yearSelect, years, 'Select year');
});

yearSelect.addEventListener('change', updateSpecsBasedOnYear);
</script>
<?php include '../footer.php'; ?>