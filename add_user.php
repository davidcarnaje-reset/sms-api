<?php
error_reporting(0);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // 1. Dinagdag ang OPTIONS dito
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 2. THE GUARD: Harangan ang CORS Preflight (Ghost Request)
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

// 3. EXTRA SECURITY: Wag i-save kapag walang laman ang email o username
if (empty($data['username']) || empty($data['email'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Empty payload. Request blocked."]);
    exit();
}

// 4. Prepare detailed User Data
$first_name = $conn->real_escape_string($data['first_name']);
$middle_name = $conn->real_escape_string($data['middle_name']);
$last_name = $conn->real_escape_string($data['last_name']);
$full_name = "$first_name $middle_name $last_name"; // Consolidated
$email = $conn->real_escape_string($data['email']);
$username = $conn->real_escape_string($data['username']);
$role = $conn->real_escape_string($data['role']); // e.g., registrar, cashier
$birthday = $conn->real_escape_string($data['birthday']);
$phone = $conn->real_escape_string($data['phone_number']);

// 5. Generate Activation Token
$token = bin2hex(random_bytes(32));

// 6. Branding Info
$branding_res = $conn->query("SELECT * FROM school_settings WHERE id=1");
$branding = $branding_res->fetch_assoc();
$school_name = $branding['school_name'] ?? "School Portal";
$school_logo = $branding['school_logo'];
$theme_color = $branding['theme_color'] ?? "#2563eb";

// 7. Database Insert (Walang password muna, is_verified = 0)
$sql = "INSERT INTO users (username, first_name, middle_name, last_name, full_name, email, role, birthday, phone_number, verification_token, is_verified) 
        VALUES ('$username', '$first_name', '$middle_name', '$last_name', '$full_name', '$email', '$role', '$birthday', '$phone', '$token', 0)";

if ($conn->query($sql)) {
    $mail = new PHPMailer(true);
    try {
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

        // Embed Logo
        $logo_src = "";
        if (!empty($school_logo)) {
            $logo_path = 'uploads/' . basename($school_logo);
            if (file_exists($logo_path)) {
                $mail->addEmbeddedImage($logo_path, 'school_logo');
                $logo_src = "cid:school_logo";
            }
        }

        $activation_link = "http://localhost:5173/setup-password?token=$token&email=" . urlencode($email);

        $mail->Subject = "Account Activation - $school_name";
        $mail->Body = "
        <div style='background-color: #f4f7f6; padding: 30px; font-family: sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 20px; overflow: hidden;'>
                <div style='background: $theme_color; padding: 40px; text-align: center; color: #fff;'>
                    " . ($logo_src ? "<img src='$logo_src' style='height: 70px; background: #fff; border-radius: 12px; padding: 5px; margin-bottom: 10px;'>" : "") . "
                    <h1 style='margin:0;'>Welcome to the Team!</h1>
                </div>
                <div style='padding: 40px;'>
                    <p style='font-size: 16px; color: #1e293b;'>Hello <strong>$first_name</strong>,</p>
                    <p style='color: #64748b; line-height: 1.6;'>Ang iyong staff account bilang <b>" . ucfirst($role) . "</b> ay handa na para sa activation. Mangyaring i-click ang button sa ibaba upang i-verify ang iyong email at mag-set ng iyong password.</p>
                    
                    <div style='text-align: center; margin: 40px 0;'>
                        <a href='$activation_link' style='background: $theme_color; color: #fff; padding: 18px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px;'>Activate & Set Password</a>
                    </div>

                    <div style='background: #f8fafc; padding: 20px; border-radius: 15px; font-size: 13px; color: #64748b;'>
                        <p style='margin:0;'><b>Username:</b> $username</p>
                        <p style='margin:5px 0 0 0;'><b>Role:</b> " . ucfirst($role) . "</p>
                    </div>
                </div>
                <div style='padding: 20px; text-align: center; color: #94a3b8; font-size: 11px;'>
                    &copy; " . date('Y') . " $school_name. This is an official system notification.
                </div>
            </div>
        </div>";

        $mail->send();
        ob_clean();
        echo json_encode(["success" => true, "message" => "Invitation sent to $email!"]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(["success" => true, "message" => "User saved, but email failed: {$mail->ErrorInfo}"]);
    }
} else {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Database Error: " . $conn->error]);
}
?>