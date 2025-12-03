<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$message = flash_message();

// Basic metrics for dashboard
$metrics = [
    'posts' => 0,
    'categories' => 0,
    'pending_comments' => 0,
];

try {
    $metrics['posts'] = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $metrics['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $metrics['pending_comments'] = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE is_deleted = 0 AND is_approved = 0")
        ->fetchColumn();
} catch (Exception $e) {
    error_log('Admin metrics failed: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Admin dashboard</h1>
    <p>Manage posts, categories, and conversations.</p>
</section>
<?php admin_nav('dashboard'); ?>
<?php $message = $message ?? flash_message(); ?>
<?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['message']); ?></div>
<?php endif; ?>
<section class="card analytics-card">
    <div class="section-header">
        <div>
            <h2>Realtime analytics</h2>
            <p class="muted">Quick view of live page activity via Google Analytics (admin login required).</p>
        </div>
        <a class="button tertiary" href="https://analytics.google.com/analytics/web/#/a163650523p514755040/realtime/pages?params=_u..nav%3Dmaui" target="_blank" rel="noopener">Open in new tab</a>
    </div>
    <div class="analytics-embed">
        <iframe
            class="analytics-frame"
            title="Google Analytics realtime pages"
            src="https://analytics.google.com/analytics/web/#/a163650523p514755040/realtime/pages?params=_u..nav%3Dmaui"
            loading="lazy"
            allowfullscreen>
        </iframe>
    </div>
</section>
<section class="admin-grid">
    <div class="card">
        <h2>Quick stats</h2>
        <ul class="metric-list">
            <li><strong><?php echo $metrics['posts']; ?></strong> posts</li>
            <li><strong><?php echo $metrics['categories']; ?></strong> categories</li>
            <li><strong><?php echo $metrics['pending_comments']; ?></strong> comments awaiting approval</li>
        </ul>
    </div>
    <div class="card">
        <h2>Shortcuts</h2>
        <div class="pill-row">
            <a class="button" href="<?php echo site_url('admin/posts.php'); ?>">Manage posts</a>
            <a class="button secondary" href="<?php echo site_url('admin/categories.php'); ?>">Manage categories</a>
            <a class="button secondary" href="<?php echo site_url('admin/comments.php'); ?>">Moderate comments</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
