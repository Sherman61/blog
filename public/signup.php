<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user'] = ['id' => $userId, 'username' => $username, 'email' => $email, 'is_admin' => 0];
            header('Location: ' . site_url());
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                $error = 'Username or email already taken.';
            } else {
                error_log('Signup failed: ' . $e->getMessage());
                $error = 'Unable to sign up right now.';
            }
        }
    }
}
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Join the circle</p>
        <h1>Create an account</h1>
        <p class="muted">Save your favorite reads and share your reflections.</p>
    </div>
</section>
<section class="card elevated">
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="stack">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="mindful_reader" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button class="button" type="submit">Sign up</button>
        <p class="muted">Already have an account? <a href="<?php echo site_url('login.php'); ?>">Log in</a>.</p>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
