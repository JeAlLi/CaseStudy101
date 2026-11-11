<?php
require_once 'db.php';
$current_page = 'browse';

$f_brand = trim($_GET['brand'] ?? '');
$f_model = trim($_GET['model'] ?? '');
$f_min   = trim($_GET['min_price'] ?? '');
$f_max   = trim($_GET['max_price'] ?? '');

$where  = ["status = 'approved'", "listing_type = 'user_sale'"];
$params = [];
if ($f_brand !== '') { $where[] = "brand = ?";           $params[] = $f_brand; }
if ($f_model !== '') { $where[] = "model LIKE ?";        $params[] = "%$f_model%"; }
if ($f_min !== '' && is_numeric($f_min)) { $where[] = "asking_price >= ?"; $params[] = (float)$f_min; }
if ($f_max !== '' && is_numeric($f_max)) { $where[] = "asking_price <= ?"; $params[] = (float)$f_max; }

$sql = "SELECT * FROM vehicle_listings WHERE " . implode(' AND ', $where) . " ORDER BY date_added DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$brands = $pdo->query("SELECT DISTINCT brand FROM vehicle_listings WHERE status='approved' AND listing_type='user_sale' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

function risk_badge(int $score, string $level): string {
    $colors = [
        'Low'    => ['bg'=>'#dcfce7', 'fg'=>'#166534', 'icon'=>'✓'],
        'Medium' => ['bg'=>'#fef3c7', 'fg'=>'#92400e', 'icon'=>'⚠'],
        'High'   => ['bg'=>'#fee2e2', 'fg'=>'#991b1b', 'icon'=>'✕'],
    ];
    $c = $colors[$level] ?? ['bg'=>'#e5e7eb', 'fg'=>'#374151', 'icon'=>'?'];
    $safeLevel = htmlspecialchars($level);
    $safeScore = (int)$score;
    return '<span class="risk-badge" '
         . 'style="background:' . $c['bg'] . '; color:' . $c['fg'] . ';" '
         . 'data-level="' . $safeLevel . '" '
         . 'data-score="' . $safeScore . '" '
         . 'title="Risk Score: ' . $safeScore . '/100 (' . $safeLevel . ') — click to pin">'
         . $c['icon'] . ' ' . $safeLevel . ' · ' . $safeScore . '/100'
         . '</span>';
}

include 'header.php';
?>

