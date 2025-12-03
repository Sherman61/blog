<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$message = flash_message();

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

    header('Location: ' . site_url('admin/comments.php'));
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

    header('Location: ' . site_url('admin/comments.php'));
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
            header('Location: ' . site_url('admin/comments.php'));
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

    header('Location: ' . site_url('admin/comments.php'));
    exit;
}

$recentComments = [];
try {
    $commentStmt = $pdo->query("SELECT c.id, c.content, c.created_at, c.is_deleted, c.is_approved, u.username, p.title AS post_title, p.slug
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN posts p ON c.post_id = p.id
        ORDER BY c.created_at DESC
        LIMIT 25");
    $recentComments = $commentStmt->fetchAll();
} catch (Exception $e) {
    error_log('Admin comments load failed: ' . $e->getMessage());
}
?>
<section class="hero">
    <h1>Moderate comments</h1>
    <p>Review, approve, edit, or remove recent comments.</p>
</section>
<?php admin_nav('comments'); ?>
<?php $message = $message ?? flash_message(); ?>
<?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['message']); ?></div>
<?php endif; ?>
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
