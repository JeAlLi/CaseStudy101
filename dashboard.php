<?php
require_once 'db.php';
$current_page = 'dashboard';

// 1. Total approved listings
$total_listings = (int)$pdo->query("SELECT COUNT(*) as total FROM vehicle_listings WHERE status = 'approved'")->fetch()['total'];

// 2. Distinct car brands covered
$brands_covered = (int)$pdo->query("SELECT COUNT(DISTINCT brand) as c FROM vehicle_listings WHERE status = 'approved'")->fetch()['c'];

if ($brands_covered === 0) {
    // Fallback to reference weights if no live data is approved yet
    $brands_covered = (int)$pdo->query("SELECT COUNT(DISTINCT brand) as c FROM reference_weights")->fetch()['c'];
}

// 3. Dynamic Averages: Filtered for approved data and grouped by Body Type using PHP
$stmtAll = $pdo->query("SELECT brand, model, asking_price, mileage FROM vehicle_listings WHERE status = 'approved' AND asking_price > 0");
$listings = $stmtAll->fetchAll();

$bodyStats = [];
foreach ($listings as $row) {
    $bType = get_body_type($row['brand'], $row['model']);
    if (!isset($bodyStats[$bType])) {
        $bodyStats[$bType] = ['total_price' => 0, 'total_mileage' => 0, 'count' => 0];
    }
    $bodyStats[$bType]['total_price'] += $row['asking_price'];
    $bodyStats[$bType]['total_mileage'] += $row['mileage'];
    $bodyStats[$bType]['count']++;
}

// Calculate the final averages to display
$displayStats = [];
foreach ($bodyStats as $type => $data) {
    $displayStats[$type] = [
        'avg_price' => $data['total_price'] / $data['count'],
        'avg_mileage' => $data['total_mileage'] / $data['count']
    ];
}

// Sort by highest average price just to keep the UI organized
uasort($displayStats, function($a, $b) {
    return $b['avg_price'] <=> $a['avg_price'];
});

// 4. Model Accuracy Metrics (Only processes approved data)
$metrics = compute_model_metrics($pdo);

include 'header.php';
?>

<div class="view">
  <div class="hero">
    <div class="hero-eyebrow">Data-driven pricing, updated live</div>
    <h1>Find the right price.<br>Sell <em>smart.</em> Buy <em>better.</em></h1>
    <p class="hero-copy">Get an instant, data-backed estimate for any car—built from real listings, not guesswork.</p>
    
    <div class="hero-stats">
      <div class="hero-stat"><b><?php echo number_format($total_listings); ?></b><span>Listings gathered</span></div>
      <div class="hero-stat">
        <b><?php echo $metrics['r2'] !== null ? number_format($metrics['r2'] * 100, 1) . '%' : '—'; ?></b>
        <span>Prediction accuracy</span>
      </div>
      <div class="hero-stat">
        <b><?php echo $metrics['mae'] !== null ? '₱' . number_format($metrics['mae']) : '—'; ?></b>
        <span>Avg. prediction error</span>
      </div>
      <div class="hero-stat"><b><?php echo number_format($brands_covered); ?></b><span>Brands covered</span></div>
    </div>
  </div>
  
