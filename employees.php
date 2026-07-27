<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();

$q = trim((string) ($_GET['q'] ?? ''));
$department = (int) ($_GET['department'] ?? 0);
$status = trim((string) ($_GET['status'] ?? ''));
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
if (in_array($status, ['Active', 'Inactive'], true)) {
    $where[] = 'e.status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees e $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT e.*, d.name AS department_name
        FROM employees e JOIN departments d ON d.id = e.department_id
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
        <form method="get" class="filters">
            <div class="form-group"><label for="q">Search</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="Name, employee no., or position"></div>
            <div class="form-group"><label for="department">Department</label><select id="department" name="department"><option value="0">All departments</option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= $department === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option <?= $status === 'Active' ? 'selected' : '' ?>>Active</option><option <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
            <div></div>
            <button class="btn btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</div>
<section class="card">
    <div class="card-header"><h2><?= $total ?> employee record<?= $total === 1 ? '' : 's' ?></h2><a class="btn btn-primary" href="employee_form.php">Add employee</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Date hired</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $employee): ?>
            <tr>
                <td><div class="employee-cell">
                    <?php if ($employee['photo_path']): ?><img class="avatar" src="<?= e($employee['photo_path']) ?>" alt=""><?php else: ?><span class="avatar"></span><?php endif; ?>
                    <div><strong><?= e(full_name($employee)) ?></strong><small><?= e($employee['employee_no']) ?></small></div>
                </div></td>
                <td><?= e($employee['department_name']) ?></td>
                <td><?= e($employee['position']) ?></td>
                <td><?= e(display_date($employee['date_hired'])) ?></td>
                <td><span class="badge badge-<?= strtolower($employee['status']) ?>"><?= e($employee['status']) ?></span></td>
                <td><div class="actions"><a class="btn btn-secondary btn-sm" href="employee_view.php?id=<?= (int) $employee['id'] ?>">View</a><a class="btn btn-secondary btn-sm" href="employee_form.php?id=<?= (int) $employee['id'] ?>">Edit</a><a class="btn btn-primary btn-sm" href="id_maker.php?id=<?= (int) $employee['id'] ?>">Generate ID</a></div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="6" class="empty">No employees matched the selected filters.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if ($totalPages > 1): ?><div class="pagination"><?php for ($i=1; $i<=$totalPages; $i++): $query = $_GET; $query['page']=$i; ?><a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e(http_build_query($query)) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
