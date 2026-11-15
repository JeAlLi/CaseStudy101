<?php
session_start();
require_once 'db.php';
$current_page = 'submit_listing';

$success_message = '';
$error_message = '';

// Authentication Gate
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $brand = $_POST['brand'] ?? 'Toyota';
        $model = $_POST['model'] ?? '';
        $year_manufactured = $_POST['year_manufactured'] ?? '';
        $mileage = (int)($_POST['mileage'] ?? 0);
        $transmission = $_POST['transmission'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';
        $is_stock = $_POST['is_stock'] ?? 'Yes';
        
        $color = $_POST['color'] ?? '';
        if (empty($color) || $color === 'Other / Custom') {
            $color = trim($_POST['color_custom'] ?? 'Standard');
        }
        
        $modifications = 'Stock / None';
        if ($is_stock === 'No') {
            $modifications = trim($_POST['modifications_details'] ?? 'Various Modifications');
        }

        $first_owner = $_POST['first_owner'] ?? 'No';
        $casa_maintained = $_POST['casa_maintained'] ?? 'No';
        $accident_history = ($_POST['major_accident'] ?? 'No') === 'Yes' ? ($_POST['accident_details'] ?? 'Yes') : 'None';
        $flooded = $_POST['flooded'] ?? 'No';
        $general_condition = $_POST['general_condition'] ?? 'Good';
        
        $lto_status = $_POST['lto_status'] ?? 'Updated';
        $or_cr = $_POST['or_cr'] ?? 'Yes';
        $fully_paid = $_POST['fully_paid'] ?? 'Yes';
        $deed_of_sale = $_POST['deed_of_sale'] ?? 'Yes';

        // Pricing capture and validation
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price'] ?? '0');
        $asking_price = (float)$asking_price;
        
        // --- BACKEND ENTRAPMENT / VALIDATION ---
        if ($mileage < 0 || $mileage > 5250000) {
            throw new Exception("Mileage cannot exceed 5,250,000 km.");
        }
        if ($asking_price <= 0 || $asking_price > 5000000) {
            throw new Exception("Asking price must be between ₱1 and ₱5,000,000.");
        }
        
        $price_negotiability = ($_POST['negotiable'] ?? 'No') === 'Yes' ? 'Negotiable' : 'Fixed Price';
        
        $payment_methods = 'Cash';
        if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
            $payment_methods = implode(', ', $_POST['payment_methods']);
        }
        $trade_in = $_POST['trade_in'] ?? 'No';

        $city     = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');

        if ($city === '') { throw new Exception('Please select a City / Municipality.'); }
        if ($barangay === '') { throw new Exception('Please select a Barangay.'); }
        
        $full_location = $city . ', ' . ($district ? $district . ', ' : '') . $barangay . ($landmark ? ' — ' . $landmark : '');

        // --- CONTACT NUMBER ENTRAPMENT ---
        $seller_name = $_POST['seller_name'] ?? '';
        $primary_contact = trim($_POST['seller_contact'] ?? '');
        $other_contact = trim($_POST['other_contact'] ?? '');
        
        if (!preg_match('/^09[0-9]{9}$/', $primary_contact)) {
            throw new Exception("Primary contact must be a valid 11-digit Philippine mobile number starting with 09.");
        }
        if (!empty($other_contact) && !preg_match('/^09[0-9]{9}$/', $other_contact)) {
            throw new Exception("Alternative contact must be a valid 11-digit Philippine mobile number starting with 09.");
        }

        $seller_contact = $primary_contact;
        if (!empty($other_contact)) {
            $seller_contact .= ' (' . $other_contact . ')';
        }
        
        $reason_for_selling = $_POST['reason_for_selling'] ?? '';
        $warranty_status = $_POST['warranty_status'] ?? 'No Warranty';
        $seller_notes = $_POST['seller_notes'] ?? '';

        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

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

        $maintenance_history = $casa_maintained === 'Yes' ? 'Excellent (Casa Maintained)' : $general_condition;

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_listings (
                user_id, vehicle_type, brand, model, year_manufactured, mileage, color, 
                transmission, fuel_type, modifications, registration_status, or_cr_ready, fully_paid, deed_of_sale_ready,
                maintenance_history, accident_history, first_owner, flooded, 
                asking_price, price_negotiability, payment_methods, trade_in,
                location, city, district, barangay, reason_for_selling, warranty_status, seller_notes, 
                photo_front, photo_rear, photo_side, photo_engine, photo_interior, photo_dashboard,
                status, listing_type, verification_status, seller_name, seller_contact,
                risk_score, risk_level, risk_flags, risk_recommendation, risk_evaluated_at
            ) VALUES (
                ?, 'Car', ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, 
                'pending', 'user_sale', 'unverified', ?, ?,
                0, 'Not evaluated', '', '', NULL
            )
        ");
        
        $stmt->execute([
            $_SESSION['user_id'], $brand, $model, $year_manufactured, $mileage, $color,
            $transmission, $fuel_type, $modifications, $lto_status, $or_cr, $fully_paid, $deed_of_sale,
            $maintenance_history, $accident_history, $first_owner, $flooded,
            $asking_price, $price_negotiability, $payment_methods, $trade_in,
            $full_location, $city, $district, $barangay, $reason_for_selling, $warranty_status, $seller_notes,
            $uploaded_files['photo_front'], $uploaded_files['photo_rear'], $uploaded_files['photo_side'], 
            $uploaded_files['photo_engine'], $uploaded_files['photo_interior'], $uploaded_files['photo_dashboard'],
            $seller_name, $seller_contact
        ]);

        $success_message = "Your listing has been submitted successfully and is pending review.";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

