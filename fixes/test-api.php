<?php
// Test database connection
header('Content-Type: application/json');

echo "Testing database connection...\n";

// Try connecting using null for empty password
$conn = new mysqli('127.0.0.1', 'root', null, '', 3306);

if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Cannot connect to MySQL',
        'error' => $conn->connect_error,
        'errno' => $conn->connect_errno
    ]);
    exit;
}

echo "Connected to MySQL successfully\n";

// Check if database exists
$result = $conn->query("SHOW DATABASES LIKE 'attendance_system'");
if ($result->num_rows === 0) {
    echo "Database 'attendance_system' does not exist. Creating it...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS attendance_system");
    echo "Database created\n";
}

$conn->select_db('attendance_system');
echo "Selected database\n";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows === 0) {
    echo "Table 'users' does not exist. Creating it...\n";
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'lecturer', 'student') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    echo "Users table created\n";
}

// Check for test users
$result = $conn->query("SELECT id, email, role FROM users");
echo "Users in database:\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['email'] . " (" . $row['role'] . ")\n";
}

echo "\nDatabase setup complete!\n";
echo "You can now try logging in with:\n";
echo "- Student: student@school.edu / password\n";
echo "- Lecturer: lecturer@school.edu / password\n";
echo "- Admin: admin@school.edu / password\n";

$conn->close();
?>

