<?php
session_start();
require_once '../db.php';
$current_page = 'manage_listings';

$success_message = '';
$error_message = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_listings.php");
    exit;
}
$listing_id = (int)$_GET['id'];

// Handle the Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect Text Fields
        $model = $_POST['model'];
        $year_manufactured = $_POST['year_manufactured'];
        $mileage = $_POST['mileage'];
        $color = $_POST['color'];
        $transmission = $_POST['transmission'];
        $fuel_type = $_POST['fuel_type'];
        $modifications = $_POST['modifications']; // Direct text edit for admin
        
        $maintenance_history = $_POST['maintenance_history'];
        $accident_history = $_POST['accident_history'];
        $first_owner = $_POST['first_owner'];
        $flooded = $_POST['flooded'];
        
        $registration_status = $_POST['registration_status'];
        $or_cr_ready = $_POST['or_cr_ready'];
        $fully_paid = $_POST['fully_paid'];
        $deed_of_sale_ready = $_POST['deed_of_sale_ready'];
        
        // Strip formatting from price (e.g. ₱ 500,000 -> 500000)
        $asking_price = str_replace(['₱', ',', ' '], '', $_POST['asking_price']); 
        $price_negotiability = $_POST['price_negotiability'];
        $payment_methods = $_POST['payment_methods']; // Direct text edit for admin
        $trade_in = $_POST['trade_in'];
        
        $seller_name = $_POST['seller_name'];
        $seller_contact = $_POST['seller_contact'];
        $location = $_POST['location'];
        $seller_notes = $_POST['seller_notes'];
        
        $status = $_POST['status'];

        // Handle Photo Uploads (Only overwrite if a new file is uploaded)
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $photo_fields = ['photo_front', 'photo_rear', 'photo_side', 'photo_engine', 'photo_interior', 'photo_dashboard'];
        $updated_photos = [];

        foreach ($photo_fields as $field) {
            // Keep old photo by default
            $updated_photos[$field] = $_POST['existing_' . $field]; 
            
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . $field . '.' . $ext;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $new_filename)) {
                    $updated_photos[$field] = $new_filename; // Update with new filename
                }
            }
        }

        // Execute Update Query
        $updateStmt = $pdo->prepare("
            UPDATE vehicle_listings SET 
                model=?, year_manufactured=?, mileage=?, color=?, 
                transmission=?, fuel_type=?, modifications=?, 
                maintenance_history=?, accident_history=?, first_owner=?, flooded=?, 
                registration_status=?, or_cr_ready=?, fully_paid=?, deed_of_sale_ready=?, 
                asking_price=?, price_negotiability=?, payment_methods=?, trade_in=?, 
                seller_name=?, seller_contact=?, location=?, seller_notes=?, status=?,
                photo_front=?, photo_rear=?, photo_side=?, photo_engine=?, photo_interior=?, photo_dashboard=?
            WHERE id=?
        ");

        $updateStmt->execute([
            $model, $year_manufactured, $mileage, $color, 
            $transmission, $fuel_type, $modifications, 
            $maintenance_history, $accident_history, $first_owner, $flooded, 
            $registration_status, $or_cr_ready, $fully_paid, $deed_of_sale_ready, 
            $asking_price, $price_negotiability, $payment_methods, $trade_in, 
            $seller_name, $seller_contact, $location, $seller_notes, $status,
            $updated_photos['photo_front'], $updated_photos['photo_rear'], $updated_photos['photo_side'], 
            $updated_photos['photo_engine'], $updated_photos['photo_interior'], $updated_photos['photo_dashboard'],
            $listing_id
        ]);

        $success_message = "Listing #{$listing_id} has been successfully updated!";
    } catch (PDOException $e) {
        $error_message = "Error updating listing: " . $e->getMessage();
    }
}

// Fetch current listing data
$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo "<div style='padding:50px; text-align:center;'>Listing not found. <a href='manage_listings.php'>Go Back</a></div>";
    exit;
}

include 'header.php';
?>

