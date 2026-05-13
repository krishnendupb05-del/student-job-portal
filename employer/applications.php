<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['emp_id'];

/* Filter keyword */
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

/* Sort validation */
$allowedSort = ['rating', 'latest', 'oldest'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)
        ? $_GET['sort']
        : 'rating';

/* ============================= */
/* Handle Accept / Reject + Notification */
/* ============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {

    $id = intval($_POST['id']);
    $status = $_POST['status'];

    if (in_array($status, ['Accepted', 'Rejected'])) {

        /* Update status */
        $update = $conn->prepare("
            UPDATE applications a
            JOIN jobs j ON a.job_id = j.job_id
            SET a.status=?
            WHERE a.application_no=? AND j.employer_id=?
        ");

        $update->bind_param("sii", $status, $id, $emp_id);
        $update->execute();
        $update->close();

        /* Get student_id + job title */
        $info = $conn->prepare("
            SELECT a.student_id, j.job_name
            FROM applications a
            JOIN jobs j ON a.job_id = j.job_id
            WHERE a.application_no=?
        ");

        $info->bind_param("i", $id);
        $info->execute();
        $res = $info->get_result();

        if ($res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $student_id = $data['student_id'];
            $job_name = $data['job_name'];

            /* Create notification message */
            $message = ($status === "Accepted") 
                ? "Your application is accepted for the job $job_name"
                : "Your application for $job_name has been rejected";

            /* Insert notification */
            $notif = $conn->prepare("
                INSERT INTO notifications (student_id, message, link, is_read)
                VALUES (?, ?, ?, 0)
            ");

            $link = "my_applications.php";
            $notif->bind_param("iss", $student_id, $message, $link);
            $notif->execute();
            $notif->close();
        }

        $info->close();
    }

    header("Location: applications.php");
    exit();
}

/* ORDER BY logic */
switch ($sort) {
    case 'latest':
        $orderBy = "a.applied_at DESC";
        break;
    case 'oldest':
        $orderBy = "a.applied_at ASC";
        break;
    default:
        $orderBy = "COALESCE(sr.rating,0) DESC, a.applied_at ASC";
}

/* Main Query */
$sql = "
SELECT 
    a.application_no,
    a.student_id,
    s.name AS student_name,
    s.email AS student_email,
    s.phone AS student_phone,
    a.applied_at,
    COALESCE(a.status, 'Pending') AS status,
    j.job_name AS job_title,
    j.skills AS job_description,
    COALESCE(sr.rating,0) AS student_rating
FROM applications a
JOIN jobs j ON a.job_id = j.job_id
JOIN students s ON a.student_id = s.id
LEFT JOIN student_ratings sr 
       ON sr.student_id = s.id 
       AND sr.employer_id = ?
WHERE j.employer_id = ?
  AND j.state = s.state
  AND j.district = s.district
  AND j.town = s.town
  AND j.skills LIKE ?
ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query Error: " . $conn->error);
}

$search = "%" . $keyword . "%";
$stmt->bind_param("iis", $emp_id, $emp_id, $search);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Student Applications</title>

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
  overflow-x: auto;
}

.card h2 {
  margin-bottom: 20px;
  color: #4f46e5;
}

/* Filter Form */
.filter-form {
  margin-bottom: 25px;
}

.filter-form input,
.filter-form select {
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ddd;
  margin-right: 10px;
}

.filter-form button {
  padding: 8px 14px;
  border-radius: 6px;
  border: none;
  background: #4f46e5;
  color: white;
  cursor: pointer;
}

.filter-form button:hover {
  background: #4338ca;
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
}

tr:hover {
  background: #f1f5ff;
}

/* Buttons */
button {
  padding: 6px 10px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 13px;
}

.accept-btn {
  background: #16a34a;
  color: white;
}

.accept-btn:hover {
  background: #15803d;
}

.reject-btn {
  background: #dc2626;
  color: white;
}

.reject-btn:hover {
  background: #b91c1c;
}

.action-link {
  color: #4f46e5;
  font-weight: 500;
  text-decoration: none;
}

.action-link:hover {
  text-decoration: underline;
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
  <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
  <div class="card">

    <h2>Student Applications</h2>

    <form method="get" class="filter-form">
      <label>Filter:</label>
      <input type="text" name="keyword"
             value="<?php echo htmlspecialchars($keyword); ?>">

      <label>Sort By:</label>
      <select name="sort">
        <option value="rating" <?php if($sort=='rating') echo 'selected'; ?>>Highest Rating</option>
        <option value="latest" <?php if($sort=='latest') echo 'selected'; ?>>Latest Applied</option>
        <option value="oldest" <?php if($sort=='oldest') echo 'selected'; ?>>Oldest Applied</option>
      </select>

      <button type="submit">Apply</button>
    </form>

    <table>
      <tr>
        <th>App No</th>
        <th>Job</th>
        <th>Description</th>
        <th>Student</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Applied</th>
        <th>Status</th>
        <th>Rating</th>
        <th>Action</th>
        <th>Profile</th>
      </tr>

      <?php if ($result->num_rows > 0) { ?>
      <?php while ($row = $result->fetch_assoc()) { ?>
      <tr>
        <td><?php echo htmlspecialchars($row['application_no']); ?></td>
        <td><?php echo htmlspecialchars($row['job_title']); ?></td>
        <td><?php echo htmlspecialchars($row['job_description']); ?></td>
        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
        <td><?php echo htmlspecialchars($row['student_email']); ?></td>
        <td><?php echo htmlspecialchars($row['student_phone']); ?></td>
        <td><?php echo htmlspecialchars($row['applied_at']); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td><?php echo htmlspecialchars($row['student_rating']); ?></td>

        <td>
          <?php if ($row['status'] === 'Pending') { ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="id" value="<?php echo $row['application_no']; ?>">
              <input type="hidden" name="status" value="Accepted">
              <button class="accept-btn" type="submit">Accept</button>
            </form>

            <form method="post" style="display:inline;"
              onsubmit="return confirm('Reject this application?');">
              <input type="hidden" name="id" value="<?php echo $row['application_no']; ?>">
              <input type="hidden" name="status" value="Rejected">
              <button class="reject-btn" type="submit">Reject</button>
            </form>

          <?php } elseif ($row['status'] === 'Accepted') { ?>
            <a class="action-link"
               href="rate_student.php?student_id=<?php echo $row['student_id']; ?>">
               <?php echo ($row['student_rating'] > 0) ? 'Update Rating' : 'Rate Student'; ?>
            </a>
          <?php } else { ?>
            <strong>Rejected</strong>
          <?php } ?>
        </td>

        <td>
          <a class="action-link"
             href="../student/profile.php?student_id=<?php echo $row['student_id']; ?>">
             View Profile
          </a>
        </td>
      </tr>
      <?php } ?>
      <?php } else { ?>
      <tr>
        <td colspan="11" style="text-align:center;">No applications yet</td>
      </tr>
      <?php } ?>

    </table>

  </div>
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