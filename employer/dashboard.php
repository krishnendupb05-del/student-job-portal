<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Employer authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../employer/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch employer profile safely */
$stmt = $conn->prepare("SELECT * FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employer profile not found.");
}

$profile = $result->fetch_assoc();
$stmt->close();

$employer_id = $profile['id'];

/* ============================= */
/* ⏳ AUTO EXPIRE JOBS */
/* ============================= */

$expireQuery = $conn->prepare("
    UPDATE jobs 
    SET status = 'expired'
    WHERE employer_id = ?
      AND due_date < CURDATE()
      AND status = 'open'
");
$expireQuery->bind_param("i", $employer_id);
$expireQuery->execute();
$expireQuery->close();

/* ============================= */
/* 🔴 Get Pending Application Count */
/* ============================= */

$pendingQuery = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE j.employer_id = ?
      AND (a.status IS NULL OR a.status = 'Pending')
");

$pendingQuery->bind_param("i", $employer_id);
$pendingQuery->execute();
$pendingResult = $pendingQuery->get_result();
$pendingCount = 0;

if ($pendingResult->num_rows > 0) {
    $data = $pendingResult->fetch_assoc();
    $pendingCount = $data['total'];
}

$pendingQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Employer Dashboard</title>

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

.container {
  padding: 60px 8%;
}

.welcome {
  margin-bottom: 25px;
  font-size: 18px;
}

.menu {
  margin-bottom: 30px;
}

.menu a {
  display: inline-block;
  margin: 10px 15px 10px 0;
  padding: 10px 18px;
  text-decoration: none;
  background: #4f46e5;
  color: white;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
}

.menu a:hover {
  background: #4338ca;
}

.logout {
  background: #dc2626 !important;
}

.badge {
  background: red;
  color: white;
  padding: 3px 8px;
  border-radius: 50%;
  font-size: 12px;
  margin-left: 6px;
}

.card {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  max-width: 600px;
}

.card h3 {
  margin-bottom: 20px;
  color: #4f46e5;
}

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
  <a href="logout.php">Logout</a>
</header>

<div class="container">

  <div class="welcome">
    Welcome, <strong><?php echo htmlspecialchars($profile['shop_name']); ?></strong>
  </div>

  <div class="menu">
    <a href="post_job.php">Post Job</a>
    <a href="manage_job.php">Manage Jobs</a>
    <a href="applications.php">
        View Applications 
        <?php if($pendingCount > 0){ ?>
            <span class="badge"><?php echo $pendingCount; ?></span>
        <?php } ?>
    </a>
    <a href="profile.php">Profile</a>
  </div>

  <div class="card">
    <h3>Employer Details</h3>

    <p><strong>Shop Name:</strong> 
       <?php echo htmlspecialchars($profile['shop_name']); ?></p>

    <p><strong>Email:</strong> 
       <?php echo htmlspecialchars($profile['email']); ?></p>

    <p><strong>District:</strong> 
       <?php echo htmlspecialchars($profile['district']); ?></p>

    <p><strong>Town:</strong> 
       <?php echo htmlspecialchars($profile['town']); ?></p>

    <br>

    <a href="edit_profile.php" style="color:#4f46e5; font-weight:500;">
        Edit Profile
    </a>
  </div>

</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>