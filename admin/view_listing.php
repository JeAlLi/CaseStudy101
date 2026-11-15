<?php
session_start();
require_once '../db.php';
$current_page = 'pending_listings';

// Strict Admin Session Check
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$listing_id) {
    header("Location: pending_listings.php");
    exit;
}

// Handle Approve / Reject actions directly from the view page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'approved', verification_status = 'mechanic_verified' WHERE id = ?");
            $stmt->execute([$listing_id]);
            $_SESSION['flash_msg'] = "Listing #{$listing_id} has been approved and verified.";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$listing_id]);
            $_SESSION['flash_msg'] = "Listing #{$listing_id} has been rejected.";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "System Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    header("Location: pending_listings.php");
    exit;
}

// Fetch the specific listing
$stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo "<div style='padding: 40px; text-align: center; color: white;'>Listing not found or has been removed. <a href='pending_listings.php'>Go back</a></div>";
    exit;
}

include 'header.php';
?>

<style>
    .admin-container { max-width: 1000px; margin: 0 auto; padding: 32px 24px; }
    
    .action-bar { display: flex; justify-content: space-between; align-items: center; background: var(--surface); padding: 16px 24px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 24px; box-shadow: var(--shadow-sm); position: sticky; top: 80px; z-index: 50;}
    .back-btn { display: flex; align-items: center; gap: 8px; color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.2s; }
    .back-btn:hover { color: var(--text); }
    .action-group { display: flex; gap: 12px; }
    
    .btn { padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; border: none; display: flex; align-items: center; gap: 8px; }
    .btn-approve { background: rgba(16, 185, 129, 0.15); color: var(--teal); border: 1px solid var(--teal); }
    .btn-approve:hover { background: var(--teal); color: white; }
    .btn-reject { background: rgba(239, 68, 68, 0.1); color: var(--coral); border: 1px solid var(--coral); }
    .btn-reject:hover { background: var(--coral); color: white; }

    .dossier-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); margin-bottom: 24px; }
    .dossier-header { background: var(--bg-color); padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: flex-start; }
    .dossier-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
    .dossier-subtitle { font-size: 14px; color: var(--text-muted); font-weight: 500; }
    .dossier-price { font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--teal); text-align: right; }
    
    .dossier-body { padding: 32px 24px; }
    .section-block { margin-bottom: 32px; }
    .section-block:last-child { margin-bottom: 0; }
    .section-title { font-size: 13px; font-weight: 800; color: var(--khaki); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px dashed var(--border); }
    
    .data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .data-box { background: var(--paper); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px; }
    .data-box.full { grid-column: 1 / -1; }
    .data-lbl { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; letter-spacing: 0.5px; }
    .data-val { font-size: 14.5px; font-weight: 600; color: var(--text); line-height: 1.4; }
    
    .photo-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .photo-item { position: relative; padding-top: 66%; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: var(--bg-color); }
    .photo-item img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
    .photo-lbl { position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.7); color: white; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; backdrop-filter: blur(4px); }

    @media (max-width: 768px) {
        .photo-gallery { grid-template-columns: repeat(2, 1fr); }
        .dossier-header { flex-direction: column; gap: 16px; }
        .dossier-price { text-align: left; }
    }
</style>

