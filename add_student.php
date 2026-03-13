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

if (empty($data['email']) || empty($data['last_name']) || empty($data['first_name'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

// -------------------------------------------------------------------
// 1. DATA SANITIZATION (Security Upgrade + program_id added)
// -------------------------------------------------------------------
$safe_data = [];
$fields = [
    'lrn',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'nickname',
    'gender',
    'dob',
    'place_of_birth',
    'nationality',
    'religion',
    'civil_status',
    'email',
    'mobile_no',
    'alt_mobile_no',
    'address_house',
    'address_brgy',
    'address_city',
    'address_province',
    'address_zip',
    'father_name',
    'father_occ',
    'father_contact',
    'mother_name',
    'mother_occ',
    'mother_contact',
    'guardian_name',
    'guardian_rel',
    'guardian_contact',
    'guardian_address',
    'school_year',
    'enrollment_type',
    'grade_level',
    'program_id',
    'payment_plan' // <-- DINAGDAG ANG program_id DITO
];

foreach ($fields as $field) {
    $safe_data[$field] = isset($data[$field]) ? $conn->real_escape_string($data[$field]) : '';
}

// -------------------------------------------------------------------
// 2. ID GENERATION
// -------------------------------------------------------------------
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

$enroll_id = "ENR-" . date('y') . "-" . strtoupper(bin2hex(random_bytes(2)));
$token = bin2hex(random_bytes(32));

$full_name = $safe_data['first_name'] . " " . ($safe_data['middle_name'] ? $safe_data['middle_name'] . " " : "") . $safe_data['last_name'];
$email = $safe_data['email'];

// -------------------------------------------------------------------
// 3. DATABASE TRANSACTION
// -------------------------------------------------------------------
$conn->begin_transaction();

try {
    // A. INSERT TO STUDENTS MASTERLIST
    $sql_student = "INSERT INTO students (
        student_id, lrn, first_name, middle_name, last_name, suffix, nickname, 
        gender, dob, place_of_birth, nationality, religion, civil_status, email, mobile_no, 
        alt_mobile_no, address_house, address_brgy, address_city, address_province, address_zip,
        father_name, father_occ, father_contact, mother_name, mother_occ, mother_contact,
        guardian_name, guardian_rel, guardian_contact, guardian_address, verification_token
    ) VALUES (
        '$student_id', '{$safe_data['lrn']}', '{$safe_data['first_name']}', '{$safe_data['middle_name']}', 
        '{$safe_data['last_name']}', '{$safe_data['suffix']}', '{$safe_data['nickname']}', '{$safe_data['gender']}', 
        '{$safe_data['dob']}', '{$safe_data['place_of_birth']}', '{$safe_data['nationality']}', '{$safe_data['religion']}', 
        '{$safe_data['civil_status']}', '$email', '{$safe_data['mobile_no']}', '{$safe_data['alt_mobile_no']}', 
        '{$safe_data['address_house']}', '{$safe_data['address_brgy']}', '{$safe_data['address_city']}', 
        '{$safe_data['address_province']}', '{$safe_data['address_zip']}', '{$safe_data['father_name']}', 
        '{$safe_data['father_occ']}', '{$safe_data['father_contact']}', '{$safe_data['mother_name']}', 
        '{$safe_data['mother_occ']}', '{$safe_data['mother_contact']}', '{$safe_data['guardian_name']}', 
        '{$safe_data['guardian_rel']}', '{$safe_data['guardian_contact']}', '{$safe_data['guardian_address']}', 
        '$token'
    )";
    $conn->query($sql_student);

    // B. PENDING ENROLLMENT CREATION (Kasama na ang program_id)
    $program_id_val = !empty($safe_data['program_id']) ? "'{$safe_data['program_id']}'" : "NULL";

    $sql_enroll = "INSERT INTO enrollments (
        enrollment_id, student_id, school_year, enrollment_type, grade_level, program_id, payment_plan, status
    ) VALUES (
        '$enroll_id', '$student_id', '{$safe_data['school_year']}', '{$safe_data['enrollment_type']}', 
        '{$safe_data['grade_level']}', $program_id_val, '{$safe_data['payment_plan']}', 'Pending'
    )";
    $conn->query($sql_enroll);

    // -------------------------------------------------------------------
    // 4. BRANDING & EMAIL DISPATCH
    // -------------------------------------------------------------------
    $branding_res = $conn->query("SELECT * FROM school_settings WHERE id=1");
    $branding = $branding_res->fetch_assoc();
    $school_name = $branding['school_name'] ?? "School Portal";
    $theme_color = $branding['theme_color'] ?? "#2563eb";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'primaschool1@gmail.com';
    $mail->Password = 'axnokkbahyzscmxf'; // Make sure to hide this in production using .env
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
                    <h1 style='margin:0; font-size: 24px;'>Welcome, {$safe_data['first_name']}!</h1>
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

    // Return Success
    echo json_encode(["success" => true, "student_id" => $student_id]);

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>