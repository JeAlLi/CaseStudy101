<?php
session_start();
require_once '../db.php';
<<<<<<< HEAD
require_once '../risk_engine.php';
$current_page = 'manage_listings';

$success_message = '';
$error_message = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_listings.php");
=======

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
>>>>>>> parent of 3985f8d (posh)
    exit;
}

<<<<<<< HEAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $model = $_POST['model'];
        $year_manufactured = $_POST['year_manufactured'];
        $mileage = $_POST['mileage'];
        $color = $_POST['color'];
        $transmission = $_POST['transmission'];
        $fuel_type = $_POST['fuel_type'];
        $modifications = $_POST['modifications']; 
        
        $maintenance_history = $_POST['maintenance_history'];
        $accident_history = $_POST['accident_history'];
        $first_owner = $_POST['first_owner'];
        $flooded = $_POST['flooded'];
        
        $registration_status = $_POST['registration_status'];
        $or_cr_ready = $_POST['or_cr_ready'];
        $fully_paid = $_POST['fully_paid'];
        $deed_of_sale_ready = $_POST['deed_of_sale_ready'];
        
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price']); 
        $price_negotiability = $_POST['price_negotiability'];
        $payment_methods = $_POST['payment_methods']; 
        $trade_in = $_POST['trade_in'];
        
        $seller_name = $_POST['seller_name'];
        $seller_contact = $_POST['seller_contact'];
        $location = $_POST['location'];
        $seller_notes = $_POST['seller_notes'];
        
        $status = $_POST['status'];
        $verification_status = in_array($_POST['verification_status'] ?? '', ['mechanic_verified', 'unverified']) ? $_POST['verification_status'] : 'unverified';

        // Recalculate Risk Score on edit
        $risk = compute_risk_score($pdo, [
            'brand' => 'Toyota', 'model' => $model,
            'year_manufactured' => (int)$year_manufactured,
            'mileage' => (int)$mileage,
            'asking_price' => (float)$asking_price,
            'location' => $location,
            'city' => '', // Will fall back to location string in risk engine
            'accident_history' => $accident_history,
            'is_flood_flagged' => ($flooded === 'Yes'),
            'verification_status' => $verification_status
        ]);

        $updateStmt = $pdo->prepare("
            UPDATE vehicle_listings SET 
                model=?, year_manufactured=?, mileage=?, color=?, 
                transmission=?, fuel_type=?, modifications=?, 
                maintenance_history=?, accident_history=?, first_owner=?, flooded=?, 
                registration_status=?, or_cr_ready=?, fully_paid=?, deed_of_sale_ready=?, 
                asking_price=?, price_negotiability=?, payment_methods=?, trade_in=?, 
                seller_name=?, seller_contact=?, location=?, seller_notes=?, 
                status=?, verification_status=?,
                risk_score=?, risk_level=?, risk_flags=?, risk_recommendation=?, risk_evaluated_at=?
            WHERE id=?
        ");

        $updateStmt->execute([
            $model, $year_manufactured, $mileage, $color, 
            $transmission, $fuel_type, $modifications, 
            $maintenance_history, $accident_history, $first_owner, $flooded, 
            $registration_status, $or_cr_ready, $fully_paid, $deed_of_sale_ready, 
            $asking_price, $price_negotiability, $payment_methods, $trade_in, 
            $seller_name, $seller_contact, $location, $seller_notes, 
            $status, $verification_status,
            $risk['risk_score'], $risk['risk_level'], implode(' | ', $risk['flags']), $risk['recommendation'], date('Y-m-d H:i:s'),
            $listing_id
=======
// Safely capture the ID from either the URL (GET) or the Form Submission (POST)
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { 
    header("Location: manage_listings.php"); 
    exit; 
}

$catalog = get_catalog($pdo); 
$current_year = (int)date('Y');
$success = false;
$errors = [];

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) { 
    header("Location: manage_listings.php"); 
    exit; 
}

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
    
    if ($mileage_raw === '' || !ctype_digit($mileage_raw) || (int)$mileage_raw < 0 || (int)$mileage_raw > 5230930) { 
        $errors['mileage'] = 'Invalid (0 - 5,230,930)'; 
    }
    if ($asking_price_raw === '' || !is_numeric($asking_price_raw) || (float)$asking_price_raw < 20000 || (float)$asking_price_raw > 50000000) { 
        $errors['asking_price'] = 'Invalid (20k - 50M)'; 
    }

    if (empty($errors)) {
        // Explicitly UPDATE the existing record where the ID matches
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
>>>>>>> parent of 3985f8d (posh)
        ]);
        
        $success = true;
        // Refresh local variable so the form updates immediately on screen
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
    }
}
<<<<<<< HEAD

