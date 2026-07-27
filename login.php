<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
        ];
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        audit_log(db(), 'LOGIN', $user['full_name'] . ' signed in.');
        redirect('dashboard.php');
    }

    flash('danger', 'Invalid username or password.');
    redirect('login.php');
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="login-card">
    <h1>Employee ID Maker</h1>
    <p>Sign in to manage employee records and generate IDs.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
