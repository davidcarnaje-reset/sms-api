<?php
error_reporting(0);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 1. HARANGAN ANG CORS PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// 2. I-check kung kumpleto ang data galing sa React
if (isset($data['email']) && isset($data['token']) && isset($data['password'])) {

    $email = $conn->real_escape_string($data['email']);
    $token = $conn->real_escape_string($data['token']);
    $new_password = $data['password'];

    // 3. Hanapin sa database ang user na may tamang email at reset_token
    $sql = "SELECT id FROM users WHERE email = '$email' AND reset_token = '$token'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        // 4. I-encrypt (hash) ang bagong password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 5. I-update ang password at BURAHIN ang reset_token (One-Time Use security)
        $update_sql = "UPDATE users SET 
                        password = '$hashed_password', 
                        reset_token = NULL 
                      WHERE email = '$email'";

        if ($conn->query($update_sql)) {
            ob_clean();
            echo json_encode(["success" => true, "message" => "Password successfully reset."]);
        } else {
            ob_clean();
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }

    } else {
        // Kapag mali ang token, o nagamit na ang link dati pa
        ob_clean();
        echo json_encode(["success" => false, "message" => "Invalid link. This reset link has expired or already been used."]);
    }

} else {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Incomplete data. Please request a new link."]);
}
?>