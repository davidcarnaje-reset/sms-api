<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

if ($search) {
    // Nag-join tayo sa students table para makuha ang pangalan habang tinitignan ang billing
    $sql = "SELECT b.*, s.first_name, s.last_name 
            FROM student_billing b
            JOIN students s ON b.student_id = s.student_id
            WHERE b.student_id = '$search' OR s.last_name LIKE '%$search%'
            LIMIT 1";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            "status" => "success",
            "data" => [
                "id" => $row['student_id'],
                "name" => $row['first_name'] . " " . $row['last_name'],
                "total" => $row['total_amount'],
                "paid" => $row['paid_amount'],
                "balance" => $row['balance'],
                "status" => $row['payment_status'],
                "last_pay" => $row['last_payment_date']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No billing record found."]);
    }
}
$conn->close();
?>