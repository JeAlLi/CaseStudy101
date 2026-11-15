<?php
session_start();
require_once 'db.php'; 

// If user is already logged in, redirect them to the homepage
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $email;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | T-Valuate Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #1A1C16;
            --surface: #2C3026;
            --surface-hover: #363b2f;
            --border: #414833;
            --text: #E8EBE4;
            --text-muted: #9BA092;
            --olive-drab: #6B8E23;
            --olive-hover: #55731b;
            --khaki: #C3B091;
            --coral: #ef4444;
            --coral-soft: rgba(239, 68, 68, 0.1);
            --font-main: 'Inter', sans-serif;
            --font-display: 'Inter', sans-serif;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: var(--font-main); 
            background-color: var(--bg-color); 
            color: var(--text); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            background-image: radial-gradient(circle at top right, rgba(107, 142, 35, 0.05), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(195, 176, 145, 0.05), transparent 40%);
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .auth-card { 
            background: var(--surface); 
            border: 1px solid var(--border); 
            border-radius: 16px; 
            padding: 40px; 
            box-shadow: 0 12px 40px rgba(0,0,0,0.4); 
        }

        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-header svg {
            width: 48px;
            height: 48px;
            color: var(--olive-drab);
            margin-bottom: 16px;
        }

        .auth-header h1 {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .auth-header p {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group { 
            display: flex; 
            flex-direction: column; 
            margin-bottom: 20px; 
        }

        .form-group label { 
            font-size: 12px; 
            font-weight: 700; 
            color: var(--text-muted); 
            margin-bottom: 8px; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }

        .form-control { 
            padding: 14px 16px; 
            background-color: var(--bg-color); 
            border: 1px solid var(--border); 
            color: var(--text); 
            border-radius: 8px; 
            font-size: 15px; 
            outline: none; 
            transition: 0.2s; 
            font-family: var(--font-main);
        }

        .form-control:focus { 
            border-color: var(--olive-drab); 
            box-shadow: 0 0 0 3px rgba(107, 142, 35, 0.2); 
        }

        .btn-submit { 
            width: 100%; 
            padding: 16px; 
            background-color: var(--olive-drab); 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            font-weight: 800; 
            font-size: 15px; 
            cursor: pointer; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            transition: 0.2s;
            margin-top: 8px;
        }

        .btn-submit:hover { 
            background-color: var(--olive-hover); 
            transform: translateY(-2px);
        }

        .alert { 
            padding: 14px 16px; 
            border-radius: 8px; 
            margin-bottom: 24px; 
            font-size: 14px; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error { 
            background: var(--coral-soft); 
            border: 1px solid var(--coral); 
            color: #ff8793; 
        }

        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .auth-footer a {
            color: var(--khaki);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .auth-footer a:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4"></path>
                    <path d="M12 8h.01"></path>
                </svg>
                <h1>Welcome Back</h1>
                <p>Log in to manage your listings.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

</body>
</html>