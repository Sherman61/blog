<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$posts = [];
try {
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.content, p.published_at, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC
        LIMIT 9");
    $stmt->execute();
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load posts: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Mindful Musings</h1>
    <p>Thoughts, reflections, and opinions on mental health and everyday realizations.</p>
</section>
<section>
    <h2>Latest stories</h2>
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <article class="card">
                <div class="post-meta"><?php echo htmlspecialchars($post['category_name']); ?> · <?php echo date('M j, Y', strtotime($post['published_at'])); ?></div>
                <h3><a href="/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)); ?>...</p>
                <a class="button secondary" href="/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">Read more</a>
            </article>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
            <p>No posts yet. Check back soon!</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
