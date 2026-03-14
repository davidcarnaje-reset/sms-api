<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

$sql = "SELECT 
            payment_id as id, 
            student_id as student, 
            amount_paid as amount, 
            fee_category as type, 
            payment_method as method,
            DATE_FORMAT(transaction_date, '%b %d, %Y - %h:%i %p') as date 
        FROM payments 
        ORDER BY transaction_date DESC"; // WALANG LIMIT DITO

$result = $conn->query($sql);
$payments = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        array_push($payments, $row);
    }
    echo json_encode($payments);
} else {
    echo json_encode(array());
}
$conn->close();
?>