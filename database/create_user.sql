-- Optional but recommended: a least-privilege MySQL account for the app,
-- so the website never logs in as root.
-- Run as an admin AFTER schema.mysql.sql:   mysql -u root -p < database/create_user.sql
-- Then put the same password in config.local.php.

CREATE USER IF NOT EXISTS 'studybuddy_app'@'localhost' IDENTIFIED BY 'change-me';
CREATE USER IF NOT EXISTS 'studybuddy_app'@'127.0.0.1' IDENTIFIED BY 'change-me';

-- The app only needs to read and write rows; it cannot DROP or ALTER tables.
GRANT SELECT, INSERT, UPDATE ON studybuddy.* TO 'studybuddy_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON studybuddy.* TO 'studybuddy_app'@'127.0.0.1';
FLUSH PRIVILEGES;
