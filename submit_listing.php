<?php
session_start();
require_once 'db.php';
$current_page = 'submit_listing';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect Section 1: Vehicle Selection
        $brand = $_POST['brand'] ?? 'Toyota';
        $model = $_POST['model'] ?? '';
        $year_manufactured = $_POST['year_manufactured'] ?? '';

        // Collect Section 2: Vehicle Specifications
        $mileage = $_POST['mileage'] ?? 0;
        $transmission = $_POST['transmission'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $color = $_POST['color'] ?? '';
        $plate_ending = $_POST['plate_ending'] ?? '';

        // Collect Section 3: Modifications & Upgrades
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

        // Collect Section 4: Condition & History
        $first_owner = $_POST['first_owner'] ?? 'No';
        $casa_maintained = $_POST['casa_maintained'] ?? 'No';
        $accident_history = ($_POST['major_accident'] ?? 'No') === 'Yes' ? ($_POST['accident_details'] ?? 'Yes') : 'None';
        $flooded = $_POST['flooded'] ?? 'No';
        $general_condition = $_POST['general_condition'] ?? 'Good';

        // Collect Section 5: Legal Documents
        $lto_status = $_POST['lto_status'] ?? 'Updated';
        $or_cr = $_POST['or_cr'] ?? 'Yes';
        $fully_paid = $_POST['fully_paid'] ?? 'Yes';
        $deed_of_sale = $_POST['deed_of_sale'] ?? 'Yes';

        // Collect Section 6: Pricing & Payment
        $asking_price = $_POST['asking_price'] ?? 0;
        $price_negotiability = ($_POST['negotiable'] ?? 'No') === 'Yes' ? 'Negotiable' : 'Fixed Price';
        $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : 'Cash';
        $trade_in = $_POST['trade_in'] ?? 'No';

        // Collect Section 8: Seller Information
        $seller_name = $_POST['seller_name'] ?? '';
        $primary_contact = $_POST['seller_contact'] ?? '';
        $other_contact = $_POST['other_contact'] ?? '';
        
        // Combine contacts if an alternative modality is provided
        $seller_contact = $primary_contact;
        if (!empty($other_contact)) {
            $seller_contact .= ' (' . trim($other_contact) . ')';
        }
        $location = $_POST['location'] ?? '';
        
        // Collect Section 9: Final Touches
        $reason_for_selling = $_POST['rfs'] ?? '';
        $warranty_status = $_POST['warranty_status'] ?? 'No Warranty';
        $seller_notes = $_POST['seller_notes'] ?? '';

        // --- Handle Section 7: Photo Uploads ---
        $upload_dir = 'uploads/';
        // Create uploads folder if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $photo_fields = ['photo_front', 'photo_rear', 'photo_side', 'photo_engine', 'photo_interior', 'photo_dashboard'];
        $uploaded_files = [];

        foreach ($photo_fields as $field) {
            $uploaded_files[$field] = null; // Default to null if no photo
            
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                // Generate a unique file name to prevent overwriting (e.g., 65f3a4b2_front.jpg)
                $file_extension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . $field . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                    $uploaded_files[$field] = $new_filename; // Save only the filename to the database
                }
            }
        }

        // Insert into vehicle_listings table (Now including photo columns)
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_listings (
                vehicle_type, brand, model, year_manufactured, mileage, color, 
                transmission, fuel_type, modifications, registration_status, or_cr_ready, fully_paid, deed_of_sale_ready,
                maintenance_history, accident_history, first_owner, flooded, 
                asking_price, price_negotiability, payment_methods, trade_in,
                location, reason_for_selling, warranty_status, seller_notes, 
                photo_front, photo_rear, photo_side, photo_engine, photo_interior, photo_dashboard,
                status, listing_type, seller_name, seller_contact
            ) VALUES (
                'Car', ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, 
                'pending', 'user_sale', ?, ?
            )
        ");

        $maintenance_history = $casa_maintained === 'Yes' ? 'Excellent (Casa)' : $general_condition;

        $stmt->execute([
            $brand, $model, $year_manufactured, $mileage, $color,
            $transmission, $fuel_type, $modifications, $lto_status, $or_cr, $fully_paid, $deed_of_sale,
            $maintenance_history, $accident_history, $first_owner, $flooded,
            $asking_price, $price_negotiability, $payment_methods, $trade_in,
            $location, $reason_for_selling, $warranty_status, $seller_notes,
            // Pass the generated photo filenames to the database
            $uploaded_files['photo_front'], $uploaded_files['photo_rear'], $uploaded_files['photo_side'], 
            $uploaded_files['photo_engine'], $uploaded_files['photo_interior'], $uploaded_files['photo_dashboard'],
            $seller_name, $seller_contact
        ]);

        $success_message = "Your listing has been submitted successfully and is pending review.";
    } catch (PDOException $e) {
        $error_message = "Error submitting listing: " . $e->getMessage();
    }
}

