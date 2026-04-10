<?php
// Test login API directly via browser
header('Content-Type: text/plain');

require_once 'config/db.php';

$email = $_GET['email'] ?? 'student@school.edu';
$password = $_GET['password'] ?? 'password';
$role = $_GET['role'] ?? 'student';

echo "Testing login API...\n";
echo "Email: $email\n";
echo "Role: $role\n";
echo "Password: " . (empty($password) ? "(empty)" : "provided") . "\n\n";

try {
    $result = executeQuery(
        "SELECT id, email, password_hash, role FROM users WHERE email = ? AND role = ?",
        [$email, $role]
    );

    if ($result->num_rows === 0) {
        echo "ERROR: User not found with email '$email' and role '$role'\n";
        exit;
    }

    $user = $result->fetch_assoc();
    echo "User found: " . $user['email'] . " (role: " . $user['role'] . ")\n";
    echo "Stored hash: " . substr($user['password_hash'], 0, 30) . "...\n\n";

    $verifyResult = verifyPassword($password, $user['password_hash']);
    
    if ($verifyResult) {
        echo "✓ PASSWORD VERIFICATION SUCCESSFUL!\n";
        echo "\nThe backend is working correctly.";
        echo "\n\nTo login, use these exact credentials:";
        echo "\n- Email: $email";
        echo "\n- Password: $password";
        echo "\n- Role: $role";
    } else {
        echo "✗ PASSWORD VERIFICATION FAILED!\n";
        echo "The password '$password' does not match the stored hash.";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
