<?php
require_once 'db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
            if ($insert->execute([$name, $email, $hashed_password])) {
                $success = 'Registration successful. You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up | Toyota Valuation Engine</title>
    <style>
        :root {
            --bg-color: #1A1C16;
            --surface: #2C3026;
            --border: #414833;
            --olive-drab: #6B8E23;
            --olive-hover: #55731b;
            --khaki: #C3B091;
            --text-main: #E8EBE4;
            --text-muted: #9BA092;
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .auth-container {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        .auth-container h2 {
            margin-top: 0;
            color: var(--khaki);
            font-size: 24px;
            margin-bottom: 24px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-color);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 6px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: var(--olive-drab);
        }
        .auth-btn {
            width: 100%;
            padding: 14px;
            background-color: var(--olive-drab);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .auth-btn:hover {
            background-color: var(--olive-hover);
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .auth-links a {
            color: var(--khaki);
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .msg { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff8793; }
        .success { background: rgba(107, 142, 35, 0.2); border: 1px solid var(--olive-drab); color: var(--khaki); }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Initiate Access</h2>
        <?php if ($error): ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg success"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="auth-btn">Register Personnel</button>
        </form>
        <div class="auth-links">
            Already registered? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>