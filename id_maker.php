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
$photoData = file_data_uri($employee['photo_path']);
$signatureData = file_data_uri($employee['signature_path']);

$localAssetDataUri = static function (string $relativePath, string $mime): string {
    $absolutePath = __DIR__ . '/' . ltrim($relativePath, '/');
    if (!is_file($absolutePath)) {
        throw new RuntimeException('Required ID template asset is missing: ' . $relativePath);
    }
    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolutePath));
};

$data = [
    'id' => (int)$employee['id'],
    'employeeNo' => $employee['employee_no'],
    'name' => strtoupper(full_name($employee)),
    'position' => $employee['position'],
    'department' => $employee['department_name'],
    'dob' => display_date($employee['date_of_birth']),
    'dateHired' => display_date($employee['date_hired']),
    'sss' => $employee['sss_number'] ?: '—',
    'philhealth' => $employee['philhealth_number'] ?: '—',
    'tin' => $employee['tin_number'] ?: '—',
    'hdmf' => $employee['hdmf_number'] ?: '—',
    'emergencyName' => strtoupper((string)($employee['emergency_contact_name'] ?: 'NOT PROVIDED')),
    'emergencyAddress' => strtoupper((string)($employee['emergency_contact_address'] ?: 'NOT PROVIDED')),
    'emergencyNumber' => $employee['emergency_contact_number'] ?: '—',
    'companyName' => COMPANY_NAME,
    'companyAddress' => COMPANY_ADDRESS,
    'presidentName' => PRESIDENT_NAME,
    'photo' => $photoData,
    'signature' => $signatureData,
];
$templateAssets = [
    'frontTemplate' => $localAssetDataUri('assets/id-template/front-template.png', 'image/png'),
    'backTemplate' => $localAssetDataUri('assets/id-template/back-template.png', 'image/png'),
    'fontMedium' => $localAssetDataUri('assets/fonts/MMCOFFICE-Medium.ttf', 'font/ttf'),
    'fontBold' => $localAssetDataUri('assets/fonts/MMCOFFICE-Bold.ttf', 'font/ttf'),
];
$pageTitle = 'Generate Employee ID';
$pageSubtitle = full_name($employee) . ' · Separate front and back outputs';
require __DIR__ . '/includes/header.php';
?>
<div class="id-layout">
    <div class="id-previews">
        <section class="card id-panel"><h3>Front</h3><div class="id-card-frame" id="frontContainer"></div><div class="actions" style="justify-content:center;margin-top:14px"><button class="btn btn-primary" type="button" data-download="front">Download front PNG</button><button class="btn btn-secondary" type="button" data-print="front">Print front</button></div></section>
        <section class="card id-panel"><h3>Back</h3><div class="id-card-frame" id="backContainer"></div><div class="actions" style="justify-content:center;margin-top:14px"><button class="btn btn-primary" type="button" data-download="back">Download back PNG</button><button class="btn btn-secondary" type="button" data-print="back">Print back</button></div></section>
    </div>
    <aside class="card">
        <div class="card-header"><h3>ID output details</h3></div>
        <div class="card-body">
            <dl class="detail">
                <dt>Employee</dt><dd><?= e(full_name($employee)) ?></dd>
                <dt style="margin-top:14px">Employee number</dt><dd><?= e($employee['employee_no']) ?></dd>
                <dt style="margin-top:14px">Department</dt><dd><?= e($employee['department_name']) ?></dd>
                <dt style="margin-top:14px">Output size</dt><dd>600 × 960 pixels per side</dd>
            </dl>
            <div class="photo-placement-editor" id="photoPlacementEditor">
                <div class="photo-placement-heading">
                    <div>
                        <h4>Photo placement</h4>
                        <p>Drag the picture on the front ID or use the controls below.</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" type="button" data-photo-reset>Reset</button>
                </div>
                <label class="photo-range">
                    <span>Horizontal position <output data-photo-output="x"></output></span>
                    <input type="range" min="0" max="295" step="1" value="40" data-photo-control="x">
                </label>
                <label class="photo-range">
                    <span>Vertical position <output data-photo-output="y"></output></span>
                    <input type="range" min="0" max="655" step="1" value="280" data-photo-control="y">
                </label>
                <label class="photo-range">
                    <span>Picture size <output data-photo-output="size"></output></span>
                    <input type="range" min="180" max="420" step="1" value="305" data-photo-control="size">
                </label>
                <label class="photo-range">
                    <span>Crop zoom <output data-photo-output="zoom"></output></span>
                    <input type="range" min="1" max="2.5" step="0.01" value="1" data-photo-control="zoom">
                </label>
                <label class="photo-range">
                    <span>Crop left/right <output data-photo-output="panX"></output></span>
                    <input type="range" min="-100" max="100" step="1" value="0" data-photo-control="panX">
                </label>
                <label class="photo-range">
                    <span>Crop up/down <output data-photo-output="panY"></output></span>
                    <input type="range" min="-100" max="100" step="1" value="0" data-photo-control="panY">
                </label>
                <p class="help">Placement is saved automatically for this employee on this browser.</p>
            </div>
            <p class="help" style="margin-top:18px">The front and back are downloaded separately. No three-ID print sheet is included.</p>
            <?php if (!$photoData): ?><div class="alert alert-warning">No employee photo has been uploaded. A placeholder will appear.</div><?php endif; ?>
            <?php if (!$signatureData): ?><div class="alert alert-warning">No employee signature has been uploaded. A signature line will appear.</div><?php endif; ?>
            <a class="btn btn-secondary" href="employee_form.php?id=<?= $id ?>">Edit employee information</a>
        </div>
    </aside>
</div>
<script>
window.ID_EMPLOYEE = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.ID_TEMPLATE_ASSETS = <?= json_encode($templateAssets, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/id-generator.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
