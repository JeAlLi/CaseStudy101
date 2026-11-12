<?php
require_once 'db.php';
$current_page = 'browse';

$f_model    = trim($_GET['model'] ?? '');
$f_min      = trim($_GET['min_price'] ?? '');
$f_max      = trim($_GET['max_price'] ?? '');
$f_sort     = trim($_GET['sort'] ?? 'newest');
$f_verified = trim($_GET['verified'] ?? 'all'); // NEW

$where  = ["status = 'approved'", "listing_type IN ('user_sale', 'scraped')"];
$params = [];

if ($f_model !== '') { $where[] = "model = ?";           $params[] = $f_model; }
if ($f_min !== '' && is_numeric($f_min)) { $where[] = "asking_price >= ?"; $params[] = (float)$f_min; }
if ($f_max !== '' && is_numeric($f_max)) { $where[] = "asking_price <= ?"; $params[] = (float)$f_max; }

// NEW: Verification Filter
if ($f_verified === 'verified') { $where[] = "verification_status = 'mechanic_verified'"; }
if ($f_verified === 'unverified') { $where[] = "verification_status = 'unverified'"; }

$orderBy = "date_added DESC";
if ($f_sort === 'price_asc')  $orderBy = "asking_price ASC";
if ($f_sort === 'price_desc') $orderBy = "asking_price DESC";
if ($f_sort === 'mileage')    $orderBy = "mileage ASC";

$sql = "SELECT * FROM vehicle_listings WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

function risk_badge(int $score, string $level): string {
    $colors = [
        'Low'    => ['bg'=>'var(--teal-soft)', 'fg'=>'var(--teal)', 'border' => 'var(--teal)', 'icon'=>'✓'],
        'Medium' => ['bg'=>'var(--amber-soft)', 'fg'=>'var(--amber)', 'border' => 'var(--amber)', 'icon'=>'⚠'],
        'High'   => ['bg'=>'var(--coral-soft)', 'fg'=>'var(--coral)', 'border' => 'var(--coral)', 'icon'=>'✕'],
    ];
    $c = $colors[$level] ?? ['bg'=>'var(--surface-hover)', 'fg'=>'var(--text-muted)', 'border' => 'var(--border)', 'icon'=>'?'];
    return '<span class="risk-badge" data-level="'.htmlspecialchars($level).'" data-score="'.(int)$score.'" style="background:'.$c['bg'].'; color:'.$c['fg'].'; border: 1px solid '.$c['border'].';">'.$c['icon'].' Risk Score: '.htmlspecialchars($level).'</span>';
}

include 'header.php';
?>