include 'header.php';
?>

<style>
    .form-container {
        background: #ffffff;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        max-width: 900px;
        margin: 0 auto;
    }
    .form-section {
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid #f3f4f6;
    }
    .form-section:last-child {
        border-bottom: none;
    }
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title span {
        background: #5865f2;
        color: white;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 12px;
    }
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 16px;
    }
    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-control {
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #111827;
        background: #f9fafb;
    }
    .form-control:focus {
        outline: none;
        border-color: #5865f2;
        box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1);
    }
    .radio-group, .checkbox-group {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .radio-label, .checkbox-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #4b5563;
        cursor: pointer;
    }
    .btn-submit {
        background: #5865f2;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background 0.2s;
    }
    .btn-submit:hover {
        background: #4752c4;
    }
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-weight: 500;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }

    /* Custom Camera Upload Styling */
    .photo-capture-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .photo-upload-box {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 20px 10px;
        text-align: center;
        background: #f9fafb;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .camera-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #5865f2;
        color: white;
        padding: 10px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
        transition: background 0.2s;
    }
    .camera-btn:hover { background: #4752c4; }
    .camera-btn svg { width: 16px; height: 16px; }
    .photo-preview {
        margin-top: 12px;
        width: 100%;
        border-radius: 6px;
        height: 120px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }
</style>

<div class="view">
    <div class="form-container">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Second-Hand Car Listing</h2>
        <p style="color: #6b7280; margin-bottom: 32px;">Complete the sections below to generate a detailed verified listing for your vehicle.</p>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Added enctype="multipart/form-data" required for file uploads -->
        <form method="POST" action="" enctype="multipart/form-data">
            
            <!-- 1. Select Your Vehicle -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Select Your Vehicle</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="Toyota" readonly style="background-color: #e5e7eb; color: #6b7280; cursor: not-allowed;" required>
                    </div>
                    <div class="form-group">
                        <label>Car Model</label>
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
                    <div class="form-group">
                        <label>Year Model</label>
                        <select name="year_manufactured" class="form-control" required>
                            <option value="">Select year</option>
                            <?php for($i = 2026; $i >= 2015; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Vehicle Specifications -->
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
                            <option value="">Select a model first</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Transmission</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="transmission" value="Automatic" required> Automatic</label>
                            <label class="radio-label"><input type="radio" name="transmission" value="Manual"> Manual</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fuel Type</label>
                        <div class="radio-group" id="fuel-radios">
                            <label class="radio-label" id="fuel-gas"><input type="radio" name="fuel_type" id="radio-gas" value="Gasoline" required> Gas</label>
                            <label class="radio-label" id="fuel-diesel"><input type="radio" name="fuel_type" id="radio-diesel" value="Diesel"> Diesel</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Plate Number Ending (Coding check)</label>
                        <select name="plate_ending" class="form-control">
                            <option value="">Select last digit</option>
                            <?php for($i = 0; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Modifications & Upgrades -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Modifications & Upgrades</div>
                <div class="form-group">
                    <label>Is the vehicle completely stock (no modifications)?</label>
                    <div class="radio-group" style="margin-bottom: 16px;">
                        <label class="radio-label"><input type="radio" name="is_stock" value="Yes" checked onclick="document.getElementById('mods-list').style.display='none'"> Yes</label>
                        <label class="radio-label"><input type="radio" name="is_stock" value="No" onclick="document.getElementById('mods-list').style.display='flex'"> No</label>
                    </div>
                    <div id="mods-list" class="checkbox-group" style="display: none; flex-direction: column;">
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Upgraded Touchscreen"> Upgraded Touchscreen / Apple CarPlay / Android Auto</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Leather Seats"> Leather Seats</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Dashcam"> Dashcam / Upgraded Reverse Camera</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Aftermarket Wheels"> Aftermarket Wheels / Mags</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Modified Exhaust"> Modified Exhaust System</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Modified Suspension"> Modified Suspension (Lifted or Lowered)</label>
                        <label class="checkbox-label"><input type="checkbox" name="modifications[]" value="Engine Tune"> Engine Tune / ECU Remap</label>
                        <div style="margin-top: 12px; width: 100%; max-width: 400px;">
                            <label style="font-size: 13px; font-weight: 600; color: #374151;">Specify Other Modifications:</label>
                            <input type="text" name="other_modifications" class="form-control" placeholder="e.g., Roof rack, Ambient lighting" style="margin-top: 6px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Condition & History -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Condition & History</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>First Owner Vehicle?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="first_owner" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="first_owner" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Casa-Maintained with Complete Service Records?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Involved in Major Accident(s)?</label>
                        <div class="radio-group" style="margin-bottom: 8px;">
                            <label class="radio-label"><input type="radio" name="major_accident" value="Yes" onclick="document.getElementById('accident-details').style.display='block'"> Yes</label>
                            <label class="radio-label"><input type="radio" name="major_accident" value="No" checked onclick="document.getElementById('accident-details').style.display='none'"> No</label>
                        </div>
                        <input type="text" name="accident_details" id="accident-details" class="form-control" placeholder="If Yes, provide details" style="display: none;">
                    </div>
                    <div class="form-group">
                        <label>Flooded Vehicle History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="flooded" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="flooded" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Overall General Condition</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="general_condition" value="Excellent" required> Excellent</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Good"> Good</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Fair"> Fair</label>
                            <label class="radio-label"><input type="radio" name="general_condition" value="Needs Minor Repairs"> Needs Minor Repairs</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Legal Documents & Registration -->
            <div class="form-section">
                <div class="section-title"><span>5</span> Legal Documents & Registration</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>LTO Registration Status</label>
                        <select name="lto_status" class="form-control">
                            <option value="Currently Registered">Currently Registered</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Original OR / CR On Hand?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="or_cr" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="or_cr" value="No"> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fully Paid (No Bank Encumbrance)?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fully_paid" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="fully_paid" value="No"> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deed of Sale Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="No"> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Pricing & Payment -->
            <div class="form-section">
                <div class="section-title"><span>6</span> Pricing & Payment</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Asking Price (PHP)</label>
                        <input type="text" id="display_price" class="form-control" placeholder="₱ 0" required>
                        <input type="hidden" name="asking_price" id="actual_price">
                    </div>
                    <div class="form-group">
                        <label>Price Negotiable?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="negotiable" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="negotiable" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Accepted Payment Methods</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Cash" checked> Cash</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Bank Financing"> Bank Financing</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Assume Balance"> Assume Balance</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Open to Trade-Ins?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="trade_in" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="trade_in" value="No" checked> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Photos (Camera Integration) -->
            <div class="form-section">
                <div class="section-title"><span>7</span> Photos</div>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">Tap the buttons below to open your camera and capture each required angle.</p>
                
                <div class="photo-capture-grid">
                    <!-- Front View -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Front View</label>
                        <label for="photo_front" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_front" name="photo_front" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_front')" required>
                        <img id="preview_front" class="photo-preview" style="display: none;">
                    </div>

                    <!-- Rear View -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Rear View</label>
                        <label for="photo_rear" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_rear" name="photo_rear" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_rear')" required>
                        <img id="preview_rear" class="photo-preview" style="display: none;">
                    </div>

                    <!-- Side Profile -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Side Profile</label>
                        <label for="photo_side" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_side" name="photo_side" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_side')" required>
                        <img id="preview_side" class="photo-preview" style="display: none;">
                    </div>

                    <!-- Engine Bay -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Engine Bay</label>
                        <label for="photo_engine" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_engine" name="photo_engine" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_engine')" required>
                        <img id="preview_engine" class="photo-preview" style="display: none;">
                    </div>

                    <!-- Interior -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Interior & Seats</label>
                        <label for="photo_interior" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_interior" name="photo_interior" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_interior')" required>
                        <img id="preview_interior" class="photo-preview" style="display: none;">
                    </div>

                    <!-- Dashboard / Odometer -->
                    <div class="photo-upload-box">
                        <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">Dashboard & Odometer</label>
                        <label for="photo_dashboard" class="camera-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Capture
                        </label>
                        <input type="file" id="photo_dashboard" name="photo_dashboard" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this, 'preview_dashboard')" required>
                        <img id="preview_dashboard" class="photo-preview" style="display: none;">
                    </div>
                </div>
            </div>

            <!-- 8. Seller Information -->
            <div class="form-section">
                <div class="section-title"><span>8</span> Seller Information</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Seller Name</label>
                        <input type="text" name="seller_name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <label>Primary Contact Number</label>
                        <input type="text" name="seller_contact" class="form-control" placeholder="e.g. 0917-123-4567" required>
                    </div>
                    <div class="form-group">
                        <label>Other Contact Method (Optional)</label>
                        <input type="text" name="other_contact" class="form-control" placeholder="e.g. Viber, Email, FB Messenger">
                    </div>
                    <div class="form-group">
                        <label>Viewing Location</label>
                        <input type="text" name="location" class="form-control" placeholder="City or Landmark (e.g. Makati City)" required>
                    </div>
                </div>
            </div>

            <!-- 9. The "Final Touches" -->
            <div class="form-section">
                <div class="section-title"><span>9</span> The "Final Touches"</div>
                <div class="form-group">
                    <label>Reason for Selling (RFS)</label>
                    <input type="text" name="rfs" class="form-control" placeholder="Enter reason for selling">
                </div>
                <div class="form-group">
                    <label>Warranty Status</label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="warranty_status" value="Active"> Active Manufacturer / Dealer Warranty</label>
                        <label class="radio-label"><input type="radio" name="warranty_status" value="No Warranty" checked> No Warranty</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Seller's Notes & Detailed Description</label>
                    <textarea name="seller_notes" class="form-control" rows="4" placeholder="Any additional information..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Generate Verified Listing</button>
        </form>
    </div>
</div>

<script>
// --- Image Preview for Camera Captures ---
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
    
    // --- 1. Dynamic Model Data (Toyota PH Spec Colors & Fuel) ---
    const modelData = {
        "Vios": {
            colors: ["Super White", "White Pearl", "Silver Metallic", "Gray Metallic", "Attitude Black", "Red Mica Metallic", "Alumina Jade Metallic", "Blackish Red Mica"],
            fuel: ["Gasoline"]
        },
        "Innova": {
            colors: ["Super White", "White Pearl Crystal Shine", "Silver Metallic", "Blackish Red Mica", "Alumina Jade Metallic", "Attitude Black", "Bronze Mica Metallic"],
            fuel: ["Diesel", "Gasoline"]
        },
        "Fortuner": {
            colors: ["Super White", "White Pearl", "Silver Metallic", "Attitude Black Mica", "Gray Metallic", "Phantom Brown Metallic", "Nebula Blue Metallic", "Sparkling Black Pearl"],
            fuel: ["Diesel", "Gasoline"]
        },
        "Hilux": {
            colors: ["Super White", "Silver Metallic", "Gray Metallic", "Attitude Black Mica", "Crimson Spark Red Metallic", "Nebula Blue", "Orange Metallic", "Emotional Red"],
            fuel: ["Diesel", "Gasoline"]
        },
        "Wigo": {
            colors: ["Orange Metallic", "Silver Metallic", "Gray Metallic", "White", "Black", "Yellow", "Red Mica Metallic"],
            fuel: ["Gasoline"]
        },
        "Avanza": {
            colors: ["Silver Mica Metallic", "Gray Metallic", "Black Metallic", "Dark Red Mica", "White", "Nebula Blue", "Green Mica"],
            fuel: ["Gasoline"]
        }
    };

    const modelSelect = document.getElementById("model-select");
    const colorSelect = document.getElementById("color-select");
    const fuelGasLabel = document.getElementById("fuel-gas");
    const fuelDieselLabel = document.getElementById("fuel-diesel");
    const radioGas = document.getElementById("radio-gas");
    const radioDiesel = document.getElementById("radio-diesel");

    modelSelect.addEventListener("change", function() {
        const selectedModel = this.value;
        
        colorSelect.innerHTML = '<option value="">Select a color</option>';
        
        if (modelData[selectedModel]) {
            colorSelect.disabled = false;
            
            modelData[selectedModel].colors.forEach(function(color) {
                const option = document.createElement("option");
                option.value = color;
                option.textContent = color;
                colorSelect.appendChild(option);
            });
            
            const otherOption = document.createElement("option");
            otherOption.value = "Other / Custom";
            otherOption.textContent = "Other / Custom Paint";
            colorSelect.appendChild(otherOption);

            const availableFuels = modelData[selectedModel].fuel;
            
            if (availableFuels.includes("Gasoline")) {
                fuelGasLabel.style.display = "inline-flex";
            } else {
                fuelGasLabel.style.display = "none";
                radioGas.checked = false;
            }
            
            if (availableFuels.includes("Diesel")) {
                fuelDieselLabel.style.display = "inline-flex";
            } else {
                fuelDieselLabel.style.display = "none";
                radioDiesel.checked = false;
            }

            if (availableFuels.length === 1) {
                if (availableFuels[0] === "Gasoline") radioGas.checked = true;
                if (availableFuels[0] === "Diesel") radioDiesel.checked = true;
            }

        } else {
            colorSelect.disabled = true;
            colorSelect.innerHTML = '<option value="">Select a model first</option>';
            fuelGasLabel.style.display = "inline-flex";
            fuelDieselLabel.style.display = "inline-flex";
        }
    });

    // --- 2. Money Formatter for Asking Price ---
    const displayPrice = document.getElementById('display_price');
    const actualPrice = document.getElementById('actual_price');

    displayPrice.addEventListener('input', function(e) {
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