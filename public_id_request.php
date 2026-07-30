<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/id_requests.php';

$pdo = db();
ensure_id_request_schema($pdo);
$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        http_response_code(422);
        exit('Submission rejected.');
    }
    if (!public_submission_allowed('id_request')) {
        $errors[] = 'Please wait before submitting another ID request.';
    }

    $data = [
        'employee_no' => strtoupper(trim((string) ($_POST['employee_no'] ?? ''))),
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'middle_name' => trim((string) ($_POST['middle_name'] ?? '')) ?: null,
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'suffix' => trim((string) ($_POST['suffix'] ?? '')) ?: null,
        'position' => trim((string) ($_POST['position'] ?? '')),
        'department_id' => (int) ($_POST['department_id'] ?? 0),
        'company_code' => strtoupper(trim((string) ($_POST['company_code'] ?? ''))),
        'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')) ?: null,
        'date_hired' => trim((string) ($_POST['date_hired'] ?? '')) ?: null,
        'sss_number' => trim((string) ($_POST['sss_number'] ?? '')) ?: null,
        'philhealth_number' => trim((string) ($_POST['philhealth_number'] ?? '')) ?: null,
        'tin_number' => trim((string) ($_POST['tin_number'] ?? '')) ?: null,
        'hdmf_number' => trim((string) ($_POST['hdmf_number'] ?? '')) ?: null,
        'emergency_contact_name' => trim((string) ($_POST['emergency_contact_name'] ?? '')) ?: null,
        'emergency_contact_address' => trim((string) ($_POST['emergency_contact_address'] ?? '')) ?: null,
        'emergency_contact_number' => trim((string) ($_POST['emergency_contact_number'] ?? '')) ?: null,
        'requester_email' => trim((string) ($_POST['requester_email'] ?? '')) ?: null,
        'requester_phone' => trim((string) ($_POST['requester_phone'] ?? '')) ?: null,
    ];

    if ($data['employee_no'] === '' || $data['first_name'] === '' || $data['last_name'] === '' || $data['position'] === '' || $data['department_id'] < 1 || !isset(ID_COMPANIES[$data['company_code']])) {
        $errors[] = 'Complete all required employee, department, and company fields.';
    }
    if ($data['requester_email'] && !filter_var($data['requester_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address or leave the email field blank.';
    }
    if (empty($_POST['privacy_consent'])) {
        $errors[] = 'Confirm the privacy and accuracy declaration before submitting.';
    }
    $departmentCheck = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = ?');
    $departmentCheck->execute([$data['department_id']]);
    if ((int) $departmentCheck->fetchColumn() === 0) {
        $errors[] = 'Select a valid department.';
    }
    $duplicate = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM employees WHERE employee_no = ?) +
            (SELECT COUNT(*) FROM id_requests WHERE employee_no = ? AND status NOT IN ('Declined','Cancelled'))"
    );
    $duplicate->execute([$data['employee_no'], $data['employee_no']]);
    if ((int) $duplicate->fetchColumn() > 0) {
        $errors[] = 'An employee record or active ID request already uses this employee number.';
    }
    if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($_FILES['signature']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Both the employee photo and signature image are required.';
    }

    if (!$errors) {
        $photoPath = null;
        $signaturePath = null;
        try {
            $photoPath = upload_image('photo', 'public-id/photos');
            $signaturePath = upload_image('signature', 'public-id/signatures');
            $reference = next_id_request_reference($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO id_requests
                 (reference_no, employee_no, first_name, middle_name, last_name, suffix, position,
                  department_id, company_code, date_of_birth, date_hired, sss_number, philhealth_number,
                  tin_number, hdmf_number, emergency_contact_name, emergency_contact_address,
                  emergency_contact_number, requester_email, requester_phone, photo_path, signature_path,
                  status, submitted_ip)
                 VALUES
                 (:reference_no, :employee_no, :first_name, :middle_name, :last_name, :suffix, :position,
                  :department_id, :company_code, :date_of_birth, :date_hired, :sss_number, :philhealth_number,
                  :tin_number, :hdmf_number, :emergency_contact_name, :emergency_contact_address,
                  :emergency_contact_number, :requester_email, :requester_phone, :photo_path, :signature_path,
                  "Pending", :submitted_ip)'
            );
            $stmt->execute($data + [
                'reference_no' => $reference,
                'photo_path' => $photoPath,
                'signature_path' => $signaturePath,
                'submitted_ip' => client_ip(),
            ]);
            mark_public_submission('id_request');
            redirect('public_id_request.php?submitted=' . urlencode($reference));
        } catch (RuntimeException | PDOException $exception) {
            foreach ([$photoPath, $signaturePath] as $path) {
                if ($path && str_starts_with($path, 'uploads/public-id/')) {
                    $absolute = __DIR__ . '/' . $path;
                    if (is_file($absolute)) {
                        unlink($absolute);
                    }
                }
            }
            $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'The ID request could not be submitted. Please try again.';
        }
    }
}

$submitted = preg_match('/^IDR-\d{4}-\d{4}$/', (string) ($_GET['submitted'] ?? '')) ? (string) $_GET['submitted'] : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee ID Request · MICEI</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <meta name="theme-color" content="#bf1f2f">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/public-requests.css">
    <script src="assets/js/theme.js" defer></script>
    <script src="assets/js/public-requests.js" defer></script>
