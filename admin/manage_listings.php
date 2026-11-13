<?php
session_start();
require_once '../db.php';

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$current_page = 'manage_listings';

<<<<<<< HEAD
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
=======
// Handle Actions (Approve, Reject, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['listing_id'])) {
        $action = $_POST['action'];
        $id = (int)$_POST['listing_id'];
        
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE vehicle_listings SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM vehicle_listings WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: manage_listings.php");
        exit;
    }
}

// --- DYNAMIC SEARCH & SORTING ALGORITHM ---

// 1. Capture Search Query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. Capture Sort Parameters
$allowed_sorts = ['id', 'brand', 'asking_price', 'listing_type', 'status'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && $_GET['dir'] === 'ASC' ? 'ASC' : 'DESC';
$next_dir = ($dir === 'ASC') ? 'DESC' : 'ASC';

// 3. Build SQL Query Dynamically
$sql = "SELECT * FROM vehicle_listings";
$params = [];

// If user is searching, append WHERE clause
if ($search !== '') {
    $sql .= " WHERE brand LIKE ? OR model LIKE ? OR status LIKE ? OR listing_type LIKE ? OR year_manufactured LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param];
>>>>>>> parent of 3985f8d (posh)
}

// Append Sort clause
$sql .= " ORDER BY $sort $dir";

// 4. Execute the Query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to build sortable header links that preserve the search query
function sortLink($column, $label, $current_sort, $current_dir, $next_dir, $search) {
    $icon = '';
    if ($current_sort === $column) {
        $icon = $current_dir === 'ASC' ? ' <span style="font-size:10px;">▲</span>' : ' <span style="font-size:10px;">▼</span>';
    }
    $search_param = $search !== '' ? '&search=' . urlencode($search) : '';
    $url = "?sort={$column}&dir=" . ($current_sort === $column ? $next_dir : 'ASC') . $search_param;
    return "<a href='{$url}' style='color:inherit; text-decoration:none; display:flex; align-items:center; gap:4px;'>{$label}{$icon}</a>";
}

include 'header.php';
?>

<style>
<<<<<<< HEAD
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
=======
  .admin-panel { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; }
  
  /* Search Bar Styling */
  .panel-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  .search-form { display: flex; gap: 8px; align-items: center; }
  .search-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; width: 250px; outline: none; transition: border-color 0.2s; }
  .search-input:focus { border-color: #5865f2; box-shadow: 0 0 0 3px rgba(88,101,242,0.1); }
  .btn-search { background: #5865f2; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
  .btn-search:hover { background: #4752c4; }
  .btn-clear { background: #f3f4f6; color: #4b5563; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; transition: background 0.2s; }
  .btn-clear:hover { background: #e5e7eb; }

  .table-container { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  th { background: #f9fafb; padding: 12px 16px; color: #374151; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
  th a:hover { color: #3b82f6 !important; }
  td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; }
  tr:hover { background: #f9fafb; }
  
  .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
  .badge.pending { background: #fef3c7; color: #d97706; }
  .badge.approved { background: #d1fae5; color: #059669; }
  .badge.rejected { background: #fee2e2; color: #dc2626; }
  .badge.scraped { background: #e0e7ff; color: #4f46e5; }
  .badge.user { background: #f3e8ff; color: #9333ea; }

  .action-btns { display: flex; gap: 8px; align-items: center; }
  .btn-icon { background: none; border: none; cursor: pointer; padding: 6px; border-radius: 4px; transition: 0.2s; color: #6b7280; display: inline-flex; }
  .btn-icon:hover { background: #e5e7eb; }
  .btn-view:hover { color: #3b82f6; }
  .btn-edit:hover { color: #8b5cf6; }
  .btn-approve:hover { color: #10b981; }
  .btn-reject:hover { color: #f59e0b; }
  .btn-delete:hover { color: #ef4444; }

  /* Modal Styling */
  .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.7); z-index: 1000; justify-content: center; align-items: center; }
  .modal-overlay.active { display: flex; }
  .modal-content { background: #fff; width: 100%; max-width: 600px; border-radius: 12px; padding: 24px; position: relative; max-height: 90vh; overflow-y: auto; }
  .modal-close { position: absolute; top: 16px; right: 16px; cursor: pointer; color: #6b7280; background: none; border: none; font-size: 20px; }
  .modal-title { font-size: 20px; font-weight: 700; margin-bottom: 16px; color: #111827; }
  .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
  .data-group { background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; }
  .data-lbl { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
  .data-val { font-size: 14px; font-weight: 600; color: #111827; }
  .price-highlight { background: #eff6ff; border-color: #bfdbfe; }
  .price-highlight .data-val { color: #1d4ed8; font-size: 16px; }
</style>

<div class="view" style="display: block; width: 100%; max-width: none;">
  <div class="admin-panel">
    
    <div class="panel-header-row">
        <div>
            <h2 style="margin:0 0 8px 0;">Manage Vehicle Listings</h2>
            <p style="color: #6b7280; margin: 0;">Review, search, and sort all gathered and user-submitted data.</p>
        </div>
        
        <!-- SEARCH BAR -->
        <form method="GET" action="manage_listings.php" class="search-form">
            <!-- Preserve current sorting when searching -->
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
            
            <input type="text" name="search" class="search-input" placeholder="Search brand, model, status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">Search</button>
            <?php if ($search !== ''): ?>
                <!-- Clear button removes search but keeps sorting intact -->
                <a href="?sort=<?php echo $sort; ?>&dir=<?php echo $dir; ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><?php echo sortLink('id', 'ID', $sort, $dir, $next_dir, $search); ?></th>
            <th><?php echo sortLink('brand', 'Vehicle', $sort, $dir, $next_dir, $search); ?></th>
            <th><?php echo sortLink('asking_price', 'Asking Price', $sort, $dir, $next_dir, $search); ?></th>
            <th><?php echo sortLink('listing_type', 'Source', $sort, $dir, $next_dir, $search); ?></th>
            <th><?php echo sortLink('status', 'Status', $sort, $dir, $next_dir, $search); ?></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listings as $car): ?>
          <tr>
            <td>#<?php echo $car['id']; ?></td>
            <td>
                <b><?php echo htmlspecialchars($car['year_manufactured'] . ' ' . $car['brand'] . ' ' . $car['model']); ?></b><br>
                <span style="font-size:12px; color:#6b7280;"><?php echo number_format($car['mileage']); ?> km • <?php echo htmlspecialchars($car['transmission']); ?></span>
            </td>
            <td>
                <b>₱<?php echo number_format($car['asking_price']); ?></b>
                <?php if($car['predicted_value'] > 0): ?>
                    <br><span style="font-size:12px; color:#3b82f6;">Est: ₱<?php echo number_format($car['predicted_value']); ?></span>
>>>>>>> parent of 3985f8d (posh)
                <?php endif; ?>
            </td>
            <td><span class="badge <?php echo $car['listing_type']; ?>"><?php echo htmlspecialchars($car['listing_type']); ?></span></td>
            <td><span class="badge <?php echo $car['status']; ?>"><?php echo htmlspecialchars($car['status']); ?></span></td>
            <td>
              <div class="action-btns">
                <!-- VIEW BUTTON -->
                <button class="btn-icon btn-view" title="View Details" onclick='openModal(<?php echo json_encode($car, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>

                <!-- EDIT BUTTON -->
                <a href="edit_listing.php?id=<?php echo $car['id']; ?>" class="btn-icon btn-edit" title="Edit Listing">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
                
                <!-- APPROVE BUTTON -->
                <?php if($car['status'] !== 'approved'): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                    <button type="submit" class="btn-icon btn-approve" title="Approve"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></button>
                </form>
                <?php endif; ?>

                <!-- REJECT BUTTON -->
                <?php if($car['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                    <button type="submit" class="btn-icon btn-reject" title="Reject"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button>
                </form>
                <?php endif; ?>

                <!-- DELETE BUTTON -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this listing?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                    <button type="submit" class="btn-icon btn-delete" title="Delete"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($listings)): ?>
          <tr><td colspan="6" style="text-align:center; padding: 40px; color: #6b7280;">No listings match your search.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<<<<<<< HEAD
=======
<!-- FULL DATA MODAL -->
<div class="modal-overlay" id="dataModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-title" id="m_title">Vehicle Details</div>
        <div class="data-grid">
            <div class="data-group price-highlight"><div class="data-lbl">Asking Price</div><div class="data-val" id="m_asking"></div></div>
            <div class="data-group price-highlight"><div class="data-lbl">System Prediction</div><div class="data-val" id="m_predicted"></div></div>
            <div class="data-group"><div class="data-lbl">Brand</div><div class="data-val" id="m_brand"></div></div>
            <div class="data-group"><div class="data-lbl">Model</div><div class="data-val" id="m_model"></div></div>
            <div class="data-group"><div class="data-lbl">Year</div><div class="data-val" id="m_year"></div></div>
            <div class="data-group"><div class="data-lbl">Mileage</div><div class="data-val" id="m_mileage"></div></div>
            <div class="data-group"><div class="data-lbl">Transmission</div><div class="data-val" id="m_trans"></div></div>
            <div class="data-group"><div class="data-lbl">Fuel Type</div><div class="data-val" id="m_fuel"></div></div>
            <div class="data-group"><div class="data-lbl">Color</div><div class="data-val" id="m_color"></div></div>
            <div class="data-group"><div class="data-lbl">Location</div><div class="data-val" id="m_location"></div></div>
        </div>
        <h4 style="margin: 0 0 12px 0; color: #374151;">Condition & History</h4>
        <div class="data-grid">
            <div class="data-group"><div class="data-lbl">Maintenance</div><div class="data-val" id="m_maint"></div></div>
            <div class="data-group"><div class="data-lbl">Accidents</div><div class="data-val" id="m_acc"></div></div>
            <div class="data-group"><div class="data-lbl">Registration</div><div class="data-val" id="m_reg"></div></div>
            <div class="data-group"><div class="data-lbl">Modifications</div><div class="data-val" id="m_mods"></div></div>
        </div>
        <h4 style="margin: 0 0 12px 0; color: #374151;">Listing Info</h4>
        <div class="data-grid">
            <div class="data-group"><div class="data-lbl">Source</div><div class="data-val" id="m_type"></div></div>
            <div class="data-group"><div class="data-lbl">Seller Name</div><div class="data-val" id="m_seller"></div></div>
            <div class="data-group" style="grid-column: span 2;"><div class="data-lbl">Contact / URL</div><div class="data-val" id="m_contact" style="word-wrap: break-word;"></div></div>
        </div>
    </div>
</div>

<script>
function formatMoney(num) { if (!num || num == 0) return 'N/A'; return '₱' + Number(num).toLocaleString(); }

function openModal(car) {
    document.getElementById('m_title').innerText = car.year_manufactured + ' ' + car.brand + ' ' + car.model;
    document.getElementById('m_asking').innerText = formatMoney(car.asking_price);
    document.getElementById('m_predicted').innerText = formatMoney(car.predicted_value);
    document.getElementById('m_brand').innerText = car.brand;
    document.getElementById('m_model').innerText = car.model;
    document.getElementById('m_year').innerText = car.year_manufactured;
    document.getElementById('m_mileage').innerText = Number(car.mileage).toLocaleString() + ' km';
    document.getElementById('m_trans').innerText = car.transmission || 'N/A';
    document.getElementById('m_fuel').innerText = car.fuel_type || 'N/A';
    document.getElementById('m_color').innerText = car.color || 'N/A';
    document.getElementById('m_location').innerText = car.location || 'N/A';
    document.getElementById('m_maint').innerText = car.maintenance_history || 'N/A';
    document.getElementById('m_acc').innerText = car.accident_history || 'N/A';
    document.getElementById('m_reg').innerText = car.registration_status || 'N/A';
    document.getElementById('m_mods').innerText = car.modifications || 'N/A';
    document.getElementById('m_type').innerText = car.listing_type.toUpperCase();
    document.getElementById('m_seller').innerText = car.seller_name || 'N/A';
    document.getElementById('m_contact').innerText = car.seller_contact || 'N/A';
    document.getElementById('dataModal').classList.add('active');
}

function closeModal() { document.getElementById('dataModal').classList.remove('active'); }
window.onclick = function(event) { if (event.target == document.getElementById('dataModal')) closeModal(); }
</script>

>>>>>>> parent of 3985f8d (posh)
<?php include '../footer.php'; ?>