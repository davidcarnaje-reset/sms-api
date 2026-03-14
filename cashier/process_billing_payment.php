<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['student_id']) && isset($data['pay_amount'])) {
    $sid = $conn->real_escape_string($data['student_id']);
    $pay = (float)$data['pay_amount'];

    $conn->begin_transaction();

    try {
        // 1. UPDATE student_billing
        // Logic: paid_amount + bagong bayad, status check, at balance update
        $update_billing = "UPDATE student_billing 
                           SET paid_amount = paid_amount + $pay,
                               balance = total_amount - (paid_amount + $pay),
                               payment_status = IF((paid_amount + $pay) >= total_amount, 'Paid', 'Partial'),
                               last_payment_date = NOW()
                           WHERE student_id = '$sid'";
        $conn->query($update_billing);

        // 2. UPDATE enrollments table -> Gawing 'Enrolled'
        $update_enroll = "UPDATE enrollments SET status = 'Enrolled' WHERE student_id = '$sid'";
        $conn->query($update_enroll);

        // 3. INSERT sa payments table para sa history/receipt
        $insert_payment = "INSERT INTO payments (student_id, amount_paid, payment_method, fee_category, transaction_date) 
                           VALUES ('$sid', '$pay', 'Cash', 'Enrollment/Tuition', NOW())";
        $conn->query($insert_payment);

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Payment successful. Student is now Enrolled!"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
$conn->close();
?>