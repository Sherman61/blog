<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$category = null;
$posts = [];

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
        }
    } catch (Exception $e) {
        error_log('Failed to load category: ' . $e->getMessage());
    }
}
?>
<section class="hero">
    <h1><?php echo $category ? htmlspecialchars($category['name']) : 'Category'; ?></h1>
    <?php if ($category && $category['description']): ?>
        <p><?php echo htmlspecialchars($category['description']); ?></p>
    <?php endif; ?>
</section>
<div class="grid">
    <?php foreach ($posts as $post): ?>
        <article class="card">
            <div class="post-meta"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></div>
            <h3><a href="/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
            <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)); ?>...</p>
        </article>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p>No posts found for this category.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
