<?php
// api/payments/read.php
require_once "../config/db.php";

try {
    $stmt =$conn->query("
        SELECT p.PaymentID, p.Amount, p.PaymentDate, p.PaymentStatus,
               st.StudentID, st.LRN,
               CONCAT(st.FirstName, ' ', st.LastName) AS StudentName,
               st.GradeLevel, st.TrackStrand
        FROM payment p
        INNER JOIN student st ON p.StudentID = st.StudentID
        ORDER BY p.PaymentDate DESC, p.PaymentID DESC
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