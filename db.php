<?php
$host = 'localhost';
$db   = 'database';  // Make sure this matches your PHPMyAdmin Database name
$user = 'root';
$pass = '';       // Default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
?>