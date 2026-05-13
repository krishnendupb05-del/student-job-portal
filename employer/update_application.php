<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    die("Invalid request");
}

$app_id = intval($_GET['id']);
$status = $_GET['status'];

/* 1️⃣ Update application status */
$update = $conn->prepare("UPDATE applications SET status=? WHERE application_no=?");
$update->bind_param("si", $status, $app_id);

if (!$update->execute()) {
    die("Update failed: " . $conn->error);
}
$update->close();

/* 2️⃣ Get application info */
$stmt = $conn->prepare("
    SELECT a.student_id, j.job_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.application_no = ?
");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Application not found.");
}

$data = $result->fetch_assoc();
$user_id = $data['student_id'];  // THIS is users.id
$job_name = $data['job_name'];
$stmt->close();

/* 3️⃣ Convert users.id → students.id */
$student_stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows == 0) {
    die("Student record not found.");
}

$student = $student_result->fetch_assoc();
$student_id = $student['id'];
$student_stmt->close();

/* 4️⃣ Prepare notification message */
if ($status === "accepted") {
    $message = "Your application is accepted for the job $job_name";
} else {
    $message = "Your application for $job_name has been rejected";
}

/* 5️⃣ Insert notification */
$notif = $conn->prepare("
    INSERT INTO notifications (student_id, message, link, is_read)
    VALUES (?, ?, ?, 0)
");
$link = "my_applications.php";
$notif->bind_param("iss", $student_id, $message, $link);

if (!$notif->execute()) {
    die("Notification insert failed: " . $conn->error);
}
$notif->close();

header("Location: applications.php");
exit();