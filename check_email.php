<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
include 'config.php';

$email = isset($_GET['email']) ? $conn->real_escape_string($_GET['email']) : '';

if (!empty($email)) {
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        echo json_encode(["exists" => true]);
    } else {
        echo json_encode(["exists" => false]);
    }
}
?>