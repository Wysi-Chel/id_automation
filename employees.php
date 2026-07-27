<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();

$q = trim((string) ($_GET['q'] ?? ''));
$department = (int) ($_GET['department'] ?? 0);
$companyCode = strtoupper(trim((string) ($_GET['company'] ?? '')));
$status = trim((string) ($_GET['status'] ?? ''));
$idStatus = trim((string) ($_GET['id_status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(e.employee_no LIKE :q OR e.first_name LIKE :q OR e.middle_name LIKE :q OR e.last_name LIKE :q OR e.position LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($department > 0) {
    $where[] = 'e.department_id = :department';
    $params[':department'] = $department;
}
if (isset(ID_COMPANIES[$companyCode])) {
    $where[] = 'e.company_code = :company_code';
    $params[':company_code'] = $companyCode;
}
if (in_array($status, ['Active', 'Inactive'], true)) {
    $where[] = 'e.status = :status';
    $params[':status'] = $status;
}
if ($idStatus === 'pending') {
    $where[] = 'e.id_is_done = 0';
} elseif ($idStatus === 'done') {
    $where[] = 'e.id_is_done = 1';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees e $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT e.*, d.name AS department_name, du.full_name AS id_done_by_name
        FROM employees e
        JOIN departments d ON d.id = e.department_id
        LEFT JOIN users du ON du.id = e.id_done_by
        $whereSql ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();
$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$totalPages = max(1, (int) ceil($total / $perPage));

$pageTitle = 'Employee Records';
$pageSubtitle = 'Create, search, review, and manage employee information.';
require __DIR__ . '/includes/header.php';
?>
<div class="card mb-18">
    <div class="card-body">
        <form method="get" class="filters employee-filters">
            <div class="form-group"><label for="q">Search</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="Name, employee no., or position"></div>
            <div class="form-group"><label for="company">Company</label><select id="company" name="company"><option value="">All companies</option><?php foreach (ID_COMPANIES as $code => $label): ?><option value="<?= e($code) ?>" <?= $companyCode === $code ? 'selected' : '' ?>><?= e($code) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="department">Department</label><select id="department" name="department"><option value="0">All departments</option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= $department === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option <?= $status === 'Active' ? 'selected' : '' ?>>Active</option><option <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
            <div class="form-group"><label for="id_status">ID workflow</label><select id="id_status" name="id_status"><option value="">All ID statuses</option><option value="pending" <?= $idStatus === 'pending' ? 'selected' : '' ?>>Pending</option><option value="done" <?= $idStatus === 'done' ? 'selected' : '' ?>>Done</option></select></div>
            <button class="btn btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</div>
<section class="card">
    <div class="card-header"><h2><?= $total ?> employee record<?= $total === 1 ? '' : 's' ?></h2><a class="btn btn-primary" href="employee_form.php">Add employee</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Employee</th><th>Company</th><th>Department</th><th>Position</th><th>Date hired</th><th>Status</th><th>ID workflow</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $employee): ?>
            <tr>
                <td><div class="employee-cell">
                    <?php if ($employee['photo_path']): ?><img class="avatar" src="<?= e($employee['photo_path']) ?>" alt=""><?php else: ?><span class="avatar"></span><?php endif; ?>
                    <div><strong><?= e(full_name($employee)) ?></strong><small><?= e($employee['employee_no']) ?></small></div>
                </div></td>
                <td><span class="badge badge-action" title="<?= e(company_label($employee['company_code'])) ?>"><?= e($employee['company_code']) ?></span></td>
                <td><?= e($employee['department_name']) ?></td>
                <td><?= e($employee['position']) ?></td>
                <td><?= e(display_date($employee['date_hired'])) ?></td>
                <td><span class="badge badge-<?= strtolower($employee['status']) ?>"><?= e($employee['status']) ?></span></td>
                <td>
                    <span class="badge <?= (int)$employee['id_is_done'] === 1 ? 'badge-done' : 'badge-pending' ?>"><?= (int)$employee['id_is_done'] === 1 ? 'Done' : 'Pending' ?></span>
                    <?php if ((int)$employee['id_is_done'] === 1 && $employee['id_done_at']): ?><small class="status-detail"><?= e($employee['id_done_by_name'] ?? 'User') ?> · <?= e(date('M d, Y', strtotime($employee['id_done_at']))) ?></small><?php endif; ?>
                </td>
                <td><div class="actions">
                    <a class="btn btn-secondary btn-sm" href="employee_view.php?id=<?= (int) $employee['id'] ?>">View</a>
                    <a class="btn btn-secondary btn-sm" href="employee_form.php?id=<?= (int) $employee['id'] ?>">Edit</a>
                    <a class="btn btn-primary btn-sm" href="id_maker.php?id=<?= (int) $employee['id'] ?>">Generate ID</a>
                    <form action="employee_done.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="employee_id" value="<?= (int) $employee['id'] ?>">
                        <input type="hidden" name="status" value="<?= (int)$employee['id_is_done'] === 1 ? 'pending' : 'done' ?>">
                        <input type="hidden" name="return_query" value="<?= e(http_build_query($_GET)) ?>">
                        <button class="btn <?= (int)$employee['id_is_done'] === 1 ? 'btn-secondary' : 'btn-primary' ?> btn-sm" type="submit"><?= (int)$employee['id_is_done'] === 1 ? 'Reopen' : 'Mark as done' ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="8" class="empty">No employees matched the selected filters.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if ($totalPages > 1): ?><div class="pagination"><?php for ($i=1; $i<=$totalPages; $i++): $query = $_GET; $query['page']=$i; ?><a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e(http_build_query($query)) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
