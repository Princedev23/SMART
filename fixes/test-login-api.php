<?php
// Test login API directly
header('Content-Type: application/json');

require_once 'config/db.php';

// Simulate login
$email = 'student@school.edu';
$password = 'password';
$role = 'student';

echo "Testing login for: $email, role: $role\n\n";

try {
    $result = executeQuery(
        "SELECT id, email, password_hash, role FROM users WHERE email = ? AND role = ?",
        [$email, $role]
    );

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    echo "User found: " . $user['email'] . " (" . $user['role'] . ")\n";
    echo "Stored hash: " . $user['password_hash'] . "\n\n";

    // Verify password
    $verifyResult = verifyPassword($password, $user['password_hash']);
    echo "Password verification result: " . ($verifyResult ? "SUCCESS" : "FAILED") . "\n";

    if ($verifyResult) {
        echo "\n✓ Login would be successful!\n";
    } else {
        echo "\n✗ Login would fail - wrong password\n";
        
        // Let's fix the password
        echo "Updating password hash...\n";
        $newHash = hashPassword($password);
        executeQuery(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$newHash, $user['id']]
        );
        echo "Password updated.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

