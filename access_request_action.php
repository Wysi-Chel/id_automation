<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/access_requests.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('access_requests.php');
}
verify_csrf();
$pdo = db();
ensure_access_request_schema($pdo);
$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$request = fetch_access_request($pdo, $id);
if (!$request) {
    flash('danger', 'Access request not found.');
    redirect('access_requests.php');
}

if ($action === 'status') {
    $status = trim((string) ($_POST['status'] ?? ''));
    $notes = trim((string) ($_POST['review_notes'] ?? ''));
    if (!in_array($status, ['Pending', 'Under Review', 'Approved', 'Declined', 'Cancelled'], true)) {
        flash('danger', 'The selected review status is not valid.');
        redirect('access_request_view.php?id=' . $id);
    }
    $stmt = $pdo->prepare('UPDATE access_requests SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
    $stmt->execute([$status, $notes !== '' ? $notes : null, current_user()['id'], $id]);
    audit_log($pdo, 'ACCESS_REQUEST_REVIEWED', 'Reviewed DMIS access request ' . $request['reference_no'] . ' and marked it ' . $status . '.', null, ['status' => $request['status']], ['status' => $status]);
    flash('success', 'Access request review saved.');
    redirect('access_request_view.php?id=' . $id);
}

flash('danger', 'Unknown request action.');
redirect('access_request_view.php?id=' . $id);
