<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// Siguraduhing kasama ang 'is_verified' sa kukunin natin sa database
$sql = "SELECT id, username, first_name, middle_name, last_name, full_name, email, phone_number, role, is_verified FROM users ORDER BY id DESC";
$result = $conn->query($sql);

$users = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
?>