<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch the user's name from the session, default to 'Guest' if not logged in
$display_name = $_SESSION['full_name'] ?? 'Guest';
$is_logged_in = isset($_SESSION['user_id']);

?>

<!-- HTML and CSS MUST go after the closing ?> tag -->
<style>
    /* Tactical Theme Header Additions */
    .user-profile-section {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .user-greeting {
        font-size: 14px;
        font-weight: 600;
        color: var(--khaki, #C3B091);
        text-transform: uppercase;
        letter-spacing: 0.5px;  
    }
    .logout-btn {
        background: transparent;
        color: var(--text-main, #E8EBE4);
        border: 1px solid var(--border, #414833);
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .logout-btn:hover {
        background: rgba(220, 53, 69, 0.1);
        border-color: #dc3545;
        color: #ff8793;
    }
</style>

</div>
</div>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>T-Valuate — Toyota Value Estimator</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script>
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>
<style>
    :root {
        --font-display: 'Plus Jakarta Sans', sans-serif;
    }
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
        <div class="brand-sub">Toyota Estimator</div>
      </div>
    </div>

    <div class="nav-label">Marketplace</div>
    <div class="nav">
      <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Dashboard
      </a>
      <a href="browse.php" class="nav-item <?php echo ($current_page == 'browse') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        Browse Cars
      </a>
      <a href="submit_listing.php" class="nav-item <?php echo ($current_page == 'submit_listing') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Sell Your Car
      </a>
    </div>

    <div class="nav-label">Tools</div>
    <div class="nav">
      <a href="predict.php" class="nav-item <?php echo ($current_page == 'predict') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
        Price Predictor
      </a>
      
      <a href="about.php" class="nav-item <?php echo ($current_page == 'about') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5"/><circle cx="12" cy="8.2" r="0.6" fill="currentColor"/></svg>
        About Us
      </a>
    </div>

    <div class="sidebar-footer">
      <div class="sf-eyebrow"><span class="sf-dot"></span>System Online</div>
      <p>Estimates are generated from real listing data and update automatically.</p>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-btn" id="menuBtn" aria-label="Toggle menu" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="topbar-title">
          <?php echo ucfirst(str_replace('_', ' ', $current_page)); ?>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" id="themeToggle" aria-label="Toggle Dark Mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </button>
        <div class="user-profile">

          <div style="display: flex; align-items: center; gap: 12px;">

    <!-- Dynamic User Profile Pill -->
    <div style="display: flex; align-items: center; gap: 8px; background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; color: var(--text, #1e293b); box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <?php echo htmlspecialchars($display_name); ?>
    </div>

    <!-- Conditional Logout Button -->
    <?php if ($is_logged_in): ?>
        <a href="logout.php" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.2s;">
            Logout
        </a>
    <?php else: ?>
        <!-- Optional: Show a login button if they are actually a guest -->
        <a href="login.php" style="background: var(--blue, #2563eb); border: 1px solid var(--blue, #2563eb); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.2s;">
            Login
        </a>
    <?php endif; ?>
</div>
        </div>
      </div>
    </div>