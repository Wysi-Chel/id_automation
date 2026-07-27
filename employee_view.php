<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT e.*, d.name AS department_name FROM employees e JOIN departments d ON d.id=e.department_id WHERE e.id=?');
$stmt->execute([$id]);
$employee = $stmt->fetch();
if (!$employee) { http_response_code(404); exit('Employee record not found.'); }
$pageTitle = full_name($employee);
$pageSubtitle = $employee['employee_no'] . ' · ' . $employee['department_name'];
require __DIR__ . '/includes/header.php';
?>
<div class="actions mb-18">
    <a class="btn btn-primary" href="id_maker.php?id=<?= $id ?>">Generate employee ID</a>
    <a class="btn btn-secondary" href="employee_form.php?id=<?= $id ?>">Edit record</a>
    <form action="employee_status.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="status" value="<?= $employee['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
        <button class="btn <?= $employee['status'] === 'Active' ? 'btn-danger' : 'btn-secondary' ?>" type="submit"><?= $employee['status'] === 'Active' ? 'Mark inactive' : 'Restore as active' ?></button>
    </form>
</div>
<section class="card mb-18">
    <div class="card-header"><h2>Employment profile</h2><span class="badge badge-<?= strtolower($employee['status']) ?>"><?= e($employee['status']) ?></span></div>
    <div class="card-body">
        <div class="employee-cell mb-18">
            <?php if ($employee['photo_path']): ?><img class="avatar" style="width:100px;height:120px" src="<?= e($employee['photo_path']) ?>" alt=""><?php else: ?><span class="avatar" style="width:100px;height:120px"></span><?php endif; ?>
            <div><strong style="font-size:21px"><?= e(full_name($employee)) ?></strong><small style="font-size:14px"><?= e($employee['position']) ?></small><small><?= e($employee['department_name']) ?></small></div>
        </div>
        <dl class="detail-grid">
            <div class="detail"><dt>Employee number</dt><dd><?= e($employee['employee_no']) ?></dd></div>
            <div class="detail"><dt>Date hired</dt><dd><?= e(display_date($employee['date_hired'])) ?></dd></div>
            <div class="detail"><dt>Date of birth</dt><dd><?= e(display_date($employee['date_of_birth'])) ?></dd></div>
            <div class="detail"><dt>SSS</dt><dd><?= e(mask_number($employee['sss_number'])) ?></dd></div>
            <div class="detail"><dt>PhilHealth</dt><dd><?= e(mask_number($employee['philhealth_number'])) ?></dd></div>
            <div class="detail"><dt>TIN</dt><dd><?= e(mask_number($employee['tin_number'])) ?></dd></div>
            <div class="detail"><dt>Pag-IBIG / HDMF</dt><dd><?= e(mask_number($employee['hdmf_number'])) ?></dd></div>
        </dl>
    </div>
</section>
<section class="card">
    <div class="card-header"><h2>Emergency contact</h2></div>
    <div class="card-body"><dl class="detail-grid">
        <div class="detail"><dt>Name</dt><dd><?= e($employee['emergency_contact_name'] ?: '—') ?></dd></div>
        <div class="detail"><dt>Number</dt><dd><?= e($employee['emergency_contact_number'] ?: '—') ?></dd></div>
        <div class="detail"><dt>Address</dt><dd><?= nl2br(e($employee['emergency_contact_address'] ?: '—')) ?></dd></div>
    </dl></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
