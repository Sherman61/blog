<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$drafts = [];
$hashtagsByPost = [];

try {
    $draftStmt = $pdo->query("SELECT p.id, p.title, p.slug, p.content, p.updated_at, p.created_at, c.name AS category_name
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'draft'
        ORDER BY p.updated_at DESC, p.created_at DESC");
    $drafts = $draftStmt->fetchAll();

    $postIds = array_column($drafts, 'id');
    if (!empty($postIds)) {
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $tagStmt = $pdo->prepare("SELECT ph.post_id, h.name FROM post_hashtags ph JOIN hashtags h ON h.id = ph.hashtag_id WHERE ph.post_id IN ($placeholders) ORDER BY h.name");
        $tagStmt->execute($postIds);
        foreach ($tagStmt->fetchAll() as $row) {
            $hashtagsByPost[$row['post_id']][] = $row['name'];
        }
    }
} catch (Exception $e) {
    error_log('Load drafts failed: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Drafts</h1>
    <p>Review posts that are still in progress and jump back into editing.</p>
</section>
<?php admin_nav('drafts'); ?>
<div class="card elevated">
    <h2>Unpublished drafts</h2>
    <?php foreach ($drafts as $draft): ?>
        <div class="comment">
            <div class="comment-header">
                <strong><?php echo htmlspecialchars($draft['title']); ?></strong>
                <span class="post-meta">Updated <?php echo date('M j, Y g:i A', strtotime($draft['updated_at'] ?? $draft['created_at'])); ?></span>
            </div>
            <p class="muted" style="margin: 4px 0 8px;">Category: <?php echo htmlspecialchars($draft['category_name']); ?></p>
            <p><?php echo htmlspecialchars(substr(strip_tags($draft['content']), 0, 140)); ?>...</p>
            <?php if (!empty($hashtagsByPost[$draft['id']])): ?>
                <div class="pill-row" style="margin-top:6px;">
                    <?php foreach ($hashtagsByPost[$draft['id']] as $tag): ?>
                        <span class="pill muted">#<?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="action-row" style="margin-top:10px;">
                <a class="button tertiary" href="<?php echo site_url('admin/posts.php?edit=' . $draft['id']); ?>">Edit draft</a>
                <span class="pill">Draft</span>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($drafts)): ?>
        <p class="muted">No drafts right now. Create a new post to start writing.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
