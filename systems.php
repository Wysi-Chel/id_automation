<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();

$user = current_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring Systems</title>
    <link rel="icon" type="image/png" sizes="145x145" href="assets/img/favicon.png">
    <meta name="theme-color" content="#111827">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="system-hub-body">
<main class="system-hub">
    <header class="system-hub-header">
        <div>
            <span class="system-hub-eyebrow">MICEI Portal</span>
            <h1>Monitoring Systems</h1>
                </div>
        <div class="system-hub-account">
            <span><?= e($user['full_name'] ?? 'System user') ?></span>
            <a href="logout.php">Sign out</a>
        </div>
    </header>

    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <section class="system-launcher-grid" aria-label="Available monitoring systems">
        <a class="system-tile system-tile-mint" href="/automated_id_maker/dashboard.php">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 13h5"/></svg>
                </span>
                <span class="system-tile-state">On</span>
            </span>
            <strong>ID Monitoring</strong>
            <small>Employee records, ID generation, and completion tracking.</small>
            <span class="system-tile-switch"><i></i><b>Open</b></span>
        </a>

        <a class="system-tile system-tile-coral" href="/equipment_repair_monitoring/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m14.7 6.3 3-3a4.5 4.5 0 0 1-5.8 5.8l-6.6 6.6a2.1 2.1 0 0 0 3 3l6.6-6.6a4.5 4.5 0 0 0 5.8-5.8l-3 3z"/></svg>
                </span>
                <span class="system-tile-state">Off</span>
            </span>
            <strong>Equipment Repair Monitoring</strong>
            <small>Repair requests, equipment status, and service history.</small>
            <span class="system-tile-switch is-off"><i></i><b>Setup</b></span>
        </a>

        <a class="system-tile system-tile-indigo" href="/training_monitoring/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m3 9 9-5 9 5-9 5z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"/></svg>
                </span>
                <span class="system-tile-state">Off</span>
            </span>
            <strong>Training Monitoring</strong>
            <small>Training schedules, participation, and employee progress.</small>
            <span class="system-tile-switch is-off"><i></i><b>Setup</b></span>
        </a>

        <a class="system-tile system-tile-blue" href="/system_monitoring/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 12l3-3 3 2 4-4"/></svg>
                </span>
                <span class="system-tile-state">On</span>
            </span>
            <strong>System Monitoring</strong>
            <small>DMIS Monitoring System</small>
            <span class="system-tile-switch"><i></i><b>Open</b></span>
        </a>
    </section>
</main>
</body>
</html>
