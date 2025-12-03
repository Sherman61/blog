<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$message = flash_message();
$categories = [];
$editingPost = null;

try {
    $catStmt = $pdo->query('SELECT * FROM categories ORDER BY name');
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {
    error_log('Load categories failed: ' . $e->getMessage());
}

// Handle create post
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
            header('Location: ' . site_url('admin/posts.php'));
            exit;
        } catch (PDOException $e) {
            error_log('Create post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create post. Ensure the title is unique.');
            header('Location: ' . site_url('admin/posts.php'));
            exit;
        }
    } else {
        $message = ['type' => 'error', 'message' => 'All post fields are required.'];
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';

    if ($postId > 0 && $title && $content && $categoryId) {
        $slug = slugify($title);
        try {
            $currentStmt = $pdo->prepare('SELECT published_at FROM posts WHERE id = ?');
            $currentStmt->execute([$postId]);
            $current = $currentStmt->fetch();

            $publishedAt = $status === 'published' ? ($current['published_at'] ?? date('Y-m-d H:i:s')) : null;

            $updateStmt = $pdo->prepare('UPDATE posts SET title = ?, slug = ?, content = ?, category_id = ?, status = ?, published_at = ?, updated_at = NOW() WHERE id = ?');
            $updateStmt->execute([$title, $slug, $content, $categoryId, $status, $publishedAt, $postId]);
            set_flash('success', 'Post updated.');
        } catch (PDOException $e) {
            error_log('Update post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to update post.');
        }
    }
    header('Location: ' . site_url('admin/posts.php'));
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        try {
            $deleteStmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
            $deleteStmt->execute([$postId]);
            set_flash('success', 'Post deleted.');
        } catch (PDOException $e) {
            error_log('Delete post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to delete post.');
        }
    }
    header('Location: ' . site_url('admin/posts.php'));
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    try {
        $editStmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
        $editStmt->execute([$editId]);
        $editingPost = $editStmt->fetch();
    } catch (Exception $e) {
        error_log('Load edit post failed: ' . $e->getMessage());
    }
}

$posts = [];
try {
    $postStmt = $pdo->query("SELECT p.id, p.title, p.status, p.published_at, p.category_id, c.name AS category_name
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC");
    $posts = $postStmt->fetchAll();
} catch (Exception $e) {
    error_log('Load posts failed: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Manage posts</h1>
    <p>Create, edit, or delete posts.</p>
</section>
<?php admin_nav('posts'); ?>
<?php $message = $message ?? flash_message(); ?>
<?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['message']); ?></div>
<?php endif; ?>
<div class="admin-grid">
    <section class="card">
        <h2><?php echo $editingPost ? 'Edit post' : 'Create a post'; ?></h2>
        <form method="post">
            <?php if ($editingPost): ?>
                <input type="hidden" name="update_post" value="1">
                <input type="hidden" name="post_id" value="<?php echo $editingPost['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="create_post" value="1">
            <?php endif; ?>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editingPost['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo isset($editingPost['category_id']) && (int)$editingPost['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($editingPost['content'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" <?php echo isset($editingPost['status']) && $editingPost['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo isset($editingPost['status']) && $editingPost['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            <div class="action-row">
                <button class="button" type="submit"><?php echo $editingPost ? 'Update post' : 'Publish'; ?></button>
                <?php if ($editingPost): ?>
                    <a class="button secondary" href="<?php echo site_url('admin/posts.php'); ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h2>Existing posts</h2>
        <?php foreach ($posts as $post): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                <div class="post-meta"><?php echo htmlspecialchars($post['category_name']); ?> · <?php echo htmlspecialchars($post['status']); ?><?php if ($post['published_at']): ?> · <?php echo date('M j, Y', strtotime($post['published_at'])); ?><?php endif; ?></div>
                <div class="action-row" style="margin-top:8px;">
                    <a class="button tertiary" href="<?php echo site_url('admin/posts.php?edit=' . $post['id']); ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this post? This will remove its comments too.');">
                        <input type="hidden" name="delete_post" value="1">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button class="button secondary" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
            <p>No posts yet.</p>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
