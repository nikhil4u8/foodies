<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout — Foodies</title>
</head>
<body>
    <h1>Checkout</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color:crimson"><?= e($error) ?></p>
    <?php endforeach; ?>

    <ul>
        <?php foreach ($rows as $row): ?>
            <li><?= e($row['name']) ?> × <?= (int) $row['quantity'] ?>
                — ₹<?= number_format((float) $row['price'] * (int) $row['quantity'], 2) ?></li>
        <?php endforeach; ?>
    </ul>

    <p><strong>Total: ₹<?= number_format($total, 2) ?></strong></p>

    <form method="post">
        <p><textarea name="delivery_address" placeholder="Delivery address" required></textarea></p>
        <button type="submit">Place order</button>
    </form>

    <p><a href="/customer/cart.php">Back to bucket</a></p>
</body>
</html>