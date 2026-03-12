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

// Tandaan: Gagamit tayo ng $_POST dahil ang React ay nagpapadala ng FormData (para sa image)
if (empty($_POST['student_id'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Student ID is required."]);
    exit();
}

$student_id = $conn->real_escape_string($_POST['student_id']);
$email = $conn->real_escape_string($_POST['email']);
$contact_no = $conn->real_escape_string($_POST['contact_no']);
$address = $conn->real_escape_string($_POST['address']);

$profile_image_query = "";

// 1. HANDLE PROFILE IMAGE UPLOAD (Kung may pinadalang file)
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $target_dir = "uploads/profiles/";
    
    // Siguraduhing existing ang folder
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    // Binabago natin ang filename para unique at hindi ma-overwrite (student_id + timestamp)
    $new_filename = $student_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Idagdag sa SQL query ang pag-update ng profile_image column
        $profile_image_query = ", profile_image = '$new_filename'";
    }
}

// 2. TRANSACTION START
$conn->begin_transaction();

try {
    // I-update ang student record. 
    // Nilagay ko rin ang 'mobile_no' sa update dahil ito ang madalas na label sa DB base sa add_student.php mo
    $sql_update = "UPDATE students SET 
                    email = '$email', 
                    mobile_no = '$contact_no', 
                    guardian_address = '$address' 
                    $profile_image_query 
                   WHERE student_id = '$student_id'";

    if ($conn->query($sql_update)) {
        $conn->commit();
        ob_clean();
        echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
    } else {
        throw new Exception("Database update failed.");
    }

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
?>