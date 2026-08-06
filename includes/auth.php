<?php
require_once __DIR__ . '/../config/database.php';

function startSessionIfNeeded(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool
{
    startSessionIfNeeded();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    return $stmt->fetch() ?: null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();
    $user = currentUser();

    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}