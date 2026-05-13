<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Employer authentication */
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];

/* Fetch rated students securely */
$stmt = $conn->prepare("
    SELECT student_id, rating, rated_at
    FROM student_ratings
    WHERE employer_id = ?
    ORDER BY rated_at DESC
");

$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rated Students</title>
</head>
<body>

<h2>Rated Students</h2>
<hr>

<?php if ($result->num_rows > 0) { ?>

<table border="1" cellpadding="8">
<tr>
    <th>Student ID</th>
    <th>Rating (1–5)</th>
    <th>Rated At</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
    <td><?php echo htmlspecialchars($row['rating']); ?></td>
    <td><?php echo htmlspecialchars($row['rated_at']); ?></td>
</tr>
<?php } ?>

</table>

<?php } else { ?>
<p>No students have been rated yet.</p>
<?php } ?>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>