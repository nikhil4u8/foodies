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

$statusColours = [
    'pending'   => 'warning',
    'confirmed' => 'info',
    'preparing' => 'warning',
    'delivered' => 'success',
    'cancelled' => 'danger',
];

$pageTitle = 'Orders';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h1 class="fw-semibold mb-1">Orders</h1>
    <p class="text-muted mb-0 small"><?= e($restaurant['name']) ?></p>
</div>

<?php if (!$orders): ?>
    <div class="empty">
        <p class="fw-medium mb-1">No orders yet.</p>
        <p class="small mb-0">They'll appear here the moment someone checks out.</p>
    </div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <?php $itemStmt->execute([$order['id']]); $items = $itemStmt->fetchAll(); ?>
        <div class="card order-card is-<?= e($order['status']) ?> mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <strong>#<?= (int) $order['id'] ?></strong>
                    <span class="text-muted">· <?= e($order['customer_name']) ?></span>
                </span>
                <span class="badge bg-<?= $statusColours[$order['status']] ?? 'secondary' ?>-subtle text-<?= $statusColours[$order['status']] ?? 'secondary' ?>-emphasis">
                    <?= e($order['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-7">
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($items as $item): ?>
                                <li class="d-flex justify-content-between border-bottom py-1 small">
                                    <span>
                                        <?= e($item['item_name']) ?>
                                        <span class="text-muted">× <?= (int) $item['quantity'] ?></span>
                                    </span>
                                    <span>₹<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="d-flex justify-content-between pt-2 fw-semibold">
                                <span>Total</span>
                                <span>₹<?= number_format((float) $order['total_amount'], 2) ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-5">
                        <div class="small text-muted mb-1">Deliver to</div>
                        <p class="small mb-3"><?= e($order['delivery_address'] ?: 'Not provided') ?></p>

                        <div class="small text-muted mb-2">Placed <?= e($order['placed_at']) ?></div>

                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach (['pending','confirmed','preparing','delivered','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>