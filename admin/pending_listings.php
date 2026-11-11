<?php
session_start();

// Connect to the database in the main folder
require_once 'C:/xampp2/htdocs/CaseStudy101/db.php';
$current_page = 'pending_list';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['listing_id'])) {
    $listing_id = (int)$_POST['listing_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $updateStmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'approved' WHERE id = ?");
        $updateStmt->execute([$listing_id]);
        $msg = "Listing #{$listing_id} has been approved and is now live.";
    } elseif ($action === 'reject') {
        $updateStmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'rejected' WHERE id = ?");
        $updateStmt->execute([$listing_id]);
        $msg = "Listing #{$listing_id} has been rejected.";
    }
}

// Fetch all pending listings
$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status = 'pending' ORDER BY date_added ASC");
$pending_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the header located inside the admin folder
include 'header.php';
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .page-title {
        font-size: 24px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .badge-count {
        background: #ef4444;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
    }
    .listing-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .listing-header {
        background: #f9fafb;
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer; 
        transition: background 0.2s;
    }
    .listing-header:hover {
        background: #f3f4f6; 
    }
    .listing-title {
        font-size: 18px;
        font-weight: 800;
        color: #111827;
    }
    .listing-price-container {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .listing-price {
        font-size: 20px;
        font-weight: 800;
        color: #10b981;
    }
    
    .chevron-icon {
        width: 20px;
        height: 20px;
        color: #6b7280;
        transition: transform 0.3s ease;
    }
    .listing-card.expanded .chevron-icon {
        transform: rotate(180deg);
    }

    .listing-body {
        padding: 24px;
        display: none; 
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
    }
    .listing-actions {
        background: #f9fafb;
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        display: none; 
        justify-content: flex-end;
        gap: 12px;
    }
    .listing-card.expanded .listing-body {
        display: grid;
    }
    .listing-card.expanded .listing-actions {
        display: flex;
    }

    .info-group { margin-bottom: 16px; }
    .info-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .status-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }
    .status-yes { background: #d1fae5; color: #065f46; }
    .status-no { background: #fee2e2; color: #991b1b; }
    
    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: opacity 0.2s;
    }
    .btn:hover { opacity: 0.9; }
    .btn-approve { background: #10b981; color: white; }
    .btn-reject { background: #ef4444; color: white; border: 1px solid #dc2626; }
    
    .section-divider {
        grid-column: span 4;
        height: 1px;
        background: #e5e7eb;
        margin: 8px 0;
    }
    .empty-state {
        text-align: center;
        padding: 48px;
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        color: #6b7280;
    }
    
    /* Photo Gallery Styling */
    .photo-gallery {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 8px;
    }
    .photo-item {
        flex: 0 0 auto;
        width: 160px;
        text-align: center;
    }
    .photo-item img {
        width: 100%;
        height: 110px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #f3f4f6;
    }
    .photo-item-label {
        font-size: 11px;
        color: #6b7280;
        margin-top: 6px;
        font-weight: 600;
    }
</style>

<div class="view admin-container">
    <div class="page-title">
        Review Pending Listings
        <span class="badge-count"><?php echo count($pending_listings); ?> Pending</span>
    </div>

    <?php if (isset($msg)): ?>
        <div style="padding: 16px; background: #eff6ff; color: #1e3a8a; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if (count($pending_listings) === 0): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <h3>All caught up!</h3>
            <p>There are currently no pending vehicle listings to review.</p>
        </div>
    <?php else: ?>
        
        <?php foreach ($pending_listings as $car): ?>
            <div class="listing-card">
                <div class="listing-header" onclick="this.parentElement.classList.toggle('expanded')">
                    <div>
                        <span style="font-size: 13px; color: #6b7280; font-weight: 600;">LISTING #<?php echo htmlspecialchars($car['id'] ?? 'N/A'); ?></span>
                        <div class="listing-title"><?php echo htmlspecialchars(($car['year_manufactured'] ?? 'N/A') . ' ' . ($car['brand'] ?? 'N/A') . ' ' . ($car['model'] ?? 'N/A')); ?></div>
                    </div>
                    <div class="listing-price-container">
                        <div class="listing-price">₱ <?php echo number_format((float)($car['asking_price'] ?? 0)); ?></div>
                        <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>

                <div class="listing-body">
                    <!-- Core Specs -->
                    <div class="info-group">
                        <div class="info-label">Mileage</div>
                        <div class="info-value"><?php echo number_format((float)($car['mileage'] ?? 0)); ?> km</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Transmission / Fuel</div>
                        <div class="info-value"><?php echo htmlspecialchars(($car['transmission'] ?? 'N/A') . ' / ' . ($car['fuel_type'] ?? 'N/A')); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Color</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['color'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Modifications</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['modifications'] ?? 'N/A'); ?></div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Condition & History -->
                    <div class="info-group">
                        <div class="info-label">Condition / History</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['maintenance_history'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Accidents</div>
                        <div class="info-value">
                            <?php if(($car['accident_history'] ?? 'None') === 'None'): ?>
                                <span class="status-badge status-yes">None</span>
                            <?php else: ?>
                                <span class="status-badge status-no"><?php echo htmlspecialchars($car['accident_history'] ?? 'N/A'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">First Owner</div>
                        <div class="info-value"><?php echo ($car['first_owner'] ?? 'No') === 'Yes' ? '<span class="status-badge status-yes">Yes</span>' : 'No'; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Flooded</div>
                        <div class="info-value"><?php echo ($car['flooded'] ?? 'No') === 'Yes' ? '<span class="status-badge status-no">Yes</span>' : '<span class="status-badge status-yes">No</span>'; ?></div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Legal Documents -->
                    <div class="info-group">
                        <div class="info-label">LTO Status</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['registration_status'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">OR/CR Ready</div>
                        <div class="info-value"><?php echo ($car['or_cr_ready'] ?? 'Yes') === 'Yes' ? '<span class="status-badge status-yes">Yes</span>' : '<span class="status-badge status-no">No</span>'; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Fully Paid</div>
                        <div class="info-value"><?php echo ($car['fully_paid'] ?? 'Yes') === 'Yes' ? '<span class="status-badge status-yes">Yes</span>' : '<span class="status-badge status-no">No</span>'; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Deed of Sale</div>
                        <div class="info-value"><?php echo ($car['deed_of_sale_ready'] ?? 'Yes') === 'Yes' ? '<span class="status-badge status-yes">Ready</span>' : '<span class="status-badge status-no">Pending</span>'; ?></div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Financial & Seller Info -->
                    <div class="info-group">
                        <div class="info-label">Payment Methods</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['payment_methods'] ?? 'Cash'); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Negotiable / Trade-in</div>
                        <div class="info-value"><?php echo htmlspecialchars($car['price_negotiability'] ?? 'N/A'); ?> | Trade-in: <?php echo htmlspecialchars($car['trade_in'] ?? 'No'); ?></div>
                    </div>
                    <div class="info-group" style="grid-column: span 2;">
                        <div class="info-label">Seller Contact & Location</div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($car['seller_name'] ?? 'Not provided'); ?></strong> <br>
                            <?php echo htmlspecialchars($car['seller_contact'] ?? 'N/A'); ?> <br>
                            <?php echo htmlspecialchars($car['location'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <!-- Image Gallery Section -->
                    <div class="info-group" style="grid-column: span 4;">
                        <div class="info-label">Vehicle Photos</div>
                        <div class="photo-gallery">
                            <?php 
                            // Define the expected photo fields and their display labels
                            $photo_fields = [
                                'photo_front' => 'Front View',
                                'photo_rear' => 'Rear View',
                                'photo_side' => 'Side Profile',
                                'photo_engine' => 'Engine Bay',
                                'photo_interior' => 'Interior',
                                'photo_dashboard' => 'Dashboard'
                            ];
                            
                            $has_photos = false;
                            
                            foreach ($photo_fields as $field => $label) {
                                // If the database column exists and isn't empty
                                if (!empty($car[$field])) {
                                    $has_photos = true;
                                    // Make sure you created an 'uploads' folder in your main CaseStudy101 directory
                                    $image_path = htmlspecialchars('../uploads/' . $car[$field]);
                                    
                                    echo "
                                    <div class='photo-item'>
                                        <a href='{$image_path}' target='_blank'>
                                            <img src='{$image_path}' alt='{$label}'>
                                        </a>
                                        <div class='photo-item-label'>{$label}</div>
                                    </div>";
                                }
                            }
                            
                            if (!$has_photos) {
                                echo "<div class='info-value' style='color: #9ca3af; font-style: italic;'>No photos uploaded for this listing.</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <?php if(!empty($car['seller_notes'])): ?>
                    <div class="info-group" style="grid-column: span 4; background: #f9fafb; padding: 12px; border-radius: 8px;">
                        <div class="info-label">Seller Notes</div>
                        <div class="info-value" style="font-style: italic;">"<?php echo htmlspecialchars($car['seller_notes']); ?>"</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="listing-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-reject" onclick="event.stopPropagation(); return confirm('Are you sure you want to reject this listing?');">Reject Listing</button>
                    </form>
                    
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-approve" onclick="event.stopPropagation(); return confirm('Approve this listing? It will immediately appear in the dataset.');">Approve Listing</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php 

?>