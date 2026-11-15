<?php
session_start();
require_once '../db.php'; // Ensure this points to your root db.php

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check against the admin_users table
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the bcrypt hash
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | Command Center</title>
    <style>
        :root {
            --bg-color: #1A1C16;
            --surface: #2C3026;
            --border: #414833;
            --olive-drab: #6B8E23;
            --khaki: #C3B091;
            --text-main: #E8EBE4;
        }
        body { background-color: var(--bg-color); color: var(--text-main); font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-container { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
        .auth-container h2 { color: var(--khaki); font-size: 24px; margin-top: 0; margin-bottom: 24px; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; color: #9BA092; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 12px; background-color: var(--bg-color); border: 1px solid var(--border); color: var(--text-main); border-radius: 6px; box-sizing: border-box; outline: none; }
        .form-group input:focus { border-color: var(--olive-drab); }
        .auth-btn { width: 100%; padding: 14px; background-color: var(--olive-drab); color: #fff; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; text-transform: uppercase; }
        .auth-btn:hover { background-color: #55731b; }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff8793; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Admin Authentication</h2>
        <?php if ($error): ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="auth-btn">Access Command Center</button>
        </form>
    </div>
</body>
</html>