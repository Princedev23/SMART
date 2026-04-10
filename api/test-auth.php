<?php
// Quick test for login
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';

echo json_encode([
    'received' => [
        'email' => $email,
        'role' => $role,
        'password_provided' => !empty($password)
    ]
]);

try {
    $result = executeQuery(
        "SELECT id, email, password_hash, role FROM users WHERE email = ? AND role = ?",
        [$email, $role]
    );

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $user = $result->fetch_assoc();
    
    $verifyResult = verifyPassword($password, $user['password_hash']);
    
    echo json_encode([
        'success' => $verifyResult,
        'user_found' => true,
        'user_email' => $user['email'],
        'user_role' => $user['role']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

