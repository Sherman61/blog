<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$category = null;
$posts = [];
$likesByPost = [];

if ($slug) {
    try {
        $catStmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ?');
        $catStmt->execute([$slug]);
        $category = $catStmt->fetch();

        if ($category) {
            $postStmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.content, p.published_at
                FROM posts p
                WHERE p.status = 'published' AND p.category_id = ?
                ORDER BY p.published_at DESC");
            $postStmt->execute([$category['id']]);
            $posts = $postStmt->fetchAll();

            if (!empty($posts)) {
                $postIds = array_column($posts, 'id');
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $likeStmt = $pdo->prepare("SELECT post_id, COUNT(*) AS likes FROM post_likes WHERE post_id IN ($placeholders) GROUP BY post_id");
                $likeStmt->execute($postIds);
                foreach ($likeStmt->fetchAll() as $likeRow) {
                    $likesByPost[(int)$likeRow['post_id']] = (int)$likeRow['likes'];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Failed to load category: ' . $e->getMessage());
    }
}
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Curated topic</p>
        <h1><?php echo $category ? htmlspecialchars($category['name']) : 'Category'; ?></h1>
        <?php if ($category && $category['description']): ?>
            <p class="muted"><?php echo htmlspecialchars($category['description']); ?></p>
        <?php endif; ?>
    </div>
    <div class="pill-row">
        <a class="pill" href="<?php echo site_url(); ?>">Back to all</a>
        <span class="pill muted">Depth</span>
        <span class="pill muted">Perspective</span>
    </div>
</section>
<div class="post-grid">
    <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <div class="post-meta"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></div>
            <h3><a href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
            <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)); ?>...</p>
            <div class="card-footer">
                <a class="button ghost" href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>">Read more</a>
                <span class="post-meta"><?php echo $likesByPost[$post['id']] ?? 0; ?> likes</span>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="muted">No posts found for this category.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
