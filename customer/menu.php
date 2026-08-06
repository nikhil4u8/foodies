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

$pageTitle = $restaurant['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h1 class="fw-bold"><?= e($restaurant['name']) ?></h1>
    <p class="text-muted"><?= e($restaurant['description'] ?? '') ?></p>
    <a href="/customer/restaurants.php" class="btn btn-sm btn-outline-secondary">All restaurants</a>
</div>

<?php if ($notice): ?>
    <div class="alert alert-success"><?= e($notice) ?></div>
<?php endif; ?>

<?php if (!$items): ?>
    <div class="alert alert-info">No dishes available.</div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($items as $item): ?>
            <div class="col">
                <div class="card h-100 dish-card">
                    <?php if (!empty($item['image'])): ?>
                        <img src="/assets/uploads/<?= e($item['image']) ?>" class="card-img-top" alt="">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= e($item['name']) ?></h5>
                        <p class="card-text text-muted small"><?= e($item['description'] ?? '') ?></p>
                        <p class="fw-bold mt-auto">₹<?= number_format((float) $item['price'], 2) ?></p>
                        <form method="post">
                            <input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>">
                            <button class="btn btn-sm btn-primary w-100">Add to bucket</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>