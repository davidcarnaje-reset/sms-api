<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'config.php';

/**
 * Ginagamit natin ang GROUP BY s.student_id para hindi mag-duplicate ang display.
 * Gumagamit din tayo ng MAX(e.grade_level) etc. para makuha ang pinakahuling data 
 * kung sakaling may multiple enrollment records ang student.
 */
$sql = "SELECT s.*, 
               e.grade_level, 
               e.enrollment_type, 
               e.school_year, 
               e.status as enrollment_status,
               b.payment_status,
               b.total_amount,
               b.paid_amount,
               b.balance
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

echo json_encode($students);
?>