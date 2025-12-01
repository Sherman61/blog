<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$posts = [];
$totalPosts = 0;
$highlightUrl = '#latest';
try {
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.content, p.published_at, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC
        LIMIT 9");
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $countStmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $totalPosts = (int) $countStmt->fetchColumn();

    if (!empty($posts)) {
        $highlightUrl = site_url('public/post.php?slug=' . urlencode($posts[0]['slug']));
    }
} catch (Exception $e) {
    error_log('Failed to load posts: ' . $e->getMessage());
}
?>
<section class="hero">
    <div class="hero-content">
        <p class="eyebrow">Curated calm</p>
        <h1>Shiya's Blog</h1>
        <p class="lead">Thoughtful stories, gentle reminders, and grounded reflections to slow down your scroll.</p>
        <div class="hero-actions">
            <a class="button" href="<?php echo htmlspecialchars($highlightUrl); ?>">Read a highlighted story</a>
            <a class="button tertiary" href="#latest">Browse the library</a>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <span class="stat-number"><?php echo $totalPosts; ?></span>
                <span class="stat-label">Published essays</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?php echo count($categories); ?></span>
                <span class="stat-label">Curated topics</span>
            </div>
            <div class="stat">
                <span class="stat-number">Weekly</span>
                <span class="stat-label">Fresh reflections</span>
            </div>
        </div>
    </div>
    <div class="hero-panel">
        <h3>Popular themes</h3>
        <div class="pill-row">
            <?php foreach ($categories as $cat): ?>
                <a class="pill"
                    href="<?php echo site_url('category.php?slug=' . urlencode($cat['slug'])); ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <p class="panel-text">A tranquil corner of the internet for mental health, personal growth, and reflections that
            resonate.</p>
    </div>
</section>
<section id="latest" class="section-header">
    <div>
        <p class="eyebrow">Latest stories</p>
        <h2>Fresh perspectives, ready to read</h2>
        <p class="muted">Explore the newest essays from our writers. Each read comes with a dose of calm and clarity.
        </p>
    </div>
    <div class="pill-row">
        <span class="pill muted">New</span>
        <span class="pill muted">Wellness</span>
        <span class="pill muted">Insights</span>
    </div>
</section>
<div class="post-grid">
    <?php foreach ($posts as $index => $post): ?>
        <article class="post-card <?php echo $index === 0 ? 'post-card--feature' : ''; ?>">
            <div class="post-meta">
                <span class="badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                <span><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
            </div>
            <h3><a
                    href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
            </h3>
            <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 170)); ?>...</p>
            <div class="card-footer">
                <a class="button ghost" href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>">Read
                    more</a>
                <span class="read-time">~<?php echo max(2, ceil(str_word_count(strip_tags($post['content'])) / 200)); ?> min
                    read</span>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="muted">No posts yet. Check back soon!</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>