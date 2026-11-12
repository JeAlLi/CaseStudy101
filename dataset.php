<?php
require_once 'db.php';
$current_page = 'dataset';

// Capture Filter Inputs
$f_brand    = 'Toyota'; // Locked to Toyota
$f_model    = trim($_GET['model'] ?? '');
$f_min      = trim($_GET['min_price'] ?? '');
$f_max      = trim($_GET['max_price'] ?? '');
$f_verified = trim($_GET['verified'] ?? 'all'); // NEW
$active_tab = $_GET['tab'] ?? 'tab-marketplace';

// Build the Dynamic SQL WHERE Clauses
$whereMarket = ["status = 'approved'", "listing_type = 'scraped'", "brand = 'Toyota'"];
$whereUser = ["status = 'approved'", "listing_type = 'user_sale'", "brand = 'Toyota'"];
$whereSRP = ["vehicle_type = 'Car'", "brand = 'Toyota'"];
$params = [];
$paramsSRP = [];

if ($f_model !== '') {
    $whereMarket[] = "model LIKE ?";
    $whereUser[] = "model LIKE ?";
    $whereSRP[] = "model LIKE ?";
    $params[] = "%" . $f_model . "%";
    $paramsSRP[] = "%" . $f_model . "%";
}
if ($f_min !== '' && is_numeric($f_min)) {
    $whereMarket[] = "asking_price >= ?";
    $whereUser[] = "asking_price >= ?";
    $whereSRP[] = "base_price >= ?";
    $params[] = (float)$f_min;
    $paramsSRP[] = (float)$f_min;
}
if ($f_max !== '' && is_numeric($f_max)) {
    $whereMarket[] = "asking_price <= ?";
    $whereUser[] = "asking_price <= ?";
    $whereSRP[] = "base_price <= ?";
    $params[] = (float)$f_max;
    $paramsSRP[] = (float)$f_max;
}

// NEW: Verification Filter for User Sales
if ($f_verified === 'verified') { 
    $whereUser[] = "verification_status = 'mechanic_verified'"; 
}
if ($f_verified === 'unverified') { 
    $whereUser[] = "verification_status = 'unverified'"; 
}

// Convert arrays to strings for the SQL query
$sqlWhereMarket = implode(' AND ', $whereMarket);
$sqlWhereUser = implode(' AND ', $whereUser);
$sqlWhereSRP = implode(' AND ', $whereSRP);

// Fetch Data with Applied Filters
$stmtSRP = $pdo->prepare("SELECT * FROM reference_weights WHERE $sqlWhereSRP ORDER BY model");
$stmtSRP->execute($paramsSRP);
$srp_catalog = $stmtSRP->fetchAll();

$stmtMarket = $pdo->prepare("SELECT * FROM vehicle_listings WHERE $sqlWhereMarket ORDER BY date_added DESC");
$stmtMarket->execute($params);
$market_data = $stmtMarket->fetchAll();

$stmtForSale = $pdo->prepare("SELECT * FROM vehicle_listings WHERE $sqlWhereUser ORDER BY date_added DESC");
$stmtForSale->execute($params);
$cars_for_sale = $stmtForSale->fetchAll();

include 'header.php';
?>

