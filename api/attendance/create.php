<?php
// api/attendance/create.php
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$enrollmentID = trim($input['EnrollmentID'] ?? '');
$date         = trim($input['Date'] ?? '');
$status       = trim($input['Status'] ?? '');

if ($enrollmentID === '' || $date === '' || $status === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Student enrollment, date, and status are required."]);
    exit();
}

try {
    // Check if attendance is already recorded for this student on this date
    $check = $conn->prepare("SELECT AttendanceID FROM attendance WHERE EnrollmentID = :eid AND Date = :dt LIMIT 1");
    $check->execute([':eid' => $enrollmentID, ':dt' => $date]);

    if ($existing = $check->fetch()) {
        $upd = $conn->prepare("UPDATE attendance SET Status = :status WHERE AttendanceID = :aid");
        $upd->execute([':status' => $status, ':aid' => $existing['AttendanceID']]);

        echo json_encode([
            "status"  => "success",
            "message" => "Attendance updated to '{$status}' ({$existing['AttendanceID']})."
        ]);
        exit();
    }

    // Generate next AttendanceID (e.g., ATT002)
    $lastStmt = $conn->query("SELECT AttendanceID FROM attendance ORDER BY AttendanceID DESC LIMIT 1");
    $lastRow = $lastStmt->fetch();
    $nextNum = $lastRow ? ((int) substr($lastRow['AttendanceID'], 3)) + 1 : 1;
    $attendanceID = "ATT" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO attendance (AttendanceID, EnrollmentID, Date, Status)
        VALUES (:aid, :eid, :dt, :status)
    ");
    $stmt->execute([
        ':aid'    => $attendanceID,
        ':eid'    => $enrollmentID,
        ':dt'     => $date,
        ':status' => $status
    ]);

    echo json_encode([
        "status"       => "success",
        "message"      => "Attendance recorded ({$attendanceID}: {$status})!",
        "AttendanceID" => $attendanceID
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>