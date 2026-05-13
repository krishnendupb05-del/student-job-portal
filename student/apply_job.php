<?php
session_start();
include("../config/db.php");

/* 🔐 Student Authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['job_id'])) {
    die("Job not specified.");
}

$job_id = intval($_GET['job_id']);
$user_id = $_SESSION['user_id'];

// 🔹 Get actual student ID from students table
$studentQuery = "SELECT id FROM students WHERE user_id = '$user_id'";
$studentResult = mysqli_query($conn, $studentQuery);
$student = mysqli_fetch_assoc($studentResult);

if (!$student) {
    die("Student record not found.");
}

$student_id = $student['id'];

// ✅ Check if already applied
$checkQuery = "
    SELECT * FROM applications 
    WHERE job_id = '$job_id' 
    AND student_id = '$student_id'
";

$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    echo "<script>
        alert('You have already applied for this job.');
        window.location='jobs.php';
    </script>";
    exit();
}

// ✅ Insert new application
$insertQuery = "
    INSERT INTO applications (job_id, student_id, applied_at, status)
    VALUES ('$job_id', '$student_id', NOW(), 'pending')
";

if (mysqli_query($conn, $insertQuery)) {
    echo "<script>
        alert('Application submitted successfully!');
        window.location='jobs.php';
    </script>";
} else {
    die("Error submitting application: " . mysqli_error($conn));
}
?>