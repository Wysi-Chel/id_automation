USE automated_id_maker;

-- ITA, JRN, and SA sign in with their own accounts instead of the shared admin account.
-- SA (System Admin) is the Super Administrator who gives the final review on System Monitoring access requests.
-- Safe to import again: it never overwrites a password its owner has already changed.
ALTER TABLE users
    MODIFY role ENUM('Administrator','Encoder','Super Administrator') NOT NULL DEFAULT 'Encoder';

-- Each account starts with the default password until its owner changes it.
SET @default_password_hash = '$2y$10$.0yYhyLA3/wGxGRkI4YG5O9tsq7xTgH.j7EE72zMNx8EKtIszJqJu';

INSERT IGNORE INTO users (username, full_name, password_hash, role) VALUES
('ita', 'ITA', @default_password_hash, 'Administrator'),
('jrn', 'JRN', @default_password_hash, 'Administrator'),
('sa', 'SA', @default_password_hash, 'Super Administrator');

-- Earlier imports copied the admin password; move those accounts to the default
-- unless their owner has already set a personal password.
UPDATE users
SET password_hash = @default_password_hash
WHERE username IN ('ita', 'jrn', 'sa')
  AND NOT EXISTS (
      SELECT 1
      FROM audit_logs
      WHERE audit_logs.user_id = users.id
        AND audit_logs.action_type = 'PASSWORD_CHANGE'
  );

UPDATE users SET is_active = 0 WHERE username = 'admin';
