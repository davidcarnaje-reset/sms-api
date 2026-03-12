<?php
header("Access-Control-Allow-Origin: http://localhost:5173"); // Payagan ang React port
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Payagan ang POST at OPTIONS
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Gamitin ang existing config.php
include_once '../config.php'; 

// Dahil ang config.php mo ay mayroon nang headers at $conn variable:

// Basahin ang JSON data galing sa React
$data = json_decode(file_get_contents("php://input"));

if(!empty($data->studentId) && !empty($data->amount)) {
    
    // Sanitize data (Importante sa mysqli para iwas SQL Injection)
    $student_id = $conn->real_escape_string($data->studentId);
    $amount = $conn->real_escape_string($data->amount);
    $category = $conn->real_escape_string($data->category);
    $method = $conn->real_escape_string($data->method);

    // SQL Query gamit ang $conn mula sa config.php
    $sql = "INSERT INTO payments (student_id, amount_paid, fee_category, payment_method) 
            VALUES ('$student_id', '$amount', '$category', '$method')";

    if($conn->query($sql) === TRUE) {
        http_response_code(201);
        echo json_encode(array("message" => "Payment successfully recorded."));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $conn->error));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}

$conn->close();
?>