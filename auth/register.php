<?php
require_once __DIR__ . '/../includes/auth.php';
startSessionIfNeeded();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    if ($name === '') {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Use at least 8 characters for your password.';
    }
    if (!in_array($role, ['customer', 'owner'], true)) {
        $errors[] = 'Pick whether you\'re ordering or selling.';
    }

    if (!$errors) {
        $pdo  = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'That email already has an account. Log in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, $role]);

            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            header('Location: /index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — Foodies</title>
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

        <h1 class="mt-3">Create an account</h1>
        <p class="auth-sub">Order from local kitchens, or list your own.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input class="form-control" type="text" id="name" name="name" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required>
                <div class="form-text small">At least 8 characters.</div>
            </div>

            <div class="mb-4">
                <span class="form-label d-block mb-2">I'm here to</span>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="role-customer"
                               value="customer" checked>
                        <label class="btn btn-outline-secondary w-100" for="role-customer">Order food</label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="role-owner" value="owner">
                        <label class="btn btn-outline-secondary w-100" for="role-owner">Sell food</label>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary w-100">Create account</button>
        </form>

        <p class="text-center mt-4 mb-0 small text-muted">
            Already registered? <a href="/auth/login.php" class="text-decoration-none fw-medium">Log in</a>
        </p>
    </div>
</div>
</body>
</html>