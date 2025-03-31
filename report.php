<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_out'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$lat = isset($_GET['lat']) ? htmlspecialchars($_GET['lat']) : '';
$lng = isset($_GET['lng']) ? htmlspecialchars($_GET['lng']) : '';
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


    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>

    <!-- Latest compiled and minified CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Latest compiled JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<style>
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
</style>
<body class="container bg-light">
    <!-- Sign-out button -->
    <form method="post" action="index.php" class="sign-out-btn">
        <button type="submit" name="sign_out" class="btn btn-danger">Sign Out</button>
    </form>

    <h1 class="text-center text-primary mt-4">Submit a City Report</h1>
    <form action="submit_report.php" method="post" class="mt-4">
        <div class="mb-3">
            <label for="description" class="form-label">Report Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label for="report_type" class="form-label">Report Type</label>
            <select class="form-select" id="report_type" name="report_type" required>
                <option value="" disabled selected>Select a type</option>
                <option value="Crime">Crime</option>
                <option value="Construction">Construction</option>
                <option value="Pothole">Pothole</option>
                <option value="Streetlight Issue">Streetlight Issue</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $lat; ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $lng; ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="contact_info" class="form-label">Contact Information (Optional)</label>
            <input type="text" class="form-control" id="contact_info" name="contact_info" placeholder="Email or Phone">
        </div>
        <button type="submit" class="btn btn-primary">Submit Report</button>
    </form>
</body>
</html>
