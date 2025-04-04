<?php
    $host = "localhost";
    $user = "stephen";
    $pass = "admin123";
    $name = "cypress"; 

    $conn = mysqli_connect($host, $user, $pass, $name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
?>