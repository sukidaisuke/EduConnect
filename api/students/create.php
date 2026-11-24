<?php
// api/students/create.php
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

$lrn         = trim($input['LRN'] ?? '');
$firstName   = trim($input['FirstName'] ?? '');
$lastName    = trim($input['LastName'] ?? '');
$gender      = trim($input['Gender'] ?? '');
$gradeLevel  = trim($input['GradeLevel'] ?? '');
$trackStrand = trim($input['TrackStrand'] ?? '');
$email       = trim($input['Email'] ?? '');

if ($lrn === '' || $firstName === '' || $lastName === '' || $gender === '' || $gradeLevel === '' || $trackStrand === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please fill in all required student fields."]);
    exit();
}

try {
    $conn->beginTransaction();

    // Generate next StudentID (e.g., STU003)
    $lastStmt = $conn->query("SELECT StudentID FROM student ORDER BY StudentID DESC LIMIT 1");
    $lastRow = $lastStmt->fetch();
    $nextNum = $lastRow ? ((int) substr($lastRow['StudentID'], 3)) + 1 : 1;
    $studentID = "STU" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);

    // Default school email if left blank
    if ($email === '') {
        $slug = strtolower(substr($firstName, 0, 1) . "." . preg_replace('/\s+/', '', $lastName));
        $email = $slug . "@lunhs.edu.ph";
    }

    $stmt = $conn->prepare("
        INSERT INTO student (StudentID, LRN, FirstName, LastName, Gender, GradeLevel, TrackStrand, Email)
        VALUES (:id, :lrn, :fname, :lname, :gender, :grade, :strand, :email)
    ");
    $stmt->execute([
        ':id'     => $studentID,
        ':lrn'    => $lrn,
        ':fname'  => $firstName,
        ':lname'  => $lastName,
        ':gender' => $gender,
        ':grade'  => $gradeLevel,
        ':strand' => $trackStrand,
        ':email'  => $email
    ]);

    // Also create a Student user account in users table
    $uLastStmt = $conn->query("SELECT UserID FROM users ORDER BY UserID DESC LIMIT 1");
    $uLastRow = $uLastStmt->fetch();
    $uNextNum = $uLastRow ? ((int) substr($uLastRow['UserID'], 3)) + 1 : 1;
    $userID = "USR" . str_pad($uNextNum, 3, "0", STR_PAD_LEFT);

    $username = strtolower(substr($firstName, 0, 1) . "." . preg_replace('/\s+/', '', $lastName)) . $nextNum;
    $defaultHash = password_hash("password", PASSWORD_DEFAULT);

    $uStmt = $conn->prepare("
        INSERT INTO users (UserID, Username, PasswordHash, Role, ReferenceID)
        VALUES (:uid, :uname, :hash, 'Student', :ref)
    ");
    $uStmt->execute([
        ':uid'   => $userID,
        ':uname' => $username,
        ':hash'  => $defaultHash,
        ':ref'   => $studentID
    ]);

    $conn->commit();

    echo json_encode([
        "status"    => "success",
        "message"   => "Student {$firstName} {$lastName} ({$studentID}) registered! Portal username: {$username}",
        "StudentID" => $studentID
    ]);
} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Registration failed (check duplicate LRN/Email): " . $e->getMessage()]);
}
?>