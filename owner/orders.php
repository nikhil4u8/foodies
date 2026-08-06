<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$user = currentUser();
$pdo  = getDatabaseConnection();

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ?');
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header('Location: /owner/restaurant_form.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'];

    if (in_array($status, $allowed, true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?');
        $stmt->execute([$status, $orderId, $restaurant['id']]);
    }

    header('Location: /owner/orders.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS customer_name
       FROM orders o
       JOIN users u ON u.id = o.user_id
      WHERE o.restaurant_id = ?
      ORDER BY o.placed_at DESC'
);
$stmt->execute([$restaurant['id']]);
$orders = $stmt->fetchAll();

$itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders — Foodies</title>
</head>
<body>
    <h1>Orders for <?= e($restaurant['name']) ?></h1>
    <p><a href="/owner/dashboard.php">Dashboard</a> | <a href="/owner/menu.php">Menu</a></p>

    <?php if (!$orders): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php $itemStmt->execute([$order['id']]); $items = $itemStmt->fetchAll(); ?>
            <div style="border:1px solid #ccc; padding:10px; margin:10px 0">
                <h3>Order #<?= (int) $order['id'] ?> — <?= e($order['customer_name']) ?></h3>
                <p><?= e($order['placed_at']) ?></p>
                <p>Deliver to: <?= e($order['delivery_address'] ?? '') ?></p>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= e($item['item_name']) ?> × <?= (int) $item['quantity'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>₹<?= number_format((float) $order['total_amount'], 2) ?></strong></p>

                <form method="post">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <select name="status">
                        <?php foreach (['pending','confirmed','preparing','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Update status</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>