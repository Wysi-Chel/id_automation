<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid or expired form token.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function full_name(array $employee): string
{
    $parts = [
        trim((string) ($employee['first_name'] ?? '')),
        trim((string) ($employee['middle_name'] ?? '')),
        trim((string) ($employee['last_name'] ?? '')),
        trim((string) ($employee['suffix'] ?? '')),
    ];
    return trim(implode(' ', array_filter($parts, fn($v) => $v !== '')));
}

function display_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $time = strtotime($value);
    return $time ? date('m/d/Y', $time) : '—';
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
}

function audit_log(
    PDO $pdo,
    string $actionType,
    string $description,
    ?int $employeeId = null,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $user = current_user();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs
         (user_id, employee_id, action_type, action_description, old_values, new_values, ip_address, user_agent)
         VALUES (:user_id, :employee_id, :action_type, :description, :old_values, :new_values, :ip, :user_agent)'
    );
    $stmt->execute([
        ':user_id' => $user['id'] ?? null,
        ':employee_id' => $employeeId,
        ':action_type' => $actionType,
        ':description' => $description,
        ':old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
        ':new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
        ':ip' => client_ip(),
        ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
}

function upload_image(string $field, string $folder, ?string $existingPath = null): ?string
{
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $field . '.');
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Uploaded image must be 5 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are accepted.');
    }

    $relativeDir = 'uploads/' . trim($folder, '/');
    $absoluteDir = dirname(__DIR__) . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $absolutePath = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    if ($existingPath && str_starts_with($existingPath, 'uploads/')) {
        $oldAbsolute = dirname(__DIR__) . '/' . $existingPath;
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    return $relativeDir . '/' . $filename;
}

function file_data_uri(?string $relativePath): ?string
{
    if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) {
        return null;
    }
    $path = dirname(__DIR__) . '/' . $relativePath;
    if (!is_file($path)) {
        return null;
    }
    $mime = mime_content_type($path) ?: 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
}

function mask_number(?string $number): string
{
    $number = trim((string) $number);
    if ($number === '') {
        return '—';
    }
    $visible = substr($number, -4);
    return str_repeat('•', max(0, strlen($number) - 4)) . $visible;
}

function employee_snapshot(array $employee): array
{
    $keys = [
        'employee_no', 'first_name', 'middle_name', 'last_name', 'suffix', 'position',
        'department_id', 'date_of_birth', 'date_hired', 'sss_number',
        'philhealth_number', 'tin_number', 'hdmf_number', 'emergency_contact_name',
        'emergency_contact_address', 'emergency_contact_number', 'photo_path',
        'signature_path', 'status'
    ];
    $snapshot = [];
    foreach ($keys as $key) {
        $snapshot[$key] = $employee[$key] ?? null;
    }
    return $snapshot;
}
