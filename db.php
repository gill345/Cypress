<?php
    $host = 'sql309.infinityfree.com';
    $user = 'if0_38581672';
    $pass = 'TTmXtGSSKgU3';
    $name = 'if0_38581672_cypress';

    $conn = mysqli_connect($host, $user, $pass, $name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
?>