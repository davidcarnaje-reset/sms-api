<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['email']) && isset($data['token']) && isset($data['password'])) {

    $email = $conn->real_escape_string($data['email']);
    $token = $conn->real_escape_string($data['token']);
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // 1. UNA: I-check sa USERS table (Staff/Admin)
    $check_users = $conn->query("SELECT id FROM users WHERE email = '$email' AND verification_token = '$token' AND is_verified = 0");

    if ($check_users->num_rows > 0) {
        $update = $conn->query("UPDATE users SET password = '$hashed_password', is_verified = 1, verification_token = NULL WHERE email = '$email'");
        if ($update) {
            echo json_encode(["success" => true, "message" => "Staff account verified!", "portal" => "staff"]);
            exit();
        }
    }

    // 2. PANGALAWA: Kung wala sa users, i-check sa STUDENTS table
    $check_students = $conn->query("SELECT id FROM students WHERE email = '$email' AND verification_token = '$token' AND is_verified = 0");

    if ($check_students->num_rows > 0) {
        $update = $conn->query("UPDATE students SET password = '$hashed_password', is_verified = 1, verification_token = NULL WHERE email = '$email'");
        if ($update) {
            echo json_encode(["success" => true, "message" => "Student account verified!", "portal" => "student"]);
            exit();
        }
    }

    // 3. KUNG WALA TALAGA SA KAHIT ALING TABLE
    echo json_encode(["success" => false, "message" => "Invalid link or account is already verified."]);

} else {
    echo json_encode(["success" => false, "message" => "Incomplete data."]);
}
?>