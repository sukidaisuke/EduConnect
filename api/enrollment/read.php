<?php
// api/enrollment/read.php
require_once "../config/db.php";

try {
    $stmt = $conn->query("
        SELECT e.EnrollmentID, e.SchoolYear, e.Status,
               st.StudentID, st.LRN, CONCAT(st.FirstName, ' ', st.LastName) AS StudentName,
               st.GradeLevel, st.TrackStrand,
               sec.SectionID, sec.SectionName
        FROM enrollment e
        INNER JOIN student st ON e.StudentID = st.StudentID
        INNER JOIN section sec ON e.SectionID = sec.SectionID
        ORDER BY e.EnrollmentID DESC
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