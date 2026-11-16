<?php
session_start();
require_once '../db.php';
$current_page = 'manage_listings';

// Strict Admin Session Check
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_listings.php");
    exit;
}

$listing_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';

// Fetch current listing data first so we have existing photo filenames
$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    die("<div style='padding:40px; text-align:center; color:#fff; background:#1A1C16; font-family:sans-serif;'>Listing not found. <a href='manage_listings.php' style='color:#6B8E23;'>Return to Manage Listings</a></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $brand = 'Toyota';
        $model = trim($_POST['model'] ?? $car['model']);
        $year_manufactured = (int)($_POST['year_manufactured'] ?? $car['year_manufactured']);
        $mileage = (int)($_POST['mileage'] ?? $car['mileage']);
        $transmission = $_POST['transmission'] ?? $car['transmission'];
        $fuel_type = $_POST['fuel_type'] ?? $car['fuel_type'];
        
        // Color handling
        $color = $_POST['color'] ?? $car['color'];
        if ($color === 'Other / Custom' && !empty($_POST['color_custom'])) {
            $color = trim($_POST['color_custom']);
        }
        
        // Modifications handling
        $is_stock = $_POST['is_stock'] ?? 'Yes';
        if ($is_stock === 'Yes') {
            $modifications = 'Stock / None';
        } else {
            $modifications = trim($_POST['modifications_details'] ?? 'Various Modifications');
        }

        // Condition & History
        $first_owner = $_POST['first_owner'] ?? 'No';
        $maintenance_history = trim($_POST['maintenance_history'] ?? 'Good');
        $flooded = $_POST['flooded'] ?? 'No';
        
        $major_accident = $_POST['major_accident'] ?? 'No';
        if ($major_accident === 'No') {
            $accident_history = 'None';
        } else {
            $accident_history = trim($_POST['accident_details'] ?? 'Yes');
        }

        // Legal Documents
        $registration_status = $_POST['registration_status'] ?? 'Updated';
        $or_cr_ready = $_POST['or_cr_ready'] ?? 'Yes';
        $fully_paid = $_POST['fully_paid'] ?? 'Yes';
        $deed_of_sale_ready = $_POST['deed_of_sale_ready'] ?? 'Yes';

        // Pricing & Payment
        $asking_price = (float)str_replace(['₱', ',', ' '], '', $_POST['asking_price'] ?? $car['asking_price']);
        if ($mileage < 0 || $mileage > 5250000) {
            throw new Exception("Mileage cannot exceed 5,250,000 km.");
        }
        if ($asking_price <= 0 || $asking_price > 5000000) {
            throw new Exception("Asking price must be between ₱1 and ₱5,000,000.");
        }

        $price_negotiability = $_POST['price_negotiability'] ?? 'Fixed Price';
        if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
            $payment_methods = implode(', ', $_POST['payment_methods']);
        } else {
            $payment_methods = trim($_POST['payment_methods_text'] ?? $car['payment_methods']);
        }
        $trade_in = $_POST['trade_in'] ?? 'No';

        // Location & Seller Info
        $city = trim($_POST['city'] ?? $car['city']);
        $district = trim($_POST['district'] ?? $car['district']);
        $barangay = trim($_POST['barangay'] ?? $car['barangay']);
        $location = trim($_POST['location'] ?? $car['location']);
        
        // Auto-rebuild full location string if city & barangay are updated and custom location isn't manually overridden
        if (!empty($_POST['rebuild_location']) && $_POST['rebuild_location'] === '1') {
            $landmark = trim($_POST['landmark'] ?? '');
            $location = $city . ($district ? ', ' . $district : '') . ($barangay ? ', ' . $barangay : '') . ($landmark ? ' — ' . $landmark : '');
        }

        $seller_name = trim($_POST['seller_name'] ?? '');
        $seller_contact = trim($_POST['seller_contact'] ?? '');
        $reason_for_selling = trim($_POST['reason_for_selling'] ?? '');
        $warranty_status = trim($_POST['warranty_status'] ?? 'No Warranty');
        $seller_notes = trim($_POST['seller_notes'] ?? '');

        // Admin Overrides
        $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected']) ? $_POST['status'] : $car['status'];
        $verification_status = in_array($_POST['verification_status'] ?? '', ['mechanic_verified', 'unverified']) ? $_POST['verification_status'] : $car['verification_status'];

        // Photo Uploads (Keep existing photo unless a new one is uploaded or marked for deletion)
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $photo_fields = ['photo_front', 'photo_rear', 'photo_side', 'photo_engine', 'photo_interior', 'photo_dashboard'];
        $updated_photos = [];

        foreach ($photo_fields as $field) {
            $updated_photos[$field] = $car[$field]; // Default to existing photo

            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $new_filename = uniqid() . '_edit_' . $field . '.' . $ext;
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $new_filename)) {
                        $updated_photos[$field] = $new_filename;
                    }
                }
            }
        }

        $updateStmt = $pdo->prepare("
            UPDATE vehicle_listings SET 
                model = ?, year_manufactured = ?, mileage = ?, color = ?, 
                transmission = ?, fuel_type = ?, modifications = ?, 
                maintenance_history = ?, accident_history = ?, first_owner = ?, flooded = ?, 
                registration_status = ?, or_cr_ready = ?, fully_paid = ?, deed_of_sale_ready = ?, 
                asking_price = ?, price_negotiability = ?, payment_methods = ?, trade_in = ?, 
                city = ?, district = ?, barangay = ?, location = ?, 
                seller_name = ?, seller_contact = ?, reason_for_selling = ?, warranty_status = ?, seller_notes = ?, 
                photo_front = ?, photo_rear = ?, photo_side = ?, photo_engine = ?, photo_interior = ?, photo_dashboard = ?,
                status = ?, verification_status = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $model, $year_manufactured, $mileage, $color, 
            $transmission, $fuel_type, $modifications, 
            $maintenance_history, $accident_history, $first_owner, $flooded, 
            $registration_status, $or_cr_ready, $fully_paid, $deed_of_sale_ready, 
            $asking_price, $price_negotiability, $payment_methods, $trade_in, 
            $city, $district, $barangay, $location, 
            $seller_name, $seller_contact, $reason_for_selling, $warranty_status, $seller_notes, 
            $updated_photos['photo_front'], $updated_photos['photo_rear'], $updated_photos['photo_side'], 
            $updated_photos['photo_engine'], $updated_photos['photo_interior'], $updated_photos['photo_dashboard'],
            $status, $verification_status,
            $listing_id
        ]);

        // Refresh $car data so the form immediately displays the newly saved changes
        $stmt->execute([$listing_id]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        $success_message = "Listing #{$listing_id} has been successfully updated.";
    } catch (Exception $e) {
        $error_message = "Error updating listing: " . $e->getMessage();
    }
}

