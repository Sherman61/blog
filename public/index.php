<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$posts = [];
$totalPosts = 0;
$highlightUrl = '#latest';
$hashtagsByPost = [];
$likesByPost = [];
$popularHashtags = [];
$hashtagFilter = trim($_GET['hashtag'] ?? '');
$hashtagQuery = ltrim($hashtagFilter, '#');
$hashtagSlug = $hashtagQuery ? slugify($hashtagQuery) : '';
$activeHashtagName = '';

try {
$popularStmt = $pdo->query("SELECT h.name, h.slug, COUNT(ph.post_id) AS uses
    FROM hashtags h
    JOIN post_hashtags ph ON ph.hashtag_id = h.id
    JOIN posts p ON p.id = ph.post_id AND p.status = 'published'
    GROUP BY h.id, h.name, h.slug
    ORDER BY uses DESC, h.name
    LIMIT 10");
    $popularHashtags = $popularStmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load popular hashtags: ' . $e->getMessage());
}

try {
    if ($hashtagSlug) {
        $tagNameStmt = $pdo->prepare('SELECT name FROM hashtags WHERE slug = ?');
        $tagNameStmt->execute([$hashtagSlug]);
        $activeHashtagName = (string)$tagNameStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.content, p.published_at, c.name AS category_name, c.slug AS category_slug
            FROM posts p
            JOIN categories c ON p.category_id = c.id
            JOIN post_hashtags ph ON ph.post_id = p.id
            JOIN hashtags h ON h.id = ph.hashtag_id
            WHERE p.status = 'published' AND h.slug = ?
            GROUP BY p.id, p.title, p.slug, p.content, p.published_at, c.name, c.slug
            ORDER BY p.published_at DESC
            LIMIT 12");
        $stmt->execute([$hashtagSlug]);
        $posts = $stmt->fetchAll();

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id)
            FROM posts p
            JOIN post_hashtags ph ON ph.post_id = p.id
            JOIN hashtags h ON h.id = ph.hashtag_id
            WHERE p.status = 'published' AND h.slug = ?");
        $countStmt->execute([$hashtagSlug]);
        $totalPosts = (int)$countStmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.content, p.published_at, c.name AS category_name, c.slug AS category_slug
            FROM posts p
            JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.published_at DESC
            LIMIT 12");
        $stmt->execute();
        $posts = $stmt->fetchAll();

        $countStmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        $totalPosts = (int)$countStmt->fetchColumn();
    }

    if (!empty($posts)) {
        $highlightUrl = site_url('post.php?slug=' . urlencode($posts[0]['slug']));

        $postIds = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $tagStmt = $pdo->prepare("SELECT ph.post_id, h.name, h.slug FROM post_hashtags ph JOIN hashtags h ON h.id = ph.hashtag_id WHERE ph.post_id IN ($placeholders) ORDER BY h.name");
        $tagStmt->execute($postIds);
        foreach ($tagStmt->fetchAll() as $row) {
            $hashtagsByPost[$row['post_id']][] = $row;
        }

        $likeStmt = $pdo->prepare("SELECT post_id, COUNT(*) AS likes FROM post_likes WHERE post_id IN ($placeholders) GROUP BY post_id");
        $likeStmt->execute($postIds);
        foreach ($likeStmt->fetchAll() as $likeRow) {
            $likesByPost[(int)$likeRow['post_id']] = (int)$likeRow['likes'];
        }
    }
} catch (Exception $e) {
    error_log('Failed to load posts: ' . $e->getMessage());
}
?>
<section class="hero">
    <div class="hero-content">
        <p class="eyebrow">Curated calm</p>
        <h1>Shiya's Blog</h1>
        <p class="lead">Reflective writing on wellbeing, growth, and the things worth pausing for.</p>
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
                <span class="stat-label">Topics</span>
            </div>
            <div class="stat">
                <span class="stat-number">#</span>
                <span class="stat-label">Follow hashtags you love</span>
            </div>
        </div>
    </div>
    <div class="hero-panel">
        <h3>Find by hashtag</h3>
        <form class="search-card" method="get" action="index.php">
            <label for="hashtag" class="muted">Search hashtags</label>
            <div class="search-row">
                <span class="muted">#</span>
                <input type="text" id="hashtag" name="hashtag" placeholder="mindful" value="<?php echo htmlspecialchars($hashtagQuery); ?>">
                <button class="button ghost" type="submit">Search</button>
            </div>
            <p class="muted">Try searching for a mood or theme. You can type with or without the # sign.</p>
        </form>
        <?php if (!empty($popularHashtags)): ?>
            <div class="tag-cloud">
                <?php foreach ($popularHashtags as $tag): ?>
                    <a class="pill" href="<?php echo site_url('index.php?hashtag=' . urlencode($tag['slug'])); ?>">#<?php echo htmlspecialchars($tag['name']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p class="panel-text">Browse by feeling or idea, then dive into the stories behind it.</p>
    </div>
</section>
<section id="latest" class="section-header">
    <div>
        <p class="eyebrow"><?php echo $hashtagSlug ? 'Filtered by hashtag' : 'Latest stories'; ?></p>
        <h2>
            <?php if ($hashtagSlug): ?>
                Posts tagged #<?php echo htmlspecialchars($activeHashtagName ?: $hashtagQuery); ?>
            <?php else: ?>
                Fresh reads for this week
            <?php endif; ?>
        </h2>
        <?php if ($hashtagSlug): ?>
            <p class="muted">Showing <?php echo $totalPosts; ?> post<?php echo $totalPosts === 1 ? '' : 's'; ?> with this hashtag. <a class="pill" href="<?php echo site_url('index.php'); ?>">Clear filter</a></p>
        <?php endif; ?>
    </div>
    <div class="pill-row">
        <?php foreach ($categories as $cat): ?>
            <a class="pill" href="<?php echo site_url('category.php?slug=' . urlencode($cat['slug'])); ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
        <?php endforeach; ?>
    </div>
</section>
<div class="post-grid">
    <?php foreach ($posts as $index => $post): ?>
        <article class="post-card <?php echo $index === 0 ? 'post-card--feature' : ''; ?>">
            <div class="post-meta">
                <span class="badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                <span><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
            </div>
            <h3><a href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
            <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 170)); ?>...</p>
            <?php if (!empty($hashtagsByPost[$post['id']])): ?>
                <div class="pill-row">
                    <?php foreach ($hashtagsByPost[$post['id']] as $tag): ?>
                        <a class="pill muted" href="<?php echo site_url('index.php?hashtag=' . urlencode($tag['slug'])); ?>">#<?php echo htmlspecialchars($tag['name']); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="card-footer">
                <a class="button ghost" href="<?php echo site_url('post.php?slug=' . urlencode($post['slug'])); ?>">Read more</a>
                <span class="post-meta"><?php echo $likesByPost[$post['id']] ?? 0; ?> likes</span>
                <span class="read-time">~<?php echo max(2, ceil(str_word_count(strip_tags($post['content'])) / 200)); ?> min read</span>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
        <p class="muted">No posts found yet. Try another hashtag or check back soon!</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>