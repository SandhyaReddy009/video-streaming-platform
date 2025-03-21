<?php
$host = 'localhost'; // Your database host
$dbname = 'sss';   // Your database name
$username = 'root';  // Your database username
$password = '';      // Your database password

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>