// Prepare state helpers for the form
$is_stock_car = (empty($car['modifications']) || strtolower(trim($car['modifications'])) === 'stock / none' || strtolower(trim($car['modifications'])) === 'none');
$has_accident = (!empty($car['accident_history']) && strtolower(trim($car['accident_history'])) !== 'none' && strtolower(trim($car['accident_history'])) !== 'no');
$current_payments = array_map('trim', explode(',', $car['payment_methods'] ?? 'Cash'));

include 'header.php';
?>

<style>
    .admin-container { max-width: 960px; margin: 0 auto; padding: 32px 24px; }
    
    .page-header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin: 0; }
    .page-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
    
    .nav-actions { display: flex; gap: 12px; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--text); text-decoration: none; font-weight: 700; transition: 0.2s; }
    .btn-back:hover { background: var(--paper); border-color: var(--text-muted); }

    .form-container { background: var(--surface); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
    
    .override-banner { background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.3); border-radius: 12px; padding: 24px; margin-bottom: 32px; }
    .override-title { font-family: var(--font-display); font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
    .form-section:last-of-type { border-bottom: none; margin-bottom: 16px; }
    
    .section-title { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .section-title span { background: var(--blue); color: white; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 13px; font-weight: 800; }
    
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    
    .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
    .form-group label { font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .form-control { padding: 11px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; color: var(--text); background: var(--paper); transition: 0.2s; width: 100%; box-sizing: border-box; font-family: inherit; }
    .form-control:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); background: var(--surface); }
    .form-control[readonly] { opacity: 0.6; cursor: not-allowed; }
    
    .radio-group, .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; padding-top: 6px; }
    .radio-label, .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text); cursor: pointer; font-weight: 500; }
    
    /* Photo Manager Grid */
    .photo-edit-card { background: var(--paper); border: 1px solid var(--border); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 10px; }
    .photo-preview { width: 100%; height: 130px; border-radius: 6px; overflow: hidden; background: var(--surface); border: 1px dashed var(--border); display: flex; align-items: center; justify-content: center; position: relative; }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
    .photo-empty { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
    
    .btn-submit { background: var(--teal); color: white; padding: 16px 24px; border: none; border-radius: 10px; font-size: 15px; font-weight: 800; cursor: pointer; width: 100%; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
    .btn-submit:hover { background: #0D9488; transform: translateY(-2px); }
    
    .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .alert-success { background: rgba(16, 185, 129, 0.12); border: 1px solid var(--teal); color: #34d399; }
    .alert-danger { background: rgba(239, 68, 68, 0.12); border: 1px solid var(--coral); color: #ff8793; }

    @media (max-width: 768px) {
        .grid-2, .grid-3 { grid-template-columns: 1fr; }
    }
</style>

<div class="view admin-container">
    <div class="page-header-nav">
        <div>
            <h1 class="page-title">Edit Listing #<?php echo (int)$car['id']; ?></h1>
            <div class="page-sub">Modify vehicle specifications, documentation, photos, or verification state.</div>
        </div>
        <div class="nav-actions">
            <a href="view_listing.php?id=<?php echo (int)$car['id']; ?>" class="btn-back">View Dossier</a>
            <a href="manage_listings.php" class="btn-back">&larr; Manage Listings</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <span>✓ <?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <span>⚠ <?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            
            <!-- Admin Status & Verification Overrides -->
            <div class="override-banner">
                <div class="override-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5" style="width:20px;height:20px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Admin Command Overrides
                </div>
                <div class="grid-2">
                    <div class="form-group" style="margin: 0;">
                        <label>Listing Visibility Status</label>
                        <select name="status" class="form-control" style="font-weight: 700;">
                            <option value="pending" <?php echo $car['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pending Review</option>
                            <option value="approved" <?php echo $car['status'] === 'approved' ? 'selected' : ''; ?>>✓ Approved (Live on Marketplace)</option>
                            <option value="rejected" <?php echo $car['status'] === 'rejected' ? 'selected' : ''; ?>>✕ Rejected (Hidden)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Mechanic Verification Status</label>
                        <select name="verification_status" class="form-control" style="font-weight: 700; border-color: var(--teal);">
                            <option value="mechanic_verified" <?php echo (($car['verification_status'] ?? '') === 'mechanic_verified') ? 'selected' : ''; ?>>🛡 Mechanic Verified (Trusted)</option>
                            <option value="unverified" <?php echo (($car['verification_status'] ?? 'unverified') === 'unverified') ? 'selected' : ''; ?>>⚠ Unverified (Self-Reported)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 1. Vehicle Basics -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Vehicle Basics</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="Toyota" readonly>
                    </div>
                    <div class="form-group">
                        <label>Car Model <span style="color:var(--coral);">*</span></label>
                        <select name="model" id="model-select" class="form-control" required>
                            <?php 
                            $models = ['Vios', 'Innova', 'Fortuner', 'Hilux', 'Wigo', 'Avanza'];
                            foreach ($models as $m): ?>
                                <option value="<?php echo $m; ?>" <?php echo $car['model'] === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Model <span style="color:var(--coral);">*</span></label>
                        <select name="year_manufactured" id="year-select" class="form-control" required>
                            <?php for ($i = 2026; $i >= 2000; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo (int)$car['year_manufactured'] === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Exterior Color <span style="color:var(--coral);">*</span></label>
                        <select name="color" id="color-select" class="form-control" required>
                            <option value="<?php echo htmlspecialchars($car['color']); ?>" selected><?php echo htmlspecialchars($car['color']); ?> (Current)</option>
                        </select>
                        <input type="text" name="color_custom" id="color-custom" class="form-control" placeholder="Type custom color" style="display:none; margin-top:8px;" maxlength="60">
                    </div>
                    <div class="form-group">
                        <label>Mileage (km) <span style="color:var(--coral);">*</span></label>
                        <input type="number" name="mileage" class="form-control" min="0" max="5250000" value="<?php echo (int)$car['mileage']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Transmission <span style="color:var(--coral);">*</span></label>
                        <select name="transmission" class="form-control" required>
                            <option value="Automatic" <?php echo $car['transmission'] === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                            <option value="Manual" <?php echo $car['transmission'] === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fuel Type <span style="color:var(--coral);">*</span></label>
                        <select name="fuel_type" class="form-control" required>
                            <option value="Gasoline" <?php echo $car['fuel_type'] === 'Gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                            <option value="Diesel" <?php echo $car['fuel_type'] === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Hybrid" <?php echo $car['fuel_type'] === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Condition & History -->
            <div class="form-section">
                <div class="section-title"><span>2</span> Condition &amp; History</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>First Owner?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="first_owner" value="Yes" <?php echo $car['first_owner'] === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="first_owner" value="No" <?php echo $car['first_owner'] !== 'Yes' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Flooded History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="flooded" value="Yes" <?php echo $car['flooded'] === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="flooded" value="No" <?php echo $car['flooded'] !== 'Yes' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Maintenance History / General Condition</label>
                        <select name="maintenance_history" class="form-control">
                            <?php 
                            $maint_options = ['Excellent (Casa Maintained)', 'Excellent', 'Good', 'Fair'];
                            $curr_maint = $car['maintenance_history'] ?? 'Good';
                            if (!in_array($curr_maint, $maint_options)) {
                                echo '<option value="' . htmlspecialchars($curr_maint) . '" selected>' . htmlspecialchars($curr_maint) . '</option>';
                            }
                            foreach ($maint_options as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $curr_maint === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Major Accident History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="major_accident" id="accident_yes" value="Yes" <?php echo $has_accident ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="major_accident" id="accident_no" value="No" <?php echo !$has_accident ? 'checked' : ''; ?>> None</label>
                        </div>
                        <input type="text" name="accident_details" id="accident_details" class="form-control" value="<?php echo $has_accident ? htmlspecialchars($car['accident_history']) : ''; ?>" placeholder="Briefly describe the damage/repair" style="<?php echo $has_accident ? 'display:block;' : 'display:none;'; ?> margin-top:10px;">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Vehicle Modifications</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="is_stock" id="stock_yes" value="Yes" <?php echo $is_stock_car ? 'checked' : ''; ?>> Stock / None</label>
                            <label class="radio-label"><input type="radio" name="is_stock" id="stock_no" value="No" <?php echo !$is_stock_car ? 'checked' : ''; ?>> Modified</label>
                        </div>
                        <input type="text" name="modifications_details" id="modifications_details" class="form-control" value="<?php echo !$is_stock_car ? htmlspecialchars($car['modifications']) : ''; ?>" placeholder="List aftermarket wheels, exhaust, suspension, etc." style="<?php echo !$is_stock_car ? 'display:block;' : 'display:none;'; ?> margin-top:10px;">
                    </div>
                </div>
            </div>

            <!-- 3. Legal Documents -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Legal Documents</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>LTO Registration Status</label>
                        <select name="registration_status" class="form-control">
                            <option value="Updated" <?php echo ($car['registration_status'] ?? '') === 'Updated' ? 'selected' : ''; ?>>Updated</option>
                            <option value="Expired" <?php echo ($car['registration_status'] ?? '') === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="Unregistered" <?php echo ($car['registration_status'] ?? '') === 'Unregistered' ? 'selected' : ''; ?>>Unregistered</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Original OR/CR Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="or_cr_ready" value="Yes" <?php echo ($car['or_cr_ready'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="or_cr_ready" value="No" <?php echo ($car['or_cr_ready'] ?? 'Yes') !== 'Yes' ? 'checked' : ''; ?>> No / Lost</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Fully Paid?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fully_paid" value="Yes" <?php echo ($car['fully_paid'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="fully_paid" value="No" <?php echo ($car['fully_paid'] ?? 'Yes') !== 'Yes' ? 'checked' : ''; ?>> No (Encumbered)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Open Deed of Sale Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="deed_of_sale_ready" value="Yes" <?php echo ($car['deed_of_sale_ready'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="deed_of_sale_ready" value="No" <?php echo ($car['deed_of_sale_ready'] ?? 'Yes') !== 'Yes' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Pricing & Payment -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Pricing &amp; Payment</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Asking Price (PHP) <span style="color:var(--coral);">*</span></label>
                        <input type="text" id="display_price" name="asking_price" class="form-control" value="₱ <?php echo number_format((float)$car['asking_price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price Negotiability</label>
                        <select name="price_negotiability" class="form-control">
                            <option value="Negotiable" <?php echo ($car['price_negotiability'] ?? '') === 'Negotiable' ? 'selected' : ''; ?>>Negotiable</option>
                            <option value="Fixed Price" <?php echo ($car['price_negotiability'] ?? '') !== 'Negotiable' ? 'selected' : ''; ?>>Fixed Price</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Open for Trade-in?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="trade_in" value="Yes" <?php echo ($car['trade_in'] ?? 'No') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="trade_in" value="No" <?php echo ($car['trade_in'] ?? 'No') !== 'Yes' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Accepted Payment Methods</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Cash" <?php echo in_array('Cash', $current_payments) ? 'checked' : ''; ?>> Cash</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Bank Transfer" <?php echo in_array('Bank Transfer', $current_payments) ? 'checked' : ''; ?>> Bank Transfer</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Financing" <?php echo in_array('Financing', $current_payments) ? 'checked' : ''; ?>> Financing</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Location & Seller Info -->
            <div class="form-section">
                <div class="section-title"><span>5</span> Location &amp; Seller Information</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>City / Municipality</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($car['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" name="barangay" class="form-control" value="<?php echo htmlspecialchars($car['barangay'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Full Display Address / Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($car['location'] ?? ''); ?>">
                        <input type="hidden" name="district" value="<?php echo htmlspecialchars($car['district'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Seller Name</label>
                        <input type="text" name="seller_name" class="form-control" value="<?php echo htmlspecialchars($car['seller_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Seller Contact Number</label>
                        <input type="text" name="seller_contact" class="form-control" value="<?php echo htmlspecialchars($car['seller_contact'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <!-- 6. Photo Gallery Management -->
            <div class="form-section">
                <div class="section-title"><span>6</span> Vehicle Photos (Upload to Replace)</div>
                <div class="grid-3">
                    <?php
                    $photo_labels = [
                        'photo_front' => 'Front Angle',
                        'photo_rear' => 'Rear Angle',
                        'photo_side' => 'Side Angle',
                        'photo_engine' => 'Engine Bay',
                        'photo_interior' => 'Interior',
                        'photo_dashboard' => 'Dashboard'
                    ];
                    foreach ($photo_labels as $field => $label):
                        $has_img = !empty($car[$field]);
                    ?>
                        <div class="photo-edit-card">
                            <label style="font-size:11px; font-weight:800; color:var(--text-muted); text-transform:uppercase;"><?php echo $label; ?></label>
                            <div class="photo-preview">
                                <?php if ($has_img): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($car[$field]); ?>" alt="<?php echo $label; ?>">
                                <?php else: ?>
                                    <span class="photo-empty">No Photo</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="<?php echo $field; ?>" class="form-control" style="font-size:12px; padding:8px;" accept="image/jpeg,image/png,image/webp">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 7. Additional Notes -->
            <div class="form-section">
                <div class="section-title"><span>7</span> Additional Details</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Reason for Selling</label>
                        <input type="text" name="reason_for_selling" class="form-control" value="<?php echo htmlspecialchars($car['reason_for_selling'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Warranty Status</label>
                        <input type="text" name="warranty_status" class="form-control" value="<?php echo htmlspecialchars($car['warranty_status'] ?? 'No Warranty'); ?>">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Seller Notes</label>
                        <textarea name="seller_notes" class="form-control" rows="4"><?php echo htmlspecialchars($car['seller_notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Save All Changes</button>
        </form>
    </div>
</div>

<script src="../car_colors.php"></script>
<script>
    // Dynamic Color Palette Loader (Preserves existing color selection on initial load)
    const modelSelect = document.getElementById('model-select');
    const yearSelect = document.getElementById('year-select');
    const colorSelect = document.getElementById('color-select');
    const colorCustom = document.getElementById('color-custom');
    const initialColor = <?php echo json_encode($car['color'] ?? ''); ?>;

    function populateColors(preserveCurrent = false) {
        const model = modelSelect.value;
        const year = yearSelect.value;
        if (!model || !year || typeof window.getCarColors !== 'function') return;

        const colors = window.getCarColors('Toyota', model, year) || [];
        const currentVal = preserveCurrent ? initialColor : colorSelect.value;

        colorSelect.innerHTML = '';
        let matched = false;

        colors.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c.toLowerCase() === String(currentVal).toLowerCase()) {
                opt.selected = true;
                matched = true;
            }
            colorSelect.appendChild(opt);
        });

        // If the existing color in DB is custom or not in the standard list, keep it as an option
        if (currentVal && !matched) {
            const currentOpt = document.createElement('option');
            currentOpt.value = currentVal;
            currentOpt.textContent = currentVal + ' (Current)';
            currentOpt.selected = true;
            colorSelect.insertBefore(currentOpt, colorSelect.firstChild);
        }

        const customOpt = document.createElement('option');
        customOpt.value = 'Other / Custom';
        customOpt.textContent = 'Other / Custom';
        colorSelect.appendChild(customOpt);
    }

    populateColors(true);
    modelSelect.addEventListener('change', () => populateColors(false));
    yearSelect.addEventListener('change', () => populateColors(false));

    colorSelect.addEventListener('change', function() {
        if (this.value === 'Other / Custom') {
            colorCustom.style.display = 'block';
            colorCustom.required = true;
            colorCustom.focus();
        } else {
            colorCustom.style.display = 'none';
            colorCustom.required = false;
        }
    });

    // Toggle Accident & Modification inputs
    document.getElementById('accident_yes').addEventListener('change', () => {
        document.getElementById('accident_details').style.display = 'block';
    });
    document.getElementById('accident_no').addEventListener('change', () => {
        document.getElementById('accident_details').style.display = 'none';
    });

    document.getElementById('stock_no').addEventListener('change', () => {
        document.getElementById('modifications_details').style.display = 'block';
    });
    document.getElementById('stock_yes').addEventListener('change', () => {
        document.getElementById('modifications_details').style.display = 'none';
    });

    // Live Price Formatter (Max 5,000,000)
    document.getElementById('display_price').addEventListener('input', function() {
        let val = this.value.replace(/\D/g, "");
        if (val !== "" && parseInt(val, 10) > 5000000) {
            val = "5000000";
        }
        this.value = val ? "₱ " + parseInt(val, 10).toLocaleString('en-US') : "";
    });
</script>

<?php include '../footer.php'; ?>