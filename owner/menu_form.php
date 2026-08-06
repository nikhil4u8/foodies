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
        $errors[] = 'Dish name is required.';
    }
    if (!is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Price must be a positive number.';
    }

    if (!empty($_FILES['image']['name'])) {
        $upload = $_FILES['image'];

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
        } elseif ($upload['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be under 2 MB.';
        } else {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime    = mime_content_type($upload['tmp_name']);

            if (!isset($allowed[$mime])) {
                $errors[] = 'Image must be JPEG, PNG or WebP.';
            } else {
                $imageName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $target    = __DIR__ . '/../assets/uploads/' . $imageName;

                if (!move_uploaded_file($upload['tmp_name'], $target)) {
                    $errors[] = 'Could not save the image.';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $item ? 'Edit' : 'Add' ?> dish — Foodies</title>
</head>
<body>
    <h1><?= $item ? 'Edit' : 'Add' ?> dish</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color:crimson"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <p><input type="text" name="name" placeholder="Dish name"
                  value="<?= e($item['name'] ?? '') ?>" required></p>
        <p><textarea name="description" placeholder="Description"><?= e($item['description'] ?? '') ?></textarea></p>
        <p><input type="text" name="category" placeholder="Category (e.g. Starters)"
                  value="<?= e($item['category'] ?? '') ?>"></p>
        <p><input type="number" name="price" step="0.01" min="0" placeholder="Price"
                  value="<?= e((string) ($item['price'] ?? '')) ?>" required></p>
        <p>
            <label>
                <input type="checkbox" name="is_available"
                       <?= (!$item || $item['is_available']) ? 'checked' : '' ?>>
                Available
            </label>
        </p>

        <?php if (!empty($item['image'])): ?>
            <p><img src="/assets/uploads/<?= e($item['image']) ?>" alt="" style="max-width:200px"></p>
        <?php endif; ?>

        <p><input type="file" name="image" accept="image/*"></p>

        <button type="submit">Save</button>
    </form>

    <p><a href="/owner/menu.php">Back to menu</a></p>
</body>
</html>