<?php
require_once 'db.php';
$current_page = 'dataset';

// 1. Capture Filter Inputs from the URL
$f_brand = trim($_GET['brand'] ?? '');
$f_model = trim($_GET['model'] ?? '');
$f_min = trim($_GET['min_price'] ?? '');
$f_max = trim($_GET['max_price'] ?? '');
$active_tab = $_GET['tab'] ?? 'tab-marketplace';

// 2. Build the Dynamic SQL WHERE Clauses
$whereMarket = ["status = 'approved'", "listing_type = 'scraped'"];
$whereUser = ["status = 'approved'", "listing_type = 'user_sale'"];
$whereSRP = ["vehicle_type = 'Car'"];
$params = [];
$paramsSRP = [];

if ($f_brand !== '') {
    $whereMarket[] = "brand = ?";
    $whereUser[] = "brand = ?";
    $whereSRP[] = "brand = ?";
    $params[] = $f_brand;
    $paramsSRP[] = $f_brand;
}
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

// Convert arrays to strings for the SQL query
$sqlWhereMarket = implode(' AND ', $whereMarket);
$sqlWhereUser = implode(' AND ', $whereUser);
$sqlWhereSRP = implode(' AND ', $whereSRP);

// 3. Fetch Data with Applied Filters
$stmtSRP = $pdo->prepare("SELECT * FROM reference_weights WHERE $sqlWhereSRP ORDER BY brand, model");
$stmtSRP->execute($paramsSRP);
$srp_catalog = $stmtSRP->fetchAll();

$stmtMarket = $pdo->prepare("SELECT * FROM vehicle_listings WHERE $sqlWhereMarket ORDER BY date_added DESC");
$stmtMarket->execute($params);
$market_data = $stmtMarket->fetchAll();

$stmtForSale = $pdo->prepare("SELECT * FROM vehicle_listings WHERE $sqlWhereUser ORDER BY date_added DESC");
$stmtForSale->execute($params);
$cars_for_sale = $stmtForSale->fetchAll();

// Get unique brands for the dropdown
$brands = $pdo->query("SELECT DISTINCT brand FROM reference_weights WHERE vehicle_type = 'Car' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

include 'header.php';
?>

<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg></div>
      <h3>Vehicle Database & Marketplace</h3>
    </div>
    <div class="panel-sub">Explore base SRPs, market research data, or find actual cars for sale by users.</div>
    
    <!-- Filter & Search Bar -->
    <form method="GET" style="background: var(--paper); padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; border: 1px solid var(--border);">
      <input type="hidden" name="tab" id="active_tab_input" value="<?php echo htmlspecialchars($active_tab); ?>">
      
      <select name="brand" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none;">
        <option value="">All Brands</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $b === $f_brand ? 'selected' : ''; ?>><?php echo htmlspecialchars($b); ?></option>
        <?php endforeach; ?>
      </select>
      
      <input type="text" name="model" placeholder="Search model..." value="<?php echo htmlspecialchars($f_model); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; flex: 1; min-width: 150px;">
      
      <input type="number" name="min_price" placeholder="Min Price ₱" value="<?php echo htmlspecialchars($f_min); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; width: 130px;">
      
      <input type="number" name="max_price" placeholder="Max Price ₱" value="<?php echo htmlspecialchars($f_max); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; width: 130px;">
      
      <button type="submit" style="padding: 9px 18px; background: var(--indigo); color: white; border: none; border-radius: 6px; font-weight: 600;">Search</button>
      
      <?php if ($f_brand || $f_model || $f_min || $f_max): ?>
        <a href="dataset.php" style="font-size: 13px; color: var(--text-muted); text-decoration: underline;">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Tab Navigation -->
    <div style="display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <button id="btn-tab-srp" class="tab-btn" onclick="switchTab('tab-srp', this)" style="padding: 8px 16px; border: none; background: transparent; color: var(--text-muted); border-radius: 6px; font-weight: 600;">SRP Catalog</button>
      <button id="btn-tab-gathered" class="tab-btn" onclick="switchTab('tab-gathered', this)" style="padding: 8px 16px; border: none; background: transparent; color: var(--text-muted); border-radius: 6px; font-weight: 600;">Gathered Data</button>
      <button id="btn-tab-marketplace" class="tab-btn" onclick="switchTab('tab-marketplace', this)" style="padding: 8px 16px; border: none; background: transparent; color: var(--text-muted); border-radius: 6px; font-weight: 600;">Cars For Sale</button>
    </div>

    <!-- Category 1: SRP Catalog -->
    <div id="tab-srp" class="tab-content" style="display: none;">
      <div class="table-scroll">
        <table>
          <thead><tr><th>Brand & Model</th><th>Base SRP (₱)</th><th>Yearly Depreciation</th><th>Mileage Penalty</th></tr></thead>
          <tbody>
            <?php if (empty($srp_catalog)): ?>
                <tr><td colspan="4" style="text-align:center; padding: 30px 0;">No matching SRP records found.</td></tr>
            <?php else: foreach ($srp_catalog as $row): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></strong></td>
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
          <thead><tr><th>Vehicle</th><th>Year</th><th>Mileage</th><th>Market Price</th><th>Source</th></tr></thead>
          <tbody>
            <?php if (empty($market_data)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 30px 0;">No matching market data found.</td></tr>
            <?php else: foreach ($market_data as $row): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></strong></td>
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
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
        <?php if (empty($cars_for_sale)): ?>
            <div style="grid-column: 1 / -1; text-align:center; padding: 40px 0; color: var(--text-faint);">
                No matching cars for sale.<br><br>
                <a href="submit_listing.php" class="predict-btn" style="display: inline-flex; text-decoration: none;">Sell Your Car</a>
            </div>
        <?php else: foreach ($cars_for_sale as $row): ?>
            <div style="border: 1px solid var(--border); border-radius: 12px; padding: 20px; background: var(--surface);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <h4 style="font-family: var(--font-display); font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h4>
                        <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['year_manufactured']); ?> • <?php echo number_format($row['mileage']); ?> km • <?php echo htmlspecialchars($row['transmission']); ?></div>
                    </div>
                    <div class="badge" style="background: var(--teal-soft); color: var(--teal);">₱<?php echo number_format($row['asking_price']); ?></div>
                </div>
                
                <div style="border-top: 1px dashed var(--border); margin: 14px 0; padding-top: 14px;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-faint); margin-bottom: 8px;">Seller Information</div>
                    <div style="font-weight: 600; font-size: 13.5px;"><?php echo htmlspecialchars($row['seller_name'] ?: 'Anonymous Seller'); ?></div>
                    <div style="font-family: var(--font-mono); font-size: 12.5px; color: var(--indigo); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
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
    
    // Reset button styles
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.style.background = 'transparent';
        el.style.color = 'var(--text-muted)';
    });
    
    // Show selected tab
    document.getElementById(tabId).style.display = 'block';
    
    // Style active button
    if (btnElement) {
        btnElement.style.background = 'var(--indigo)';
        btnElement.style.color = 'white';
    }
    
    // Update the hidden input so the filter form remembers the tab
    activeTabInput.value = tabId;
}

// Trigger the active tab on page load
const initialBtn = document.getElementById('btn-' + initialTabId);
if (initialBtn) switchTab(initialTabId, initialBtn);
</script>

<?php include 'footer.php'; ?>