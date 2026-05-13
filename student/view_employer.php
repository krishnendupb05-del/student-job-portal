<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['employer_id'])) {
    die("Employer not specified.");
}

$employer_id = intval($_GET['employer_id']);
$from = isset($_GET['from']) ? $_GET['from'] : 'jobs';

// Get employer details
$employerQuery = "
    SELECT *
    FROM employers
    WHERE id = '$employer_id'
";

$employerResult = mysqli_query($conn, $employerQuery);

if (mysqli_num_rows($employerResult) == 0) {
    die("Employer not found.");
}

$employer = mysqli_fetch_assoc($employerResult);

// Get employer reviews
$reviewQuery = "
    SELECT er.rating, er.feedback, er.created_at, s.name
    FROM employer_reviews er
    JOIN students s ON er.student_id = s.id
    WHERE er.employer_id = '$employer_id'
    ORDER BY er.created_at DESC
";

$reviewResult = mysqli_query($conn, $reviewQuery);

// Calculate average rating
$avgQuery = "
    SELECT AVG(rating) as average_rating
    FROM employer_reviews
    WHERE employer_id = '$employer_id'
";

$avgResult = mysqli_query($conn, $avgQuery);
$avgData = mysqli_fetch_assoc($avgResult);
$average = round($avgData['average_rating'], 1);
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
.card h2, .card h3 {
    color: #4f46e5;
    margin-bottom: 15px;
}

/* Text */
.card p {
    margin-bottom: 10px;
    font-size: 14px;
}

/* Reviews */
.review-box {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    background: #f8fafc;
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

    <?php if ($from == 'applications') { ?>
        <a href="my_applications.php"> Back to My Applications</a>
    <?php } else { ?>
        <a href="jobs.php"> Back to Jobs</a>
    <?php } ?>
</header>

<div class="container">

    <div class="card">
        <h2><?php echo htmlspecialchars($employer['shop_name']); ?></h2>

        <p><strong>Email:</strong> 
            <?php echo htmlspecialchars($employer['email']); ?>
        </p>

        <p><strong>Location:</strong> 
            <?php 
            echo htmlspecialchars(
                $employer['town'] . ", " . 
                $employer['district'] . ", " . 
                $employer['state']
            ); 
            ?>
        </p>

        <p><strong>Average Rating:</strong> 
            <?php 
            echo $average 
                ? htmlspecialchars($average) . " / 5" 
                : "No ratings yet"; 
            ?>
        </p>
    </div>

    <div class="card">
        <h3>Student Reviews</h3>

        <?php
        if (mysqli_num_rows($reviewResult) > 0) {
            while ($review = mysqli_fetch_assoc($reviewResult)) {
                echo '<div class="review-box">';
                echo "<p><strong>Student:</strong> " . htmlspecialchars($review['name']) . "</p>";
                echo "<p><strong>Rating:</strong> " . htmlspecialchars($review['rating']) . " / 5</p>";
                echo "<p><strong>Feedback:</strong> " . htmlspecialchars($review['feedback']) . "</p>";
                echo "<p><small>Reviewed on: " . htmlspecialchars($review['created_at']) . "</small></p>";
                echo '</div>';
            }
        } else {
            echo "<p>No reviews yet.</p>";
        }
        ?>
    </div>

</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>