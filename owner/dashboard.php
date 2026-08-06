<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$user = currentUser();
$pdo  = getDatabaseConnection();

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ?');
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard — Foodies</title>
</head>
<body>
    <h1>Owner dashboard</h1>
    <p>Signed in as <?= e($user['name']) ?> — <a href="/auth/logout.php">Log out</a></p>

    <?php if ($restaurant): ?>
        <h2><?= e($restaurant['name']) ?></h2>
        <p><?= e($restaurant['description'] ?? '') ?></p>
        <p><a href="/owner/menu.php">Manage menu</a></p>
    <?php else: ?>
        <p>You haven't set up a restaurant yet.</p>
        <p><a href="/owner/restaurant_form.php">Create your restaurant</a></p>
    <?php endif; ?>
</body>
</html>