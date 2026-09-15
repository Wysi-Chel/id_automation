USE automated_id_maker;

-- ITA, JRN, and LBA sign in with their own accounts instead of the shared admin account.
-- LBA is the Super Administrator who gives the final review on System Monitoring access requests.
ALTER TABLE users
    MODIFY role ENUM('Administrator','Encoder','Super Administrator') NOT NULL DEFAULT 'Encoder';

-- Each account starts with the shared account's current password.
INSERT IGNORE INTO users (username, full_name, password_hash, role)
SELECT accounts.username, accounts.full_name, shared.password_hash, accounts.role
FROM (
    SELECT 'ita' AS username, 'ITA' AS full_name, 'Administrator' AS role
    UNION ALL SELECT 'jrn', 'JRN', 'Administrator'
    UNION ALL SELECT 'lba', 'LBA', 'Super Administrator'
) AS accounts
CROSS JOIN users AS shared
WHERE shared.username = 'admin';

UPDATE users SET is_active = 0 WHERE username = 'admin';
