<?php
require_once __DIR__ . '/includes/auth.php';

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Foodies</title>
</head>
<body>
    <h1>Foodies</h1>

    <?php if ($user): ?>
        <p>Signed in as <?= e($user['name']) ?> (<?= e($user['role']) ?>)</p>
        <p><a href="/auth/logout.php">Log out</a></p>
    <?php else: ?>
        <p><a href="/auth/login.php">Log in</a> or <a href="/auth/register.php">register</a></p>
    <?php endif; ?>
</body>
</html>