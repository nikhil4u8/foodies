<?php
require_once __DIR__ . '/../includes/auth.php';
startSessionIfNeeded();

$_SESSION = [];
session_destroy();

header('Location: /auth/login.php');
exit;