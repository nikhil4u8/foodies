<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('customer');

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

$pageTitle = 'Food bucket';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-3">Your food bucket</h1>
<p><a href="/customer/restaurants.php" class="btn btn-sm btn-outline-secondary">Keep browsing</a></p>

<?php if (!$rows): ?>
    <div class="alert alert-info">Your bucket is empty.</div>
<?php else: ?>
    <table class="table table-striped align-middle bg-white">
        <thead>
            <tr><th>Dish</th><th>Restaurant</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-muted"><?= e($row['restaurant_name']) ?></td>
                    <td>₹<?= number_format((float) $row['price'], 2) ?></td>
                    <td>
                        <form method="post" class="d-flex gap-1">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_item_id" value="<?= (int) $row['id'] ?>">
                            <input type="number" name="quantity" min="1"
                                   class="form-control form-control-sm" style="width:70px"
                                   value="<?= (int) $row['quantity'] ?>">
                            <button class="btn btn-sm btn-outline-primary">Update</button>
                        </form>
                    </td>
                    <td class="fw-semibold">₹<?= number_format((float) $row['price'] * (int) $row['quantity'], 2) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="cart_item_id" value="<?= (int) $row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Total</th>
                <th colspan="2">₹<?= number_format($total, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <a href="/customer/checkout.php" class="btn btn-primary">Proceed to checkout</a>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>