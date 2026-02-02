<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student'){
    header("Location: ../auth/login.php");
    exit;
}
?>

<h2>Student Dashboard</h2>
<a href="jobs.php">View Nearby Jobs</a>
