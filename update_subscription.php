<?php
require_once 'db.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['action']) || $_POST['action'] !== 'update_subscription' || !isset($_POST['report_id']) || !isset($_POST['subscribe'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

function sendSubscriptionConfirmation($report_id, $recipient_email) {
    try {
        
        global $conn;
        $query = "SELECT description, report_type, status, latitude, longitude, urgency 
                 FROM city_reports WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();

        if (!$report) {
            return false;
        }

        $mail = new PHPMailer(true);
        
        // Enter your SMTP server details here
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Enter your sender email address here
        $mail->setFrom('', 'Cypress Notification');
        $mail->addAddress($recipient_email);
        
        
        $mail->isHTML(true);
        $mail->Subject = "Subscription Confirmation - Report #$report_id - Cypress";
        $mail->Body = "
            <h2>You have successfully subscribed to Report #$report_id!</h2>
            <p>You will now receive notifications for any updates about this report.</p>
            <h3>Report Details:</h3>
            <ul>
                <li><strong>Type:</strong> {$report['report_type']}</li>
                <li><strong>Description:</strong> {$report['description']}</li>
                <li><strong>Current Status:</strong> {$report['status']}</li>
                <li><strong>Location:</strong> Latitude: {$report['latitude']}, Longitude: {$report['longitude']}</li>
                <li><strong>Urgency:</strong> {$report['urgency']}</li>
            </ul>
            <p>Thank you for participating in improving our community through Cypress!</p>
            <p><small>This is an automated message from the Cypress Report System. Please do not reply to this email.</small></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

$report_id = intval($_POST['report_id']);
$subscribe = intval($_POST['subscribe']);
$user_id = $_SESSION['user_id'];
$email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : null;

if ($subscribe && !$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

try {
    if ($subscribe) {
        
        $insert_query = "INSERT INTO report_subscriptions (report_id, user_id, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $report_id, $user_id, $email);
        
        if ($stmt->execute()) {
            
            if (sendSubscriptionConfirmation($report_id, $email)) {
                echo json_encode(['success' => true, 'message' => 'Subscribed successfully']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Subscribed successfully but failed to send confirmation email']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update subscription']);
        }
    } else {
        // Remove subscription
        $delete_query = "DELETE FROM report_subscriptions WHERE report_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $report_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'You will no longer receive notifications for this report. Thank you for using Cypress!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unsubscribe']);
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error updating subscription: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
} 