<?php
session_start();
require_once '../db.php';

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$current_page = 'pending_listings';

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
        header("Location: pending_listings.php");
        exit;
    }
}

// --- DYNAMIC SEARCH & SORTING ALGORITHM ---

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$allowed_sorts = ['id', 'brand', 'asking_price', 'listing_type'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && $_GET['dir'] === 'ASC' ? 'ASC' : 'DESC';
$next_dir = ($dir === 'ASC') ? 'DESC' : 'ASC';

// Build SQL Query - Strictly locked to 'pending' status
$sql = "SELECT * FROM vehicle_listings WHERE status = 'pending'";
$params = [];

if ($search !== '') {
    // Use AND to keep the pending lock while searching
    $sql .= " AND (brand LIKE ? OR model LIKE ? OR listing_type LIKE ? OR year_manufactured LIKE ?)";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

$sql .= " ORDER BY $sort $dir";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  .admin-panel { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; }
  .panel-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  .search-form { display: flex; gap: 8px; align-items: center; }
  .search-input { padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; width: 250px; outline: none; transition: 0.2s; }
  .search-input:focus { border-color: #5865f2; box-shadow: 0 0 0 3px rgba(88,101,242,0.1); }
  .btn-search { background: #5865f2; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
  .btn-search:hover { background: #4752c4; }
  .btn-clear { background: #f3f4f6; color: #4b5563; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; }
  
  .table-container { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  th { background: #f9fafb; padding: 12px 16px; color: #374151; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
  th a:hover { color: #3b82f6 !important; }
  td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #111827; }
  tr:hover { background: #f9fafb; }
  
  .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
  .badge.pending { background: #fef3c7; color: #d97706; }
  .badge.user { background: #f3e8ff; color: #9333ea; }
  .badge.scraped { background: #e0e7ff; color: #4f46e5; }

  .action-btns { display: flex; gap: 8px; align-items: center; }
  .btn-icon { background: none; border: none; cursor: pointer; padding: 6px; border-radius: 4px; transition: 0.2s; color: #6b7280; display: inline-flex; }
  .btn-icon:hover { background: #e5e7eb; }
  .btn-view:hover { color: #3b82f6; }
  .btn-edit:hover { color: #8b5cf6; }
  .btn-approve { background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
  .btn-approve:hover { background: #059669; }
  .btn-reject { background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
  .btn-reject:hover { background: #dc2626; }

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
            <h2 style="margin:0 0 8px 0; color: #d97706;">Pending Approvals</h2>
            <p style="color: #6b7280; margin: 0;">Review user-submitted listings before they go live on the prediction market.</p>
        </div>
        
        <!-- SEARCH BAR -->
        <form method="GET" action="pending_listings.php" class="search-form">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
            <input type="text" name="search" class="search-input" placeholder="Search pending vehicles..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">Search</button>
            <?php if ($search !== ''): ?>
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
                    <br><span style="font-size:12px; color:#3b82f6;">System Est: ₱<?php echo number_format($car['predicted_value']); ?></span>
                <?php endif; ?>
            </td>
            <td><span class="badge <?php echo $car['listing_type']; ?>"><?php echo htmlspecialchars($car['listing_type']); ?></span></td>
            <td>
              <div class="action-btns">
                <button class="btn-icon btn-view" title="View Details" onclick='openModal(<?php echo json_encode($car, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
                <a href="edit_listing.php?id=<?php echo $car['id']; ?>" class="btn-icon btn-edit" title="Edit Listing">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
                
                <!-- Expanded Approve & Reject buttons for easy clicking -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                    <button type="submit" class="btn-approve">Approve</button>
                </form>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                    <button type="submit" class="btn-reject">Reject</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($listings)): ?>
          <tr><td colspan="5" style="text-align:center; padding: 40px; color: #6b7280;">No pending listings to review. You're all caught up!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Logic (Identical to manage_listings) -->
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

<?php include '../footer.php'; ?>