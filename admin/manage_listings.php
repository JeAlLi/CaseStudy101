<?php
session_start();
require_once '../db.php';
$current_page = 'manage_listings';

// Handle Toggle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_verify' && isset($_POST['listing_id'])) {
    $id = (int)$_POST['listing_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'mechanic_verified') ? 'unverified' : 'mechanic_verified';
    
    $stmt = $pdo->prepare("UPDATE vehicle_listings SET verification_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    $msg = "Listing #{$id} verification changed to " . strtoupper($new_status);
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['listing_id'])) {
    $delete_id = (int)$_POST['listing_id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM vehicle_listings WHERE id = ?");
        $delStmt->execute([$delete_id]);
        $msg = "Listing #{$delete_id} has been successfully deleted.";
    } catch (PDOException $e) {
        $msg = "Error deleting listing. It might be referenced elsewhere.";
    }
}

$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE brand LIKE ? OR model LIKE ? OR status LIKE ? ORDER BY date_added DESC");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query("SELECT * FROM vehicle_listings ORDER BY date_added DESC");
}
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header-titles { display: flex; flex-direction: column; }
    .page-title { font-size: 28px; font-weight: 800; color: #111827; margin: 0; }
    .page-subtitle { font-size: 15px; color: #6b7280; margin-top: 4px; }
    .search-form { display: flex; gap: 8px; }
    .search-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; width: 260px; }
    .search-btn { background-color: #6366f1; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .table-container { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: #f9fafb; padding: 16px 20px; font-size: 12px; text-transform: uppercase; color: #6b7280; font-weight: 700; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
    td { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #111827; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background: #f3f4f6; }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    
    .verify-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
    .badge-verified { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .badge-unverified { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    
    .action-group { display: flex; justify-content: flex-end; gap: 8px; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; background: none; border: none; color: #6b7280; cursor: pointer; padding: 6px; border-radius: 6px; transition: 0.2s; text-decoration: none; }
    .action-btn:hover { background: #e5e7eb; color: #111827; }
    .action-btn.delete-btn:hover { background: #fee2e2; color: #dc2626; }
    .action-btn.verify-btn:hover { background: #dcfce7; color: #166534; }
    .action-btn svg { width: 18px; height: 18px; }
</style>

<div class="view admin-container">
    <div class="header-section">
        <div class="header-titles">
            <h1 class="page-title">Manage Vehicle Listings</h1>
            <div class="page-subtitle">Review, verify, and manage all gathered and user-submitted data.</div>
        </div>
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" class="search-input" placeholder="Search brand, model, status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>
    
    <?php if (isset($msg)): ?>
        <div style="padding: 16px; background: #eff6ff; color: #1e3a8a; border-radius: 8px; margin-bottom: 24px; font-weight: 600;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($listings) > 0): ?>
                    <?php foreach ($listings as $car): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($car['id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($car['year_manufactured'] . ' ' . $car['brand'] . ' ' . $car['model']); ?></strong><br>
                                <span style="font-size: 12px; color: #6b7280;"><?php echo number_format((float)$car['mileage']); ?> km</span>
                            </td>
                            <td style="font-weight: 700; color: #10b981;">₱ <?php echo number_format((float)$car['asking_price']); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($car['status']); ?>"><?php echo ucfirst($car['status']); ?></span></td>
                            <td>
                                <?php if (($car['verification_status'] ?? '') === 'mechanic_verified'): ?>
                                    <span class="verify-badge badge-verified">✓ Verified</span>
                                <?php else: ?>
                                    <span class="verify-badge badge-unverified">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-group">
                                    <!-- Toggle Verify Button -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_verify">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $car['verification_status'] ?? 'unverified'; ?>">
                                        <button type="submit" class="action-btn verify-btn" title="Toggle Mechanic Verification">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                <?php if (($car['verification_status'] ?? '') === 'mechanic_verified'): ?>
                                                    <path d="M9 12l2 2 4-4" stroke="#166534" stroke-width="2.5"/>
                                                <?php endif; ?>
                                            </svg>
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <a href="edit_listing.php?id=<?php echo $car['id']; ?>" class="action-btn" title="Edit Listing">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete listing #<?php echo $car['id']; ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Delete Listing">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 32px; color: #6b7280;">No listings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../footer.php'; ?>