<style>
    .tabs-container { 
        display: flex; gap: 8px; margin-bottom: 24px; 
        border-bottom: 1px solid var(--border); padding-bottom: 16px; overflow-x: auto; 
    }
    .tab-btn { 
        padding: 10px 20px; border: none; background: transparent; 
        color: var(--text-muted); font-weight: 700; font-size: 14px; 
        border-radius: 8px; cursor: pointer; transition: 0.2s; white-space: nowrap; 
    }
    .tab-btn:hover { background: var(--paper); color: var(--text); }
    .tab-btn.active { 
        background: var(--blue); color: #fff; 
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); 
    }
    
    .filter-form { 
        background: var(--paper); border: 1px solid var(--border); 
        padding: 16px; border-radius: 12px; margin-bottom: 24px; 
        display: flex; flex-wrap: wrap; gap: 12px; align-items: center; 
        transition: var(--transition);
    }
    .filter-form input, .filter-form select {
        padding: 10px 14px; border: 1px solid var(--border); 
        border-radius: 8px; background: var(--surface); color: var(--text); 
        font-size: 14px; font-family: var(--font-body); outline: none; transition: 0.2s;
    }
    .filter-form input:focus, .filter-form select:focus {
        border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft);
    }
    .filter-btn { 
        padding: 10px 20px; background: var(--blue); color: white; 
        border: none; border-radius: 8px; font-weight: 700; cursor: pointer; 
        transition: 0.2s;
    }
    .filter-btn:hover { background: var(--blue-dark); }

    /* Cars for Sale Grid */
    .sale-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 12px; padding: 20px; transition: var(--transition);
        display: flex; flex-direction: column; height: 100%;
    }
    .sale-card:hover {
        transform: translateY(-4px); box-shadow: var(--shadow-md);
        border-color: var(--border-hover);
    }
    .sale-title { font-family: var(--font-display); font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
    .sale-meta { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-bottom: 16px; }
    .sale-price { 
        font-family: var(--font-mono); font-size: 16px; font-weight: 800; 
        color: var(--teal); background: var(--teal-soft); padding: 6px 12px; 
        border-radius: 8px; display: inline-block; margin-bottom: 16px; align-self: flex-start;
    }
    .sale-footer { 
        margin-top: auto; border-top: 1px dashed var(--border); 
        padding-top: 14px; font-size: 12px; color: var(--text-muted); 
    }
    .sale-contact { font-family: var(--font-mono); font-weight: 600; color: var(--blue); margin-top: 4px; display: flex; align-items: center; gap: 6px; }
</style>

<div class="view">
  <div class="panel">
    <div class="panel-head">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg></div>
      <h3>Toyota Market Data & Catalog</h3>
    </div>
    <div class="panel-sub">Explore official Toyota SRPs, market research data, or find actual Toyota cars for sale by users.</div>
    
    <!-- Filter & Search Bar -->
    <form method="GET" class="filter-form">
      <input type="hidden" name="tab" id="active_tab_input" value="<?php echo htmlspecialchars($active_tab); ?>">
      
      <input type="text" name="model" placeholder="Search Toyota model..." value="<?php echo htmlspecialchars($f_model); ?>" style="flex: 1; min-width: 150px;">
      
      <!-- NEW VERIFICATION FILTER -->
      <select name="verified" style="width: 160px;">
        <option value="all" <?php echo $f_verified === 'all' ? 'selected' : ''; ?>>All Status</option>
        <option value="verified" <?php echo $f_verified === 'verified' ? 'selected' : ''; ?>>Verified Only</option>
        <option value="unverified" <?php echo $f_verified === 'unverified' ? 'selected' : ''; ?>>Unverified Only</option>
      </select>

      <input type="number" name="min_price" placeholder="Min Price ₱" value="<?php echo htmlspecialchars($f_min); ?>" style="width: 140px;">
      <input type="number" name="max_price" placeholder="Max Price ₱" value="<?php echo htmlspecialchars($f_max); ?>" style="width: 140px;">
      
      <button type="submit" class="filter-btn">Search</button>
      
      <?php if ($f_model || $f_min || $f_max || $f_verified !== 'all'): ?>
        <a href="dataset.php" style="font-size: 13px; color: var(--text-muted); text-decoration: underline; font-weight: 600;">Clear Filters</a>
      <?php endif; ?>
    </form>

    <!-- Tab Navigation -->
    <div class="tabs-container">
      <button id="btn-tab-srp" class="tab-btn" onclick="switchTab('tab-srp', this)">Toyota SRP Catalog</button>
      <button id="btn-tab-gathered" class="tab-btn" onclick="switchTab('tab-gathered', this)">Gathered Toyota Data</button>
      <button id="btn-tab-marketplace" class="tab-btn" onclick="switchTab('tab-marketplace', this)">Toyota Cars For Sale</button>
    </div>

    <!-- Category 1: SRP Catalog -->
    <div id="tab-srp" class="tab-content" style="display: none;">
      <div class="table-scroll">
        <table>
          <thead><tr><th>Toyota Model</th><th>Base SRP (₱)</th><th>Yearly Depreciation</th><th>Mileage Penalty</th></tr></thead>
          <tbody>
            <?php if (empty($srp_catalog)): ?>
                <tr><td colspan="4" style="text-align:center; padding: 30px 0;">No matching SRP records found.</td></tr>
            <?php else: foreach ($srp_catalog as $row): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($row['model']); ?></strong></td>
                <td class="mono-cell" style="color: var(--teal);">₱<?php echo number_format($row['base_price']); ?></td>
                <td class="mono-cell" style="color: var(--coral);">-₱<?php echo number_format($row['yearly_depreciation']); ?>/yr</td>
                <td class="mono-cell" style="color: var(--coral);">-₱<?php echo number_format($row['mileage_penalty'], 2); ?>/km</td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Category 2: Admin Gathered Data -->
    <div id="tab-gathered" class="tab-content" style="display: none;">
      <div class="table-scroll">
        <table>
          <thead><tr><th>Toyota Vehicle</th><th>Year</th><th>Mileage</th><th>Market Price</th><th>Source</th></tr></thead>
          <tbody>
            <?php if (empty($market_data)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 30px 0;">No matching market data found.</td></tr>
            <?php else: foreach ($market_data as $row): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($row['model']); ?></strong></td>
                <td class="mono-cell"><?php echo htmlspecialchars($row['year_manufactured']); ?></td>
                <td class="mono-cell"><?php echo number_format($row['mileage']); ?> km</td>
                <td class="mono-cell">₱<?php echo number_format($row['asking_price']); ?></td>
                <td style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($row['location'] ?: 'External Scrape'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Category 3: Active Marketplace -->
    <div id="tab-marketplace" class="tab-content" style="display: none;">
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php if (empty($cars_for_sale)): ?>
            <div style="grid-column: 1 / -1; text-align:center; padding: 60px 0; color: var(--text-muted);">
                No matching Toyota cars for sale.<br><br>
                <a href="submit_listing.php" class="predict-btn" style="display: inline-flex; text-decoration: none;">Sell Your Toyota</a>
            </div>
        <?php else: foreach ($cars_for_sale as $row): ?>
            <div class="sale-card">
                <div class="sale-title">
                    Toyota <?php echo htmlspecialchars($row['model']); ?>
                    <?php if($row['verification_status'] === 'mechanic_verified'): ?>
                        <span style="font-size: 10px; background: var(--teal-soft); color: var(--teal); padding: 2px 6px; border-radius: 4px; margin-left: 8px; border: 1px solid var(--teal); vertical-align: middle;">✓ Verified</span>
                    <?php endif; ?>
                </div>
                <div class="sale-meta"><?php echo htmlspecialchars($row['year_manufactured']); ?> • <?php echo number_format($row['mileage']); ?> km • <?php echo htmlspecialchars($row['transmission']); ?></div>
                
                <div class="sale-price">₱<?php echo number_format($row['asking_price']); ?></div>
                
                <div class="sale-footer">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-faint); font-weight: 700; margin-bottom: 6px;">Seller Info</div>
                    <div style="font-weight: 600; font-size: 13.5px; color: var(--text); margin-bottom: 4px;"><?php echo htmlspecialchars($row['seller_name'] ?: 'Anonymous Seller'); ?></div>
                    <div class="sale-contact">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                        <?php echo htmlspecialchars($row['seller_contact'] ?: 'Contact hidden'); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Logic to handle tab switching and remember the active tab during search
const activeTabInput = document.getElementById('active_tab_input');
const initialTabId = activeTabInput.value || 'tab-marketplace';

function switchTab(tabId, btnElement) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    
    // Reset button styles (remove active class)
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabId).style.display = 'block';
    
    // Style active button
    if (btnElement) {
        btnElement.classList.add('active');
    } else {
        // If called on load, find the button and add active class
        const btn = document.getElementById('btn-' + tabId);
        if (btn) btn.classList.add('active');
    }
    
    // Update the hidden input so the filter form remembers the tab
    activeTabInput.value = tabId;
}

// Trigger the active tab on page load
switchTab(initialTabId);
</script>

<?php include 'footer.php'; ?>