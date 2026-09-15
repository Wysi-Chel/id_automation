<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function require_auth(): void
{
    if (!current_user()) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_auth();
    if (!in_array(current_user()['role'] ?? '', ['Super Administrator', 'Administrator'], true)) {
        http_response_code(403);
        exit('Administrator access is required.');
    }
}
