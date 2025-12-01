<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

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
            header('Location: /admin/index.php');
            exit;
        } catch (PDOException $e) {
            error_log('Create category failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create category.');
            header('Location: /admin/index.php');
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
            header('Location: /admin/index.php');
            exit;
        } catch (PDOException $e) {
            error_log('Create post failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create post.');
            header('Location: /admin/index.php');
            exit;
        }
    } else {
        $message = ['type' => 'error', 'message' => 'All post fields are required.'];
    }
}

// Fetch categories and posts for dashboard
try {
    $catStmt = $pdo->query('SELECT * FROM categories ORDER BY name');
    $categories = $catStmt->fetchAll();

    $postStmt = $pdo->prepare("SELECT p.id, p.title, p.status, p.published_at, c.name AS category_name
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC");
    $postStmt->execute([current_user()['id']]);
    $userPosts = $postStmt->fetchAll();
} catch (Exception $e) {
    error_log('Admin load failed: ' . $e->getMessage());
    $categories = [];
    $userPosts = [];
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
    <h2>Your posts</h2>
    <?php foreach ($userPosts as $userPost): ?>
        <div class="comment">
            <strong><?php echo htmlspecialchars($userPost['title']); ?></strong>
            <div class="post-meta"><?php echo htmlspecialchars($userPost['category_name']); ?> · <?php echo htmlspecialchars($userPost['status']); ?><?php if ($userPost['published_at']): ?> · <?php echo date('M j, Y', strtotime($userPost['published_at'])); ?><?php endif; ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($userPosts)): ?>
        <p>No posts yet.</p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
