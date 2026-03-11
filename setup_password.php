<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// 1. Siguraduhing kumpleto ang data na pinasa ng React
if (isset($data['email']) && isset($data['token']) && isset($data['password'])) {

    $email = $conn->real_escape_string($data['email']);
    $token = $conn->real_escape_string($data['token']);
    $raw_password = $data['password'];

    // 2. Hanapin sa database ang user na may tamang email at token, at hindi pa verified (0)
    $sql = "SELECT id FROM users WHERE email = '$email' AND verification_token = '$token' AND is_verified = 0";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        // 3. I-encrypt (hash) ang bagong password para secured
        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

        // 4. I-update ang record: I-save ang password, gawing verified (1), at burahin ang token
        $update_sql = "UPDATE users SET 
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
        // Kapag mali ang token, o na-verify na ang account dati pa
        echo json_encode(["success" => false, "message" => "Invalid link or account is already verified."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Incomplete data. Please use the link from your email."]);
}
?>