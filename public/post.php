<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    echo '<p>Post not found.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, u.username FROM posts p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.user_id = u.id
        WHERE p.slug = ? AND p.status = 'published'");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
} catch (Exception $e) {
    error_log('Failed to load post: ' . $e->getMessage());
    $post = null;
}

if (!$post) {
    http_response_code(404);
    echo '<p>Post not found.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Likes
$likeCount = 0;
$userLiked = false;
try {
    $likeStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM post_likes WHERE post_id = ?');
    $likeStmt->execute([$post['id']]);
    $likeCount = (int)$likeStmt->fetchColumn();

    if (is_logged_in()) {
        $userLikeStmt = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
        $userLikeStmt->execute([$post['id'], current_user()['id']]);
        $userLiked = (bool)$userLikeStmt->fetch();
    }
} catch (Exception $e) {
    error_log('Failed to load likes: ' . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['like']) && is_logged_in()) {
        try {
            if ($userLiked) {
                $deleteLike = $pdo->prepare('DELETE FROM post_likes WHERE post_id = ? AND user_id = ?');
                $deleteLike->execute([$post['id'], current_user()['id']]);
            } else {
                $insertLike = $pdo->prepare('INSERT IGNORE INTO post_likes (post_id, user_id) VALUES (?, ?)');
                $insertLike->execute([$post['id'], current_user()['id']]);
            }
        } catch (Exception $e) {
            error_log('Failed to toggle like: ' . $e->getMessage());
        }
        header('Location: ' . site_url('post.php?slug=' . urlencode($slug)));
        exit;
    }

    if (isset($_POST['comment']) && is_logged_in()) {
        $content = trim($_POST['content'] ?? '');
        if ($content) {
            try {
                $insertComment = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
                $insertComment->execute([$post['id'], current_user()['id'], $content]);
            } catch (Exception $e) {
                error_log('Failed to add comment: ' . $e->getMessage());
            }
        }
        header('Location: ' . site_url('post.php?slug=' . urlencode($slug)));
        exit;
    }
}

// Comments
$comments = [];
try {
    $commentStmt = $pdo->prepare('SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at DESC');
    $commentStmt->execute([$post['id']]);
    $comments = $commentStmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load comments: ' . $e->getMessage());
}
?>
<article class="article">
    <div class="article-head">
        <div class="badge-row">
            <span class="badge">Posted in <?php echo htmlspecialchars($post['category_name']); ?></span>
            <span class="pill muted"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
        </div>
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <p class="muted">Written by <?php echo htmlspecialchars($post['username']); ?></p>
    </div>
    <div class="article-body"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
</article>
<section class="engagement">
    <div class="engagement-card">
        <div>
            <p class="eyebrow">Support the author</p>
            <p class="muted">A small tap goes a long way. Join the conversation or drop a like.</p>
        </div>
        <form method="post" class="action-row">
            <?php if (is_logged_in()): ?>
                <button class="button" type="submit" name="like" value="1"><?php echo $userLiked ? 'Unlike' : 'Like'; ?> (<?php echo $likeCount; ?>)</button>
            <?php else: ?>
                <a class="button secondary" href="<?php echo site_url('login.php'); ?>">Login to like</a>
                <span class="post-meta"><?php echo $likeCount; ?> likes</span>
            <?php endif; ?>
        </form>
    </div>
</section>
<section class="comments">
    <div class="section-header">
        <div>
            <p class="eyebrow">Conversation</p>
            <h3>Comments</h3>
        </div>
    </div>
    <div class="comment-box">
        <?php if (is_logged_in()): ?>
            <form method="post" class="stack">
                <input type="hidden" name="comment" value="1">
                <div class="form-group">
                    <label for="content">Add your thoughts</label>
                    <textarea id="content" name="content" placeholder="Share your perspective" required></textarea>
                </div>
                <button class="button" type="submit">Post comment</button>
            </form>
        <?php else: ?>
            <p><a href="<?php echo site_url('login.php'); ?>">Login</a> or <a href="<?php echo site_url('signup.php'); ?>">sign up</a> to join the discussion.</p>
        <?php endif; ?>
    </div>

    <div class="comment-list">
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                    <span class="post-meta"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                </div>
                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (empty($comments)): ?>
            <p class="muted">No comments yet.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
