<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();

$stats = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active_count,
        SUM(status = 'Inactive') AS inactive_count,
        COUNT(DISTINCT department_id) AS departments_used
     FROM employees"
)->fetch();

$generatedToday = (int) $pdo->query(
    "SELECT COUNT(*) FROM audit_logs WHERE action_type = 'ID_GENERATED' AND DATE(created_at) = CURDATE()"
)->fetchColumn();
$changesToday = (int) $pdo->query(
    "SELECT COUNT(*) FROM audit_logs WHERE action_type IN ('EMPLOYEE_CREATED','EMPLOYEE_UPDATED','STATUS_CHANGED') AND DATE(created_at) = CURDATE()"
)->fetchColumn();

$departmentStats = $pdo->query(
    "SELECT d.name, COUNT(e.id) AS total,
            SUM(e.status = 'Active') AS active_count
     FROM departments d
     LEFT JOIN employees e ON e.department_id = d.id
     GROUP BY d.id, d.name
     ORDER BY d.name"
)->fetchAll();

$recentEmployees = $pdo->query(
    "SELECT e.*, d.name AS department_name
     FROM employees e JOIN departments d ON d.id = e.department_id
     ORDER BY e.created_at DESC LIMIT 6"
)->fetchAll();

$recentLogs = $pdo->query(
    "SELECT a.*, u.full_name AS user_name, e.employee_no,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name, e.suffix) AS employee_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     LEFT JOIN users du ON du.id = a.done_by
     LEFT JOIN employees e ON e.id = a.employee_id
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'ID Monitoring';
$pageSubtitle = 'Overview of employee records and recent system activity.';
require __DIR__ . '/includes/header.php';
?>
<div class="grid grid-2 mb-18">
    <div class="card stat">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M16 10h2M16 14h2"/><circle cx="9" cy="10.5" r="2"/><path d="M5.5 16a3.7 3.7 0 0 1 7 0"/></svg></span>
        <div><div class="label">Total employee records</div><div class="value"><?= (int) ($stats['total'] ?? 0) ?></div><div class="hint"><?= (int) ($stats['active_count'] ?? 0) ?> active</div></div>
    </div>
    <div class="card stat stat-amber">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
        <div><div class="label">Inactive records</div><div class="value"><?= (int) ($stats['inactive_count'] ?? 0) ?></div><div class="hint">Retained for record history</div></div>
    </div>
</div>



<section class="card">
    <div class="card-header"><h2>Recently added employees</h2><a class="btn btn-primary btn-sm" href="employee_form.php">Add employee</a></div>
    <div class="table-wrap">
        <table><thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($recentEmployees as $employee): ?>
            <tr>
                <td><div class="employee-cell">
                    <?php if ($employee['photo_path']): ?><img class="avatar" src="<?= e($employee['photo_path']) ?>" alt=""><?php else: ?><span class="avatar"></span><?php endif; ?>
                    <div><strong><?= e(full_name($employee)) ?></strong><small><?= e($employee['employee_no']) ?></small></div>
                </div></td>
                <td><?= e($employee['department_name']) ?></td>
                <td><?= e($employee['position']) ?></td>
                <td><span class="badge badge-<?= strtolower($employee['status']) ?>"><?= e($employee['status']) ?></span></td>
                <td><a class="btn btn-secondary btn-sm" href="employee_view.php?id=<?= (int) $employee['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recentEmployees): ?><tr><td colspan="5" class="empty">No employee records yet.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
<br>
<div class="grid grid-1 mb-18">
    <section class="card">
        <div class="card-header"><h2>Employees by department</h2><a class="btn btn-secondary btn-sm" href="employees.php">View records</a></div>
        <div class="table-wrap">
            <table><thead><tr><th>Department</th><th>Total</th><th>Active</th></tr></thead><tbody>
            <?php foreach ($departmentStats as $row): ?>
                <tr><td><?= e($row['name']) ?></td><td><?= (int) $row['total'] ?></td><td><?= (int) $row['active_count'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
