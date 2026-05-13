<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Student Authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ============================= */
/* ⏳ AUTO EXPIRE JOBS (GLOBAL) */
/* ============================= */
$conn->query("
    UPDATE jobs
    SET status = 'expired'
    WHERE due_date < CURDATE()
      AND status = 'open'
");

/* ============================= */
/* 🔹 Get Student Details Safely */
/* ============================= */
$stmt = $conn->prepare("SELECT id, state, district, town FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student location not found.");
}

$student = $result->fetch_assoc();
$stmt->close();

$student_id = $student['id'];
$state = $student['state'];
$district = $student['district'];
$town = $student['town'];

/* ============================= */
/* 🔹 Sorting Logic */
/* ============================= */
$orderBy = "ORDER BY created_at DESC";

if (isset($_GET['sort'])) {
    if ($_GET['sort'] === "salary_low") {
        $orderBy = "ORDER BY salary ASC";
    } elseif ($_GET['sort'] === "salary_high") {
        $orderBy = "ORDER BY salary DESC";
    } elseif ($_GET['sort'] === "urgency") {
        $orderBy = "ORDER BY urgency DESC";
    }
}

/* ============================= */
/* 🔹 Fetch Jobs */
/* ============================= */
$sql = "
    SELECT *
    FROM jobs
    WHERE state = ?
      AND district = ?
      AND town = ?
      AND status = 'open'
    $orderBy
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $state, $district, $town);
$stmt->execute();
$jobResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Available Jobs</title>

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

/* Card/Table */
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

/* Filter form */
form select {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ddd;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
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

/* Buttons / links */
.apply-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    background: #4f46e5;
    color: white;
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    transition: 0.3s;
}

.apply-btn:hover {
    background: #4338ca;
}

.status {
    font-weight: bold;
    color: green;
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
    <a href="dashboard.php"> Back to Dashboard</a>
</header>

<div class="container card">
    <h2>Jobs in Your Area</h2>

    <!-- SORT DROPDOWN -->
    <form method="GET">
        <label>Sort By:</label>
        <select name="sort" onchange="this.form.submit()">
            <option value="">Latest</option>
            <option value="salary_low">Salary: Low to High</option>
            <option value="salary_high">Salary: High to Low</option>
            <option value="urgency">Urgency</option>
        </select>
    </form>

    <?php if ($jobResult->num_rows > 0) { ?>
    <table>
        <tr>
            <th>Job Name</th>
            <th>Salary</th>
            <th>Hours</th>
            <th>Skills</th>
            <th>Urgency</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>

        <?php while ($job = $jobResult->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($job['job_name']); ?></td>
            <td><?= htmlspecialchars($job['salary']); ?></td>
            <td><?= htmlspecialchars($job['hours']); ?></td>
            <td><?= htmlspecialchars($job['skills']); ?></td>
            <td><?= htmlspecialchars($job['urgency']); ?></td>
            <td><?= htmlspecialchars($job['due_date']); ?></td>
            <td>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php
                    $job_id = $job['job_id'];
                    $checkStmt = $conn->prepare("
                        SELECT status FROM applications 
                        WHERE job_id = ? AND student_id = ?
                    ");
                    $checkStmt->bind_param("ii", $job_id, $student_id);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows > 0) {
                        $app = $checkResult->fetch_assoc();
                        echo "<span class='status'>";
                        echo ($app['status'] === 'Pending') ? "Applied" : ucfirst($app['status']);
                        echo "</span>";
                    } else {
                        echo "<a class='apply-btn' href='apply_job.php?job_id=" . $job_id . "'>Apply</a>";
                    }

                    $checkStmt->close();
                    ?>

                    <a href="view_employer.php?employer_id=<?php echo $job['employer_id']; ?>&from=jobs">View Employer</a>
                </div>
            </td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { ?>
        <p>No jobs available in your area.</p>
    <?php } ?>
</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>