<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_auth();

$modules = [
    'equipment' => [
        'title' => 'Equipment Repair Monitoring',
        'code' => 'ER',
        'description' => 'The equipment repair workspace is ready for its records and workflow to be added.',
    ],
    'training' => [
        'title' => 'Training Monitoring',
        'code' => 'TM',
        'description' => 'The training workspace is ready for its records and workflow to be added.',
    ],
];

$moduleKey = (string) ($_GET['module'] ?? '');
if (!isset($modules[$moduleKey])) {
    http_response_code(404);
    exit('Monitoring system not found.');
}

$module = $modules[$moduleKey];
$pageTitle = $module['title'];
$pageSubtitle = 'Monitoring Systems';
require __DIR__ . '/includes/header.php';
?>
<section class="card module-placeholder">
    <span class="module-placeholder-icon" aria-hidden="true"><?= e($module['code']) ?></span>
    <h2><?= e($module['title']) ?></h2>
    <p><?= e($module['description']) ?></p>
    <a class="btn btn-primary" href="systems.php">Return to system menu</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
