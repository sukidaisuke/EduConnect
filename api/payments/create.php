<?php
// api/payments/create.php
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$studentID     = trim($input['StudentID'] ?? '');
$amount        = $input['Amount'] ?? '';
$paymentDate   = trim($input['PaymentDate'] ?? '');
$paymentStatus = trim($input['PaymentStatus'] ?? '');

if ($studentID === '' || $amount === '' || $paymentDate === '' || $paymentStatus === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All payment fields are required."]);
    exit();
}

$numericAmount = (float) $amount;
if ($numericAmount <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Payment amount must be greater than zero."]);
    exit();
}

try {
    // Generate next PaymentID (e.g., PAY002)
    $lastStmt = $conn->query("SELECT PaymentID FROM payment ORDER BY PaymentID DESC LIMIT 1");
    $lastRow = $lastStmt->fetch();
    $nextNum = $lastRow ? ((int) substr($lastRow['PaymentID'], 3)) + 1 : 1;
    $paymentID = "PAY" . str_pad($nextNum, 3, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO payment (PaymentID, StudentID, Amount, PaymentDate, PaymentStatus)
        VALUES (:pid, :sid, :amt, :pdate, :pstatus)
    ");
    $stmt->execute([
        ':pid'     => $paymentID,
        ':sid'     => $studentID,
        ':amt'     => $numericAmount,
        ':pdate'   => $paymentDate,
        ':pstatus' => $paymentStatus
    ]);

    echo json_encode([
        "status"    => "success",
        "message"   => "Payment recorded ({$paymentID}: ₱" . number_format($numericAmount, 2) . " - {$paymentStatus})!",
        "PaymentID" => $paymentID
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>