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
        
        // FIX: Capture the color variable properly from the select or custom input
        $color = $_POST['color'] ?? '';
        if (empty($color)) {
            $color = $_POST['color_custom'] ?? 'Standard';
        }
        
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

        $first_owner = $_POST['first_owner'] ?? 'No';
        $casa_maintained = $_POST['casa_maintained'] ?? 'No';
        $accident_history = ($_POST['major_accident'] ?? 'No') === 'Yes' ? ($_POST['accident_details'] ?? 'Yes') : 'None';
        $flooded = $_POST['flooded'] ?? 'No';
        $general_condition = $_POST['general_condition'] ?? 'Good';
        $lto_status = $_POST['lto_status'] ?? 'Updated';
        $or_cr = $_POST['or_cr'] ?? 'Yes';
        $fully_paid = $_POST['fully_paid'] ?? 'Yes';
        $deed_of_sale = $_POST['deed_of_sale'] ?? 'Yes';

        // Strip formatting from price, and ensure it's a valid number
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price'] ?? '0');
        if (empty($asking_price)) $asking_price = 0;
        
        $price_negotiability = ($_POST['negotiable'] ?? 'No') === 'Yes' ? 'Negotiable' : 'Fixed Price';
        $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : 'Cash';
        $trade_in = $_POST['trade_in'] ?? 'No';

        $city     = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $landmark = trim($_POST['location'] ?? '');

        if ($city === '') { throw new Exception('Please select a City / Municipality.'); }
        if ($barangay === '') { throw new Exception('Please select a Barangay.'); }
        
        $full_location = $city . ', ' . ($district ? $district . ', ' : '') . $barangay . ($landmark ? ' — ' . $landmark : '');

        $seller_name = $_POST['seller_name'] ?? '';
        $primary_contact = $_POST['seller_contact'] ?? '';
        $other_contact = $_POST['other_contact'] ?? '';
        $seller_contact = $primary_contact;
        if (!empty($other_contact)) {
            $seller_contact .= ' (' . trim($other_contact) . ')';
        }
        
        $reason_for_selling = $_POST['rfs'] ?? '';
        $warranty_status = $_POST['warranty_status'] ?? 'No Warranty';
        $seller_notes = $_POST['seller_notes'] ?? '';

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

        $risk = compute_risk_score($pdo, [
            'brand' => $brand, 'model' => $model,
            'year_manufactured' => (int)$year_manufactured,
            'mileage' => (int)$mileage,
            'asking_price' => (float)$asking_price,
            'location' => $full_location,
            'city' => $city, 'district' => $district, 'barangay' => $barangay,
            'accident_history' => $accident_history,
            'is_flood_flagged' => ($flooded === 'Yes'),
            'verification_status' => 'unverified',
        ]);

        // FIX: Ensure risk flags is an array before imploding to prevent fatal errors
        $safe_risk_flags = isset($risk['flags']) && is_array($risk['flags']) ? implode(' | ', $risk['flags']) : '';

        if (($risk['risk_level'] ?? '') === 'High') {
            throw new Exception('Listing rejected by risk engine: ' . $safe_risk_flags);
        }

        // FIX: Added verification_status to the INSERT query
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_listings (
                vehicle_type, brand, model, year_manufactured, mileage, color, 
                transmission, fuel_type, modifications, registration_status, or_cr_ready, fully_paid, deed_of_sale_ready,
                maintenance_history, accident_history, first_owner, flooded, 
                asking_price, price_negotiability, payment_methods, trade_in,
                location, city, district, barangay, reason_for_selling, warranty_status, seller_notes, 
                photo_front, photo_rear, photo_side, photo_engine, photo_interior, photo_dashboard,
                status, listing_type, verification_status, seller_name, seller_contact,
                risk_score, risk_level, risk_flags, risk_recommendation, risk_evaluated_at
            ) VALUES (
                'Car', ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, 
                'pending', 'user_sale', 'unverified', ?, ?,
                ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $brand, $model, $year_manufactured, $mileage, $color,
            $transmission, $fuel_type, $modifications, $lto_status, $or_cr, $fully_paid, $deed_of_sale,
            $maintenance_history, $accident_history, $first_owner, $flooded,
            $asking_price, $price_negotiability, $payment_methods, $trade_in,
            $full_location, $city, $district, $barangay, $reason_for_selling, $warranty_status, $seller_notes,
            $uploaded_files['photo_front'] ?: null, $uploaded_files['photo_rear'] ?: null, $uploaded_files['photo_side'] ?: null, 
            $uploaded_files['photo_engine'] ?: null, $uploaded_files['photo_interior'] ?: null, $uploaded_files['photo_dashboard'] ?: null,
            $seller_name, $seller_contact,
            $risk['risk_score'] ?? 0, $risk['risk_level'] ?? 'Not evaluated', $safe_risk_flags, $risk['recommendation'] ?? '', date('Y-m-d H:i:s')
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
    .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
    .form-group label { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
    .form-control { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; color: var(--text); background: var(--paper); transition: var(--transition); }
    .form-control:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); background: var(--surface); }
    .form-control:disabled { background: var(--border); cursor: not-allowed; }
    .radio-group, .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; }
    .radio-label, .checkbox-label { display: flex; align-items: center; gap: 6px; font-size: 14px; color: var(--text); cursor: pointer; }
    .btn-submit { background: var(--blue); color: white; padding: 14px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; transition: 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
    .btn-submit:hover { background: var(--blue-dark); transform: translateY(-2px); }
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: var(--teal-soft); color: var(--teal); border: 1px solid var(--teal); }
    .alert-danger { background: var(--coral-soft); color: var(--coral); border: 1px solid var(--coral); }
    
    .photo-capture-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .photo-upload-box { border: 2px dashed var(--border); border-radius: 12px; padding: 20px 10px; text-align: center; background: var(--paper); position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: var(--transition); }
    .camera-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--blue-soft); color: var(--blue); padding: 10px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700; margin-top: 8px; transition: 0.2s; }
    .camera-btn:hover { background: var(--blue); color: white; }
    .camera-btn svg { width: 16px; height: 16px; }
    .photo-preview { margin-top: 12px; width: 100%; border-radius: 6px; height: 120px; object-fit: cover; border: 1px solid var(--border); display: none; }
