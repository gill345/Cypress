<?php

    // Database connection details
    // Replace with your actual database credentials
    $host = "";
    $user = "";
    $pass = "";
    $name = ""; 

    $conn = mysqli_connect($host, $user, $pass, $name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
?>