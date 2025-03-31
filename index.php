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

    <div class="center-content" id="map"></div>
    

    <script>
        var map = L.map('map').setView([43.66127272915081, -79.38768514171629], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);


    

    map.on('click', function(e) {
        var coords = e.latlng;
        var url = "report.php?lat=" + coords.lat + "&lng=" + coords.lng;
        window.location.href = url;
    });
    
    </script>
</body>

</html>