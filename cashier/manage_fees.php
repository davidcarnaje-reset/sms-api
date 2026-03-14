<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $result = $conn->query("SELECT * FROM fees_catalog ORDER BY category ASC");
        $fees = [];
        while($row = $result->fetch_assoc()) { $fees[] = $row; }
        echo json_encode($fees);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $conn->real_escape_string($data['item_name']); // Adjusted to item_name
        $amt = (float)$data['amount'];
        $cat = $conn->real_escape_string($data['category']);
        $app = $conn->real_escape_string($data['applicable_to'] ?? 'All');

        if (isset($data['id'])) {
            $id = $data['id'];
            $sql = "UPDATE fees_catalog SET item_name='$name', amount='$amt', category='$cat', applicable_to='$app' WHERE id=$id";
        } else {
            $sql = "INSERT INTO fees_catalog (item_name, amount, category, applicable_to) VALUES ('$name', '$amt', '$cat', '$app')";
        }   

        if ($conn->query($sql)) echo json_encode(["status" => "success"]);
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $conn->query("DELETE FROM fees_catalog WHERE id=$id");
        echo json_encode(["status" => "success"]);
        break;
}
$conn->close();
?>