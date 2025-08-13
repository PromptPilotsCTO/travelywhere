<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'config.php';
require_once 'vendor/PHPMailer/Exception.php';
require_once 'vendor/PHPMailer/PHPMailer.php';
require_once 'vendor/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $inviter_user_id = $data['inviter_user_id'] ?? null;
    $invitee_email = $data['invitee_email'] ?? null;
    $inviter_firstname = $data['inviter_firstname'] ?? null;

    if (empty($inviter_user_id) || empty($invitee_email) || empty($inviter_firstname)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }

    $token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("INSERT INTO user_shares (inviter_user_id, invitee_email, token) VALUES (?, ?, ?)");
        $stmt->execute([$inviter_user_id, $invitee_email, $token]);

        $mail = new PHPMailer(true);

        // Server settings from config.php
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Korrigiert, um mit register_user.php übereinzustimmen
        $mail->Port = SMTP_PORT;

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($invitee_email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = "You've been invited to follow trips on travelywhere!";
        $accept_link = "https://travelywhere.com/api/confirm_invitation.php?token=$token";
        $mail->Body    = "<p>Hi there,</p><p><b>$inviter_firstname</b> has invited you to follow their trips on travelywhere!</p><p>Click the link below to accept the invitation:</p><p><a href='$accept_link'>Accept Invitation</a></p><p>Thanks,<br>The travelywhere Team</p>";
        $mail->AltBody = "Hi there, $inviter_firstname has invited you to follow their trips on travelywhere! To accept, please visit the following link: $accept_link";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Invitation sent successfully.']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
}
?>
