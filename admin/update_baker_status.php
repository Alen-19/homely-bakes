<?php
session_start();
include '../connect.php';

// Update paths to point to phpmailer-master directory
require_once '../phpmailer-master/src/Exception.php';
require_once '../phpmailer-master/src/PHPMailer.php';
require_once '../phpmailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendStatusNotificationEmail($email, $status) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alenkuriakose29@gmail.com';
        $mail->Password = 'gqdx bwfq pqzk ibvv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Additional settings to improve deliverability
        $mail->XMailer = 'HomelyBakes Mailer';
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Recipients
        $mail->setFrom('alenkuriakose29@gmail.com', 'HomelyBakes Admin');
        $mail->addAddress($email);
        $mail->addReplyTo('alenkuriakose29@gmail.com', 'HomelyBakes Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'HomelyBakes - Account Status Update';
        
        if ($status === 'restricted') {
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Account Status Update</h2>
                    <p>Dear Baker,</p>
                    <p>Your HomelyBakes baker account has been temporarily set to <strong style='color: #ff4444;'>unavailable</strong> by the administrator.</p>
                    <p>During this time, you won't be able to receive new orders.</p>
                    <p>If you have any questions, please contact our support team.</p>
                    <p style='margin-top: 20px;'>Best regards,<br>HomelyBakes Admin Team</p>
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                        <p>This is an automated message from HomelyBakes. Please do not reply to this email.</p>
                    </div>
                </div>
            ";
        } else {
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Account Status Update</h2>
                    <p>Dear Baker,</p>
                    <p>Your HomelyBakes baker account has been <strong style='color: #44aa44;'>reactivated</strong> and is now available to receive orders.</p>
                    <p>You can now log in and start accepting new orders.</p>
                    <p style='margin-top: 20px;'>Best regards,<br>HomelyBakes Admin Team</p>
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                        <p>This is an automated message from HomelyBakes. Please do not reply to this email.</p>
                    </div>
                </div>
            ";
        }

        // Plain text alternative
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $mail->Body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
}

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['baker_id']) && isset($_POST['status'])) {
        $baker_id = (int)$_POST['baker_id'];
        $new_status = $_POST['status'];
        
        // Get baker's email
        $stmt = $conn->prepare("SELECT l.email FROM table_baker b JOIN table_login l ON b.user_id = l.user_id WHERE b.baker_id = ?");
        $stmt->bind_param("i", $baker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update status
            $stmt = $conn->prepare("UPDATE table_baker SET admin_override = ? WHERE baker_id = ?");
            $stmt->bind_param("si", $new_status, $baker_id);
            
            if ($stmt->execute()) {
                if (sendStatusNotificationEmail($row['email'], $new_status)) {
                    $_SESSION['success'] = "Status updated and notification email sent";
                } else {
                    $_SESSION['success'] = "Status updated but failed to send notification";
                }
            } else {
                $_SESSION['error'] = "Failed to update status";
            }
        } else {
            $_SESSION['error'] = "Baker not found";
        }
    } else {
        $_SESSION['error'] = "Invalid parameters";
    }
    
    // Redirect back to manage bakers page
    header("Location: manage_bakers.php");
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 