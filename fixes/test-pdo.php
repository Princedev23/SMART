<?php
// Test database connection using PDO
header('Content-Type: text/plain');

echo "Testing database connection with PDO...\n\n";

try {
    // Try connecting using PDO
    $pdo = new PDO(
        "mysql:host=localhost:3307;dbname=attendance_system",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✓ Connected to MySQL successfully!\n\n";
    
    // Check users table
    $stmt = $pdo->query("SELECT id, email, role FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found. Creating test users...\n\n";
        
        // Create test users with password "password"
        $passwordHash = password_hash('password', PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
        
        // Admin
        $stmt->execute(['admin@school.edu', $passwordHash, 'admin']);
        echo "✓ Created admin user\n";
        
        // Lecturer
        $stmt->execute(['lecturer@school.edu', $passwordHash, 'lecturer']);
        echo "✓ Created lecturer user\n";
        
        // Student
        $stmt->execute(['student@school.edu', $passwordHash, 'student']);
        echo "✓ Created student user\n";
        
        echo "\n";
    } else {
        echo "Users in database:\n";
        foreach ($users as $user) {
            echo "- " . $user['email'] . " (" . $user['role'] . ")\n";
        }
        echo "\n";
    }
    
    echo "=================================\n";
    echo "LOGIN CREDENTIALS:\n";
    echo "=================================\n";
    echo "Student:  student@school.edu / password\n";
    echo "Lecturer: lecturer@school.edu / password\n";
    echo "Admin:    admin@school.edu / password\n";
    echo "=================================\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nError Code: " . $e->getCode() . "\n";
}
?>

