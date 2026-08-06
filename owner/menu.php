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
    $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?');
    $stmt->execute([(int) $_POST['item_id'], $restaurant['id']]);
    header('Location: /owner/menu.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name');
$stmt->execute([$restaurant['id']]);
$items = $stmt->fetchAll();

$pageTitle = 'Menu';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-semibold mb-1">Menu</h1>
        <p class="text-muted mb-0 small"><?= e($restaurant['name']) ?></p>
    </div>
    <a href="/owner/menu_form.php" class="btn btn-primary">Add a dish</a>
</div>

<?php if (!$items): ?>
    <div class="empty">
        <p class="fw-medium mb-1">Your menu is empty.</p>
        <p class="small mb-3">Add your first dish and it'll show up for customers straight away.</p>
        <a href="/owner/menu_form.php" class="btn btn-primary">Add a dish</a>
    </div>
<?php else: ?>
    <div class="card">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th colspan="2">Dish</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="width:60px">
                            <?php if (!empty($item['image'])): ?>
                                <img src="/assets/uploads/<?= e($item['image']) ?>" class="thumb" alt="">
                            <?php else: ?>
                                <div class="thumb-empty"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-medium"><?= e($item['name']) ?></div>
                            <?php if (!empty($item['description'])): ?>
                                <div class="small text-muted text-truncate" style="max-width:260px">
                                    <?= e($item['description']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e($item['category'] ?: '—') ?></td>
                        <td><span class="price">₹<?= number_format((float) $item['price'], 2) ?></span></td>
                        <td>
                            <?php if ($item['is_available']): ?>
                                <span class="badge bg-success-subtle text-success-emphasis">Available</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">Sold out</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/owner/menu_form.php?id=<?= (int) $item['id'] ?>"
                               class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Remove <?= e($item['name']) ?> from the menu?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>