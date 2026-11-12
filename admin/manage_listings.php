<?php
session_start();
require_once '../db.php';
$current_page = 'manage_listings';

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

// Handle Search Query
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
    .admin-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* --- New Header & Search Layout --- */
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .header-titles {
        display: flex;
        flex-direction: column;
    }
    .page-title {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        margin: 0;
    }
    .page-subtitle {
        font-size: 15px;
        color: #6b7280;
        margin-top: 4px;
    }
    .search-form {
        display: flex;
        gap: 8px;
    }
    .search-input {
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        width: 260px;
    }
    .search-input:focus {
        outline: none;
        border-color: #5865f2;
        box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1);
    }
    .search-btn {
        background-color: #6366f1; /* Matching the purple/blue in the image */
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .search-btn:hover {
        background-color: #4f46e5;
    }

    /* --- Table Styling --- */
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    th {
        background: #f9fafb;
        padding: 16px 20px;
        font-size: 12px;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e5e7eb;
    }
    td {
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #111827;
        vertical-align: middle;
    }
    tr:last-child td {
        border-bottom: none;
    }
    tr:hover {
        background: #f3f4f6;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    
    .action-group {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: 0.2s;
        text-decoration: none;
    }
    .action-btn:hover {
        background: #e5e7eb;
        color: #111827;
    }
    .action-btn.delete-btn:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .action-btn svg {
        width: 18px;
        height: 18px;
    }

    /* --- Modal Styling --- */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(17, 24, 39, 0.6);
        display: none; 
        justify-content: center;
        align-items: center;
        z-index: 1000;
        padding: 20px;
    }
    .modal-overlay.active {
        display: flex;
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        animation: modalFadeIn 0.2s ease-out;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
        border-radius: 12px 12px 0 0;
    }
    .modal-title {
        font-size: 20px;
        font-weight: 800;
        color: #111827;
    }
    .btn-close {
        background: none; border: none; cursor: pointer; color: #6b7280;
    }
    .btn-close:hover { color: #111827; }
    
    .modal-body {
        padding: 24px;
        overflow-y: auto;
    }
    .detail-section {
        margin-bottom: 24px;
    }
    .detail-section h4 {
        margin: 0 0 12px 0;
        font-size: 15px;
        font-weight: 700;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 6px;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .data-group {
        display: flex;
        flex-direction: column;
    }
    .data-lbl {
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .data-val {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .risk-high { color: #dc2626; font-weight: 700; }
    .risk-med { color: #d97706; font-weight: 700; }
    .risk-low { color: #059669; font-weight: 700; }

    /* Photo Gallery inside Modal */
    .photo-gallery {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 8px;
    }
    .photo-item {
        flex: 0 0 auto;
        width: 150px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .photo-item img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        display: block;
    }
    .photo-label {
        font-size: 11px;
        text-align: center;
        padding: 4px;
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
    }
</style>

<div class="view admin-container">
    
    <!-- Updated Header matching the provided image -->
    <div class="header-section">
        <div class="header-titles">
            <h1 class="page-title">Manage Vehicle Listings</h1>
            <div class="page-subtitle">Review, search, and sort all gathered and user-submitted data.</div>
        </div>
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" class="search-input" placeholder="Search brand, model, status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>
    
    <?php if (isset($msg)): ?>
        <div style="padding: 16px; background: #eff6ff; color: #1e3a8a; border-radius: 8px; margin-bottom: 24px; font-weight: 600;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Price</th>
                    <th>Mileage</th>
                    <th>Seller</th>
                    <th>Status</th>
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
                                <span style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($car['color'] . ' • ' . $car['transmission']); ?></span>
                            </td>
                            <td style="font-weight: 700; color: #10b981;">₱ <?php echo number_format((float)$car['asking_price']); ?></td>
                            <td><?php echo number_format((float)$car['mileage']); ?> km</td>
                            <td><?php echo htmlspecialchars($car['seller_name'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($car['status']); ?>">
                                    <?php echo ucfirst($car['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <!-- View (Eye) Button -->
                                    <button class="action-btn" onclick="openDetailsModal(this)" data-car="<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES, 'UTF-8'); ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Edit (Pencil) Button -->
                                    <a href="edit_listing.php?id=<?php echo $car['id']; ?>" class="action-btn" title="Edit Listing">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>

                                    <!-- Delete (Trash) Button -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete listing #<?php echo $car['id']; ?>? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="listing_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn" style="color: #dc2626;" title="Delete Listing">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: #6b7280;">No listings found matching your search.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================= -->
<!-- DETAILED VIEW MODAL       -->
<!-- ========================= -->
<div id="detailsModal" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        
        <div class="modal-header">
            <div class="modal-title" id="m_title">Vehicle Details</div>
            <button class="btn-close" onclick="document.getElementById('detailsModal').classList.remove('active')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        
        <div class="modal-body">
            
            <!-- Section 1: Specs -->
            <div class="detail-section">
                <h4>Vehicle Specifications</h4>
                <div class="detail-grid">
                    <div class="data-group"><span class="data-lbl">Brand / Model</span><span class="data-val" id="m_vehicle"></span></div>
                    <div class="data-group"><span class="data-lbl">Mileage</span><span class="data-val" id="m_mileage"></span></div>
                    <div class="data-group"><span class="data-lbl">Transmission</span><span class="data-val" id="m_trans"></span></div>
                    <div class="data-group"><span class="data-lbl">Fuel Type</span><span class="data-val" id="m_fuel"></span></div>
                    <div class="data-group"><span class="data-lbl">Color</span><span class="data-val" id="m_color"></span></div>
                    <div class="data-group"><span class="data-lbl">Modifications</span><span class="data-val" id="m_mods"></span></div>
                </div>
            </div>

            <!-- Section 2: Condition & Legal -->
            <div class="detail-section">
                <h4>Condition & Documentation</h4>
                <div class="detail-grid">
                    <div class="data-group"><span class="data-lbl">General Condition</span><span class="data-val" id="m_cond"></span></div>
                    <div class="data-group"><span class="data-lbl">First Owner</span><span class="data-val" id="m_owner"></span></div>
                    <div class="data-group"><span class="data-lbl">Flooded</span><span class="data-val" id="m_flood"></span></div>
                    <div class="data-group"><span class="data-lbl">Accidents</span><span class="data-val" id="m_acc"></span></div>
                    <div class="data-group"><span class="data-lbl">LTO Status</span><span class="data-val" id="m_lto"></span></div>
                    <div class="data-group"><span class="data-lbl">OR/CR Ready</span><span class="data-val" id="m_orcr"></span></div>
                    <div class="data-group"><span class="data-lbl">Fully Paid</span><span class="data-val" id="m_paid"></span></div>
                    <div class="data-group"><span class="data-lbl">Deed of Sale</span><span class="data-val" id="m_deed"></span></div>
                </div>
            </div>

            <!-- Section 3: Pricing & Seller -->
            <div class="detail-section">
                <h4>Pricing & Seller Info</h4>
                <div class="detail-grid">
                    <div class="data-group"><span class="data-lbl">Asking Price</span><span class="data-val" id="m_price" style="color:#10b981; font-weight:700;"></span></div>
                    <div class="data-group"><span class="data-lbl">Negotiability</span><span class="data-val" id="m_nego"></span></div>
                    <div class="data-group"><span class="data-lbl">Payment Methods</span><span class="data-val" id="m_pay"></span></div>
                    <div class="data-group"><span class="data-lbl">Seller Name</span><span class="data-val" id="m_seller"></span></div>
                    <div class="data-group"><span class="data-lbl">Contact Number</span><span class="data-val" id="m_contact"></span></div>
                    <div class="data-group"><span class="data-lbl">Location</span><span class="data-val" id="m_loc"></span></div>
                    <div class="data-group" style="grid-column: span 3;"><span class="data-lbl">Seller Notes</span><span class="data-val" id="m_notes" style="font-style: italic;"></span></div>
                </div>
            </div>

            <!-- Section 4: Risk Assessment -->
            <div class="detail-section" id="risk_section">
                <h4>AI Risk Assessment</h4>
                <div class="detail-grid">
                    <div class="data-group"><span class="data-lbl">Risk Level</span><span class="data-val" id="m_risk_lvl"></span></div>
                    <div class="data-group"><span class="data-lbl">Risk Score</span><span class="data-val" id="m_risk_score"></span></div>
                    <div class="data-group"><span class="data-lbl">Recommendation</span><span class="data-val" id="m_risk_rec"></span></div>
                    <div class="data-group" style="grid-column: span 3;"><span class="data-lbl">Identified Flags</span><span class="data-val" id="m_risk_flags"></span></div>
                </div>
            </div>

            <!-- Section 5: Photos -->
            <div class="detail-section" id="photo_section">
                <h4>Vehicle Photos</h4>
                <div class="photo-gallery" id="m_gallery">
                    <!-- Images injected via JS -->
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const formatMoney = (num) => {
    return "₱ " + parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 0 });
};

function openDetailsModal(button) {
    const carData = button.getAttribute('data-car');
    if(!carData) return;
    
    const car = JSON.parse(carData);

    // 1. Populate Headers & Specs
    document.getElementById('m_title').innerText = "Listing #" + car.id + " Details";
    document.getElementById('m_vehicle').innerText = car.year_manufactured + ' ' + car.brand + ' ' + car.model;
    document.getElementById('m_mileage').innerText = Number(car.mileage).toLocaleString() + ' km';
    document.getElementById('m_trans').innerText = car.transmission || 'N/A';
    document.getElementById('m_fuel').innerText = car.fuel_type || 'N/A';
    document.getElementById('m_color').innerText = car.color || 'N/A';
    document.getElementById('m_mods').innerText = car.modifications || 'Stock / None';

    // 2. Populate Condition & Legal
    document.getElementById('m_cond').innerText = car.maintenance_history || 'N/A';
    document.getElementById('m_owner').innerText = car.first_owner || 'N/A';
    document.getElementById('m_flood').innerText = car.flooded || 'No';
    document.getElementById('m_acc').innerText = car.accident_history || 'None';
    
    document.getElementById('m_lto').innerText = car.registration_status || 'Unknown';
    document.getElementById('m_orcr').innerText = car.or_cr_ready || 'N/A';
    document.getElementById('m_paid').innerText = car.fully_paid || 'N/A';
    document.getElementById('m_deed').innerText = car.deed_of_sale_ready || 'N/A';

    // 3. Populate Pricing & Seller
    document.getElementById('m_price').innerText = formatMoney(car.asking_price);
    document.getElementById('m_nego').innerText = car.price_negotiability || 'N/A';
    document.getElementById('m_pay').innerText = car.payment_methods || 'Cash';
    
    document.getElementById('m_seller').innerText = car.seller_name || 'N/A';
    document.getElementById('m_contact').innerText = car.seller_contact || 'N/A';
    
    // Combine detailed location if available
    let locArray = [];
    if(car.location) locArray.push(car.location);
    if(car.barangay) locArray.push(car.barangay);
    if(car.city) locArray.push(car.city);
    document.getElementById('m_loc').innerText = locArray.length > 0 ? locArray.join(', ') : 'N/A';
    
    document.getElementById('m_notes').innerText = car.seller_notes ? `"${car.seller_notes}"` : 'No additional notes provided.';

    // 4. Populate Risk Assessment (Hide section if no risk data)
    const riskSection = document.getElementById('risk_section');
    if (car.risk_level) {
        riskSection.style.display = 'block';
        let riskColorClass = 'risk-low';
        if(car.risk_level === 'High') riskColorClass = 'risk-high';
        if(car.risk_level === 'Medium') riskColorClass = 'risk-med';
        
        document.getElementById('m_risk_lvl').innerHTML = `<span class="${riskColorClass}">${car.risk_level}</span>`;
        document.getElementById('m_risk_score').innerText = car.risk_score + '/100';
        document.getElementById('m_risk_rec').innerText = car.risk_recommendation || 'N/A';
        document.getElementById('m_risk_flags').innerText = car.risk_flags || 'None detected.';
    } else {
        riskSection.style.display = 'none';
    }

    // 5. Populate Photo Gallery
    const gallery = document.getElementById('m_gallery');
    gallery.innerHTML = ''; 
    
    const photoFields = [
        { key: 'photo_front', label: 'Front View' },
        { key: 'photo_rear', label: 'Rear View' },
        { key: 'photo_side', label: 'Side Profile' },
        { key: 'photo_engine', label: 'Engine Bay' },
        { key: 'photo_interior', label: 'Interior' },
        { key: 'photo_dashboard', label: 'Dashboard' }
    ];

    let hasPhotos = false;
    photoFields.forEach(p => {
        if (car[p.key]) {
            hasPhotos = true;
            gallery.innerHTML += `
                <div class="photo-item">
                    <a href="../uploads/${car[p.key]}" target="_blank">
                        <img src="../uploads/${car[p.key]}" alt="${p.label}" onerror="this.src='https://via.placeholder.com/150?text=Missing'">
                    </a>
                    <div class="photo-label">${p.label}</div>
                </div>
            `;
        }
    });

    if (!hasPhotos) {
        gallery.innerHTML = '<span style="color:#6b7280; font-size: 13px; padding: 12px 0;">No photos uploaded for this listing.</span>';
    }

    // Show Modal
    document.getElementById('detailsModal').classList.add('active');
}

function closeModal(event) {
    if (event.target === document.getElementById('detailsModal')) {
        document.getElementById('detailsModal').classList.remove('active');
    }
}
</script>

<?php include 'footer.php'; ?>