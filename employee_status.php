<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('employees.php');
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
if (!in_array($status, ['Active','Inactive'], true)) redirect('employee_view.php?id=' . $id);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?'); $stmt->execute([$id]); $employee=$stmt->fetch();
if (!$employee) { http_response_code(404); exit('Employee record not found.'); }
$pdo->beginTransaction();
$pdo->prepare('UPDATE employees SET status=?, updated_by=? WHERE id=?')->execute([$status,current_user()['id'],$id]);
audit_log($pdo,'STATUS_CHANGED','Changed ' . full_name($employee) . ' from ' . $employee['status'] . ' to ' . $status . '.',$id,['status'=>$employee['status']],['status'=>$status]);
$pdo->commit();
flash('success','Employee status updated.');
redirect('employee_view.php?id='.$id);