<style>
    .listing-card {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        background: var(--surface);
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        display: flex;
        flex-direction: column;
    }
    .listing-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(16, 20, 30, 0.12);
    }
    .risk-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: transform 0.12s;
        user-select: none;
    }
    .risk-badge:hover { transform: scale(1.05); }
    .risk-badge.pinned {
        outline: 2px solid #4f46e5;
        outline-offset: 2px;
    }

    /* How-risk panel */
    .how-risk-panel {
        background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
        border: 1px solid #fde68a;
        border-radius: 10px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .how-risk-panel summary {
        padding: 16px 20px;
        cursor: pointer;
        font-weight: 700;
        font-size: 14px;
        color: #854d0e;
        display: flex;
        align-items: center;
        gap: 10px;
        list-style: none;
        user-select: none;
    }
    .how-risk-panel summary::-webkit-details-marker { display: none; }
    .how-risk-panel summary:hover { background: rgba(255,255,255,0.5); }
    .how-risk-panel[open] summary { border-bottom: 1px solid #fde68a; }
    .how-icon {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: #eab308;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .how-chevron {
        margin-left: auto;
        font-size: 11px;
        font-weight: 500;
        color: #ca8a04;
        transition: transform 0.2s;
    }
    .how-risk-panel[open] .how-chevron { transform: rotate(180deg); }
    .how-body { padding: 20px; }
    .how-intro {
        font-size: 13.5px;
        color: #a16207;
        margin-bottom: 18px;
        line-height: 1.65;
    }
    .how-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .how-card {
        background: white;
        border: 1px solid #fef3c7;
        border-radius: 8px;
        padding: 14px 16px;
    }
    .how-card .hc-icon {
        width: 28px; height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }
    .how-card .hc-title {
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .how-card .hc-pts {
        font-family: var(--font-mono);
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
        white-space: nowrap;
    }
    .how-card .hc-desc {
        font-size: 11.5px;
        color: #6b7280;
        line-height: 1.55;
    }
    .how-bands {
        padding: 12px 14px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.7;
    }
    .how-bands strong { color: #374151; font-size: 12px; }
    .how-band-pill {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 11px;
        margin: 0 4px;
    }
    .how-note {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px dashed #fde68a;
        font-size: 11.5px;
        color: #ca8a04;
        line-height: 1.5;
    }

    /* ============ TOOLTIP ============ */
    #risk-tooltip-global {
        position: fixed;
        background: #111827;
        color: #fff;
        border-radius: 12px;
        padding: 14px;
        width: 340px;
        z-index: 99999;
        box-shadow: 0 12px 36px rgba(0,0,0,0.4);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s, visibility 0.15s;
        pointer-events: none;
    }
    #risk-tooltip-global.show { opacity: 1; visibility: visible; }
    .tt-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #374151;
    }
    .tt-current {
        padding: 10px 12px;
        background: rgba(255,255,255,0.08);
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 12.5px;
        color: #e5e7eb;
        line-height: 1.5;
        display: none;
    }
    .tt-current strong { font-weight: 800; }
    .tt-row {
        display: grid;
        grid-template-columns: 62px 68px 1fr;
        gap: 8px;
        align-items: center;
        padding: 8px 10px;
        border-radius: 6px;
        margin-bottom: 4px;
        font-size: 12px;
        line-height: 1.3;
        border-left: 3px solid transparent;
        transition: background 0.15s, border-color 0.15s;
    }
    .tt-row:last-of-type { margin-bottom: 0; }
    .tt-range { font-family: var(--font-mono); font-weight: 600; color: #d1d5db; }
    .tt-tier { font-weight: 700; padding: 3px 8px; border-radius: 10px; text-align: center; font-size: 11px; }
    .tt-low  { background: #dcfce7; color: #166534; }
    .tt-med  { background: #fef3c7; color: #92400e; }
    .tt-high { background: #fee2e2; color: #991b1b; }
    .tt-desc { color: #9ca3af; font-size: 11px; }

    /* Active-row highlight — bright, tier-colored, cannot be missed */
    .tt-row.tt-active {
        font-weight: 700;
    }
    .tt-row.tt-active .tt-range { color: #ffffff; }
    .tt-row.tt-active .tt-desc { color: #e5e7eb; }
    .tt-row[data-tier="Low"].tt-active {
        background: rgba(34, 197, 94, 0.25);
        border-left-color: #22c55e;
        box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.4);
    }
    .tt-row[data-tier="Medium"].tt-active {
        background: rgba(234, 179, 8, 0.30);
        border-left-color: #eab308;
        box-shadow: inset 0 0 0 1px rgba(234, 179, 8, 0.5);
    }
    .tt-row[data-tier="High"].tt-active {
        background: rgba(239, 68, 68, 0.25);
        border-left-color: #ef4444;
        box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.4);
    }
    .tt-row.tt-active::before {
        content: '▶';
        position: absolute;
        margin-left: -6px;
        font-size: 9px;
        color: #ffffff;
    }

    .photo-placeholder {
        width: 100%; min-height: 200px;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-radius: 10px;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        color: #9ca3af; gap: 10px;
    }
    .photo-placeholder svg { width: 56px; height: 56px; opacity: 0.4; }

    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(17, 24, 39, 0.75); z-index: 1000;
        align-items: center; justify-content: center;
        padding: 20px; animation: fadeIn 0.2s ease;
    }
    .modal-overlay.show { display: flex; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-content {
        background: #fff; border-radius: 16px; max-width: 900px; width: 100%;
        max-height: 92vh; overflow-y: auto; position: relative;
        animation: slideUp 0.25s ease; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .modal-close {
        position: absolute; top: 16px; right: 16px; width: 36px; height: 36px;
        border-radius: 50%; background: rgba(255,255,255,0.95); border: 1px solid #e5e7eb;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 22px; line-height: 1; color: #374151; z-index: 10;
    }
    .modal-close:hover { background: #f3f4f6; }
    .modal-header-photo {
        height: 240px; border-radius: 16px 16px 0 0;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        display: flex; align-items: center; justify-content: center;
        color: #9ca3af; flex-direction: column; gap: 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .modal-header-photo svg { width: 72px; height: 72px; opacity: 0.5; }
    .modal-body { padding: 24px; }
    .modal-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px; }
    .info-box { background: #f9fafb; padding: 12px 14px; border-radius: 8px; border: 1px solid #e5e7eb; }
    .info-box .lbl { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 700; margin-bottom: 4px; }
    .info-box .val { font-size: 14px; font-weight: 600; color: #111827; }
    .risk-section { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .flag-item { font-size: 13px; padding: 8px 12px; margin-bottom: 6px; border-radius: 6px; background: #fff; border-left: 3px solid #f59e0b; color: #374151; line-height: 1.5; }
    .flag-item:last-child { margin-bottom: 0; }
    @media (max-width: 640px) {
        .modal-grid { grid-template-columns: 1fr; }
        .modal-header-photo { height: 180px; }
        #risk-tooltip-global { width: 300px; }
        .how-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="view" style="display: block;">
  <div class="panel">
    <div class="panel-head">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="var(--indigo)" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg></div>
      <h3>Cars For Sale</h3>
    </div>
    <div class="panel-sub">Browse approved listings. Hover or click the risk badge to see the score scale.</div>

    <!-- ============ HOW RISK ASSESSMENT WORKS ============ -->
    <details class="how-risk-panel">
      <summary>
        <span class="how-icon">?</span>
        How risk assessment works
        <span class="how-chevron">▼ Click to expand</span>
      </summary>
      <div class="how-body">
        <p class="how-intro">
          Every listing is scored <strong>0–100</strong> by an automated heuristic engine before appearing here.
          The score combines five weighted risk factors — <strong>higher score = riskier listing</strong>.
          Listings scoring 60+ are automatically rejected and never reach this page.
        </p>

        <div class="how-grid">
          <div class="how-card">
            <div class="hc-icon" style="background:#eef2ff; color:#4f46e5;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
              </svg>
            </div>
            <div class="hc-title">
              Price vs Market
              <span class="hc-pts" style="background:#eef2ff; color:#4f46e5;">up to +40</span>
            </div>
            <div class="hc-desc">Compares the asking price to the average of comparable listings in the same region. Flags scams (way too cheap) and overpricing (way too expensive).</div>
          </div>

          <div class="how-card">
            <div class="hc-icon" style="background:#fef3c7; color:#92400e;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <path d="M3 17l6-6 4 4 8-8"/>
              </svg>
            </div>
            <div class="hc-title">
              Mileage Anomaly
              <span class="hc-pts" style="background:#fef3c7; color:#92400e;">up to +25</span>
            </div>
            <div class="hc-desc">Calculates km/year and flags extreme cases — under 500 km/yr (possible odometer rollback) or over 40,000 km/yr (heavy commercial use).</div>
          </div>

          <div class="how-card">
            <div class="hc-icon" style="background:#ecfdf5; color:#059669;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <path d="M2 12c2-2 4-2 6 0s4 2 6 0 4-2 6 0M2 17c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/>
              </svg>
            </div>
            <div class="hc-title">
              Flood Risk
              <span class="hc-pts" style="background:#ecfdf5; color:#059669;">up to +50</span>
            </div>
            <div class="hc-desc">Matches the location (city / district / barangay) against PAGASA, Project NOAH, and DENR-MGB flood hotspots. Also adds points if the seller declares flood damage.</div>
          </div>

          <div class="how-card">
            <div class="hc-icon" style="background:#fff7ed; color:#ea580c;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
              </svg>
            </div>
            <div class="hc-title">
              Age &amp; Accident
              <span class="hc-pts" style="background:#fff7ed; color:#ea580c;">up to +30</span>
            </div>
            <div class="hc-desc">Vehicles 15+ years old carry elevated registration/maintenance risk. Major accident history adds a significant penalty.</div>
          </div>

          <div class="how-card">
            <div class="hc-icon" style="background:#fef2f2; color:#dc2626;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>
              </svg>
            </div>
            <div class="hc-title">
              Data Consistency
              <span class="hc-pts" style="background:#fef2f2; color:#dc2626;">up to +15</span>
            </div>
            <div class="hc-desc">Validates that the year/model combo actually exists. Flags impossible combinations (e.g. 2024 Vios with 2015 features) or unknown models.</div>
          </div>

          <div class="how-card" style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-color: #86efac;">
            <div class="hc-icon" style="background:#dcfce7; color:#16a34a;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
            <div class="hc-title" style="color:#065f46;">
              Why it matters
            </div>
            <div class="hc-desc" style="color:#166534;">Protects buyers from scams, hidden flood damage, and odometer tampering. Low-risk listings (0–29) auto-proceed. Medium-risk (30–59) get manual review before approval.</div>
          </div>
        </div>

        <div class="how-bands">
          <strong>Score bands:</strong>
          <span class="how-band-pill" style="background:#dcfce7; color:#166534;">0–29 · Low</span>
          Proceed to ML model
          <span class="how-band-pill" style="background:#fef3c7; color:#92400e; margin-left: 12px;">30–59 · Medium</span>
          Flag for manual review
          <span class="how-band-pill" style="background:#fee2e2; color:#991b1b; margin-left: 12px;">60–100 · High</span>
          Reject listing

          <div class="how-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;vertical-align:middle;display:inline-block;margin-right:4px;">
              <path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>
            </svg>
            <strong>Tip:</strong> Hover or click any risk badge on a listing card to see the full score scale with the current tier highlighted.
          </div>
        </div>
      </div>
    </details>

    <!-- Filters -->
    <form method="GET" style="background: var(--paper); padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; border: 1px solid var(--border);">
      <select name="brand" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none;">
        <option value="">All Brands</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $b === $f_brand ? 'selected' : ''; ?>><?php echo htmlspecialchars($b); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="model" placeholder="Search model..." value="<?php echo htmlspecialchars($f_model); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; flex: 1; min-width: 150px;">
      <input type="number" name="min_price" placeholder="Min Price ₱" value="<?php echo htmlspecialchars($f_min); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; width: 130px;">
      <input type="number" name="max_price" placeholder="Max Price ₱" value="<?php echo htmlspecialchars($f_max); ?>" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; outline: none; width: 130px;">
      <button type="submit" style="padding: 9px 18px; background: var(--indigo); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Search</button>
      <?php if ($f_brand || $f_model || $f_min || $f_max): ?>
        <a href="browse.php" style="font-size: 13px; color: var(--text-muted); text-decoration: underline;">Clear</a>
      <?php endif; ?>
    </form>

    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
      <?php echo count($listings); ?> listing<?php echo count($listings) === 1 ? '' : 's'; ?> found
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
      <?php if (empty($listings)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-faint);">
          <div style="margin-bottom: 16px; color: #d1d5db;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;">
              <path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18M6.5 15.5h.01M17.5 15.5h.01"/>
            </svg>
          </div>
          <h4 style="color: var(--text); margin-bottom: 8px;">No cars for sale yet</h4>
          <p style="margin-bottom: 16px;">Be the first to list your vehicle.</p>
          <a href="submit_listing.php" class="predict-btn" style="display: inline-flex; text-decoration: none; padding: 11px 22px;">Sell Your Car</a>
        </div>
      <?php else: foreach ($listings as $idx => $row): ?>
        <div class="listing-card" onclick="openListingModal(<?php echo $idx; ?>)">

          <div class="photo-placeholder" style="margin-bottom: 14px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="6" width="18" height="14" rx="2"/>
              <circle cx="12" cy="13" r="3"/>
              <path d="M8 6l1.5-2h5L16 6"/>
            </svg>
            <span style="font-size: 12px; font-weight: 500;">No Photo Available</span>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; gap: 8px;">
            <div style="flex: 1; min-width: 0;">
              <h4 style="font-family: var(--font-display); font-size: 16px; margin-bottom: 4px;">
                <?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?>
              </h4>
              <div style="font-size: 12px; color: var(--text-muted);">
                <?php echo (int)$row['year_manufactured']; ?> •
                <?php echo number_format($row['mileage']); ?> km •
                <?php echo htmlspecialchars($row['transmission']); ?>
              </div>
            </div>
            <div class="badge" style="background: var(--teal-soft); color: var(--teal); font-size: 13px; font-weight: 700; padding: 5px 10px; white-space: nowrap;">
              ₱<?php echo number_format($row['asking_price']); ?>
            </div>
          </div>

          <div style="margin-bottom: 12px;" onclick="event.stopPropagation();">
            <?php
              if (!empty($row['risk_level'])) {
                  echo risk_badge((int)$row['risk_score'], $row['risk_level']);
              } else {
                  echo '<span class="risk-badge" '
                     . 'style="background:#f3f4f6; color:#6b7280;" '
                     . 'data-level="" '
                     . 'data-score="0" '
                     . 'title="Not evaluated — click to learn more">'
                     . 'Not evaluated</span>';
              }
            ?>
          </div>

          <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 13px; height: 13px; flex-shrink: 0;">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              <?php
                $loc_parts = array_filter([$row['city'] ?? '', $row['district'] ?? '', $row['barangay'] ?? '']);
                echo htmlspecialchars(implode(', ', $loc_parts)) ?: 'N/A';
              ?>
            </span>
          </div>

          <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border); font-size: 11.5px; color: var(--indigo); font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 13px; height: 13px;">
              <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            Click for full details
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Global tooltip -->
<div id="risk-tooltip-global" role="tooltip">
  <div class="tt-title">Risk Score Scale</div>
  <div class="tt-current" id="tt-current"></div>
  <div class="tt-row" data-tier="Low">
    <span class="tt-range">0 – 29</span>
    <span class="tt-tier tt-low">Low</span>
    <span class="tt-desc">Safe — proceed</span>
  </div>
  <div class="tt-row" data-tier="Medium">
    <span class="tt-range">30 – 59</span>
    <span class="tt-tier tt-med">Medium</span>
    <span class="tt-desc">Manual review</span>
  </div>
  <div class="tt-row" data-tier="High">
    <span class="tt-range">60 – 100</span>
    <span class="tt-tier tt-high">High</span>
    <span class="tt-desc">Reject listing</span>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="listingModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-content">
    <button class="modal-close" onclick="closeModal()">×</button>
    <div class="modal-header-photo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="3" y="6" width="18" height="14" rx="2"/>
        <circle cx="12" cy="13" r="3"/>
        <path d="M8 6l1.5-2h5L16 6"/>
      </svg>
      <span style="font-size: 13px; font-weight: 500;">No Photo Available</span>
    </div>
    <div class="modal-body">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
        <div>
          <h2 id="m-title" style="font-family: var(--font-display); font-size: 24px; font-weight: 800; margin-bottom: 6px;"></h2>
          <div id="m-subtitle" style="font-size: 14px; color: var(--text-muted);"></div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-faint); margin-bottom: 4px;">Asking Price</div>
          <div id="m-price" style="font-size: 28px; font-weight: 800; color: var(--teal); font-family: var(--font-mono);"></div>
        </div>
      </div>
      <div class="risk-section">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text);">Risk Assessment</div>
          <div id="m-risk-badge"></div>
        </div>
        <div id="m-risk-flags"></div>
        <div id="m-risk-rec" style="margin-top: 12px; font-size: 12.5px; color: var(--text-muted); font-style: italic;"></div>
      </div>
      <div class="modal-grid">
        <div class="info-box"><div class="lbl">Year</div><div class="val" id="m-year"></div></div>
        <div class="info-box"><div class="lbl">Mileage</div><div class="val" id="m-mileage"></div></div>
        <div class="info-box"><div class="lbl">Transmission</div><div class="val" id="m-trans"></div></div>
        <div class="info-box"><div class="lbl">Fuel Type</div><div class="val" id="m-fuel"></div></div>
        <div class="info-box"><div class="lbl">Color</div><div class="val" id="m-color"></div></div>
        <div class="info-box"><div class="lbl">Condition</div><div class="val" id="m-cond"></div></div>
        <div class="info-box"><div class="lbl">Registration</div><div class="val" id="m-reg"></div></div>
        <div class="info-box"><div class="lbl">Price</div><div class="val" id="m-negotiable"></div></div>
      </div>
      <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text);">Location</h3>
      <div class="info-box" style="margin-bottom: 20px;">
        <div class="val" id="m-location" style="font-size: 14px;"></div>
        <div class="lbl" id="m-landmark" style="margin-top: 6px; text-transform: none; letter-spacing: 0;"></div>
      </div>
      <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text);">Seller</h3>
      <div class="modal-grid">
        <div class="info-box"><div class="lbl">Name</div><div class="val" id="m-seller"></div></div>
        <div class="info-box"><div class="lbl">Contact</div><div class="val" id="m-contact" style="font-family: var(--font-mono); color: var(--indigo);"></div></div>
      </div>
      <div id="m-rfs-wrap" style="margin-top: 16px; display: none;">
        <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 8px; color: var(--text);">Reason for Selling</h3>
        <div class="info-box"><div class="val" id="m-rfs" style="font-weight: 400; line-height: 1.5;"></div></div>
      </div>
      <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 11.5px; color: var(--text-faint); text-align: center;" id="m-listed"></div>
    </div>
  </div>
</div>

<script>
const LISTINGS = <?php echo json_encode($listings, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

// ============ TOOLTIP — hover AND click-to-pin (Event Delegation) ============
const tooltip = document.getElementById('risk-tooltip-global');
const currentEl = document.getElementById('tt-current');
let pinnedBadge = null;

function showTooltip(badge) {
    const level = (badge.getAttribute('data-level') || '').trim();
    const score = (badge.getAttribute('data-score') || '0').trim();

    tooltip.querySelectorAll('.tt-row').forEach(r => r.classList.remove('tt-active'));

    if (!level || level.toLowerCase() === 'not evaluated') {
        currentEl.innerHTML = 'This listing has not been risk-scored yet.';
        currentEl.style.display = 'block';
        currentEl.style.background = 'rgba(239, 68, 68, 0.15)';
        currentEl.style.color = '#fca5a5';
    } else {
        const activeRow = Array.from(tooltip.querySelectorAll('.tt-row')).find(
            r => (r.getAttribute('data-tier') || '').toLowerCase() === level.toLowerCase()
        );
        if (activeRow) activeRow.classList.add('tt-active');

        const tierColors = {
            'low':    { bg: 'rgba(34, 197, 94, 0.20)',  fg: '#4ade80' },
            'medium': { bg: 'rgba(234, 179, 8, 0.20)',  fg: '#fbbf24' },
            'high':   { bg: 'rgba(239, 68, 68, 0.20)',  fg: '#f87171' }
        };
        const c = tierColors[level.toLowerCase()] || { bg: 'rgba(255,255,255,0.08)', fg: '#e5e7eb' };

        currentEl.innerHTML = '▶ YOUR LISTING: <strong style="color:' + c.fg + ';">' + level.toUpperCase() + '</strong> · ' + score + '/100';
        currentEl.style.background = c.bg;
        currentEl.style.color = '#e5e7eb';
        currentEl.style.display = 'block';
    }

    tooltip.classList.add('show');

    // Position above or below the badge
    const rect = badge.getBoundingClientRect();
    const ttWidth = tooltip.offsetWidth;
    let left = rect.left;
    if (left + ttWidth > window.innerWidth - 10) left = window.innerWidth - ttWidth - 10;
    if (left < 10) left = 10;

    const ttHeight = tooltip.offsetHeight;
    let top = rect.top - ttHeight - 10;
    if (top < 10) top = rect.bottom + 10;

    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function hideTooltip() {
    tooltip.classList.remove('show');
    document.querySelectorAll('.risk-badge.pinned').forEach(b => b.classList.remove('pinned'));
    pinnedBadge = null;
}

// 🎯 EVENT DELEGATION — works on cards AND modal badges
document.addEventListener('mouseover', function(e) {
    const badge = e.target.closest('.risk-badge[data-level]');
    if (badge && !pinnedBadge) showTooltip(badge);
});

document.addEventListener('mouseout', function(e) {
    const badge = e.target.closest('.risk-badge[data-level]');
    if (badge && !pinnedBadge) tooltip.classList.remove('show');
});

document.addEventListener('click', function(e) {
    const badge = e.target.closest('.risk-badge[data-level]');
    
    if (badge) {
        // Clicked a badge → toggle pin
        e.stopPropagation();
        if (pinnedBadge === badge) {
            hideTooltip();
        } else {
            document.querySelectorAll('.risk-badge.pinned').forEach(b => b.classList.remove('pinned'));
            pinnedBadge = badge;
            badge.classList.add('pinned');
            showTooltip(badge);
        }
    } else if (pinnedBadge && !pinnedBadge.contains(e.target)) {
        // Clicked elsewhere → unpin
        hideTooltip();
    }
});

window.addEventListener('scroll', function() {
    tooltip.classList.remove('show');
}, { passive: true });

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideTooltip();
        closeModal();
    }
});

// ============ MODAL ============
function openListingModal(idx) {
    const r = LISTINGS[idx];
    if (!r) return;

    document.getElementById('m-title').textContent = r.brand + ' ' + r.model;
    document.getElementById('m-subtitle').textContent = r.year_manufactured + ' • ' + Number(r.mileage).toLocaleString() + ' km • ' + (r.transmission || 'N/A') + ' • ' + (r.fuel_type || 'N/A');
    document.getElementById('m-price').textContent = '₱' + Number(r.asking_price).toLocaleString();
    document.getElementById('m-year').textContent = r.year_manufactured || 'N/A';
    document.getElementById('m-mileage').textContent = Number(r.mileage).toLocaleString() + ' km';
    document.getElementById('m-trans').textContent = r.transmission || 'N/A';
    document.getElementById('m-fuel').textContent = r.fuel_type || 'N/A';
    document.getElementById('m-color').textContent = r.color || 'N/A';
    document.getElementById('m-cond').textContent = r.maintenance_history || 'N/A';
    document.getElementById('m-reg').textContent = r.registration_status || 'N/A';
    document.getElementById('m-negotiable').textContent = r.price_negotiability || 'Fixed Price';

    const loc = [r.city, r.district, r.barangay].filter(Boolean).join(', ');
    document.getElementById('m-location').textContent = loc || 'N/A';

    const landmarkEl = document.getElementById('m-landmark');
    if (r.location && r.location !== loc) {
        landmarkEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;vertical-align:middle;display:inline-block;margin-right:3px;color:#6b7280;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' + escapeHtml(r.location);
    } else {
        landmarkEl.innerHTML = '';
    }

    document.getElementById('m-seller').textContent = r.seller_name || 'Anonymous Seller';
    document.getElementById('m-contact').textContent = r.seller_contact || 'N/A';

    const riskMap = {
        'Low':    { bg: '#dcfce7', fg: '#166534', icon: '✓' },
        'Medium': { bg: '#fef3c7', fg: '#92400e', icon: '⚠' },
        'High':   { bg: '#fee2e2', fg: '#991b1b', icon: '✕' }
    };
    const badge = document.getElementById('m-risk-badge');
    const flagsDiv = document.getElementById('m-risk-flags');
    const recDiv = document.getElementById('m-risk-rec');

    if (r.risk_level && riskMap[r.risk_level]) {
        const c = riskMap[r.risk_level];
        // 🎯 ADDED data-level, data-score AND REMOVED cursor:default
        badge.innerHTML = '<span class="risk-badge" '
            + 'style="background:' + c.bg + '; color:' + c.fg + '; font-size: 13px; padding: 6px 14px;" '
            + 'data-level="' + r.risk_level + '" '
            + 'data-score="' + r.risk_score + '" '
            + 'title="Risk Score: ' + r.risk_score + '/100 (' + r.risk_level + ') — click to pin">'
            + c.icon + ' ' + r.risk_level + ' · ' + r.risk_score + '/100'
            + '</span>';
            
        const flags = (r.risk_flags || '').split('|').map(f => f.trim()).filter(f => f.length > 0);
        flagsDiv.innerHTML = flags.length
            ? flags.map(f => '<div class="flag-item">' + escapeHtml(f) + '</div>').join('')
            : '<div style="font-size: 13px; color: #6b7280; font-style: italic;">No risk flags detected</div>';
        recDiv.textContent = 'Recommendation: ' + (r.risk_recommendation || 'N/A');
    } else {
        badge.innerHTML = '<span class="risk-badge" '
            + 'style="background:#f3f4f6; color:#6b7280; font-size: 13px; padding: 6px 14px;" '
            + 'data-level="" data-score="0" '
            + 'title="Not evaluated — click to learn more">'
            + 'Not evaluated</span>';
        flagsDiv.innerHTML = '<div style="font-size: 13px; color: #6b7280; font-style: italic;">This listing has not been risk-scored.</div>';
        recDiv.textContent = '';
    }

    const rfsWrap = document.getElementById('m-rfs-wrap');
    if (r.reason_for_selling) {
        rfsWrap.style.display = 'block';
        document.getElementById('m-rfs').textContent = r.reason_for_selling;
    } else {
        rfsWrap.style.display = 'none';
    }

    if (r.date_added) {
        const d = new Date(r.date_added.replace(' ', 'T'));
        document.getElementById('m-listed').textContent = 'Listed on ' + d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    document.getElementById('listingModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    hideTooltip(); // Hide tooltip if it was pinned when modal closes
    document.getElementById('listingModal').classList.remove('show');
    document.body.style.overflow = '';
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}
</script>

<?php include 'footer.php'; ?>