<div class="view admin-container">
    
    <!-- Action Bar -->
    <div class="action-bar">
        <a href="pending_listings.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Queue
        </a>
        <div class="action-group">
            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this listing permanently?');">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-reject">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Reject
                </button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Verify and approve this listing for the live market?');">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-approve">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Approve
                </button>
            </form>
        </div>
    </div>

    <!-- Main Dossier -->
    <div class="dossier-card">
        <div class="dossier-header">
            <div>
                <div class="dossier-title"><?php echo htmlspecialchars($car['year_manufactured'] . ' ' . $car['brand'] . ' ' . $car['model']); ?></div>
                <div class="dossier-subtitle">Listing ID: #<?php echo $car['id']; ?> | Submitted: <?php echo date('F j, Y, g:i a', strtotime($car['date_added'])); ?></div>
            </div>
            <div>
                <div class="dossier-price">₱<?php echo number_format($car['asking_price']); ?></div>
                <div class="dossier-subtitle" style="text-align: right;"><?php echo htmlspecialchars($car['price_negotiability']); ?></div>
            </div>
        </div>

        <div class="dossier-body">
            
            <!-- Vehicle Specs -->
            <div class="section-block">
                <div class="section-title">Vehicle Specifications</div>
                <div class="data-grid">
                    <div class="data-box"><div class="data-lbl">Mileage</div><div class="data-val"><?php echo number_format($car['mileage']); ?> km</div></div>
                    <div class="data-box"><div class="data-lbl">Transmission</div><div class="data-val"><?php echo htmlspecialchars($car['transmission']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Fuel Type</div><div class="data-val"><?php echo htmlspecialchars($car['fuel_type']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Exterior Color</div><div class="data-val"><?php echo htmlspecialchars($car['color']); ?></div></div>
                    <div class="data-box full"><div class="data-lbl">Modifications</div><div class="data-val"><?php echo htmlspecialchars($car['modifications'] ?: 'Stock / None'); ?></div></div>
                </div>
            </div>

            <!-- Condition & History -->
            <div class="section-block">
                <div class="section-title">Condition & History</div>
                <div class="data-grid">
                    <div class="data-box"><div class="data-lbl">First Owner</div><div class="data-val"><?php echo htmlspecialchars($car['first_owner']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Maintenance</div><div class="data-val"><?php echo htmlspecialchars($car['maintenance_history']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Flooded</div><div class="data-val"><?php echo htmlspecialchars($car['flooded']); ?></div></div>
                    <div class="data-box full"><div class="data-lbl">Accident History</div><div class="data-val"><?php echo htmlspecialchars($car['accident_history']); ?></div></div>
                </div>
            </div>

            <!-- Legal Documents -->
            <div class="section-block">
                <div class="section-title">Legal & Documents</div>
                <div class="data-grid">
                    <div class="data-box"><div class="data-lbl">LTO Status</div><div class="data-val"><?php echo htmlspecialchars($car['registration_status']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Original OR/CR</div><div class="data-val"><?php echo htmlspecialchars($car['or_cr_ready']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Fully Paid</div><div class="data-val"><?php echo htmlspecialchars($car['fully_paid']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Deed of Sale</div><div class="data-val"><?php echo htmlspecialchars($car['deed_of_sale_ready']); ?></div></div>
                </div>
            </div>

            <!-- Location & Contact -->
            <div class="section-block">
                <div class="section-title">Location & Contact</div>
                <div class="data-grid">
                    <div class="data-box"><div class="data-lbl">Seller Name</div><div class="data-val"><?php echo htmlspecialchars($car['seller_name']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Contact Numbers</div><div class="data-val"><?php echo htmlspecialchars($car['seller_contact']); ?></div></div>
                    <div class="data-box full"><div class="data-lbl">Exact Location</div><div class="data-val"><?php echo htmlspecialchars($car['location']); ?></div></div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="section-block">
                <div class="section-title">Payment & Notes</div>
                <div class="data-grid">
                    <div class="data-box"><div class="data-lbl">Accepted Payments</div><div class="data-val"><?php echo htmlspecialchars($car['payment_methods']); ?></div></div>
                    <div class="data-box"><div class="data-lbl">Trade-in</div><div class="data-val"><?php echo htmlspecialchars($car['trade_in']); ?></div></div>
                    <div class="data-box full"><div class="data-lbl">Reason for Selling</div><div class="data-val"><?php echo htmlspecialchars($car['reason_for_selling'] ?: 'Not provided'); ?></div></div>
                    <div class="data-box full"><div class="data-lbl">Seller Notes</div><div class="data-val" style="white-space: pre-wrap;"><?php echo htmlspecialchars($car['seller_notes'] ?: 'No additional notes provided.'); ?></div></div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="section-block">
                <div class="section-title">Photo Gallery</div>
                <div class="photo-gallery">
                    <?php 
                    $photos = [
                        'photo_front' => 'Front Angle',
                        'photo_rear' => 'Rear Angle',
                        'photo_side' => 'Side Angle',
                        'photo_engine' => 'Engine Bay',
                        'photo_interior' => 'Interior',
                        'photo_dashboard' => 'Dashboard'
                    ];
                    foreach ($photos as $key => $label): 
                        if (!empty($car[$key])): 
                    ?>
                        <div class="photo-item">
                            <img src="../uploads/<?php echo htmlspecialchars($car[$key]); ?>" alt="<?php echo $label; ?>">
                            <div class="photo-lbl"><?php echo $label; ?></div>
                        </div>
                    <?php 
                        endif; 
                    endforeach; 
                    ?>
                </div>
                <?php if (empty($car['photo_front']) && empty($car['photo_rear'])): ?>
                    <div style="color: var(--text-muted); font-size: 14px; font-style: italic;">No photos uploaded for this vehicle.</div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include '../footer.php'; ?>