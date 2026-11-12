<?php
session_start();
require_once 'db.php';
require_once 'risk_engine.php';
$current_page = 'submit_listing';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $brand = $_POST['brand'] ?? 'Toyota';
        $model = $_POST['model'] ?? '';
        $year_manufactured = $_POST['year_manufactured'] ?? '';
        $mileage = $_POST['mileage'] ?? 0;
        $transmission = $_POST['transmission'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $plate_ending = $_POST['plate_ending'] ?? '';
        $is_stock = $_POST['is_stock'] ?? 'Yes';
        
        $modifications = 'Stock / None';
        if ($is_stock === 'No') {
            $mod_list = $_POST['modifications'] ?? [];
            if (!empty($_POST['other_modifications'])) {
                $mod_list[] = trim($_POST['other_modifications']);
            }
            if (!empty($mod_list)) {
                $modifications = implode(', ', $mod_list);
            }
        }

        // Condition & History
        $first_owner = $_POST['first_owner'] ?? 'No';
        $casa_maintained = $_POST['casa_maintained'] ?? 'No';
        $accident_history = ($_POST['major_accident'] ?? 'No') === 'Yes' ? ($_POST['accident_details'] ?? 'Yes') : 'None';
        $flooded = $_POST['flooded'] ?? 'No';
        $general_condition = $_POST['general_condition'] ?? 'Good';
        $lto_status = $_POST['lto_status'] ?? 'Updated';
        $or_cr = $_POST['or_cr'] ?? 'Yes';
        $fully_paid = $_POST['fully_paid'] ?? 'Yes';
        $deed_of_sale = $_POST['deed_of_sale'] ?? 'Yes';

        // Pricing & Payment
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price'] ?? '0');
        $price_negotiability = ($_POST['negotiable'] ?? 'No') === 'Yes' ? 'Negotiable' : 'Fixed Price';
        $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : 'Cash';
        $trade_in = $_POST['trade_in'] ?? 'No';

        // Location (3-Level)
        $city     = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $landmark = trim($_POST['location'] ?? '');

        if ($city === '') { throw new Exception('Please select a City / Municipality.'); }
        if ($barangay === '') { throw new Exception('Please select a Barangay.'); }
        
        // Combine into a single string for the 'location' column
        $full_location = $city . ', ' . ($district ? $district . ', ' : '') . $barangay . ($landmark ? ' — ' . $landmark : '');

        // Seller Info
        $seller_name = $_POST['seller_name'] ?? '';
        $primary_contact = $_POST['seller_contact'] ?? '';
        $other_contact = $_POST['other_contact'] ?? '';
        $seller_contact = $primary_contact;
        if (!empty($other_contact)) {
            $seller_contact .= ' (' . trim($other_contact) . ')';
        }
        
        // Final Touches
        $reason_for_selling = $_POST['rfs'] ?? '';
        $warranty_status = $_POST['warranty_status'] ?? 'No Warranty';
        $seller_notes = $_POST['seller_notes'] ?? '';

        // Handle Photo Uploads
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $photo_fields = ['photo_front', 'photo_rear', 'photo_side', 'photo_engine', 'photo_interior', 'photo_dashboard'];
        $uploaded_files = [];

        foreach ($photo_fields as $field) {
            $uploaded_files[$field] = null;
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . $field . '.' . $file_extension;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $new_filename)) {
                    $uploaded_files[$field] = $new_filename;
                }
            }
        }

        $maintenance_history = $casa_maintained === 'Yes' ? 'Excellent (Casa)' : $general_condition;

        // RUN RISK ENGINE
        $risk = compute_risk_score($pdo, [
            'brand' => $brand, 'model' => $model,
            'year_manufactured' => (int)$year_manufactured,
            'mileage' => (int)$mileage,
            'asking_price' => (float)$asking_price,
            'location' => $full_location,
            'city' => $city,
            'district' => $district,
            'barangay' => $barangay,
            'accident_history' => $accident_history,
            'is_flood_flagged' => ($flooded === 'Yes'),
            'verification_status' => 'unverified', // New user sales default to unverified
        ]);

        if ($risk['risk_level'] === 'High') {
            throw new Exception('Listing rejected by risk engine: ' . implode('; ', $risk['flags']));
        }

        // INSERT INTO DATABASE (One single query)
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_listings (
                vehicle_type, brand, model, year_manufactured, mileage, color, 
                transmission, fuel_type, modifications, registration_status, or_cr_ready, fully_paid, deed_of_sale_ready,
                maintenance_history, accident_history, first_owner, flooded, 
                asking_price, price_negotiability, payment_methods, trade_in,
                location, city, district, barangay, reason_for_selling, warranty_status, seller_notes, 
                photo_front, photo_rear, photo_side, photo_engine, photo_interior, photo_dashboard,
                status, listing_type, seller_name, seller_contact,
                risk_score, risk_level, risk_flags, risk_recommendation, risk_evaluated_at
            ) VALUES (
                'Car', ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, 
                'pending', 'user_sale', ?, ?,
                ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $brand, $model, $year_manufactured, $mileage, $color,
            $transmission, $fuel_type, $modifications, $lto_status, $or_cr, $fully_paid, $deed_of_sale,
            $maintenance_history, $accident_history, $first_owner, $flooded,
            $asking_price, $price_negotiability, $payment_methods, $trade_in,
            $full_location, $city, $district, $barangay, $reason_for_selling, $warranty_status, $seller_notes,
            $uploaded_files['photo_front'], $uploaded_files['photo_rear'], $uploaded_files['photo_side'], 
            $uploaded_files['photo_engine'], $uploaded_files['photo_interior'], $uploaded_files['photo_dashboard'],
            $seller_name, $seller_contact,
            $risk['risk_score'], $risk['risk_level'], implode(' | ', $risk['flags']), $risk['recommendation'], date('Y-m-d H:i:s')
        ]);

        $success_message = "Your listing has been submitted successfully and is pending review.";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

