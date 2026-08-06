<?php
require_once __DIR__ . '/auth.php';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — Foodies' : 'Foodies' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php">Foodies</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <?php if ($currentUser): ?>

                    <?php if ($currentUser['role'] === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="/customer/restaurants.php">Restaurants</a></li>
                        <li class="nav-item"><a class="nav-link" href="/customer/cart.php">Bucket</a></li>
                        <li class="nav-item"><a class="nav-link" href="/customer/orders.php">My orders</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/owner/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/owner/menu.php">Menu</a></li>
                        <li class="nav-item"><a class="nav-link" href="/owner/orders.php">Orders</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link" href="/auth/logout.php">Log out</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/customer/restaurants.php">Restaurants</a></li>
                    <li class="nav-item"><a class="nav-link" href="/auth/login.php">Log in</a></li>
                    <li class="nav-item"><a class="nav-link" href="/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">