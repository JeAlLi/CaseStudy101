<?php
session_start();
require_once '../db.php';
$current_page = 'pending_listings';

// Strict Admin Session Check
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Handle Form Submissions via Post-Redirect-Get pattern to prevent double-submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['listing_id'])) {
    $listing_id = (int)$_POST['listing_id'];
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
    
    // Redirect to clear the POST request
    header("Location: pending_listings.php");
    exit;
}

// Retrieve flash messages
$msg = $_SESSION['flash_msg'] ?? null;
$msg_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Fetch Pending Data
$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status = 'pending' ORDER BY date_added ASC");
$pending_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .admin-container { max-width: 1280px; margin: 0 auto; padding: 32px 24px; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin: 0; }
    .badge-count { background: var(--amber); color: #000; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }
    
    /* Search Bar */
    .controls-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: var(--surface); padding: 16px 24px; border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
    .search-input { background: var(--bg-color); border: 1px solid var(--border); color: var(--text); padding: 10px 16px; border-radius: 8px; width: 100%; max-width: 400px; outline: none; transition: 0.2s; font-size: 14px; }
    .search-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
    
    .table-wrapper { overflow-x: auto; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
    th { background: rgba(0,0,0,0.2); padding: 16px 24px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 800; border-bottom: 1px solid var(--border); }
    td { padding: 16px 24px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; color: var(--text); transition: background 0.2s; }
    tr:last-child td { border-bottom: none; }
    tr.table-row:hover td { background: rgba(255,255,255,0.03); }
    
    .car-name { font-weight: 800; color: var(--text); margin-bottom: 4px; font-size: 15px; }
    .car-meta { font-size: 12px; color: var(--text-muted); font-weight: 500; }
    .price-tag { font-family: var(--font-display); font-weight: 800; color: var(--blue); font-size: 15px; }
    
    .action-group { display: flex; gap: 8px; justify-content: flex-end; }
    .btn { display: inline-flex; align-items: center; justify-content: center; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; text-transform: uppercase; text-decoration: none; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-view { background: var(--surface-hover, #363b2f); color: var(--text); border: 1px solid var(--border); }
    .btn-view:hover { background: var(--border); }
    .btn-approve { background: rgba(16, 185, 129, 0.15); color: var(--teal); border: 1px solid var(--teal); }
    .btn-approve:hover:not(:disabled) { background: var(--teal); color: white; }
    .btn-reject { background: rgba(239, 68, 68, 0.1); color: var(--coral); border: 1px solid var(--coral); }
    .btn-reject:hover:not(:disabled) { background: var(--coral); color: white; }
    
    .empty-state { text-align: center; padding: 80px 20px; color: var(--text-muted); }
    .empty-state svg { width: 48px; height: 48px; color: var(--teal); margin-bottom: 16px; opacity: 0.8; }
    
    .alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; }
    .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ff8793; }

    /* Quick View Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 20px; opacity: 0; transition: opacity 0.3s; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-content { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.5); position: relative; transform: translateY(20px); transition: transform 0.3s; }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { font-family: var(--font-display); font-size: 20px; font-weight: 800; margin: 0; }
    .modal-close { background: transparent; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; transition: 0.2s; }
    .modal-close:hover { color: var(--coral); }
    .modal-body { padding: 24px; }
    .modal-image { width: 100%; height: 250px; border-radius: 8px; object-fit: cover; background: var(--paper); border: 1px solid var(--border); margin-bottom: 20px; }
    .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .info-box { background: var(--paper); padding: 12px; border-radius: 8px; border: 1px solid var(--border); }
    .info-box label { display: block; font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; }
    .info-box span { font-weight: 600; font-size: 14px; }
</style>

<div class="view admin-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pending Listings</h1>
            <p style="font-size: 14px; color: var(--text-muted); margin-top: 6px;">Review and approve user-submitted Toyota vehicles before they are deployed to the live market.</p>
        </div>
        <div class="badge-count" id="count-badge"><?php echo count($pending_listings); ?> Awaiting Review</div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>" id="flash-alert">
            <span><?php echo htmlspecialchars($msg); ?></span>
            <button style="background:transparent; border:none; color:inherit; font-weight:bold; cursor:pointer;" onclick="document.getElementById('flash-alert').style.display='none';">×</button>
        </div>
    <?php endif; ?>

    <div class="controls-row">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by model, location, or seller...">
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Live Filtering
        </div>
    </div>

    <div class="table-wrapper">
        <?php if (empty($pending_listings)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <h3 style="font-family: var(--font-display); font-size: 20px; color: var(--text); margin-bottom: 8px;">Queue is Clear</h3>
                <p>There are no pending listings requiring mechanic verification at this time.</p>
            </div>
        <?php else: ?>
            <table id="listingsTable">
                <thead>
                    <tr>
                        <th>Vehicle Target</th>
                        <th>Asking Price</th>
                        <th>Seller Details</th>
                        <th>Date</th>
                        <th style="text-align: right;">Tactical Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_listings as $car): 
                        $car_name = $car['year_manufactured'] . ' ' . $car['brand'] . ' ' . $car['model'];
                        $img_src = !empty($car['photo_front']) ? '../uploads/' . htmlspecialchars($car['photo_front']) : '../assets/placeholder.jpg';
                    ?>
                        <tr class="table-row">
                            <td>
                                <div class="car-name search-target"><?php echo htmlspecialchars($car_name); ?></div>
                                <div class="car-meta"><?php echo number_format($car['mileage']); ?> km • <?php echo htmlspecialchars($car['transmission']); ?> • <?php echo htmlspecialchars($car['color']); ?></div>
                            </td>
                            <td class="price-tag search-target">₱<?php echo number_format($car['asking_price']); ?></td>
                            <td>
                                <div style="color: var(--text); font-weight: 600; font-size: 13px;" class="search-target"><?php echo htmlspecialchars($car['seller_name']); ?></div>
                                <div class="car-meta search-target"><?php echo htmlspecialchars($car['city']); ?></div>
                            </td>
                            <td style="font-size: 13px; font-weight: 500; color: var(--text-muted);">
                                <?php echo date('M j, Y', strtotime($car['date_added'])); ?>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button type="button" class="btn btn-view" onclick="openModal(
                                        '<?php echo addslashes($car_name); ?>', 
                                        '₱<?php echo number_format($car['asking_price']); ?>', 
                                        '<?php echo number_format($car['mileage']); ?> km', 
                                        '<?php echo addslashes($car['transmission']); ?>', 
                                        '<?php echo addslashes($car['fuel_type']); ?>', 
                                        '<?php echo addslashes($car['seller_name']); ?>', 
                                        '<?php echo addslashes($car['seller_contact']); ?>', 
                                        '<?php echo addslashes($car['city'] . ', ' . $car['barangay']); ?>', 
                                        '<?php echo $img_src; ?>',
                                        <?php echo $car['id']; ?>
                                    )">Quick View</button>
                                    
                                    <form method="POST" style="display:inline;" onsubmit="return handleFormSubmit(this, 'Rejecting...')">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Confirm rejection of Listing #<?php echo $car['id']; ?>?');">Reject</button>
                                    </form>
                                    
                                    <form method="POST" style="display:inline;" onsubmit="return handleFormSubmit(this, 'Approving...')">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Verify and approve Listing #<?php echo $car['id']; ?> for the live market?');">Approve</button>
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

<!-- Quick View Modal -->
<div class="modal-overlay" id="quickViewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Vehicle Details</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <img src="" id="modalImg" class="modal-image" alt="Vehicle Photo">
            <div class="modal-grid">
                <div class="info-box"><label>Asking Price</label><span id="modalPrice" style="color: var(--teal); font-size:18px;"></span></div>
                <div class="info-box"><label>Mileage</label><span id="modalMileage"></span></div>
                <div class="info-box"><label>Transmission</label><span id="modalTrans"></span></div>
                <div class="info-box"><label>Fuel Type</label><span id="modalFuel"></span></div>
                <div class="info-box"><label>Seller Name</label><span id="modalSeller"></span></div>
                <div class="info-box"><label>Contact</label><span id="modalContact" style="color: var(--blue);"></span></div>
                <div class="info-box" style="grid-column: 1 / -1;"><label>Location</label><span id="modalLocation"></span></div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px;">
                <!-- Updated link to view_listing.php instead of edit_listing.php -->
                <a href="#" id="modalFullView" class="btn btn-view" style="flex:1;">View Full Specifications</a>
                <button class="btn btn-approve" style="flex:1;" onclick="triggerApprove()">Approve Instantly</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Live Search Filter
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.table-row');
    const countBadge = document.getElementById('count-badge');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const textContent = row.innerText.toLowerCase();
                if (textContent.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            countBadge.textContent = visibleCount + ' Awaiting Review';
        });
    }

    // Button Loading State Prevent Double Submission
    function handleFormSubmit(form, loadingText) {
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; margin-right:6px; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 2v4"></path></svg> ${loadingText}`;
        return true;
    }

    // Modal Logic
    const modal = document.getElementById('quickViewModal');
    let currentListingId = null;

    function openModal(name, price, mileage, trans, fuel, seller, contact, loc, img, id) {
        document.getElementById('modalTitle').textContent = name;
        document.getElementById('modalPrice').textContent = price;
        document.getElementById('modalMileage').textContent = mileage;
        document.getElementById('modalTrans').textContent = trans;
        document.getElementById('modalFuel').textContent = fuel;
        document.getElementById('modalSeller').textContent = seller;
        document.getElementById('modalContact').textContent = contact;
        document.getElementById('modalLocation').textContent = loc;
        document.getElementById('modalImg').src = img;
        
        // Updated target to view_listing.php
        document.getElementById('modalFullView').href = "view_listing.php?id=" + id;
        currentListingId = id;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        currentListingId = null;
    }

    function triggerApprove() {
        if (!currentListingId) return;
        if (confirm('Are you sure you want to approve this listing from the Quick View?')) {
            // Find the specific form for this ID and submit it
            const forms = document.querySelectorAll(`form input[value="${currentListingId}"]`);
            forms.forEach(input => {
                if(input.nextElementSibling.value === 'approve') {
                    input.parentElement.querySelector('button').click();
                }
            });
        }
    }

    // Close modal on outside click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // Add keyframes for spinner dynamically
    const style = document.createElement('style');
    style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
    document.head.appendChild(style);
</script>

<?php include '../footer.php'; ?>