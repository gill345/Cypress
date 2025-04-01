<?php
require_once 'db.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_out'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_urgency = isset($_GET['urgency']) ? $_GET['urgency'] : '';

$query = "SELECT id, description, report_type, latitude, longitude, status, created_at, urgency FROM city_reports WHERE 1=1";

if (!empty($filter_status)) {
    $query .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($filter_type)) {
    $query .= " AND report_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if (!empty($filter_urgency)) {
    $query .= " AND urgency = '" . $conn->real_escape_string($filter_urgency) . "'";
}

$result = $conn->query($query);

$displayed_reports = [];
while ($row = $result->fetch_assoc()) {
    $displayed_reports[] = $row;
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
        height: 750px;
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
        height: 100vh; 
        text-align: center;
    }

    .navbar {
        width: 100%;
        margin: 0;
    }
</style>

<body class="bg-light">
    
    
    <nav class="navbar navbar-light p-2" style="background-color: #93B5E1;">
        <h1><a class="navbar-brand link-dark font-bold fs-1" href="index.php">Project Cypress</a></h1>
        <form method="post" class="d-flex">
            <a href="admin.php" class="btn btn-warning me-2">Admin Mode</a>
            <button type="submit" name="sign_out" class="btn btn-danger">Sign Out</button>
        </form>
    </nav>

    <?php if (isset($_GET['report']) && $_GET['report'] === 'submitted'): ?>
        <div class="alert alert-success text-center" role="alert">
            Report submitted successfully!
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="box text-center">
            <h1 class="h1 text-primary">Cypress</h1>
            <p class="h5 text-dark">Cypress is a community-driven platform for reporting and tracking public issues on a Toronto map. Users can create alerts for problems like potholes or broken streetlights, while city workers can update and resolve them in real time.</p>
        </div>
    </div>

    <div class="container mt-4">
        <form method="GET" action="index.php" class="row g-3">
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
                <label for="urgency" class="form-label">Filter by Urgency</label>
                <select id="urgency" name="urgency" class="form-select">
                    <option value="">All</option>
                    <option value="Low" <?php echo $filter_urgency === 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $filter_urgency === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo $filter_urgency === 'High' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
            <div class="col-12 text-center pb-4">
                <button type="submit" class="btn btn-success">Apply Filters</button>
                <a href="index.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <div class="center-content" id="map"></div>
    

    <script>
        var map = L.map('map').setView([43.66127272915081, -79.38768514171629], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

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

    var displayedReports = <?php echo json_encode($displayed_reports); ?>;

    displayedReports.forEach(function(report) {
        var icon = customIcons[report.report_type.toLowerCase()] || L.icon({
            iconUrl: 'https://img.icons8.com/ios-filled/50/000000/marker.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        });

        var marker = L.marker([report.latitude, report.longitude], { icon: icon }).addTo(map);

        marker.bindTooltip(
            `<strong>Problem #${report.id}</strong><br>${report.description}<br><strong>Type:</strong> ${report.report_type}<br><strong>Status:</strong> ${report.status}<br><strong>Urgency:</strong> ${report.urgency}<br><strong>Created At:</strong> ${report.created_at}`,
            { permanent: false, direction: 'top' }
        );
    });

    map.on('click', function(e) {
        var coords = e.latlng;
        var url = "report.php?lat=" + coords.lat + "&lng=" + coords.lng;
        window.location.href = url;
    });
    
    </script>
</body>

</html>