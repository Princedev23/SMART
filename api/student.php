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

if ($action === 'profile') { getMyProfile(); exit(); }

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['error' => 'Access denied']); exit();
}

switch ($action) {
    case 'list':   listStudents();  break;
    case 'add':    addStudent();    break;
    case 'delete': deleteStudent(); break;
    case 'get':    getStudent();    break;
    case 'update': updateStudent(); break;
    default: http_response_code(400); echo json_encode(['error' => 'Invalid action']);
}

function getMyProfile() {
    $userId = $_SESSION['user_id'];
    try {
        $result = executeQuery("
            SELECT s.id, s.name, s.email, s.department, s.gender, s.phone_number,
                   s.parent_phone, s.guardian_email, s.face_image_path, s.created_at
            FROM students s WHERE s.user_id = ?
        ", [$userId]);
        $row = $result->fetch_assoc();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Profile not found']); return; }
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch profile']); }
}

function listStudents() {
    try {
        // Include face_descriptor so the lecturer attendance page can do real matching
        $result = executeQuery("
            SELECT s.id, s.name, s.email, s.department,
                   s.phone_number, s.parent_phone, s.guardian_email,
                   s.face_image_path, s.face_descriptor
            FROM students s ORDER BY s.name ASC
        ");
        $students = [];
        while ($row = $result->fetch_assoc()) $students[] = $row;
        echo json_encode(['success' => true, 'data' => $students]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch students']); }
}

function addStudent() {
    $name           = isset($_POST['name'])            ? sanitize($_POST['name'])            : '';
    $email          = isset($_POST['email'])           ? sanitize($_POST['email'])           : '';
    $department     = isset($_POST['department'])      ? sanitize($_POST['department'])      : '';
    $gender         = isset($_POST['gender'])          ? sanitize($_POST['gender'])          : '';
    $phoneNumber    = isset($_POST['phone_number'])    ? sanitize($_POST['phone_number'])    : '';
    $parentPhone    = isset($_POST['parent_phone'])    ? sanitize($_POST['parent_phone'])    : '';
    $guardianEmail  = isset($_POST['guardian_email'])  ? sanitize($_POST['guardian_email'])  : '';
    // face_descriptor is a JSON string sent from the browser after face-api.js extraction
    $faceDescriptor = isset($_POST['face_descriptor']) ? $_POST['face_descriptor']           : null;
    $password       = generatePassword();

    if (!$name || !$email || !$department || !$guardianEmail) {
        http_response_code(400); echo json_encode(['error' => 'Missing required fields']); return;
    }

    try {
        $passwordHash = hashPassword($password);
        executeQuery("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')", [$email, $passwordHash]);
        $userId = getLastInsertId();

        $faceImagePath = null;
        if (isset($_FILES['face_image']) && $_FILES['face_image']['error'] == 0) {
            $uploadDir = '../uploads/faces/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileExt  = pathinfo($_FILES['face_image']['name'], PATHINFO_EXTENSION);
            $fileName = $userId . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['face_image']['tmp_name'], $uploadDir . $fileName))
                $faceImagePath = 'uploads/faces/' . $fileName;
        }

        executeQuery(
            "INSERT INTO students
                (user_id, name, email, department, gender, phone_number, parent_phone,
                 face_image_path, guardian_email, face_descriptor)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $name, $email, $department, $gender, $phoneNumber, $parentPhone,
             $faceImagePath, $guardianEmail, $faceDescriptor]
        );
//fix
        try {
            sendCredentialsEmail($email, $name, $email, $password, 'Student');
        } catch (Exception $mailErr) {
            error_log('Mail error for ' . $email . ': ' . $mailErr->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Student added successfully.',
            'credentials' => ['email' => $email, 'password' => $password]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add student: ' . $e->getMessage()]);
    }
}

function updateStudent() {
    $data          = json_decode(file_get_contents("php://input"), true);
    $id            = isset($data['id'])             ? (int)$data['id']                  : 0;
    $name          = isset($data['name'])           ? sanitize($data['name'])           : '';
    $department    = isset($data['department'])     ? sanitize($data['department'])     : '';
    $gender        = isset($data['gender'])         ? sanitize($data['gender'])         : '';
    $phoneNumber   = isset($data['phone_number'])   ? sanitize($data['phone_number'])   : '';
    $parentPhone   = isset($data['parent_phone'])   ? sanitize($data['parent_phone'])   : '';
    $guardianEmail = isset($data['guardian_email']) ? sanitize($data['guardian_email']) : '';

    if (!$id || !$name || !$department || !$guardianEmail) {
        http_response_code(400); echo json_encode(['error' => 'Missing required fields']); return;
    }
    try {
        executeQuery(
            "UPDATE students SET name=?, department=?, gender=?, phone_number=?, parent_phone=?, guardian_email=? WHERE id=?",
            [$name, $department, $gender, $phoneNumber, $parentPhone, $guardianEmail, $id]
        );
        echo json_encode(['success' => true, 'message' => 'Student updated']);
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error' => 'Failed to update: ' . $e->getMessage()]);
    }
}

function deleteStudent() {
    $id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Student ID required']); return; }
    try {
        $result = executeQuery("SELECT user_id FROM students WHERE id = ?", [$id]);
        $student = $result->fetch_assoc();
        if (!$student) { http_response_code(404); echo json_encode(['error' => 'Student not found']); return; }
        executeQuery("DELETE FROM students WHERE id = ?", [$id]);
        executeQuery("DELETE FROM users WHERE id = ?", [$student['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Student deleted']);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to delete student']); }
}

function getStudent() {
    $id = isset($_GET['id']) ? (int)sanitize($_GET['id']) : 0;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Student ID required']); return; }
    try {
        $result = executeQuery(
            "SELECT id, name, email, department, gender, phone_number, parent_phone,
                    guardian_email, face_image_path, face_descriptor
             FROM students WHERE id = ?", [$id]);
        $row = $result->fetch_assoc();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Student not found']); return; }
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch student']); }
}

function sendCredentialsEmail($to, $name, $email, $password, $role) {
    // ── SMTP CONFIG ── Edit these five values then save ──────────────────────
    // Works with Gmail, Outlook, SendGrid, Mailgun, etc.
    // For Gmail: enable 2-Step Verification → generate an App Password at
    //   https://myaccount.google.com/apppasswords  (use THAT password here)
    define('SMTP_HOST',     'smtp.gmail.com');   // smtp.office365.com / smtp.sendgrid.net
    define('SMTP_PORT',     587);                // 587 = STARTTLS  |  465 = SSL
    define('SMTP_USER',     'your-email@gmail.com');   // ← your sending address
    define('SMTP_PASS',     'your-app-password');       // ← App Password (not your login password)
    define('SMTP_FROM_NAME','Smart Attendance');
    // ─────────────────────────────────────────────────────────────────────────

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

    // ── PHPMailer via SMTP ───────────────────────────────────────────────────
    // Install once with:  composer require phpmailer/phpmailer
    // OR drop PHPMailer's src/ folder into your project as a fallback.

    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($composerAutoload)) {
        // Fallback: try a bundled copy in lib/
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
