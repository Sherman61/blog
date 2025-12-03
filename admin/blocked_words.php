<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_nav.php';
require_admin();

$message = flash_message();
$blockedWords = load_blocked_words();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_blocked_words'])) {
    $rawInput = trim($_POST['blocked_words'] ?? '');

    if ($rawInput !== '') {
        $newWords = preg_split('/[\n,]+/', $rawInput);
        $cleanedWords = array_values(array_filter(array_map(static fn($word) => strtolower(trim($word)), $newWords), static fn($word) => $word !== ''));

        $merged = array_values(array_unique(array_merge($blockedWords, $cleanedWords)));

        if ($merged === $blockedWords) {
            set_flash('info', 'No new blocked words were added.');
        } elseif (save_blocked_words($merged)) {
            $added = array_diff($merged, $blockedWords);
            set_flash('success', 'Blocked words updated. Added: ' . implode(', ', $added));
            $blockedWords = $merged;
        } else {
            set_flash('error', 'Unable to update blocked words list.');
        }
    } else {
        set_flash('error', 'Please enter at least one word to block.');
    }

    header('Location: ' . site_url('admin/blocked_words.php'));
    exit;
}
?>
<section class="hero">
    <h1>Blocked words</h1>
    <p>Review and expand the list of words that are prevented from appearing in comments.</p>
</section>
<?php admin_nav('blocked_words'); ?>
<?php $message = $message ?? flash_message(); ?>
<?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['message']); ?></div>
<?php endif; ?>

<div class="admin-grid">
    <section class="card">
        <h2>Current blocked words</h2>
        <?php if (!empty($blockedWords)): ?>
            <ul class="pill-list">
                <?php foreach ($blockedWords as $word): ?>
                    <li class="pill"><?php echo htmlspecialchars($word); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No blocked words defined yet.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Add blocked words</h2>
        <form method="post">
            <input type="hidden" name="add_blocked_words" value="1">
            <div class="form-group">
                <label for="blocked_words">Words to block</label>
                <textarea id="blocked_words" name="blocked_words" rows="4" placeholder="Enter words separated by commas or new lines" required></textarea>
            </div>
            <p class="muted">You can paste multiple words separated by commas (e.g. spam, scam, troll).</p>
            <button class="button" type="submit">Save blocked words</button>
        </form>
    </section>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
