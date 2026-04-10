<?php
/**
 * Quick User Creator - Run this via browser
 * URL: http://localhost/smartat/create-users.php
 * 
 * This creates users with password: password123 for all accounts
 */

require_once 'config/db.php';

echo "<h1>Creating test users...</h1>";

try {
    // Test database connection first
    global $conn;
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "<p>✓ Database connected</p>";
    
    // Create users with password: password123
    $users = [
        ['email' => 'admin@school.edu', 'password' => 'password123', 'role' => 'admin'],
        ['email' => 'lecturer@school.edu', 'password' => 'password123', 'role' => 'lecturer'],
        ['email' => 'student@school.edu', 'password' => 'password123', 'role' => 'student'],
    ];
    
    foreach ($users as $user) {
        // Check if user exists
        $result = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $result->bind_param("s", $user['email']);
        $result->execute();
        $resultStore = $result->get_result();
        
        if ($resultStore->num_rows > 0) {
            // Update password
            $row = $resultStore->fetch_assoc();
            $userId = $row['id'];
            $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
            
            $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $passwordHash, $userId);
            $update->execute();
            
            echo "<p>✓ Updated password for: " . $user['email'] . " (" . $user['role'] . ")</p>";
        } else {
            // Insert new user
            $passwordHash = password_hash($user['password'], PASSWORD_BCRYPT);
            
            $insert = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $user['email'], $passwordHash, $user['role']);
            $insert->execute();
            $userId = $conn->insert_id;
            
            echo "<p>✓ Created user: " . $user['email'] . " (" . $user['role'] . ")</p>";
            
            // Create profile in respective table
            if ($user['role'] === 'student') {
                $profile = $conn->prepare("INSERT INTO students (user_id, name, email, department, phone_number, guardian_email) VALUES (?, ?, ?, ?, ?, ?)");
                $name = 'John Student';
                $dept = 'Computer Science';
                $phone = '5550101';
                $guardian = 'parent@school.edu';
                $profile->bind_param("isssss", $userId, $name, $user['email'], $dept, $phone, $guardian);
                $profile->execute();
                echo "<p>  ✓ Created student profile</p>";
            } elseif ($user['role'] === 'lecturer') {
                $profile = $conn->prepare("INSERT INTO lecturers (user_id, name, email, phone_number, courses_teaching) VALUES (?, ?, ?, ?, ?)");
                $name = 'Prof. John Smith';
                $phone = '5550100';
                $courses = json_encode(['CS101', 'CS102']);
                $profile->bind_param("issss", $userId, $name, $user['email'], $phone, $courses);
                $profile->execute();
                echo "<p>  ✓ Created lecturer profile</p>";
            }
        }
    }
    
    echo "<h2>Done!</h2>";
    echo "<p><strong>All users created with password: password123</strong></p>";
    echo "<ul>";
    echo "<li>Student: student@school.edu / password123</li>";
    echo "<li>Lecturer: lecturer@school.edu / password123</li>";
    echo "<li>Admin: admin@school.edu / password123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