<style>
    .marketplace-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .marketplace-title { font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text); letter-spacing: -0.5px; margin: 0; }
    .marketplace-sub { font-size: 15px; color: var(--text-muted); margin-top: 4px; }
    
    .search-bar-container { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 32px; box-shadow: var(--shadow-sm); display: flex; gap: 12px; flex-wrap: wrap; align-items: center; transition: var(--transition); }
    .search-select, .search-input { padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: var(--font-body); color: var(--text); background: var(--paper); outline: none; transition: var(--transition); }
    .search-select:focus, .search-input:focus { border-color: var(--blue); background: var(--surface); box-shadow: 0 0 0 3px var(--blue-soft); }
    .search-btn { padding: 12px 24px; background: var(--blue); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37,99,235,0.2); }
    .search-btn:hover { background: var(--blue-dark); box-shadow: var(--shadow-glow); }

    .listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
    
    .car-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; cursor: pointer; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; position: relative; }
    .car-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: var(--border-hover); }
    
    .car-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: rgba(37, 99, 235, 0.5); z-index: 10; pointer-events: none; }
    .car-card.card-verified::before { background: rgba(16, 185, 129, 0.7); }
    
    .car-image-box { position: relative; width: 100%; height: 220px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--paper); }
    .car-image-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .car-card:hover .car-image-box img { transform: scale(1.08); }
    
    .car-placeholder { background-color: var(--surface-hover); background-image: radial-gradient(var(--border-hover) 1.5px, transparent 1.5px); background-size: 24px 24px; border-bottom: 1px solid var(--border); }
    .placeholder-content { display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); gap: 8px; z-index: 2; }
    .placeholder-content svg { width: 48px; height: 48px; opacity: 0.6; }
    .placeholder-content span { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-faint); }
    
    .price-tag { position: absolute; bottom: 16px; right: 16px; background: var(--surface); color: var(--text); font-family: var(--font-display); font-size: 20px; font-weight: 800; padding: 8px 16px; border-radius: 8px; box-shadow: var(--shadow-md); z-index: 2; letter-spacing: -0.5px; border: 1px solid var(--border); }
    .save-btn { position: absolute; top: 12px; right: 12px; width: 36px; height: 36px; background: var(--surface); border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 2; color: var(--text-muted); transition: 0.2s; box-shadow: var(--shadow-sm); }
    .save-btn:hover { color: var(--coral); transform: scale(1.1); }
    
    .car-content { padding: 20px; display: flex; flex-direction: column; flex: 1; }
    .car-title { font-family: var(--font-display); font-size: 20px; font-weight: 800; color: var(--text); line-height: 1.2; margin: 0 0 4px 0; }
    .car-subtitle { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-bottom: 16px; }
    
    .spec-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .spec-pill { background: var(--paper); color: var(--text); font-size: 12.5px; font-weight: 600; padding: 6px 12px; border-radius: 8px; display: flex; align-items: center; gap: 6px; border: 1px solid var(--border); }
    .spec-pill svg { width: 14px; height: 14px; color: var(--text-muted); }
    
    .badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .verify-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 6px; letter-spacing: 0.02em; cursor: pointer; user-select: none; transition: transform 0.12s; }
    .verify-badge:hover { transform: translateY(-1px); }
    .vb-verified { background: var(--teal-soft); color: var(--teal); border: 1px solid var(--teal); }
    .vb-unverified { background: var(--paper); color: var(--text-muted); border: 1px solid var(--border); }
    .verify-badge svg { width: 14px; height: 14px; }

    .risk-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 6px; letter-spacing: 0.02em; cursor: pointer; user-select: none; transition: transform 0.12s; }
    .risk-badge:hover { transform: translateY(-1px); }

    .car-footer { margin-top: auto; border-top: 1px dashed var(--border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; }
    .car-location { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-muted); font-weight: 600; max-width: 60%; }
    .car-location svg { width: 15px; height: 15px; color: var(--text-faint); flex-shrink: 0; }
    
    .view-btn { background: var(--paper); color: var(--blue); border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; font-size: 12.5px; font-weight: 700; transition: 0.2s; display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .car-card:hover .view-btn { background: var(--blue); color: #fff; border-color: var(--blue); }

    /* TOOLTIPS & MODALS */
    #info-tooltip-global { position: fixed; background: var(--ink); color: #fff; border-radius: 12px; padding: 16px; width: 320px; z-index: 99999; box-shadow: var(--shadow-lg); opacity: 0; visibility: hidden; transition: opacity 0.15s, visibility 0.15s; pointer-events: none; }
    #info-tooltip-global.show { opacity: 1; visibility: visible; }
    .tt-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-faint); margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
    .tt-current { padding: 10px 12px; background: var(--ink-soft); border-radius: 8px; margin-bottom: 10px; font-size: 12.5px; color: #fff; line-height: 1.5; display: none; }
    .tt-row { display: grid; grid-template-columns: 62px 68px 1fr; gap: 8px; align-items: center; padding: 8px 10px; border-radius: 6px; margin-bottom: 4px; font-size: 12px; line-height: 1.3; border-left: 3px solid transparent; transition: background 0.15s, border-color 0.15s; }
    .tt-range { font-family: var(--font-mono); font-weight: 600; color: #cbd5e1; }
    .tt-tier { font-weight: 700; padding: 3px 8px; border-radius: 10px; text-align: center; font-size: 11px; }
    .tt-low { background: var(--teal-soft); color: var(--teal); }
    .tt-med { background: var(--amber-soft); color: var(--amber); }
    .tt-high { background: var(--coral-soft); color: var(--coral); }
    .tt-desc { color: var(--text-faint); font-size: 11px; }
    .tt-row.tt-active { font-weight: 700; }
    .tt-row.tt-active .tt-range { color: #ffffff; }
    .tt-row.tt-active .tt-desc { color: #e2e8f0; }
    .tt-row[data-tier="Low"].tt-active { background: rgba(34, 197, 94, 0.2); border-left-color: #22c55e; }
    .tt-row[data-tier="Medium"].tt-active { background: rgba(234, 179, 8, 0.2); border-left-color: #eab308; }
    .tt-row[data-tier="High"].tt-active { background: rgba(239, 68, 68, 0.2); border-left-color: #ef4444; }

    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.show { display: flex; }
    .modal-content { background: var(--surface); border-radius: 16px; max-width: 900px; width: 100%; max-height: 92vh; overflow-y: auto; position: relative; box-shadow: var(--shadow-lg); }
    .modal-close { position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; border-radius: 50%; background: var(--surface); border: 1px solid var(--border); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-muted); z-index: 20; box-shadow: var(--shadow-sm); transition: 0.2s; }
    .modal-close:hover { background: var(--paper); color: var(--text); }

    .modal-hero { height: 320px; border-radius: 16px 16px 0 0; overflow: hidden; position: relative; border-bottom: 1px solid var(--border); }
    .modal-hero img { width: 100%; height: 100%; object-fit: cover; }
    .modal-thumb-strip { display: flex; gap: 8px; padding: 12px 32px 0; overflow-x: auto; }
    .modal-thumb { width: 64px; height: 64px; border-radius: 6px; object-fit: cover; cursor: pointer; border: 2px solid transparent; flex-shrink: 0; transition: border-color 0.15s, opacity 0.15s; opacity: 0.7; }
    .modal-thumb:hover { opacity: 1; }
    .modal-thumb.active { border-color: var(--blue); opacity: 1; }
    
    .modal-body { padding: 24px 32px 32px; }
    .modal-title-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
    .modal-title { font-family: var(--font-display); font-size: 26px; font-weight: 800; margin-bottom: 6px; color: var(--text); }
    .modal-subtitle { font-size: 14px; color: var(--text-muted); font-weight: 500; }
    .modal-price-box { text-align: right; }
    .modal-price-lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-faint); margin-bottom: 4px; font-weight: 700; }
    .modal-price { font-size: 32px; font-weight: 800; color: var(--blue); font-family: var(--font-display); letter-spacing: -0.5px; }
    .modal-price-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .risk-panel { background: var(--paper); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; }
    .risk-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .risk-panel-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text); }
    .flag-item { font-size: 12.5px; padding: 10px 14px; margin-bottom: 8px; border-radius: 6px; background: var(--surface); border-left: 3px solid var(--amber); color: var(--text); line-height: 1.5; box-shadow: var(--shadow-sm); }
    .flag-item:last-child { margin-bottom: 0; }

    .modal-section-title { font-size: 13px; font-weight: 800; color: var(--text); text-transform: uppercase; letter-spacing: 0.06em; margin: 22px 0 12px; padding-top: 18px; border-top: 1px solid var(--border); }
    .modal-section-title:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }

    .modal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .modal-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .modal-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }

    .info-box { background: var(--paper); padding: 12px 14px; border-radius: 8px; border: 1px solid var(--border); }
    .info-box .lbl { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; }
    .info-box .val { font-size: 13.5px; font-weight: 600; color: var(--text); line-height: 1.4; }

    .pill-yes { display: inline-flex; align-items: center; gap: 4px; background: var(--teal-soft); color: var(--teal); border: 1px solid var(--teal); padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
    .pill-no { display: inline-flex; align-items: center; gap: 4px; background: var(--coral-soft); color: var(--coral); border: 1px solid var(--coral); padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
    .pill-neutral { display: inline-flex; align-items: center; gap: 4px; background: var(--surface-hover); color: var(--text-muted); border: 1px solid var(--border); padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }

    .notes-box { background: var(--paper); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; font-size: 13.5px; color: var(--text); line-height: 1.6; margin-bottom: 10px; }
    .notes-box .notes-lbl { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; }

    @media (max-width: 640px) { 
        .marketplace-header { flex-direction: column; align-items: flex-start; }
        .search-bar-container { flex-direction: column; align-items: stretch; }
        .search-select, .search-input, .search-btn { width: 100%; }
        .modal-grid { grid-template-columns: 1fr; } 
        .modal-grid-3, .modal-grid-4 { grid-template-columns: 1fr 1fr; }
        #info-tooltip-global { width: 280px; }
        .modal-body { padding: 20px; }
        .modal-hero { height: 220px; }
        .modal-price-box { text-align: left; }
    }
</style>

<div class="view">
  
  <div class="marketplace-header">
    <div>
      <h1 class="marketplace-title">Find your next car</h1>
      <div class="marketplace-sub">Browse <?php echo count($listings); ?> trusted listings from verified sellers.</div>
    </div>
  </div>

  <form method="GET" class="search-bar-container">
    <select name="model" class="search-select" style="flex: 1; min-width: 200px;">
      <option value="">All Toyota Models</option>
      <option value="Vios" <?php echo $f_model === 'Vios' ? 'selected' : ''; ?>>Vios</option>
      <option value="Innova" <?php echo $f_model === 'Innova' ? 'selected' : ''; ?>>Innova</option>
      <option value="Fortuner" <?php echo $f_model === 'Fortuner' ? 'selected' : ''; ?>>Fortuner</option>
      <option value="Hilux" <?php echo $f_model === 'Hilux' ? 'selected' : ''; ?>>Hilux</option>
      <option value="Wigo" <?php echo $f_model === 'Wigo' ? 'selected' : ''; ?>>Wigo</option>
      <option value="Avanza" <?php echo $f_model === 'Avanza' ? 'selected' : ''; ?>>Avanza</option>
    </select>
    
    <!-- NEW VERIFICATION FILTER -->
    <select name="verified" class="search-select" style="width: 160px;">
      <option value="all" <?php echo $f_verified === 'all' ? 'selected' : ''; ?>>All Status</option>
      <option value="verified" <?php echo $f_verified === 'verified' ? 'selected' : ''; ?>>Verified Only</option>
      <option value="unverified" <?php echo $f_verified === 'unverified' ? 'selected' : ''; ?>>Unverified Only</option>
    </select>

    <input type="number" name="min_price" placeholder="Min Price (₱)" value="<?php echo htmlspecialchars($f_min); ?>" class="search-input" style="width: 140px;">
    <input type="number" name="max_price" placeholder="Max Price (₱)" value="<?php echo htmlspecialchars($f_max); ?>" class="search-input" style="width: 140px;">
    
    <select name="sort" class="search-select" style="width: 150px;">
      <option value="newest" <?php echo $f_sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
      <option value="price_asc" <?php echo $f_sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
      <option value="price_desc" <?php echo $f_sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
      <option value="mileage" <?php echo $f_sort === 'mileage' ? 'selected' : ''; ?>>Lowest Mileage</option>
    </select>

    <button type="submit" class="search-btn">Search</button>
    <?php if ($f_model || $f_min || $f_max || $f_sort !== 'newest' || $f_verified !== 'all'): ?>
      <a href="browse.php" style="font-size: 13px; color: var(--text-muted); text-decoration: underline; margin-left: 8px; font-weight: 600;">Clear Filters</a>
    <?php endif; ?>
  </form>

  <div class="listings-grid">
    <?php if (empty($listings)): ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; background: var(--surface); border-radius: 16px; border: 1px solid var(--border);">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-faint)" stroke-width="1.5" style="width:64px;height:64px;margin-bottom:16px;"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        <h4 style="color: var(--text); margin-bottom: 8px; font-size: 18px;">No vehicles found</h4>
        <p style="margin-bottom: 24px; color: var(--text-muted);">Try adjusting your search filters.</p>
        <a href="browse.php" style="background: var(--blue); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">Clear Search</a>
      </div>
    <?php else: foreach ($listings as $idx => $row): 
        $has_photo = !empty($row['photo_front']);
        $img_src = $has_photo ? 'uploads/' . htmlspecialchars($row['photo_front']) : '';
    ?>
      <div class="car-card <?php echo (($row['verification_status'] ?? '') === 'mechanic_verified') ? 'card-verified' : ''; ?>" onclick="openListingModal(<?php echo $idx; ?>)">
        
        <div class="car-image-box <?php echo !$has_photo ? 'car-placeholder' : ''; ?>">
          <?php if ($has_photo): ?>
            <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?>">
          <?php else: ?>
            <div class="placeholder-content">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18M6.5 15.5h.01M17.5 15.5h.01"/></svg>
              <span>Photos Unavailable</span>
            </div>
          <?php endif; ?>
          <div class="price-tag">₱<?php echo number_format($row['asking_price']); ?></div>
          
          <div class="save-btn" onclick="event.stopPropagation();">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </div>
        </div>

        <div class="car-content">
          <h3 class="car-title"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></h3>
          <div class="car-subtitle"><?php echo htmlspecialchars($row['color']); ?> • <?php echo htmlspecialchars($row['fuel_type']); ?></div>

          <div class="spec-pills">
            <span class="spec-pill">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?php echo $row['year_manufactured']; ?>
            </span>
            <span class="spec-pill">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?php echo number_format($row['mileage']); ?> km
            </span>
            <span class="spec-pill">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <?php echo htmlspecialchars($row['transmission']); ?>
            </span>
          </div>

          <div class="badge-row" onclick="event.stopPropagation();">
            <?php if (($row['verification_status'] ?? '') === 'mechanic_verified'): ?>
              <span class="verify-badge vb-verified" data-status="verified">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2.5"/></svg>
                Mechanic Verified
              </span>
            <?php else: ?>
              <span class="verify-badge vb-unverified" data-status="unverified">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Unverified
              </span>
              <?php if (!empty($row['risk_level'])) echo risk_badge((int)$row['risk_score'], $row['risk_level']); ?>
            <?php endif; ?>
          </div>

          <div class="car-footer">
            <div class="car-location">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span><?php echo htmlspecialchars($row['city'] ?: 'N/A'); ?></span>
            </div>
            <div class="view-btn" onclick="event.stopPropagation(); openListingModal(<?php echo $idx; ?>)">
              View
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- KEEP ALL YOUR EXISTING MODAL HTML AND JS BELOW THIS LINE -->
<div id="info-tooltip-global" role="tooltip">
  <div id="tt-content"></div>
</div>

<div class="modal-overlay" id="listingModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-content">
    <button class="modal-close" onclick="closeModal()">×</button>
    
    <div id="m-image-box" class="car-image-box modal-hero">
        <img id="m-img" src="" style="display: none;">
        <div id="m-placeholder" class="placeholder-content" style="display: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18M6.5 15.5h.01M17.5 15.5h.01"/></svg>
            <span>Photos Unavailable</span>
        </div>
    </div>
    
    <div id="m-thumb-strip" class="modal-thumb-strip"></div>

    <div class="modal-body">
      <div class="modal-title-row">
        <div>
          <h2 id="m-title" class="modal-title"></h2>
          <div id="m-subtitle" class="modal-subtitle"></div>
        </div>
        <div class="modal-price-box">
          <div class="modal-price-lbl">Asking Price</div>
          <div id="m-price" class="modal-price"></div>
          <div id="m-price-meta" class="modal-price-meta"></div>
        </div>
      </div>
      
      <div id="m-risk-section" class="risk-panel">
        <div class="risk-panel-head">
          <div class="risk-panel-title">Risk Assessment</div>
          <div id="m-risk-badge"></div>
        </div>
        <div id="m-risk-flags"></div>
      </div>

      <div class="modal-section-title">Specifications</div>
      <div class="modal-grid-3">
        <div class="info-box"><div class="lbl">Year</div><div class="val" id="m-year"></div></div>
        <div class="info-box"><div class="lbl">Mileage</div><div class="val" id="m-mileage"></div></div>
        <div class="info-box"><div class="lbl">Color</div><div class="val" id="m-color"></div></div>
        <div class="info-box"><div class="lbl">Transmission</div><div class="val" id="m-trans"></div></div>
        <div class="info-box"><div class="lbl">Fuel Type</div><div class="val" id="m-fuel"></div></div>
        <div class="info-box"><div class="lbl">Modifications</div><div class="val" id="m-mods"></div></div>
      </div>

      <div class="modal-section-title">Condition &amp; History</div>
      <div class="modal-grid-4">
        <div class="info-box"><div class="lbl">First Owner</div><div class="val" id="m-first-owner"></div></div>
        <div class="info-box"><div class="lbl">Maintenance</div><div class="val" id="m-maint"></div></div>
        <div class="info-box"><div class="lbl">Accident History</div><div class="val" id="m-accident"></div></div>
        <div class="info-box"><div class="lbl">Flooded</div><div class="val" id="m-flooded"></div></div>
      </div>

      <div class="modal-section-title">Legal Documents</div>
      <div class="modal-grid-4">
        <div class="info-box"><div class="lbl">LTO Status</div><div class="val" id="m-lto"></div></div>
        <div class="info-box"><div class="lbl">OR / CR</div><div class="val" id="m-orcr"></div></div>
        <div class="info-box"><div class="lbl">Fully Paid</div><div class="val" id="m-paid"></div></div>
        <div class="info-box"><div class="lbl">Deed of Sale</div><div class="val" id="m-deed"></div></div>
      </div>

      <div class="modal-section-title">Pricing &amp; Payment</div>
      <div class="modal-grid-3">
        <div class="info-box"><div class="lbl">Negotiability</div><div class="val" id="m-nego"></div></div>
        <div class="info-box"><div class="lbl">Trade-In</div><div class="val" id="m-trade"></div></div>
        <div class="info-box"><div class="lbl">Warranty</div><div class="val" id="m-warranty"></div></div>
      </div>
      <div class="modal-grid" style="margin-top:12px;">
        <div class="info-box" style="grid-column: span 2;"><div class="lbl">Accepted Payment Methods</div><div class="val" id="m-payment"></div></div>
      </div>

      <div class="modal-section-title">Location</div>
      <div class="modal-grid">
        <div class="info-box"><div class="lbl">City / Municipality</div><div class="val" id="m-city"></div></div>
        <div class="info-box"><div class="lbl">Barangay</div><div class="val" id="m-barangay"></div></div>
      </div>
      <div class="info-box" style="margin-top:12px;"><div class="lbl">Full Address</div><div class="val" id="m-location"></div></div>

      <div class="modal-section-title">Seller Contact</div>
      <div class="modal-grid">
        <div class="info-box"><div class="lbl">Name</div><div class="val" id="m-seller"></div></div>
        <div class="info-box"><div class="lbl">Contact Number</div><div class="val" id="m-contact" style="font-family: var(--font-mono); color: var(--blue);"></div></div>
      </div>

      <div id="m-notes-section" style="display:none;">
        <div class="modal-section-title">Seller Notes</div>
        <div id="m-notes-wrap"></div>
      </div>
    </div>
  </div>
</div>

<script>
const LISTINGS = <?php echo json_encode($listings, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const tooltip = document.getElementById('info-tooltip-global');
const ttContent = document.getElementById('tt-content');
let pinnedTarget = null;

function yesNoPill(v) {
    const s = String(v ?? '').trim();
    if (/^(yes|true|1)$/i.test(s)) return '<span class="pill-yes">✓ Yes</span>';
    if (/^(no|false|0)$/i.test(s)) return '<span class="pill-no">✕ No</span>';
    if (s === '') return '<span class="pill-neutral">—</span>';
    return '<span class="pill-neutral">' + esc(s) + '</span>';
}
function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function setText(id, val, fallback = 'N/A') {
    document.getElementById(id).textContent = (val === null || val === undefined || val === '') ? fallback : val;
}
function setHtml(id, html) {
    document.getElementById(id).innerHTML = html;
}

function buildRiskTooltip(level, score) {
    let bg = 'var(--surface-hover)', fg = 'var(--text)';
    if (level === 'Low') { bg = 'var(--teal-soft)'; fg = 'var(--teal)'; }
    if (level === 'Medium') { bg = 'var(--amber-soft)'; fg = 'var(--amber)'; }
    if (level === 'High') { bg = 'var(--coral-soft)'; fg = 'var(--coral)'; }

    return `
        <div class="tt-title">Risk Score Scale</div>
        <div class="tt-current" style="display:block; background:${bg}; color:${fg}; border:1px solid ${fg};">
            ▶ CURRENT: <strong>${esc(level.toUpperCase())}</strong> · ${score}/100
        </div>
        <div class="tt-row ${level==='Low'?'tt-active':''}" data-tier="Low"><span class="tt-range">0 – 29</span><span class="tt-tier tt-low">Low</span><span class="tt-desc">Safe</span></div>
        <div class="tt-row ${level==='Medium'?'tt-active':''}" data-tier="Medium"><span class="tt-range">30 – 59</span><span class="tt-tier tt-med">Medium</span><span class="tt-desc">Review</span></div>
        <div class="tt-row ${level==='High'?'tt-active':''}" data-tier="High"><span class="tt-range">60 – 100</span><span class="tt-tier tt-high">High</span><span class="tt-desc">Reject</span></div>
    `;
}

function buildVerifyTooltip(status) {
    if (status === 'verified') {
        return `<div class="tt-title" style="color:var(--teal);">✓ Mechanic Verified</div><div style="font-size: 12.5px; line-height: 1.6; color: var(--text);">A real mechanic has physically inspected this vehicle. The risk score is hidden because human inspection is trusted.</div>`;
    } else {
        return `<div class="tt-title" style="color:var(--text-muted);">Unverified Listing</div><div style="font-size: 12.5px; line-height: 1.6; color: var(--text);">This data was self-reported. Because we haven't inspected it, the risk score is displayed to warn you of potential issues.</div>`;
    }
}

function showTooltip(target, contentHtml) {
    ttContent.innerHTML = contentHtml;
    tooltip.classList.add('show');
    const rect = target.getBoundingClientRect();
    let left = rect.left;
    if (left + tooltip.offsetWidth > window.innerWidth - 10) left = window.innerWidth - tooltip.offsetWidth - 10;
    if (left < 10) left = 10;
    let top = rect.top - tooltip.offsetHeight - 10;
    if (top < 10) top = rect.bottom + 10;
    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function hideTooltip() {
    tooltip.classList.remove('show');
    document.querySelectorAll('.pinned').forEach(b => b.classList.remove('pinned'));
    pinnedTarget = null;
}

document.addEventListener('mouseover', function(e) {
    if (pinnedTarget) return;
    const riskBadge = e.target.closest('.risk-badge[data-level]');
    if (riskBadge) {
        const lvl = riskBadge.getAttribute('data-level');
        const score = riskBadge.getAttribute('data-score');
        if (lvl && lvl !== 'Not evaluated') showTooltip(riskBadge, buildRiskTooltip(lvl, score));
        return;
    }
    const verifyBadge = e.target.closest('.verify-badge');
    if (verifyBadge) showTooltip(verifyBadge, buildVerifyTooltip(verifyBadge.dataset.status));
});

document.addEventListener('mouseout', function(e) {
    if (pinnedTarget) return;
    if (e.target.closest('.risk-badge[data-level]') || e.target.closest('.verify-badge')) tooltip.classList.remove('show');
});

document.addEventListener('click', function(e) {
    const riskBadge = e.target.closest('.risk-badge[data-level]');
    if (riskBadge) {
        e.stopPropagation();
        if (pinnedTarget === riskBadge) { hideTooltip(); }
        else {
            hideTooltip(); pinnedTarget = riskBadge; riskBadge.classList.add('pinned');
            const lvl = riskBadge.getAttribute('data-level');
            const score = riskBadge.getAttribute('data-score');
            if (lvl && lvl !== 'Not evaluated') showTooltip(riskBadge, buildRiskTooltip(lvl, score));
        }
        return;
    }
    const verifyBadge = e.target.closest('.verify-badge');
    if (verifyBadge) {
        e.stopPropagation();
        if (pinnedTarget === verifyBadge) { hideTooltip(); }
        else {
            hideTooltip(); pinnedTarget = verifyBadge; verifyBadge.classList.add('pinned');
            showTooltip(verifyBadge, buildVerifyTooltip(verifyBadge.dataset.status));
        }
        return;
    }
    if (pinnedTarget && !pinnedTarget.contains(e.target)) hideTooltip();
});

window.addEventListener('scroll', () => { if(!pinnedTarget) tooltip.classList.remove('show'); }, { passive: true });
document.querySelector('.modal-content').addEventListener('scroll', () => {
    if (tooltip.classList.contains('show')) hideTooltip();
}, { passive: true });

function buildPhotoGallery(r) {
    const fields = [['photo_front', 'Front'], ['photo_rear', 'Rear'], ['photo_side', 'Side'], ['photo_engine', 'Engine'], ['photo_interior', 'Interior'], ['photo_dashboard', 'Dashboard']];
    return fields.filter(function(f) { return r[f[0]] && r[f[0]] !== 'null' && r[f[0]] !== ''; }).map(function(f) { return { key: f[0], src: 'uploads/' + r[f[0]], label: f[1] }; });
}

function setMainPhoto(src) {
    const imgEl = document.getElementById('m-img');
    const phEl = document.getElementById('m-placeholder');
    const box = document.getElementById('m-image-box');
    if (src) {
        imgEl.src = src; imgEl.style.display = 'block'; phEl.style.display = 'none'; box.classList.remove('car-placeholder');
    } else {
        imgEl.style.display = 'none'; phEl.style.display = 'flex'; box.classList.add('car-placeholder');
    }
}

function openListingModal(idx) {
    const r = LISTINGS[idx];
    if (!r) return;

    const photos = buildPhotoGallery(r);
    const thumbStrip = document.getElementById('m-thumb-strip');
    thumbStrip.innerHTML = '';
    if (photos.length > 0) {
        setMainPhoto(photos[0].src);
        if (photos.length > 1) {
            photos.forEach(function(p, i) {
                const t = document.createElement('img');
                t.src = p.src; t.className = 'modal-thumb' + (i === 0 ? ' active' : ''); t.alt = p.label;
                t.onclick = function() {
                    setMainPhoto(p.src);
                    thumbStrip.querySelectorAll('.modal-thumb').forEach(function(el) { el.classList.remove('active'); });
                    t.classList.add('active');
                };
                thumbStrip.appendChild(t);
            });
        } else {
            thumbStrip.style.display = 'none';
        }
        thumbStrip.style.display = '';
    } else {
        setMainPhoto(null); thumbStrip.style.display = 'none';
    }

    setText('m-title', r.brand + ' ' + r.model);
    setText('m-subtitle', r.year_manufactured + ' • ' + Number(r.mileage).toLocaleString() + ' km • ' + (r.transmission || 'N/A') + ' • ' + (r.fuel_type || 'N/A'));
    setText('m-price', '₱' + Number(r.asking_price).toLocaleString());
    setText('m-price-meta', r.price_negotiability || 'Fixed Price');

    setText('m-year', r.year_manufactured);
    setText('m-mileage', Number(r.mileage).toLocaleString() + ' km');
    setText('m-color', r.color);
    setText('m-trans', r.transmission);
    setText('m-fuel', r.fuel_type);
    setText('m-mods', r.modifications || 'Stock / None');

    setHtml('m-first-owner', yesNoPill(r.first_owner));
    setText('m-maint', r.maintenance_history || 'N/A');
    const acc = (r.accident_history || 'None').trim();
    setHtml('m-accident', acc && acc.toLowerCase() !== 'none' ? '<span class="pill-no">' + esc(acc) + '</span>' : '<span class="pill-yes">✓ None</span>');
    setHtml('m-flooded', yesNoPill(r.flooded));

    setText('m-lto', r.registration_status || 'N/A');
    setHtml('m-orcr', yesNoPill(r.or_cr_ready));
    setHtml('m-paid', yesNoPill(r.fully_paid));
    setHtml('m-deed', yesNoPill(r.deed_of_sale_ready));

    setText('m-nego', r.price_negotiability || 'N/A');
    setHtml('m-trade', yesNoPill(r.trade_in));
    setText('m-warranty', r.warranty_status || 'No Warranty');
    setText('m-payment', r.payment_methods || 'Cash');

    setText('m-city', r.city);
    setText('m-barangay', r.barangay);
    setText('m-location', r.location || [r.city, r.district, r.barangay].filter(Boolean).join(', '));

    setText('m-seller', r.seller_name, 'Anonymous Seller');
    setText('m-contact', r.seller_contact);

    const notesWrap = document.getElementById('m-notes-wrap');
    const notesSection = document.getElementById('m-notes-section');
    const notesHtml = [];
    if (r.reason_for_selling) notesHtml.push(`<div class="notes-box"><span class="notes-lbl">Reason for Selling</span>${esc(r.reason_for_selling)}</div>`);
    if (r.seller_notes) notesHtml.push(`<div class="notes-box"><span class="notes-lbl">Additional Notes</span>${esc(r.seller_notes)}</div>`);
    
    if (notesHtml.length > 0) {
        notesWrap.innerHTML = notesHtml.join(''); notesSection.style.display = 'block';
    } else {
        notesSection.style.display = 'none';
    }

    const riskSection = document.getElementById('m-risk-section');
    if ((r.verification_status ?? '') === 'mechanic_verified') {
        riskSection.style.display = 'none';
    } else {
        riskSection.style.display = 'block';
        const riskMap = { 'Low': { bg: 'var(--teal-soft)', fg: 'var(--teal)', border: 'var(--teal)', icon: '✓' }, 'Medium': { bg: 'var(--amber-soft)', fg: 'var(--amber)', border: 'var(--amber)', icon: '⚠' }, 'High': { bg: 'var(--coral-soft)', fg: 'var(--coral)', border: 'var(--coral)', icon: '✕' } };
        const badge = document.getElementById('m-risk-badge');
        const flagsDiv = document.getElementById('m-risk-flags');

        if (r.risk_level && riskMap[r.risk_level]) {
            const c = riskMap[r.risk_level];
            badge.innerHTML = '<span class="risk-badge" data-level="' + r.risk_level + '" data-score="' + (r.risk_score ?? 0) + '" style="background:' + c.bg + '; color:' + c.fg + '; border: 1px solid ' + c.border + '; font-size: 13px; padding: 6px 14px;">' + c.icon + ' Risk Score: ' + r.risk_level + '</span>';
            const flags = (r.risk_flags || '').split('|').map(function(f) { return f.trim(); }).filter(function(f) { return f.length > 0; });
            flagsDiv.innerHTML = flags.length ? flags.map(function(f) { return '<div class="flag-item">' + esc(f) + '</div>'; }).join('') : '<div style="font-size: 13px; color: var(--text-muted); font-style: italic;">No risk flags detected</div>';
        } else {
            badge.innerHTML = '<span class="risk-badge" style="background:var(--surface-hover); color:var(--text-muted); border: 1px solid var(--border);">Not evaluated</span>';
            flagsDiv.innerHTML = '<div style="font-size: 13px; color: var(--text-muted); font-style: italic;">Not evaluated.</div>';
        }
    }

    document.getElementById('listingModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    document.querySelector('.modal-content').scrollTop = 0;
}

function closeModal() {
    hideTooltip();
    document.getElementById('listingModal').classList.remove('show');
    document.body.style.overflow = '';
}
</script>

<?php include 'footer.php'; ?>