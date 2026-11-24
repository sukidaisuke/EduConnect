<?php
// api/enrollment/create.php
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$studentID  = trim($input['StudentID'] ?? '');
$sectionID  = trim($input['SectionID'] ?? '');
$schoolYear = trim($input['SchoolYear'] ?? '2026-2027');

if ($studentID === '' || $sectionID === '' || $schoolYear === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Student, Section, and School Year are required."]);
    exit();
}

try {
    // Prevent duplicate enrollment for the same student in the same school year
    $check = $conn->prepare("SELECT EnrollmentID FROM enrollment WHERE StudentID = :sid AND SchoolYear = :sy LIMIT 1");
    $check->execute([':sid' => $studentID, ':sy' => $schoolYear]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "This student is already enrolled for SY {$schoolYear}."]);
        exit();
    }

    // Generate next EnrollmentID (e.g., ENR002)
    $lastStmt = $conn->query("SELECT EnrollmentID FROM enrollment ORDER BY EnrollmentID DESC LIMIT 1");
    $lastRow = $lastStmt->fetch();
    $nextNum = $lastRow ? ((int) substr($lastRow['EnrollmentID'], 3)) + 1 : 1;
    $enrollmentID = "ENR" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO enrollment (EnrollmentID, StudentID, SectionID, SchoolYear, Status)
        VALUES (:eid, :sid, :secid, :sy, 'Enrolled')
    ");
    $stmt->execute([
        ':eid'   => $enrollmentID,
        ':sid'   => $studentID,
        ':secid' => $sectionID,
        ':sy'    => $schoolYear
    ]);

    echo json_encode([
        "status"       => "success",
        "message"      => "Student successfully enrolled ({$enrollmentID})!",
        "EnrollmentID" => $enrollmentID
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>