$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) { die("Listing not found."); }

include 'header.php';
?>

<style>
    .admin-container { max-width: 900px; margin: 0 auto; padding: 20px; }
    .page-header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .back-btn { font-size: 14px; color: #5865f2; text-decoration: none; font-weight: 600; }
    .form-container { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
    .form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #f3f4f6; }
    .form-section:last-child { border-bottom: none; }
    .section-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title span { background: #5865f2; color: white; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-control { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827; background: #f9fafb; }
    .form-control:focus { outline: none; border-color: #5865f2; box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1); }
    .radio-group { display: flex; gap: 16px; flex-wrap: wrap; }
    .radio-label { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #4b5563; cursor: pointer; }
    .btn-submit { background: #10b981; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; }
    .btn-submit:hover { background: #059669; }
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
</style>

<div class="view admin-container">
    <div class="page-header-nav">
        <h2 style="font-size: 24px; font-weight: 800; margin: 0;">Edit Listing #<?php echo $car['id']; ?></h2>
        <a href="manage_listings.php" class="back-btn">&larr; Back to Manage Listings</a>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <!-- Admin Controls -->
            <div class="form-section" style="background: #eff6ff; padding: 20px; border-radius: 8px; border: 1px solid #bfdbfe;">
                <div class="section-title" style="margin-bottom: 12px;"><span style="background: #3b82f6;">!</span> System Overrides</div>
                <div class="grid-2">
                    <div class="form-group" style="margin: 0;">
                        <label>Set Listing Visibility</label>
                        <select name="status" class="form-control" style="font-weight: bold; background: white;">
                            <option value="pending" <?php echo $car['status'] === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="approved" <?php echo $car['status'] === 'approved' ? 'selected' : ''; ?>>Approved (Live on site)</option>
                            <option value="rejected" <?php echo $car['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Data Provenance / Verification</label>
                        <select name="verification_status" class="form-control" style="font-weight: bold; background: white; border-color: #10b981;">
                            <option value="unverified" <?php echo (($car['verification_status'] ?? 'unverified') === 'unverified') ? 'selected' : ''; ?>>Unverified (Show Risk Score)</option>
                            <option value="mechanic_verified" <?php echo (($car['verification_status'] ?? 'unverified') === 'mechanic_verified') ? 'selected' : ''; ?>>Mechanic Verified (Hide Risk Score)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Vehicle Info -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Vehicle Selection</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Car Brand</label>
                        <input type="text" class="form-control" value="Toyota" readonly style="background-color: #e5e7eb;">
                    </div>
                    <div class="form-group">
                        <label>Car Model</label>
                        <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Year Model</label>
                        <input type="number" name="year_manufactured" class="form-control" value="<?php echo htmlspecialchars($car['year_manufactured']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Specs -->
            <div class="form-section">
                <div class="section-title"><span>2</span> Vehicle Specifications</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mileage (km)</label>
                        <input type="number" name="mileage" class="form-control" value="<?php echo htmlspecialchars($car['mileage']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Exterior Color</label>
                        <input type="text" name="color" class="form-control" value="<?php echo htmlspecialchars($car['color']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="transmission" value="Automatic" <?php echo $car['transmission'] === 'Automatic' ? 'checked' : ''; ?>> Automatic</label>
                            <label class="radio-label"><input type="radio" name="transmission" value="Manual" <?php echo $car['transmission'] === 'Manual' ? 'checked' : ''; ?>> Manual</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fuel Type</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fuel_type" value="Gasoline" <?php echo $car['fuel_type'] === 'Gasoline' ? 'checked' : ''; ?>> Gasoline</label>
                            <label class="radio-label"><input type="radio" name="fuel_type" value="Diesel" <?php echo $car['fuel_type'] === 'Diesel' ? 'checked' : ''; ?>> Diesel</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Pricing & Details</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Asking Price (PHP)</label>
                        <input type="text" id="display_price" name="asking_price" class="form-control" value="₱ <?php echo number_format((float)$car['asking_price']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Price Negotiability</label>
                        <input type="text" name="price_negotiability" class="form-control" value="<?php echo htmlspecialchars($car['price_negotiability']); ?>">
                    </div>
                    <div class="form-group"><label>Payment Methods</label><input type="text" name="payment_methods" class="form-control" value="<?php echo htmlspecialchars($car['payment_methods'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Trade In</label><input type="text" name="trade_in" class="form-control" value="<?php echo htmlspecialchars($car['trade_in'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Modifications</label><input type="text" name="modifications" class="form-control" value="<?php echo htmlspecialchars($car['modifications']); ?>"></div>
                </div>
            </div>

            <!-- Seller -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Seller Information</div>
                <div class="grid-2">
                    <div class="form-group"><label>Seller Name</label><input type="text" name="seller_name" class="form-control" value="<?php echo htmlspecialchars($car['seller_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Contact</label><input type="text" name="seller_contact" class="form-control" value="<?php echo htmlspecialchars($car['seller_contact'] ?? ''); ?>"></div>
                    <div class="form-group" style="grid-column: span 2;"><label>Location</label><input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($car['location'] ?? ''); ?>"></div>
                    <div class="form-group" style="grid-column: span 2;"><label>Notes</label><textarea name="seller_notes" class="form-control" rows="3"><?php echo htmlspecialchars($car['seller_notes'] ?? ''); ?></textarea></div>
                </div>
            </div>

            <!-- Hidden fields to keep data intact -->
            <input type="hidden" name="maintenance_history" value="<?php echo htmlspecialchars($car['maintenance_history']); ?>">
            <input type="hidden" name="accident_history" value="<?php echo htmlspecialchars($car['accident_history']); ?>">
            <input type="hidden" name="first_owner" value="<?php echo htmlspecialchars($car['first_owner']); ?>">
            <input type="hidden" name="flooded" value="<?php echo htmlspecialchars($car['flooded']); ?>">
            <input type="hidden" name="registration_status" value="<?php echo htmlspecialchars($car['registration_status']); ?>">
            <input type="hidden" name="or_cr_ready" value="<?php echo htmlspecialchars($car['or_cr_ready'] ?? 'Yes'); ?>">
            <input type="hidden" name="fully_paid" value="<?php echo htmlspecialchars($car['fully_paid'] ?? 'Yes'); ?>">
            <input type="hidden" name="deed_of_sale_ready" value="<?php echo htmlspecialchars($car['deed_of_sale_ready'] ?? 'Yes'); ?>">

            <button type="submit" class="btn-submit">Save Changes & Recalculate Risk</button>
        </form>
    </div>
</div>

<script>
document.getElementById('display_price').addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, ""); 
    if (value) {
        let intValue = parseInt(value, 10);
        this.value = "₱ " + intValue.toLocaleString('en-US');
    } else {
        this.value = "";
    }
});
</script>

=======
include 'header.php';
?>

<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic" style="background:var(--indigo-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
      <h3>Edit Listing #<?php echo $id; ?></h3>
    </div>
    
    <?php if ($success): ?>
      <div class="alert alert-success"><span><b>Listing Updated!</b> Changes securely saved to the database.</span></div>
    <?php endif; ?>

    <!-- Added specific action URL so the browser doesn't lose the ID -->
    <form method="POST" action="edit_listing.php?id=<?php echo $id; ?>" novalidate>
      
      <!-- Hidden input to guarantee the ID makes it to the PHP processor -->
      <input type="hidden" name="id" value="<?php echo $id; ?>">
      
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
          <input type="number" name="mileage" min="0" max="5230930" oninput="this.value = this.value.slice(0, 7)" value="<?php echo htmlspecialchars($_POST['mileage'] ?? $listing['mileage']); ?>">
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
  resetSelect(select, placeholder, false);
  values.forEach(v => {
    const opt = document.createElement('option'); opt.value = v; opt.textContent = v;
    if (String(v) === String(selected)) opt.selected = true;
    select.appendChild(opt);
  });
  // FIX: disable the placeholder so it can't be re-selected after a real value is chosen
  if (select.options[0]) {
    select.options[0].disabled = true;
  }
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
>>>>>>> parent of 3985f8d (posh)
<?php include '../footer.php'; ?>