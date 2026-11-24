<?php
// api/enrollment/sections.php
require_once "../config/db.php";

try {
    $stmt = $conn->query("
        SELECT s.SectionID, s.SectionName, s.GradeLevel, s.TrackStrand,
               CONCAT(t.FirstName, ' ', t.LastName) AS AdviserName
        FROM section s
        LEFT JOIN teacher t ON s.AdviserID = t.TeacherID
        ORDER BY s.GradeLevel ASC, s.SectionName ASC
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