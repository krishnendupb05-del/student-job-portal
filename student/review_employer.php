<?php
session_start();
require_once("../config/db.php");

// Student authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get student id */
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) die("Student not found.");
$student_id = $student['id'];

/* Get employer_id */
if (!isset($_GET['employer_id'])) {
    die("Invalid Access");
}

$employer_id = intval($_GET['employer_id']);

/* Check if student worked for this employer (accepted job) */
$check = $conn->prepare("
    SELECT a.application_no
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.student_id=? 
    AND j.employer_id=? 
    AND LOWER(a.status)='accepted'
");
$check->bind_param("ii", $student_id, $employer_id);
$check->execute();
$worked = $check->get_result();

if ($worked->num_rows == 0) {
    die("You can only review employers you worked for.");
}
$check->close();

/* Prevent duplicate review */
$dup = $conn->prepare("SELECT id FROM employer_reviews WHERE student_id=? AND employer_id=?");
$dup->bind_param("ii", $student_id, $employer_id);
$dup->execute();
$dup_result = $dup->get_result();

if ($dup_result->num_rows > 0) {
    die("You have already reviewed this employer.");
}
$dup->close();

/* Handle submit */
$success = '';
$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = intval($_POST['rating']);
    $feedback = trim($_POST['feedback']);

    if($rating < 1 || $rating > 5 || empty($feedback)) {
        $error = "Please provide a valid rating and feedback.";
    } else {
        $insert = $conn->prepare("
            INSERT INTO employer_reviews 
            (employer_id, student_id, rating, feedback) 
            VALUES (?, ?, ?, ?)
        ");
        $insert->bind_param("iiis", $employer_id, $student_id, $rating, $feedback);

        if ($insert->execute()) {
            $success = "✅ Review submitted successfully!";
        } else {
            $error = "❌ Error submitting review.";
        }

        $insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Review Employer</title>

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
    padding: 60px 8%;
}

/* Form Box */
.form-box {
    background: white;
    padding: 40px;
    width: 100%;
    max-width: 500px;
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
.input-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    outline: none;
    font-size: 14px;
}

.input-group input:focus,
.input-group textarea:focus {
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

/* Messages */
.success-msg { color: green; font-weight: bold; margin-bottom: 15px; }
.error-msg { color: red; font-weight: bold; margin-bottom: 15px; }

/* Responsive */
@media(max-width: 600px){
    .form-box {
        padding: 25px;
    }
}
</style>
</head>
<body>

<header>
    <h1>HireHub</h1>
    <a href="my_applications.php">⬅ Back to Applications</a>
</header>

<div class="container">
    <div class="form-box">
        <h2>Review Employer</h2>

        <?php if (!empty($error)) echo "<p class='error-msg'>$error</p>"; ?>
        <?php if (!empty($success)) echo "<p class='success-msg'>$success</p>"; ?>

        <form method="POST">
            <div class="input-group">
                <label>Rating (1-5):</label>
                <input type="number" name="rating" min="1" max="5" required>
            </div>

            <div class="input-group">
                <label>Feedback:</label>
                <textarea name="feedback" rows="5" required></textarea>
            </div>

            <button type="submit">Submit Review</button>
        </form>
    </div>
</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>