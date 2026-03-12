<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config.php';

// SQL query para makuha ang pinakabagong transactions (descending order)
$sql = "SELECT payment_id as id, student_id as student, amount_paid as amount, fee_category as type, transaction_date as date 
        FROM payments 
        ORDER BY transaction_date DESC 
        LIMIT 10";

$result = $conn->query($sql);

$payments = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Gawin nating "Paid" ang default status dahil pumasok na sa record
        $row['status'] = "Paid";
        array_push($payments, $row);
    }
    http_response_code(200);
    echo json_encode($payments);
} else {
    echo json_encode(array());
}

$conn->close();
?>