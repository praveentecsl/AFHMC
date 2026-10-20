<?php
session_start();
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$pdo = getDbConnection();

// Fetch all applications from the database
$stmt = $pdo->query("SELECT * FROM afhmc_applications ORDER BY id DESC");
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - AFHMC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #111827; color: white; }
        a.btn { background: #2563eb; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; }
        a.logout { background: #dc2626; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <h1>Submitted Applications (Database View)</h1>
            <a href="logout.php" class="logout">Logout</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Submitted At</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Membership Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): 
                    $fullName = trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''));
                ?>
                <tr>
                    <td><?= htmlspecialchars($sub['id']) ?></td>
                    <td><?= htmlspecialchars($sub['submitted_at']) ?></td>
                    <td><?= htmlspecialchars($fullName ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($sub['mobile'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($sub['membership_type'] ?? 'N/A') ?></td>
                    <td><a class="btn" href="view.php?id=<?= $sub['id'] ?>">View Form</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="6" style="text-align:center;">No submissions found in the database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>