<!-- Price Trends Chart Panel -->
  <div class="panel" style="margin-top: 28px;">
    <div class="panel-head">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><path d="M3 17l6-6 4 4 8-8"/></svg></div>
      <h3>Average Price Trends by Body Type</h3>
    </div>
    <div class="panel-sub">Historical market data and projected depreciation trends (2019–2026).</div>
    
    <div class="chart-wrap" style="position: relative; height: 300px; margin-top: 16px;">
      <canvas id="trendChart"></canvas>
    </div>
    
    <div class="legend-row" style="display: flex; gap: 18px; margin-top: 18px; justify-content: center;">
      <div style="display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-muted);">
        <span style="width: 10px; height: 10px; border-radius: 2px; background: #4F5FFF;"></span> Sedan
      </div>
      <div style="display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-muted);">
        <span style="width: 10px; height: 10px; border-radius: 2px; background: #12B8A2;"></span> SUV
      </div>
      <div style="display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-muted);">
        <span style="width: 10px; height: 10px; border-radius: 2px; background: #F5A623;"></span> Pickup
      </div>
    </div>
  </div>

    <!-- Average Price Breakdown -->
    <div class="kpi">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--teal-soft)"><svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
        <span class="lbl" style="font-weight:600; color:var(--text); margin:0;">Avg Asking Price</span>
      </div>
      <div style="font-family:var(--font-mono); font-size:12.5px; margin-top:12px; display:flex; flex-direction:column; gap:6px;">
        <?php if (empty($displayStats)): ?>
            <div style="color:var(--text-faint);">Awaiting market data...</div>
        <?php else: ?>
            <?php $count = 0; foreach ($displayStats as $type => $stats): if($count++ >= 3) break; ?>
                <div style="display:flex; justify-content:space-between;">
                    <span><?php echo htmlspecialchars($type); ?></span> 
                    <b style="color:var(--text)">₱<?php echo number_format($stats['avg_price']); ?></b>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Average Mileage Breakdown -->
    <div class="kpi">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#FFF4E0"><svg viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <span class="lbl" style="font-weight:600; color:var(--text); margin:0;">Average Mileage</span>
      </div>
      <div style="font-family:var(--font-mono); font-size:12.5px; margin-top:12px; display:flex; flex-direction:column; gap:6px;">
        <?php if (empty($displayStats)): ?>
            <div style="color:var(--text-faint);">Awaiting market data...</div>
        <?php else: ?>
            <?php $count = 0; foreach ($displayStats as $type => $stats): if($count++ >= 3) break; ?>
                <div style="display:flex; justify-content:space-between;">
                    <span><?php echo htmlspecialchars($type); ?></span> 
                    <b style="color:var(--text)"><?php echo number_format($stats['avg_mileage']); ?> km</b>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Model Status -->
    <div class="kpi">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:var(--coral-soft)"><svg viewBox="0 0 24 24" fill="none" stroke="var(--coral)" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg></div>
      </div>
      <div class="val"><?php echo $metrics['r2'] !== null ? number_format($metrics['r2'] * 100, 1) . '%' : 'Building…'; ?></div>
      <div class="lbl">Prediction accuracy <span style="color:var(--text-faint)">(R² score)</span></div>
      <?php if ($metrics['n'] < 2): ?>
        <div class="lbl" style="margin-top:4px; color:var(--text-faint);">Accuracy will appear once enough approved listings are on file.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Configure global defaults to match your custom fonts and colors
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';

// Initialize the Trend Line Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
  type: 'line',
  data: {
    labels: ['2019', '2020', '2021', '2022', '2023', '2024', '2025', '2026'],
    datasets: [
      {
        label: 'Sedan', 
        data: [560000, 572000, 590000, 610000, 628000, 642000, 655000, 668000], 
        borderColor: '#4F5FFF', 
        backgroundColor: 'rgba(79, 95, 255, 0.08)', 
        fill: true, 
        tension: 0.35, 
        borderWidth: 2.5, 
        pointRadius: 0,
        pointHoverRadius: 6
      },
      {
        label: 'SUV', 
        data: [980000, 1005000, 1040000, 1080000, 1120000, 1160000, 1195000, 1230000], 
        borderColor: '#12B8A2', 
        backgroundColor: 'rgba(18, 184, 162, 0.06)', 
        fill: true, 
        tension: 0.35, 
        borderWidth: 2.5, 
        pointRadius: 0,
        pointHoverRadius: 6
      },
      {
        label: 'Pickup', 
        data: [1100000, 1120000, 1150000, 1210000, 1280000, 1350000, 1400000, 1450000], 
        borderColor: '#F5A623', 
        backgroundColor: 'rgba(245, 166, 35, 0.06)', 
        fill: true, 
        tension: 0.35, 
        borderWidth: 2.5, 
        pointRadius: 0,
        pointHoverRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0E1116',
        titleFont: { family: "'Space Grotesk', sans-serif", size: 14 },
        bodyFont: { family: "'JetBrains Mono', monospace", size: 13 },
        padding: 12,
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) label += ': ';
                if (context.parsed.y !== null) {
                    label += '₱' + context.parsed.y.toLocaleString('en-PH');
                }
                return label;
            }
        }
      }
    },
    scales: {
      y: {
        grid: { color: '#EEF0F3', drawBorder: false },
        ticks: { 
            callback: value => '₱' + (value / 1000) + 'K',
            font: { family: "'JetBrains Mono', monospace", size: 11 }
        }
      },
      x: {
        grid: { display: false, drawBorder: false },
        ticks: { font: { family: "'JetBrains Mono', monospace", size: 12 } }
      }
    }
  }
});
</script>

<?php include 'footer.php'; ?>