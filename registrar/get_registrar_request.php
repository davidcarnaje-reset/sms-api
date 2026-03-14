<?php
// Query para sa table ni Registrar
$sql = "SELECT r.id, s.first_name, s.last_name, f.item_name, r.status, r.created_at 
        FROM service_requests r
        JOIN students s ON r.student_id = s.student_id
        JOIN fees_catalog f ON r.fee_id = f.id
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
echo json_encode($requests);
?>