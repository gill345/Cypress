<?php
require_once 'db.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Function to send status update notifications
function sendStatusNotification($report_id, $status, $recipient_email) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'son18032005@gmail.com';
        $mail->Password   = 'lkxhhtncozhbybui';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('son18032005@gmail.com', 'Cypress Notification');
        $mail->addAddress($recipient_email);
        
        // Content
        $mail->isHTML(true);
        
        switch ($status) {
            case 'In Progress':
                $mail->Subject = 'Your Report #' . $report_id . ' is Now In Progress - Cypress';
                $mail->Body = "
                    <h2>Your Report #$report_id is Now Being Processed!</h2>
                    <p>Good news! Our team has reviewed your report and we are now actively working on addressing the issue.</p>
                    <p>We will keep you updated on any further developments.</p>
                    <p>Thank you for helping make our city better!</p>
                    <p><small>This is an automated message from the Cypress Report System. Please do not reply to this email.</small></p>
                ";
                break;
                
            case 'Resolved':
                $mail->Subject = 'Your Report #' . $report_id . ' Has Been Resolved - Cypress';
                $mail->Body = "
                    <h2>Your Report #$report_id Has Been Successfully Resolved!</h2>
                    <p>We're pleased to inform you that the issue you reported has been completely resolved.</p>
                    <p>Thank you for your valuable contribution to improving our community!</p>
                    <p>If you notice any other issues in the future, please don't hesitate to submit another report.</p>
                    <p><small>This is an automated message from the Cypress Report System. Please do not reply to this email.</small></p>
                ";
                break;
                
            case 'Deleted':
            case 'Duplicates Removed':
                $mail->Subject = 'Update Regarding Your Report #' . $report_id . ' - Cypress';
                $mail->Body = "
                    <h2>Important Update About Your Report #$report_id</h2>
                    <p>We will no longer be proceeding further with this report anymore and have it removed from our system.</p>
                    <p>This could be due to various reasons such as inappropiate information, duplicate reports, issue being outside our jurisdiction or has been resolved.</p>
                    <p>We appreciate your time and effort in bringing this matter to our attention.</p>
                    <p>Please feel free to submit new reports for other issues in the future.</p>
                    <p><small>This is an automated message from the Cypress Report System. Please do not reply to this email.</small></p>
                ";
                break;
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
$_SESSION['role'] = $role;

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_out'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $problem_id = intval($_POST['problem_id']);

        if ($action === 'update' && isset($_POST['new_status'])) {
            $new_status = $_POST['new_status'];
            if (in_array($new_status, ['Submitted', 'In Progress', 'Resolved'])) {
                $update_query = "UPDATE city_reports SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $new_status, $problem_id);
                $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => true, 'message' => "Problem #$problem_id updated to status: $new_status"]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit();
            }
        }

        if ($action === 'set_in_progress') {
            $new_status = 'In Progress';
            $update_query = "UPDATE city_reports SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $problem_id);
            $stmt->execute();
            $stmt->close();
            
            // Get user email for notification
            $email_query = "SELECT u.email, cr.contact_info, cr.notify_updates 
                           FROM city_reports cr 
                           LEFT JOIN users u ON cr.user_id = u.id 
                           WHERE cr.id = ?";
            $stmt = $conn->prepare($email_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            // Send notification if user has opted in
            if ($user_data && $user_data['notify_updates']) {
                // Use contact info email if provided, otherwise use user's email
                $notification_email = !empty($user_data['contact_info']) && filter_var($user_data['contact_info'], FILTER_VALIDATE_EMAIL) 
                    ? $user_data['contact_info'] 
                    : $user_data['email'];
                
                if ($notification_email) {
                    sendStatusNotification($problem_id, $new_status, $notification_email);
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Problem #$problem_id status set to: $new_status"]);
            exit();
        }

        if ($action === 'set_resolved') {
            $new_status = 'Resolved';
            $update_query = "UPDATE city_reports SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $problem_id);
            $stmt->execute();
            $stmt->close();
            
            // Get user email for notification
            $email_query = "SELECT u.email, cr.contact_info, cr.notify_updates 
                           FROM city_reports cr 
                           LEFT JOIN users u ON cr.user_id = u.id 
                           WHERE cr.id = ?";
            $stmt = $conn->prepare($email_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            // Send notification if user has opted in
            if ($user_data && $user_data['notify_updates']) {
                // Use contact info email if provided, otherwise use user's email
                $notification_email = !empty($user_data['contact_info']) && filter_var($user_data['contact_info'], FILTER_VALIDATE_EMAIL) 
                    ? $user_data['contact_info'] 
                    : $user_data['email'];
                
                if ($notification_email) {
                    sendStatusNotification($problem_id, $new_status, $notification_email);
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Problem #$problem_id status set to: $new_status"]);
            exit();
        }

        if ($action === 'delete') {
            // Get user email for notification before deleting
            $email_query = "SELECT u.email, cr.contact_info, cr.notify_updates 
                           FROM city_reports cr 
                           LEFT JOIN users u ON cr.user_id = u.id 
                           WHERE cr.id = ?";
            $stmt = $conn->prepare($email_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            // Delete the report
            $delete_query = "DELETE FROM city_reports WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();
            
            // Send notification if user has opted in
            if ($user_data && $user_data['notify_updates']) {
                // Use contact info email if provided, otherwise use user's email
                $notification_email = !empty($user_data['contact_info']) && filter_var($user_data['contact_info'], FILTER_VALIDATE_EMAIL) 
                    ? $user_data['contact_info'] 
                    : $user_data['email'];
                
                if ($notification_email) {
                    sendStatusNotification($problem_id, 'Deleted', $notification_email);
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Problem #$problem_id has been deleted"]);
            exit();
        }

        if ($action === 'accept_with_duplicates') {
            $duplicates = isset($_POST['duplicates']) ? json_decode($_POST['duplicates'], true) : [];
            $duplicates = array_map('intval', $duplicates);
            
            // Get user email for notification before updating
            $email_query = "SELECT u.email, cr.contact_info, cr.notify_updates 
                           FROM city_reports cr 
                           LEFT JOIN users u ON cr.user_id = u.id 
                           WHERE cr.id = ?";
            $stmt = $conn->prepare($email_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            // Update the selected report to "In Progress"
            $update_query = "UPDATE city_reports SET status = 'In Progress' WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();
            
            // Send notification for the selected report if user has opted in
            if ($user_data && $user_data['notify_updates']) {
                // Use contact info email if provided, otherwise use user's email
                $notification_email = !empty($user_data['contact_info']) && filter_var($user_data['contact_info'], FILTER_VALIDATE_EMAIL) 
                    ? $user_data['contact_info'] 
                    : $user_data['email'];
                
                if ($notification_email) {
                    sendStatusNotification($problem_id, 'In Progress', $notification_email);
                }
            }
            
            // Handle reports to remove
            if (!empty($duplicates)) {
                // Get user emails for reports to be removed
                $duplicate_ids = implode(',', $duplicates);
                $duplicate_query = "SELECT cr.id, u.email, cr.contact_info, cr.notify_updates 
                                   FROM city_reports cr 
                                   LEFT JOIN users u ON cr.user_id = u.id 
                                   WHERE cr.id IN ($duplicate_ids)";
                $duplicate_result = $conn->query($duplicate_query);
                
                // Delete the reports
                $delete_query = "DELETE FROM city_reports WHERE id IN (" . implode(',', $duplicates) . ")";
                $conn->query($delete_query);
                
                // Send notifications for removed reports
                while ($duplicate = $duplicate_result->fetch_assoc()) {
                    if ($duplicate['notify_updates']) {
                        // Use contact info email if provided, otherwise use user's email
                        $notification_email = !empty($duplicate['contact_info']) && filter_var($duplicate['contact_info'], FILTER_VALIDATE_EMAIL) 
                            ? $duplicate['contact_info'] 
                            : $duplicate['email'];
                        
                        if ($notification_email) {
                            sendStatusNotification($duplicate['id'], 'Duplicates Removed', $notification_email);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => "Report #$problem_id has been processed and duplicates removed"]);
            exit();
        }

        if ($action === 'contact_emergency') {
            $update_query = "UPDATE city_reports SET emergency_contacted = 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => "Emergency services contacted for Problem #$problem_id"]);
            exit();
        }

        if ($action === 'contact_city_service') {
            $update_query = "UPDATE city_reports SET city_service_contacted = 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => "City services contacted for Problem #$problem_id"]);
            exit();
        }
    }
}

// Get filter values from the request
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_urgency = isset($_GET['urgency']) ? $_GET['urgency'] : '';

// Modify the query to include filters
$query = "SELECT city_reports.id, city_reports.description, city_reports.report_type, city_reports.latitude, city_reports.longitude, city_reports.contact_info, city_reports.status, city_reports.created_at, city_reports.urgency, city_reports.emergency_contacted, city_reports.city_service_contacted, users.name AS submitted_by 
          FROM city_reports 
          LEFT JOIN users ON city_reports.user_id = users.id 
          WHERE 1=1";

if (!empty($filter_type)) {
    $query .= " AND city_reports.report_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if (!empty($filter_status)) {
    $query .= " AND city_reports.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($filter_urgency)) {
    $query .= " AND city_reports.urgency = '" . $conn->real_escape_string($filter_urgency) . "'";
}

$result = $conn->query($query);

function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c; 
}

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

$grouped_reports = [];
$processed_ids = [];
$distance_threshold = 0.3; 

foreach ($reports as $report) {
    if (in_array($report['id'], $processed_ids)) continue; 
    $group = array_merge($report, ['duplicates' => []]);
    $processed_ids[] = $report['id'];

    foreach ($reports as $other_report) {
        if (in_array($other_report['id'], $processed_ids) || $other_report['id'] === $report['id']) continue;
        $distance = haversine($report['latitude'], $report['longitude'], $other_report['latitude'], $other_report['longitude']);

        if ($distance <= $distance_threshold) {
            $group['duplicates'][] = $other_report;
            $processed_ids[] = $other_report['id'];
        }
    }

    $grouped_reports[] = $group;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Cypress</title>
    <link rel="icon" type="image/x-icon" href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
    <link rel="stylesheet" href="style.css">

    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        #map {
            height: 350px;
            width: 75%;
            margin: 0 auto;
            display: block;
        }

        .sign-out-btn {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .center-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .container {
            margin-top: 20px;
        }

        .navbar {
            width: 100%;
            margin: 0;
        }

        .map-container {
            height: 200px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-light p-2" style="background-color: #93B5E1;">
        <h1>
            <a class="navbar-brand link-dark font-bold fs-1 d-flex align-items-center" href="index.php">
                <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" alt="Logo" style="height: 50px; margin-right: 10px;">
                Project Cypress
            </a>
        </h1>
        <form method="post" action="index.php" class="d-flex">
            <a href="index.php" class="btn btn-success me-2">User View</a>
            <button type="submit" name="sign_out" class="btn btn-danger">Sign Out</button>
        </form>
    </nav>

    <div class="container">
        <h2 class="text-center my-4">Admin Problem Review</h2>

        <!-- Filter Form -->
        <form method="GET" action="admin.php" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="type" class="form-label">Filter by Type</label>
                <select id="type" name="type" class="form-select">
                    <option value="">All</option>
                    <option value="Accident" <?php echo $filter_type === 'Accident' ? 'selected' : ''; ?>>Accident</option>
                    <option value="Crime" <?php echo $filter_type === 'Crime' ? 'selected' : ''; ?>>Crime</option>
                    <option value="Construction" <?php echo $filter_type === 'Construction' ? 'selected' : ''; ?>>Construction</option>
                    <option value="Pothole" <?php echo $filter_type === 'Pothole' ? 'selected' : ''; ?>>Pothole</option>
                    <option value="Streetlight Issue" <?php echo $filter_type === 'Streetlight Issue' ? 'selected' : ''; ?>>Streetlight Issue</option>
                    <option value="Other" <?php echo $filter_type === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Submitted" <?php echo $filter_status === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="In Progress" <?php echo $filter_status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="urgency" class="form-label">Filter by Urgency</label>
                <select id="urgency" name="urgency" class="form-select">
                    <option value="">All</option>
                    <option value="Low" <?php echo $filter_urgency === 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $filter_urgency === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo $filter_urgency === 'High' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-success">Apply Filters</button>
                <a href="admin.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>

        <?php foreach ($grouped_reports as $group): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Problem #<?php echo htmlspecialchars($group['id']); ?></h5>
                    <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($group['description']); ?></p>
                    <p class="card-text"><strong>Type:</strong> <?php echo htmlspecialchars($group['report_type']); ?></p>
                    <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($group['status']); ?></p>
                    <p class="card-text"><strong>Urgency:</strong> <?php echo htmlspecialchars($group['urgency']); ?></p>
                    <p class="card-text"><strong>Emergency Contacted:</strong> <?php echo $group['emergency_contacted'] ? 'Yes' : 'No'; ?></p>
                    <p class="card-text"><strong>City Service Contacted:</strong> <?php echo $group['city_service_contacted'] ? 'Yes' : 'No'; ?></p>
                    <p class="card-text"><strong>Contact Info:</strong> <?php echo htmlspecialchars($group['contact_info'] ?: 'N/A'); ?></p>
                    <p class="card-text"><strong>Submitted By:</strong> <?php echo htmlspecialchars($group['submitted_by'] ?: 'Unknown'); ?></p>
                    <p class="card-text"><strong>Created At:</strong> <?php echo htmlspecialchars($group['created_at']); ?></p>
                    
                    <div id="map-<?php echo htmlspecialchars($group['id']); ?>" class="map-container"></div>
                    <script>
                        // Define custom icons based on problem type
                        var customIcons = {
                            "accident": L.icon({
                                iconUrl: 'https://img.icons8.com/external-flaticons-lineal-color-flat-icons/100/external-crash-racing-flaticons-lineal-color-flat-icons.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            }),
                            "pothole": L.icon({
                                iconUrl: 'https://img.icons8.com/external-filled-outline-chattapat-/100/external-accident-car-accident-filled-outline-chattapat-.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            }),
                            "construction": L.icon({
                                iconUrl: 'https://img.icons8.com/color/100/crane.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            }),
                            "crime": L.icon({
                                iconUrl: 'https://img.icons8.com/color/100/pickpocket.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            }),
                            "streetlight issue": L.icon({
                                iconUrl: 'https://img.icons8.com/color/100/traffic-light.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            }),
                            "other": L.icon({
                                iconUrl: 'https://img.icons8.com/color/100/error--v1.png',
                                iconSize: [50, 50],
                                iconAnchor: [25, 50],
                                popupAnchor: [0, -50]
                            })
                        };

                        
                        var map<?php echo $group['id']; ?> = L.map('map-<?php echo $group['id']; ?>').setView([<?php echo $group['latitude']; ?>, <?php echo $group['longitude']; ?>], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                        }).addTo(map<?php echo $group['id']; ?>);

                        
                        var problemType = "<?php echo strtolower($group['report_type']); ?>";
                        var icon = customIcons[problemType] || L.icon({
                            iconUrl: 'https://img.icons8.com/ios-filled/50/000000/marker.png', 
                            iconSize: [30, 30],
                            iconAnchor: [15, 30],
                            popupAnchor: [0, -30]
                        });

                        L.marker([<?php echo $group['latitude']; ?>, <?php echo $group['longitude']; ?>], { icon: icon })
                            .addTo(map<?php echo $group['id']; ?>)
                            .bindPopup('<strong>Problem #<?php echo $group['id']; ?></strong><br><?php echo htmlspecialchars($group['description']); ?><br><strong>Status:</strong> <?php echo htmlspecialchars($group['status']); ?>')
                            .openPopup();
                    </script>

                    <?php if (!empty($group['duplicates'])): ?>
                        <div class="mt-3">
                            <p class="card-text text-danger"><strong>Duplicates:</strong> <?php echo count($group['duplicates']); ?></p>
                            <div class="alert alert-info">
                                <p>These reports are potential duplicates. Please select which report to keep and all duplicates will be remove.</p>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="keep_report_<?php echo $group['id']; ?>" id="keep_main_<?php echo $group['id']; ?>" value="<?php echo $group['id']; ?>" checked>
                                        <label class="form-check-label" for="keep_main_<?php echo $group['id']; ?>">
                                            <strong>Main Report #<?php echo htmlspecialchars($group['id']); ?></strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <h5>Duplicate Reports:</h5>
                            <?php foreach ($group['duplicates'] as $duplicate): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="keep_report_<?php echo $group['id']; ?>" id="keep_duplicate_<?php echo $duplicate['id']; ?>" value="<?php echo $duplicate['id']; ?>">
                                            <label class="form-check-label" for="keep_duplicate_<?php echo $duplicate['id']; ?>">
                                                <strong>Duplicate Report #<?php echo htmlspecialchars($duplicate['id']); ?></strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($duplicate['description']); ?></p>
                                        <p class="card-text"><strong>Type:</strong> <?php echo htmlspecialchars($duplicate['report_type']); ?></p>
                                        <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($duplicate['status']); ?></p>
                                        <p class="card-text"><strong>Urgency:</strong> <?php echo htmlspecialchars($duplicate['urgency']); ?></p>
                                        <p class="card-text"><strong>Submitted By:</strong> <?php echo htmlspecialchars($duplicate['submitted_by'] ?: 'Unknown'); ?></p>
                                        <p class="card-text"><strong>Contact Info:</strong> <?php echo htmlspecialchars($duplicate['contact_info'] ?: 'N/A'); ?></p>
                                        <p class="card-text"><strong>Created At:</strong> <?php echo htmlspecialchars($duplicate['created_at']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <button class="btn btn-primary" onclick="handleDuplicates(<?php echo $group['id']; ?>, <?php echo htmlspecialchars(json_encode(array_column($group['duplicates'], 'id'))); ?>)">Process Selected Report</button>
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <span class="me-2">
                                <i class="bi bi-check-circle-fill text-success"></i>
                            </span>
                            <p class="card-text text-success"><strong>No duplicates found.</strong></p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3 d-flex align-items-center">
                        <button class="btn btn-warning me-2" onclick="contactEmergencyService(<?php echo $group['id']; ?>)">Contact Emergency Service</button>
                        <button class="btn btn-info me-2" onclick="contactCityService(<?php echo $group['id']; ?>)">Contact City Service</button>
                        <button class="btn btn-primary me-2" onclick="setInProgress(<?php echo $group['id']; ?>)">Set In Progress</button>
                        <button class="btn btn-success me-2" onclick="setResolved(<?php echo $group['id']; ?>)">Set Resolved</button>
                        <button class="btn btn-danger" onclick="deleteProblem(<?php echo $group['id']; ?>)">Delete</button>
                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>

        function setInProgress(id) {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'set_in_progress',
                    problem_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function setResolved(id) {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'set_resolved',
                    problem_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteProblem(id) {
            if (confirm(`Are you sure you want to delete Problem #${id}?`)) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete',
                        problem_id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function handleDuplicates(mainId, duplicates) {
            // Get the selected report to keep
            const selectedReportId = document.querySelector(`input[name="keep_report_${mainId}"]:checked`).value;
            
            // Determine which reports to remove
            let reportsToRemove = [];
            
            // If the main report is selected, remove all duplicates
            if (selectedReportId == mainId) {
                reportsToRemove = duplicates;
            } 
            // If a duplicate is selected, remove the main report and all other duplicates
            else {
                reportsToRemove.push(mainId);
                duplicates.forEach(duplicateId => {
                    if (duplicateId != selectedReportId) {
                        reportsToRemove.push(duplicateId);
                    }
                });
            }
            
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'accept_with_duplicates',
                    problem_id: selectedReportId,
                    duplicates: JSON.stringify(reportsToRemove)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function contactEmergencyService(reportId) {
            if (confirm("Are you sure you want to contact Emergency Services for this problem?")) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'contact_emergency',
                        problem_id: reportId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function contactCityService(reportId) {
            if (confirm("Are you sure you want to contact City Services for this problem?")) {
                fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'contact_city_service',
                        problem_id: reportId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>