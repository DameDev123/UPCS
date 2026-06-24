<?php
// Database configuration
$servername = "sql205.infinityfree.com";
$port       = 3306;
$username   = "if0_42250571";
$password   = "Dame2030";
$dbname     = "if0_42250571_upcs";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
