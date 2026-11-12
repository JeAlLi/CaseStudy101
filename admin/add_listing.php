<?php
session_start();
require_once '../db.php';
$current_page = 'add_listing';

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
        
        $color = $_POST['color'] ?? '';
        if ($color === 'Other / Custom') {
            $custom_color = trim($_POST['color_custom'] ?? '');
            if ($custom_color !== '') { $color = $custom_color; }
        }
        
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price'] ?? '0');
        $status = $_POST['status'] ?? 'approved';
        $verification_status = $_POST['verification_status'] ?? 'unverified';

        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $full_location = $city . ($district ? ', ' . $district : '') . ($barangay ? ', ' . $barangay : '');

        $seller_name = $_POST['seller_name'] ?? '';
        $seller_contact = $_POST['seller_contact'] ?? '';
        $seller_notes = $_POST['seller_notes'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_listings (
                vehicle_type, brand, model, year_manufactured, mileage, color, 
                transmission, fuel_type, asking_price, 
                location, city, district, barangay,
                status, verification_status, listing_type, seller_name, seller_contact, seller_notes
            ) VALUES (
                'Car', ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, 'user_sale', ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $brand, $model, $year_manufactured, $mileage, $color,
            $transmission, $fuel_type, $asking_price,
            $full_location, $city, $district, $barangay,
            $status, $verification_status, $seller_name, $seller_contact, $seller_notes
        ]);

        $success_message = "Toyota listing added successfully! It is now live.";
    } catch (PDOException $e) {
        $error_message = "Error adding listing: " . $e->getMessage();
    }
}

include 'header.php';
?>

<script src="../ph_locations.php"></script>
<script src="../car_colors.php"></script>

<style>
    .admin-container { max-width: 900px; margin: 0 auto; padding: 24px 20px; }
    .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .page-sub { font-size: 14px; color: var(--text-muted); margin-bottom: 24px; }
    .form-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm); transition: var(--transition); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .field { display: flex; flex-direction: column; margin-bottom: 16px; }
    .field label { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .form-control { padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; color: var(--text); background: var(--paper); font-family: var(--font-body); transition: var(--transition); }
    .form-control:focus { outline: none; border-color: var(--blue); background: var(--surface); box-shadow: 0 0 0 3px var(--blue-soft); }
    
    .btn-submit { background: var(--blue); color: white; border: none; padding: 14px 24px; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; width: 100%; transition: 0.2s; margin-top: 16px; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
    .btn-submit:hover { background: var(--blue-dark); transform: translateY(-2px); }
    
    .form-section-title { font-family: var(--font-display); font-size: 15px; font-weight: 800; color: var(--text); padding-bottom: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
</style>

<div class="view admin-container">
    <h1 class="page-title">Add Toyota Listing</h1>
    <p class="page-sub">Publish a verified listing directly to the public database.</p>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?php echo $error_message; ?></div><?php endif; ?>

    <div class="form-panel">
        <form method="POST">
            
            <!-- SYSTEM OVERRIDES -->
            <div class="form-section" style="background: var(--paper); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 32px;">
                <div class="form-section-title" style="margin-bottom: 16px; border: none;">System Overrides</div>
                <div class="grid-2">
                    <div class="field">
                        <label>Listing Status</label>
                        <select name="status" class="form-control">
                            <option value="approved" selected>Approved (Go Live)</option>
                            <option value="pending">Pending (Review Later)</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Verification Status</label>
                        <select name="verification_status" class="form-control">
                            <option value="unverified" selected>Unverified (Shows Risk Score)</option>
                            <option value="mechanic_verified">Mechanic Verified (Hide Risk Score)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- VEHICLE INFO -->
            <div class="field" style="margin-bottom: 24px;">
                <div class="form-section-title">Vehicle Information</div>
                <div class="grid-2">
                    <div class="field">
                        <label>Brand</label>
                        <input type="text" class="form-control" value="Toyota" readonly style="background: var(--border); color: var(--text-muted);">
                    </div>
                    <div class="field">
                        <label>Model</label>
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
                    <div class="field">
                        <label>Year</label>
                        <select name="year_manufactured" id="year-select" class="form-control" required>
                            <option value="">Select year</option>
                            <?php for($i = 2026; $i >= 2010; $i--): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Mileage (km)</label>
                        <input type="number" name="mileage" class="form-control" required>
                    </div>
                    <div class="field">
                        <label>Transmission</label>
                        <select name="transmission" class="form-control">
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Fuel Type</label>
                        <select name="fuel_type" class="form-control">
                            <option value="Gasoline">Gasoline</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Color</label>
                        <select name="color" id="color-select" class="form-control" required disabled>
                            <option value="">Select model & year first</option>
                        </select>
                        <input type="text" name="color_custom" id="color-custom" class="form-control" placeholder="Type custom color" style="display:none; margin-top:8px;" maxlength="60">
                    </div>
                    <div class="field">
                        <label>Asking Price (₱)</label>
                        <input type="text" id="display_price" name="asking_price" class="form-control" placeholder="₱ 0" required>
                    </div>
                </div>
            </div>

            <!-- LOCATION -->
            <div class="field" style="margin-bottom: 24px;">
                <div class="form-section-title">Location</div>
                <div class="grid-2">
                    <div class="field">
                        <label>City / Municipality</label>
                        <select name="city" id="city-select" class="form-control" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="field" id="district-wrapper" style="display: block;">
                        <label>District (If applicable)</label>
                        <select name="district" id="district-select" class="form-control" disabled>
                            <option value="">Select city first</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column: span 2;">
                        <label>Barangay</label>
                        <select name="barangay" id="barangay-select" class="form-control" required disabled>
                            <option value="">Select city (and district) first</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SELLER INFO -->
            <div class="field" style="margin-bottom: 24px;">
                <div class="form-section-title">Seller Information</div>
                <div class="grid-2">
                    <div class="field">
                        <label>Seller Name</label>
                        <input type="text" name="seller_name" class="form-control" placeholder="Full Name">
                    </div>
                    <div class="field">
                        <label>Contact Number</label>
                        <input type="text" name="seller_contact" class="form-control" placeholder="0917-123-4567">
                    </div>
                    <div class="field" style="grid-column: span 2;">
                        <label>Seller Notes</label>
                        <textarea name="seller_notes" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Publish Listing</button>
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

<?php include '../footer.php'; ?>