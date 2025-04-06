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
    header('Location: login.php');
    exit();
}


if (isset($_GET['admin_mode'])) {
    $query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if (strtolower($role) !== 'admin') {
        echo "<script>alert('Access Denied: You must be an admin to access this mode.');</script>";
    } else {
        header('Location: admin.php');
        exit();
    }
}


function sendNotificationEmail($to_email, $report_details, $report_id) {
    try {
        $mail = new PHPMailer(true);
        

       
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        
        // Enter your SMTP server details here
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = '';                
        $mail->Password   = '';                    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
        $mail->Port       = 587;                                    

        // Enter your sender email address here
        $mail->setFrom('', 'Cypress Notification');
        $mail->addAddress($to_email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'New Report Notification - Cypress';
        $mail->Body    = "
            <h2>Your Report #{$report_id} Has Been Submitted, It's Now Being Reviewed!</h2>
            <p>Thank you for submitting a report to Cypress. Here are the details:</p>
            <ul>
                <li><strong>Report Type:</strong> {$report_details['report_type']}</li>
                <li><strong>Description:</strong> {$report_details['description']}</li>
                <li><strong>Location:</strong> Latitude: {$report_details['latitude']}, Longitude: {$report_details['longitude']}</li>
                <li><strong>Urgency:</strong> {$report_details['urgency']}</li>
            </ul>
            <p>We will notify you of any updates to this report.</p>
            <p><small>This is an automated message from the Cypress Report System. Please do not reply to this email!</small></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_out'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$lat = isset($_GET['lat']) ? htmlspecialchars($_GET['lat']) : '';
$lng = isset($_GET['lng']) ? htmlspecialchars($_GET['lng']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $description = htmlspecialchars($_POST['description']);
        $report_type = htmlspecialchars($_POST['report_type']);
        $latitude = htmlspecialchars($_POST['latitude']);
        $longitude = htmlspecialchars($_POST['longitude']);
        $urgency = htmlspecialchars($_POST['urgency']); 
        $contact_info = isset($_POST['contact_info']) ? htmlspecialchars($_POST['contact_info']) : null;
        $subscribe_notifications = isset($_POST['subscribe_notifications']) ? 1 : 0;
        $user_id = $_SESSION['user_id'];

        // Get user's email from the database
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            throw new Exception("Failed to get user email: " . $stmt->error);
        }
        
        $user = $result->fetch_assoc();
        if (!$user) {
            throw new Exception("User not found in database");
        }
        $user_email = $user['email'];

        // Insert report into database
        $stmt = $conn->prepare("INSERT INTO city_reports (user_id, description, report_type, latitude, longitude, urgency, contact_info, status, notify_updates) VALUES (?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $status = "Submitted";
        $stmt->bind_param("issssssi", $user_id, $description, $report_type, $latitude, $longitude, $urgency, $contact_info, $subscribe_notifications);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert report: " . $stmt->error);
        }
        $report_id = $conn->insert_id; 
        $stmt->close();

       
        if ($subscribe_notifications) {
            $notification_email = !empty($contact_info) && filter_var($contact_info, FILTER_VALIDATE_EMAIL) 
                ? $contact_info 
                : $user_email;

            $subscription_query = "INSERT INTO report_subscriptions (report_id, user_id, email) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($subscription_query);
            $stmt->bind_param("iis", $report_id, $user_id, $notification_email);
            
            if (!$stmt->execute()) {
                error_log("Failed to create subscription entry: " . $stmt->error);
                
            }
            $stmt->close();
        }

       
        if ($subscribe_notifications) {
            $report_details = [
                'report_type' => $report_type,
                'description' => $description,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'urgency' => $urgency
            ];

            if ($notification_email) {
                $email_sent = sendNotificationEmail($notification_email, $report_details, $report_id);
                if (!$email_sent) {
                    error_log("Failed to send notification email to: " . $notification_email);
                    
                }
            }
        }

        header('Location: index.php?report=submitted');
        exit();
    } catch (Exception $e) {
        
        error_log("Error in report submission: " . $e->getMessage());
        
        die("An error occurred while submitting your report. Please try again or contact support if the problem persists. Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Cypress - Submit Report</title>
    <link rel="icon" type="image/x-icon" href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #4a90e2;
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .container-form {
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 40px;
        }
        .btn-primary {
            background-color: #4a90e2;
            border: none;
        }
        .btn-primary:hover {
            background-color: #357ab7;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" alt="Logo" height="40">
                Project Cypress
            </a>
            <div>
            <a href="report.php?admin_mode=true" class="btn btn-warning me-2">Admin Mode</a>
                <form method="post" action="index.php" class="d-inline">
                    <button type="submit" name="sign_out" class="btn btn-danger">Sign Out</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container d-flex justify-content-center">
        <div class="container-form">
            <h2 class="text-center text-primary mb-4">Submit a City Report</h2>
            <form action="report.php" method="post">
                <div class="mb-3">
                    <label for="description" class="form-label">Report Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select class="form-select" id="report_type" name="report_type" required>
                        <option value="" disabled selected>Select a type</option>
                        <option value="Accident">Accident</option>
                        <option value="Crime">Crime</option>
                        <option value="Construction">Construction</option>
                        <option value="Pothole">Pothole</option>
                        <option value="Streetlight Issue">Streetlight Issue</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $lat; ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $lng; ?>"readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="urgency" class="form-label">Urgency</label>
                    <select class="form-select" id="urgency" name="urgency" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="contact_info" class="form-label">Contact Information (Optional)</label>
                    <input type="text" class="form-control" id="contact_info" name="contact_info" placeholder="Email or Phone">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="subscribe_notifications" name="subscribe_notifications">
                    <label class="form-check-label" for="subscribe_notifications">Subscribe to email notifications</label>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                    <a href="index.php" class="btn btn-secondary">Return to Home</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

