<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$user   = currentUser();
$pdo    = getDatabaseConnection();
$errors = [];

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ?');
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Give your restaurant a name.';
    }

    if (!$errors) {
        if ($restaurant) {
            $stmt = $pdo->prepare(
                'UPDATE restaurants SET name = ?, description = ?, address = ? WHERE id = ? AND owner_id = ?'
            );
            $stmt->execute([$name, $description, $address, $restaurant['id'], $user['id']]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO restaurants (owner_id, name, description, address) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$user['id'], $name, $description, $address]);
        }

        header('Location: /owner/dashboard.php');
        exit;
    }
}

$pageTitle = $restaurant ? 'Edit restaurant' : 'Add restaurant';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width:560px">
    <h1 class="fw-semibold mb-1"><?= $restaurant ? 'Edit your restaurant' : 'Add your restaurant' ?></h1>
    <p class="text-muted small mb-4">
        <?= $restaurant
            ? 'Changes show up for customers straight away.'
            : 'This is what customers see when they browse.' ?>
    </p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="card card-body">
        <div class="mb-3">
            <label class="form-label" for="name">Restaurant name</label>
            <input class="form-control" id="name" type="text" name="name"
                   value="<?= e($restaurant['name'] ?? '') ?>" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      placeholder="What kind of food do you serve?"><?= e($restaurant['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label" for="address">Address</label>
            <input class="form-control" id="address" type="text" name="address"
                   placeholder="Street, area, city"
                   value="<?= e($restaurant['address'] ?? '') ?>">
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary"><?= $restaurant ? 'Save changes' : 'Add restaurant' ?></button>
            <?php if ($restaurant): ?>
                <a href="/owner/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>