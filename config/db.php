<?php
$servername = "localhost";   // or 127.0.0.1
$username = "root";
$password = "";               // no password
$database = "student_job_portal";
$port = 3307;                 // your MySQL port

$conn = mysqli_connect($servername, $username, $password, $database, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
