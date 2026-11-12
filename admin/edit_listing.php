<?php
session_start();
require_once '../db.php';
require_once '../risk_engine.php';
$current_page = 'manage_listings';

$success_message = '';
$error_message = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_listings.php");
    exit;
}
$listing_id = (int)$_GET['id'];

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
        ]);

        $success_message = "Listing #{$listing_id} has been successfully updated!";
    } catch (PDOException $e) {
        $error_message = "Error updating listing: " . $e->getMessage();
    }
}

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

<?php include '../footer.php'; ?>