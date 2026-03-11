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

require 'libs/PHPMailer/Exception.php';
require 'libs/PHPMailer/PHPMailer.php';
require 'libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['last_name'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

// 1. GENERATE STUDENT ID (Format: 2026-0001)
$current_year = date('Y');
$id_prefix = $current_year . "-";
$check_id = $conn->query("SELECT student_id FROM students WHERE student_id LIKE '$id_prefix%' ORDER BY id DESC LIMIT 1");

if ($check_id->num_rows > 0) {
    $last_id = $check_id->fetch_assoc()['student_id'];
    $last_num = (int) substr($last_id, 5);
    $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
} else {
    $new_num = "0001";
}
$student_id = $id_prefix . $new_num;

// 2. GENERATE ENROLLMENT ID
$enroll_id = "ENR-" . date('y') . "-" . strtoupper(bin2hex(random_bytes(2)));

$first_name = $conn->real_escape_string($data['first_name']);
$last_name = $conn->real_escape_string($data['last_name']);
$full_name = $first_name . " " . ($data['middle_name'] ? $data['middle_name'] . " " : "") . $last_name;
$email = $conn->real_escape_string($data['email']);
$token = bin2hex(random_bytes(32));

// 3. TRANSACTION START
$conn->begin_transaction();

try {
    // A. INSERT TO STUDENTS
    // A. INSERT TO STUDENTS (Updated with more fields)
    $sql_student = "INSERT INTO students (
        student_id, lrn, first_name, middle_name, last_name, suffix, nickname, 
        gender, dob, place_of_birth, nationality, religion, civil_status, email, mobile_no, 
        alt_mobile_no, address_house, address_brgy, address_city, address_province, address_zip,
        father_name, father_occ, father_contact, mother_name, mother_occ, mother_contact,
        guardian_name, guardian_rel, guardian_contact, guardian_address, verification_token
    ) VALUES (
        '$student_id', 
        '{$data['lrn']}', 
        '$first_name', 
        '{$data['middle_name']}', 
        '$last_name', 
        '{$data['suffix']}', 
        '{$data['nickname']}', 
        '{$data['gender']}', 
        '{$data['dob']}', 
        '{$data['place_of_birth']}', 
        '{$data['nationality']}', 
        '{$data['religion']}', 
        '{$data['civil_status']}', 
        '$email', 
        '{$data['mobile_no']}', 
        '{$data['alt_mobile_no']}', 
        '{$data['address_house']}', 
        '{$data['address_brgy']}', 
        '{$data['address_city']}', 
        '{$data['address_province']}', 
        '{$data['address_zip']}', 
        '{$data['father_name']}', 
        '{$data['father_occ']}', 
        '{$data['father_contact']}', 
        '{$data['mother_name']}', 
        '{$data['mother_occ']}', 
        '{$data['mother_contact']}', 
        '{$data['guardian_name']}', 
        '{$data['guardian_rel']}', 
        '{$data['guardian_contact']}', 
        '{$data['guardian_address']}', 
        '$token'
    )";

    $conn->query($sql_student);

    // B. INSERT TO ENROLLMENTS
    $sql_enroll = "INSERT INTO enrollments (
        enrollment_id, student_id, school_year, enrollment_type, grade_level, payment_plan, status
    ) VALUES (
        '$enroll_id', '$student_id', '{$data['school_year']}', '{$data['enrollment_type']}', '{$data['grade_level']}', '{$data['payment_plan']}', 'Pending'
    )";
    $conn->query($sql_enroll);

    // C. INITIAL BILLING SETUP (Dito papasok ang integration sa Cashier)
    // For now, let's set a base tuition. In a full system, this depends on grade_level.
    $initial_tuition = 15000.00; // Example amount
    $sql_billing = "INSERT INTO student_billing (student_id, total_amount, balance, payment_status) 
                    VALUES ('$student_id', $initial_tuition, $initial_tuition, 'Unpaid')";
    $conn->query($sql_billing);

    // 4. BRANDING & EMAIL
    $branding_res = $conn->query("SELECT * FROM school_settings WHERE id=1");
    $branding = $branding_res->fetch_assoc();
    $school_name = $branding['school_name'] ?? "School Portal";
    $theme_color = $branding['theme_color'] ?? "#2563eb";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'primaschool1@gmail.com';
    $mail->Password = 'axnokkbahyzscmxf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('primaschool1@gmail.com', $school_name);
    $mail->addAddress($email, $full_name);
    $mail->isHTML(true);

    $setup_link = "http://localhost:5173/setup-password?token=$token&email=" . urlencode($email);

    $mail->Subject = "Welcome to $school_name - Official Student Portal Access";
    $mail->Body = "
        <div style='font-family: sans-serif; padding: 20px; background: #f4f7f6;'>
            <div style='max-width: 600px; margin: auto; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                <div style='background: $theme_color; padding: 40px; text-align: center; color: white;'>
                    <h1 style='margin:0; font-size: 24px;'>Welcome, $first_name!</h1>
                    <p style='opacity: 0.9;'>You are now officially registered.</p>
                </div>
                <div style='padding: 40px;'>
                    <p style='color: #4b5563;'>Mabuhay! Gamitin ang mga detalye sa ibaba para sa iyong official records:</p>
                    <div style='background: #f9fafb; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; margin: 25px 0;'>
                        <p style='margin:0; font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase;'>Student ID</p>
                        <p style='margin:0; font-size: 18px; color: #1e293b; font-weight: 800;'>$student_id</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                        <p style='margin:0; font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase;'>Enrollment ID</p>
                        <p style='margin:0; font-size: 16px; color: #1e293b; font-weight: 700;'>$enroll_id</p>
                    </div>
                    <p style='color: #64748b; font-size: 14px;'>Mangyaring i-setup ang iyong account para ma-access ang iyong schedules, grades, at financial records.</p>
                    <div style='text-align:center; margin: 35px 0;'>
                        <a href='$setup_link' style='display: inline-block; background: $theme_color; color: white; padding: 18px 35px; text-decoration: none; border-radius: 14px; font-weight: 900; box-shadow: 0 10px 20px -5px $theme_color;'>Setup My Student Portal</a>
                    </div>
                </div>
            </div>
        </div>";

    $mail->send();
    $conn->commit();
    ob_clean();
    echo json_encode(["success" => true, "student_id" => $student_id]);

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(["success" => false, "message" => "Critical Error: " . $e->getMessage()]);
}
?>