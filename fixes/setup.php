<?php
/**
 * Simple Setup Script - Run this via browser
 * URL: http://localhost/smartat/setup.php
 */

require_once 'config/db.php';

echo "<h1>Setting up test users...</h1>";

try {
    // Test database connection first
    global $conn;
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "<p>✓ Database connected successfully</p>";
    
    // Define test users - matching test-db.php credentials
    $testUsers = [
        [
            'email' => 'admin@school.edu',
            'password' => 'admin123',
            'role' => 'admin',
            'name' => 'Admin User'
        ],
        [
            'email' => 'lecturer@school.edu',
            'password' => 'lecturer123',
            'role' => 'lecturer',
            'name' => 'Prof. John Smith'
        ],
        [
            'email' => 'student@school.edu',
            'password' => 'student123',
            'role' => 'student',
            'name' => 'John Student'
        ]
    ];

    foreach ($testUsers as $user) {
        // Check if user exists
        $result = executeQuery("SELECT id FROM users WHERE email = ?", [$user['email']]);
        
        if ($result->num_rows === 0) {
            // Create user
            $passwordHash = hashPassword($user['password']);
            executeQuery(
                "INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)",
                [$user['email'], $passwordHash, $user['role']]
            );
            $userId = getLastInsertId();
            echo "<p>✓ Created user: " . $user['email'] . " with role: " . $user['role'] . "</p>";
            
            // Create profile in respective table
            if ($user['role'] === 'student') {
                executeQuery(
                    "INSERT INTO students (user_id, name, email, department, phone_number, guardian_email) VALUES (?, ?, ?, ?, ?, ?)",
                    [$userId, $user['name'], $user['email'], 'Computer Science', '555-0101', 'parent@school.edu']
                );
                echo "<p>  ✓ Created student profile</p>";
            } elseif ($user['role'] === 'lecturer') {
                executeQuery(
                    "INSERT INTO lecturers (user_id, name, email, phone_number, courses_teaching) VALUES (?, ?, ?, ?, ?)",
                    [$userId, $user['name'], $user['email'], '555-0100', json_encode(['CS101', 'CS102'])]
                );
                echo "<p>  ✓ Created lecturer profile</p>";
            }
        } else {
            // User exists, update password to ensure it works
            $userData = $result->fetch_assoc();
            $userId = $userData['id'];
            $passwordHash = hashPassword($user['password']);
            executeQuery(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$passwordHash, $userId]
            );
            echo "<p>✓ Updated password for: " . $user['email'] . "</p>";
        }
    }

    echo "<h2>Setup Complete!</h2>";
    echo "<h3>Test Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@school.edu / admin123</li>";
    echo "<li><strong>Lecturer:</strong> lecturer@school.edu / lecturer123</li>";
    echo "<li><strong>Student:</strong> student@school.edu / student123</li>";
    echo "</ul>";
    echo "<p><em>Note: Make sure to select the correct role (Admin/Lecturer/Student) when logging in!</em></p>";
    
    // Debug: Show current users in database
    echo "<h3>Current Users in Database:</h3>";
    $result = executeQuery("SELECT id, email, role FROM users");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['email'] . "</td><td>" . $row['role'] . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

