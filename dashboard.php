<?php
require_once 'db.php';
$current_page = 'dashboard';

$stats = $pdo->query("
    SELECT COUNT(*) AS total, AVG(asking_price) AS avg_price
    FROM vehicle_listings
    WHERE status = 'approved'
")->fetch();

$total_listings = (int)$stats['total'];
$avg_price      = (float)$stats['avg_price'];

$verified_count = (int)$pdo->query("
    SELECT COUNT(*) FROM vehicle_listings
    WHERE status = 'approved' AND verification_status = 'mechanic_verified'
")->fetchColumn();

$featured_listings = $pdo->query("
    SELECT * FROM vehicle_listings
    WHERE status = 'approved' AND listing_type = 'user_sale'
    ORDER BY
        CASE WHEN verification_status = 'mechanic_verified' THEN 0 ELSE 1 END,
        CASE WHEN photo_front IS NOT NULL AND photo_front != '' THEN 0 ELSE 1 END,
        date_added DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$brand_data = $pdo->query("
    SELECT brand, COUNT(*) AS cnt FROM vehicle_listings
    WHERE status = 'approved' GROUP BY brand
")->fetchAll(PDO::FETCH_KEY_PAIR);

$trend_data = $pdo->query("
    SELECT year_manufactured, AVG(asking_price) AS avg_p
    FROM vehicle_listings
    WHERE status = 'approved' AND year_manufactured BETWEEN 2015 AND 2026
    GROUP BY year_manufactured ORDER BY year_manufactured ASC
")->fetchAll(PDO::FETCH_ASSOC);

$trend_labels = []; $trend_values = [];
foreach ($trend_data as $t) { $trend_labels[] = $t['year_manufactured']; $trend_values[] = round($t['avg_p'], 2); }

include 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .hero-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    border-radius: 20px; padding: 48px 48px 40px; color: #fff;
    margin-bottom: 28px; position: relative; overflow: hidden;
    box-shadow: 0 20px 40px -20px rgba(30, 27, 75, 0.6);
  }
  .hero-banner::before {
    content: ''; position: absolute; top: -120px; right: -120px;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, transparent 70%);
    border-radius: 50%; pointer-events: none;
  }
  .hero-inner {
    position: relative; z-index: 2;
    display: flex; justify-content: space-between; align-items: center;
    gap: 40px; flex-wrap: wrap;
  }
  .hero-copy { flex: 1; min-width: 280px; }
  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(99, 102, 241, 0.18); border: 1px solid rgba(129, 140, 248, 0.4);
    color: #c7d2fe; padding: 6px 14px; border-radius: 20px;
    font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; margin-bottom: 20px;
  }
  .hero-badge::before {
    content: ''; width: 6px; height: 6px; background: #10b981;
    border-radius: 50%; box-shadow: 0 0 8px #10b981;
  }
  .hero-title {
    font-family: var(--font-display); font-size: 40px; font-weight: 800;
    line-height: 1.15; letter-spacing: -1px; margin-bottom: 16px;
  }
  .hero-title span { color: #a5b4fc; }
  .hero-desc {
    color: #cbd5e1; font-size: 15px; line-height: 1.6;
    max-width: 480px; margin-bottom: 28px;
  }
  .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
  .hero-btn {
    padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    transition: transform 0.15s ease;
  }
  .hero-btn:hover { transform: translateY(-2px); }
  .hero-btn.primary { background: #fff; color: #1e1b4b; box-shadow: 0 8px 20px -8px rgba(255,255,255,0.5); }
  .hero-btn.secondary { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
  .hero-btn.secondary:hover { background: rgba(255,255,255,0.15); }

  .hero-stats-panel {
    display: flex; flex-direction: column; gap: 14px;
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 16px; padding: 24px 28px; backdrop-filter: blur(10px);
    min-width: 220px;
  }
  .hstat { display: flex; align-items: center; gap: 14px; }
  .hstat-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(99, 102, 241, 0.25); flex-shrink: 0;
  }
  .hstat-icon.green { background: rgba(16, 185, 129, 0.25); }
  .hstat-icon.amber { background: rgba(245, 158, 11, 0.25); }
  .hstat-icon svg { width: 18px; height: 18px; }
  .hstat-val { font-family: var(--font-display); font-size: 22px; font-weight: 800; color: #fff; line-height: 1.1; }
  .hstat-lbl { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }

  .featured-section { margin-bottom: 32px; }
  .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; }
  .section-title { font-family: var(--font-display); font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.4px; }
  .section-sub { font-size: 13.5px; color: #64748b; margin-top: 4px; }
  .see-all { font-size: 13px; font-weight: 700; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .see-all:hover { text-decoration: underline; }

  .featured-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  .feat-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
    overflow: hidden; text-decoration: none; color: inherit;
    display: flex; flex-direction: column; position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .feat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 32px -12px rgba(0,0,0,0.15); border-color: #cbd5e1; }
  .feat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6366f1, #2563eb); z-index: 3;
  }
  .feat-card.is-verified::before { background: linear-gradient(90deg, #10b981, #059669); }
  .feat-img {
    width: 100%; height: 170px; background: #f1f5f9;
    background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
    background-size: 20px 20px;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
  }
  .feat-img img { width: 100%; height: 100%; object-fit: cover; }
  .feat-img .ph-icon { width: 44px; height: 44px; color: #94a3b8; opacity: 0.7; }
  .feat-price {
    position: absolute; bottom: 12px; right: 12px;
    background: #fff; color: #0f172a; font-family: var(--font-display);
    font-size: 17px; font-weight: 800; padding: 6px 12px;
    border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 2;
  }
  .feat-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
  .feat-title { font-family: var(--font-display); font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
  .feat-sub { font-size: 12.5px; color: #64748b; margin-bottom: 14px; }
  .feat-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
  .feat-badge {
    font-size: 10.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px;
    display: inline-flex; align-items: center; gap: 4px;
  }
  .feat-badge.verified { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
  .feat-badge.verified svg { width: 12px; height: 12px; }
  .feat-badge.unverified { background: #fff; color: #64748b; border: 1px solid #cbd5e1; }
  .feat-footer {
    margin-top: auto; border-top: 1px dashed #e2e8f0; padding-top: 12px;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 12px; color: #64748b; font-weight: 500;
  }
  .feat-footer svg { width: 13px; height: 13px; }

  .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px; }
  .chart-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
  .chart-header { font-family: var(--font-display); font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
  .chart-header .tag { font-size: 11px; font-weight: 600; color: #6b7280; background: #f3f4f6; padding: 4px 10px; border-radius: 6px; }
  .chart-container { position: relative; height: 280px; width: 100%; }

  .empty-featured { grid-column: 1 / -1; padding: 60px 20px; text-align: center; background: #fff; border: 1px dashed #cbd5e1; border-radius: 16px; color: #94a3b8; }

  @media (max-width: 1024px) {
    .featured-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; }
    .hero-inner { flex-direction: column; align-items: flex-start; }
    .hero-stats-panel { width: 100%; flex-direction: row; flex-wrap: wrap; justify-content: space-between; }
    .hstat { flex: 1; min-width: 140px; }
  }
  @media (max-width: 640px) {
    .hero-banner { padding: 32px 24px; }
    .hero-title { font-size: 30px; }
    .featured-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="view" style="display: block; width: 100%; max-width: 1400px; margin: 0 auto;">

  <!-- HERO -->
  <div class="hero-banner">
    <div class="hero-inner">
      <div class="hero-copy">
        <div class="hero-badge">Live Market Data</div>
        <h1 class="hero-title">Find the right price.<br>Sell <span>smart</span>. Buy <span>better</span>.</h1>
        <p class="hero-desc">
          Real listings from real sellers across the Philippines. Browse verified vehicles,
          or get a fair price estimate for your own car in seconds.
        </p>
        <div class="hero-actions">
          <a href="browse.php" class="hero-btn primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            Browse Cars
          </a>
          <a href="predict.php" class="hero-btn secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            Predict a Price
          </a>
        </div>
      </div>

      <div class="hero-stats-panel">
        <div class="hstat">
          <div class="hstat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo number_format($total_listings); ?></div>
            <div class="hstat-lbl">Active Listings</div>
          </div>
        </div>
        <div class="hstat">
          <div class="hstat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo number_format($verified_count); ?></div>
            <div class="hstat-lbl">Verified Cars</div>
          </div>
        </div>
        <div class="hstat">
          <div class="hstat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fcd34d" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo $avg_price > 0 ? '₱' . number_format($avg_price / 1000, 0) . 'K' : '—'; ?></div>
            <div class="hstat-lbl">Avg. Price</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FEATURED -->
  <div class="featured-section">
    <div class="section-header">
      <div>
        <h2 class="section-title">Featured Listings</h2>
        <div class="section-sub">Hand-picked cars from verified and active sellers.</div>
      </div>
      <a href="browse.php" class="see-all">
        See all listings
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="featured-grid">
      <?php if (empty($featured_listings)): ?>
        <div class="empty-featured">
          <p>No featured listings yet — check back soon.</p>
        </div>
      <?php else: foreach ($featured_listings as $car):
          $has_photo = !empty($car['photo_front']);
          $is_verified = ($car['verification_status'] ?? '') === 'mechanic_verified';
      ?>
        <a class="feat-card <?php echo $is_verified ? 'is-verified' : ''; ?>" href="browse.php">
          <div class="feat-img">
            <?php if ($has_photo): ?>
              <img src="uploads/<?php echo htmlspecialchars($car['photo_front']); ?>" alt="<?php echo htmlspecialchars($car['brand'].' '.$car['model']); ?>">
            <?php else: ?>
              <svg class="ph-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18"/></svg>
            <?php endif; ?>
            <div class="feat-price">₱<?php echo number_format($car['asking_price']); ?></div>
          </div>
          <div class="feat-body">
            <div class="feat-title"><?php echo htmlspecialchars($car['brand'].' '.$car['model']); ?></div>
            <div class="feat-sub">
              <?php echo $car['year_manufactured']; ?> • <?php echo number_format($car['mileage']); ?> km • <?php echo htmlspecialchars($car['transmission']); ?>
            </div>
            <div class="feat-badges">
              <?php if ($is_verified): ?>
                <span class="feat-badge verified">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                  Mechanic Verified
                </span>
              <?php else: ?>
                <span class="feat-badge unverified">Unverified</span>
              <?php endif; ?>
            </div>
            <div class="feat-footer">
              <span style="display:flex;align-items:center;gap:5px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo htmlspecialchars($car['city'] ?: ($car['location'] ?: 'N/A')); ?>
              </span>
              <span style="color:#2563eb;font-weight:700;">View →</span>
            </div>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- CHARTS -->
  <div class="charts-grid">
    <div class="chart-card">
      <div class="chart-header">
        <span>Average Price by Year</span>
        <span class="tag">Market Trend</span>
      </div>
      <div class="chart-container"><canvas id="priceTrendChart"></canvas></div>
    </div>

    <div class="chart-card">
      <div class="chart-header">
        <span>Listings by Brand</span>
        <span class="tag">Share %</span>
      </div>
      <div class="chart-container"><canvas id="brandDistChart"></canvas></div>
    </div>
  </div>

</div>

<script>
  new Chart(document.getElementById('priceTrendChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode($trend_labels); ?>,
      datasets: [{
        label: 'Avg Market Price (₱)',
        data: <?php echo json_encode($trend_values); ?>,
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.08)',
        borderWidth: 3, fill: true, tension: 0.35,
        pointRadius: 4, pointBackgroundColor: '#6366f1',
        pointBorderColor: '#fff', pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: false, grid: { color: '#eef0f3' }, ticks: { callback: v => '₱' + (v / 1000).toFixed(0) + 'K' } },
        x: { grid: { display: false } }
      }
    }
  });

  new Chart(document.getElementById('brandDistChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_keys($brand_data)); ?>,
      datasets: [{
        data: <?php echo json_encode(array_values($brand_data)); ?>,
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'],
        borderWidth: 3, borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, padding: 12 } } }
    }
  });
</script>

<?php include 'footer.php'; ?>