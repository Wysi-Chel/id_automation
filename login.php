<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('systems.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->query(
        "SELECT *
         FROM users
         WHERE is_active = 1
         ORDER BY (role = 'Administrator') DESC, id ASC
         LIMIT 1"
    );
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
        redirect('systems.php');
    }

    flash('danger', 'Invalid password.');
    redirect('login.php');
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="login-card">
    <h1>Monitoring MIS</h1>
    <p></p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        </div>
        <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
