<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'config.php';

$data = json_decode(file_get_contents("php://input"));

// 1. Check kung kumpleto ang data (Username, Password, at Portal)
if (isset($data->username) && isset($data->password) && isset($data->portal)) {

    $username = $conn->real_escape_string($data->username);
    $password = $data->password;
    $portal = $data->portal; // 'admin', 'staff', o 'student'

    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. I-check kung tama ang password
        if (password_verify($password, $user['password'])) {

            // --- 3. THE BOUNCER LOGIC (Security Layer) ---
            $isAllowed = false;

            if ($portal === 'admin' && $user['role'] === 'admin') {
                $isAllowed = true;
            } elseif ($portal === 'staff' && in_array($user['role'], ['registrar', 'cashier', 'teacher'])) {
                $isAllowed = true;
            } elseif ($portal === 'student' && $user['role'] === 'student') {
                $isAllowed = true;
            }

            if ($isAllowed) {
                // Pag allowed, i-send ang user data (exclude password for safety)
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
                // Kung tama ang password pero maling portal
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
} else {
    echo json_encode(["success" => false, "message" => "Incomplete login data"]);
}
?>