</style>

<div class="view">
    <div class="form-container">
        <h2 style="font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px;">Sell Your Car</h2>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Complete the sections below to generate a detailed verified listing for your vehicle.</p>

        <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- 1. Select Your Vehicle -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Select Your Vehicle</div>
                <div class="grid-2">
                    <div class="form-group"><label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="Toyota" readonly style="background-color:var(--border);color:var(--text-muted);cursor:not-allowed;" required>
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
                    <div class="form-group"><label>Exterior Color</label>
                        <select name="color" id="color-select" class="form-control" required disabled>
                            <option value="">Select model &amp; year first</option>
                        </select>
                        <input type="text" name="color_custom" id="color-custom" class="form-control" placeholder="Type custom color" style="display:none; margin-top:8px;" maxlength="60">
                    </div>
                </div>
            </div>

            <!-- 2. Vehicle Specs -->
            <div class="form-section">
                <div class="section-title"><span>2</span> Vehicle Specifications</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mileage (Max 5,250,000 km)</label>
                        <input type="number" name="mileage" class="form-control" min="0" max="5250000" placeholder="e.g. 45000" required>
                    </div>
                    <div class="form-group"><label>Transmission</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="transmission" value="Automatic" required> Automatic</label>
                            <label class="radio-label"><input type="radio" name="transmission" value="Manual"> Manual</label>
                        </div>
                    </div>
                    <div class="form-group"><label>Fuel Type</label>
                        <div class="radio-group">
                            <label class="radio-label" id="fuel-gas"><input type="radio" name="fuel_type" id="radio-gas" value="Gasoline" required> Gas</label>
                            <label class="radio-label" id="fuel-diesel"><input type="radio" name="fuel_type" id="radio-diesel" value="Diesel"> Diesel</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Pricing -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Pricing &amp; Payment</div>
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
                </div>
            </div>

            <!-- 4. Location & Seller Info -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Location & Seller Information</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>City / Municipality <span style="color:var(--coral);">*</span></label>
                        <select name="city" id="city-select" class="form-control" required>
                            <option value="">Select City / Municipality</option>
                        </select>
                    </div>
                    <div class="form-group" id="district-wrapper">
                        <!-- FIX: Changed from <select> to <input> so it doesn't block the location flow if districts are unavailable -->
                        <label>District (Optional)</label>
                        <input type="text" name="district" id="district-input" class="form-control" placeholder="e.g. Sampaloc, Diliman">
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label>Barangay <span style="color:var(--coral);">*</span></label>
                        <select name="barangay" id="barangay-select" class="form-control" required disabled>
                            <option value="">Select city first</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Seller Name <span style="color:var(--coral);">*</span></label>
                        <input type="text" name="seller_name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span style="color:var(--coral);">*</span></label>
                        <input type="tel" name="seller_contact" class="form-control" placeholder="e.g. 09171234567" maxlength="11" required>
                    </div>
                </div>
            </div>
            
            <!-- (You can insert your photo upload HTML here if needed, based on your previous logic) -->

            <button type="submit" class="btn-submit">Generate Verified Listing</button>
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
        const brand = 'Toyota'; // Hardcoded for this valuation engine
        const model = modelSelect.value;
        const year = yearSelect.value;

        if (!model || !year) {
            colorSelect.innerHTML = '<option value="">Select model & year first</option>';
            colorSelect.disabled = true;
            colorCustom.style.display = 'none';
            return;
        }

        // Pulls from your car_colors.php file
        const colors = typeof window.getCarColors === 'function' ? window.getCarColors(brand, model, year) : [];
        
        colorSelect.innerHTML = '<option value="">Select color...</option>';
        colors.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            colorSelect.appendChild(opt);
        });
        
        colorSelect.innerHTML += '<option value="Other">Other (Custom)</option>';
        colorSelect.disabled = false;
    }

    modelSelect.addEventListener('change', updateColors);
    yearSelect.addEventListener('change', updateColors);

    colorSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            colorCustom.style.display = 'block';
            colorCustom.required = true;
        } else {
            colorCustom.style.display = 'none';
            colorCustom.required = false;
            colorCustom.value = '';
        }
    });

    // ==========================================
    // 2. PRICING FORMATTER LOGIC
    // ==========================================
    const displayPrice = document.getElementById('display_price');
    const actualPrice = document.getElementById('actual_price');

    displayPrice.addEventListener('input', function(e) {
        // Strip everything except numbers
        let val = this.value.replace(/[^0-9]/g, ''); 
        actualPrice.value = val; // Store raw number for PHP
        
        if (val) {
            this.value = '₱ ' + parseInt(val, 10).toLocaleString(); // Format with commas
        } else {
            this.value = '';
        }
    });

    // ==========================================
    // 3. LOCATION LOGIC (YOUR DATABASE)
    // ==========================================
    // Since ph_locations.php handles the dropdowns for city and barangay,
    // ensure your existing logic is included or invoked here.

    document.getElementById('display_price').addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, ""); 
    if (value) {
        let intValue = parseInt(value, 10);
        this.value = "₱ " + intValue.toLocaleString('en-US');
    } else {
        this.value = "";
    }
});

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
        districtWrap.style.display = 'block';
        return;
    }

    const hasDistricts = window.isCityWithDistricts(city);

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

function updateColors() {
    const model = document.getElementById('model-select').value;
    const year = document.getElementById('year-select').value;
    const colorSelect = document.getElementById('color-select');
    const colorCustom = document.getElementById('color-custom');
    
    colorSelect.innerHTML = '<option value="">Select a color</option>';
    colorCustom.style.display = 'none';
    colorCustom.value = '';
    
    if (model && year) {
        colorSelect.disabled = false;
        const colors = window.getCarColors('Toyota', model, year) || [];
        
        colors.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; opt.textContent = c;
            colorSelect.appendChild(opt);
        });
        
        const other = document.createElement('option');
        other.value = 'Other / Custom'; 
        other.textContent = 'Other / Custom';
        colorSelect.appendChild(other);
        
        if (colorSelect.options.length > 0) {
            colorSelect.options[0].disabled = true;
        }
    } else {
        colorSelect.disabled = true;
        colorSelect.innerHTML = '<option value="">Select model & year first</option>';
    }
}

document.getElementById('color-select').addEventListener('change', function() {
    const colorCustom = document.getElementById('color-custom');
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

document.getElementById('model-select').addEventListener('change', updateColors);
document.getElementById('year-select').addEventListener('change', updateColors);
</script>
</script>

<?php include 'footer.php'; ?>