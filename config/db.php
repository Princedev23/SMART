<?php

define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'error' => 'Database connection error',
        'details' => $conn->connect_error,
        'errno'   => $conn->connect_errno,
        'hint'    => 'Check DB_HOST, DB_PORT, DB_USER, DB_PASS in config/db.php'
    ]));
}

$conn->set_charset("utf8");

function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function executeQuery($sql, $params = []) {
    global $conn;

    if (empty($params)) {
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception($conn->error);
        }
        return $result;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    return $stmt->get_result();
}

function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

?>
