<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = flash_message();

// Handle new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($name) {
        $slug = slugify($name);
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
            $stmt->execute([$name, $slug, $description ?: null]);
            set_flash('success', 'Category created successfully.');
            header('Location: ' . site_url('admin/index.php'));
            exit;
        } catch (PDOException $e) {
            error_log('Create category failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create category.');
            header('Location: ' . site_url('admin/index.php'));
            exit;
        }
    } else {
        $message = ['type' => 'error', 'message' => 'Category name is required.'];
    }
}

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';

    if ($title && $content && $categoryId) {
        $slug = slugify($title);
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
        try {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, category_id, title, slug, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([current_user()['id'], $categoryId, $title, $slug, $content, $status, $publishedAt]);
            set_flash('success', 'Post created successfully.');
            header('Location: ' . site_url('admin/index.php'));
            exit;
        } catch (PDOException $e) {
            error_log('Create post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create post.');
            header('Location: ' . site_url('admin/index.php'));
            exit;
        }
    } else {
        $message = ['type' => 'error', 'message' => 'All post fields are required.'];
    }
}

// Publish draft
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_post'])) {
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId > 0) {
        try {
            $publishStmt = $pdo->prepare("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = ?");
            $publishStmt->execute([$postId]);

            if ($publishStmt->rowCount() > 0) {
                set_flash('success', 'Draft published successfully.');
            } else {
                set_flash('error', 'Post not found or already published.');
            }
        } catch (PDOException $e) {
            error_log('Publish post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to publish post.');
        }
    }

    header('Location: ' . site_url('admin/index.php'));
    exit;
}

// Delete comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId > 0) {
        try {
            $deleteStmt = $pdo->prepare('UPDATE comments SET is_deleted = 1 WHERE id = ?');
            $deleteStmt->execute([$commentId]);

            if ($deleteStmt->rowCount() > 0) {
                set_flash('success', 'Comment deleted.');
            } else {
                set_flash('error', 'Comment not found.');
            }
        } catch (PDOException $e) {
            error_log('Delete comment failed: ' . $e->getMessage());
            set_flash('error', 'Unable to delete comment.');
        }
    }

    header('Location: ' . site_url('admin/index.php'));
    exit;
}

// Approve comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_comment'])) {
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId > 0) {
        try {
            $approveStmt = $pdo->prepare('UPDATE comments SET is_approved = 1 WHERE id = ?');
            $approveStmt->execute([$commentId]);

            if ($approveStmt->rowCount() > 0) {
                set_flash('success', 'Comment approved.');
            } else {
                set_flash('error', 'Comment not found.');
            }
        } catch (PDOException $e) {
            error_log('Approve comment failed: ' . $e->getMessage());
            set_flash('error', 'Unable to approve comment.');
        }
    }

    header('Location: ' . site_url('admin/index.php'));
    exit;
}

// Edit comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($commentId > 0 && $content) {
        $blocked = find_blocked_words($content);
        if ($blocked) {
            set_flash('error', 'Edited comment still contains blocked words: ' . implode(', ', $blocked));
            header('Location: ' . site_url('admin/index.php'));
            exit;
        }

        try {
            $editStmt = $pdo->prepare('UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?');
            $editStmt->execute([$content, $commentId]);
            set_flash('success', 'Comment updated.');
        } catch (PDOException $e) {
            error_log('Edit comment failed: ' . $e->getMessage());
            set_flash('error', 'Unable to edit comment.');
        }
    }

    header('Location: ' . site_url('admin/index.php'));
    exit;
}

// Fetch categories and posts for dashboard
try {
    $catStmt = $pdo->query('SELECT * FROM categories ORDER BY name');
    $categories = $catStmt->fetchAll();

    $postStmt = $pdo->query("SELECT p.id, p.title, p.status, p.published_at, c.name AS category_name, u.username
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC");
    $userPosts = $postStmt->fetchAll();

    $commentStmt = $pdo->query("SELECT c.id, c.content, c.created_at, c.is_deleted, c.is_approved, u.username, p.title AS post_title, p.slug
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN posts p ON c.post_id = p.id
        ORDER BY c.created_at DESC
        LIMIT 15");
    $recentComments = $commentStmt->fetchAll();
} catch (Exception $e) {
    error_log('Admin load failed: ' . $e->getMessage());
    $categories = [];
    $userPosts = [];
    $recentComments = [];
}
?>
<section class="hero">
    <h1>Admin dashboard</h1>
    <p>Publish new categories and posts.</p>
</section>
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
<div class="admin-grid">
    <section class="card">
        <h2>Create a post</h2>
        <form method="post">
            <input type="hidden" name="create_post" value="1">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <button class="button" type="submit">Publish</button>
        </form>
    </section>
    <section class="card">
        <h2>Add category</h2>
        <form method="post">
            <input type="hidden" name="create_category" value="1">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <button class="button" type="submit">Create category</button>
        </form>
    </section>
</div>
<section class="card" style="margin-top:20px;">
    <h2>Posts</h2>
    <?php foreach ($userPosts as $userPost): ?>
        <div class="comment">
            <strong><?php echo htmlspecialchars($userPost['title']); ?></strong>
            <div class="post-meta"><?php echo htmlspecialchars($userPost['category_name']); ?> · by <?php echo htmlspecialchars($userPost['username']); ?> · <?php echo htmlspecialchars($userPost['status']); ?><?php if ($userPost['published_at']): ?> · <?php echo date('M j, Y', strtotime($userPost['published_at'])); ?><?php endif; ?></div>
            <?php if ($userPost['status'] === 'draft'): ?>
                <form method="post" class="action-row" style="margin-top:8px;">
                    <input type="hidden" name="publish_post" value="1">
                    <input type="hidden" name="post_id" value="<?php echo $userPost['id']; ?>">
                    <button class="button" type="submit">Publish draft</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($userPosts)): ?>
        <p>No posts yet.</p>
    <?php endif; ?>
</section>

<section class="card" style="margin-top:20px;">
    <h2>Recent comments</h2>
    <?php foreach ($recentComments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                    <span class="post-meta"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                    <?php if (!$comment['is_approved']): ?>
                        <span class="badge secondary">Pending</span>
                    <?php else: ?>
                        <span class="badge">Approved</span>
                    <?php endif; ?>
                </div>
                <p class="muted">On <a href="<?php echo site_url('post.php?slug=' . urlencode($comment['slug'])); ?>"><?php echo htmlspecialchars($comment['post_title']); ?></a></p>
                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
            <?php if (!$comment['is_deleted']): ?>
                <form method="post" class="action-row" style="margin-top:8px;">
                    <input type="hidden" name="delete_comment" value="1">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <button class="button secondary" type="submit">Delete comment</button>
                </form>
                <?php if (!$comment['is_approved']): ?>
                    <form method="post" class="action-row" style="margin-top:8px;">
                        <input type="hidden" name="approve_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <button class="button" type="submit">Approve comment</button>
                    </form>
                <?php endif; ?>
                <form method="post" class="stack" style="margin-top:12px;">
                    <input type="hidden" name="edit_comment" value="1">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <label for="comment-<?php echo $comment['id']; ?>">Edit content</label>
                    <textarea id="comment-<?php echo $comment['id']; ?>" name="content" required><?php echo htmlspecialchars($comment['content']); ?></textarea>
                    <button class="button tertiary" type="submit">Save edit</button>
                </form>
            <?php else: ?>
                <p class="muted">Comment deleted.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($recentComments)): ?>
        <p>No comments yet.</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
