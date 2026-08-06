<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pdo  = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['cart_item_id'] ?? 0);

    if ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([$itemId, $user['id']]);
    } elseif ($action === 'update') {
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$quantity, $itemId, $user['id']]);
    }

    header('Location: /customer/cart.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT c.id, c.quantity, m.id AS menu_item_id, m.name, m.price, m.image,
            r.id AS restaurant_id, r.name AS restaurant_name
       FROM cart_items c
       JOIN menu_items m  ON m.id = c.menu_item_id
       JOIN restaurants r ON r.id = m.restaurant_id
      WHERE c.user_id = ?
      ORDER BY r.name, m.name'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$total = 0.0;
foreach ($rows as $row) {
    $total += (float) $row['price'] * (int) $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Food bucket — Foodies</title>
</head>
<body>
    <h1>Your food bucket</h1>
    <p><a href="/customer/restaurants.php">Keep browsing</a></p>

    <?php if (!$rows): ?>
        <p>Your bucket is empty.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr><th>Dish</th><th>Restaurant</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['restaurant_name']) ?></td>
                    <td>₹<?= number_format((float) $row['price'], 2) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_item_id" value="<?= (int) $row['id'] ?>">
                            <input type="number" name="quantity" min="1" style="width:60px"
                                   value="<?= (int) $row['quantity'] ?>">
                            <button type="submit">Update</button>
                        </form>
                    </td>
                    <td>₹<?= number_format((float) $row['price'] * (int) $row['quantity'], 2) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="cart_item_id" value="<?= (int) $row['id'] ?>">
                            <button type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr><th colspan="4">Total</th><th colspan="2">₹<?= number_format($total, 2) ?></th></tr>
        </table>

        <p><a href="/customer/checkout.php">Proceed to checkout</a></p>
    <?php endif; ?>
</body>
</html>