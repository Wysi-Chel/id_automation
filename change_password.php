<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('systems.php');
}

verify_csrf();
$user = current_user();
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$user['id']]);
$passwordHash = $stmt->fetchColumn();

if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
    flash('danger', 'Password not changed: the current password is incorrect.');
    redirect('systems.php');
}

if (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
    flash('danger', 'Password not changed: the new password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.');
    redirect('systems.php');
}

if ($newPassword !== $confirmPassword) {
    flash('danger', 'Password not changed: the new passwords do not match.');
    redirect('systems.php');
}

if (password_verify($newPassword, $passwordHash)) {
    flash('danger', 'Password not changed: the new password is the same as the current one.');
    redirect('systems.php');
}

db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
audit_log(db(), 'PASSWORD_CHANGE', $user['full_name'] . ' changed their password.');
flash('success', 'Password changed. Use the new password the next time you sign in.');
redirect('systems.php');
