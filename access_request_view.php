<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/access_requests.php';
require_auth();
$pdo = db();
ensure_access_request_schema($pdo);
$id = (int) ($_GET['id'] ?? 0);
$request = fetch_access_request($pdo, $id);
if (!$request) {
    http_response_code(404);
    exit('Access request not found.');
}

$pageTitle = $request['reference_no'];
$pageSubtitle = $request['requester_name'] . ' · submitted ' . display_datetime($request['created_at']);
require __DIR__ . '/includes/header.php';
?>
<div class="detail-toolbar">
    <div><span class="badge badge-request-<?= e(strtolower(str_replace(' ', '-', $request['status']))) ?>"><?= e($request['status']) ?></span></div>
    <div class="actions"><a class="btn btn-secondary btn-sm" href="access_requests.php">Back to access requests</a></div>
</div>

<div class="detail-layout">
    <div class="detail-main">
        <section class="card">
            <div class="card-header"><div><span class="section-kicker">Requestor details</span><h2>Request information</h2></div></div>
            <div class="card-body">
                <dl class="detail-grid">
                    <div><dt>Name</dt><dd><?= e($request['requester_name']) ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($request['department_name']) ?></dd></div>
                    <div><dt>DMIS username</dt><dd><?= e($request['dmis_username']) ?></dd></div>
                    <div><dt>Module</dt><dd><?= e($request['module']) ?></dd></div>
                    <div><dt>Submitted</dt><dd><?= e(display_datetime($request['created_at'])) ?></dd></div>
                    <div><dt>Submitted IP</dt><dd><?= e($request['submitted_ip']) ?></dd></div>
                </dl>
                <div class="narrative"><span>Description</span><p><?= nl2br(e($request['description'])) ?></p></div>
            </div>
        </section>
    </div>

    <aside class="detail-side">
        <section class="card sticky-card">
            <div class="card-header"><div><span class="section-kicker">Review</span><h2>Request decision</h2></div></div>
            <div class="card-body">
                <form method="post" action="access_request_action.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <input type="hidden" name="action" value="status">
                    <div class="form-group"><label for="status">Status</label><select id="status" name="status"><?php foreach (['Pending', 'Under Review', 'Approved', 'Declined', 'Cancelled'] as $option): ?><option value="<?= e($option) ?>" <?= $request['status'] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="review_notes">Review notes</label><textarea id="review_notes" name="review_notes" placeholder="Approval scope, decline reason, or follow-up notes"><?= e($request['review_notes'] ?? '') ?></textarea></div>
                    <button class="btn btn-secondary btn-block" type="submit">Save review</button>
                </form>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><div><span class="section-kicker">Status</span><h2>Review history</h2></div></div>
            <div class="card-body summary-list">
                <div><span>Last reviewed</span><strong><?= e(display_datetime($request['reviewed_at'])) ?></strong></div>
            </div>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
