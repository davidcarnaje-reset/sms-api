<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

$search = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if ($search) {
    // 1. Kunin ang Student Info at Billing Summary
    $sql_billing = "SELECT b.*, s.first_name, s.last_name 
                    FROM student_billing b
                    JOIN students s ON b.student_id = s.student_id
                    WHERE b.student_id = '$search' LIMIT 1";
    
    $res_billing = $conn->query($sql_billing);

    if ($res_billing->num_rows > 0) {
        $billing = $res_billing->fetch_assoc();
        $billing_id = $billing['id'];

        // 2. Kunin ang mga items na nilagay/inapprove ni Registrar
        $sql_items = "SELECT item_name, amount FROM student_billing_items WHERE billing_id = '$billing_id'";
        $res_items = $conn->query($sql_items);
        $items = [];
        while($item = $res_items->fetch_assoc()) { 
            $items[] = $item; 
        }

        echo json_encode([
            "status" => "success",
            "summary" => $billing,
            "items" => $items
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Student not found or no billing record."]);
    }
}
$conn->close();
?>