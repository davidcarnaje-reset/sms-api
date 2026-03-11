<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'config.php';

$data = json_decode(file_get_contents("php://input"));

// 1. Check kung kumpleto ang data
if (isset($data->username) && isset($data->password) && isset($data->portal)) {

    $username = $conn->real_escape_string($data->username);
    $password = $data->password;
    $portal = $data->portal; // 'admin', 'staff', o 'student'

    // ==========================================
    // PINTO NG ESTUDYANTE
    // ==========================================
    if ($portal === 'student') {
        // Titingin sa STUDENTS table (hinahanap ang student_id)
        $result = $conn->query("SELECT * FROM students WHERE student_id = '$username' AND is_verified = 1");

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                echo json_encode([
                    "success" => true,
                    "user" => [
                        "id" => $user['student_id'],
                        "username" => $user['student_id'],
                        "role" => "student",
                        "full_name" => $user['first_name'] . " " . $user['last_name'],
                        "email" => $user['email']
                    ]
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "User not found or account not yet activated."]);
        }
    }
    // ==========================================
    // PINTO NG ADMIN & STAFF (Original Logic Mo)
    // ==========================================
    else {
        // Titingin sa USERS table
        $result = $conn->query("SELECT * FROM users WHERE username = '$username'");

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $isAllowed = false;

                if ($portal === 'admin' && $user['role'] === 'admin') {
                    $isAllowed = true;
                } elseif ($portal === 'staff' && in_array($user['role'], ['registrar', 'cashier', 'teacher'])) {
                    $isAllowed = true;
                }

                if ($isAllowed) {
                    echo json_encode([
                        "success" => true,
                        "user" => [
                            "id" => $user['id'],
                            "username" => $user['username'],
                            "role" => $user['role'],
                            "full_name" => $user['full_name'],
                            "email" => $user['email']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Unauthorized: Hindi ka pwedeng mag-login dito gamit ang " . ucfirst($user['role']) . " account."
                    ]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "User not found"]);
        }
    }
} else {
    echo json_encode(["success" => false, "message" => "Incomplete login data"]);
}
?>