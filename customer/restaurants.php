<?php
require_once __DIR__ . '/../includes/auth.php';

$pdo    = getDatabaseConnection();
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT r.*, (SELECT COUNT(*) FROM menu_items mi WHERE mi.restaurant_id = r.id) AS dish_count
           FROM restaurants r
           LEFT JOIN menu_items m ON m.restaurant_id = r.id
          WHERE r.name LIKE :term OR m.name LIKE :term
          ORDER BY r.name'
    );
    $stmt->execute([':term' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query(
        'SELECT r.*, (SELECT COUNT(*) FROM menu_items mi WHERE mi.restaurant_id = r.id) AS dish_count
           FROM restaurants r ORDER BY r.name'
    );
}

$restaurants = $stmt->fetchAll();

$pageTitle = 'Restaurants';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h1 class="fw-semibold mb-1">Kitchens near you</h1>
    <p class="text-muted mb-3">Search by restaurant or by the dish you're craving.</p>

    <form method="get" class="d-flex gap-2" style="max-width:520px">
        <input type="search" name="q" class="form-control"
               placeholder="Butter chicken, biryani, dhaba…" value="<?= e($search) ?>">
        <button class="btn btn-primary">Search</button>
        <?php if ($search !== ''): ?>
            <a href="/customer/restaurants.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($search !== ''): ?>
    <p class="small text-muted">
        <?= count($restaurants) ?> result<?= count($restaurants) === 1 ? '' : 's' ?> for “<?= e($search) ?>”
    </p>
<?php endif; ?>

<?php if (!$restaurants): ?>
    <div class="empty">
        <p class="mb-1 fw-medium">
            <?= $search !== '' ? 'Nothing matched that search.' : 'No kitchens listed yet.' ?>
        </p>
        <p class="small mb-0">
            <?= $search !== '' ? 'Try a shorter word, or browse everything.' : 'Check back soon.' ?>
        </p>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($restaurants as $r): ?>
            <div class="col">
                <a href="/customer/menu.php?restaurant=<?= (int) $r['id'] ?>"
                   class="text-decoration-none text-reset">
                    <div class="card h-100 dish-card">
                        <div class="card-body">
                            <h2 class="h5 mb-1"><?= e($r['name']) ?></h2>
                            <?php if (!empty($r['address'])): ?>
                                <p class="small text-muted mb-2"><?= e($r['address']) ?></p>
                            <?php endif; ?>
                            <p class="small text-muted mb-3"><?= e($r['description'] ?? '') ?></p>
                            <span class="badge bg-light text-dark border">
                                <?= (int) $r['dish_count'] ?> dish<?= (int) $r['dish_count'] === 1 ? '' : 'es' ?>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>