<?php
require_once 'config/db.php';

echo "Setting up test database...\n";

// Create admin user
$adminEmail = 'admin@school.edu';
$adminPassword = 'admin123';

$result = executeQuery("SELECT id FROM users WHERE email = ?", [$adminEmail]);
if ($result->num_rows === 0) {
    $passwordHash = hashPassword($adminPassword);
    executeQuery(
        "INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')",
        [$adminEmail, $passwordHash]
    );
    echo "Admin user created: $adminEmail / $adminPassword\n";
} else {
    echo "Admin user already exists\n";
}

// Create lecturer user
$lecturerEmail = 'lecturer@school.edu';
$lecturerPassword = 'lecturer123';

$result = executeQuery("SELECT id FROM users WHERE email = ?", [$lecturerEmail]);
if ($result->num_rows === 0) {
    $passwordHash = hashPassword($lecturerPassword);
    executeQuery(
        "INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'lecturer')",
        [$lecturerEmail, $passwordHash]
    );
    $lecturerUserId = getLastInsertId();
    
    executeQuery(
        "INSERT INTO lecturers (user_id, name, email, phone_number, courses_teaching) VALUES (?, ?, ?, ?, ?)",
        [$lecturerUserId, 'Prof. John Smith', $lecturerEmail, '555-0100', json_encode(['CS101', 'CS102'])]
    );
    
    echo "Lecturer user created: $lecturerEmail / $lecturerPassword\n";
} else {
    echo "Lecturer user already exists\n";
}

// Create student user
$studentEmail = 'student@school.edu';
$studentPassword = 'student123';

$result = executeQuery("SELECT id FROM users WHERE email = ?", [$studentEmail]);
if ($result->num_rows === 0) {
    $passwordHash = hashPassword($studentPassword);
    executeQuery(
        "INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')",
        [$studentEmail, $passwordHash]
    );
    $studentUserId = getLastInsertId();
    
    executeQuery(
        "INSERT INTO students (user_id, name, email, department, phone_number, guardian_email) VALUES (?, ?, ?, ?, ?, ?)",
        [$studentUserId, 'John Student', $studentEmail, 'Computer Science', '555-0101', 'parent@school.edu']
    );
    
    echo "Student user created: $studentEmail / $studentPassword\n";
} else {
    echo "Student user already exists\n";
}

// Get a lecturer ID for attendance records
$lecturerResult = executeQuery("SELECT id FROM lecturers LIMIT 1");
$lecturerId = $lecturerResult->fetch_assoc()['id'] ?? null;

// Get a student ID for attendance records
$studentResult = executeQuery("SELECT id FROM students LIMIT 1");
$studentId = $studentResult->fetch_assoc()['id'] ?? null;

if ($lecturerId && $studentId) {
    // Add sample attendance records for the current month
    $year = date('Y');
    $month = date('m');
    
    // Check if attendance records exist
    $attendanceResult = executeQuery(
        "SELECT COUNT(*) as count FROM attendance WHERE student_id = ? AND lecturer_id = ?",
        [$studentId, $lecturerId]
    );
    $attendanceCount = $attendanceResult->fetch_assoc()['count'] ?? 0;
    
    if ($attendanceCount == 0) {
        // Create sample attendance for past 5 days
        for ($i = 5; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $status = $i > 2 ? 'present' : 'absent';
            
            executeQuery(
                "INSERT INTO attendance (student_id, lecturer_id, date, time, status, confidence_score) VALUES (?, ?, ?, NOW(), ?, ?)",
                [$studentId, $lecturerId, $date, $status, 0.95]
            );
        }
        echo "Sample attendance records created\n";
    } else {
        echo "Attendance records already exist\n";
    }
}

echo "\n=== TEST CREDENTIALS ===\n";
echo "Admin: admin@school.edu / admin123\n";
echo "Lecturer: lecturer@school.edu / lecturer123\n";
echo "Student: student@school.edu / student123\n";
echo "=======================\n";

echo "Database setup complete!\n";
?>
