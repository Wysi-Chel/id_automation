<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$employee = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    if (!$employee) {
        http_response_code(404);
        exit('Employee record not found.');
    }
}
$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$companies = ID_COMPANIES;
$pageTitle = $employee ? 'Edit Employee' : 'Add Employee';
$pageSubtitle = $employee ? 'Update employee information and uploaded images.' : 'Enter the information that will appear on the employee ID.';
require __DIR__ . '/includes/header.php';
?>
<form action="employee_save.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int) ($employee['id'] ?? 0) ?>">
    <section class="card mb-18">
        <div class="card-header"><h2>Basic employment details</h2></div>
        <div class="card-body form-grid">
            <div class="form-group"><label>Employee number <span class="required">*</span></label><input name="employee_no" required maxlength="50" value="<?= e($employee['employee_no'] ?? '') ?>"></div>
            <div class="form-group"><label>Department <span class="required">*</span></label><select name="department_id" required><option value="">Select department</option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (int)($employee['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group full"><label>Company / ID template <span class="required">*</span></label><select name="company_code" required><?php foreach ($companies as $code => $label): ?><option value="<?= e($code) ?>" <?= ($employee['company_code'] ?? 'MGSC') === $code ? 'selected' : '' ?>><?= e($code . ' — ' . $label) ?></option><?php endforeach; ?></select><div class="help">This selects the employee’s default ID design and enables company filtering.</div></div>
            <div class="form-group"><label>First name <span class="required">*</span></label><input name="first_name" required maxlength="100" value="<?= e($employee['first_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Middle name</label><input name="middle_name" maxlength="100" value="<?= e($employee['middle_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Last name <span class="required">*</span></label><input name="last_name" required maxlength="100" value="<?= e($employee['last_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Suffix</label><input name="suffix" maxlength="20" placeholder="Jr., III" value="<?= e($employee['suffix'] ?? '') ?>"></div>
            <div class="form-group full"><label>Position <span class="required">*</span></label><input name="position" required maxlength="150" value="<?= e($employee['position'] ?? '') ?>"></div>
            <div class="form-group"><label>Date of birth</label><input type="date" name="date_of_birth" value="<?= e($employee['date_of_birth'] ?? '') ?>"></div>
            <div class="form-group"><label>Date hired</label><input type="date" name="date_hired" value="<?= e($employee['date_hired'] ?? '') ?>"></div>
        </div>
    </section>
    <section class="card mb-18">
        <div class="card-header"><h2>Government and employment numbers</h2></div>
        <div class="card-body form-grid">
            <div class="form-group"><label>SSS number</label><input name="sss_number" maxlength="50" value="<?= e($employee['sss_number'] ?? '') ?>"></div>
            <div class="form-group"><label>PhilHealth number</label><input name="philhealth_number" maxlength="50" value="<?= e($employee['philhealth_number'] ?? '') ?>"></div>
            <div class="form-group"><label>TIN</label><input name="tin_number" maxlength="50" value="<?= e($employee['tin_number'] ?? '') ?>"></div>
            <div class="form-group"><label>Pag-IBIG / HDMF number</label><input name="hdmf_number" maxlength="50" value="<?= e($employee['hdmf_number'] ?? '') ?>"></div>
        </div>
    </section>
    <section class="card mb-18">
        <div class="card-header"><h2>Emergency contact</h2></div>
        <div class="card-body form-grid">
            <div class="form-group"><label>Contact name</label><input name="emergency_contact_name" maxlength="150" value="<?= e($employee['emergency_contact_name'] ?? '') ?>"></div>
            <div class="form-group"><label>Contact number</label><input name="emergency_contact_number" maxlength="50" value="<?= e($employee['emergency_contact_number'] ?? '') ?>"></div>
            <div class="form-group full"><label>Contact address</label><textarea name="emergency_contact_address"><?= e($employee['emergency_contact_address'] ?? '') ?></textarea></div>
        </div>
    </section>
    <section class="card mb-18">
        <div class="card-header"><h2>ID images</h2></div>
        <div class="card-body form-grid">
            <div class="form-group"><label>Employee photo</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><div class="help">Use a clear, front-facing portrait. JPG, PNG, or WEBP; maximum 5 MB.</div><?php if (!empty($employee['photo_path'])): ?><img class="avatar" style="width:90px;height:110px" src="<?= e($employee['photo_path']) ?>" alt="Current employee photo"><?php endif; ?></div>
            <div class="form-group"><label>Employee signature</label><input type="file" name="signature" accept="image/jpeg,image/png,image/webp"><div class="help">Transparent PNG is recommended. JPG, PNG, or WEBP; maximum 5 MB.</div><?php if (!empty($employee['signature_path'])): ?><img style="max-width:180px;max-height:80px" src="<?= e($employee['signature_path']) ?>" alt="Current employee signature"><?php endif; ?></div>
        </div>
    </section>
    <div class="actions"><button class="btn btn-primary" type="submit">Save employee</button><a class="btn btn-secondary" href="<?= $employee ? 'employee_view.php?id='.(int)$employee['id'] : 'employees.php' ?>">Cancel</a></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
