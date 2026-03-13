<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'config.php';

try {
    // 1. TOTAL STUDENTS
    $total_students_query = $conn->query("SELECT COUNT(*) as total FROM students");
    $total_students = $total_students_query->fetch_assoc()['total'];

    // 2. TOTAL OFFICIALLY ENROLLED
    $total_enrolled_query = $conn->query("SELECT COUNT(*) as total FROM enrollments WHERE status = 'Enrolled'");
    $total_enrolled = $total_enrolled_query->fetch_assoc()['total'];

    // 3. PENDING ASSESSMENT (Ready to Assess)
    $pending_registrar_query = $conn->query("SELECT COUNT(*) as total FROM enrollments WHERE status = 'Pending'");
    $pending_registrar = $pending_registrar_query->fetch_assoc()['total'];

    // 4. AWAITING PAYMENT (Assessed)
    $awaiting_payment_query = $conn->query("SELECT COUNT(*) as total FROM enrollments WHERE status = 'Assessed'");
    $awaiting_payment = $awaiting_payment_query->fetch_assoc()['total'];

    // 5. TOTAL COLLECTION
    $revenue_query = $conn->query("SELECT SUM(paid_amount) as total FROM student_billing");
    $total_revenue = $revenue_query->fetch_assoc()['total'] ?? 0;

    // 6. ENROLLMENT BY GRADE LEVEL
    $grade_stats = [];
    $grade_query = $conn->query("SELECT grade_level, COUNT(*) as count FROM enrollments GROUP BY grade_level");
    while ($row = $grade_query->fetch_assoc()) {
        $grade_stats[] = $row;
    }

    // 7. RECENT ACTIVITIES (Itinama ang column name mula date_added -> created_at)
    $recent_students = [];
    $recent_query = $conn->query("SELECT s.first_name, s.last_name, e.status, e.created_at 
                                  FROM students s 
                                  JOIN enrollments e ON s.student_id = e.student_id 
                                  ORDER BY e.id DESC LIMIT 5");

    if (!$recent_query) {
        throw new Exception($conn->error);
    }

    while ($row = $recent_query->fetch_assoc()) {
        $status_label = $row['status'];
        if ($row['status'] == 'Pending')
            $status_label = 'Ready to Assess';
        if ($row['status'] == 'Assessed')
            $status_label = 'Awaiting Cashier';

        $recent_students[] = [
            "first_name" => $row['first_name'],
            "last_name" => $row['last_name'],
            "status" => $status_label,
            "date_added" => $row['created_at'] // Ipinasa bilang date_added para sa React
        ];
    }

    echo json_encode([
        "success" => true,
        "stats" => [
            "total_students" => (int) $total_students,
            "total_enrolled" => (int) $total_enrolled,
            "pending_registrar" => (int) $pending_registrar,
            "awaiting_payment" => (int) $awaiting_payment,
            "total_revenue" => (float) $total_revenue
        ],
        "grade_distribution" => $grade_stats,
        "recent_activities" => $recent_students
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>