<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('customer');

$user   = currentUser();
$pdo    = getDatabaseConnection();
$errors = [];

$stmt = $pdo->prepare(
    'SELECT c.quantity, m.id AS menu_item_id, m.name, m.price, m.restaurant_id
       FROM cart_items c
       JOIN menu_items m ON m.id = c.menu_item_id
      WHERE c.user_id = ?'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

if (!$rows) {
    header('Location: /customer/cart.php');
    exit;
}

$restaurantIds = array_unique(array_column($rows, 'restaurant_id'));

$stmt = $pdo->prepare('SELECT id FROM restaurants WHERE id = ? AND owner_id = ?');
$stmt->execute([(int) $restaurantIds[0], $user['id']]);

if ($stmt->fetch()) {
    $errors[] = 'You cannot order from your own restaurant.';
}

if (count($restaurantIds) > 1) {
    $errors[] = 'Your bucket contains dishes from more than one restaurant. Please order from one at a time.';
}

$total = 0.0;
foreach ($rows as $row) {
    $total += (float) $row['price'] * (int) $row['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $address = trim($_POST['delivery_address'] ?? '');

    if ($address === '') {
        $errors[] = 'A delivery address is required.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, restaurant_id, total_amount, delivery_address)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$user['id'], (int) $restaurantIds[0], $total, $address]);

            $orderId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($rows as $row) {
                $stmt->execute([
                    $orderId,
                    (int) $row['menu_item_id'],
                    $row['name'],
                    $row['price'],
                    (int) $row['quantity'],
                ]);
            }

            $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->execute([$user['id']]);

            $pdo->commit();

            header('Location: /customer/orders.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Checkout failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-3">Checkout</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card bg-white mb-4">
    <ul class="list-group list-group-flush">
        <?php foreach ($rows as $row): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= e($row['name']) ?> <span class="text-muted">× <?= (int) $row['quantity'] ?></span></span>
                <span>₹<?= number_format((float) $row['price'] * (int) $row['quantity'], 2) ?></span>
            </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span>₹<?= number_format($total, 2) ?></span>
        </li>
    </ul>
</div>

<form method="post" class="card card-body bg-white">
    <div class="mb-3">
        <label class="form-label" for="delivery_address">Delivery address</label>
        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required></textarea>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Place order</button>
        <a href="/customer/cart.php" class="btn btn-outline-secondary">Back to bucket</a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>