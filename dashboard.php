<?php
require_once 'db.php';
$current_page = 'dashboard';

// Fetch featured listings
$featured_listings = $pdo->query("
    SELECT * FROM vehicle_listings
    WHERE status = 'approved' AND listing_type = 'user_sale'
    ORDER BY CASE WHEN verification_status = 'mechanic_verified' THEN 0 ELSE 1 END, date_added DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

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

  @media (max-width: 1024px) {
    .featured-grid { grid-template-columns: repeat(2, 1fr); }
    .hero-inner { flex-direction: column; align-items: flex-start; }
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
</div>

<?php include 'footer.php'; ?>