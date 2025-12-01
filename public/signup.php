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
            $_SESSION['user'] = ['id' => $userId, 'username' => $username, 'email' => $email];
            header('Location: /');
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
<section class="card">
    <h2>Create an account</h2>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button class="button" type="submit">Sign up</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
