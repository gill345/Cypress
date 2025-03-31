<?php

require_once 'db.php';
session_start();

$query = "SELECT role from users WHERE id = ?";
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


$query = "SELECT id, description, report_type, latitude, longitude, contact_info, status FROM city_reports";
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
$threshold = 0.5; 

foreach ($reports as $report) {
    $grouped = false;
    foreach ($grouped_reports as &$group) {
        if ($group['report_type'] === $report['report_type'] &&
            haversine($group['latitude'], $group['longitude'], $report['latitude'], $report['longitude']) <= $threshold) {
            $group['duplicates'][] = $report;
            $grouped = true;
            break;
        }
    }
    if (!$grouped) {
        $grouped_reports[] = array_merge($report, ['duplicates' => []]);
    }
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
</style>
<body class="bg-light">
 
    <nav class="navbar navbar-light p-2" style="background-color: #93B5E1;">
        <h1><a class="navbar-brand link-dark font-bold fs-1" href="index.php">Project Cypress</a></h1>
        <form method="post" action="index.php" class="d-flex">
            <a href="index.php" class="btn btn-success me-2">User View</a>
            <button type="submit" name="sign_out" class="btn btn-danger">Sign Out</button>
        </form>
    </nav>

    <div class="container">
        <h2 class="text-center my-4">Grouped Problems</h2>
        <?php foreach ($grouped_reports as $group): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Problem #<?php echo $group['id']; ?></h5>
                    <p class="card-text"><strong>Description:</strong> <?php echo $group['description']; ?></p>
                    <p class="card-text"><strong>Type:</strong> <?php echo $group['report_type']; ?></p>
                    <p class="card-text"><strong>Status:</strong> <?php echo $group['status']; ?></p>
                    <p class="card-text"><strong>Contact Info:</strong> <?php echo $group['contact_info'] ?: 'N/A'; ?></p>
                    <div id="map-<?php echo $group['id']; ?>" style="height: 200px;"></div>
                    <?php if (!empty($group['duplicates'])): ?>
                        <div class="mt-3">
                            <p class="card-text text-danger"><strong>Duplicates:</strong> <?php echo count($group['duplicates']); ?></p>
                            <div class="d-flex align-items-center">
                                <span class="me-2">
                                    <i class="bi <?php echo count($group['duplicates']) > 0 ? 'bi-exclamation-circle-fill text-danger' : 'bi-check-circle-fill text-success'; ?>"></i>
                                </span>
                                <button class="btn btn-success me-2" onclick="handleApprove(<?php echo $group['id']; ?>)">Approve</button>
                                <button class="btn btn-danger" onclick="handleDeny(<?php echo $group['id']; ?>)">Deny</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var map = L.map('map-<?php echo $group['id']; ?>').setView([<?php echo $group['latitude']; ?>, <?php echo $group['longitude']; ?>], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                    }).addTo(map);
                    L.marker([<?php echo $group['latitude']; ?>, <?php echo $group['longitude']; ?>]).addTo(map)
                        .bindPopup('<strong>Problem #<?php echo $group['id']; ?></strong><br><?php echo $group['description']; ?>')
                        .openPopup();
                });

                function handleApprove(id) {
                    // Placeholder for approve logic
                    console.log('Approved report ID:', id);
                }

                function handleDeny(id) {
                    // Placeholder for deny logic
                    console.log('Denied report ID:', id);
                }
            </script>
        <?php endforeach; ?>
    </div>

</body>
</html>