include 'header.php';
?>
<style>
    .form-container { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; max-width: 900px; margin: 0 auto; }
    .form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #f3f4f6; }
    .form-section:last-child { border-bottom: none; }
    .section-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title span { background: #5865f2; color: white; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-control { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827; background: #f9fafb; }
    .form-control:focus { outline: none; border-color: #5865f2; box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1); }
    .form-control:disabled { background: #e5e7eb; cursor: not-allowed; }
    .radio-group, .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; }
    .radio-label, .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #4b5563; cursor: pointer; }
    .btn-submit { background: #5865f2; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.2s; }
    .btn-submit:hover { background: #4752c4; }
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    .photo-capture-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .photo-upload-box { border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px 10px; text-align: center; background: #f9fafb; position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .camera-btn { display: inline-flex; align-items: center; gap: 8px; background: #5865f2; color: white; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; margin-top: 8px; transition: background 0.2s; }
    .camera-btn:hover { background: #4752c4; }
    .camera-btn svg { width: 16px; height: 16px; }
    .photo-preview { margin-top: 12px; width: 100%; border-radius: 6px; height: 120px; object-fit: cover; border: 1px solid #e5e7eb; }
</style>

<div class="view">
    <div class="form-container">
        <h2 style="font-size:24px; font-weight:800; margin-bottom:8px;">Second-Hand Car Listing</h2>
        <p style="color:#6b7280; margin-bottom:32px;">Complete the sections below to generate a detailed verified listing for your vehicle.</p>

        <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-section">
                <div class="section-title"><span>1</span> Select Your Vehicle</div>
                <div class="grid-2">
                    <div class="form-group"><label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="Toyota" readonly style="background-color:#e5e7eb;color:#6b7280;cursor:not-allowed;" required>
                    </div>
                    <div class="form-group"><label>Car Model</label>
                        <select name="model" id="model-select" class="form-control" required>
                            <option value="">Select model</option>
                            <option value="Vios">Vios</option>
                            <option value="Innova">Innova</option>
                            <option value="Fortuner">Fortuner</option>
                            <option value="Hilux">Hilux</option>
                            <option value="Wigo">Wigo</option>
                            <option value="Avanza">Avanza</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Year Model</label>
                        <select name="year_manufactured" id="year-select" class="form-control" required>
                            <option value="">Select year</option>
                            <?php for ($i = 2026; $i >= 2013; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>2</span> Vehicle Specifications</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mileage (Max 5,250,000 km)</label>
                        <input type="number" name="mileage" class="form-control" min="0" max="5250000" placeholder="e.g. 45000" required>
                    </div>
                    <div class="form-group">
                        <label>Exterior Color</label>
                        <select name="color" id="color-select" class="form-control" required disabled>
                            <option value="">Select model &amp; year first</option>
                        </select>
                        <input type="text" name="color_custom" id="color-custom" class="form-control"
                               placeholder="Type custom color (e.g., Red Blue)"
                               style="display:none; margin-top:8px;" maxlength="60">
                    </div>
                    <div class="form-group"><label>Transmission</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="transmission" value="Automatic" required> Automatic</label>
                            <label class="radio-label"><input type="radio" name="transmission" value="Manual"> Manual</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Fuel Type</label>
                        <div class="radio-group" id="fuel-radios">
                            <label class="radio-label" id="fuel-gas"><input type="radio" name="fuel_type" id="radio-gas" value="Gasoline" required> Gas</label>
                            <label class="radio-label" id="fuel-diesel"><input type="radio" name="fuel_type" id="radio-diesel" value="Diesel"> Diesel</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>3</span> Modifications &amp; Upgrades</div>
                <div class="form-group">
                    <label>Is the vehicle completely stock (no modifications)?</label>
                    <div class="radio-group" style="margin-bottom:16px;">
                        <label class="radio-label"><input type="radio" name="is_stock" value="Yes" checked onclick="document.getElementById('mods-list').style.display='none'"> Yes</label>
                        <label class="radio-label"><input type="radio" name="is_stock" value="No" onclick="document.getElementById('mods-list').style.display='flex'"> No</label>
                    </div>
                    <div id="mods-list" class="checkbox-group" style="display:none;flex-direction:column;">
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Upgraded Touchscreen"> Upgraded Touchscreen / Apple CarPlay / Android Auto</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Leather Seats"> Leather Seats</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Dashcam"> Dashcam / Upgraded Reverse Camera</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Aftermarket Wheels"> Aftermarket Wheels / Mags</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Modified Exhaust"> Modified Exhaust System</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Modified Suspension"> Modified Suspension</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Engine Tune"> Engine Tune / ECU Remap</label>
                        <div style="margin-top: 12px; width: 100%; max-width: 400px;">
                            <label style="font-size: 13px; font-weight: 600; color: #374151;">Specify Other Modifications:</label>
                            <input type="text" name="other_modifications" class="form-control" placeholder="e.g., Roof rack, Ambient lighting" style="margin-top: 6px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>4</span> Condition &amp; History</div>
                <div class="grid-2">
                    <div class="form-group"><label>First Owner Vehicle?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="first_owner" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="first_owner" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Casa-Maintained with Records?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Involved in Major Accident(s)?</label>
                        <div class="radio-group" style="margin-bottom:8px;">
                            <label class="radio-label"><input type="radio" name="major_accident" value="Yes" onclick="document.getElementById('accident-details').style.display='block'"> Yes</label>
                            <label class="radio-label"><input type="radio" name="major_accident" value="No" checked onclick="document.getElementById('accident-details').style.display='none'"> No</label>
                        </div>
                        <input type="text" name="accident_details" id="accident-details" class="form-control" placeholder="If Yes, provide details" style="display:none;">
                    </div>
                    <div class="form-group"><label>Flooded Vehicle History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="flooded" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="flooded" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column:span 2;"><label>Overall General Condition</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="general_condition" value="Excellent" required> Excellent</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Good"> Good</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Fair"> Fair</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Needs Minor Repairs"> Needs Minor Repairs</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>5</span> Legal Documents &amp; Registration</div>
                <div class="grid-2">
                    <div class="form-group"><label>LTO Registration Status</label>
                        <select name="lto_status" class="form-control">
                            <option value="Currently Registered">Currently Registered</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Original OR / CR On Hand?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="or_cr" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="or_cr" value="No"> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Fully Paid?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fully_paid" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="fully_paid" value="No"> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Deed of Sale Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="No"> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>6</span> Pricing &amp; Payment</div>
                <div class="grid-2">
                    <div class="form-group"><label>Asking Price (PHP)</label>
                        <input type="text" id="display_price" class="form-control" placeholder="₱ 0" required>
                        <input type="hidden" name="asking_price" id="actual_price">
                    </div>
                    <div class="form-group"><label>Price Negotiable?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="negotiable" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="negotiable" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Accepted Payment Methods</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Cash" checked> Cash</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Bank Financing"> Bank Financing</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Assume Balance"> Assume Balance</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Open to Trade-Ins?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="trade_in" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="trade_in" value="No" checked> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>7</span> Photos</div>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">Tap the buttons below to open your camera and capture each required angle.</p>
                
                <div class="photo-capture-grid">
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Front View</label>
                        <label for="photo_front" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_front" name="photo_front" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_front')" required>
                        <img id="preview_front" class="photo-preview" style="display: none;">
                    </div>
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Rear View</label>
                        <label for="photo_rear" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_rear" name="photo_rear" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_rear')" required>
                        <img id="preview_rear" class="photo-preview" style="display: none;">
                    </div>
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Side Profile</label>
                        <label for="photo_side" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_side" name="photo_side" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_side')" required>
                        <img id="preview_side" class="photo-preview" style="display: none;">
                    </div>
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Engine Bay</label>
                        <label for="photo_engine" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_engine" name="photo_engine" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_engine')" required>
                        <img id="preview_engine" class="photo-preview" style="display: none;">
                    </div>
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Interior & Seats</label>
                        <label for="photo_interior" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_interior" name="photo_interior" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_interior')" required>
                        <img id="preview_interior" class="photo-preview" style="display: none;">
                    </div>
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Dashboard & Odometer</label>
                        <label for="photo_dashboard" class="camera-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture</label>
                        <input type="file" id="photo_dashboard" name="photo_dashboard" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_dashboard')" required>
                        <img id="preview_dashboard" class="photo-preview" style="display: none;">
                    </div>
                </div>
            </div>

            <!-- 8. Location & Seller Info -->
            <div class="form-section">
                <div class="section-title"><span>8</span> Location & Seller Information</div>
                <div class="grid-2">
                    <div class="form-group" style="position:relative;">
                        <label>City / Municipality <span style="color:#ef4444;">*</span></label>
                        <select name="city" id="city-select" class="form-control" required>
                            <option value="">Select City / Municipality</option>
                        </select>
                    </div>
                    <div class="form-group" id="district-wrapper">
                        <label>District <span style="color:#ef4444;">*</span></label>
                        <select name="district" id="district-select" class="form-control" disabled>
                            <option value="">Select city first</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Barangay <span style="color:#ef4444;">*</span></label>
                        <select name="barangay" id="barangay-select" class="form-control" required disabled>
                            <option value="">Select city (and district) first</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Street / Landmark <span style="color:#9ca3af;">(optional)</span></label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., SM North EDSA">
                    </div>

                    <div class="form-group" style="grid-column:span 2; margin-top: 16px;">
                        <label style="font-size: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px; color:#111827;">Seller Contact Details</label>
                    </div>
                    <div class="form-group">
                        <label>Seller Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="seller_name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <label>Primary Contact Number <span style="color:#ef4444;">*</span></label>
                        <input type="tel" name="seller_contact" id="seller-contact" class="form-control" placeholder="e.g. 09171234567" inputmode="numeric" maxlength="11" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" required>
                    </div>
                    <div class="form-group">
                        <label>Other Contact Method (Optional)</label>
                        <input type="text" name="other_contact" class="form-control" placeholder="e.g. Viber, Email, FB Messenger">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><span>9</span> The "Final Touches"</div>
                <div class="form-group">
                    <label>Reason for Selling (RFS)</label>
                    <input type="text" name="rfs" class="form-control" placeholder="Enter reason for selling">
                </div>
                <div class="form-group"><label>Warranty Status</label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="warranty_status" value="Active"> Active Warranty</label>
                        <label class="radio-label"><input type="radio" name="warranty_status" value="No Warranty" checked> No Warranty</label>
                    </div>
                </div>
                <div class="form-group"><label>Seller's Notes</label>
                    <textarea name="seller_notes" class="form-control" rows="4" placeholder="Additional info..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Generate Verified Listing</button>
        </form>
    </div>
</div>

<script src="car_colors.php"></script>
<script src="ph_locations.php"></script>
<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // ---------- COLOR CATALOG ----------
    const modelSelect = document.getElementById("model-select");
    const yearSelect  = document.getElementById("year-select");
    const colorSelect = document.getElementById("color-select");
    const colorCustom = document.getElementById("color-custom");
    const fuelGasLabel    = document.getElementById("fuel-gas");
    const fuelDieselLabel = document.getElementById("fuel-diesel");
    const radioGas    = document.getElementById("radio-gas");
    const radioDiesel = document.getElementById("radio-diesel");

    if (modelSelect.options[0]) modelSelect.options[0].disabled = true;
    if (yearSelect.options[0])  yearSelect.options[0].disabled  = true;

    const fuelData = {
        "Vios":     ["Gasoline"], "Innova":   ["Diesel","Gasoline"], "Fortuner": ["Diesel","Gasoline"],
        "Hilux":    ["Diesel","Gasoline"], "Wigo":     ["Gasoline"], "Avanza":   ["Gasoline"]
    };

    function populateColors() {
        const model = modelSelect.value;
        const year  = yearSelect.value;
        colorCustom.style.display = 'none';
        colorCustom.required = false;
        colorCustom.value = '';
        colorSelect.innerHTML = '<option value="">Select a color</option>';
        colorSelect.disabled = true;
        if (!model || !year) return;
        const colors = (window.getCarColors ? window.getCarColors('Toyota', model, year) : []) || [];
        if (colors.length === 0) return;
        colorSelect.disabled = false;
        colors.forEach(function(c) {
            const o = document.createElement("option");
            o.value = c; o.textContent = c;
            colorSelect.appendChild(o);
        });
        const other = document.createElement("option");
        other.value = "Other / Custom"; other.textContent = "Other / Custom";
        colorSelect.appendChild(other);
        colorSelect.options[0].disabled = true;
    }

    modelSelect.addEventListener("change", function() {
        this.options[0].disabled = true;
        const selectedModel = this.value;
        if (fuelData[selectedModel]) {
            const fuels = fuelData[selectedModel];
            fuelGasLabel.style.display    = fuels.includes("Gasoline") ? "inline-flex" : "none";
            fuelDieselLabel.style.display = fuels.includes("Diesel")   ? "inline-flex" : "none";
            if (!fuels.includes("Gasoline")) radioGas.checked = false;
            if (!fuels.includes("Diesel"))   radioDiesel.checked = false;
            if (fuels.length === 1) {
                if (fuels[0] === "Gasoline") radioGas.checked = true;
                if (fuels[0] === "Diesel")   radioDiesel.checked = true;
            }
        } else {
            fuelGasLabel.style.display = "inline-flex";
            fuelDieselLabel.style.display = "inline-flex";
        }
        populateColors();
    });

    yearSelect.addEventListener("change", function() {
        this.options[0].disabled = true;
        populateColors();
    });

    colorSelect.addEventListener('change', function() {
        if (this.value === 'Other / Custom') {
            colorCustom.style.display = 'block';
            colorCustom.required = true;
            colorCustom.focus();
        } else {
            colorCustom.style.display = 'none';
            colorCustom.required = false;
            colorCustom.value = '';
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // 3-LEVEL LOCATION DROPDOWNS
    // ═══════════════════════════════════════════════════════════════
    const citySelect     = document.getElementById('city-select');
    const districtSelect = document.getElementById('district-select');
    const districtWrap   = document.getElementById('district-wrapper');
    const barangaySelect = document.getElementById('barangay-select');

    const regionGroups = {
        "Metro Manila (NCR)": ['Caloocan','Las Piñas','Makati','Malabon','Mandaluyong','Manila','Marikina','Muntinlupa','Navotas','Parañaque','Pasay','Pateros','Quezon City','San Juan','Taguig','Valenzuela'],
        "Cavite": ['Bacoor','Imus','Noveleta','Kawit','Dasmariñas','Tagaytay','Trece Martires','Cavite City','General Trias'],
        "Laguna": ['Calamba','San Pedro','Santa Rosa','Biñan','Los Baños','Cabuyao','Santa Cruz','Bay','Victoria','Pakil','Pila','Lumban','Cavinti'],
        "Rizal": ['Antipolo','Cainta','Taytay','Angono','Binangonan','Cardona','Morong','Baras','Tanay','Pililla','Jala-Jala','San Mateo'],
        "Bulacan": ['Malolos','Meycauayan','Marilao','Bocaue','Hagonoy','Calumpit','Paombong','San Jose del Monte','San Miguel','Pulilan','Balagtas','Guiguinto','Pandi'],
        "Pampanga": ['San Fernando (Pampanga)','Angeles','Mabalacat','Lubao','Sasmuan','Macabebe','Porac','Masantol','Candaba','Guagua','Arayat','Apalit','San Simon','Floridablanca'],
        "Visayas": ['Cebu City','Mandaue','Lapu-Lapu','Iloilo City','Bacolod','Dumaguete','Tagbilaran'],
        "Mindanao": ['Davao City','Cagayan de Oro','Zamboanga City','General Santos']
    };

    Object.keys(regionGroups).forEach(function(region) {
        const group = document.createElement('optgroup');
        group.label = region;
        regionGroups[region].forEach(function(city) {
            const opt = document.createElement('option');
            opt.value = city; opt.textContent = city;
            group.appendChild(opt);
        });
        citySelect.appendChild(group);
    });

    citySelect.addEventListener('change', function() {
        const city = this.value;
        districtSelect.innerHTML = '';
        barangaySelect.innerHTML = '<option value="">Select city (and district) first</option>';
        barangaySelect.disabled = true;

        if (!city) {
            districtSelect.innerHTML = '<option value="">Select city first</option>';
            districtSelect.disabled = true;
            districtWrap.style.display = 'flex';
            return;
        }

        const hasDistricts = window.isCityWithDistricts(city);

        if (hasDistricts) {
            districtWrap.style.display = 'flex';
            districtSelect.disabled = false;
            districtSelect.innerHTML = '<option value="">Select district</option>';
            window.getDistricts(city).forEach(function(d) {
                const opt = document.createElement('option');
                opt.value = d; opt.textContent = d;
                districtSelect.appendChild(opt);
            });
            districtSelect.options[0].disabled = true;
        } else {
            districtWrap.style.display = 'none';
            districtSelect.disabled = true;
            districtSelect.innerHTML = '<option value="">N/A</option>';

            barangaySelect.disabled = false;
            barangaySelect.innerHTML = '<option value="">Select barangay</option>';
            window.getBarangays(city, '').forEach(function(b) {
                const opt = document.createElement('option');
                opt.value = b; opt.textContent = b;
                barangaySelect.appendChild(opt);
            });
            barangaySelect.options[0].disabled = true;
        }
    });

    districtSelect.addEventListener('change', function() {
        const city = citySelect.value;
        const district = this.value;
        barangaySelect.innerHTML = '';
        barangaySelect.disabled = true;

        if (!city || !district) {
            barangaySelect.innerHTML = '<option value="">Select district first</option>';
            return;
        }

        barangaySelect.disabled = false;
        barangaySelect.innerHTML = '<option value="">Select barangay</option>';
        window.getBarangays(city, district).forEach(function(b) {
            const opt = document.createElement('option');
            opt.value = b; opt.textContent = b;
            barangaySelect.appendChild(opt);
        });
        barangaySelect.options[0].disabled = true;
    });

    // ---------- MONEY FORMATTER ----------
    const displayPrice = document.getElementById('display_price');
    const actualPrice  = document.getElementById('actual_price');
    displayPrice.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, "");
        if (value) {
            let intValue = parseInt(value, 10);
            actualPrice.value = intValue;
            this.value = "₱ " + intValue.toLocaleString('en-US');
        } else {
            actualPrice.value = "";
            this.value = "";
        }
    });
});
</script>

<?php include 'footer.php'; ?>