<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// In-adjust ang columns base sa iyong student_billing table
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.school_year, 
               e.prev_school,
               e.payment_plan,
               e.status as enrollment_status,
               b.payment_status,
               b.total_amount,
               b.paid_amount,
               b.balance,
               b.last_payment_date
        FROM students s 
        LEFT JOIN enrollments e ON s.student_id = e.student_id 
        LEFT JOIN student_billing b ON s.student_id = b.student_id
        ORDER BY s.id DESC";

$result = $conn->query($sql);
$students = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode($students);
?>