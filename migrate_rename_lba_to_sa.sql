USE automated_id_maker;

-- The LBA account is now SA (System Admin). The row keeps its id, password, and
-- audit history, so only the username and display name change.
-- Safe to import again: it does nothing once the account is already named SA.
UPDATE users
SET username = 'sa',
    full_name = 'SA'
WHERE username = 'lba';
