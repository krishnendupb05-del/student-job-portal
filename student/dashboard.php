<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Student authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* 🔎 Fetch student profile safely */
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student profile not found.");
}

$profile = $result->fetch_assoc();
$stmt->close();

$student_id = $profile['id'];

/* 🔔 Fetch unread notifications count */
$notif_stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE student_id = ? AND is_read = 0");
$notif_stmt->bind_param("i", $student_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notif_data = $notif_result->fetch_assoc();
$unread_count = $notif_data['total'];
$notif_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>

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

/* Dashboard Card */
.dashboard-box {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.dashboard-box h2 {
  color: #4f46e5;
  margin-bottom: 10px;
}

.success-msg {
  color: green;
  font-weight: bold;
  margin-bottom: 15px;
}

/* Menu Buttons */
.menu {
  margin: 20px 0;
}

.menu a {
  display: inline-block;
  margin: 5px 10px 5px 0;
  padding: 8px 15px;
  background: #4f46e5;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-size: 14px;
  transition: 0.3s;
}

.menu a:hover {
  background: #4338ca;
}

.logout {
  background: #dc2626 !important;
}

.logout:hover {
  background: #b91c1c !important;
}

/* Notification Badge */
.badge {
  background: red;
  color: white;
  padding: 2px 6px;
  border-radius: 50%;
  font-size: 12px;
  margin-left: 5px;
}

/* Details Section */
.section-title {
  margin-top: 30px;
  margin-bottom: 15px;
  color: #4f46e5;
  font-weight: 600;
  border-bottom: 2px solid #eef2ff;
  padding-bottom: 5px;
}

p {
  margin-bottom: 8px;
  font-size: 14px;
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
  <a href="logout.php">Logout</a>
</header>

<div class="container">
  <div class="dashboard-box">

    <h2>Student Dashboard</h2>
    <p>Welcome, <b><?php echo htmlspecialchars($profile['name']); ?></b></p>

    <?php if (isset($_GET['applied']) && $_GET['applied'] == 1) { ?>
        <p class="success-msg">✅ Applied successfully!</p>
    <?php } ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1) { ?>
        <p class="success-msg">✅ Profile updated successfully!</p>
    <?php } ?>

    <!-- Menu -->
    <div class="menu">
        <a href="jobs.php">View Jobs</a>
        <a href="my_applications.php">My Applications</a>
        <a href="notifications.php">
            Notifications
            <?php if ($unread_count > 0) { ?>
                <span class="badge"><?php echo $unread_count; ?></span>
            <?php } ?>
        </a>
        <a href="profile.php">Profile</a>
        
    </div>

    <div class="section-title">My Details</div>

    <p><strong>Name:</strong> <?php echo htmlspecialchars($profile['name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
    <p><strong>Age:</strong> <?php echo htmlspecialchars($profile['age']); ?></p>
    <p><strong>District:</strong> <?php echo htmlspecialchars($profile['district']); ?></p>
    <p><strong>Town:</strong> <?php echo htmlspecialchars($profile['town']); ?></p>

    <br>
    <a href="edit_profile.php" class="menu">Edit Profile</a>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Empowering Students & Careers</p>
</footer>

</body>
</html>