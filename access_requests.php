<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/access_requests.php';
require_auth();
$pdo = db();
ensure_access_request_schema($pdo);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$allowedStatuses = ['Pending', 'Under Review', 'Approved', 'Declined', 'Cancelled'];
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(r.reference_no LIKE :q OR r.requester_name LIKE :q2 OR r.dmis_username LIKE :q3 OR r.module LIKE :q4)';
    foreach ([':q', ':q2', ':q3', ':q4'] as $key) {
        $params[$key] = '%' . $q . '%';
    }
}
if (in_array($status, $allowedStatuses, true)) {
    $where[] = 'r.status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare(
    "SELECT r.*, d.name AS department_name
     FROM access_requests r JOIN departments d ON d.id = r.department_id
     $whereSql
     ORDER BY FIELD(r.status, 'Pending','Under Review','Approved','Declined','Cancelled'), r.created_at DESC"
);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$counts = array_fill_keys($allowedStatuses, 0);
foreach ($pdo->query('SELECT status, COUNT(*) AS total FROM access_requests GROUP BY status')->fetchAll() as $row) {
    if (array_key_exists($row['status'], $counts)) {
        $counts[$row['status']] = (int) $row['total'];
    }
}

$pageTitle = 'Access Requests';
$pageSubtitle = 'Review public DMIS access submissions.';
require __DIR__ . '/includes/header.php';
?>
<div class="grid grid-3 mb-18">
    <div class="card stat stat-amber"><span class="stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><div class="label">Pending review</div><div class="value"><?= $counts['Pending'] ?></div><div class="hint">New public submissions</div></div></div>
    <div class="card stat stat-blue"><span class="stat-icon"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span><div><div class="label">Approved</div><div class="value"><?= $counts['Approved'] ?></div><div class="hint">Access granted</div></div></div>
    <div class="card stat stat-green"><span class="stat-icon"><svg viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 10h2M16 14h2"/><circle cx="9" cy="10.5" r="2"/></svg></span><div><div class="label">Declined</div><div class="value"><?= $counts['Declined'] ?></div><div class="hint">Access refused</div></div></div>
</div>

<section class="card mb-18">
    <div class="card-body">
        <form method="get" class="filters public-admin-filters">
            <div class="form-group"><label for="q">Search requests</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="Reference, name, username, or module"></div>
            <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $option): ?><option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
            <button class="btn btn-secondary" type="submit"><?= button_icon('filter') ?>Filter</button>
        </form>
    </div>
</section>

<section class="card">
    <div class="card-header"><h2><?= count($requests) ?> access request<?= count($requests) === 1 ? '' : 's' ?></h2><a class="btn btn-secondary btn-sm" href="public_access_request.php" target="_blank">Open public form ↗</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reference / submitted</th><th>Requestor</th><th>Department</th><th>DMIS username</th><th>Module</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><a class="ticket-link" href="access_request_view.php?id=<?= (int) $request['id'] ?>"><?= e($request['reference_no']) ?></a><small><?= e(display_datetime($request['created_at'])) ?></small></td>
                    <td><strong><?= e($request['requester_name']) ?></strong></td>
                    <td><?= e($request['department_name']) ?></td>
                    <td><?= e($request['dmis_username']) ?></td>
                    <td><?= e($request['module']) ?></td>
                    <td><span class="badge badge-request-<?= e(strtolower(str_replace(' ', '-', $request['status']))) ?>"><?= e($request['status']) ?></span></td>
                    <td><a class="btn btn-secondary btn-sm" href="access_request_view.php?id=<?= (int) $request['id'] ?>"><?= button_icon('eye') ?>Review</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?><tr><td colspan="7" class="empty"><strong>No matching access requests</strong></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
