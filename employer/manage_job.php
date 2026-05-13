<?php
session_start();
require_once __DIR__ . '/../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* LOGIN CHECK */
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

/* Fetch jobs */
$sql = "SELECT * FROM jobs WHERE employer_id = ? ORDER BY job_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Manage Jobs</title>

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
  padding: 60px 8%;
}

/* Card */
.card {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  overflow-x: auto;
}

.card h2 {
  margin-bottom: 20px;
  color: #4f46e5;
}

/* Success Message */
.success {
  background: #dcfce7;
  color: #166534;
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 20px;
}

/* Table */
table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #4f46e5;
  color: white;
  padding: 10px;
  text-align: left;
}

td {
  padding: 10px;
  border-bottom: 1px solid #eee;
}

tr:hover {
  background: #f1f5ff;
}

/* Buttons */
.action-btn {
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
}

.edit-btn {
  background: #4f46e5;
  color: white;
}

.edit-btn:hover {
  background: #4338ca;
}

.delete-btn {
  background: #dc2626;
  color: white;
}

.delete-btn:hover {
  background: #b91c1c;
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
  <div class="card">

    <h2>Manage Jobs</h2>

    <?php if (isset($_GET['deleted'])) { ?>
      <div class="success">Job deleted successfully!</div>
    <?php } ?>

    <?php if ($result->num_rows > 0) { ?>

      <table>
        <tr>
          <th>Job ID</th>
          <th>Job Name</th>
          <th>Skills</th>
          <th>Hours</th>
          <th>Salary</th>
          <th>Urgency</th>
          <th>Due Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
          <td><?= $row['job_id']; ?></td>
          <td><?= htmlspecialchars($row['job_name']); ?></td>
          <td><?= htmlspecialchars($row['skills']); ?></td>
          <td><?= htmlspecialchars($row['hours']); ?></td>
          <td><?= htmlspecialchars($row['salary']); ?></td>
          <td><?= htmlspecialchars($row['urgency']); ?></td>
          <td><?= htmlspecialchars($row['due_date']); ?></td>
          <td><?= htmlspecialchars($row['status']); ?></td>
          <td>
            <a class="action-btn edit-btn" href="edit_job.php?id=<?= $row['job_id']; ?>">Edit</a>
            <a class="action-btn delete-btn" 
               href="delete_job.php?id=<?= $row['job_id']; ?>"
               onclick="return confirm('Are you sure you want to delete this job?');">
               Delete
            </a>
          </td>
        </tr>
        <?php } ?>

      </table>

    <?php } else { ?>
      <p>No jobs posted yet.</p>
    <?php } ?>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>