<?php
// api/students/read.php
require_once "../config/db.php";

try {
    $stmt = $conn->query("SELECT StudentID, LRN, FirstName, LastName, Gender, GradeLevel, TrackStrand, Email FROM student ORDER BY LastName ASC");
    $students = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "count"  => count($students),
        "data"   => $students
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
?>