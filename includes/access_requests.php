<?php

declare(strict_types=1);

function ensure_access_request_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS access_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reference_no VARCHAR(30) NOT NULL UNIQUE,
            requester_name VARCHAR(150) NOT NULL,
            department_id INT UNSIGNED NOT NULL,
            dmis_username VARCHAR(100) NOT NULL,
            module VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            review_notes TEXT NULL,
            submitted_ip VARCHAR(45) NOT NULL,
            reviewed_by INT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_access_request_department FOREIGN KEY (department_id) REFERENCES departments(id),
            CONSTRAINT fk_access_request_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_access_request_status (status),
            INDEX idx_access_request_username (dmis_username),
            INDEX idx_access_request_created (created_at)
        ) ENGINE=InnoDB"
    );
    $ready = true;
}

function next_access_request_reference(PDO $pdo): string
{
    $prefix = 'DAR-' . date('Y') . '-';
    $stmt = $pdo->prepare('SELECT reference_no FROM access_requests WHERE reference_no LIKE ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');
    $sequence = $last !== '' ? (int) substr($last, -4) + 1 : 1;
    return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
}

function fetch_access_request(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, d.name AS department_name
         FROM access_requests r JOIN departments d ON d.id = r.department_id
         WHERE r.id = ?'
    );
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    return $request ?: null;
}
