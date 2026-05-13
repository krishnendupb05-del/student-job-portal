<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* DEBUG */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* LOGIN CHECK */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../employer/login.php");
    exit();
}

/* Get logged-in user_id */
$user_id = $_SESSION['user_id'];

/* Fetch employer profile */
$sql = "SELECT id, state, district, town FROM employers WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employer profile not found.");
}

$profile = $result->fetch_assoc();

$employer_id = $profile['id'];
$state    = $profile['state'];
$district = $profile['district'];
$town     = $profile['town'];

$stmt->close();

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $job_name = $_POST['job_name'] ?? '';
    $hours    = $_POST['hours'] ?? '';
    $skills   = $_POST['skills'] ?? '';
    $urgency  = $_POST['urgency'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $salary   = $_POST['salary'] ?? '';

    $status = "open";

    /* Insert Job */
    $sql = "INSERT INTO jobs 
            (employer_id, job_name, hours, skills, urgency, due_date, salary, status, state, district, town) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Insert prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "issssssssss",
        $employer_id,
        $job_name,
        $hours,
        $skills,
        $urgency,
        $due_date,
        $salary,
        $status,
        $state,
        $district,
        $town
    );

    if ($stmt->execute()) {

        /* 🔔 GET LAST INSERTED JOB ID */
        $job_id = $stmt->insert_id;
        $stmt->close();

        /* 🔔 Find students in same location */
        $student_sql = "SELECT id FROM students 
                        WHERE state = ? AND district = ? AND town = ?";
        $student_stmt = $conn->prepare($student_sql);

        if ($student_stmt) {

            $student_stmt->bind_param("sss", $state, $district, $town);
            $student_stmt->execute();
            $students = $student_stmt->get_result();

            /* 🔔 Insert notification for each student */
            while ($student = $students->fetch_assoc()) {

                $student_id = $student['id'];
                $message = "New job in your area. Go to View Jobs.";
                $link = "jobs.php";

                $notif_sql = "INSERT INTO notifications (student_id, message, link) 
                              VALUES (?, ?, ?)";
                $notif_stmt = $conn->prepare($notif_sql);

                if ($notif_stmt) {
                    $notif_stmt->bind_param("iss", $student_id, $message, $link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }

            $student_stmt->close();
        }

        header("Location: dashboard.php?posted=1");
        exit();

    } else {
        echo "<p style='color:red'>DB Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Post Job</title>

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
.input-group textarea,
.input-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  outline: none;
  font-size: 14px;
}

.input-group textarea {
  resize: vertical;
}

.input-group input:focus,
.input-group textarea:focus,
.input-group select:focus {
  border-color: #4f46e5;
}

/* Location Info */
.location-box {
  background: #eef2ff;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
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
  <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
  <div class="form-box">
    <h2>Post a Job</h2>

    <form method="POST">

      <div class="input-group">
        <label>Job Name</label>
        <input type="text" name="job_name" required>
      </div>

      <div class="input-group">
        <label>Working Hours</label>
        <input type="text" name="hours" required>
      </div>

      <div class="input-group">
        <label>Salary</label>
        <input type="text" name="salary" placeholder="e.g. 15000/month" required>
      </div>

      <div class="input-group">
        <label>Required Skills</label>
        <textarea name="skills" rows="4" required></textarea>
      </div>

      <div class="input-group">
        <label>Urgency</label>
        <select name="urgency" required>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
        </select>
      </div>

      <div class="input-group">
        <label>Due Date</label>
        <input type="date" name="due_date" required>
      </div>

      <div class="location-box">
        <strong>Location (from your profile):</strong><br>
        State: <?= htmlspecialchars($state); ?> |
        District: <?= htmlspecialchars($district); ?> |
        Town: <?= htmlspecialchars($town); ?>
      </div>

      <button type="submit">Post Job</button>

    </form>
  </div>
</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>