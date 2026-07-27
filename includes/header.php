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
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark">M</span>
            <span><strong>ID Maker</strong><small>Employee Management</small></span>
        </a>
        <nav>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
            <a class="<?= in_array($currentPage, ['employees.php','employee_form.php','employee_view.php'], true) ? 'active' : '' ?>" href="employees.php">Employee Records</a>        </nav>
        <div class="sidebar-user">
            <strong><?= e($user['full_name']) ?></strong>
            <small><?= e($user['role']) ?></small>
            <a href="logout.php">Sign out</a>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <?php if (!empty($pageSubtitle)): ?><p><?= e($pageSubtitle) ?></p><?php endif; ?>
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
