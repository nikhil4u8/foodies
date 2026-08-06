<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('customer');

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

$statusColours = [
    'pending'   => 'secondary',
    'confirmed' => 'info',
    'preparing' => 'warning',
    'delivered' => 'success',
    'cancelled' => 'danger',
];

$pageTitle = 'Your orders';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-3">Your orders</h1>
<p><a href="/customer/restaurants.php" class="btn btn-sm btn-outline-secondary">Browse restaurants</a></p>

<?php if (!$orders): ?>
    <div class="alert alert-info">You haven't placed any orders yet.</div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <?php $itemStmt->execute([$order['id']]); $items = $itemStmt->fetchAll(); ?>
        <div class="card bg-white mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <strong>Order #<?= (int) $order['id'] ?></strong>
                    — <?= e($order['restaurant_name']) ?>
                </span>
                <span class="badge bg-<?= $statusColours[$order['status']] ?? 'secondary' ?>">
                    <?= e($order['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Placed <?= e($order['placed_at']) ?></p>
                <ul class="list-unstyled mb-2">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <?= e($item['item_name']) ?>
                            <span class="text-muted">× <?= (int) $item['quantity'] ?></span>
                            — ₹<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="fw-bold mb-0">Total: ₹<?= number_format((float) $order['total_amount'], 2) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>