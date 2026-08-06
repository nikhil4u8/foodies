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

    $error = 'That email and password don\'t match. Try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in — Foodies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <a href="/index.php" class="text-decoration-none">
            <span class="navbar-brand text-dark p-0">Foodies</span>
        </a>

        <h1 class="mt-3">Welcome back</h1>
        <p class="auth-sub">Log in to pick up where you left off.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email"
                       autocomplete="email" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-100">Log in</button>
        </form>

        <p class="text-center mt-4 mb-0 small text-muted">
            New here? <a href="/auth/register.php" class="text-decoration-none fw-medium">Create an account</a>
        </p>
    </div>
</div>
</body>
</html>