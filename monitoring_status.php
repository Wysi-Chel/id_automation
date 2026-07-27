<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$pdo = db();

$logId = (int) ($_POST['log_id'] ?? 0);
$requestedStatus = trim((string) ($_POST['status'] ?? ''));
$returnQuery = trim((string) ($_POST['return_query'] ?? ''));

if ($logId <= 0 || !in_array($requestedStatus, ['done', 'pending'], true)) {
    flash('danger', 'Invalid monitoring record request.');
    redirect('monitoring.php');
}

$check = $pdo->prepare(
    'SELECT id
     FROM audit_logs
     WHERE id = ?
     LIMIT 1'
);

$check->execute([$logId]);

if (!$check->fetchColumn()) {
    flash('danger', 'The monitoring record could not be found.');
    redirect('monitoring.php');
}

if ($requestedStatus === 'done') {
    $statement = $pdo->prepare(
        'UPDATE audit_logs
         SET is_done = 1,
             done_by = :done_by,
             done_at = NOW()
         WHERE id = :id'
    );

    $statement->execute([
        ':done_by' => current_user()['id'] ?? null,
        ':id' => $logId,
    ]);

    flash('success', 'Monitoring record marked as done.');
} else {
    $statement = $pdo->prepare(
        'UPDATE audit_logs
         SET is_done = 0,
             done_by = NULL,
             done_at = NULL
         WHERE id = :id'
    );

    $statement->execute([
        ':id' => $logId,
    ]);

    flash('success', 'Monitoring record reopened.');
}

/*
 * Return to monitoring.php while preserving the current filters.
 * Only approved query parameters are accepted.
 */
$target = 'monitoring.php';

if ($returnQuery !== '') {
    parse_str($returnQuery, $query);

    $allowedParameters = [
        'q',
        'action',
        'department',
        'date_from',
        'date_to',
        'status',
        'page',
    ];

    $safeQuery = array_intersect_key(
        $query,
        array_flip($allowedParameters)
    );

    if ($safeQuery) {
        $target .= '?' . http_build_query($safeQuery);
    }
}

redirect($target);