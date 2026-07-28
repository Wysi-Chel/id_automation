USE automated_id_maker;

ALTER TABLE employees
    ADD COLUMN id_is_released TINYINT(1) NOT NULL DEFAULT 0 AFTER id_done_at,
    ADD COLUMN id_released_by INT UNSIGNED NULL AFTER id_is_released,
    ADD COLUMN id_released_at DATETIME NULL AFTER id_released_by,
    ADD INDEX idx_employee_id_released (id_is_released),
    ADD CONSTRAINT fk_employee_id_released_by
        FOREIGN KEY (id_released_by) REFERENCES users(id) ON DELETE SET NULL;
