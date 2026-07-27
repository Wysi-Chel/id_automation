<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('employees.php');
}
verify_csrf();
$pdo = db();
$id = (int) ($_POST['id'] ?? 0);
$old = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) {
        http_response_code(404);
        exit('Employee record not found.');
    }
}

$data = [
    'employee_no' => trim((string) ($_POST['employee_no'] ?? '')),
    'first_name' => trim((string) ($_POST['first_name'] ?? '')),
    'middle_name' => trim((string) ($_POST['middle_name'] ?? '')) ?: null,
    'last_name' => trim((string) ($_POST['last_name'] ?? '')),
    'suffix' => trim((string) ($_POST['suffix'] ?? '')) ?: null,
    'position' => trim((string) ($_POST['position'] ?? '')),
    'department_id' => (int) ($_POST['department_id'] ?? 0),
    'company_code' => strtoupper(trim((string) ($_POST['company_code'] ?? 'MGSC'))),
    'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')) ?: null,
    'date_hired' => trim((string) ($_POST['date_hired'] ?? '')) ?: null,
    'sss_number' => trim((string) ($_POST['sss_number'] ?? '')) ?: null,
    'philhealth_number' => trim((string) ($_POST['philhealth_number'] ?? '')) ?: null,
    'tin_number' => trim((string) ($_POST['tin_number'] ?? '')) ?: null,
    'hdmf_number' => trim((string) ($_POST['hdmf_number'] ?? '')) ?: null,
    'emergency_contact_name' => trim((string) ($_POST['emergency_contact_name'] ?? '')) ?: null,
    'emergency_contact_address' => trim((string) ($_POST['emergency_contact_address'] ?? '')) ?: null,
    'emergency_contact_number' => trim((string) ($_POST['emergency_contact_number'] ?? '')) ?: null,
];

if ($data['employee_no'] === '' || $data['first_name'] === '' || $data['last_name'] === '' || $data['position'] === '' || $data['department_id'] <= 0 || !isset(ID_COMPANIES[$data['company_code']])) {
    flash('danger', 'Employee number, name, position, department, and company are required.');
    redirect($id > 0 ? 'employee_form.php?id=' . $id : 'employee_form.php');
}

try {
    $data['photo_path'] = upload_image('photo', 'photos', $old['photo_path'] ?? null);
    $data['signature_path'] = upload_image('signature', 'signatures', $old['signature_path'] ?? null);

    $pdo->beginTransaction();
    if ($old) {
        $sql = 'UPDATE employees SET
            employee_no=:employee_no, first_name=:first_name, middle_name=:middle_name,
            last_name=:last_name, suffix=:suffix, position=:position, department_id=:department_id,
            company_code=:company_code,
            date_of_birth=:date_of_birth, date_hired=:date_hired, sss_number=:sss_number,
            philhealth_number=:philhealth_number, tin_number=:tin_number, hdmf_number=:hdmf_number,
            emergency_contact_name=:emergency_contact_name, emergency_contact_address=:emergency_contact_address,
            emergency_contact_number=:emergency_contact_number, photo_path=:photo_path,
            signature_path=:signature_path, updated_by=:updated_by WHERE id=:id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data + ['updated_by' => current_user()['id'], 'id' => $id]);
        $fresh = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
        $fresh->execute([$id]);
        $new = $fresh->fetch();
        audit_log($pdo, 'EMPLOYEE_UPDATED', 'Updated employee record for ' . full_name($new) . '.', $id, employee_snapshot($old), employee_snapshot($new));
        $message = 'Employee record updated.';
    } else {
        $sql = 'INSERT INTO employees
            (employee_no, first_name, middle_name, last_name, suffix, position, department_id, company_code,
             date_of_birth, date_hired, sss_number, philhealth_number, tin_number, hdmf_number,
             emergency_contact_name, emergency_contact_address, emergency_contact_number,
             photo_path, signature_path, created_by, updated_by)
            VALUES
            (:employee_no, :first_name, :middle_name, :last_name, :suffix, :position, :department_id, :company_code,
             :date_of_birth, :date_hired, :sss_number, :philhealth_number, :tin_number, :hdmf_number,
             :emergency_contact_name, :emergency_contact_address, :emergency_contact_number,
             :photo_path, :signature_path, :created_by, :updated_by)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data + ['created_by' => current_user()['id'], 'updated_by' => current_user()['id']]);
        $id = (int) $pdo->lastInsertId();
        audit_log($pdo, 'EMPLOYEE_CREATED', 'Created employee record for ' . trim($data['first_name'] . ' ' . $data['last_name']) . '.', $id, null, $data);
        $message = 'Employee record created.';
    }
    $pdo->commit();
    flash('success', $message);
    redirect('employee_view.php?id=' . $id);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((string) $e->getCode() === '23000') {
        flash('danger', 'The employee number already exists.');
    } else {
        flash('danger', 'The employee record could not be saved.');
    }
    redirect($id > 0 ? 'employee_form.php?id=' . $id : 'employee_form.php');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('danger', $e->getMessage());
    redirect($id > 0 ? 'employee_form.php?id=' . $id : 'employee_form.php');
}
