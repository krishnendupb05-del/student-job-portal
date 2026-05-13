<?php
session_start();
require_once("../config/db.php");

/* 🔐 Student Authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* 🔹 Get student ID safely */
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

$student_id = $student['id'];

/* 🔹 Handle Delete (POST method) */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {

    $app_id = intval($_POST['delete_id']);

    $check = $conn->prepare("SELECT application_no FROM applications 
                             WHERE application_no = ? 
                             AND student_id = ? 
                             AND LOWER(TRIM(status)) = 'pending'");
    $check->bind_param("ii", $app_id, $student_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $delete = $conn->prepare("DELETE FROM applications WHERE application_no = ?");
        $delete->bind_param("i", $app_id);
        $delete->execute();
        $delete->close();

        header("Location: my_applications.php?deleted=1");
        exit();

    } else {
        $error = "You can only delete your own pending applications.";
    }

    $check->close();
}

/* 🔹 Fetch applied jobs */
$query = $conn->prepare("
    SELECT a.application_no, a.status, j.job_name, j.salary, j.hours, 
           j.skills, j.urgency, j.due_date, j.employer_id
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.student_id = ?
    ORDER BY a.application_no DESC
");
$query->bind_param("i", $student_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - My Applications</title>

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

/* Table */
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
    vertical-align: top;
}

tr:hover {
    background: #f1f5ff;
}

/* Status */
.status-pending { color: #f59e0b; font-weight: bold; }
.status-accepted { color: #16a34a; font-weight: bold; }
.status-rejected { color: #dc2626; font-weight: bold; }

/* Buttons / Links */
a, button {
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
}

button {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    background: #4f46e5;
    color: white;
    transition: 0.3s;
}

button:hover {
    background: #4338ca;
}

/* Inline form */
form.inline {
    display: inline;
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
    table, th, td {
        font-size: 12px;
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
        <h2>My Job Applications</h2>

        <?php
        if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
            echo "<p style='color:green;font-weight:bold;'>✅ Application deleted successfully!</p>";
        }

        if (isset($error)) {
            echo "<p style='color:red;font-weight:bold;'>$error</p>";
        }
        ?>

        <?php if ($result->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Job Name</th>
                <th>Salary</th>
                <th>Hours</th>
                <th>Skills</th>
                <th>Urgency</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { 
                $status = strtolower(trim($row['status']));
                $statusClass = 'status-pending';
                if ($status === 'accepted') $statusClass = 'status-accepted';
                elseif ($status === 'rejected') $statusClass = 'status-rejected';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['job_name']); ?></td>
                <td><?php echo htmlspecialchars($row['salary']); ?></td>
                <td><?php echo htmlspecialchars($row['hours']); ?></td>
                <td><?php echo htmlspecialchars($row['skills']); ?></td>
                <td><?php echo htmlspecialchars($row['urgency']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td class="<?php echo $statusClass; ?>">
                    <?php echo ucfirst($status); ?>
                </td>
                <td>
                    <a href="view_employer.php?employer_id=<?php echo $row['employer_id']; ?>&from=applications">
                        View Employer
                    </a>

                    <?php if ($status === 'accepted') { ?>
                        | <a href="review_employer.php?employer_id=<?php echo $row['employer_id']; ?>">
                            Review
                        </a>
                    <?php } ?>

                    <?php if ($status === 'pending') { ?>
                        | 
                        <form method="POST" class="inline">
                            <input type="hidden" name="delete_id" value="<?php echo $row['application_no']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this application?');">
                                Delete
                            </button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { ?>
            <p>You have not applied for any jobs yet.</p>
        <?php } ?>

    </div>
</div>

<footer>
    <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>