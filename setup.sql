CREATE DATABASE IF NOT EXISTS automated_id_maker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE automated_id_maker;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Administrator','Encoder') NOT NULL DEFAULT 'Encoder',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_no VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20) NULL,
    position VARCHAR(150) NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    company_code VARCHAR(10) NOT NULL DEFAULT 'MGSC',
    date_of_birth DATE NULL,
    date_hired DATE NULL,
    sss_number VARCHAR(50) NULL,
    philhealth_number VARCHAR(50) NULL,
    tin_number VARCHAR(50) NULL,
    hdmf_number VARCHAR(50) NULL,
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_address TEXT NULL,
    emergency_contact_number VARCHAR(50) NULL,
    photo_path VARCHAR(255) NULL,
    signature_path VARCHAR(255) NULL,
    id_is_done TINYINT(1) NOT NULL DEFAULT 0,
    id_done_by INT UNSIGNED NULL,
    id_done_at DATETIME NULL,
    id_is_released TINYINT(1) NOT NULL DEFAULT 0,
    id_released_by INT UNSIGNED NULL,
    id_released_at DATETIME NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_employee_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_id_done_by FOREIGN KEY (id_done_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_id_released_by FOREIGN KEY (id_released_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_department (department_id),
    INDEX idx_employee_company_code (company_code),
    INDEX idx_employee_status (status),
    INDEX idx_employee_id_done (id_is_done),
    INDEX idx_employee_id_released (id_is_released),
    INDEX idx_employee_name (last_name, first_name)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    employee_id INT UNSIGNED NULL,
    action_type VARCHAR(60) NOT NULL,
    action_description VARCHAR(500) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    done_by INT UNSIGNED NULL,
    done_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_done_by FOREIGN KEY (done_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_audit_action (action_type),
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_employee (employee_id),
    INDEX idx_audit_done (is_done)
) ENGINE=InnoDB;

INSERT INTO departments (name, code) VALUES
('Accounting', 'ACC'),
('Sales', 'SAL'),
('Service', 'SER'),
('Information Technology', 'IT'),
('Human Resource', 'HR'),
('CNC', 'CNC'),
('BRP', 'BRP'),
('Manila Office', 'MNL');

INSERT INTO users (username, full_name, password_hash, role)
VALUES ('admin', 'System Administrator', '$2y$10$Ww2Mhvi5dql17thz7nHsJelL6AlhtMJHFXWHcVtpsPiAzoguriVBm', 'Administrator');
