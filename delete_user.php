<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->id)) {
    $id = $conn->real_escape_string($data->id);

    // SQL query para burahin ang user base sa ID
    $sql = "DELETE FROM users WHERE id = '$id'";

    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "User deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "ID not provided"]);
}
?>