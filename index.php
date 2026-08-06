<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
    <h1 class="display-5 fw-bold">Foodies</h1>
    <p class="lead text-muted">Order from restaurants near you.</p>

    <?php if ($currentUser): ?>
        <p>Signed in as <strong><?= e($currentUser['name']) ?></strong>
           <span class="badge bg-secondary"><?= e($currentUser['role']) ?></span></p>
        <a href="/customer/restaurants.php" class="btn btn-primary">Browse restaurants</a>
    <?php else: ?>
        <a href="/auth/register.php" class="btn btn-primary">Get started</a>
        <a href="/auth/login.php" class="btn btn-outline-secondary">Log in</a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>