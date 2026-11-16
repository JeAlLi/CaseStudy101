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
            $error = 'Invalid credentials or engine authorization failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login | T-Valuate Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #121410;
            --surface: rgba(30, 34, 26, 0.90); /* Slight transparency for background blend */
            --surface-hover: #262B21;
            --border: #323A2B;
            --text: #F1F3EE;
            --text-muted: #8E9885;
            --accent-green: #76A032; /* Tactical Olive */
            --accent-green-hover: #63862A;
            --accent-blue: #3B82F6;   /* Telemetry Blue */
            --khaki: #C3B091;
            --coral: #EF4444;
            --coral-soft: rgba(239, 68, 68, 0.1);
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
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
            /* Automotive background image with a deep tactical dark gradient overlay */
            background-image: 
                linear-gradient(to bottom, rgba(18, 20, 16, 0.82), rgba(18, 20, 16, 0.94)), 
                url('https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=1600');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .auth-card { 
            background: var(--surface); 
            border: 1px solid var(--border); 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.7); 
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        /* Top decorative telemetry stripe */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-green), var(--accent-blue));
        }

        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-header .icon-box {
            width: 60px;
            height: 60px;
            background: rgba(118, 160, 50, 0.1);
            border: 1px solid rgba(118, 160, 50, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--accent-green);
        }

        .auth-header svg {
            width: 30px;
            height: 30px;
        }

        .auth-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .auth-header p {
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group { 
            display: flex; 
            flex-direction: column; 
            margin-bottom: 20px; 
        }

        .form-group label { 
            font-size: 11px; 
            font-weight: 700; 
            color: var(--text-muted); 
            margin-bottom: 8px; 
            text-transform: uppercase; 
            letter-spacing: 1.2px;
            display: flex;
            justify-content: space-between;
        }

        .form-control { 
            padding: 14px 16px; 
            background-color: rgba(18, 20, 16, 0.8); 
            border: 1px solid var(--border); 
            color: var(--text); 
            border-radius: 10px; 
            font-size: 15px; 
            outline: none; 
            transition: 0.2s; 
            font-family: var(--font-main);
        }

        .form-control:focus { 
            border-color: var(--accent-green); 
            box-shadow: 0 0 0 3px rgba(118, 160, 50, 0.15); 
            background: rgba(18, 20, 16, 0.95);
        }

        .btn-submit { 
            width: 100%; 
            padding: 16px; 
            background-color: var(--accent-green); 
            color: #fff; 
            border: none; 
            border-radius: 10px; 
            font-weight: 800; 
            font-size: 14px; 
            cursor: pointer; 
            text-transform: uppercase; 
            letter-spacing: 1.5px;
            transition: 0.2s;
            margin-top: 8px;
            box-shadow: 0 4px 16px rgba(118, 160, 50, 0.3);
        }

        .btn-submit:hover { 
            background-color: var(--accent-green-hover); 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(118, 160, 50, 0.4);
        }

        .alert { 
            padding: 14px 16px; 
            border-radius: 10px; 
            margin-bottom: 24px; 
            font-size: 13.5px; 
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
            margin-top: 28px;
            text-align: center;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
            border-top: 1px solid var(--border);
            padding-top: 20px;
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

        .telemetry-tag {
            font-family: var(--font-mono);
            font-size: 10px;
            color: var(--text-muted);
            text-align: center;
            margin-top: 16px;
            letter-spacing: 1px;
            opacity: 0.6;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="icon-box">
                    <!-- Tachometer / Gauge Icon -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 0 0-7.07 17.07l1.41-1.41A8 8 0 1 1 19.48 17.66l1.41 1.41A10 10 0 0 0 12 2z"></path>
                        <path d="M12 12l4-4"></path>
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                </div>
                <h1>Driver Portal</h1>
                <p>Access your garage, listings &amp; valuations</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><span>Email Address</span></label>
                    <input type="email" name="email" class="form-control" placeholder="driver@domain.com" required autofocus>
                </div>
                <div class="form-group">
                    <label>
                        <span>Password</span>
                    </label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Ignite Session</button>
            </form>

            <div class="auth-footer">
                New to the platform? <a href="register.php">Register vehicle account</a>
            </div>
            
            <div class="telemetry-tag">
                T-Valuate Security Terminal v2.6
            </div>
        </div>
    </div>

</body>
</html>