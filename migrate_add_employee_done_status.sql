USE automated_id_maker;

ALTER TABLE employees
    ADD COLUMN id_is_done TINYINT(1) NOT NULL DEFAULT 0 AFTER signature_path,
    ADD COLUMN id_done_by INT UNSIGNED NULL AFTER id_is_done,
    ADD COLUMN id_done_at DATETIME NULL AFTER id_done_by,
    ADD INDEX idx_employee_id_done (id_is_done),
    ADD CONSTRAINT fk_employee_id_done_by
        FOREIGN KEY (id_done_by) REFERENCES users(id) ON DELETE SET NULL;
