<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

// Kunin ang lahat ng students at i-join sa enrollment para makuha ang grade_level
$sql = "SELECT s.*, e.grade_level, e.status as enrollment_status 
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