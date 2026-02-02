<?php
session_start();
include('../config/db.php'); // database connection

// Check login
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student location
$sq = mysqli_query($conn, "SELECT latitude, longitude FROM students WHERE user_id=$user_id");
$student = mysqli_fetch_assoc($sq);
$lat1 = $student['latitude'];
$lon1 = $student['longitude'];

// Get all open jobs
$jobs = mysqli_query($conn, "SELECT job_name, salary, latitude, longitude FROM jobs WHERE status='open'");

// Haversine formula function
function haversine($lat1, $lon1, $lat2, $lon2){
    $R = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

echo "<h2>Nearby Jobs (within 15 km)</h2>";

while($job = mysqli_fetch_assoc($jobs)){
    $dist = haversine($lat1, $lon1, $job['latitude'], $job['longitude']);
    if($dist <= 15){
        echo "<p>";
        echo "<b>{$job['job_name']}</b> – ₹{$job['salary']}<br>";
        echo "Distance: " . round($dist,2) . " km";
        echo "</p>";
    }
}
?>
