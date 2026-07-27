USE automated_id_maker;

ALTER TABLE employees
    ADD COLUMN company_code VARCHAR(10) NOT NULL DEFAULT 'MGSC' AFTER department_id,
    ADD INDEX idx_employee_company_code (company_code);
