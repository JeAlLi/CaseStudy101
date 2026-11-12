<?php
ob_start(); // Prevents "Headers already sent" errors

// Force browser to never cache this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$__admin_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$__admin_titles = [
    'dashboard.php'        => 'Admin Dashboard',
    'pending_listings.php' => 'Pending Listings',
    'add_listing.php'      => 'Add Listing',
    'manage_listings.php'  => 'Manage Listings',
    'edit_listing.php'     => 'Edit Listing',
    'index.php'            => 'Administrator Login',
];
$__admin_title = $__admin_titles[$__admin_page] ?? 'Admin Panel';
$__admin_user  = $_SESSION['admin_username'] ?? 'Admin';

$__is_dash    = $__admin_page === 'dashboard.php';
$__is_pending = $__admin_page === 'pending_listings.php';
$__is_add     = $__admin_page === 'add_listing.php';
$__is_manage  = in_array($__admin_page, ['manage_listings.php', 'edit_listing.php'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Meta tags to kill BFCache -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title><?php echo htmlspecialchars($__admin_title); ?> — T-Valuate Admin</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<script>
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>
<style>
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 32px; border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 30; transition: var(--transition); }
    [data-theme="dark"] .topbar { background: rgba(30, 41, 59, 0.85); }
    .topbar-left { display: flex; align-items: center; gap: 16px; }
    .topbar-title { font-family: var(--font-display); font-weight: 800; font-size: 18px; letter-spacing: -0.3px; }
    .topbar-actions { display: flex; align-items: center; gap: 16px; }
    .icon-btn { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--paper); border: 1px solid var(--border); color: var(--text-muted); cursor: pointer; transition: var(--transition); }
    .icon-btn:hover { background: var(--surface); color: var(--text); border-color: var(--border-hover); transform: translateY(-1px); }
    .icon-btn svg { width: 18px; height: 18px; }
    .user-profile { display: flex; align-items: center; gap: 10px; padding: 4px 16px 4px 4px; border-radius: 24px; background: var(--paper); border: 1px solid var(--border); cursor: pointer; transition: var(--transition); }
    .user-profile:hover { background: var(--surface); border-color: var(--border-hover); }
    .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 800; font-size: 13px; }
    .user-name { font-size: 13px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    @media (max-width: 768px) { .user-name { display: none; } .user-profile { padding: 4px; border: none; background: transparent; } .topbar { padding: 12px 20px; } }
</style>
</head>
<body>
<div class="app">

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M3 12l1.5-5A2 2 0 016.4 5.5h11.2A2 2 0 0119.5 7L21 12M3 12v5a1 1 0 001 1h1a1 1 0 001-1v-1h12v1a1 1 0 001 1h1a1 1 0 001-1v-5M3 12h18M6.5 15.5h.01M17.5 15.5h.01" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div>
        <div class="brand-text">T-<span>Valuate</span></div>
        <div class="brand-sub">Admin Console</div>
      </div>
    </div>

    <div class="nav-label">Management</div>
    <div class="nav">
      <a href="dashboard.php" class="nav-item <?php echo $__is_dash ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Admin Dashboard
      </a>
      <a href="pending_listings.php" class="nav-item <?php echo $__is_pending ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        Pending Listings
      </a>
      <a href="manage_listings.php" class="nav-item <?php echo $__is_manage ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Manage Listings
      </a>
      <a href="add_listing.php" class="nav-item <?php echo $__is_add ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
        Add Listing
      </a>
    </div>

    <div class="nav-label">Site</div>
    <div class="nav">
      <a href="../dashboard.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>
        View Public Site
      </a>
      <a href="logout.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Log Out
      </a>
    </div>

    <div class="sidebar-footer">
      <div class="sf-eyebrow"><span class="sf-dot"></span>Admin Session Active</div>
      <p>Signed in as <strong style="color:#F8FAFC;"><?php echo htmlspecialchars($__admin_user); ?></strong>.</p>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-btn" id="menuBtn" aria-label="Toggle menu" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="topbar-title">
          <?php echo htmlspecialchars($__admin_title); ?>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" id="themeToggle" aria-label="Toggle Dark Mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </button>
        <div class="user-profile">
          <div class="user-avatar">A</div>
          <div class="user-name"><?php echo htmlspecialchars($__admin_user); ?></div>
        </div>
      </div>
    </div>