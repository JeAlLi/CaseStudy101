<?php
// Database Configuration
$host = 'localhost';
$dbname = 'vehicle_predictor';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch all reference data, sorted logically by type and brand
$stmt = $pdo->query("SELECT * FROM reference_weights ORDER BY vehicle_type, brand, model");
$dataset = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dataset Viewer — Vehicle Price Predictor</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #0E1116; --surface: #FFFFFF; --paper: #F2F3F6;
    --border: #E2E4EA; --text: #1A1D24; --text-muted: #6B7280; --text-faint: #9AA0AC;
    --indigo: #4F5FFF; --teal-soft: #E6FBF7; --teal: #0C8778;
    --font-display: 'Space Grotesk', sans-serif;
    --font-body: 'Inter', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
  }
  body { 
      font-family: var(--font-body); background: var(--paper); color: var(--text); 
      padding: 40px; display: flex; flex-direction: column; align-items: center; 
  }
  .container { width: 100%; max-width: 900px; }
  
  .nav-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .btn-back { 
      padding: 8px 16px; background: var(--surface); border: 1px solid var(--border); 
      border-radius: 8px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 600; 
      transition: border-color 0.15s ease;
  }
  .btn-back:hover { border-color: var(--indigo); }
  
  .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 26px; }
  h3 { font-family: var(--font-display); font-size: 18px; margin-bottom: 6px; }
  .panel-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5; }
  
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  thead th {
    text-align: left; font-family: var(--font-mono); font-size: 10.5px; letter-spacing: .04em; 
    text-transform: uppercase; color: var(--text-faint); padding: 12px; border-bottom: 1px solid var(--border);
  }
  tbody td { padding: 14px 12px; border-bottom: 1px solid var(--border); }
  tbody tr:hover { background: var(--paper); }
  tbody tr:last-child td { border-bottom: none; }
  
  .mono-cell { font-family: var(--font-mono); font-weight: 500; }
  .badge { font-family: var(--font-mono); font-size: 10.5px; padding: 3px 9px; border-radius: 20px; font-weight: 600; background: var(--teal-soft); color: var(--teal); }
</style>
</head>
<body>

<div class="container">
    <div class="nav-bar">
        <h2>System Dataset</h2>
        <a href="index.php" class="btn-back">← Back to Predictor</a>
    </div>

    <div class="panel">
        <h3>Reference Weights & Base Prices</h3>
        <p class="panel-sub">This table displays the core regression weights currently loaded into the MySQL database. These values dictate the baseline cost, annual depreciation, and per-kilometer usage penalties for the prediction engine.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Base Price (New)</th>
                    <th>Yearly Depreciation</th>
                    <th>Mileage Penalty (/km)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataset)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted);">No reference data found in the database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dataset as $row): ?>
                        <tr>
                            <td><span class="badge"><?php echo htmlspecialchars($row['vehicle_type']); ?></span></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['brand']); ?></td>
                            <td><?php echo htmlspecialchars($row['model']); ?></td>
                            <td class="mono-cell">₱<?php echo number_format($row['base_price'], 2); ?></td>
                            <td class="mono-cell" style="color: var(--coral);">−₱<?php echo number_format($row['yearly_depreciation'], 2); ?></td>
                            <td class="mono-cell" style="color: var(--coral);">−₱<?php echo number_format($row['mileage_penalty'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>