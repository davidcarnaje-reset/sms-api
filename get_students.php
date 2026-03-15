<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

/**
 * SQL Query Logic:
 * Ginagamit natin ang LEFT JOIN sa student_billing_items para hanapin 
 * ang row na ang item_name ay 'Tuition Fee'.
 * Gagamit tayo ng SUM(CASE...) para makuha ang specific amount nito.
 */
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.section,
               e.school_year, 
               e.payment_plan, 
               e.scholarship_type,
               e.status as enrollment_status,
               b.id as billing_id, 
               b.payment_status as current_payment_status,
               b.total_amount,
               b.paid_amount,
               b.balance,
               b.last_payment_date,
               -- KUKUNIN LANG NATIN ANG TUITION FEE ITEM AMOUNT --
               (SELECT SUM(amount) FROM student_billing_items 
                WHERE billing_id = b.id AND item_name LIKE '%Tuition%') as tuition_only_amount
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
        $row['payment_status'] = $row['current_payment_status']; 
        
        // Siguraduhing float ang values para sa calculation sa frontend
        $row['paid_amount'] = (float)($row['paid_amount'] ?? 0);
        $row['tuition_only_amount'] = (float)($row['tuition_only_amount'] ?? 0);
        
        $students[] = $row;
    }
}

// Para sa debugging o iba pang features, kuhanin pa rin natin ang lahat ng billing items
$itemSql = "SELECT billing_id, item_name, amount FROM student_billing_items";
$itemResult = $conn->query($itemSql);
$billingItems = [];

if ($itemResult && $itemResult->num_rows > 0) {
    while ($itemRow = $itemResult->fetch_assoc()) {
        $billingItems[] = $itemRow;
    }
}

echo json_encode([
    "success" => true,
    "students" => $students,
    "billing_items" => $billingItems
]);
?>