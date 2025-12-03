<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$message = flash_message();
$editingCategory = null;

// Create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($name) {
        $slug = slugify($name);
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
            $stmt->execute([$name, $slug, $description ?: null]);
            set_flash('success', 'Category created successfully.');
            header('Location: ' . site_url('admin/categories.php'));
            exit;
        } catch (PDOException $e) {
            error_log('Create category failed: ' . $e->getMessage());
            set_flash('error', 'Unable to create category. Make sure the name is unique.');
            header('Location: ' . site_url('admin/categories.php'));
            exit;
        }
    } else {
        $message = ['type' => 'error', 'message' => 'Category name is required.'];
    }
}

// Update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($categoryId > 0 && $name) {
        $slug = slugify($name);
        try {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?');
            $stmt->execute([$name, $slug, $description ?: null, $categoryId]);
            set_flash('success', 'Category updated.');
        } catch (PDOException $e) {
            error_log('Update category failed: ' . $e->getMessage());
            set_flash('error', 'Unable to update category.');
        }
    }
    header('Location: ' . site_url('admin/categories.php'));
    exit;
}

// Delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    if ($categoryId > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$categoryId]);
            set_flash('success', 'Category deleted.');
        } catch (PDOException $e) {
            error_log('Delete category failed: ' . $e->getMessage());
            set_flash('error', 'Unable to delete category. It may be in use by posts.');
        }
    }
    header('Location: ' . site_url('admin/categories.php'));
    exit;
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$editId]);
        $editingCategory = $stmt->fetch();
    } catch (Exception $e) {
        error_log('Load category failed: ' . $e->getMessage());
    }
}

$categories = [];
try {
    $catStmt = $pdo->query('SELECT * FROM categories ORDER BY name');
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {
    error_log('Load categories failed: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Manage categories</h1>
    <p>Create, edit, or remove categories shown across the site.</p>
</section>
<?php admin_nav('categories'); ?>
<?php $message = $message ?? flash_message(); ?>
<?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['message']); ?></div>
<?php endif; ?>
<div class="admin-grid">
    <section class="card">
        <h2><?php echo $editingCategory ? 'Edit category' : 'Add category'; ?></h2>
        <form method="post">
            <?php if ($editingCategory): ?>
                <input type="hidden" name="update_category" value="1">
                <input type="hidden" name="category_id" value="<?php echo $editingCategory['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="create_category" value="1">
            <?php endif; ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editingCategory['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($editingCategory['description'] ?? ''); ?></textarea>
            </div>
            <div class="action-row">
                <button class="button" type="submit"><?php echo $editingCategory ? 'Update category' : 'Create category'; ?></button>
                <?php if ($editingCategory): ?>
                    <a class="button secondary" href="<?php echo site_url('admin/categories.php'); ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card">
        <h2>Existing categories</h2>
        <?php foreach ($categories as $cat): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                <p class="muted">Slug: <?php echo htmlspecialchars($cat['slug']); ?></p>
                <?php if ($cat['description']): ?>
                    <p><?php echo htmlspecialchars($cat['description']); ?></p>
                <?php endif; ?>
                <div class="action-row" style="margin-top:8px;">
                    <a class="button tertiary" href="<?php echo site_url('admin/categories.php?edit=' . $cat['id']); ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this category? Posts linked to it will block deletion.');">
                        <input type="hidden" name="delete_category" value="1">
                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                        <button class="button secondary" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <p>No categories yet.</p>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
