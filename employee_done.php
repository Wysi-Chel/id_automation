<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$employeeId = (int) ($_POST['employee_id'] ?? 0);
$requestedStatus = trim((string) ($_POST['status'] ?? ''));
$returnQuery = trim((string) ($_POST['return_query'] ?? ''));

if ($employeeId <= 0 || !in_array($requestedStatus, ['done', 'pending', 'released'], true)) {
    flash('danger', 'Invalid employee completion request.');
    redirect('employees.php');
}

$pdo = db();
$statement = $pdo->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
$statement->execute([$employeeId]);
$employee = $statement->fetch();

if (!$employee) {
    flash('danger', 'The employee record could not be found.');
    redirect('employees.php');
}

if ((int) ($employee['id_is_released'] ?? 0) === 1 && $requestedStatus !== 'released') {
    flash('warning', 'Released employee IDs cannot be reopened.');
    redirect('employees.php');
}

$userId = (int) (current_user()['id'] ?? 0);
$pdo->beginTransaction();

try {
    if ($requestedStatus === 'released') {
        if ((int) ($employee['id_is_done'] ?? 0) !== 1) {
            $pdo->rollBack();
            flash('danger', 'Mark the employee ID as done before releasing it.');
            redirect('employees.php');
        }

        $pdo->prepare(
            'UPDATE employees
             SET id_is_released = 1, id_released_by = ?, id_released_at = NOW(), updated_by = ?
             WHERE id = ?'
        )->execute([$userId ?: null, $userId ?: null, $employeeId]);

        audit_log(
            $pdo,
            'ID_WORKFLOW_RELEASED',
            'Marked the employee ID for ' . full_name($employee) . ' as released.',
            $employeeId,
            ['id_is_released' => (int) ($employee['id_is_released'] ?? 0)],
            ['id_is_released' => 1]
        );
        flash('success', 'Employee ID marked as released.');
    } elseif ($requestedStatus === 'done') {
        $pdo->prepare(
            'UPDATE employees
             SET id_is_done = 1, id_done_by = ?, id_done_at = NOW(),
                 id_is_released = 0, id_released_by = NULL, id_released_at = NULL,
                 updated_by = ?
             WHERE id = ?'
        )->execute([$userId ?: null, $userId ?: null, $employeeId]);

        audit_log(
            $pdo,
            'ID_WORKFLOW_DONE',
            'Marked the employee ID for ' . full_name($employee) . ' as done.',
            $employeeId,
            ['id_is_done' => (int) ($employee['id_is_done'] ?? 0)],
            ['id_is_done' => 1, 'id_is_released' => 0]
        );
        flash('success', 'Employee ID marked as done.');
    } else {
        $pdo->prepare(
            'UPDATE employees
             SET id_is_done = 0, id_done_by = NULL, id_done_at = NULL,
                 id_is_released = 0, id_released_by = NULL, id_released_at = NULL,
                 updated_by = ?
             WHERE id = ?'
        )->execute([$userId ?: null, $employeeId]);

        audit_log(
            $pdo,
            'ID_WORKFLOW_REOPENED',
            'Reopened the employee ID for ' . full_name($employee) . '.',
            $employeeId,
            ['id_is_done' => (int) ($employee['id_is_done'] ?? 0)],
            ['id_is_done' => 0, 'id_is_released' => 0]
        );
        flash('success', 'Employee ID reopened.');
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

$target = 'employees.php';
if ($returnQuery !== '') {
    parse_str($returnQuery, $query);
    $allowedParameters = ['q', 'company', 'department', 'status', 'id_status', 'page'];
    $safeQuery = array_intersect_key($query, array_flip($allowedParameters));
    if ($safeQuery) {
        $target .= '?' . http_build_query($safeQuery);
    }
}

redirect($target);
