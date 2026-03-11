<?php
include 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->id)) {
    $id = $conn->real_escape_string($data->id);
    $full_name = $conn->real_escape_string($data->full_name);
    $username = $conn->real_escape_string($data->username);
    $role = $conn->real_escape_string($data->role);

    // Basic Update Query
    $query = "UPDATE users SET full_name='$full_name', username='$username', role='$role'";

    // Kung may nilagay na bagong password, i-hash at isama sa update
    if (!empty($data->password)) {
        $password = password_hash($data->password, PASSWORD_BCRYPT);
        $query .= ", password='$password'";
    }

    $query .= " WHERE id='$id'";

    if ($conn->query($query)) {
        echo json_encode(["success" => true, "message" => "User updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
}
?>