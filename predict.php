<?php 
require_once 'db.php';
$current_page = 'predict';

$estimated_price = null;
$breakdown = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    // 1. Vehicle Specifications
    $type = $_POST['vehicle_type'] ?? 'Car';
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = (int)$_POST['year'];
    $mileage = (int)$_POST['mileage'];
    
    // 2. Condition & History Inputs
    $registration = $_POST['registration'] ?? 'Updated';
    $maintenance = $_POST['maintenance'] ?? 'Average';
    $accidents = $_POST['accidents'] ?? 'None';
    
    $current_year = (int)date("Y");
    
    $stmt = $pdo->prepare("SELECT * FROM reference_weights WHERE vehicle_type = ? AND brand = ? AND model = ? LIMIT 1");
    $stmt->execute([$type, $brand, $model]);
    $vehicle_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle_data) {
        $age = max(0, $current_year - $year);
        $base = $vehicle_data['base_price'];
        
        // Base Regression Adjustments
        $year_adj = $age * $vehicle_data['yearly_depreciation'];
        $mileage_adj = $mileage * $vehicle_data['mileage_penalty'];
        
        // Dynamic Condition Adjustments based on user input
        $cond_adj = 0;
        
        // Registration Status Modifier
        if ($registration === 'Expired') $cond_adj -= 8000;
        
        // Maintenance History Modifier
        if ($maintenance === 'Full Service Records') $cond_adj += 15000;
        if ($maintenance === 'No Records') $cond_adj -= 10000;
        
        // Accident History Modifier
        if ($accidents === 'Minor Scratches') $cond_adj -= 12000;
        if ($accidents === 'Major Repairs') $cond_adj -= 45000;
        
        // Calculate Final Price
        $final_price = $base - $year_adj - $mileage_adj + $cond_adj;
        $estimated_price = max(1000, $final_price); 
        
        $range_low = $estimated_price * 0.947;
        $range_high = $estimated_price * 1.053;
        
        $breakdown = [
            'base' => $base, 
            'year_adj' => $year_adj,
            'mileage_adj' => $mileage_adj, 
            'cond_adj' => $cond_adj
        ];
    } else {
        $error = "Model not found in training dataset.";
    }
}

include 'header.php'; 
?>

