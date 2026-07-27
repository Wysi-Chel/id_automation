<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }
$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload) || !hash_equals(csrf_token(), (string)($payload['csrf_token'] ?? ''))) { http_response_code(419); echo json_encode(['ok'=>false]); exit; }
$employeeId = (int) ($payload['employee_id'] ?? 0);
$side = in_array(($payload['side'] ?? ''), ['front','back'], true) ? $payload['side'] : 'unknown';
$mode = in_array(($payload['mode'] ?? ''), ['download','print'], true) ? $payload['mode'] : 'generated';
$stmt=db()->prepare('SELECT employee_no, first_name, last_name FROM employees WHERE id=?'); $stmt->execute([$employeeId]); $employee=$stmt->fetch();
if (!$employee) { http_response_code(404); echo json_encode(['ok'=>false]); exit; }
audit_log(db(),'ID_GENERATED',ucfirst($mode) . 'ed the ' . $side . ' ID for ' . $employee['employee_no'] . ' - ' . $employee['first_name'] . ' ' . $employee['last_name'] . '.',$employeeId,null,['side'=>$side,'mode'=>$mode]);
echo json_encode(['ok'=>true]);
