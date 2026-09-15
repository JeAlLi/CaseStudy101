<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE vehicle_listings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header("Location: dashboard.php");
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status = 'pending' ORDER BY date_added DESC");
$pending_listings = $stmt->fetchAll();

include 'header.php';
?>
<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <h3>Data Approval Queue</h3>
    </div>
    <div class="panel-sub">Approved records will immediately update the live public dataset.</div>
    
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Vehicle Model</th>
            <th>Year</th>
            <th>Mileage</th>
            <th>Asking Price</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pending_listings)): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 40px 0;">No pending listings to review.</td>
            </tr>
          <?php else: foreach ($pending_listings as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></strong></td>
              <td class="mono-cell"><?php echo htmlspecialchars($row['year_manufactured']); ?></td>
              <td class="mono-cell"><?php echo number_format($row['mileage']); ?> km</td>
              <td class="mono-cell">
                <?php echo $row['asking_price'] ? '₱' . number_format($row['asking_price']) : 'N/A'; ?>
              </td>
              <td style="text-align: right;">
                <a href="dashboard.php?action=approve&id=<?php echo $row['id']; ?>" class="badge" style="background: var(--teal-soft); color: var(--teal); text-decoration: none;">Approve</a>
                <a href="dashboard.php?action=reject&id=<?php echo $row['id']; ?>" class="badge" style="background: var(--coral-soft); color: var(--coral); text-decoration: none;">Reject</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main></div></body></html>