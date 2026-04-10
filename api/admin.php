<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['error' => 'Access denied']); exit();
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

switch ($action) {
    case 'get_stats':                  getStats();                  break;
    case 'get_monthly_attendance_rate': getMonthlyAttendanceRate(); break;
    case 'get_annual_attendance_rate':  getAnnualAttendanceRate();  break;
    case 'send_notifications':          sendNotifications();        break;
    default: http_response_code(400); echo json_encode(['error' => 'Invalid action']);
}

function getStats() {
    try {
        $studentCount  = executeQuery("SELECT COUNT(*) as count FROM students")->fetch_assoc();
        $lecturerCount = executeQuery("SELECT COUNT(*) as count FROM lecturers")->fetch_assoc();
        echo json_encode(['success' => true, 'data' => ['total_students' => $studentCount['count'], 'total_lecturers' => $lecturerCount['count']]]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to fetch stats']); }
}

function getMonthlyAttendanceRate() {
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');
    try {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));
        $result    = executeQuery("SELECT status, COUNT(*) as count FROM attendance WHERE date BETWEEN ? AND ? GROUP BY status", [$startDate, $endDate]);
        $present = $absent = $total = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'present') $present = $row['count']; else $absent = $row['count'];
            $total += $row['count'];
        }
        $rate = $total === 0 ? 0 : round(($present / $total) * 100);
        echo json_encode(['success' => true, 'data' => ['rate' => $rate, 'present' => $present, 'absent' => $absent, 'total' => $total]]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to calculate rate']); }
}

function getAnnualAttendanceRate() {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    try {
        $result = executeQuery("SELECT status, COUNT(*) as count FROM attendance WHERE date BETWEEN ? AND ? GROUP BY status", ["$year-01-01", "$year-12-31"]);
        $present = $absent = $total = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'present') $present = $row['count']; else $absent = $row['count'];
            $total += $row['count'];
        }
        $rate = $total === 0 ? 0 : round(($present / $total) * 100);
        echo json_encode(['success' => true, 'data' => ['rate' => $rate, 'present' => $present, 'absent' => $absent, 'total' => $total]]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error' => 'Failed to calculate rate']); }
}

function sendNotifications() {
    $data  = json_decode(file_get_contents("php://input"), true);
    $month = isset($data['month']) ? (int)$data['month'] : date('m');
    $year  = isset($data['year'])  ? (int)$data['year']  : date('Y');

    try {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        $students = executeQuery("SELECT id, name, guardian_email FROM students");
        $sent = 0;

        while ($student = $students->fetch_assoc()) {
            // Get summary
            $summary = executeQuery("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? AND date BETWEEN ? AND ? GROUP BY status", [$student['id'], $startDate, $endDate]);
            $present = $absent = $total = 0;
            while ($row = $summary->fetch_assoc()) {
                if ($row['status'] === 'present') $present = $row['count']; else $absent = $row['count'];
                $total += $row['count'];
            }
            $rate = $total === 0 ? 0 : round(($present / $total) * 100);

            // Get daily records
            $records = executeQuery("
                SELECT a.date, a.status, l.name as lecturer_name
                FROM attendance a
                LEFT JOIN lecturers l ON a.lecturer_id = l.id
                WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
                ORDER BY a.date ASC
            ", [$student['id'], $startDate, $endDate]);

            $rows = '';
            while ($rec = $records->fetch_assoc()) {
                $statusColor = $rec['status'] === 'present' ? '#16a34a' : '#dc2626';
                $statusText  = ucfirst($rec['status']);
                $rows .= "<tr>
                    <td style='padding:8px;border-bottom:1px solid #e2e8f0'>{$rec['date']}</td>
                    <td style='padding:8px;border-bottom:1px solid #e2e8f0'>{$rec['lecturer_name']}</td>
                    <td style='padding:8px;border-bottom:1px solid #e2e8f0;color:{$statusColor};font-weight:bold'>{$statusText}</td>
                </tr>";
            }

            if (empty($rows)) {
                $rows = "<tr><td colspan='3' style='padding:8px;text-align:center;color:#64748b'>No attendance records for this month</td></tr>";
            }

            if (!empty($student['guardian_email'])) {
                $body = "<!DOCTYPE html><html><head><style>
                    body{font-family:Arial,sans-serif;color:#333}
                    .container{max-width:600px;margin:0 auto;padding:20px}
                    .header{background:#8B1A2A;color:white;padding:20px;border-radius:8px 8px 0 0;text-align:center}
                    .content{background:#f8fafc;padding:20px;border-radius:0 0 8px 8px}
                    .stat-row{display:flex;gap:10px;margin:15px 0}
                    .stat{background:white;border-radius:8px;padding:15px;flex:1;text-align:center;border:1px solid #e2e8f0}
                    .stat-num{font-size:28px;font-weight:bold;color:#8B1A2A}
                    .stat-label{font-size:12px;color:#64748b;margin-top:4px}
                    table{width:100%;border-collapse:collapse;background:white;border-radius:8px;overflow:hidden;margin-top:15px}
                    th{background:#8B1A2A;color:white;padding:10px 8px;text-align:left;font-size:13px}
                </style></head><body>
                <div class='container'>
                    <div class='header'><h2>Attendance Report — $monthName $year</h2></div>
                    <div class='content'>
                        <p>Dear Guardian,</p>
                        <p>Please find below the attendance report for <strong>{$student['name']}</strong> for <strong>$monthName $year</strong>.</p>
                        <div class='stat-row'>
                            <div class='stat'><div class='stat-num'>$rate%</div><div class='stat-label'>Attendance Rate</div></div>
                            <div class='stat'><div class='stat-num' style='color:#16a34a'>$present</div><div class='stat-label'>Days Present</div></div>
                            <div class='stat'><div class='stat-num' style='color:#dc2626'>$absent</div><div class='stat-label'>Days Absent</div></div>
                        </div>
                        <table>
                            <thead><tr><th>Date</th><th>Lecturer</th><th>Status</th></tr></thead>
                            <tbody>$rows</tbody>
                        </table>
                        <p style='margin-top:20px'>If you have concerns, please contact the administration office.</p>
                        <p>Best regards,<br>Smart Attendance Administration</p>
                    </div>
                </div></body></html>";

                $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: noreply@smartattendance.local\r\n";
                mail($student['guardian_email'], "Attendance Report - {$student['name']} ($monthName $year)", $body, $headers);
                $sent++;
            }
        }

        echo json_encode(['success' => true, 'message' => "Notifications sent to $sent guardian(s)", 'count' => $sent]);
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error' => 'Failed to send notifications: ' . $e->getMessage()]);
    }
}
?>
