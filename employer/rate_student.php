<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];

if (!isset($_GET['student_id'])) {
    die("Student not specified.");
}

$student_id = intval($_GET['student_id']);

if (isset($_POST['submit'])) {

    $rating = intval($_POST['rating']);

    if ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO student_ratings (student_id, employer_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                rated_at = CURRENT_TIMESTAMP
        ");

        $stmt->bind_param("iii", $student_id, $emp_id, $rating);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Failed to submit rating.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Rate Student</title>

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
    max-width: 450px;
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

.input-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    outline: none;
    font-size: 14px;
}

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
    <a href="dashboard.php"> Back to Dashboard</a>
</header>

<div class="container">
    <div class="form-box">
        <h2>Rate Student (ID: <?php echo $student_id; ?>)</h2>

        <?php if (isset($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>

        <form method="POST">
            <div class="input-group">
                <label>Select Rating:</label>
                <select name="rating" required>
                    <option value="">Choose</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Fair</option>
                    <option value="3">3 - Good</option>
                    <option value="4">4 - Very Good</option>
                    <option value="5">5 - Excellent</option>
                </select>
            </div>

            <button type="submit" name="submit">Submit Rating</button>
        </form>
    </div>
</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>