<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pdo  = getDatabaseConnection();

$stmt = $pdo->prepare(
    'SELECT o.*, r.name AS restaurant_name
       FROM orders o
       JOIN restaurants r ON r.id = o.restaurant_id
      WHERE o.user_id = ?
      ORDER BY o.placed_at DESC'
);
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your orders — Foodies</title>
</head>
<body>
    <h1>Your orders</h1>
    <p><a href="/customer/restaurants.php">Browse restaurants</a></p>

    <?php if (!$orders): ?>
        <p>You haven't placed any orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php $itemStmt->execute([$order['id']]); $items = $itemStmt->fetchAll(); ?>
            <div style="border:1px solid #ccc; padding:10px; margin:10px 0">
                <h3>Order #<?= (int) $order['id'] ?> — <?= e($order['restaurant_name']) ?></h3>
                <p>Placed <?= e($order['placed_at']) ?> — status: <?= e($order['status']) ?></p>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= e($item['item_name']) ?> × <?= (int) $item['quantity'] ?>
                            — ₹<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Total: ₹<?= number_format((float) $order['total_amount'], 2) ?></strong></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>