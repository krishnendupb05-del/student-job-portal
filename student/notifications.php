<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get student ID */
$studentQuery = mysqli_query($conn, "SELECT id FROM students WHERE user_id='$user_id'");
$student = mysqli_fetch_assoc($studentQuery);
$student_id = $student['id'];

/* Mark all as read */
mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE student_id=$student_id");

/* Fetch notifications */
$result = mysqli_query($conn,
    "SELECT * FROM notifications 
     WHERE student_id=$student_id 
     ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Notifications</title>

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
    margin-bottom: 20px;
}

/* Headings */
.card h2 {
    color: #4f46e5;
    margin-bottom: 20px;
}

/* Notification items */
.notification {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification.unread {
    background-color: #f1f5ff;
    font-weight: bold;
}

.notification a {
    color: #4f46e5;
    text-decoration: none;
    font-weight: 500;
    margin-left: 15px;
}

.notification a:hover {
    text-decoration: underline;
}

/* Footer */
footer {
    text-align: center;
    padding: 20px;
    background: #e0e7ff;
    margin-top: 60px;
}

/* Responsive */
@media(max-width: 600px){
    .card {
        padding: 20px;
    }
    .notification {
        flex-direction: column;
        align-items: flex-start;
    }
    .notification a {
        margin: 5px 0 0 0;
    }
}
</style>
</head>
<body>

<header>
    <h1>HireHub</h1>
    <a href="dashboard.php"> Back to Dashboard</a>
</header>

<div class="container">
    <div class="card">
        <h2>Notifications</h2>

        <?php if(mysqli_num_rows($result) > 0) { ?>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                <div class="notification <?php if(!$row['is_read']) echo 'unread'; ?>">
                    <div>
                        <?php echo htmlspecialchars($row['message']); ?><br>
                        <small><?php echo $row['created_at']; ?></small>
                    </div>
                    <?php if($row['link']) { ?>
                        <a href="<?php echo $row['link']; ?>">View</a>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No notifications available.</p>
        <?php } ?>

    </div>
</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>