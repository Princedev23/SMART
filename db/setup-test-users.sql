-- Run this SQL to create test users for the attendance system
-- Execute this in phpMyAdmin or via MySQL command line

USE attendance_system;

-- Create admin user
INSERT INTO users (email, password_hash, role) 
SELECT 'admin@school.edu', '$2y$10$IJ/tLTUUev.ZjTSOrbfmt.hla1OUp8tlR9LE1DcsKxbmsv0qgEDSe', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@school.edu');

-- Get admin user_id
SET @admin_user_id = (SELECT id FROM users WHERE email = 'admin@school.edu' LIMIT 1);

-- Create lecturer user
INSERT INTO users (email, password_hash, role) 
SELECT 'lecturer@school.edu', '$2y$10$RXJ4zFVAOcY6oYB5ZHFFbuOb4V9zV9QBpirJKKYge1jrE8aezSOjm', 'lecturer'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'lecturer@school.edu');

-- Get lecturer user_id
SET @lecturer_user_id = (SELECT id FROM users WHERE email = 'lecturer@school.edu' LIMIT 1);

-- Create lecturer profile
INSERT INTO lecturers (user_id, name, email, phone_number, courses_teaching)
SELECT @lecturer_user_id, 'Prof. John Smith', 'lecturer@school.edu', '555-0100', '["CS101", "CS102"]'
WHERE NOT EXISTS (SELECT 1 FROM lecturers WHERE user_id = @lecturer_user_id);

-- Create student user
INSERT INTO users (email, password_hash, role) 
SELECT 'student@school.edu', '$2y$10$Ao4EA/eDPsqCzkg9nSsvyuNWsnTLsLguQAqJjWsKsn93pQyNxBdZ6', 'student'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'student@school.edu');

-- Get student user_id
SET @student_user_id = (SELECT id FROM users WHERE email = 'student@school.edu' LIMIT 1);

-- Create student profile
INSERT INTO students (user_id, name, email, department, phone_number, guardian_email)
SELECT @student_user_id, 'John Student', 'student@school.edu', 'Computer Science', '555-0101', 'parent@school.edu'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE user_id = @student_user_id);

-- Get IDs for attendance
SET @lecturer_id = (SELECT id FROM lecturers WHERE user_id = @lecturer_user_id LIMIT 1);
SET @student_id = (SELECT id FROM students WHERE user_id = @student_user_id LIMIT 1);

-- Create sample attendance records (past 5 days)
INSERT INTO attendance (student_id, lecturer_id, date, time, status, confidence_score)
SELECT @student_id, @lecturer_id, DATE_SUB(CURDATE(), INTERVAL n DAY), NOW(), 
       CASE WHEN n > 2 THEN 'present' ELSE 'absent' END, 0.95
FROM (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) AS days
WHERE NOT EXISTS (
    SELECT 1 FROM attendance 
    WHERE student_id = @student_id 
    AND lecturer_id = @lecturer_id
    AND date = DATE_SUB(CURDATE(), INTERVAL n DAY)
);

-- Display results
SELECT 'Setup complete!' AS message;

SELECT '=== TEST CREDENTIALS ===' AS info;
SELECT 'admin@school.edu / password123' AS admin;
SELECT 'lecturer@school.edu / password123' AS lecturer;  
SELECT 'student@school.edu / password123' AS student;
SELECT '=======================' AS info;

