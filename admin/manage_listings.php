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
    $stmt = $pdo->prepare("SELECT * FROM vehicle_listings WHERE (brand LIKE ? OR model LIKE ? OR status LIKE ?) AND brand = 'Toyota' ORDER BY date_added DESC");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE brand = 'Toyota' ORDER BY date_added DESC");
}
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .admin-container { max-width: 1280px; margin: 0 auto; padding: 24px 20px; }
    .header-section { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
    .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin: 0; }
    .page-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }
    
    .search-form { display: flex; gap: 12px; }
    .search-input { padding: 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; width: 260px; background: var(--surface); color: var(--text); outline: none; transition: 0.2s; }
    .search-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
    .search-btn { background: var(--blue); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .search-btn:hover { background: var(--blue-dark); }
    
    .table-container { background: var(--surface); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: var(--paper); padding: 16px 24px; font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 800; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
    td { padding: 16px 24px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover { background: var(--paper); }
    
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; display: inline-block; }
    .status-pending { background: var(--amber-soft); color: var(--amber); }
    .status-approved { background: var(--teal-soft); color: var(--teal); }
    .status-rejected { background: var(--coral-soft); color: var(--coral); }
    
    .verify-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; }
    .badge-verified { background: var(--teal-soft); color: var(--teal); border: 1px solid var(--teal); }
    .badge-unverified { background: var(--paper); color: var(--text-muted); border: 1px solid var(--border); }
    
    .action-group { display: flex; justify-content: flex-end; gap: 8px; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px; border-radius: 8px; transition: 0.2s; text-decoration: none; }
    .action-btn:hover { background: var(--paper); color: var(--text); }
    .action-btn.delete-btn:hover { background: var(--coral-soft); color: var(--coral); }
    .action-btn.verify-btn:hover { background: var(--teal-soft); color: var(--teal); }
    .action-btn svg { width: 18px; height: 18px; }
</style>

<div class="view admin-container">
    <div class="header-section">
        <div class="header-titles">
            <h1 class="page-title">Manage Toyota Listings</h1>
            <div class="page-subtitle">Review, verify, and manage all gathered and user-submitted Toyota data.</div>
        </div>
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" class="search-input" placeholder="Search model, status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>
    
    <?php if (isset($msg)): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Toyota Vehicle</th>
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
                                <strong style="color: var(--text);"><?php echo htmlspecialchars($car['year_manufactured'] . ' Toyota ' . $car['model']); ?></strong><br>
                                <span style="font-size: 12.5px; color: var(--text-muted); font-weight: 500;"><?php echo number_format((float)$car['mileage']); ?> km</span>
                            </td>
                            <td style="font-family: var(--font-mono); font-weight: 800; color: var(--teal);">₱<?php echo number_format((float)$car['asking_price']); ?></td>
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
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_verify">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $car['verification_status'] ?? 'unverified'; ?>">
                                        <button type="submit" class="action-btn verify-btn" title="Toggle Mechanic Verification">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                <?php if (($car['verification_status'] ?? '') === 'mechanic_verified'): ?>
                                                    <path d="M9 12l2 2 4-4" stroke="var(--teal)" stroke-width="2.5"/>
                                                <?php endif; ?>
                                            </svg>
                                        </button>
                                    </form>

                                    <a href="edit_listing.php?id=<?php echo $car['id']; ?>" class="action-btn" title="Edit Listing">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>

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
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No Toyota listings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../footer.php'; ?>