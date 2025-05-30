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

if (isset($_GET['clear_filters'])) {
    setcookie('filter_status', '', time() - 3600, '/', '', true, true);
    setcookie('filter_type', '', time() - 3600, '/', '', true, true);
    setcookie('filter_urgency', '', time() - 3600, '/', '', true, true);
    setcookie('filter_time', '', time() - 3600, '/', '', true, true);
    setcookie('filter_subscription', '', time() - 3600, '/', '', true, true);
    header('Location: index.php');
    exit();
}


if (empty($_GET) || (count($_GET) === 1 && isset($_GET['report']))) {
    setcookie('filter_status', '', time() - 3600, '/', '', true, true);
    setcookie('filter_type', '', time() - 3600, '/', '', true, true);
    setcookie('filter_urgency', '', time() - 3600, '/', '', true, true);
    setcookie('filter_time', '', time() - 3600, '/', '', true, true);
    setcookie('filter_subscription', '', time() - 3600, '/', '', true, true);
}


$filter_status = isset($_GET['status']) ? $_GET['status'] : (isset($_COOKIE['filter_status']) ? $_COOKIE['filter_status'] : '');
$filter_type = isset($_GET['type']) ? $_GET['type'] : (isset($_COOKIE['filter_type']) ? $_COOKIE['filter_type'] : '');
$filter_urgency = isset($_GET['urgency']) ? $_GET['urgency'] : (isset($_COOKIE['filter_urgency']) ? $_COOKIE['filter_urgency'] : '');
$filter_time = isset($_GET['time']) ? $_GET['time'] : (isset($_COOKIE['filter_time']) ? $_COOKIE['filter_time'] : '');
$filter_subscription = isset($_GET['subscription']) ? $_GET['subscription'] : (isset($_COOKIE['filter_subscription']) ? $_COOKIE['filter_subscription'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['status'])) {
        setcookie('filter_status', $_GET['status'], time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    if (isset($_GET['type'])) {
        setcookie('filter_type', $_GET['type'], time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    if (isset($_GET['urgency'])) {
        setcookie('filter_urgency', $_GET['urgency'], time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    if (isset($_GET['time'])) {
        setcookie('filter_time', $_GET['time'], time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    if (isset($_GET['subscription'])) {
        setcookie('filter_subscription', $_GET['subscription'], time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
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

$query = "SELECT cr.id, cr.description, cr.report_type, cr.latitude, cr.longitude, cr.status, 
          cr.created_at, cr.urgency, cr.user_id, cr.contact_info, u.email as user_email,
          (SELECT COUNT(*) FROM report_subscriptions rs WHERE rs.report_id = cr.id AND rs.user_id = ?) as is_subscribed
          FROM city_reports cr 
          LEFT JOIN users u ON cr.user_id = u.id 
          WHERE cr.status != 'Submitted'";

if (!empty($filter_status)) {
    $query .= " AND cr.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($filter_type)) {
    $query .= " AND cr.report_type = '" . $conn->real_escape_string($filter_type) . "'";
}
if (!empty($filter_urgency)) {
    $query .= " AND cr.urgency = '" . $conn->real_escape_string($filter_urgency) . "'";
}
if (!empty($filter_subscription)) {
    if ($filter_subscription === 'subscribed') {
        $query .= " AND EXISTS (SELECT 1 FROM report_subscriptions rs WHERE rs.report_id = cr.id AND rs.user_id = ?)";
    } else if ($filter_subscription === 'unsubscribed') {
        $query .= " AND NOT EXISTS (SELECT 1 FROM report_subscriptions rs WHERE rs.report_id = cr.id AND rs.user_id = ?)";
    }
}
if (!empty($filter_time)) {
    $time_condition = '';
    switch ($filter_time) {
        case 'last_hour':
            $time_condition = "AND cr.created_at >= NOW() - INTERVAL 1 HOUR";
            break;
        case 'last_day':
            $time_condition = "AND cr.created_at >= NOW() - INTERVAL 1 DAY";
            break;
        case 'last_week':
            $time_condition = "AND cr.created_at >= NOW() - INTERVAL 1 WEEK";
            break;
        case 'last_month':
            $time_condition = "AND cr.created_at >= NOW() - INTERVAL 1 MONTH";
            break;
    }
    $query .= " $time_condition";
}

$stmt = $conn->prepare($query);


if (!empty($filter_subscription)) {
    if ($filter_subscription === 'subscribed') {
        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
    } else if ($filter_subscription === 'unsubscribed') {
        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
    }
} else {
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

$displayed_reports = [];
while ($row = $result->fetch_assoc()) {
    $displayed_reports[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Cypress</title>
    <link rel="icon" type="image/x-icon" href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
    
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
   
    <!-- Place your Google Maps API key here in the src attribute-->
    <script src=""></script>

    <style>
        body {
            background: linear-gradient(135deg, #eceff1 0%, #cfd8dc 100%);
            margin: 0;
            padding: 0;
            font-family: 'Roboto', 'Segoe UI', sans-serif;
            color: #263238;
        }

        .navbar {
            background: linear-gradient(to right, #0288d1, #0277bd);
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: #fff !important;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-right: 2rem;
            transition: transform 0.2s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.03);
        }

        .navbar-brand img {
            margin-right: 8px;
            height: 32px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            margin-top: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .btn {
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #0288d1;
            border: none;
        }

        .btn-primary:hover {
            background: #0277bd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 136, 209, 0.3);
        }

        .btn-warning {
            background: #ffb300;
            border: none;
            color: #fff;
        }

        .btn-warning:hover {
            background: #ffa000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 179, 0, 0.3);
        }

        .btn-danger {
            background: #d32f2f;
            border: none;
        }

        .btn-danger:hover {
            background: #c62828;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .btn-success {
            background: #2e7d32;
            border: none;
        }

        .btn-success:hover {
            background: #27632a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .btn-secondary {
            background: #78909c;
            border: none;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #607d8b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(120, 144, 156, 0.3);
        }

        .search-container {
            max-width: 700px;
            margin: 25px auto;
        }

        #location-search {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        #location-search:focus {
            box-shadow: 0 4px 12px rgba(2, 136, 209, 0.2);
            outline: none;
        }

        h1.text-primary {
            color: #0277bd;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        p.h5 {
            color: #455a64;
            font-size: 1.2rem;
            line-height: 1.7;
            max-width: 800px;
            margin: 0 auto;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: none;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 500px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-select {
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .form-select:focus {
            box-shadow: 0 4px 12px rgba(2, 136, 209, 0.2);
            outline: none;
        }

        .form-label {
            font-weight: 500;
            color: #37474f;
        }

        .center-content {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }

        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .filter-group {
            flex: 1;
            min-width: 180px;
        }
        .filter-group .form-label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #495057;
        }
        .filter-group .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: white;
            font-size: 0.9rem;
        }
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .btn {
            padding: 8px 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg text-dark">
        <div class="container">
            <a class="navbar-brand link-dark" href="index.php" style="color: black !important;">
                <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" alt="Logo">
                Project Cypress
            </a>
            <div class="navbar-nav ms-auto">
                <form method="post" class="d-flex gap-3 align-items-center">
                    <a href="index.php?admin_mode=true" class="btn btn-warning px-4 py-2">Admin Mode</a>
                    <button type="submit" name="sign_out" class="btn btn-danger px-4 py-2">Sign Out</button>
                </form>
            </div>
        </div>
    </nav>

    <?php if (isset($_GET['report']) && $_GET['report'] === 'submitted'): ?>
        <div class="alert alert-success text-center" role="alert">
            Report submitted successfully!
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="text-center mb-4">
            <h1 class="h1 text-primary">Cypress</h1>
            <p class="h5">Cypress is a community-driven platform for reporting and tracking public issues on a Toronto map. Users can create alerts for problems like potholes or broken streetlights, while city workers can update and resolve them in real time.</p>
        </div>

        <form method="GET" action="index.php" class="filter-form mb-4">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="In Progress" <?php echo $filter_status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="type" class="form-label">Type</label>
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
                <div class="filter-group">
                    <label for="urgency" class="form-label">Urgency</label>
                    <select id="urgency" name="urgency" class="form-select">
                        <option value="">All</option>
                        <option value="Low" <?php echo $filter_urgency === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $filter_urgency === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $filter_urgency === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="time" class="form-label">Time</label>
                    <select id="time" name="time" class="form-select">
                        <option value="">All</option>
                        <option value="last_hour" <?php echo $filter_time === 'last_hour' ? 'selected' : ''; ?>>Last Hour</option>
                        <option value="last_day" <?php echo $filter_time === 'last_day' ? 'selected' : ''; ?>>Last Day</option>
                        <option value="last_week" <?php echo $filter_time === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                        <option value="last_month" <?php echo $filter_time === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="subscription" class="form-label">Notification Status</label>
                    <select id="subscription" name="subscription" class="form-select">
                        <option value="" selected>All</option>
                        <option value="subscribed" <?php echo $filter_subscription === 'subscribed' ? 'selected' : ''; ?>>Subscribed</option>
                        <option value="unsubscribed" <?php echo $filter_subscription === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                    </select>
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn btn-success me-2">Apply Filters</button>
                <a href="index.php?clear_filters=true" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>

        <div class="search-container mt-4">
            <input type="text" id="location-search" class="form-control" placeholder="Search for a location...">
        </div>

        <div class="center-content">
            <div id="map"></div>
        </div>
    </div>

    <script>
        var map = L.map('map').setView([43.66127272915081, -79.38768514171629], 12);
        var userLocationMarker = null;
        var searchLocationMarker = null;
        
       
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        
        const searchInput = document.getElementById('location-search');
        const autocomplete = new google.maps.places.Autocomplete(searchInput, {
            componentRestrictions: { country: "ca" },
            fields: ["formatted_address", "geometry", "name"],
            strictBounds: false,
            types: ["geocode", "establishment"]
        });

        
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                console.error("No location data for this place");
                return;
            }
            
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            
            
            if (searchLocationMarker) {
                map.removeLayer(searchLocationMarker);
            }
            
            
            searchLocationMarker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                })
            }).addTo(map);
            
           
            map.setView([lat, lng], 15);
        });

        
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                
                userLocationMarker = L.marker([userLat, userLng], {
                    icon: L.divIcon({
                        className: 'user-location-marker',
                        html: '<div style="background-color: #2196F3; border: 2px solid white; border-radius: 50%; width: 15px; height: 15px; box-shadow: 0 0 3px rgba(0,0,0,0.3);"></div>'
                    })
                }).addTo(map);
                
                map.setView([userLat, userLng], 13);
            });
        }

        
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

            
            marker.bindTooltip(`Problem #${report.id}: ${report.description}`, { 
                permanent: false, 
                direction: 'top',
                offset: [0, -30]
            });

          
            marker.bindPopup(
                `<strong>Problem #${report.id}</strong><br>
                ${report.description}<br>
                <strong>Type:</strong> ${report.report_type}<br>
                <strong>Status:</strong> ${report.status}<br>
                <strong>Urgency:</strong> ${report.urgency}<br>
                <strong>Created At:</strong> ${report.created_at}<br>
                <strong>Notification:</strong> 
                <button class="btn btn-sm ${report.is_subscribed > 0 ? 'btn-danger' : 'btn-success'}" 
                        onclick="toggleNotification(${report.id}, ${report.is_subscribed > 0}, '${report.user_email}', '${report.contact_info}')">
                    ${report.is_subscribed > 0 ? 'Unsubscribe' : 'Subscribe'}
                </button>`,
                { 
                    closeButton: true,
                    closeOnClick: true,
                    autoClose: false
                }
            );
        });

       
        map.on('click', function(e) {
            var coords = e.latlng;
            var url = "report.php?lat=" + coords.lat + "&lng=" + coords.lng;
            window.location.href = url;
        });

        function toggleNotification(reportId, currentStatus, userEmail, contactInfo) {
            if (!currentStatus) {
                
                const email = prompt("Enter email for notifications:", contactInfo || userEmail || "");
                if (!email) return; 
                
                if (!validateEmail(email)) {
                    alert("Please enter a valid email address");
                    return;
                }
                
                updateSubscription(reportId, true, email);
            } else {
               
                if (confirm("Are you sure you want to unsubscribe from notifications for this problem?")) {
                    updateSubscription(reportId, false);
                }
            }
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function updateSubscription(reportId, subscribe, email = null) {
            const formData = new FormData();
            formData.append('action', 'update_subscription');
            formData.append('report_id', reportId);
            formData.append('subscribe', subscribe ? 1 : 0);
            if (email) formData.append('email', email);

            fetch('update_subscription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); 
                } else {
                    alert(data.message || 'Failed to update subscription');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update subscription');
            });
        }
    </script>
</body>
</html>