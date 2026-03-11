<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'config.php';

// --- 1. GET SETTINGS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM school_settings WHERE id=1");
    echo json_encode($result->fetch_assoc());
    exit;
}

// --- 2. POST / UPDATE SETTINGS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dahil FormData ang gamit, gagamit tayo ng $_POST imbes na json_decode
    $school_name = $conn->real_escape_string($_POST['school_name']);
    $theme_color = $conn->real_escape_string($_POST['theme_color']);

    // Update basic info
    $sql = "UPDATE school_settings SET school_name='$school_name', theme_color='$theme_color' WHERE id=1";
    $conn->query($sql);

    // Handle Logo Upload kung meron
    if (isset($_FILES['logo'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_url = "http://localhost/sms-api/" . $target_file;
            $conn->query("UPDATE school_settings SET school_logo='$logo_url' WHERE id=1");
        }
    }

    echo json_encode(["success" => true]);
    exit;
}
?>