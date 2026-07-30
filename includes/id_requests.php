<?php

declare(strict_types=1);

function ensure_id_request_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS id_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reference_no VARCHAR(30) NOT NULL UNIQUE,
            employee_no VARCHAR(50) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NOT NULL,
            suffix VARCHAR(20) NULL,
            position VARCHAR(150) NOT NULL,
            department_id INT UNSIGNED NOT NULL,
            company_code VARCHAR(10) NOT NULL,
            date_of_birth DATE NULL,
            date_hired DATE NULL,
            sss_number VARCHAR(50) NULL,
            philhealth_number VARCHAR(50) NULL,
            tin_number VARCHAR(50) NULL,
            hdmf_number VARCHAR(50) NULL,
            emergency_contact_name VARCHAR(150) NULL,
            emergency_contact_address TEXT NULL,
            emergency_contact_number VARCHAR(50) NULL,
            requester_email VARCHAR(150) NULL,
            requester_phone VARCHAR(50) NULL,
            photo_path VARCHAR(255) NOT NULL,
            signature_path VARCHAR(255) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            review_notes TEXT NULL,
            employee_id INT UNSIGNED NULL,
            submitted_ip VARCHAR(45) NOT NULL,
            reviewed_by INT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_id_request_department FOREIGN KEY (department_id) REFERENCES departments(id),
            CONSTRAINT fk_id_request_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
            CONSTRAINT fk_id_request_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_id_request_status (status),
            INDEX idx_id_request_employee_no (employee_no),
            INDEX idx_id_request_created (created_at)
        ) ENGINE=InnoDB"
    );
    $ready = true;
}

function next_id_request_reference(PDO $pdo): string
{
    $prefix = 'IDR-' . date('Y') . '-';
    $stmt = $pdo->prepare('SELECT reference_no FROM id_requests WHERE reference_no LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');
    $sequence = $last !== '' ? (int) substr($last, -4) + 1 : 1;
    return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
}

function fetch_id_request(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, d.name AS department_name
         FROM id_requests r JOIN departments d ON d.id = r.department_id
         WHERE r.id = ?'
    );
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    return $request ?: null;
}

function id_request_name(array $request): string
{
    return trim(implode(' ', array_filter([
        trim((string) ($request['first_name'] ?? '')),
        trim((string) ($request['middle_name'] ?? '')),
        trim((string) ($request['last_name'] ?? '')),
        trim((string) ($request['suffix'] ?? '')),
    ])));
}

function public_submission_allowed(string $key, int $cooldown = 45): bool
{
    $last = (int) ($_SESSION['public_submit_times'][$key] ?? 0);
    return $last === 0 || (time() - $last) >= $cooldown;
}

function mark_public_submission(string $key): void
{
    $_SESSION['public_submit_times'][$key] = time();
}
