<?php
require_once 'db.php';
$current_page = 'dashboard';

$stats = $pdo->query("SELECT COUNT(*) AS total, AVG(asking_price) AS avg_price FROM vehicle_listings WHERE status = 'approved'")->fetch();
$total_listings = (int)$stats['total'];
$avg_price      = (float)$stats['avg_price'];
$verified_count = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_listings WHERE status = 'approved' AND verification_status = 'mechanic_verified'")->fetchColumn();

$featured_listings = $pdo->query("
    SELECT * FROM vehicle_listings
    WHERE status = 'approved' AND listing_type = 'user_sale'
    ORDER BY CASE WHEN verification_status = 'mechanic_verified' THEN 0 ELSE 1 END, date_added DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$model_data = $pdo->query("SELECT model, COUNT(*) AS cnt FROM vehicle_listings WHERE status = 'approved' GROUP BY model ORDER BY cnt DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
$trend_data = $pdo->query("SELECT year_manufactured, AVG(asking_price) AS avg_p FROM vehicle_listings WHERE status = 'approved' AND year_manufactured BETWEEN 2015 AND 2026 GROUP BY year_manufactured ORDER BY year_manufactured ASC")->fetchAll(PDO::FETCH_ASSOC);

$trend_labels = []; $trend_values = [];
foreach ($trend_data as $t) { $trend_labels[] = $t['year_manufactured']; $trend_values[] = round($t['avg_p'], 2); }

include 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .hero-banner {
    background-image: linear-gradient(to right, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.7) 50%, rgba(15, 23, 42, 0.1) 100%), 
                      url('https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=1600');
    background-size: cover;
    background-position: center right;
    border-radius: 24px; padding: 64px 56px; color: #fff;
    margin-bottom: 32px; position: relative; overflow: hidden;
    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; gap: 40px; flex-wrap: wrap; }
  .hero-copy { flex: 1; min-width: 300px; }
  .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #93C5FD; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 24px; backdrop-filter: blur(8px); }
  .hero-badge::before { content: ''; width: 8px; height: 8px; background: #10B981; border-radius: 50%; box-shadow: 0 0 12px #10B981; }
  .hero-title { font-family: var(--font-display); font-size: 52px; font-weight: 800; line-height: 1.1; letter-spacing: -1.5px; margin-bottom: 20px; text-shadow: 0 4px 20px rgba(0,0,0,0.4); }
  .hero-title span { background: linear-gradient(to right, #60A5FA, #A78BFA); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  .hero-desc { color: #E2E8F0; font-size: 16px; line-height: 1.6; max-width: 480px; margin-bottom: 32px; font-weight: 500; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
  .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }
  .hero-btn { padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; }
  .hero-btn:hover { transform: translateY(-2px); }
  .hero-btn.primary { background: var(--blue); color: #fff; box-shadow: 0 8px 20px -8px rgba(37, 99, 235, 0.5); }
  .hero-btn.secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px); }
  .hero-btn.secondary:hover { background: rgba(255,255,255,0.2); }
  .hero-stats-panel { display: flex; flex-direction: column; gap: 20px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; padding: 28px 32px; backdrop-filter: blur(16px); min-width: 260px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
  .hstat { display: flex; align-items: center; gap: 16px; }
  .hstat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(37, 99, 235, 0.25); flex-shrink: 0; border: 1px solid rgba(59, 130, 246, 0.4); }
  .hstat-icon.green { background: rgba(16, 185, 129, 0.25); border-color: rgba(16, 185, 129, 0.4); }
  .hstat-icon.amber { background: rgba(245, 158, 11, 0.25); border-color: rgba(245, 158, 11, 0.4); }
  .hstat-icon svg { width: 22px; height: 22px; }
  .hstat-val { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 4px; }
  .hstat-lbl { font-size: 12px; color: #CBD5E1; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
  .featured-section { margin-bottom: 40px; }
  .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
  .section-title { font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin: 0; letter-spacing: -0.8px; }
  .section-sub { font-size: 15px; color: var(--text-muted); margin-top: 6px; font-weight: 500; }
  .see-all { font-size: 14px; font-weight: 700; color: var(--blue); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
  .see-all:hover { gap: 10px; color: var(--blue-dark); }
  .featured-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
  .feat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; text-decoration: none; color: inherit; display: flex; flex-direction: column; position: relative; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease; }
  .feat-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: var(--border-hover); }
  .feat-img { width: 100%; height: 200px; background: var(--paper); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; border-bottom: 1px solid var(--border); }
  .feat-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
  .feat-card:hover .feat-img img { transform: scale(1.06); }
  .car-placeholder-ui { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: var(--text-muted); }
  .car-placeholder-ui svg { width: 48px; height: 48px; opacity: 0.3; }
  .car-placeholder-ui span { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-faint); background: var(--surface); padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border); }
  .feat-price { position: absolute; bottom: 16px; right: 16px; background: rgba(15, 23, 42, 0.85); color: #fff; font-family: var(--font-display); font-size: 18px; font-weight: 800; padding: 8px 14px; border-radius: 10px; backdrop-filter: blur(8px); z-index: 2; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); }
  .feat-body { padding: 24px; display: flex; flex-direction: column; flex: 1; }
  .feat-title { font-family: var(--font-display); font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.4px; }
  .feat-sub { font-size: 13.5px; color: var(--text-muted); margin-bottom: 20px; font-weight: 500; }
  .feat-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
  .feat-badge { font-size: 11px; font-weight: 800; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; letter-spacing: 0.3px; }
  .feat-badge.verified { background: var(--teal-soft); color: var(--teal); border: 1px solid var(--teal); }
  .feat-badge.unverified { background: var(--paper); color: var(--text-muted); border: 1px solid var(--border); }
  .feat-footer { margin-top: auto; border-top: 1px dashed var(--border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--text-muted); font-weight: 600; }
  .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 28px; margin-top: 24px; }
  .chart-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 28px; box-shadow: var(--shadow-sm); transition: var(--transition); }
  .chart-header { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; letter-spacing: -0.3px; }
  .chart-header .tag { font-size: 11px; font-weight: 800; color: var(--text-muted); background: var(--paper); padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.5px; }
  .chart-container { position: relative; height: 300px; width: 100%; }

  @media (max-width: 1024px) {
    .featured-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; }
    .hero-inner { flex-direction: column; align-items: flex-start; }
    .hero-stats-panel { width: 100%; flex-direction: row; flex-wrap: wrap; justify-content: space-between; }
    .hstat { flex: 1; min-width: 160px; }
  }
  @media (max-width: 640px) {
    .hero-banner { padding: 40px 24px; }
    .hero-title { font-size: 36px; }
    .featured-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="view">
  <div class="hero-banner">
    <div class="hero-inner">
      <div class="hero-copy">
        <div class="hero-badge">Live Toyota Market Data</div>
        <h1 class="hero-title">Find the right price.<br>Sell <span>smart</span>. Buy <span>better</span>.</h1>
        <p class="hero-desc">
          Real listings from real sellers across the Philippines. Browse verified Toyota vehicles,
          or get a fair price estimate for your own car in seconds.
        </p>
        <div class="hero-actions">
          <a href="browse.php" class="hero-btn primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:18px;height:18px;"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            Browse Cars
          </a>
          <a href="predict.php" class="hero-btn secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:18px;height:18px;"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
            Predict a Price
          </a>
        </div>
      </div>

      <div class="hero-stats-panel">
        <div class="hstat">
          <div class="hstat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#93C5FD" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo number_format($total_listings); ?></div>
            <div class="hstat-lbl">Active Listings</div>
          </div>
        </div>
        <div class="hstat">
          <div class="hstat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="#6EE7B7" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo number_format($verified_count); ?></div>
            <div class="hstat-lbl">Verified Cars</div>
          </div>
        </div>
        <div class="hstat">
          <div class="hstat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="#FCD34D" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          </div>
          <div>
            <div class="hstat-val"><?php echo $avg_price > 0 ? '₱' . number_format($avg_price / 1000, 0) . 'K' : '—'; ?></div>
            <div class="hstat-lbl">Avg. Price</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="featured-section">
    <div class="section-header">
      <div>
        <h2 class="section-title">Featured Listings</h2>
        <div class="section-sub">Hand-picked Toyota cars from verified and active sellers.</div>
      </div>
      <a href="browse.php" class="see-all">
        See all listings
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="featured-grid">
      <?php if (empty($featured_listings)): ?>
        <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; background: var(--surface); border: 1px dashed var(--border); border-radius: 20px; color: var(--text-muted);">
          <p>No featured listings yet — check back soon.</p>
        </div>
      <?php else: foreach ($featured_listings as $car):
          $has_photo = !empty($car['photo_front']);
          $is_verified = ($car['verification_status'] ?? '') === 'mechanic_verified';
      ?>
        <a class="feat-card" href="browse.php">
          <div class="feat-img">
            <?php if ($has_photo): ?>
              <img src="uploads/<?php echo htmlspecialchars($car['photo_front']); ?>" alt="<?php echo htmlspecialchars($car['brand'].' '.$car['model']); ?>">
            <?php else: ?>
              <div class="car-placeholder-ui">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18"/></svg>
                <span>Photos Unavailable</span>
              </div>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:12px;height:12px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                  Mechanic Verified
                </span>
              <?php else: ?>
                <span class="feat-badge unverified">Unverified</span>
              <?php endif; ?>
            </div>
            <div class="feat-footer">
              <span style="display:flex;align-items:center;gap:5px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo htmlspecialchars($car['city'] ?: 'N/A'); ?>
              </span>
              <span style="color:var(--blue);">View →</span>
            </div>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

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
        <span>Listings by Model</span>
        <span class="tag">Share %</span>
      </div>
      <div class="chart-container"><canvas id="modelDistChart"></canvas></div>
    </div>
  </div>
</div>

<script>
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDark ? '#94A3B8' : '#64748B';
  const gridColor = isDark ? '#334155' : '#E2E8F0';

  new Chart(document.getElementById('priceTrendChart').getContext('2d'), {
    type: 'line',
    data: {
      labels: <?php echo json_encode($trend_labels); ?>,
      datasets: [{
        label: 'Avg Market Price',
        data: <?php echo json_encode($trend_values); ?>,
        borderColor: isDark ? '#60A5FA' : '#2563EB',
        backgroundColor: isDark ? 'rgba(96, 165, 250, 0.1)' : 'rgba(37, 99, 235, 0.08)',
        borderWidth: 3, fill: true, tension: 0.4,
        pointBackgroundColor: isDark ? '#1E293B' : '#fff',
        pointBorderColor: isDark ? '#60A5FA' : '#2563EB',
        pointBorderWidth: 2, pointRadius: 4
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: false, grid: { color: gridColor }, ticks: { color: textColor, callback: v => '₱' + (v / 1000).toFixed(0) + 'K' } },
        x: { grid: { display: false }, ticks: { color: textColor } }
      }
    }
  });

  new Chart(document.getElementById('modelDistChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_keys($model_data)); ?>,
      datasets: [{
        data: <?php echo json_encode(array_values($model_data)); ?>,
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'],
        borderWidth: isDark ? 2 : 3,
        borderColor: isDark ? '#1E293B' : '#fff'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '70%',
      plugins: { legend: { position: 'bottom', labels: { color: textColor, boxWidth: 12, font: { size: 12, family: "'Plus Jakarta Sans', sans-serif" }, padding: 16 } } }
    }
  });
</script>

<?php include 'footer.php'; ?>