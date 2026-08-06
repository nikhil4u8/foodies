<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo    = getDatabaseConnection();
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT r.*
           FROM restaurants r
           LEFT JOIN menu_items m ON m.restaurant_id = r.id
          WHERE r.name LIKE :term OR m.name LIKE :term
          ORDER BY r.name'
    );
    $stmt->execute([':term' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM restaurants ORDER BY name');
}

$restaurants = $stmt->fetchAll();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Restaurants — Foodies</title>
</head>
<body>
    <h1>Restaurants</h1>
    <p><a href="/index.php">Home</a>
       <?php if ($user): ?>| <a href="/customer/cart.php">Food bucket</a><?php endif; ?></p>

    <form method="get">
        <input type="text" name="q" placeholder="Search restaurants or dishes"
               value="<?= e($search) ?>">
        <button type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a href="/customer/restaurants.php">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$restaurants): ?>
        <p><?= $search !== '' ? 'No matches found.' : 'No restaurants yet.' ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($restaurants as $r): ?>
                <li>
                    <a href="/customer/menu.php?restaurant=<?= (int) $r['id'] ?>">
                        <?= e($r['name']) ?>
                    </a>
                    <?php if (!empty($r['address'])): ?>
                        — <?= e($r['address']) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>