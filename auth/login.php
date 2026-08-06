<?php
require_once __DIR__ . '/../includes/auth.php';
startSessionIfNeeded();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo  = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        header('Location: /index.php');
        exit;
    }

    $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Log in — Foodies</title>
</head>
<body>
    <h1>Log in</h1>

    <?php if ($error): ?>
        <p style="color:crimson"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <p><input type="email" name="email" placeholder="Email" required></p>
        <p><input type="password" name="password" placeholder="Password" required></p>
        <button type="submit">Log in</button>
    </form>

    <p>No account? <a href="/auth/register.php">Register</a></p>
</body>
</html>