<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Payagan ang React na kumonekta sa PHP natin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'config.php';

// Kukunin natin lahat ng fees at i-so-sort base sa Category para maayos tingnan sa Frontend
$sql = "SELECT id, item_name, amount, category FROM fees_catalog ORDER BY category ASC, item_name ASC";
$result = $conn->query($sql);

$fees = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // I-format ang amount para siguradong number at may decimal (e.g., 15000.00)
        $row['amount'] = number_format((float) $row['amount'], 2, '.', '');
        $fees[] = $row;
    }
    // Ibalik ang data papuntang React bilang JSON
    echo json_encode($fees);
} else {
    // Kung may error sa query
    echo json_encode(["success" => false, "message" => "Database Error: " . $conn->error]);
}

$conn->close();
?>