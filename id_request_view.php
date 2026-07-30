<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/id_requests.php';
require_auth();
$pdo = db();
ensure_id_request_schema($pdo);
$id = (int) ($_GET['id'] ?? 0);
$request = fetch_id_request($pdo, $id);
if (!$request) {
    http_response_code(404);
    exit('ID request not found.');
}

$pageTitle = $request['reference_no'];
$pageSubtitle = id_request_name($request) . ' · submitted ' . display_datetime($request['created_at']);
require __DIR__ . '/includes/header.php';
?>
<div class="detail-toolbar">
    <div><span class="badge badge-request-<?= e(strtolower(str_replace(' ', '-', $request['status']))) ?>"><?= e($request['status']) ?></span></div>
    <div class="actions"><a class="btn btn-secondary btn-sm" href="id_requests.php">Back to ID requests</a><?php if ($request['employee_id']): ?><a class="btn btn-primary btn-sm" href="employee_view.php?id=<?= (int) $request['employee_id'] ?>">Open employee record</a><?php endif; ?></div>
</div>

<div class="detail-layout">
    <div class="detail-main">
        <section class="card">
            <div class="card-header"><div><span class="section-kicker">Employment details</span><h2>Requested ID information</h2></div></div>
            <div class="card-body">
                <dl class="detail-grid">
                    <div><dt>Employee number</dt><dd><?= e($request['employee_no']) ?></dd></div>
                    <div><dt>Full name</dt><dd><?= e(id_request_name($request)) ?></dd></div>
                    <div><dt>Position</dt><dd><?= e($request['position']) ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($request['department_name']) ?></dd></div>
                    <div><dt>Dealer</dt><dd><?= e(company_label($request['company_code'])) ?></dd></div>
                    <div><dt>Date hired</dt><dd><?= e(display_date($request['date_hired'])) ?></dd></div>
                    <div><dt>Date of birth</dt><dd><?= e(display_date($request['date_of_birth'])) ?></dd></div>
                    <div><dt>Submitted</dt><dd><?= e(display_datetime($request['created_at'])) ?></dd></div>
                    <div><dt>Submitted IP</dt><dd><?= e($request['submitted_ip']) ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><div><span class="section-kicker">ID back information</span><h2>Government and emergency details</h2></div></div>
            <div class="card-body">
                <dl class="detail-grid">
                    <div><dt>SSS number</dt><dd><?= e($request['sss_number'] ?: '—') ?></dd></div>
                    <div><dt>PhilHealth number</dt><dd><?= e($request['philhealth_number'] ?: '—') ?></dd></div>
                    <div><dt>TIN</dt><dd><?= e($request['tin_number'] ?: '—') ?></dd></div>
                    <div><dt>Pag-IBIG / HDMF</dt><dd><?= e($request['hdmf_number'] ?: '—') ?></dd></div>
                    <div><dt>Emergency contact</dt><dd><?= e($request['emergency_contact_name'] ?: '—') ?></dd></div>
                    <div><dt>Emergency number</dt><dd><?= e($request['emergency_contact_number'] ?: '—') ?></dd></div>
                </dl>
                <div class="narrative"><span>Emergency contact address</span><p><?= $request['emergency_contact_address'] ? nl2br(e($request['emergency_contact_address'])) : '<em>Not provided.</em>' ?></p></div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><div><span class="section-kicker">Submitted images</span><h2>Employee photo and signature</h2></div></div>
            <div class="card-body public-admin-images">
                <a href="<?= e($request['photo_path']) ?>" target="_blank"><span>Employee photo</span><img src="<?= e($request['photo_path']) ?>" alt="Submitted employee photo"></a>
                <a href="<?= e($request['signature_path']) ?>" target="_blank"><span>Signature image</span><img src="<?= e($request['signature_path']) ?>" alt="Submitted signature"></a>
            </div>
        </section>
    </div>

    <aside class="detail-side">
        <section class="card sticky-card">
            <div class="card-header"><div><span class="section-kicker">Review</span><h2>Request decision</h2></div></div>
            <div class="card-body">
                <form method="post" action="id_request_action.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                    <input type="hidden" name="action" value="status">
                    <div class="form-group"><label for="status">Status</label><select id="status" name="status" <?= $request['status'] === 'Converted' ? 'disabled' : '' ?>><?php foreach (['Pending','Under Review','Approved','Declined','Cancelled'] as $option): ?><option value="<?= e($option) ?>" <?= $request['status'] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="review_notes">Review notes</label><textarea id="review_notes" name="review_notes" placeholder="Correction, approval, or decline notes"><?= e($request['review_notes'] ?? '') ?></textarea></div>
                    <?php if ($request['status'] !== 'Converted'): ?><button class="btn btn-secondary btn-block" type="submit">Save review</button><?php endif; ?>
                </form>
                <?php if ($request['status'] !== 'Converted'): ?>
                    <form class="convert-request-form" method="post" action="id_request_action.php" onsubmit="return confirm('Create an employee record from this public ID request?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="action" value="convert">
                        <button class="btn btn-primary btn-block" type="submit">Approve and create employee record</button>
                        <p class="help">This copies the verified information and images into ID Monitoring.</p>
                    </form>
                <?php endif; ?>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><div><span class="section-kicker">Requestor contact</span><h2>Clarification details</h2></div></div>
            <div class="card-body summary-list">
                <div><span>Email</span><strong><?= e($request['requester_email'] ?: 'Not provided') ?></strong></div>
                <div><span>Phone</span><strong><?= e($request['requester_phone'] ?: 'Not provided') ?></strong></div>
                <div><span>Last reviewed</span><strong><?= e(display_datetime($request['reviewed_at'])) ?></strong></div>
            </div>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
