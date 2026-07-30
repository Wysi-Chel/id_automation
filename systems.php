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
    <title>System Launcher · MICEI Portal</title>
    <link rel="icon" type="image/png" sizes="145x145" href="assets/img/favicon.png">
    <meta name="theme-color" content="#f4f5f7">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/theme.js" defer></script>
</head>
<body class="system-hub-body">
<div class="system-hub-backdrop" aria-hidden="true">
    <span></span>
    <span></span>
</div>
<main class="system-hub">
    <nav class="system-hub-nav" aria-label="Portal navigation">
        <a class="system-hub-brand" href="systems.php" aria-label="MICEI portal home">
            <span class="system-hub-brand-mark">
                <img src="assets/img/favicon.png" alt="">
            </span>
            <span>
                <small>MICEI</small>
                <strong>Operations Portal</strong>
            </span>
        </a>
        <div class="system-hub-account">
            <a class="system-hub-public-link" href="public_requests.php">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                <span>Public forms</span>
            </a>
            <button class="micei-theme-toggle compact" type="button" data-theme-toggle aria-pressed="false">
                <svg class="theme-icon theme-icon-sun" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="theme-icon theme-icon-moon" aria-hidden="true" viewBox="0 0 24 24"><path d="M20.5 15.4A9 9 0 0 1 8.6 3.5 9 9 0 1 0 20.5 15.4Z"/></svg>
                <span data-theme-label>Dark mode</span>
            </button>
            <span class="system-hub-account-copy">
                <small>Signed in as</small>
                <strong><?= e($user['full_name'] ?? 'System user') ?></strong>
            </span>
            <span class="system-hub-avatar" aria-hidden="true">
                <?= e(strtoupper(substr((string) ($user['full_name'] ?? 'U'), 0, 1))) ?>
            </span>
            <a class="system-hub-signout" href="logout.php">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M10 17l5-5-5-5M15 12H3"/>
                    <path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/>
                </svg>
                <span>Sign out</span>
            </a>
        </div>
    </nav>

    <header class="system-hub-header">
        <div class="system-hub-intro">
            <span class="system-hub-eyebrow">
                <i aria-hidden="true"></i>
                Systems directory
            </span>
            <h1>Choose your workspace</h1>
        </div>

    </header>

    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <section class="system-launcher-grid" aria-label="Available monitoring systems">
        <a class="system-tile system-tile-red" href="/micei_mis/dashboard.php">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="3.5" y="5" width="17" height="14" rx="2.5"/>
                        <circle cx="8.5" cy="10" r="2"/>
                        <path d="M5.8 16c.5-2 1.4-3 2.7-3s2.2 1 2.7 3M14 9h3.5M14 13h3.5"/>
                    </svg>
                </span>
                <span class="system-tile-state"><i aria-hidden="true"></i> Available</span>
            </span>
            <span class="system-tile-copy">
                <strong>ID Monitoring</strong>
                <small>Manage employee records, generate company IDs, and monitor completion and release.</small>
            </span>
            <span class="system-tile-footer">
                <span class="system-tile-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </span>
        </a>

        <a class="system-tile system-tile-amber" href="/e-repair_system/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m14.7 6.3 3-3a4.5 4.5 0 0 1-5.8 5.8l-6.6 6.6a2.1 2.1 0 0 0 3 3l6.6-6.6a4.5 4.5 0 0 0 5.8-5.8l-3 3z"/>
                    </svg>
                </span>
                <span class="system-tile-state"><i aria-hidden="true"></i> Available</span>
            </span>
            <span class="system-tile-copy">
                <strong>Equipment Repair Monitoring</strong>
                <small>Track repair requests, equipment status, service progress, and maintenance history.</small>
            </span>
            <span class="system-tile-footer">
                <span class="system-tile-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </span>
        </a>

        <a class="system-tile system-tile-blue" href="/inkmonitoring/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.5s7 7.3 7 12.5a7 7 0 0 1-14 0c0-5.2 7-12.5 7-12.5Z"/>
                        <path d="M9 17.5c.7 1 1.7 1.5 3 1.5"/>
                    </svg>
                </span>
                <span class="system-tile-state"><i aria-hidden="true"></i> Available</span>
            </span>
            <span class="system-tile-copy">
                <strong>Ink Monitoring</strong>
                <small>Track printer ink requests, requisition slips, ink-level evidence, and issuance status.</small>
            </span>
            <span class="system-tile-footer">
                <span class="system-tile-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </span>
        </a>

        <a class="system-tile system-tile-indigo" href="/training_monitoring/">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m3 9 9-5 9 5-9 5z"/>
                        <path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"/>
                    </svg>
                </span>
                <span class="system-tile-state is-setup"><i aria-hidden="true"></i> In setup</span>
            </span>
            <span class="system-tile-copy">
                <strong>Training Monitoring</strong>
                <small>Organize training schedules, participation records, and employee development progress.</small>
            </span>
            <span class="system-tile-footer">
                <span>View setup</span>
                <span class="system-tile-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </span>
        </a>

        <a class="system-tile system-tile-blue" href="/system_monitoring/index.php">
            <span class="system-tile-top">
                <span class="system-tile-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="13" rx="2"/>
                        <path d="M8 21h8M12 17v4M7 12l3-3 3 2 4-4"/>
                    </svg>
                </span>
                <span class="system-tile-state"><i aria-hidden="true"></i> Available</span>
            </span>
            <span class="system-tile-copy">
                <strong>System Monitoring</strong>
                <small>Access DMIS monitoring and maintain visibility across essential internal systems.</small>
            </span>
            <span class="system-tile-footer">
                <span class="system-tile-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </span>
        </a>
    </section>

    <footer class="system-hub-footer">
    </footer>
</main>
</body>
</html>
