USE automated_id_maker;

ALTER TABLE audit_logs
    ADD COLUMN is_done TINYINT(1) NOT NULL DEFAULT 0 AFTER user_agent,
    ADD COLUMN done_by INT UNSIGNED NULL AFTER is_done,
    ADD COLUMN done_at DATETIME NULL AFTER done_by,
    ADD INDEX idx_audit_done (is_done),
    ADD CONSTRAINT fk_audit_done_by
        FOREIGN KEY (done_by) REFERENCES users(id) ON DELETE SET NULL;
