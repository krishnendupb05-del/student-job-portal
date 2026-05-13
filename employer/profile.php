<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Employer authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch employer profile */
$stmt = $conn->prepare("SELECT * FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employer profile not found.");
}

$profile = $result->fetch_assoc();
$stmt->close();

/* Handle image upload */
if (isset($_POST['upload_image'])) {

    if (!empty($_FILES['profile_image']['name'])) {

        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {

            $update = $conn->prepare("UPDATE employers SET profile_image = ? WHERE user_id = ?");
            $update->bind_param("si", $file_name, $user_id);
            $update->execute();
            $update->close();

            header("Location: profile.php");
            exit();
        } else {
            $upload_error = "Failed to upload image.";
        }
    }
}

/* ⭐ Fetch student reviews for this employer */
$review_stmt = $conn->prepare("
    SELECT r.rating, r.feedback, r.created_at, s.name
    FROM employer_reviews r
    JOIN students s ON r.student_id = s.id
    WHERE r.employer_id = ?
    ORDER BY r.created_at DESC
");
$review_stmt->bind_param("i", $profile['id']);
$review_stmt->execute();
$reviews = $review_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Employer Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f3f4f6;
}

/* Header */
header {
    background: linear-gradient(to right, #5b4bdb, #4f46e5);
    color: white;
    padding: 25px 8%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h1 {
    font-size: 28px;
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

/* Main Container */
.container {
    padding: 60px 8%;
}

/* Profile Card */
.profile-card {
    background: #f9fafb;
    padding: 40px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}

/* Top Section */
.profile-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-top h2 {
    font-size: 24px;
    color: #4f46e5;
}

/* Edit Button */
.edit-btn {
    background: #4f46e5;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

.edit-btn:hover {
    background: #4338ca;
}

/* Section Title */
.section-title {
    margin-top: 30px;
    margin-bottom: 15px;
    color: #4f46e5;
    font-weight: 600;
    font-size: 18px;
    border-bottom: 1px solid #d1d5db;
    padding-bottom: 8px;
}

/* Info Text */
.info p {
    margin-bottom: 10px;
    font-size: 14px;
}

.info strong {
    font-weight: 600;
}

/* Review Box */
.review-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #e5e7eb;
}

.review-box strong {
    color: #4f46e5;
}

.review-box small {
    color: gray;
    font-size: 12px;
}

/* Profile Image */
.profile-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 20px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<!-- Header -->
<header>
    <h1>HireHub</h1>
    <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
    <div class="profile-card">

        <!-- Top Title -->
        <div class="profile-top">
            <h2>Profile: <?php echo htmlspecialchars($profile['shop_name']); ?></h2>
            <a href="edit_profile.php" class="edit-btn">Edit Profile</a>
        </div>

        <!-- Personal Details -->
        <div class="section-title">Personal Details</div>

        <?php if (!empty($profile['profile_image'])): ?>
            <img src="../uploads/<?php echo htmlspecialchars($profile['profile_image']); ?>" class="profile-img">
        <?php endif; ?>

        <div class="info">
            <p><strong>Shop Name:</strong> <?php echo htmlspecialchars($profile['shop_name']); ?></p>
            <p><strong>Owner Name:</strong> <?php echo htmlspecialchars($profile['owner_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
            <p><strong>District:</strong> <?php echo htmlspecialchars($profile['district']); ?></p>
            <p><strong>Town:</strong> <?php echo htmlspecialchars($profile['town']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
        </div>

        <!-- Reviews Section -->
        <div class="section-title">Student Reviews</div>

        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($row = $reviews->fetch_assoc()): ?>
                <div class="review-box">
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($row['name']); ?></p>
                    <p><strong>Rating:</strong> <?php echo htmlspecialchars($row['rating']); ?> / 5</p>
                    <p><strong>Feedback:</strong> <?php echo htmlspecialchars($row['feedback']); ?></p>
                    <small>Reviewed on: <?php echo htmlspecialchars($row['created_at']); ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>