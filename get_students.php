<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// Dinagdagan natin ng e.enrollment_type, e.school_year, e.prev_school, etc.
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.school_year, 
               e.prev_school,
               e.payment_plan,
               e.status as enrollment_status 
        FROM students s 
        LEFT JOIN enrollments e ON s.student_id = e.student_id 
        ORDER BY s.id DESC";

$result = $conn->query($sql);
$students = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode($students);
?>