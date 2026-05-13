<?php
session_start();
require_once __DIR__ . '/../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Check login */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../employer/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get employer_id */
$stmt = $conn->prepare("SELECT id FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultEmp = $stmt->get_result();

if ($resultEmp->num_rows === 0) {
    die("Employer profile not found.");
}

$employer = $resultEmp->fetch_assoc();
$employer_id = $employer['id'];
$stmt->close();

/* Validate job ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_job.php");
    exit();
}

$job_id = intval($_GET['id']);

/* Fetch job */
$stmt = $conn->prepare("SELECT * FROM jobs WHERE job_id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Job not found or permission denied.");
}

$job = $result->fetch_assoc();
$stmt->close();

/* Handle Update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $job_name = trim($_POST['job_name']);
    $hours    = trim($_POST['hours']);
    $skills   = trim($_POST['skills']);
    $urgency  = trim($_POST['urgency']);
    $due_date = trim($_POST['due_date']);
    $salary   = trim($_POST['salary']);   // ✅ NEW

    if (empty($job_name) || empty($hours) || empty($skills) || 
        empty($urgency) || empty($due_date) || empty($salary)) {

        echo "<p style='color:red;'>All fields are required.</p>";

    } else {

        $stmt2 = $conn->prepare("
            UPDATE jobs
            SET job_name = ?, 
                hours = ?, 
                skills = ?, 
                urgency = ?, 
                due_date = ?, 
                salary = ?     /* ✅ NEW */
            WHERE job_id = ? AND employer_id = ?
        ");

        $stmt2->bind_param(
            "ssssssii",   // ✅ one extra s added
            $job_name,
            $hours,
            $skills,
            $urgency,
            $due_date,
            $salary,      // ✅ NEW
            $job_id,
            $employer_id
        );

        if ($stmt2->execute()) {
            header("Location: manage_job.php?updated=1");
            exit();
        } else {
            echo "Error updating job: " . $stmt2->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Edit Job</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}

body {
  background: linear-gradient(to right, #eef2ff, #f8fafc);
}

/* Header */
header {
  background: #4f46e5;
  color: white;
  padding: 20px 8%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

header h1 {
  font-weight: 600;
}

header a {
  color: #e0e7ff;
  text-decoration: none;
  font-weight: 500;
}

header a:hover {
  color: white;
}

/* Container */
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px 20px;
}

/* Form Card */
.form-box {
  background: white;
  padding: 40px;
  width: 100%;
  max-width: 600px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.form-box h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #4f46e5;
}

/* Input Group */
.input-group {
  margin-bottom: 20px;
}

.input-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}

.input-group input,
.input-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  outline: none;
  font-size: 14px;
}

.input-group input:focus,
.input-group select:focus {
  border-color: #4f46e5;
}

/* Button */
button {
  width: 100%;
  padding: 12px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
}

button:hover {
  background: #4338ca;
}

/* Footer */
footer {
  text-align: center;
  padding: 20px;
  background: #e0e7ff;
  margin-top: 60px;
}
</style>
</head>

<body>

<header>
  <h1>HireHub</h1>
  <a href="manage_job.php">Back to Manage Jobs</a>
</header>

<div class="container">
  <div class="form-box">

    <h2>Edit Job</h2>

    <form method="POST">

      <div class="input-group">
        <label>Job Name</label>
        <input type="text" name="job_name"
               value="<?= htmlspecialchars($job['job_name']) ?>" required>
      </div>

      <div class="input-group">
        <label>Working Hours</label>
        <input type="text" name="hours"
               value="<?= htmlspecialchars($job['hours']) ?>" required>
      </div>

      <div class="input-group">
        <label>Salary</label>
        <input type="text" name="salary"
               value="<?= htmlspecialchars($job['salary']) ?>" required>
      </div>

      <div class="input-group">
        <label>Skills</label>
        <input type="text" name="skills"
               value="<?= htmlspecialchars($job['skills']) ?>" required>
      </div>

      <div class="input-group">
        <label>Urgency</label>
        <select name="urgency" required>
          <option value="Low" <?= $job['urgency']=="Low"?"selected":""; ?>>Low</option>
          <option value="Medium" <?= $job['urgency']=="Medium"?"selected":""; ?>>Medium</option>
          <option value="High" <?= $job['urgency']=="High"?"selected":""; ?>>High</option>
        </select>
      </div>

      <div class="input-group">
        <label>Due Date</label>
        <input type="date" name="due_date"
               value="<?= htmlspecialchars($job['due_date']) ?>" required>
      </div>

      <button type="submit">Update Job</button>

    </form>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>