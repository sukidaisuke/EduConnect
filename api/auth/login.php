<?php
// api/auth/login.php
session_start();
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please enter both username and password."]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT UserID, Username, PasswordHash, Role, ReferenceID FROM users WHERE Username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['PasswordHash'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
        exit();
    }

    // Resolve display name based on role & ReferenceID
    $fullName = $user['Username'];
    if ($user['Role'] === 'Student' && !empty($user['ReferenceID'])) {
        $sStmt = $conn->prepare("SELECT FirstName, LastName FROM student WHERE StudentID = :id LIMIT 1");
        $sStmt->execute([':id' => $user['ReferenceID']]);
        if ($row = $sStmt->fetch()) {
            $fullName = $row['FirstName'] . ' ' . $row['LastName'];
        }
    } elseif ($user['Role'] === 'Teacher' && !empty($user['ReferenceID'])) {
        $tStmt = $conn->prepare("SELECT FirstName, LastName FROM teacher WHERE TeacherID = :id LIMIT 1");
        $tStmt->execute([':id' => $user['ReferenceID']]);
        if ($row = $tStmt->fetch()) {
            $fullName = $row['FirstName'] . ' ' . $row['LastName'];
        }
    } else {
        $fullName = $user['Role'] . " Office";
    }

    // Map each role to its dashboard file
    $roleRoutes = [
        'Administrator' => 'dashboards/admin.html',
        'Registrar'     => 'dashboards/registrar.html',
        'Teacher'       => 'dashboards/teacher.html',
        'Cashier'       => 'dashboards/cashier.html',
        'Student'       => 'dashboards/student.html'
    ];

    $_SESSION['user'] = [
        'UserID'      => $user['UserID'],
        'Username'    => $user['Username'],
        'Role'        => $user['Role'],
        'ReferenceID' => $user['ReferenceID'],
        'FullName'    => $fullName
    ];

    echo json_encode([
        "status"   => "success",
        "message"  => "Login successful.",
        "user"     => $_SESSION['user'],
        "redirect" => $roleRoutes[$user['Role']] ?? 'index.html'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>