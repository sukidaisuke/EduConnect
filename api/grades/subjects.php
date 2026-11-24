<?php
// api/grades/subjects.php
session_start();
require_once "../config/db.php";

try {
    $role  = $_SESSION['user']['Role'] ?? '';
    $refID = $_SESSION['user']['ReferenceID'] ?? '';

    if ($role === 'Teacher' && !empty($refID)) {
        $stmt = $conn->prepare("
            SELECT SubjectID, SubjectName, SubjectType
            FROM subject
            WHERE TeacherID = :tid
            ORDER BY SubjectName ASC
        ");
        $stmt->execute([':tid' => $refID]);
    } else {
        $stmt = $conn->query("
            SELECT SubjectID, SubjectName, SubjectType
            FROM subject
            ORDER BY SubjectName ASC
        ");
    }

    echo json_encode([
        "status" => "success",
        "data"   => $stmt->fetchAll()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>