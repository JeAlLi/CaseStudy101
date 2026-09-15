<?php
// Point back one folder level to access db.php
require_once '../db.php';

$username = 'admin';
$password = 'admin123'; // Change this before running if you want a different password
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);
    echo "<h3>Admin user created successfully!</h3>";
    echo "Username: <b>{$username}</b><br>Password: <b>{$password}</b><br><br>";
    echo "<a href='index.php'>Go to Login</a><br><br>";
    echo "<strong style='color:red;'>SECURITY WARNING: Delete this file (admin_setup.php) immediately after generating the account.</strong>";
} catch (Exception $e) {
    echo "Error (or user already exists): " . $e->getMessage();
}
?>