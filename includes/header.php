<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$flashes = pull_flashes();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" sizes="145x145" href="assets/img/favicon.png">
    <meta name="theme-color" content="#bf1f2f">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar">
        <a class="brand" href="systems.php">
            <span class="brand-mark"><img src="assets/img/favicon.png" alt=""></span>
            <span><small>MICEI Portal</small><strong>ID Monitoring</strong></span>
        </a>
        <nav aria-label="Main navigation">
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                Dashboard
            </a>
            <a class="<?= in_array($currentPage, ['employees.php','employee_form.php','employee_view.php','id_maker.php'], true) ? 'active' : '' ?>" href="employees.php">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 10h2M16 14h2"/><circle cx="9" cy="10.5" r="2"/><path d="M5.5 16a3.7 3.7 0 0 1 7 0"/></svg>
                Employee IDs
            </a>
        </nav>
        <div class="sidebar-user">
            <span class="user-avatar"><?= e(strtoupper(substr((string) ($user['full_name'] ?? 'U'), 0, 1))) ?></span>
            <div>
                <strong><?= e($user['full_name']) ?></strong>
                <small><?= e($user['role']) ?></small>
            </div>
            <a href="systems.php">Back to systems</a>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">IT Department</p>
                <h1><?= e($pageTitle) ?></h1>
                <?php if (!empty($pageSubtitle)): ?><p class="subtitle"><?= e($pageSubtitle) ?></p><?php endif; ?>
            </div>
            <div class="topbar-actions">
                <button class="sidebar-toggle" type="button" aria-controls="app-sidebar" aria-expanded="false">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    Menu
                </button>
                <a class="btn btn-primary" href="employee_form.php">
                    <?= button_icon('plus') ?>
                    Add employee
                </a>
            </div>
        </header>
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
<?php else: ?>
<div class="auth-shell">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
<?php endif; ?>