include 'header.php';
?>
<style>
    .form-container { 
        background: var(--surface); 
        border-radius: 16px; 
        padding: 32px; 
        box-shadow: var(--shadow-sm); 
        border: 1px solid var(--border); 
        max-width: 900px; 
        margin: 0 auto; 
        transition: var(--transition);
    }
    .form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
    .form-section:last-child { border-bottom: none; }
    .section-title { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title span { background: var(--blue); color: white; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
    .form-group label { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
    .form-control { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; color: var(--text); background: var(--paper); transition: var(--transition); width: 100%; box-sizing: border-box;}
    .form-control:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); background: var(--surface); }
    .form-control:disabled { background: var(--border); cursor: not-allowed; opacity: 0.7; }
    .radio-group, .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; }
    .radio-label, .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text); cursor: pointer; }
    .btn-submit { background: var(--olive-drab); color: white; padding: 16px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; transition: 0.2s; box-shadow: 0 4px 12px rgba(107, 142, 35, 0.2); text-transform: uppercase; letter-spacing: 1px;}
    .btn-submit:hover { background: var(--olive-hover); transform: translateY(-2px); }
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; }
    .alert-danger { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ff8793; }
</style>

<div class="view">
    <div class="form-container">
        <h2 style="font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px;">List Your Vehicle</h2>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Complete all sections to generate a verified market listing.</p>

        <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            
            <!-- 1. Select Your Vehicle -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Vehicle Basics</div>
                <div class="grid-2">
                    <div class="form-group"><label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="Toyota" readonly style="background-color:var(--border);color:var(--text-muted);cursor:not-allowed;" required>
                    </div>
                    <div class="form-group"><label>Car Model <span style="color:var(--coral);">*</span></label>
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
                    <div class="form-group"><label>Year Model <span style="color:var(--coral);">*</span></label>
                        <select name="year_manufactured" id="year-select" class="form-control" required>
                            <option value="">Select year</option>
                            <?php for ($i = 2026; $i >= 2000; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Exterior Color <span style="color:var(--coral);">*</span></label>
                        <select name="color" id="color-select" class="form-control" required disabled>
                            <option value="">Select model &amp; year first</option>
                        </select>
                        <input type="text" name="color_custom" id="color-custom" class="form-control" placeholder="Type custom color" style="display:none; margin-top:8px;" maxlength="60">
                    </div>
                    <div class="form-group">
                        <label>Mileage (Max 5,250,000 km) <span style="color:var(--coral);">*</span></label>
                        <input type="number" name="mileage" class="form-control" min="0" max="5250000" placeholder="e.g. 45000" required>
                    </div>
                    <div class="form-group"><label>Transmission <span style="color:var(--coral);">*</span></label>
                        <select class="form-control" name="transmission" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Fuel Type <span style="color:var(--coral);">*</span></label>
                        <select class="form-control" name="fuel_type" required>
                            <option value="Gasoline">Gasoline</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Condition & History -->
            <div class="form-section">
                <div class="section-title"><span>2</span> Condition & History</div>
                <div class="grid-2">
                    <div class="form-group"><label>Are you the First Owner?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="first_owner" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="first_owner" value="No"> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Casa Maintained?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="casa_maintained" value="No" checked> No (Third-party/DIY)</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Flooded?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="flooded" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="flooded" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>General Condition</label>
                        <select name="general_condition" class="form-control">
                            <option value="Excellent">Excellent</option>
                            <option value="Good" selected>Good</option>
                            <option value="Fair">Fair / Needs Minor Work</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Major Accident History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="major_accident" id="accident_yes" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="major_accident" id="accident_no" value="No" checked> None</label>
                        </div>
                        <input type="text" name="accident_details" id="accident_details" class="form-control" placeholder="Briefly describe the damage/repair" style="display:none; margin-top:8px;">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Is the vehicle completely stock?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="is_stock" id="stock_yes" value="Yes" checked> Yes, Stock/None</label>
                            <label class="radio-label"><input type="radio" name="is_stock" id="stock_no" value="No"> No, Modified</label>
                        </div>
                        <input type="text" name="modifications_details" id="modifications_details" class="form-control" placeholder="List aftermarket wheels, exhaust, tuning, etc." style="display:none; margin-top:8px;">
                    </div>
                </div>
            </div>

            <!-- 3. Legal Documents -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Legal Documents</div>
                <div class="grid-2">
                    <div class="form-group"><label>LTO Registration Status</label>
                        <select name="lto_status" class="form-control">
                            <option value="Updated" selected>Updated</option>
                            <option value="Expired">Expired</option>
                            <option value="Unregistered">Unregistered</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Original OR/CR Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="or_cr" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="or_cr" value="No"> No / Lost</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Is the vehicle fully paid?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fully_paid" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="fully_paid" value="No"> No (Encumbered)</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Open Deed of Sale Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="Yes" checked> Yes</label>
                            <label class="radio-label"><input type="radio" name="deed_of_sale" value="No"> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Pricing & Payment -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Pricing & Payment</div>
                <div class="grid-2">
                    <div class="form-group"><label>Asking Price (Max ₱5,000,000) <span style="color:var(--coral);">*</span></label>
                        <input type="text" id="display_price" class="form-control" placeholder="₱ 0" required>
                        <input type="hidden" name="asking_price" id="actual_price">
                    </div>
                    <div class="form-group"><label>Price Negotiable?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="negotiable" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="negotiable" value="No" checked> No (Fixed)</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Open for Trade-in?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="trade_in" value="Yes"> Yes</label>
                            <label class="radio-label"><input type="radio" name="trade_in" value="No" checked> No</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Accepted Payment Methods</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Cash" checked> Cash</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Bank Transfer"> Bank Transfer</label>
                            <label class="checkbox-label"><input type="checkbox" name="payment_methods[]" value="Financing"> Auto Loan / Financing</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Location & Seller Info -->
            <div class="form-section">
                <div class="section-title"><span>5</span> Location & Contact</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>City / Municipality <span style="color:var(--coral);">*</span></label>
                        <select name="city" id="city-select" class="form-control" required>
                            <option value="">Select City / Municipality</option>
                        </select>
                    </div>
                    <!-- District Wrapper -->
                    <div class="form-group" id="district-wrapper" style="display:none;">
                        <label>District <span style="color:var(--coral);">*</span></label>
                        <select name="district" id="district-select" class="form-control" disabled>
                            <option value="">Select district</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Barangay <span style="color:var(--coral);">*</span></label>
                        <select name="barangay" id="barangay-select" class="form-control" required disabled>
                            <option value="">Select city first</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Landmark / Specific Location (Optional)</label>
                        <input type="text" name="landmark" class="form-control" placeholder="e.g. Near SM Fairview">
                    </div>
                    <div class="form-group">
                        <label>Seller Name <span style="color:var(--coral);">*</span></label>
                        <input type="text" name="seller_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Primary Contact Number (09...) <span style="color:var(--coral);">*</span></label>
                        <input type="tel" name="seller_contact" class="form-control" placeholder="e.g. 09171234567" pattern="09[0-9]{9}" maxlength="11" required>
                    </div>
                    <div class="form-group">
                        <label>Alternative Contact Number (Optional)</label>
                        <input type="tel" name="other_contact" class="form-control" placeholder="e.g. 09181234567" pattern="09[0-9]{9}" maxlength="11">
                    </div>
                </div>
            </div>

            <!-- 6. Photo Gallery -->
            <div class="form-section">
                <div class="section-title"><span>6</span> Vehicle Photos</div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Clear, well-lit photos increase buyer trust. Max size 5MB per photo.</p>
                <div class="grid-3">
                    <div class="form-group">
                        <label>Front Angle <span style="color:var(--coral);">*</span></label>
                        <input type="file" name="photo_front" class="form-control" accept="image/jpeg, image/png, image/webp" required>
                    </div>
                    <div class="form-group">
                        <label>Rear Angle <span style="color:var(--coral);">*</span></label>
                        <input type="file" name="photo_rear" class="form-control" accept="image/jpeg, image/png, image/webp" required>
                    </div>
                    <div class="form-group">
                        <label>Side Angle</label>
                        <input type="file" name="photo_side" class="form-control" accept="image/jpeg, image/png, image/webp">
                    </div>
                    <div class="form-group">
                        <label>Engine Bay</label>
                        <input type="file" name="photo_engine" class="form-control" accept="image/jpeg, image/png, image/webp">
                    </div>
                    <div class="form-group">
                        <label>Interior (Seats)</label>
                        <input type="file" name="photo_interior" class="form-control" accept="image/jpeg, image/png, image/webp">
                    </div>
                    <div class="form-group">
                        <label>Dashboard & Odometer</label>
                        <input type="file" name="photo_dashboard" class="form-control" accept="image/jpeg, image/png, image/webp">
                    </div>
                </div>
            </div>

            <!-- 7. Additional Information -->
            <div class="form-section">
                <div class="section-title"><span>7</span> Additional Details</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Reason for Selling (Optional)</label>
                        <input type="text" name="reason_for_selling" class="form-control" placeholder="e.g. Upgrading to SUV, Moving out of country">
                    </div>
                    <div class="form-group">
                        <label>Warranty Status</label>
                        <input type="text" name="warranty_status" class="form-control" placeholder="e.g. Under Casa Warranty until 2026, None">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Additional Seller Notes</label>
                        <textarea name="seller_notes" class="form-control" rows="4" placeholder="Detail any recent maintenance, parts replaced, or minor issues buyers should know about..."></textarea>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Publish Verified Listing</button>
        </form>
    </div>
</div>

<script src="car_colors.php"></script>
<script src="ph_locations.php"></script>
<script>
    // ==========================================
    // 1. VEHICLE MODEL, YEAR & COLOR LOGIC
    // ==========================================
    const modelSelect = document.getElementById('model-select');
    const yearSelect = document.getElementById('year-select');
    const colorSelect = document.getElementById('color-select');
    const colorCustom = document.getElementById('color-custom');

    function updateColors() {
        const brand = 'Toyota';
        const model = modelSelect.value;
        const year = yearSelect.value;

        if (!model || !year) {
            colorSelect.innerHTML = '<option value="">Select model & year first</option>';
            colorSelect.disabled = true;
            colorCustom.style.display = 'none';
            colorCustom.required = false;
            return;
        }

        const colors = typeof window.getCarColors === 'function' ? window.getCarColors(brand, model, year) : [];
        
        colorSelect.innerHTML = '<option value="">Select color...</option>';
        colors.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            colorSelect.appendChild(opt);
        });
        
        colorSelect.innerHTML += '<option value="Other / Custom">Other / Custom</option>';
        colorSelect.disabled = false;
    }

    modelSelect.addEventListener('change', updateColors);
    yearSelect.addEventListener('change', updateColors);

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

    // ==========================================
    // 1.5 ACCIDENT AND MODIFICATION TOGGLES
    // ==========================================
    const accidentYes = document.getElementById('accident_yes');
    const accidentNo = document.getElementById('accident_no');
    const accidentDetails = document.getElementById('accident_details');

    accidentYes.addEventListener('change', () => { accidentDetails.style.display = 'block'; accidentDetails.required = true; });
    accidentNo.addEventListener('change', () => { accidentDetails.style.display = 'none'; accidentDetails.required = false; });

    const stockYes = document.getElementById('stock_yes');
    const stockNo = document.getElementById('stock_no');
    const modificationsDetails = document.getElementById('modifications_details');

    stockNo.addEventListener('change', () => { modificationsDetails.style.display = 'block'; modificationsDetails.required = true; });
    stockYes.addEventListener('change', () => { modificationsDetails.style.display = 'none'; modificationsDetails.required = false; });


    // ==========================================
    // 2. PRICING FORMATTER & CAP LOGIC (Max 5M)
    // ==========================================
    const displayPrice = document.getElementById('display_price');
    const actualPrice = document.getElementById('actual_price');

    displayPrice.addEventListener('input', function(e) {
        let val = this.value.replace(/[^0-9]/g, ''); 
        
        if (val !== '' && parseInt(val, 10) > 5000000) {
            val = '5000000';
        }
        
        actualPrice.value = val; 
        
        if (val) {
            this.value = '₱ ' + parseInt(val, 10).toLocaleString('en-US'); 
        } else {
            this.value = '';
        }
    });

    // ==========================================
    // 3. LOCATION LOGIC
    // ==========================================
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
            districtWrap.style.display = 'none';
            return;
        }

        const hasDistricts = typeof window.isCityWithDistricts === 'function' ? window.isCityWithDistricts(city) : false;

        if (hasDistricts) {
            districtWrap.style.display = 'block';
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
            if(typeof window.getBarangays === 'function'){
                window.getBarangays(city, '').forEach(function(b) {
                    const opt = document.createElement('option');
                    opt.value = b; opt.textContent = b;
                    barangaySelect.appendChild(opt);
                });
            }
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
        if(typeof window.getBarangays === 'function'){
            window.getBarangays(city, district).forEach(function(b) {
                const opt = document.createElement('option');
                opt.value = b; opt.textContent = b;
                barangaySelect.appendChild(opt);
            });
        }
        barangaySelect.options[0].disabled = true;
    });
</script>

<?php include 'footer.php'; ?>