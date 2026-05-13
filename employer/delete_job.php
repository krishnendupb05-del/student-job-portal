<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* Show errors (for debugging) */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Check login and role */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../employer/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get employer_id */
$stmt = $conn->prepare("SELECT id FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employer profile not found.");
}

$employer = $result->fetch_assoc();
$employer_id = $employer['id'];
$stmt->close();

/* Validate job ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_job.php");
    exit();
}

$job_id = intval($_GET['id']);

/* ✅ FIXED HERE — using job_id */
$stmt = $conn->prepare("DELETE FROM jobs WHERE job_id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);

if ($stmt->execute()) {

    /* Delete related applications */
    $stmt2 = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
    $stmt2->bind_param("i", $job_id);
    $stmt2->execute();
    $stmt2->close();

    $stmt->close();

    header("Location: manage_job.php?deleted=1");
    exit();

} else {
    echo "Error deleting job: " . $stmt->error;
}
?>
