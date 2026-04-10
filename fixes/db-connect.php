<?php
/**
 * Try to connect with different common XAMPP passwords
 */

echo "<h1>Finding MySQL Password...</h1>";

$test_passwords = array(
    '' => '(empty)',
    'root' => 'root',
    'password' => 'password',
);

foreach ($test_passwords as $pwd => $desc) {
    echo "Trying: $desc<br>";
    
    // Use direct connection without the wrapper
    $conn = mysqli_init();
    @$conn->real_connect('localhost', 'root', $pwd);
    
    if (!$conn->connect_error) {
        echo "<h3 style='color:green'>✓ SUCCESS with password: $desc</h3>";
        
        // Select database
        $conn->select_db('attendance_system');
        
        if ($conn->error) {
            echo "<p>Database error: " . $conn->error . "</p>";
            echo "<p>Please create the database first in phpMyAdmin using db/init.sql</p>";
        } else {
            echo "<p>✓ Connected to attendance_system database</p>";
            
            // Check users
            $result = $conn->query("SELECT COUNT(*) as cnt FROM users");
            $row = $result->fetch_assoc();
            echo "<p>Users in table: " . $row['cnt'] . "</p>";
            
            if ($row['cnt'] == 0) {
                echo "<h3>Creating test users...</h3>";
                
                $users = [
                    ['email' => 'admin@school.edu', 'password' => 'admin123', 'role' => 'admin'],
                    ['email' => 'lecturer@school.edu', 'password' => 'lecturer123', 'role' => 'lecturer'],
                    ['email' => 'student@school.edu', 'password' => 'student123', 'role' => 'student']
                ];
                
                foreach ($users as $u) {
                    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $u['email'], $hash, $u['role']);
                    
                    if ($stmt->execute()) {
                        echo "<p>✓ Created: " . $u['email'] . " (" . $u['role'] . ")</p>";
                    }
                }
                
                echo "<h3>NOW YOU CAN LOGIN WITH:</h3>";
                echo "<ul>";
                echo "<li><strong>Admin:</strong> admin@school.edu / admin123</li>";
                echo "<li><strong>Lecturer:</strong> lecturer@school.edu / lecturer123</li>";
                echo "<li><strong>Student:</strong> student@school.edu / student123</li>";
                echo "</ul>";
            }
        }
        
        $conn->close();
        break;
    }
}

if (!isset($conn) || $conn->connect_error) {
    echo "<h3 style='color:red'>Could not connect. Please check:</h3>";
    echo "<ul>";
    echo "<li>Is XAMPP MySQL running?</li>";
    echo "<li>Check phpMyAdmin user privileges</li>";
    echo "</ul>";
}
?>

