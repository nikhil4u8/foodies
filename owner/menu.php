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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $stmt = $pdo->prepare(
        'DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?'
    );
    $stmt->execute([(int) $_POST['item_id'], $restaurant['id']]);

    header('Location: /owner/menu.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name');
$stmt->execute([$restaurant['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Menu — Foodies</title>
</head>
<body>
    <h1>Menu for <?= e($restaurant['name']) ?></h1>
    <p><a href="/owner/menu_form.php">Add a dish</a> | <a href="/owner/dashboard.php">Dashboard</a></p>

    <?php if (!$items): ?>
        <p>No dishes yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Name</th><th>Category</th><th>Price</th><th>Available</th><th></th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category'] ?? '—') ?></td>
                    <td>₹<?= number_format((float) $item['price'], 2) ?></td>
                    <td><?= $item['is_available'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="/owner/menu_form.php?id=<?= (int) $item['id'] ?>">Edit</a>
                        <form method="post" style="display:inline"
                              onsubmit="return confirm('Delete this dish?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>