<style>
    /* Borrowed identical styles from submit_listing.php */
    .admin-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    .page-header-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .back-btn {
        font-size: 14px;
        color: #5865f2;
        text-decoration: none;
        font-weight: 600;
    }
    .back-btn:hover { text-decoration: underline; }
    
    .form-container {
        background: #ffffff;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .form-section {
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 1px solid #f3f4f6;
    }
    .form-section:last-child { border-bottom: none; }
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
        background: #10b981;
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
    .btn-submit:hover { background: #059669; }
    
    .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }

    /* Photo Upload Grid */
    .photo-capture-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    .photo-upload-box {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 16px 10px;
        text-align: center;
        background: #f9fafb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .camera-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #6b7280;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        margin-top: 8px;
        transition: background 0.2s;
    }
    .camera-btn:hover { background: #4b5563; }
    .camera-btn svg { width: 14px; height: 14px; }
    .photo-preview {
        margin-top: 8px;
        width: 100%;
        border-radius: 6px;
        height: 110px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }
</style>

<div class="view admin-container">
    <div class="page-header-nav">
        <h2 style="font-size: 24px; font-weight: 800; margin: 0;">Edit Listing #<?php echo $car['id']; ?></h2>
        <a href="manage_listings.php" class="back-btn">&larr; Back to Manage Listings</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">

            <!-- Admin Status Control -->
            <div class="form-section" style="background: #eff6ff; padding: 20px; border-radius: 8px; border: 1px solid #bfdbfe;">
                <div class="section-title" style="margin-bottom: 12px;"><span style="background: #3b82f6;">!</span> System Status</div>
                <div class="form-group" style="margin: 0;">
                    <label>Set Listing Visibility</label>
                    <select name="status" class="form-control" style="font-weight: bold; background: white;">
                        <option value="pending" <?php echo $car['status'] === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo $car['status'] === 'approved' ? 'selected' : ''; ?>>Approved (Live on site)</option>
                        <option value="rejected" <?php echo $car['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </div>

            <!-- 1. Select Your Vehicle -->
            <div class="form-section">
                <div class="section-title"><span>1</span> Vehicle Selection</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Car Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($car['brand']); ?>" readonly style="background-color: #e5e7eb; color: #6b7280;">
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

            <!-- 2. Vehicle Specifications -->
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
                            <label class="radio-label"><input type="radio" name="transmission" value="CVT" <?php echo $car['transmission'] === 'CVT' ? 'checked' : ''; ?>> CVT</label>
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

            <!-- 3. Modifications & Upgrades -->
            <div class="form-section">
                <div class="section-title"><span>3</span> Modifications & Upgrades</div>
                <div class="form-group">
                    <label>Modifications List (Edit text directly)</label>
                    <input type="text" name="modifications" class="form-control" value="<?php echo htmlspecialchars($car['modifications']); ?>">
                </div>
            </div>

            <!-- 4. Condition & History -->
            <div class="form-section">
                <div class="section-title"><span>4</span> Condition & History</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>First Owner Vehicle?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="first_owner" value="Yes" <?php echo $car['first_owner'] === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="first_owner" value="No" <?php echo $car['first_owner'] === 'No' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Flooded Vehicle History?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="flooded" value="Yes" <?php echo $car['flooded'] === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="flooded" value="No" <?php echo $car['flooded'] === 'No' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Maintenance History</label>
                        <select name="maintenance_history" class="form-control">
                            <option value="Excellent (Casa)" <?php echo $car['maintenance_history'] === 'Excellent (Casa)' ? 'selected' : ''; ?>>Excellent (Casa)</option>
                            <option value="Excellent" <?php echo $car['maintenance_history'] === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo $car['maintenance_history'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Average" <?php echo $car['maintenance_history'] === 'Average' ? 'selected' : ''; ?>>Average</option>
                            <option value="Poor" <?php echo $car['maintenance_history'] === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Accident History</label>
                        <input type="text" name="accident_history" class="form-control" value="<?php echo htmlspecialchars($car['accident_history']); ?>">
                    </div>
                </div>
            </div>

            <!-- 5. Legal Documents -->
            <div class="form-section">
                <div class="section-title"><span>5</span> Legal Documents & Registration</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>LTO Registration Status</label>
                        <select name="registration_status" class="form-control">
                            <option value="Updated" <?php echo $car['registration_status'] === 'Updated' ? 'selected' : ''; ?>>Updated</option>
                            <option value="Expired" <?php echo $car['registration_status'] === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Original OR / CR On Hand?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="or_cr_ready" value="Yes" <?php echo ($car['or_cr_ready']??'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="or_cr_ready" value="No" <?php echo ($car['or_cr_ready']??'') === 'No' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fully Paid (No Bank Encumbrance)?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="fully_paid" value="Yes" <?php echo ($car['fully_paid']??'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="fully_paid" value="No" <?php echo ($car['fully_paid']??'') === 'No' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deed of Sale Ready?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="deed_of_sale_ready" value="Yes" <?php echo ($car['deed_of_sale_ready']??'Yes') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="deed_of_sale_ready" value="No" <?php echo ($car['deed_of_sale_ready']??'') === 'No' ? 'checked' : ''; ?>> No</label>
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
                        <input type="text" id="display_price" name="asking_price" class="form-control" value="₱ <?php echo number_format((float)$car['asking_price']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Price Negotiability</label>
                        <input type="text" name="price_negotiability" class="form-control" value="<?php echo htmlspecialchars($car['price_negotiability']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Accepted Payment Methods</label>
                        <input type="text" name="payment_methods" class="form-control" value="<?php echo htmlspecialchars($car['payment_methods'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Open to Trade-Ins?</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="trade_in" value="Yes" <?php echo ($car['trade_in']??'') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                            <label class="radio-label"><input type="radio" name="trade_in" value="No" <?php echo ($car['trade_in']??'No') === 'No' ? 'checked' : ''; ?>> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Photos -->
            <div class="form-section">
                <div class="section-title"><span>7</span> Photos</div>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">Current photos are shown below. Select a new image to replace an existing one.</p>
                
                <div class="photo-capture-grid">
                    <?php 
                    $photo_fields_map = [
                        'photo_front' => 'Front View', 
                        'photo_rear' => 'Rear View', 
                        'photo_side' => 'Side Profile', 
                        'photo_engine' => 'Engine Bay', 
                        'photo_interior' => 'Interior', 
                        'photo_dashboard' => 'Dashboard'
                    ];
                    
                    foreach ($photo_fields_map as $key => $label): 
                    ?>
                        <div class="photo-upload-box">
                            <label style="font-weight: 700; font-size: 14px; margin-bottom: 4px;"><?php echo $label; ?></label>
                            
                            <?php if (!empty($car[$key])): ?>
                                <img id="preview_<?php echo $key; ?>" class="photo-preview" src="../uploads/<?php echo htmlspecialchars($car[$key]); ?>" style="display: block;">
                            <?php else: ?>
                                <img id="preview_<?php echo $key; ?>" class="photo-preview" style="display: none; background: #e5e7eb;">
                            <?php endif; ?>
                            
                            <label for="<?php echo $key; ?>" class="camera-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> 
                                Upload Replace
                            </label>
                            
                            <input type="hidden" name="existing_<?php echo $key; ?>" value="<?php echo htmlspecialchars($car[$key] ?? ''); ?>">
                            <input type="file" id="<?php echo $key; ?>" name="<?php echo $key; ?>" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview_<?php echo $key; ?>')">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 8. Seller Information -->
            <div class="form-section">
                <div class="section-title"><span>8</span> Seller Information</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Seller Name</label>
                        <input type="text" name="seller_name" class="form-control" value="<?php echo htmlspecialchars($car['seller_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact Details</label>
                        <input type="text" name="seller_contact" class="form-control" value="<?php echo htmlspecialchars($car['seller_contact'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($car['location'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- 9. Final Touches -->
            <div class="form-section">
                <div class="section-title"><span>9</span> Final Touches</div>
                <div class="form-group">
                    <label>Seller's Notes</label>
                    <textarea name="seller_notes" class="form-control" rows="4"><?php echo htmlspecialchars($car['seller_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Save Changes to Database</button>
        </form>
    </div>
</div>

<script>
// Image Preview Script
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

// Money Formatter Script
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

<?php include 'footer.php'; ?>