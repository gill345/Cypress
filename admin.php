<?php
require_once 'db.php';
session_start();


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
            echo json_encode(['success' => true, 'message' => "Problem #$problem_id status set to: $new_status"]);
            exit();
        }

        if ($action === 'delete') {
            $delete_query = "DELETE FROM city_reports WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => "Problem #$problem_id has been deleted"]);
            exit();
        }

        if ($action === 'accept_with_duplicates') {
            $duplicates = isset($_POST['duplicates']) ? json_decode($_POST['duplicates'], true) : [];
            $duplicates = array_map('intval', $duplicates);

            $update_query = "UPDATE city_reports SET status = 'In Progress' WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $problem_id);
            $stmt->execute();
            $stmt->close();

            if (!empty($duplicates)) {
                $delete_query = "DELETE FROM city_reports WHERE id IN (" . implode(',', $duplicates) . ")";
                $conn->query($delete_query);
            }

            echo json_encode(['success' => true, 'message' => "Problem #$problem_id accepted and duplicates removed"]);
            exit();
        }
    }
}

$query = "SELECT city_reports.id, city_reports.description, city_reports.report_type, city_reports.latitude, city_reports.longitude, city_reports.contact_info, city_reports.status, city_reports.created_at, city_reports.urgency, users.name AS submitted_by 
          FROM city_reports 
          LEFT JOIN users ON city_reports.user_id = users.id";
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
        <?php foreach ($grouped_reports as $group): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Problem #<?php echo htmlspecialchars($group['id']); ?></h5>
                    <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($group['description']); ?></p>
                    <p class="card-text"><strong>Type:</strong> <?php echo htmlspecialchars($group['report_type']); ?></p>
                    <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($group['status']); ?></p>
                    <p class="card-text"><strong>Urgency:</strong> <?php echo htmlspecialchars($group['urgency']); ?></p>
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
                            <ul>
                                <?php foreach ($group['duplicates'] as $duplicate): ?>
                                    <li>Duplicate Problem #<?php echo htmlspecialchars($duplicate['id']); ?>: <?php echo htmlspecialchars($duplicate['description']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button class="btn btn-primary" onclick="acceptWithDuplicates(<?php echo $group['id']; ?>, <?php echo htmlspecialchars(json_encode(array_column($group['duplicates'], 'id'))); ?>)">Accept and Remove Duplicates</button>
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

        function acceptWithDuplicates(id, duplicates) {
            fetch('admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'accept_with_duplicates',
                    problem_id: id,
                    duplicates: JSON.stringify(duplicates)
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
    </script>
</body>
</html>