<div class="view">
  <div class="two-col">
    <!-- FORM PANEL -->
    <div class="panel">
      <div class="panel-head">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></div>
        <h3>Vehicle Information</h3>
      </div>
      <div class="panel-sub">Enter the vehicle's exact specifications and history to predict its price.</div>

      <form method="POST">
        <div class="form-grid">
          <!-- Make & Model -->
          <div class="field" style="grid-column: span 2;"><label>Vehicle Type</label>
            <select name="vehicle_type">
              <option value="Car" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Car') ? 'selected' : ''; ?>>Car</option>
              <option value="Motorcycle" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
            </select>
          </div>
          <div class="field"><label>Make / Brand</label>
            <input type="text" name="brand" value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : 'Toyota'; ?>" required>
          </div>
          <div class="field"><label>Model</label>
            <input type="text" name="model" value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : 'Vios'; ?>" required>
          </div>
          <div class="field"><label>Year of Manufacture</label>
            <input type="number" name="year" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : '2021'; ?>" required>
          </div>
          <div class="field"><label>Mileage (km)</label>
            <input type="number" name="mileage" value="<?php echo isset($_POST['mileage']) ? htmlspecialchars($_POST['mileage']) : '35000'; ?>" required>
          </div>
          
          <!-- Specs -->
          <div class="field"><label>Transmission</label>
            <select name="transmission"><option>Automatic</option><option>Manual</option></select>
          </div>
          <div class="field"><label>Fuel Type</label>
            <select name="fuel_type"><option>Gasoline</option><option>Diesel</option><option>Hybrid</option><option>Electric</option></select>
          </div>
          <div class="field"><label>Color</label>
            <input type="text" name="color" placeholder="e.g., Pearl White">
          </div>
          <div class="field"><label>Modifications</label>
            <select name="modifications"><option>Stock / None</option><option>Minor Upgrades</option><option>Heavily Modified</option></select>
          </div>

          <!-- History & Condition -->
          <div class="field" style="grid-column: span 2; border-top: 1px solid var(--border); padding-top: 16px; margin-top: 8px;">
            <label style="color: var(--indigo); font-size: 13px;">Condition & History Assessment</label>
          </div>
          
          <div class="field"><label>Registration Status (LTO)</label>
            <select name="registration">
                <option value="Updated" <?php echo (isset($_POST['registration']) && $_POST['registration'] == 'Updated') ? 'selected' : ''; ?>>Up to Date</option>
                <option value="Expired" <?php echo (isset($_POST['registration']) && $_POST['registration'] == 'Expired') ? 'selected' : ''; ?>>Expired</option>
            </select>
          </div>
          <div class="field"><label>Maintenance History</label>
            <select name="maintenance">
                <option value="Full Service Records" <?php echo (isset($_POST['maintenance']) && $_POST['maintenance'] == 'Full Service Records') ? 'selected' : ''; ?>>Full Service Records (Casa/Shop)</option>
                <option value="Average" <?php echo (empty($_POST['maintenance']) || $_POST['maintenance'] == 'Average') ? 'selected' : ''; ?>>Average / Some Records</option>
                <option value="No Records" <?php echo (isset($_POST['maintenance']) && $_POST['maintenance'] == 'No Records') ? 'selected' : ''; ?>>No Records</option>
            </select>
          </div>
          <div class="field" style="grid-column: span 2;"><label>Accident History</label>
            <select name="accidents">
                <option value="None" <?php echo (empty($_POST['accidents']) || $_POST['accidents'] == 'None') ? 'selected' : ''; ?>>None / Clean History</option>
                <option value="Minor Scratches" <?php echo (isset($_POST['accidents']) && $_POST['accidents'] == 'Minor Scratches') ? 'selected' : ''; ?>>Minor Scratches / Dents</option>
                <option value="Major Repairs" <?php echo (isset($_POST['accidents']) && $_POST['accidents'] == 'Major Repairs') ? 'selected' : ''; ?>>Major Accidents / Body Repairs</option>
            </select>
          </div>
        </div>

        <button type="submit" name="predict" class="predict-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>
          Predict Price
        </button>
      </form>
    </div>

    <!-- RESULT PANEL -->
    <div>
      <div class="panel">
        <div class="panel-head">
          <div class="ic" style="background:var(--teal-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M12 3l2.5 5.5L20 9l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/></svg></div>
          <h3>Estimated Price</h3>
        </div>
        <div class="panel-sub">Based on the specifications and history provided.</div>

        <?php if ($estimated_price !== null): ?>
            <div class="price-box">
              <div class="lbl">Estimated Market Price</div>
              <div class="amt">₱ <?php echo number_format($estimated_price); ?></div>
              <div class="range">Confidence Range<br><b>₱ <?php echo number_format($range_low); ?> – ₱ <?php echo number_format($range_high); ?></b></div>
            </div>

            <div class="breakdown">
              <div class="row"><span>Base Price (Similar Listings)</span><span class="amt">₱ <?php echo number_format($breakdown['base']); ?></span></div>
              <div class="row"><span>Age Depreciation</span><span class="amt neg">− ₱ <?php echo number_format($breakdown['year_adj']); ?> ▼</span></div>
              <div class="row"><span>Mileage Penalty</span><span class="amt neg">− ₱ <?php echo number_format($breakdown['mileage_adj']); ?> ▼</span></div>
              
              <div class="row">
                  <span>Condition & History Impact</span>
                  <?php if ($breakdown['cond_adj'] >= 0): ?>
                      <span class="amt pos">+ ₱ <?php echo number_format($breakdown['cond_adj']); ?> ▲</span>
                  <?php else: ?>
                      <span class="amt neg">− ₱ <?php echo number_format(abs($breakdown['cond_adj'])); ?> ▼</span>
                  <?php endif; ?>
              </div>
              
              <div class="row total"><span>Estimated Price</span><span class="amt">₱ <?php echo number_format($estimated_price); ?></span></div>
            </div>
        <?php elseif (isset($error)): ?>
            <div style="color: var(--coral); font-weight: 600; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: var(--text-faint); padding: 40px 0;">
                Fill out the form and click "Predict Price" to generate a valuation.
            </div>
        <?php endif; ?>
      </div>
      
      <!-- Seller Listing Data (For Display Only) -->
      <div class="panel" style="margin-top: 20px;">
        <div class="panel-head">
          <div class="ic" style="background:var(--indigo-soft);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
          <h3>Listing Generation Details</h3>
        </div>
        <div class="panel-sub">Details typically required when publishing the final classified ad.</div>
        <div class="form-grid">
            <div class="field"><label>Asking Price Status</label><select><option>Fixed Price</option><option>Slightly Negotiable</option></select></div>
            <div class="field"><label>Viewing Location</label><input type="text" placeholder="e.g., Makati, Metro Manila"></div>
            <div class="field" style="grid-column: span 2;"><label>Inclusions</label><input type="text" placeholder="Spare keys, manual, extra tires..."></div>
            <div class="field" style="grid-column: span 2;"><label>Reason for Selling</label><input type="text" placeholder="Upgrading, moving abroad, etc."></div>
        </div>
      </div>
      
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>