</head>
<body class="public-request-body">
<main class="public-request-shell">
    <nav class="public-nav">
        <a class="public-brand" href="public_requests.php"><span><img src="assets/img/favicon.png" alt=""></span><span><small>MICEI</small><strong>Public Request Portal</strong></span></a>
        <div>
            <button class="micei-theme-toggle compact" type="button" data-theme-toggle aria-pressed="false">
                <svg class="theme-icon theme-icon-sun" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="theme-icon theme-icon-moon" aria-hidden="true" viewBox="0 0 24 24"><path d="M20.5 15.4A9 9 0 0 1 8.6 3.5 9 9 0 1 0 20.5 15.4Z"/></svg>
                <span data-theme-label>Dark mode</span>
            </button>
            <a class="btn btn-secondary btn-sm" href="public_requests.php">All forms</a>
        </div>
    </nav>

    <?php if ($submitted): ?>
        <section class="public-success">
            <span class="success-check">✓</span>
            <span class="public-kicker">Request received</span>
            <h1>Your ID request is now pending review.</h1>
            <p>Save this tracking reference. The IT Department may use your submitted contact details if clarification is needed.</p>
            <strong class="tracking-reference"><?= e($submitted) ?></strong>
            <div class="public-success-actions"><a class="btn btn-primary" href="public_requests.php">Return to request portal</a><a class="btn btn-secondary" href="public_id_request.php">Submit another request</a></div>
        </section>
    <?php else: ?>
        <header class="public-form-header">
            <span class="public-kicker">Employee ID Request</span>
            <h1>Request a company ID</h1>
        </header>
        <?php if ($errors): ?><div class="public-errors"><strong>Please review the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form class="public-request-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="public-honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 1</span><h2>Employment details</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group"><label>Employee number <span class="required">*</span></label><input name="employee_no" required maxlength="50" value="<?= e($_POST['employee_no'] ?? '') ?>"></div>
                    <div class="form-group"><label>Department <span class="required">*</span></label><select name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) ($_POST['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group full"><label>Company / dealer <span class="required">*</span></label><select name="company_code" required><option value="">Select company</option><?php foreach (ID_COMPANIES as $code => $label): ?><option value="<?= e($code) ?>" <?= ($_POST['company_code'] ?? '') === $code ? 'selected' : '' ?>><?= e($code . ' — ' . $label) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>First name <span class="required">*</span></label><input name="first_name" required maxlength="100" value="<?= e($_POST['first_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Middle name</label><input name="middle_name" maxlength="100" value="<?= e($_POST['middle_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Last name <span class="required">*</span></label><input name="last_name" required maxlength="100" value="<?= e($_POST['last_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Suffix</label><input name="suffix" maxlength="20" placeholder="" value="<?= e($_POST['suffix'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Position <span class="required">*</span></label><input name="position" required maxlength="150" value="<?= e($_POST['position'] ?? '') ?>"></div>
                    <div class="form-group"><label>Date of birth</label><input type="date" name="date_of_birth" value="<?= e($_POST['date_of_birth'] ?? '') ?>"></div>
                    <div class="form-group"><label>Date hired</label><input type="date" name="date_hired" value="<?= e($_POST['date_hired'] ?? '') ?>"></div>
                </div>
            </section>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 2</span><h2>ID and emergency information</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group"><label>SSS number</label><input name="sss_number" maxlength="50" value="<?= e($_POST['sss_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>PhilHealth number</label><input name="philhealth_number" maxlength="50" value="<?= e($_POST['philhealth_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>TIN</label><input name="tin_number" maxlength="50" value="<?= e($_POST['tin_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Pag-IBIG / HDMF number</label><input name="hdmf_number" maxlength="50" value="<?= e($_POST['hdmf_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Emergency contact name</label><input name="emergency_contact_name" maxlength="150" value="<?= e($_POST['emergency_contact_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Emergency contact number</label><input name="emergency_contact_number" maxlength="50" value="<?= e($_POST['emergency_contact_number'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Emergency contact address</label><textarea name="emergency_contact_address"><?= e($_POST['emergency_contact_address'] ?? '') ?></textarea></div>
                </div>
            </section>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 3</span><h2>Photo, signature, and contact</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group"><label>Employee photo <span class="required">*</span></label><label class="public-file-drop"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required data-public-file><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg><strong>Choose employee photo</strong><small>JPG, PNG, or WebP · maximum 5 MB</small><em data-file-label>No file selected</em></label></div>
                    <div class="form-group"><label>Signature image <span class="required">*</span></label><label class="public-file-drop"><input type="file" name="signature" accept="image/jpeg,image/png,image/webp" required data-public-file><svg viewBox="0 0 24 24"><path d="M4 19c4-1 5-8 7-8 1.5 0-1 6 1 6 1.5 0 2-4 3-4 1.5 0 0 4 2 4 1 0 2-.5 3-1"/></svg><strong>Choose signature image</strong><small>Transparent PNG is recommended · maximum 5 MB</small><em data-file-label>No file selected</em></label></div>
                    <div class="form-group"><label>Your email</label><input type="email" name="requester_email" maxlength="150" value="<?= e($_POST['requester_email'] ?? '') ?>" placeholder="For clarification, if needed"></div>
                    <div class="form-group"><label>Your contact number</label><input name="requester_phone" maxlength="50" value="<?= e($_POST['requester_phone'] ?? '') ?>"></div>
                    <label class="public-consent full"><input type="checkbox" name="privacy_consent" value="1" required <?= !empty($_POST['privacy_consent']) ? 'checked' : '' ?>><span>I confirm that the information is accurate and that I am authorized to submit these employee details and images for ID processing.</span></label>
                </div>
            </section>
            <div class="public-submit-bar"><span>Submission status will start as <strong>Pending</strong>.</span><button class="btn btn-primary" type="submit">Submit ID request</button></div>
        </form>
    <?php endif; ?>
    <footer class="public-footer"><span>MICEI Information Technology Department</span><span>Protected request intake</span></footer>
</main>
</body>
</html>
