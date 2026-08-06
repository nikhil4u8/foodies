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
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!in_array($role, ['customer', 'owner'], true)) {
        $errors[] = 'Invalid role.';
    }

    if (!$errors) {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
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
    <title>Register — Foodies</title>
</head>
<body>
    <h1>Create an account</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color:crimson"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p><input type="text" name="name" placeholder="Name" required></p>
        <p><input type="email" name="email" placeholder="Email" required></p>
        <p><input type="password" name="password" placeholder="Password" required></p>
        <p>
            <label><input type="radio" name="role" value="customer" checked> Customer</label>
            <label><input type="radio" name="role" value="owner"> Restaurant owner</label>
        </p>
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="/auth/login.php">Log in</a></p>
</body>
</html>