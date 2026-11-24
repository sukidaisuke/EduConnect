<?php
// api/students/portal.php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Student') {
    http_response_code(401);
    echo json_encode(["status" => "unauthorized", "message" => "Student session required."]);
    exit();
}

$studentID = $_SESSION['user']['ReferenceID'] ?? '';
if ($studentID === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No student profile linked to this account."]);
    exit();
}

try {
    // 1. Student profile + latest enrollment & section info
    $profStmt = $conn->prepare("
        SELECT st.StudentID, st.LRN, st.FirstName, st.LastName, st.Gender,
               st.GradeLevel, st.TrackStrand, st.Email,
               e.EnrollmentID, e.SchoolYear, e.Status AS EnrollmentStatus,
               sec.SectionName,
               CONCAT(t.FirstName, ' ', t.LastName) AS AdviserName
        FROM student st
        LEFT JOIN enrollment e ON st.StudentID = e.StudentID
        LEFT JOIN section sec ON e.SectionID = sec.SectionID
        LEFT JOIN teacher t ON sec.AdviserID = t.TeacherID
        WHERE st.StudentID = :sid
        ORDER BY e.EnrollmentID DESC
        LIMIT 1
    ");
    $profStmt->execute([':sid' => $studentID]);
    $profile = $profStmt->fetch();

    // 2. Semestral grades
    $grdStmt = $conn->prepare("
        SELECT g.GradeID, g.Grade, g.GradingPeriod,
               CASE WHEN g.Grade >= 75 THEN 'Passed' ELSE 'Failed' END AS Remarks,
               sub.SubjectID, sub.SubjectName, sub.SubjectType
        FROM grades g
        INNER JOIN enrollment e ON g.EnrollmentID = e.EnrollmentID
        INNER JOIN subject sub ON g.SubjectID = sub.SubjectID
        WHERE e.StudentID = :sid
        ORDER BY g.GradingPeriod ASC, sub.SubjectName ASC
    ");
    $grdStmt->execute([':sid' => $studentID]);
    $grades = $grdStmt->fetchAll();

    // 3. Attendance logs
    $attStmt = $conn->prepare("
        SELECT a.AttendanceID, a.Date, a.Status
        FROM attendance a
        INNER JOIN enrollment e ON a.EnrollmentID = e.EnrollmentID
        WHERE e.StudentID = :sid
        ORDER BY a.Date DESC, a.AttendanceID DESC
    ");
    $attStmt->execute([':sid' => $studentID]);
    $attendance = $attStmt->fetchAll();

    // 4. Payment records
    $payStmt = $conn->prepare("
        SELECT PaymentID, Amount, PaymentDate, PaymentStatus
        FROM payment
        WHERE StudentID = :sid
        ORDER BY PaymentDate DESC, PaymentID DESC
    ");
    $payStmt->execute([':sid' => $studentID]);
    $payments = $payStmt->fetchAll();

    echo json_encode([
        "status"     => "success",
        "profile"    => $profile,
        "grades"     => $grades,
        "attendance" => $attendance,
        "payments"   => $payments
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>