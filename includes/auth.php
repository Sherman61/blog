<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . site_url('login.php'));
        exit;
    }
}

function require_admin(): void
{
    require_login();

    $user = current_user();
    if (empty($user['is_admin'])) {
        header('Location: ' . site_url());
        exit;
    }
}
?>
