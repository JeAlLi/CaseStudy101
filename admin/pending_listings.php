<?php
session_start();
require_once '../db.php';
$current_page = 'pending_listings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['listing_id'])) {
    $listing_id = (int)$_POST['listing_id'];
    $action = $_POST['action'];

if ($action === 'approve') {
        // Updated query to set BOTH status and verification_status
        $pdo->prepare("UPDATE vehicle_listings SET status = 'approved', verification_status = 'mechanic_verified' WHERE id = ?")->execute([$listing_id]);
        $msg = "Listing #{$listing_id} has been approved and is now mechanic verified.";
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE vehicle_listings SET status = 'rejected' WHERE id = ?")->execute([$listing_id]);
        $msg = "Listing #{$listing_id} has been rejected.";
    }
}


$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status = 'pending' ORDER BY date_added ASC");
$pending_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .admin-container { max-width: 1200px; margin: 0 auto; padding: 24px 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
    .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin: 0; }
    .badge-count { background: var(--coral); color: white; padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 800; }
    
    .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { background: var(--paper); padding: 16px 24px; font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 800; border-bottom: 1px solid var(--border); }
    td { padding: 16px 24px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; color: var(--text); }
    tr:last-child td { border-bottom: none; }
    
    .car-name { font-weight: 800; color: var(--text); margin-bottom: 4px; }
    .car-meta { font-size: 12px; color: var(--text-muted); font-weight: 500; }
    .price-tag { font-family: var(--font-mono); font-weight: 800; color: var(--teal); }
    
    .action-group { display: flex; gap: 12px; }
    .btn-approve { background: var(--teal); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-approve:hover { background: #0D9488; }
    .btn-reject { background: var(--coral-soft); color: var(--coral); border: 1px solid var(--coral); padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-reject:hover { background: var(--coral); color: white; }
    
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state svg { width: 48px; height: 48px; color: var(--teal); margin-bottom: 12px; opacity: 0.5; }
</style>

<div class="view admin-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pending Listings</h1>
            <p style="font-size: 14px; color: var(--text-muted); margin-top: 4px;">Review and approve user-submitted Toyota vehicles before they go live.</p>
        </div>
        <div class="badge-count"><?php echo count($pending_listings); ?> Pending</div>
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="table-card">
        <?php if (empty($pending_listings)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <h3 style="font-family: var(--font-display); font-size: 18px; color: var(--text);">All caught up!</h3>
                <p>There are no pending listings to review right now.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Toyota Vehicle</th>
                        <th>Price</th>
                        <th>Location</th>
                        <th>Date Submitted</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_listings as $car): ?>
                        <tr>
                            <td>
                                <div class="car-name"><?php echo htmlspecialchars($car['year_manufactured'] . ' Toyota ' . $car['model']); ?></div>
                                <div class="car-meta"><?php echo number_format($car['mileage']); ?> km • <?php echo htmlspecialchars($car['color']); ?></div>
                            </td>
                            <td class="price-tag">₱<?php echo number_format($car['asking_price']); ?></td>
                            <td><?php echo htmlspecialchars($car['city'] ?: 'N/A'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($car['date_added'])); ?></td>
                            <td>
                                <div class="action-group" style="justify-content: flex-end;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Reject this listing?');">Reject</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-approve" onclick="return confirm('Approve this listing?');">Approve</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>