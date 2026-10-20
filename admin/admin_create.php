<?php
require_once __DIR__ . '/../db.php';
$pdo = getDbConnection();

$username = 'admin';
$plainPassword = 'admin123'; // Change to your password
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO afhmc_admins (username, password_hash) VALUES (:username, :hash)");
try {
    $stmt->execute([':username' => $username, ':hash' => $hash]);
    echo "Admin user created successfully!";
} catch (Exception $e) {
    echo "User might already exist or error: " . $e->getMessage();
}
?>