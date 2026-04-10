<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit();
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($action === 'profile') { getLecturerProfile(); exit(); }

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['error' => 'Access denied']); exit();
}

switch ($action) {
    case 'list':   listLecturers();   break;
    case 'add':    addLecturer();     break;
    case 'delete': deleteLecturer();  break;
    case 'get':    getLecturer();     break;
    case 'update': updateLecturer();  break;
    default: http_response_code(400); echo json_encode(['error' => 'Invalid action']);
}

function getLecturerProfile() {
    $userId = $_SESSION['user_id'];
    try {
        $result = executeQuery("
            SELECT l.id, l.name, l.email, l.gender, l.phone_number, l.courses_teaching, l.lecturer_image_path, l.created_at
            FROM lecturers l WHERE l.user_id = ?
        ", [$userId]);
        if ($result->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Profile not found']); return; }
        $lecturer = $result->fetch_assoc();
        $lecturer['courses_teaching'] = json_decode($lecturer['courses_teaching'], true) ?? [];
        echo json_encode(['success' => true, 'data' => $lecturer]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch profile']); }
}

function listLecturers() {
    try {
        $result = executeQuery("SELECT l.id, l.name, l.email, l.gender, l.phone_number, l.courses_teaching, l.lecturer_image_path FROM lecturers l ORDER BY l.name ASC");
        $lecturers = [];
        while ($row = $result->fetch_assoc()) {
            $row['courses_teaching'] = json_decode($row['courses_teaching'], true) ?? [];
            $lecturers[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $lecturers]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch lecturers']); }
}

function addLecturer() {
    $name        = isset($_POST['name'])             ? sanitize($_POST['name'])             : '';
    $email       = isset($_POST['email'])            ? sanitize($_POST['email'])            : '';
    $gender      = isset($_POST['gender'])           ? sanitize($_POST['gender'])           : '';
    $phoneNumber = isset($_POST['phone_number'])     ? sanitize($_POST['phone_number'])     : '';
    $courses     = isset($_POST['courses_teaching']) ? sanitize($_POST['courses_teaching']) : '';
    $password    = generatePassword();

    if (!$name || !$email || !$courses) {
        http_response_code(400); echo json_encode(['error' => 'Missing required fields']); return;
    }
    try {
        $coursesArray = array_map('trim', explode(',', $courses));
        $courseJson   = json_encode($coursesArray);
        $passwordHash = hashPassword($password);

        executeQuery("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'lecturer')", [$email, $passwordHash]);
        $userId = getLastInsertId();

        $lecturerImagePath = null;
        if (isset($_FILES['lecturer_image']) && $_FILES['lecturer_image']['error'] == 0) {
            $uploadDir = '../uploads/lecturers/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileExt  = pathinfo($_FILES['lecturer_image']['name'], PATHINFO_EXTENSION);
            $fileName = $userId . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['lecturer_image']['tmp_name'], $uploadDir . $fileName))
                $lecturerImagePath = 'uploads/lecturers/' . $fileName;
        }

        executeQuery(
            "INSERT INTO lecturers (user_id, name, email, gender, phone_number, courses_teaching, lecturer_image_path) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $name, $email, $gender, $phoneNumber, $courseJson, $lecturerImagePath]
        );

        sendCredentialsEmail($email, $name, $email, $password, 'Lecturer');
        echo json_encode(['success' => true, 'message' => 'Lecturer added. Login credentials sent to ' . $email, 'credentials' => ['email' => $email, 'password' => $password]]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to add lecturer: ' . $e->getMessage()]); }
}

function updateLecturer() {
    $data        = json_decode(file_get_contents("php://input"), true);
    $id          = isset($data['id'])           ? (int)$data['id']                : 0;
    $name        = isset($data['name'])         ? sanitize($data['name'])         : '';
    $gender      = isset($data['gender'])       ? sanitize($data['gender'])       : '';
    $phoneNumber = isset($data['phone_number']) ? sanitize($data['phone_number']) : '';
    $courses     = isset($data['courses'])      ? $data['courses']                : [];

    if (!$id || !$name) {
        http_response_code(400); echo json_encode(['error' => 'Missing required fields']); return;
    }
    try {
        $coursesJson = json_encode(is_array($courses) ? $courses : explode(',', str_replace(' ', '', $courses)));
        executeQuery(
            "UPDATE lecturers SET name=?, gender=?, phone_number=?, courses_teaching=? WHERE id=?",
            [$name, $gender, $phoneNumber, $coursesJson, $id]
        );
        echo json_encode(['success' => true, 'message' => 'Lecturer updated']);
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error' => 'Failed to update: ' . $e->getMessage()]);
    }
}

function deleteLecturer() {
    $id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Lecturer ID required']); return; }
    try {
        $result = executeQuery("SELECT user_id FROM lecturers WHERE id = ?", [$id]);
        if ($result->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Lecturer not found']); return; }
        $lecturer = $result->fetch_assoc();
        executeQuery("DELETE FROM lecturers WHERE id = ?", [$id]);
        executeQuery("DELETE FROM users WHERE id = ?", [$lecturer['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Lecturer deleted']);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to delete lecturer']); }
}

function getLecturer() {
    $id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Lecturer ID required']); return; }
    try {
        $result = executeQuery("SELECT id, name, email, gender, phone_number, courses_teaching, lecturer_image_path FROM lecturers WHERE id = ?", [$id]);
        if ($result->num_rows === 0) { http_response_code(404); echo json_encode(['error' => 'Lecturer not found']); return; }
        $lecturer = $result->fetch_assoc();
        $lecturer['courses_teaching'] = json_decode($lecturer['courses_teaching'], true) ?? [];
        echo json_encode(['success' => true, 'data' => $lecturer]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch lecturer']); }
}

function sendCredentialsEmail($to, $name, $email, $password, $role) {
    // ── SMTP CONFIG ── same settings as student.php ───────────────────────
    // Edit once here (or better: move to a shared config/mail.php)
    if (!defined('SMTP_HOST')) define('SMTP_HOST',     'smtp.gmail.com');
    if (!defined('SMTP_PORT')) define('SMTP_PORT',     587);
    if (!defined('SMTP_USER')) define('SMTP_USER',     'your-email@gmail.com');
    if (!defined('SMTP_PASS')) define('SMTP_PASS',     'your-app-password');
    if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Smart Attendance');
    // ─────────────────────────────────────────────────────────────────────

    $subject = "Your Smart Attendance System Login Credentials";
    $body = "<!DOCTYPE html><html><head><style>
        body{font-family:Arial,sans-serif;color:#333;margin:0;padding:0}
        .container{max-width:600px;margin:0 auto;padding:20px}
        .header{background:#8B1A2A;color:white;padding:24px 20px;border-radius:8px 8px 0 0;text-align:center}
        .header h2{margin:0;font-size:22px}
        .content{background:#f8fafc;padding:30px;border-radius:0 0 8px 8px;border:1px solid #e2e8f0}
        .cred-box{background:white;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin:20px 0}
        .cred-label{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
        .cred-value{font-size:17px;font-weight:bold;color:#1e293b;word-break:break-all}
        .footer{font-size:12px;color:#94a3b8;margin-top:20px;text-align:center}
    </style></head><body>
    <div class='container'>
        <div class='header'><h2>&#127979; Welcome to Smart Attendance</h2></div>
        <div class='content'>
            <p>Dear <strong>$name</strong>,</p>
            <p>Your <strong>$role</strong> account has been created. Use the credentials below to log in:</p>
            <div class='cred-box'>
                <div class='cred-label'>Email / Username</div>
                <div class='cred-value'>$email</div>
                <br>
                <div class='cred-label'>Password</div>
                <div class='cred-value'>$password</div>
            </div>
            <p>&#128274; Please keep your credentials safe and do not share them.</p>
            <p>Best regards,<br><strong>Smart Attendance Administration</strong></p>
            <div class='footer'>This is an automated message. Please do not reply.</div>
        </div>
    </div></body></html>";

    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($composerAutoload)) {
        $composerAutoload = __DIR__ . '/../lib/PHPMailer/autoload.php';
    }
    if (!file_exists($composerAutoload)) {
        error_log('PHPMailer not found. Run: composer require phpmailer/phpmailer');
        return false;
    }

    require_once $composerAutoload;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = (SMTP_PORT === 465)
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = "Welcome $name!\n\nYour $role credentials:\nEmail: $email\nPassword: $password\n\nKeep these safe.";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('PHPMailer error for ' . $to . ': ' . $mail->ErrorInfo);
        return false;
    }
}

function generatePassword() {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
    $p = '';
    for ($i = 0; $i < 10; $i++) $p .= $chars[random_int(0, strlen($chars)-1)];
    return $p;
}
?>
