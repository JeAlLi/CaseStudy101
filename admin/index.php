<?php
session_start();
// Point back to the main folder's database connection
require_once '../db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

include 'header.php';
?>
<div class="view" style="display: block;">
  <div class="panel" style="max-width: 400px; margin: 40px auto;">
    <div class="panel-head">
      <h3>Administrator Login</h3>
    </div>
    <div class="panel-sub" style="margin-left: 0; margin-bottom: 20px;">Sign in to manage the vehicle dataset.</div>
    
    <?php if ($error): ?>
        <div class="alert alert-error" style="color: #B0402F;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="field" style="margin-bottom: 16px;">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="field" style="margin-bottom: 24px;">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="predict-btn" style="width: 100%;">Secure Login</button>
    </form>
  </div>
</div>
</main></div></body></html>