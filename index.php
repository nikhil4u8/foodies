<?php
require __DIR__ . '/config/database.php';

$pdo = getDatabaseConnection();
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

echo "Foodies is running. Users in database: {$count}";