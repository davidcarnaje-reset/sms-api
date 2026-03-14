<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->student_id) && !empty($data->fee_id)) {
    try {
        $conn->begin_transaction();

        // 1. I-insert ang request
        $stmt = $conn->prepare("INSERT INTO service_requests (student_id, fee_id, status) VALUES (?, ?, 'Pending Payment')");
        $stmt->bind_param("si", $data->student_id, $data->fee_id);
        $stmt->execute();

        // 2. Kuhanin ang presyo mula sa fees_catalog
        $fee_query = $conn->query("SELECT amount, item_name FROM fees_catalog WHERE id = $data->fee_id");
        $fee_data = $fee_query->fetch_assoc();
        $amount = $fee_data['amount'];
        $item_name = $fee_data['item_name'];

        // 3. Gagawa ng Billing Record para makita ni Cashier
        $stmt_bill = $conn->prepare("INSERT INTO student_billing (student_id, total_amount, balance, payment_status) VALUES (?, ?, ?, 'Unpaid')");
        $stmt_bill->bind_param("sdd", $data->student_id, $amount, $amount);
        $stmt_bill->execute();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Request added! Waiting for payment."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>