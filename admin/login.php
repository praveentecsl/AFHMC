<?php
session_start();
require_once __DIR__ . '/../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM afhmc_admins WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        // Verify user exists and password hash matches
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - AFHMC</title>
    <style>
        body { font-family: Arial, sans-serif; background: #d7d7d7; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #ffff80; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); width: 320px; }
        input { width: 100%; padding: 8px; margin: 8px 0 16px 0; box-sizing: border-box; }
        button { width: 100%; background: #111827; color: white; border: 0; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error { color: #8a1f1f; background: #fde8e8; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>