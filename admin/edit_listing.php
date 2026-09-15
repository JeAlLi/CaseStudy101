<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: manage_listings.php"); exit; }

$catalog = get_catalog($pdo); 
$current_year = (int)date('Y');
$success = false;
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) { header("Location: manage_listings.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_listing'])) {
    
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year_raw = trim($_POST['year'] ?? '');
    $mileage_raw = trim($_POST['mileage'] ?? '');
    $asking_price_raw = trim($_POST['asking_price'] ?? '');
    
    $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected']) ? $_POST['status'] : 'pending';
    $listing_type = in_array($_POST['listing_type'] ?? '', ['scraped', 'user_sale']) ? $_POST['listing_type'] : 'scraped';
    
    $seller_name = trim($_POST['seller_name'] ?? '');
    $seller_contact = trim($_POST['seller_contact'] ?? '');

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

    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE vehicle_listings SET 
            brand = ?, model = ?, year_manufactured = ?, mileage = ?, asking_price = ?, 
            status = ?, listing_type = ?, seller_name = ?, seller_contact = ?
            WHERE id = ?");
        
        $update->execute([
            normalize_title($brand), normalize_title($model), (int)$year_raw, (int)$mileage_raw, 
            (float)$asking_price_raw, $status, $listing_type, 
            $seller_name !== '' ? $seller_name : null, 
            $seller_contact !== '' ? $seller_contact : null, 
            $id
        ]);
        
        $success = true;
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
    }
}
include 'header.php';
?>

<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic" style="background:var(--indigo-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
      <h3>Edit Listing #<?php echo $id; ?></h3>
    </div>
    
    <?php if ($success): ?>
      <div class="alert alert-success"><span><b>Listing Updated!</b> Changes saved to the database.</span></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-grid">
        <div class="field"><label>Listing Type</label>
          <select name="listing_type">
            <option value="scraped" <?php echo ($listing['listing_type'] === 'scraped') ? 'selected' : ''; ?>>Admin Gathered (Scraped)</option>
            <option value="user_sale" <?php echo ($listing['listing_type'] === 'user_sale') ? 'selected' : ''; ?>>Active Marketplace (User Sale)</option>
          </select>
        </div>
        <div class="field"><label>Approval Status</label>
          <select name="status">
            <option value="pending" <?php echo ($listing['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo ($listing['status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo ($listing['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>

        <div class="field"><label>Brand</label><select name="brand" id="brand"><option value="">Select brand</option></select></div>
        <div class="field"><label>Model</label><select name="model" id="model" disabled><option value="">Select model</option></select></div>
        <div class="field"><label>Year</label><select name="year" id="year" disabled><option value="">Select year</option></select></div>
        
        <div class="field"><label>Mileage (km)</label>
          <input type="number" name="mileage" min="0" max="999999" oninput="this.value = this.value.slice(0, 6)" value="<?php echo htmlspecialchars($_POST['mileage'] ?? $listing['mileage']); ?>">
          <div class="field-error"><?php echo htmlspecialchars($errors['mileage'] ?? ''); ?></div>
        </div>
        <div class="field"><label>Listed Price (₱)</label>
          <input type="number" name="asking_price" min="20000" max="50000000" oninput="if(this.value.length > 8) this.value = this.value.slice(0,8);" value="<?php echo htmlspecialchars($_POST['asking_price'] ?? $listing['asking_price']); ?>">
          <div class="field-error"><?php echo htmlspecialchars($errors['asking_price'] ?? ''); ?></div>
        </div>
      </div>

      <div class="form-section-title" style="margin-top: 24px;">Seller Information (For User Sales)</div>
      <div class="form-grid">
        <div class="field"><label>Seller Name</label>
          <input type="text" name="seller_name" maxlength="100" value="<?php echo htmlspecialchars($_POST['seller_name'] ?? $listing['seller_name']); ?>">
        </div>
        <div class="field"><label>Seller Contact</label>
          <input type="text" name="seller_contact" maxlength="100" value="<?php echo htmlspecialchars($_POST['seller_contact'] ?? $listing['seller_contact']); ?>">
        </div>
      </div>
      
      <div class="btn-row" style="margin-top: 20px;">
        <input type="hidden" name="update_listing" value="1">
        <button type="submit" class="predict-btn">Save Changes</button>
        <a href="manage_listings.php" class="reset-btn" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 11px 18px;">Back to Listings</a>
      </div>
    </form>
  </div>
</div>

<script>
const CATALOG = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?>;
const CURRENT_YEAR = <?php echo $current_year; ?>;
const INITIAL = { 
  brand: <?php echo json_encode($_POST['brand'] ?? $listing['brand']); ?>, 
  model: <?php echo json_encode($_POST['model'] ?? $listing['model']); ?>, 
  year: <?php echo json_encode($_POST['year'] ?? $listing['year_manufactured']); ?> 
};

const brandSelect = document.getElementById('brand');
const modelSelect = document.getElementById('model');
const yearSelect  = document.getElementById('year');

function resetSelect(select, placeholder) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = true;
}
function fillSelect(select, values, selected, placeholder) {
  select.innerHTML = '';
  const opt = document.createElement('option'); opt.value = ''; opt.textContent = placeholder;
  select.appendChild(opt); select.disabled = false;
  values.forEach(v => {
    const o = document.createElement('option'); o.value = v; o.textContent = v;
    if (String(v) === String(selected)) o.selected = true;
    select.appendChild(o);
  });
}
function refreshBrands(selectedBrand) { fillSelect(brandSelect, Object.keys(CATALOG['Car'] || {}), selectedBrand, 'Select brand'); }
function refreshModels(selectedModel) {
  const brand = brandSelect.value;
  if (!brand || !CATALOG['Car'][brand]) { resetSelect(modelSelect, 'Select model'); resetSelect(yearSelect, 'Select year'); return; }
  fillSelect(modelSelect, Object.keys(CATALOG['Car'][brand]), selectedModel, 'Select model');
}
function refreshYears(selectedYear) {
  const brand = brandSelect.value; const model = modelSelect.value;
  const info = (brand && model && CATALOG['Car'][brand]) ? CATALOG['Car'][brand][model] : null;
  if (!info) { resetSelect(yearSelect, 'Select year'); return; }
  const years = [];
  for (let y = (info.year_end || CURRENT_YEAR); y >= info.year_start; y--) years.push(y);
  fillSelect(yearSelect, years, selectedYear, 'Select year');
}

brandSelect.addEventListener('change', function() { refreshModels(null); resetSelect(yearSelect, 'Select year'); });
modelSelect.addEventListener('change', function() { refreshYears(null); });

(function init() {
  refreshBrands(INITIAL.brand);
  if (INITIAL.brand) { refreshModels(INITIAL.model); if (INITIAL.model) refreshYears(INITIAL.year); }
})();
</script>
<?php include '../footer.php'; ?>