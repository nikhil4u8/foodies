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
        $errors[] = 'Restaurant name is required.';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $restaurant ? 'Edit' : 'Create' ?> restaurant — Foodies</title>
</head>
<body>
    <h1><?= $restaurant ? 'Edit' : 'Create' ?> your restaurant</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color:crimson"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p><input type="text" name="name" placeholder="Restaurant name"
                  value="<?= e($restaurant['name'] ?? '') ?>" required></p>
        <p><textarea name="description" placeholder="Description"><?= e($restaurant['description'] ?? '') ?></textarea></p>
        <p><input type="text" name="address" placeholder="Address"
                  value="<?= e($restaurant['address'] ?? '') ?>"></p>
        <button type="submit">Save</button>
    </form>

    <p><a href="/owner/dashboard.php">Back to dashboard</a></p>
</body>
</html>