<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();
$pdo = db();
$action = trim((string)($_GET['action'] ?? ''));
$department = (int)($_GET['department'] ?? 0);
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$page=max(1,(int)($_GET['page']??1)); $perPage=20; $offset=($page-1)*$perPage;
$where=[]; $params=[];
if ($action !== '') { $where[]='a.action_type=:action'; $params[':action']=$action; }
if ($department > 0) { $where[]='e.department_id=:department'; $params[':department']=$department; }
if ($dateFrom !== '') { $where[]='DATE(a.created_at)>=:date_from'; $params[':date_from']=$dateFrom; }
if ($dateTo !== '') { $where[]='DATE(a.created_at)<=:date_to'; $params[':date_to']=$dateTo; }
if ($q !== '') { $where[]='(a.action_description LIKE :q OR e.employee_no LIKE :q OR e.first_name LIKE :q OR e.last_name LIKE :q OR u.full_name LIKE :q)'; $params[':q']='%'.$q.'%'; }
$whereSql=$where?'WHERE '.implode(' AND ',$where):'';
$base="FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN employees e ON e.id=a.employee_id LEFT JOIN departments d ON d.id=e.department_id $whereSql";
$count=$pdo->prepare("SELECT COUNT(*) $base"); $count->execute($params); $total=(int)$count->fetchColumn();
$stmt=$pdo->prepare("SELECT a.*,u.full_name AS user_name,e.employee_no,CONCAT_WS(' ',e.first_name,e.middle_name,e.last_name,e.suffix) AS employee_name,d.name AS department_name $base ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset"); $stmt->execute($params); $logs=$stmt->fetchAll();
$actions=$pdo->query('SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type')->fetchAll(PDO::FETCH_COLUMN);
$departments=$pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$summary=$pdo->query("SELECT COUNT(*) AS all_logs,SUM(DATE(created_at)=CURDATE()) AS today_logs,SUM(action_type='ID_GENERATED') AS id_logs,SUM(action_type IN ('EMPLOYEE_CREATED','EMPLOYEE_UPDATED','STATUS_CHANGED')) AS record_changes FROM audit_logs")->fetch();
$totalPages=max(1,(int)ceil($total/$perPage));
$pageTitle='Record Monitoring'; $pageSubtitle='Audit trail for employee records, status changes, sign-ins, and generated IDs.';
require __DIR__.'/includes/header.php';
?>
<div class="grid grid-4 mb-18">
 <div class="card stat"><div class="label">All monitored events</div><div class="value"><?= (int)($summary['all_logs']??0) ?></div></div>
 <div class="card stat"><div class="label">Events today</div><div class="value"><?= (int)($summary['today_logs']??0) ?></div></div>
 <div class="card stat"><div class="label">ID output events</div><div class="value"><?= (int)($summary['id_logs']??0) ?></div></div>
 <div class="card stat"><div class="label">Employee record changes</div><div class="value"><?= (int)($summary['record_changes']??0) ?></div></div>
</div>
<div class="card mb-18"><div class="card-body"><form class="filters" method="get">
 <div class="form-group"><label>Search activity</label><input name="q" value="<?= e($q) ?>" placeholder="Employee, user, or description"></div>
 <div class="form-group"><label>Action</label><select name="action"><option value="">All actions</option><?php foreach($actions as $a):?><option value="<?= e($a) ?>" <?= $action===$a?'selected':'' ?>><?= e(str_replace('_',' ',$a)) ?></option><?php endforeach;?></select></div>
 <div class="form-group"><label>Department</label><select name="department"><option value="0">All departments</option><?php foreach($departments as $d):?><option value="<?= (int)$d['id'] ?>" <?= $department===(int)$d['id']?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach;?></select></div>
 <div class="form-group"><label>Date from</label><input type="date" name="date_from" value="<?= e($dateFrom) ?>"></div>
 <div class="form-group"><label>Date to</label><input type="date" name="date_to" value="<?= e($dateTo) ?>"></div>
 <button class="btn btn-secondary" type="submit">Apply filters</button>
 </form></div></div>
<section class="card">
 <div class="card-header"><h2><?= $total ?> monitored event<?= $total===1?'':'s' ?></h2><a class="btn btn-secondary btn-sm" href="monitoring.php">Clear filters</a></div>
 <div class="table-wrap"><table><thead><tr><th>Date and time</th><th>Action</th><th>Activity</th><th>Employee</th><th>User</th><th>IP address</th></tr></thead><tbody>
 <?php foreach($logs as $log):?><tr>
  <td><?= e(date('M d, Y h:i:s A',strtotime($log['created_at']))) ?></td>
  <td><span class="badge badge-action"><?= e(str_replace('_',' ',$log['action_type'])) ?></span></td>
  <td class="audit-description"><?= e($log['action_description']) ?><?php if($log['department_name']):?><small><?= e($log['department_name']) ?></small><?php endif;?></td>
  <td><?= $log['employee_no']?e($log['employee_no'].' · '.$log['employee_name']):'—' ?></td>
  <td><?= e($log['user_name']??'System') ?></td><td><?= e($log['ip_address']) ?></td>
 </tr><?php endforeach;?>
 <?php if(!$logs):?><tr><td class="empty" colspan="6">No monitored events matched the selected filters.</td></tr><?php endif;?>
 </tbody></table></div>
 <?php if($totalPages>1):?><div class="pagination"><?php for($i=1;$i<=$totalPages;$i++):$query=$_GET;$query['page']=$i;?><a class="<?= $i===$page?'active':'' ?>" href="?<?= e(http_build_query($query)) ?>"><?= $i ?></a><?php endfor;?></div><?php endif;?>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
