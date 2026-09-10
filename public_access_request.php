<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/access_requests.php';

$pdo = db();
ensure_access_request_schema($pdo);
$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        http_response_code(422);
        exit('Submission rejected.');
    }
    if (!public_submission_allowed('access_request')) {
        $errors[] = 'Please wait before submitting another access request.';
    }

    $data = [
        'requester_name' => trim((string) ($_POST['requester_name'] ?? '')),
        'department_id' => (int) ($_POST['department_id'] ?? 0),
        'dmis_username' => trim((string) ($_POST['dmis_username'] ?? '')),
        'module' => trim((string) ($_POST['module'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if ($data['requester_name'] === '' || $data['department_id'] < 1 || $data['dmis_username'] === '' || $data['module'] === '' || $data['description'] === '') {
        $errors[] = 'Complete all required fields.';
    }
    if (empty($_POST['privacy_consent'])) {
        $errors[] = 'Confirm the accuracy declaration before submitting.';
    }
    $departmentCheck = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = ?');
    $departmentCheck->execute([$data['department_id']]);
    if ((int) $departmentCheck->fetchColumn() === 0) {
        $errors[] = 'Select a valid department.';
    }

    if (!$errors) {
        try {
            $reference = next_access_request_reference($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO access_requests
                 (reference_no, requester_name, department_id, dmis_username, module, description, status, submitted_ip)
                 VALUES
                 (:reference_no, :requester_name, :department_id, :dmis_username, :module, :description, "Pending", :submitted_ip)'
            );
            $stmt->execute($data + [
                'reference_no' => $reference,
                'submitted_ip' => client_ip(),
            ]);
            mark_public_submission('access_request');
            redirect('public_access_request.php?submitted=' . urlencode($reference));
        } catch (PDOException $exception) {
            $errors[] = 'The access request could not be submitted. Please try again.';
        }
    }
}

$submitted = preg_match('/^DAR-\d{4}-\d{4}$/', (string) ($_GET['submitted'] ?? '')) ? (string) $_GET['submitted'] : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DMIS Access Request · MICEI</title>
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
            <h1>Your DMIS access request is now pending review.</h1>
            <p>Save this tracking reference. The IT Department may contact you through your department if clarification is needed.</p>
            <strong class="tracking-reference"><?= e($submitted) ?></strong>
            <div class="public-success-actions"><a class="btn btn-primary" href="public_requests.php">Return to request portal</a><a class="btn btn-secondary" href="public_access_request.php">Submit another request</a></div>
        </section>
    <?php else: ?>
        <header class="public-form-header">
            <span class="public-kicker">DMIS Access Request</span>
            <h1>Request DMIS system access</h1>
        </header>
        <?php if ($errors): ?><div class="public-errors"><strong>Please review the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form class="public-request-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="public-honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 1</span><h2>Requestor details</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group"><label>Name <span class="required">*</span></label><input name="requester_name" required maxlength="150" value="<?= e($_POST['requester_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Department <span class="required">*</span></label><select name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) ($_POST['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group full"><label>DMIS username <span class="required">*</span></label><input name="dmis_username" required maxlength="100" value="<?= e($_POST['dmis_username'] ?? '') ?>"></div>
                </div>
            </section>

            <section class="card public-section">
                <div class="card-header"><div><span class="public-step">Step 2</span><h2>Access being requested</h2></div></div>
                <div class="card-body form-grid">
                    <div class="form-group full"><label>Module <span class="required">*</span></label><input name="module" required maxlength="150" placeholder="e.g. Inventory, Sales, Payroll" value="<?= e($_POST['module'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Description <span class="required">*</span></label><textarea name="description" required placeholder="Describe the specific access needed and the reason for the request"><?= e($_POST['description'] ?? '') ?></textarea></div>
                    <label class="public-consent full"><input type="checkbox" name="privacy_consent" value="1" required <?= !empty($_POST['privacy_consent']) ? 'checked' : '' ?>><span>I confirm that the information above is accurate and that I am authorized to request this system access.</span></label>
                </div>
            </section>
            <div class="public-submit-bar"><span>Submission status will start as <strong>Pending</strong>.</span><button class="btn btn-primary" type="submit">Submit access request</button></div>
        </form>
    <?php endif; ?>
    <footer class="public-footer"><span>MICEI Information Technology Department</span><span>Protected request intake</span></footer>
</main>
</body>
</html>
