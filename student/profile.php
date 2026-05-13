<?php
session_start();
require_once("../config/db.php");

/* ===============================
   Determine who is viewing the profile
   =============================== */
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    // Student viewing own profile
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("i", $user_id);
    $is_student = true;
} elseif (isset($_SESSION['emp_id']) && isset($_GET['student_id'])) {
    // Employer viewing a student profile
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $student_id_param = intval($_GET['student_id']);
    $stmt->bind_param("i", $student_id_param);
    $is_student = false;
} else {
    die("Access denied.");
}

/* Fetch student details */
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student profile not found.");
}

// Always get the DB PK for experiences, skills, and ratings
$student_id = $student['id'];

/* ===============================
   Fetch previous experiences
   =============================== */
$exp_stmt = $conn->prepare("
    SELECT company_name, role, start_date, end_date, description
    FROM student_experiences
    WHERE student_id = ?
    ORDER BY start_date DESC
");
$exp_stmt->bind_param("i", $student_id);
$exp_stmt->execute();
$experiences = $exp_stmt->get_result();
$exp_stmt->close();

/* ===============================
   Fetch skills
   =============================== */
$skills = [];
$skill_stmt = $conn->prepare("
    SELECT skill_name 
    FROM student_skills 
    WHERE student_id = ?
");
$skill_stmt->bind_param("i", $student_id);
$skill_stmt->execute();
$skills_result = $skill_stmt->get_result();
while ($row = $skills_result->fetch_assoc()) {
    $skills[] = $row['skill_name'];
}
$skill_stmt->close();

/* ===============================
   Fetch ratings from employers
   =============================== */
$rating_stmt = $conn->prepare("
    SELECT sr.rating, sr.rated_at, e.shop_name AS employer_name
    FROM student_ratings sr
    JOIN employers e ON sr.employer_id = e.id
    WHERE sr.student_id = ?
    ORDER BY sr.rated_at DESC
");
$rating_stmt->bind_param("i", $student_id);
$rating_stmt->execute();
$ratings = $rating_stmt->get_result();
$rating_stmt->close();

/* ===============================
   Calculate average rating
   =============================== */
$avg_stmt = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_ratings
    FROM student_ratings
    WHERE student_id = ?
");
$avg_stmt->bind_param("i", $student_id);
$avg_stmt->execute();
$avg_data = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();

$avg_rating = round($avg_data['avg_rating'], 1);
$total_ratings = $avg_data['total_ratings'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($student['name']); ?> - Profile</title>

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

/* Profile Card */
.profile-box {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* Profile Header (Title + Button) */
.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.profile-header h2 {
  color: #4f46e5;
}

/* Edit Button */
.edit-btn {
  padding: 8px 18px;
  background: #4f46e5;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-size: 14px;
  transition: 0.3s;
}

.edit-btn:hover {
  background: #4338ca;
}

.section-title {
  margin-top: 30px;
  margin-bottom: 15px;
  color: #4f46e5;
  font-weight: 600;
  border-bottom: 2px solid #eef2ff;
  padding-bottom: 5px;
}

p {
  margin-bottom: 10px;
  font-size: 14px;
}

/* Experience & Rating Boxes */
.exp-box, .rating-box {
  border: 1px solid #e5e7eb;
  padding: 15px;
  margin-bottom: 15px;
  border-radius: 8px;
  background: #f9fafb;
}

.rating-summary {
  font-size: 16px;
  color: #16a34a;
  font-weight: 600;
  margin-bottom: 15px;
}

ul.skills {
  padding-left: 20px;
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
  <?php if ($is_student) { ?>
    <a href="dashboard.php">Back to Dashboard</a>
  <?php } else { ?>
    <a href="../employer/applications.php">Back to Applications</a>
  <?php } ?>
</header>

<div class="container">
  <div class="profile-box">

    <!-- Profile Title + Edit Button -->
    <div class="profile-header">
      <h2>Profile: <?php echo htmlspecialchars($student['name']); ?></h2>
      <?php if ($is_student) { ?>
        <a href="edit_profile.php" class="edit-btn">Edit Profile</a>
      <?php } ?>
    </div>

    <div class="section-title">Personal Details</div>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
    <p><strong>Age:</strong> <?php echo htmlspecialchars($student['age']); ?></p>
    <p><strong>District:</strong> <?php echo htmlspecialchars($student['district']); ?></p>
    <p><strong>Town:</strong> <?php echo htmlspecialchars($student['town']); ?></p>
    <p><strong>Permanent Address:</strong> <?php echo nl2br(htmlspecialchars($student['permanent_address'])); ?></p>
    <p><strong>Residential Address:</strong> <?php echo nl2br(htmlspecialchars($student['residential_address'])); ?></p>
    <p><strong>Institution:</strong> <?php echo htmlspecialchars($student['institution']); ?></p>
    <p><strong>Current Status:</strong> <?php echo htmlspecialchars($student['current_status']); ?></p>
    <p><strong>Qualification:</strong> <?php echo htmlspecialchars($student['qualification']); ?></p>

    <div class="section-title">Previous Experiences</div>
    <?php if ($experiences->num_rows > 0) { ?>
        <?php while ($exp = $experiences->fetch_assoc()) { ?>
            <div class="exp-box">
                <strong><?php echo htmlspecialchars($exp['role']); ?></strong> at 
                <em><?php echo htmlspecialchars($exp['company_name']); ?></em><br>
                <?php echo htmlspecialchars($exp['start_date']); ?> - 
                <?php echo htmlspecialchars($exp['end_date'] ?? 'Present'); ?><br>
                <?php echo nl2br(htmlspecialchars($exp['description'])); ?>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p>No previous experiences listed.</p>
    <?php } ?>

    <div class="section-title">Skills</div>
    <?php if (!empty($skills)) { ?>
        <ul class="skills">
            <?php foreach ($skills as $skill): ?>
                <li><?php echo htmlspecialchars($skill); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php } else { ?>
        <p>No skills added yet.</p>
    <?php } ?>

    <div class="section-title">Employer Ratings</div>
    <?php if ($total_ratings > 0) { ?>
        <p class="rating-summary">
            Average Rating: <?php echo $avg_rating; ?> / 5
            (<?php echo $total_ratings; ?> Ratings)
        </p>

        <?php while ($row = $ratings->fetch_assoc()) { ?>
            <div class="rating-box">
                <strong><?php echo htmlspecialchars($row['employer_name']); ?></strong><br>
                ⭐ Rating: <?php echo $row['rating']; ?> / 5<br>
                <small>Rated on: <?php echo $row['rated_at']; ?></small>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p>No ratings yet.</p>
    <?php } ?>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>