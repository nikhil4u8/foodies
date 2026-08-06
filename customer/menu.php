<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo          = getDatabaseConnection();
$restaurantId = isset($_GET['restaurant']) ? (int) $_GET['restaurant'] : 0;

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    http_response_code(404);
    exit('Restaurant not found.');
}

$user   = currentUser();
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        header('Location: /auth/login.php');
        exit;
    }

    $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);

    $stmt = $pdo->prepare(
        'SELECT id FROM menu_items WHERE id = ? AND restaurant_id = ? AND is_available = 1'
    );
    $stmt->execute([$menuItemId, $restaurant['id']]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare(
            'INSERT INTO cart_items (user_id, menu_item_id, quantity)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = quantity + 1'
        );
        $stmt->execute([$user['id'], $menuItemId]);
        $notice = 'Added to your bucket.';
    }
}

$stmt = $pdo->prepare(
    'SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = 1 ORDER BY category, name'
);
$stmt->execute([$restaurant['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($restaurant['name']) ?> — Foodies</title>
</head>
<body>
    <h1><?= e($restaurant['name']) ?></h1>
    <p><?= e($restaurant['description'] ?? '') ?></p>
    <p><a href="/customer/restaurants.php">All restaurants</a>
       <?php if ($user): ?>| <a href="/customer/cart.php">Food bucket</a><?php endif; ?></p>

    <?php if ($notice): ?>
        <p style="color:green"><?= e($notice) ?></p>
    <?php endif; ?>

    <?php if (!$items): ?>
        <p>No dishes available.</p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div style="border:1px solid #ccc; padding:10px; margin:10px 0">
                <?php if (!empty($item['image'])): ?>
                    <img src="/assets/uploads/<?= e($item['image']) ?>" alt="" style="max-width:150px">
                <?php endif; ?>
                <h3><?= e($item['name']) ?></h3>
                <p><?= e($item['description'] ?? '') ?></p>
                <p>₹<?= number_format((float) $item['price'], 2) ?></p>

                <form method="post">
                    <input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>">
                    <button type="submit">Add to bucket</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>