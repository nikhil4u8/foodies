<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$user   = currentUser();
$pdo    = getDatabaseConnection();
$errors = [];

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_id = ?');
$stmt->execute([$user['id']]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header('Location: /owner/restaurant_form.php');
    exit;
}

$item   = null;
$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($itemId) {
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ? AND restaurant_id = ?');
    $stmt->execute([$itemId, $restaurant['id']]);
    $item = $stmt->fetch();

    if (!$item) {
        http_response_code(404);
        exit('Dish not found.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = $_POST['price'] ?? '';
    $available   = isset($_POST['is_available']) ? 1 : 0;
    $imageName   = $item['image'] ?? null;

    if ($name === '') {
        $errors[] = 'Give the dish a name.';
    }
    if (!is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Enter a price as a number, without the currency symbol.';
    }

    if (!empty($_FILES['image']['name'])) {
        $upload = $_FILES['image'];

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The photo didn\'t upload. Try again.';
        } elseif ($upload['size'] > 2 * 1024 * 1024) {
            $errors[] = 'That photo is over 2 MB. Use a smaller one.';
        } else {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime    = mime_content_type($upload['tmp_name']);

            if (!isset($allowed[$mime])) {
                $errors[] = 'Photos must be JPEG, PNG or WebP.';
            } else {
                $imageName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $target    = __DIR__ . '/../assets/uploads/' . $imageName;

                if (!move_uploaded_file($upload['tmp_name'], $target)) {
                    $errors[] = 'Couldn\'t save the photo. Try again.';
                    $imageName = $item['image'] ?? null;
                }
            }
        }
    }

    if (!$errors) {
        if ($item) {
            $stmt = $pdo->prepare(
                'UPDATE menu_items
                    SET name = ?, description = ?, category = ?, price = ?, is_available = ?, image = ?
                  WHERE id = ? AND restaurant_id = ?'
            );
            $stmt->execute([
                $name, $description, $category, $price, $available, $imageName,
                $item['id'], $restaurant['id'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO menu_items (restaurant_id, name, description, category, price, is_available, image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $restaurant['id'], $name, $description, $category, $price, $available, $imageName,
            ]);
        }

        header('Location: /owner/menu.php');
        exit;
    }
}

$pageTitle = $item ? 'Edit dish' : 'Add dish';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width:560px">
    <h1 class="fw-semibold mb-1"><?= $item ? 'Edit dish' : 'Add a dish' ?></h1>
    <p class="text-muted small mb-4"><?= e($restaurant['name']) ?></p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="card card-body">
        <div class="mb-3">
            <label class="form-label" for="name">Dish name</label>
            <input class="form-control" id="name" type="text" name="name"
                   value="<?= e($item['name'] ?? '') ?>" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"
                      placeholder="What's in it?"><?= e($item['description'] ?? '') ?></textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-7">
                <label class="form-label" for="category">Category</label>
                <input class="form-control" id="category" type="text" name="category"
                       placeholder="Starters, Mains, Breads…"
                       value="<?= e($item['category'] ?? '') ?>">
            </div>
            <div class="col-5">
                <label class="form-label" for="price">Price (₹)</label>
                <input class="form-control" id="price" type="number" step="0.01" min="0"
                       name="price" value="<?= e((string) ($item['price'] ?? '')) ?>" required>
            </div>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="is_available" name="is_available"
                   <?= (!$item || $item['is_available']) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_available">Available to order</label>
        </div>

        <div class="mb-4">
            <label class="form-label" for="image">Photo</label>
            <?php if (!empty($item['image'])): ?>
                <div class="mb-2">
                    <img src="/assets/uploads/<?= e($item['image']) ?>" class="rounded"
                         style="max-width:170px" alt="">
                </div>
            <?php endif; ?>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <div class="form-text small">
                JPEG, PNG or WebP, up to 2 MB.<?= !empty($item['image']) ? ' Leave empty to keep the current photo.' : '' ?>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary"><?= $item ? 'Save changes' : 'Add dish' ?></button>
            <a href="/owner/menu.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>