<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Sign-in choices, in display order: username => label.
const LOGIN_ACCOUNTS = [
    'sa' => 'SA',
    'ita' => 'ITA',
    'jrn' => 'JRN',
];

if (current_user()) {
    redirect('systems.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!isset(LOGIN_ACCOUNTS[$username])) {
        flash('danger', 'Choose an account to sign in.');
        redirect('login.php');
    }

    $stmt = db()->prepare(
        "SELECT *
         FROM users
         WHERE username = ?
           AND is_active = 1
         LIMIT 1"
    );
    $stmt->execute([$username]);
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

    flash('danger', 'Incorrect password for ' . LOGIN_ACCOUNTS[$username] . '.');
    redirect('login.php?account=' . urlencode($username));
}

$selectedAccount = (string) ($_GET['account'] ?? '');
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="login-card">
    <h1>Monitoring MIS</h1>
    <p></p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <fieldset class="login-accounts">
            <legend>Sign in as</legend>
            <div class="login-account-options">
                <?php foreach (LOGIN_ACCOUNTS as $accountUsername => $accountLabel): ?>
                    <label class="login-account-option">
                        <input type="radio" name="username" value="<?= e($accountUsername) ?>" <?= $accountUsername === $selectedAccount ? 'checked' : '' ?> required>
                        <span><?= e($accountLabel) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="current-password" required <?= isset(LOGIN_ACCOUNTS[$selectedAccount]) ? 'autofocus' : '' ?>>
                <?= password_toggle_button() ?>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Sign in</button>
    </form>
    <a class="public-forms-login-link" href="public_requests.php">Open public request forms →</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
