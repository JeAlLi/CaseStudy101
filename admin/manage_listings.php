<?php
session_start();
require_once '../db.php';

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Handle Secure Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM vehicle_listings WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_listings.php");
    exit;
}

// Fetch all active/rejected records (skip pending, which are in the Queue)
$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status != 'pending' ORDER BY date_added DESC");
$listings = $stmt->fetchAll();

include 'header.php';
?>

<div class="view" style="display: block;">
  <div class="panel">
<div class="panel-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px;">
          <div class="ic" style="background:var(--coral-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--coral)" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></div>
          <h3 style="margin: 0;">Manage Listings</h3>
        </div>
        <div class="panel-sub" style="margin-top: 8px;">View, edit, or permanently remove processed listings to maintain dataset accuracy.</div>
      </div>
      
      <!-- CSV Export Button -->
      <a href="export.php" class="predict-btn" style="text-decoration: none; display: inline-flex; width: auto; padding: 10px 16px; background: var(--teal);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; margin-right: 8px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        Export as CSV
      </a>
    </div>
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Vehicle Model</th>
            <th>Year</th>
            <th>Asking Price</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($listings)): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 40px 0; color: var(--text-muted);">No active listings to manage.</td>
            </tr>
          <?php else: foreach ($listings as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></strong></td>
              <td class="mono-cell"><?php echo htmlspecialchars($row['year_manufactured']); ?></td>
              <td class="mono-cell"><?php echo $row['asking_price'] ? '₱' . number_format($row['asking_price']) : 'N/A'; ?></td>
              <td>
                <span class="badge" style="background: <?php echo $row['status'] === 'approved' ? 'var(--teal-soft)' : 'var(--coral-soft)'; ?>; color: <?php echo $row['status'] === 'approved' ? 'var(--teal)' : 'var(--coral)'; ?>;">
                  <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="edit_listing.php?id=<?php echo $row['id']; ?>" class="badge" style="background: var(--indigo-soft); color: var(--indigo); text-decoration: none; padding: 6px 12px; margin-right: 6px; display:inline-block;">Edit</a>
                <a href="manage_listings.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this listing permanently? This cannot be undone.');" class="badge" style="background: var(--coral-soft); color: var(--coral); text-decoration: none; padding: 6px 12px; display:inline-block;">Delete</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>