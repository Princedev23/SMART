<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

switch ($action) {
    case 'record':
        recordAttendance();
        break;
    case 'get_by_date':
        getAttendanceByDate();
        break;
    case 'get_by_student':
        getAttendanceByStudent();
        break;
    case 'update':
        updateAttendance();
        break;
    case 'delete':
        deleteAttendance();
        break;
    case 'get_lecturer_attendance':
        getLecturerAttendance();
        break;
    case 'get_student_attendance':
        getStudentAttendance();
        break;
    case 'get_monthly_stats':
        getMonthlyStats();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function recordAttendance() {
    if ($_SESSION['role'] !== 'lecturer') {
        http_response_code(403);
        echo json_encode(['error' => 'Only lecturers can record attendance']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $studentId = isset($data['student_id']) ? (int)$data['student_id'] : 0;
    $date = isset($data['date']) ? sanitize($data['date']) : date('Y-m-d');
    $status = isset($data['status']) ? sanitize($data['status']) : 'present';
    $confidence = isset($data['confidence_score']) ? (float)$data['confidence_score'] : 0.0;

    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID required']);
        return;
    }

    try {
        $result = executeQuery(
            "SELECT id FROM lecturers WHERE user_id = ?",
            [$_SESSION['user_id']]
        );

        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Lecturer profile not found']);
            return;
        }

        $lecturer = $result->fetch_assoc();
        $lecturerId = $lecturer['id'];

        $existing = executeQuery(
            "SELECT id FROM attendance WHERE student_id = ? AND lecturer_id = ? AND date = ?",
            [$studentId, $lecturerId, $date]
        );

        if ($existing->num_rows > 0) {
            $attendanceRecord = $existing->fetch_assoc();
            executeQuery(
                "UPDATE attendance SET status = ?, confidence_score = ? WHERE id = ?",
                [$status, $confidence, $attendanceRecord['id']]
            );
            echo json_encode(['success' => true, 'message' => 'Attendance updated']);
        } else {
            executeQuery(
                "INSERT INTO attendance (student_id, lecturer_id, date, time, status, confidence_score)
                 VALUES (?, ?, ?, NOW(), ?, ?)",
                [$studentId, $lecturerId, $date, $status, $confidence]
            );
            echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to record attendance']);
    }
}

function getAttendanceByDate() {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    $date = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');

    try {
        $result = executeQuery("
            SELECT a.id, a.date, a.status, a.confidence_score,
                   s.name as student_name, l.name as lecturer_name
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN lecturers l ON a.lecturer_id = l.id
            WHERE a.date = ?
            ORDER BY a.date DESC
        ", [$date]);

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function getAttendanceByStudent() {
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID required']);
        return;
    }

    try {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $result = executeQuery("
            SELECT a.id, a.date, a.status, a.confidence_score,
                   l.name as lecturer_name, l.courses_teaching
            FROM attendance a
            JOIN lecturers l ON a.lecturer_id = l.id
            WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
            ORDER BY a.date DESC
        ", [$studentId, $startDate, $endDate]);

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $row['courses_teaching'] = json_decode($row['courses_teaching'], true) ?? [];
            $records[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function updateAttendance() {
    if ($_SESSION['role'] !== 'lecturer') {
        http_response_code(403);
        echo json_encode(['error' => 'Only lecturers can update attendance']);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $status = isset($data['status']) ? sanitize($data['status']) : '';

    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'ID and status required']);
        return;
    }

    try {
        $result = executeQuery("
            SELECT a.lecturer_id FROM attendance a WHERE a.id = ?
        ", [$id]);

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Attendance record not found']);
            return;
        }

        $record = $result->fetch_assoc();

        $lecturer = executeQuery(
            "SELECT id FROM lecturers WHERE user_id = ?",
            [$_SESSION['user_id']]
        );

        if ($lecturer->num_rows === 0 || $lecturer->fetch_assoc()['id'] != $record['lecturer_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot update this record']);
            return;
        }

        executeQuery(
            "UPDATE attendance SET status = ? WHERE id = ?",
            [$status, $id]
        );

        echo json_encode(['success' => true, 'message' => 'Attendance updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update attendance']);
    }
}

function deleteAttendance() {
    if ($_SESSION['role'] !== 'lecturer') {
        http_response_code(403);
        echo json_encode(['error' => 'Only lecturers can delete attendance']);
        return;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Attendance ID required']);
        return;
    }

    try {
        $result = executeQuery(
            "SELECT lecturer_id FROM attendance WHERE id = ?",
            [$id]
        );

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Attendance record not found']);
            return;
        }

        $record = $result->fetch_assoc();

        $lecturer = executeQuery(
            "SELECT id FROM lecturers WHERE user_id = ?",
            [$_SESSION['user_id']]
        );

        if ($lecturer->num_rows === 0 || $lecturer->fetch_assoc()['id'] != $record['lecturer_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete this record']);
            return;
        }

        executeQuery("DELETE FROM attendance WHERE id = ?", [$id]);

        echo json_encode(['success' => true, 'message' => 'Attendance deleted']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete attendance']);
    }
}

function getLecturerAttendance() {
    if ($_SESSION['role'] !== 'lecturer') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    try {
        $result = executeQuery("
            SELECT l.id FROM lecturers WHERE user_id = ?
        ", [$_SESSION['user_id']]);

        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Lecturer profile not found']);
            return;
        }

        $lecturer = $result->fetch_assoc();

        $records = executeQuery("
            SELECT a.id, a.date, a.status,
                   s.name as student_name, s.id as student_id
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            WHERE a.lecturer_id = ?
            ORDER BY a.date DESC
            LIMIT 50
        ", [$lecturer['id']]);

        $data = [];
        while ($row = $records->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function getStudentAttendance() {
    if ($_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    try {
        $result = executeQuery("
            SELECT s.id FROM students WHERE user_id = ?
        ", [$_SESSION['user_id']]);

        if ($result->num_rows === 0) {
            // Student profile not found - return empty data
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        $student = $result->fetch_assoc();
        $studentId = $student['id'];

        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $records = executeQuery("
            SELECT a.id, a.date, a.status,
                   l.name as lecturer_name, l.courses_teaching
            FROM attendance a
            JOIN lecturers l ON a.lecturer_id = l.id
            WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
            ORDER BY a.date DESC
        ", [$studentId, $startDate, $endDate]);

        $data = [];
        while ($row = $records->fetch_assoc()) {
            $row['courses_teaching'] = json_decode($row['courses_teaching'], true) ?? [];
            $data[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function getMonthlyStats() {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    try {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $result = executeQuery("
            SELECT a.id, a.date, a.status, a.confidence_score,
                   s.name as student_name, l.name as lecturer_name
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN lecturers l ON a.lecturer_id = l.id
            WHERE a.date BETWEEN ? AND ?
            ORDER BY a.date DESC
        ", [$startDate, $endDate]);

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'data' => []]);
    }
}

?>
