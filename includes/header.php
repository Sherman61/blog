<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$categories = [];
try {
    $stmt = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name');
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load categories: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <a class="logo" href="/">Mindful Musings</a>
            <p class="tagline">Stories for calmer minds</p>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav" id="primary-nav">
            <?php foreach ($categories as $cat): ?>
                <a href="/category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <?php if (is_logged_in()): ?>
                <span class="welcome">Hi, <?php echo htmlspecialchars(current_user()['username']); ?></span>
                <a class="button" href="/admin/index.php">Admin</a>
                <a class="button secondary" href="/logout.php">Logout</a>
            <?php else: ?>
                <a class="button secondary" href="/login.php">Login</a>
                <a class="button" href="/signup.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main container">
