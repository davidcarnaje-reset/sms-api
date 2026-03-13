<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'config.php';

// Basahin ang JSON data galing sa React
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['student_id']) || empty($data['selected_fees'])) {
    echo json_encode(["success" => false, "message" => "Missing required data. Please select fees."]);
    exit();
}

$student_id = $conn->real_escape_string($data['student_id']);
$school_year = $conn->real_escape_string($data['school_year']);
$selected_fees = $data['selected_fees'];

// Simulan ang Database Transaction
$conn->begin_transaction();

try {
    // 1. KUNIN ANG MGA DETALYE NG FEES MULA SA CATALOG
    $total_amount = 0;
    $items_to_save = [];
    $fee_ids = implode(',', array_map('intval', $selected_fees));

    $fees_query = $conn->query("SELECT id, item_name, amount FROM fees_catalog WHERE id IN ($fee_ids)");

    while ($fee = $fees_query->fetch_assoc()) {
        $total_amount += $fee['amount'];
        $items_to_save[] = $fee;
    }

    // 2. INSERT SA STUDENT_BILLING
    // Sa sms_db(10), kailangan ang total_amount, paid_amount(0), at balance (total)
    $stmt_bill = $conn->prepare("INSERT INTO student_billing (student_id, total_amount, paid_amount, balance, payment_status) VALUES (?, ?, 0, ?, 'Unpaid')");
    $stmt_bill->bind_param("sdd", $student_id, $total_amount, $total_amount);
    $stmt_bill->execute();
    $billing_id = $stmt_bill->insert_id;

    // 3. INSERT SA STUDENT_BILLING_ITEMS
    // Siguraduhin na ang 'item_label' ay naging 'item_name' na sa SQL mo
    $stmt_item = $conn->prepare("INSERT INTO student_billing_items (billing_id, fee_id, item_name, amount) VALUES (?, ?, ?, ?)");
    foreach ($items_to_save as $item) {
        $stmt_item->bind_param("iisd", $billing_id, $item['id'], $item['item_name'], $item['amount']);
        $stmt_item->execute();
    }

    // 4. UPDATE ENROLLMENT STATUS TO 'Assessed'
    // Ito ang maglilipat sa estudyante mula "Ready to Assess" tab papuntang "Assessed" tab
    $stmt_enroll = $conn->prepare("UPDATE enrollments SET status = 'Assessed' WHERE student_id = ? AND school_year = ?");
    $stmt_enroll->bind_param("ss", $student_id, $school_year);
    $stmt_enroll->execute();

    // I-commit ang lahat ng changes
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Enrollment assessed successfully. Record forwarded to Cashier.",
        "billing_id" => $billing_id
    ]);

} catch (Exception $e) {
    // Kapag may error, bawiin lahat (rollback)
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
?>