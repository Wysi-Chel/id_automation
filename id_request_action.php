<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/id_requests.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('id_requests.php');
}
verify_csrf();
$pdo = db();
ensure_id_request_schema($pdo);
$id = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$request = fetch_id_request($pdo, $id);
if (!$request) {
    flash('danger', 'ID request not found.');
    redirect('id_requests.php');
}

if ($action === 'status') {
    $status = trim((string) ($_POST['status'] ?? ''));
    $notes = trim((string) ($_POST['review_notes'] ?? ''));
    if (!in_array($status, ['Pending', 'Under Review', 'Approved', 'Declined', 'Cancelled'], true) || $request['status'] === 'Converted') {
        flash('danger', 'The selected review status is not valid.');
        redirect('id_request_view.php?id=' . $id);
    }
    $stmt = $pdo->prepare('UPDATE id_requests SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
    $stmt->execute([$status, $notes !== '' ? $notes : null, current_user()['id'], $id]);
    audit_log($pdo, 'ID_REQUEST_REVIEWED', 'Reviewed public ID request ' . $request['reference_no'] . ' and marked it ' . $status . '.', null, ['status' => $request['status']], ['status' => $status]);
    flash('success', 'ID request review saved.');
    redirect('id_request_view.php?id=' . $id);
}

if ($action === 'convert') {
    if ($request['status'] === 'Converted' || $request['employee_id']) {
        flash('warning', 'This request has already been converted.');
        redirect('id_request_view.php?id=' . $id);
    }
    $duplicate = $pdo->prepare('SELECT id FROM employees WHERE employee_no = ?');
    $duplicate->execute([$request['employee_no']]);
    if ($duplicate->fetchColumn()) {
        flash('danger', 'An employee record already uses employee number ' . $request['employee_no'] . '.');
        redirect('id_request_view.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO employees
             (employee_no, first_name, middle_name, last_name, suffix, position, department_id, company_code,
              date_of_birth, date_hired, sss_number, philhealth_number, tin_number, hdmf_number,
              emergency_contact_name, emergency_contact_address, emergency_contact_number,
              photo_path, signature_path, status, created_by, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Active", ?, ?)'
        );
        $stmt->execute([
            $request['employee_no'], $request['first_name'], $request['middle_name'], $request['last_name'],
            $request['suffix'], $request['position'], $request['department_id'], $request['company_code'],
            $request['date_of_birth'], $request['date_hired'], $request['sss_number'], $request['philhealth_number'],
            $request['tin_number'], $request['hdmf_number'], $request['emergency_contact_name'],
            $request['emergency_contact_address'], $request['emergency_contact_number'],
            $request['photo_path'], $request['signature_path'], current_user()['id'], current_user()['id'],
        ]);
        $employeeId = (int) $pdo->lastInsertId();
        $pdo->prepare(
            "UPDATE id_requests SET status = 'Converted', employee_id = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?"
        )->execute([$employeeId, current_user()['id'], $id]);
        audit_log(
            $pdo,
            'EMPLOYEE_CREATED_FROM_PUBLIC_REQUEST',
            'Created employee record from public ID request ' . $request['reference_no'] . '.',
            $employeeId,
            null,
            ['public_request' => $request['reference_no'], 'employee_no' => $request['employee_no']]
        );
        $pdo->commit();
        flash('success', 'Employee record created from ' . $request['reference_no'] . '.');
        redirect('employee_view.php?id=' . $employeeId);
    } catch (PDOException $exception) {
        $pdo->rollBack();
        flash('danger', 'The employee record could not be created.');
        redirect('id_request_view.php?id=' . $id);
    }
}

flash('danger', 'Unknown request action.');
redirect('id_request_view.php?id=' . $id);
