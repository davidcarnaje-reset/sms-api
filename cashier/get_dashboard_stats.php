<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config.php';

// 1. Total Collections Today (Daily Sum)
$sql_total = "SELECT SUM(amount_paid) as total FROM payments WHERE DATE(transaction_date) = CURDATE()";
$result_total = $conn->query($sql_total);
$total_today = $result_total->fetch_assoc()['total'] ?? 0;

// 2. Count Today's Transactions
$sql_count = "SELECT COUNT(*) as count FROM payments WHERE DATE(transaction_date) = CURDATE()";
$result_count = $conn->query($sql_count);
$today_count = $result_count->fetch_assoc()['count'] ?? 0;

// 3. Breakdown per Method (Today Only)
$methods = ['Cash', 'GCash', 'Card'];
$breakdown = [];
foreach ($methods as $m) {
    $sql_m = "SELECT SUM(amount_paid) as total FROM payments 
              WHERE payment_method = '$m' AND DATE(transaction_date) = CURDATE()";
    $res_m = $conn->query($sql_m);
    $breakdown[$m] = $res_m->fetch_assoc()['total'] ?? 0;
}

echo json_encode([
    "totalCollections" => "₱" . number_format($total_today, 2),
    "todayTransactions" => $today_count,
    "pendingPayments" => 0, // Placeholder
    "breakdown" => $breakdown
]);
$conn->close();
?>