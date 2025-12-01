<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
                header('Location: /');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } catch (Exception $e) {
            error_log('Login failed: ' . $e->getMessage());
            $error = 'Unable to log in right now.';
        }
    }
}
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Welcome back</p>
        <h1>Log in</h1>
        <p class="muted">Access your saved likes and join the conversation.</p>
    </div>
</section>
<section class="card elevated">
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="stack">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button class="button" type="submit">Log in</button>
        <p class="muted">No account? <a href="/signup.php">Create one</a>.</p>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
