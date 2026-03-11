<?php
error_reporting(0);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 1. HARANGAN ANG CORS PREFLIGHT
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

if (empty($data['email'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Please provide an email address."]);
    exit();
}

$email = $conn->real_escape_string($data['email']);

// 2. I-check kung nag-e-exist ang email sa database
$sql = "SELECT first_name, full_name, role FROM users WHERE email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'] ? $user['first_name'] : $user['full_name'];

    // 3. Mag-generate ng secure Reset Token
    $reset_token = bin2hex(random_bytes(32));

    // 4. I-save ang Reset Token sa database
    $update_sql = "UPDATE users SET reset_token = '$reset_token' WHERE email = '$email'";

    if ($conn->query($update_sql)) {

        // 5. Kunin ang Branding para sa Email
        $branding_res = $conn->query("SELECT * FROM school_settings WHERE id=1");
        $branding = $branding_res->fetch_assoc();
        $school_name = $branding['school_name'] ?? "School Portal";
        $school_logo = $branding['school_logo'];
        $theme_color = $branding['theme_color'] ?? "#2563eb";

        // 6. I-send ang Email gamit ang PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'primaschool1@gmail.com'; // Iyong school email
            $mail->Password = 'axnokkbahyzscmxf';       // Iyong App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('primaschool1@gmail.com', "$school_name IT Support");
            $mail->addAddress($email, $first_name);
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

            // ITO ANG LINK PAPUNTA SA RESET PASSWORD PAGE NA GAGAWIN NATIN NEXT
            $reset_link = "http://localhost:5173/reset-password?token=$reset_token&email=" . urlencode($email);

            $mail->Subject = "Password Reset Request - $school_name";
            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 20px; overflow: hidden;'>
                    <div style='background: $theme_color; padding: 40px; text-align: center; color: #fff;'>
                        " . ($logo_src ? "<img src='$logo_src' style='height: 70px; background: #fff; border-radius: 12px; padding: 5px; margin-bottom: 10px;'>" : "") . "
                        <h1 style='margin:0;'>Password Reset</h1>
                    </div>
                    <div style='padding: 40px;'>
                        <p style='font-size: 16px; color: #1e293b;'>Hello <strong>$first_name</strong>,</p>
                        <p style='color: #64748b; line-height: 1.6;'>Nakapagtala kami ng request para i-reset ang password ng iyong account. Kung ikaw ang gumawa nito, i-click ang button sa ibaba upang makagawa ng bagong password.</p>
                        
                        <div style='text-align: center; margin: 40px 0;'>
                            <a href='$reset_link' style='background: $theme_color; color: #fff; padding: 18px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px;'>Reset My Password</a>
                        </div>

                        <p style='color: #ef4444; font-size: 13px;'>Kung hindi ikaw ang nag-request nito, maaari mong i-ignore ang email na ito. Mananatiling ligtas ang iyong account.</p>
                    </div>
                    <div style='padding: 20px; text-align: center; color: #94a3b8; font-size: 11px; background: #f8fafc;'>
                        &copy; " . date('Y') . " $school_name IT Support.
                    </div>
                </div>
            </div>";

            $mail->send();
            ob_clean();
            echo json_encode(["success" => true, "message" => "A password reset link has been sent to your email."]);
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo]);
        }
    } else {
        ob_clean();
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
} else {
    // Kung walang nahanap na email sa DB
    ob_clean();
    echo json_encode(["success" => false, "message" => "We could not find an account registered with that email address."]);
}
?>