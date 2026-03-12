<?php
error_reporting(0);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'config.php';

// Check kung may student_id (ito ang unique key natin)
if (empty($_POST['student_id'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Student ID is missing."]);
    exit();
}

$student_id = $conn->real_escape_string($_POST['student_id']);
$email = $conn->real_escape_string($_POST['email']);
$contact_no = $conn->real_escape_string($_POST['contact_no']);
$address = $conn->real_escape_string($_POST['address']);

$image_sql = "";

// 1. IMAGE UPLOAD LOGIC
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $target_dir = "uploads/profiles/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    // Pinapangalanan natin base sa student_id + timestamp para iwas cache
    $new_filename = $student_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $image_sql = ", profile_image = '$new_filename'";
    }
}

// 2. DATABASE UPDATE
$conn->begin_transaction();

try {
    // I-uupdate natin ang mobile_no at address_house (o guardian_address)
    // base sa fields na ginamit mo sa add student.
    $sql = "UPDATE students SET 
                email = '$email', 
                mobile_no = '$contact_no', 
                address_house = '$address' 
                $image_sql 
            WHERE student_id = '$student_id'";

    if ($conn->query($sql)) {
        $conn->commit();
        ob_clean();
        echo json_encode(["success" => true, "message" => "Student records updated!"]);
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(["success" => false, "message" => "Update Failed: " . $e->getMessage()]);
}

$conn->close();
?>