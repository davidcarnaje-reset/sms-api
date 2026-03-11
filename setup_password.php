<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['email']) && isset($data['token']) && isset($data['password'])) {

    $email = $conn->real_escape_string($data['email']);
    $token = $conn->real_escape_string($data['token']);
    $raw_password = $data['password'];

    // 1. PINALITAN: students table dapat, hindi users!
    $sql = "SELECT id FROM students WHERE email = '$email' AND verification_token = '$token' AND is_verified = 0";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

        // 2. PINALITAN: students table din dapat dito!
        $update_sql = "UPDATE students SET 
                        password = '$hashed_password', 
                        is_verified = 1, 
                        verification_token = NULL 
                      WHERE email = '$email'";

        if ($conn->query($update_sql)) {
            echo json_encode(["success" => true, "message" => "Account successfully verified and password saved."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }

    } else {
        echo json_encode(["success" => false, "message" => "Invalid link or account is already verified."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Incomplete data. Please use the link from your email."]);
}
?>