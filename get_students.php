<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// SQL Query na may malinaw na mapping para sa Gatekeeper logic
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.section,
               e.school_year, 
               e.payment_plan, 
               e.status as enrollment_status,
               b.id as billing_id, 
               b.payment_status as current_payment_status, -- Binigyan ng alias para sigurado
               b.total_amount,
               b.paid_amount,
               b.balance,
               b.last_payment_date 
        FROM students s 
        LEFT JOIN enrollments e ON s.student_id = e.student_id 
        LEFT JOIN student_billing b ON s.student_id = b.student_id
        WHERE e.id = (
            SELECT MAX(id) FROM enrollments WHERE student_id = s.student_id
        ) OR e.id IS NULL
        GROUP BY s.student_id
        ORDER BY s.id DESC";

$result = $conn->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // I-map ang 'current_payment_status' para madaling basahin ng React
        $row['payment_status'] = $row['current_payment_status']; 
        $students[] = $row;
    }
}

$itemSql = "SELECT billing_id, item_name, amount FROM student_billing_items";
$itemResult = $conn->query($itemSql);
$billingItems = [];

if ($itemResult && $itemResult->num_rows > 0) {
    while ($itemRow = $itemResult->fetch_assoc()) {
        $billingItems[] = $itemRow;
    }
}

echo json_encode([
    "students" => $students,
    "billing_items" => $billingItems
]);
?>