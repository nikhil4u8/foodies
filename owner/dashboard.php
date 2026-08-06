<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$user = currentUser();
$pdo  = getDatabaseConnection();

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ?');
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

$stats = ['dishes' => 0, 'orders' => 0, 'pending' => 0, 'revenue' => 0.0];

if ($restaurant) {
    $stmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM menu_items WHERE restaurant_id = :r1) AS dishes,
            (SELECT COUNT(*) FROM orders WHERE restaurant_id = :r2) AS orders,
            (SELECT COUNT(*) FROM orders WHERE restaurant_id = :r3 AND status = \'pending\') AS pending,
            (SELECT COALESCE(SUM(total_amount), 0) FROM orders
              WHERE restaurant_id = :r4 AND status = \'delivered\') AS revenue'
    );
    $stmt->execute([
        ':r1' => $restaurant['id'],
        ':r2' => $restaurant['id'],
        ':r3' => $restaurant['id'],
        ':r4' => $restaurant['id'],
    ]);
    $stats = $stmt->fetch();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="owner-hero mb-4">
    <span class="eyebrow">Restaurant owner</span>
    <h1 class="mt-1"><?= $restaurant ? e($restaurant['name']) : 'Set up your kitchen' ?></h1>
    <p class="mb-0 opacity-75 small">
        <?= $restaurant
            ? e($restaurant['address'] ?: 'No address set')
            : 'Add your restaurant to start listing dishes.' ?>
    </p>
</div>

<?php if (!$restaurant): ?>
    <div class="empty">
        <p class="fw-medium mb-1">You haven't added a restaurant yet.</p>
        <p class="small mb-3">Customers can't find you until you do.</p>
        <a href="/owner/restaurant_form.php" class="btn btn-primary">Add your restaurant</a>
    </div>
<?php else: ?>

    <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
            <div class="stat-tile">
                <div class="stat-value"><?= (int) $stats['dishes'] ?></div>
                <div class="stat-label">On the menu</div>
            </div>
        </div>
        <div class="col">
            <div class="stat-tile">
                <div class="stat-value"><?= (int) $stats['orders'] ?></div>
                <div class="stat-label">Orders received</div>
            </div>
        </div>
        <div class="col">
            <div class="stat-tile">
                <div class="stat-value"><?= (int) $stats['pending'] ?></div>
                <div class="stat-label">Awaiting action</div>
            </div>
        </div>
        <div class="col">
            <div class="stat-tile">
                <div class="stat-value">₹<?= number_format((float) $stats['revenue'], 0) ?></div>
                <div class="stat-label">Delivered value</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Menu</h2>
                    <p class="small text-muted">Add dishes, set prices, mark things sold out.</p>
                    <a href="/owner/menu.php" class="btn btn-sm btn-primary">Manage menu</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Orders</h2>
                    <p class="small text-muted">
                        <?= (int) $stats['pending'] > 0
                            ? (int) $stats['pending'] . ' order' . ((int) $stats['pending'] === 1 ? '' : 's') . ' waiting to be confirmed.'
                            : 'Nothing waiting right now.' ?>
                    </p>
                    <a href="/owner/orders.php" class="btn btn-sm btn-primary">View orders</a>
                </div>
            </div>
        </div>
    </div>

    <p class="mt-4 small">
        <a href="/owner/restaurant_form.php" class="text-muted">Edit restaurant details</a>
    </p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>