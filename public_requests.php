<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Request Forms · MICEI</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <meta name="theme-color" content="#bf1f2f">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/public-requests.css">
    <script src="assets/js/theme.js" defer></script>
</head>
<body class="public-request-body">
<main class="public-request-shell public-hub">
    <nav class="public-nav">
        <a class="public-brand" href="public_requests.php">
            <span><img src="assets/img/favicon.png" alt=""></span>
            <span><small>MICEI</small><strong>Public Request Portal</strong></span>
        </a>
        <div>
            <button class="micei-theme-toggle compact" type="button" data-theme-toggle aria-pressed="false">
                <svg class="theme-icon theme-icon-sun" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="theme-icon theme-icon-moon" aria-hidden="true" viewBox="0 0 24 24"><path d="M20.5 15.4A9 9 0 0 1 8.6 3.5 9 9 0 1 0 20.5 15.4Z"/></svg>
                <span data-theme-label>Dark mode</span>
            </button>
        </div>
    </nav>

    <header class="public-hero">
        <span class="public-kicker">MICEI IT Department</span>
        <h1>Submit a service request</h1>
    </header>

    <section class="public-form-choices">
        <a class="public-choice id-choice" href="public_id_request.php">
            <span class="public-choice-icon">
                <svg viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 10h2M16 14h2"/><circle cx="9" cy="10.5" r="2"/><path d="M5.5 16a3.7 3.7 0 0 1 7 0"/></svg>
            </span>
            <span class="public-choice-state">Public form</span>
            <h2>Employee ID Request</h2>
            <p>Request a new company ID and provide the employee details, photo, and signature needed for processing.</p>
            <span class="public-choice-action">Open ID request form <b>→</b></span>
        </a>
        <a class="public-choice ink-choice" href="/inkmonitoring/public_request.php">
            <span class="public-choice-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2.5s7 7.3 7 12.5a7 7 0 0 1-14 0c0-5.2 7-12.5 7-12.5Z"/><path d="M9 17.5c.7 1 1.7 1.5 3 1.5"/></svg>
            </span>
            <span class="public-choice-state">Public form</span>
            <h2>Printer Ink Request</h2>
            <p>Request printer ink and attach the requisition slip together with a clear image of the current ink level.</p>
            <span class="public-choice-action">Open ink request form <b>→</b></span>
        </a>
    </section>

    <section class="public-notice">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <div><strong>Before submitting</strong><p>Verify all information and attachments. Public submissions enter a Pending queue and remain subject to IT Department review.</p></div>
    </section>
    <footer class="public-footer"><span>MICEI Information Technology Department</span><span></span></footer>
</main>
</body>
</html>
