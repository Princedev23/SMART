-- ============================================================
-- RUN THIS ONCE in phpMyAdmin to fix the root password issue
-- Steps: phpMyAdmin > SQL tab (top menu) > paste this > Go
-- ============================================================

-- Reset root password to empty (no password)
ALTER USER 'root'@'localhost' IDENTIFIED BY '';
FLUSH PRIVILEGES;

-- Verify it worked - you should see root in the result
SELECT user, host, authentication_string FROM mysql.user WHERE user = 'root';
