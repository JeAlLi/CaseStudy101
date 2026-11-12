<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../db.php';

// Strict Session Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

try {
    $total_listings = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_listings")->fetchColumn();
    $pending_count = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_listings WHERE status = 'pending'")->fetchColumn();
    $approved_count = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_listings WHERE status = 'approved'")->fetchColumn();
    $verified_count = (int)$pdo->query("SELECT COUNT(*) FROM vehicle_listings WHERE verification_status = 'mechanic_verified'")->fetchColumn();
    
    $recent_pending = $pdo->query("SELECT * FROM vehicle_listings WHERE status = 'pending' ORDER BY date_added DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("<h3>Database Error:</h3> <p>" . $e->getMessage() . "</p>");
}

include 'header.php';
?>

<style>
  .admin-dash { max-width: 1280px; margin: 0 auto; padding: 24px 20px; }
  .dash-hero { background: var(--ink); border-radius: 20px; padding: 40px; color: #fff; margin-bottom: 24px; box-shadow: var(--shadow-md); border: 1px solid var(--ink-line); }
  .dash-hero h1 { font-family: var(--font-display); font-size: 32px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.5px; }
  .dash-hero p { color: #CBD5E1; font-size: 15px; max-width: 600px; line-height: 1.6; }

  .dash-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
  .dash-stat { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; transition: var(--transition); }
  .dash-stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--border-hover); }
  .dash-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
  .dash-stat-icon svg { width: 24px; height: 24px; }
  .dash-stat-val { font-family: var(--font-display); font-size: 32px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
  .dash-stat-lbl { font-size: 13px; color: var(--text-muted); font-weight: 600; }

  .quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
  .qa-card { display: flex; align-items: center; gap: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 20px 24px; text-decoration: none; color: inherit; transition: var(--transition); }
  .qa-card:hover { transform: translateY(-2px); border-color: var(--blue); box-shadow: var(--shadow-md); }
  .qa-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .qa-icon svg { width: 24px; height: 24px; }
  .qa-title { font-family: var(--font-display); font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
  .qa-sub { font-size: 13px; color: var(--text-muted); font-weight: 500; }

  .dash-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); }
  .dash-panel-head { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid var(--border); }
  .dash-panel-title { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text); }
  
  .pending-list { padding: 8px 0; }
  .pending-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); text-decoration: none; color: inherit; transition: var(--transition); }
  .pending-item:hover { background: var(--paper); }
  .pending-left { display: flex; align-items: center; gap: 16px; }
  .pending-avatar { width: 44px; height: 44px; border-radius: 10px; background: var(--blue-soft); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--blue); font-weight: 800; font-size: 14px; overflow: hidden; }
  .pending-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .pending-info .name { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
  .pending-info .meta { font-size: 12.5px; color: var(--text-muted); font-weight: 500; }
  .pending-price { font-family: var(--font-display); font-size: 16px; font-weight: 800; color: var(--teal); }

  @media (max-width: 1024px) { .dash-stats { grid-template-columns: repeat(2, 1fr); } .quick-actions { grid-template-columns: 1fr; } }
  @media (max-width: 640px) { .dash-stats { grid-template-columns: 1fr; } }
</style>

<div class="view admin-dash">
  <div class="dash-hero">
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h1>
    <p>Here's the current state of your T-Valuate database. Use the shortcuts below to review pending items or manage existing listings.</p>
  </div>

  <div class="dash-stats">
    <div class="dash-stat">
      <div class="dash-stat-icon" style="background:var(--blue-soft); color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg></div>
      <div class="dash-stat-val"><?php echo number_format($total_listings); ?></div>
      <div class="dash-stat-lbl">Total Listings</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-icon" style="background:var(--amber-soft); color:var(--amber);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
      <div class="dash-stat-val"><?php echo number_format($pending_count); ?></div>
      <div class="dash-stat-lbl">Pending Review</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-icon" style="background:var(--teal-soft); color:var(--teal);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
      <div class="dash-stat-val"><?php echo number_format($approved_count); ?></div>
      <div class="dash-stat-lbl">Approved &amp; Live</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-icon" style="background:rgba(16, 185, 129, 0.15); color:#10B981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></div>
      <div class="dash-stat-val"><?php echo number_format($verified_count); ?></div>
      <div class="dash-stat-lbl">Mechanic Verified</div>
    </div>
  </div>

  <div class="quick-actions">
    <a href="pending_listings.php" class="qa-card">
      <div class="qa-icon" style="background:var(--amber-soft); color:var(--amber);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
      <div><div class="qa-title">Review Pending</div><div class="qa-sub"><?php echo $pending_count; ?> listing<?php echo $pending_count === 1 ? '' : 's'; ?> awaiting review</div></div>
    </a>
    <a href="add_listing.php" class="qa-card">
      <div class="qa-icon" style="background:var(--blue-soft); color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg></div>
      <div><div class="qa-title">Add New Listing</div><div class="qa-sub">Publish directly to the database</div></div>
    </a>
    <a href="manage_listings.php" class="qa-card">
      <div class="qa-icon" style="background:var(--teal-soft); color:var(--teal);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
      <div><div class="qa-title">Manage Listings</div><div class="qa-sub">Edit, verify, or delete records</div></div>
    </a>
  </div>

  <div class="dash-panel">
    <div class="dash-panel-head">
      <div class="dash-panel-title">Recent Pending Listings</div>
      <a href="pending_listings.php" style="font-size: 13px; font-weight: 700; color: var(--blue); text-decoration: none;">View all →</a>
    </div>
    <?php if (empty($recent_pending)): ?>
      <div style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 14px;">All caught up — no pending listings.</div>
    <?php else: ?>
      <div class="pending-list">
        <?php foreach ($recent_pending as $p):
            $has_photo = !empty($p['photo_front']);
            $initials = strtoupper(substr($p['brand'] ?? '?', 0, 1) . substr($p['model'] ?? '', 0, 1));
        ?>
        <a href="edit_listing.php?id=<?php echo (int)$p['id']; ?>" class="pending-item">
          <div class="pending-left">
            <div class="pending-avatar">
              <?php if ($has_photo): ?><img src="../uploads/<?php echo htmlspecialchars($p['photo_front']); ?>" alt=""><?php else: ?><?php echo htmlspecialchars($initials); ?><?php endif; ?>
            </div>
            <div class="pending-info">
              <div class="name"><?php echo htmlspecialchars($p['year_manufactured'] . ' Toyota ' . $p['model']); ?></div>
              <div class="meta"><?php echo number_format((float)$p['mileage']); ?> km · submitted <?php echo date('M j', strtotime($p['date_added'])); ?></div>
            </div>
          </div>
          <div class="pending-price">₱<?php echo number_format((float)$p['asking_price']); ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>