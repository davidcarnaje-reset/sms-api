<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'config.php';

if (empty($_POST['student_id'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Student ID missing."]);
    exit();
}

$student_id = $conn->real_escape_string($_POST['student_id']);
$email = $conn->real_escape_string($_POST['email']);
// Binago natin ang keys para mag-match sa FormData ng StudentLayout.jsx
$contact = $conn->real_escape_string($_POST['mobile_no']);
$address = $conn->real_escape_string($_POST['address_house']);

$image_sql = "";

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $target_dir = "uploads/profiles/";
    
    // Siguraduhin na nage-exist ang folder
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = $student_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $image_sql = ", profile_image = '$new_filename'";
    }
}

try {
    // Siguraduhin na ang column names dito ay mobile_no at address_house
    $sql = "UPDATE students SET 
                email = '$email', 
                mobile_no = '$contact', 
                address_house = '$address' 
                $image_sql 
            WHERE student_id = '$student_id'";

    if ($conn->query($sql)) {
        ob_clean();
        echo json_encode(["success" => true, "message" => "Record updated!"]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conn->close();
?>