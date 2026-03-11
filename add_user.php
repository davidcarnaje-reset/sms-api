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

if (empty($data['email']) || empty($data['username'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

$first_name = $conn->real_escape_string($data['first_name']);
$middle_name = $conn->real_escape_string($data['middle_name']);
$last_name = $conn->real_escape_string($data['last_name']);
$full_name = $first_name . " " . ($middle_name ? $middle_name . " " : "") . $last_name;
$email = $conn->real_escape_string($data['email']);
$username = $conn->real_escape_string($data['username']);
$role = $conn->real_escape_string($data['role']);
$token = bin2hex(random_bytes(32));

// Branding Fetch
$branding_res = $conn->query("SELECT * FROM school_settings WHERE id=1");
$branding = $branding_res->fetch_assoc();
$school_name = $branding['school_name'] ?? "School Portal";
$theme_color = $branding['theme_color'] ?? "#2563eb";

// Start Transaction
$conn->begin_transaction();

try {
    // INSERT TO USERS
    $sql = "INSERT INTO users (username, first_name, middle_name, last_name, full_name, email, role, verification_token, is_verified) 
            VALUES ('$username', '$first_name', '$middle_name', '$last_name', '$full_name', '$email', '$role', '$token', 0)";

    $conn->query($sql);

    // MAILER LOGIC (Kapareho ng sa Student)
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

    $mail->Subject = "Account Activation - $school_name";
    $mail->Body = "
        <div style='font-family: sans-serif; padding: 20px; background: #f4f7f6;'>
            <div style='max-width: 600px; margin: auto; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);'>
                <div style='background: $theme_color; padding: 40px; text-align: center; color: white;'>
                    <h1 style='margin:0; font-size: 24px;'>Welcome, $first_name!</h1>
                    <p style='opacity: 0.9;'>Your Staff Account is ready.</p>
                </div>
                <div style='padding: 40px;'>
                    <p style='color: #4b5563;'>Ang iyong account bilang <b>" . ucfirst($role) . "</b> ay matagumpay na nagawa sa system.</p>
                    <div style='background: #f9fafb; padding: 25px; border-radius: 16px; border: 1px solid #f1f5f9; margin: 25px 0;'>
                        <p style='margin:0; font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase;'>Username</p>
                        <p style='margin:0; font-size: 18px; color: #1e293b; font-weight: 800;'>$username</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                        <p style='margin:0; font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase;'>Role</p>
                        <p style='margin:0; font-size: 16px; color: #1e293b; font-weight: 700;'>" . ucfirst($role) . "</p>
                    </div>
                    <p style='color: #64748b; font-size: 14px;'>Mangyaring i-setup ang iyong password upang ma-access ang staff dashboard at simulan ang iyong trabaho.</p>
                    <div style='text-align:center; margin: 35px 0;'>
                        <a href='$setup_link' style='display: inline-block; background: $theme_color; color: white; padding: 18px 35px; text-decoration: none; border-radius: 14px; font-weight: 900; box-shadow: 0 10px 20px -5px $theme_color;'>Activate My Staff Account</a>
                    </div>
                </div>
            </div>
        </div>";

    $mail->send();
    $conn->commit();
    ob_clean();
    echo json_encode(["success" => true, "message" => "Staff added and email sent!"]);

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>