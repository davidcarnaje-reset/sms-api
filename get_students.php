<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// 1. SQL Query para sa main Student Data (Billing at Enrollment)
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.section,
               e.school_year, 
               e.payment_plan, 
               e.status as enrollment_status,
               b.id as billing_id, -- Kailangan ito para sa mapping ng items
               b.payment_status,
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
        $students[] = $row;
    }
}

// 2. SQL Query para sa Student Billing Items
// Kinukuha natin ang item_name at amount base sa billing_id
$itemSql = "SELECT billing_id, item_name, amount FROM student_billing_items";
$itemResult = $conn->query($itemSql);
$billingItems = [];

if ($itemResult && $itemResult->num_rows > 0) {
    while ($itemRow = $itemResult->fetch_assoc()) {
        $billingItems[] = $itemRow;
    }
}

// 3. Pagsamahin sa isang JSON response
echo json_encode([
    "students" => $students,
    "billing_items" => $billingItems
]);
?>