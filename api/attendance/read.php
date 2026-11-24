<?php
// api/attendance/read.php
require_once "../config/db.php";

try {
    $stmt = $conn->query("
        SELECT a.AttendanceID, a.Date, a.Status,
               e.EnrollmentID,
               st.StudentID, CONCAT(st.FirstName, ' ', st.LastName) AS StudentName,
               st.GradeLevel, st.TrackStrand,
               sec.SectionName
        FROM attendance a
        INNER JOIN enrollment e ON a.EnrollmentID = e.EnrollmentID
        INNER JOIN student st ON e.StudentID = st.StudentID
        INNER JOIN section sec ON e.SectionID = sec.SectionID
        ORDER BY a.Date DESC, a.AttendanceID DESC
    ");
    echo json_encode([
        "status" => "success",
        "data"   => $stmt->fetchAll()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>