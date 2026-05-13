<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Student authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* 🔎 Get student details */
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student profile not found.");
}

$profile = $result->fetch_assoc();
$stmt->close();

/* 🔎 Get existing experiences */
$exp_stmt = $conn->prepare("SELECT * FROM student_experiences WHERE student_id = ? ORDER BY start_date DESC");
$exp_stmt->bind_param("i", $profile['id']);
$exp_stmt->execute();
$experiences = $exp_stmt->get_result();
$exp_stmt->close();

/* 🔎 Get existing skills */
$skill_stmt = $conn->prepare("SELECT skill_name FROM student_skills WHERE student_id=?");
$skill_stmt->bind_param("i", $profile['id']);
$skill_stmt->execute();
$skills_result = $skill_stmt->get_result();
$skills = [];
while ($row = $skills_result->fetch_assoc()) {
    $skills[] = $row['skill_name'];
}
$skill_stmt->close();

/* ✏️ Handle form submission */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name                = trim($_POST['name']);
    $email               = trim($_POST['email']);
    $phone               = trim($_POST['phone']);
    $age                 = trim($_POST['age']);
    $district            = trim($_POST['district']);
    $town                = trim($_POST['town']);
    $permanent_address   = trim($_POST['permanent_address']);
    $residential_address = trim($_POST['residential_address']);
    $institution         = trim($_POST['institution']);
    $current_status      = trim($_POST['current_status']);
    $qualification       = trim($_POST['qualification']);

    $conn->begin_transaction();

    try {
        // Update main profile including new fields
        $update = $conn->prepare("UPDATE students 
                                  SET name=?, email=?, phone=?, age=?, district=?, town=?, 
                                      permanent_address=?, residential_address=?, institution=?, current_status=?, qualification=?
                                  WHERE user_id=?");
        $update->bind_param(
            "sssisssssssi",
            $name, $email, $phone, $age, $district, $town,
            $permanent_address, $residential_address, $institution, $current_status, $qualification,
            $user_id
        );
        $update->execute();
        $update->close();

        // Delete old experiences
        $del_stmt = $conn->prepare("DELETE FROM student_experiences WHERE student_id=?");
        $del_stmt->bind_param("i", $profile['id']);
        $del_stmt->execute();
        $del_stmt->close();

        // Insert new experiences
        if (isset($_POST['company_name']) && is_array($_POST['company_name'])) {
            $exp_stmt = $conn->prepare("
                INSERT INTO student_experiences 
                (student_id, company_name, role, start_date, end_date, description) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            for ($i = 0; $i < count($_POST['company_name']); $i++) {
                $company = trim($_POST['company_name'][$i]);
                $role = trim($_POST['role'][$i]);
                $start_date = trim($_POST['start_date'][$i]);
                $end_date = trim($_POST['end_date'][$i]);
                $desc = trim($_POST['description'][$i]);
                if ($company !== "" && $role !== "") {
                    $exp_stmt->bind_param("isssss", $profile['id'], $company, $role, $start_date, $end_date, $desc);
                    $exp_stmt->execute();
                }
            }
            $exp_stmt->close();
        }

        // Delete old skills
        $del_skill = $conn->prepare("DELETE FROM student_skills WHERE student_id=?");
        $del_skill->bind_param("i", $profile['id']);
        $del_skill->execute();
        $del_skill->close();

        // Insert new skills
        if (isset($_POST['skills']) && is_array($_POST['skills'])) {
            $ins_skill = $conn->prepare("INSERT INTO student_skills (student_id, skill_name) VALUES (?, ?)");
            foreach ($_POST['skills'] as $skill) {
                $s = trim($skill);
                if ($s !== '') {
                    $ins_skill->bind_param("is", $profile['id'], $s);
                    $ins_skill->execute();
                }
            }
            $ins_skill->close();
        }

        $conn->commit();
        header("Location: dashboard.php?updated=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Something went wrong: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile</title>

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

/* Form Card */
.form-box {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.form-box h2 {
  color: #4f46e5;
  margin-bottom: 20px;
}

/* Inputs */
label {
  font-weight: 500;
  font-size: 14px;
}

input, textarea {
  width: 100%;
  padding: 10px;
  margin: 6px 0 15px 0;
  border: 1px solid #ddd;
  border-radius: 6px;
  outline: none;
  font-size: 14px;
}

input:focus, textarea:focus {
  border-color: #4f46e5;
}

textarea {
  resize: vertical;
}

/* Experience Box */
.experience {
  border: 1px solid #e5e7eb;
  padding: 15px;
  margin-bottom: 15px;
  border-radius: 8px;
  background: #f9fafb;
}

.skill-box {
  margin-bottom: 10px;
}

.remove-btn {
  color: red;
  cursor: pointer;
  font-size: 13px;
}

/* Buttons */
button {
  padding: 10px 20px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.3s;
  margin-right: 10px;
}

button:hover {
  background: #4338ca;
}

.add-btn {
  background: #16a34a;
}

.add-btn:hover {
  background: #15803d;
}

/* Footer */
footer {
  text-align: center;
  padding: 20px;
  background: #e0e7ff;
  margin-top: 60px;
}
</style>

<script>
function addExperience() {
    const container = document.getElementById('experiences');
    const html = `
    <div class="experience">
        <label>Company Name:</label>
        <input type="text" name="company_name[]" required>

        <label>Role:</label>
        <input type="text" name="role[]" required>

        <label>Start Date:</label>
        <input type="date" name="start_date[]">

        <label>End Date:</label>
        <input type="date" name="end_date[]">

        <label>Description:</label>
        <textarea name="description[]"></textarea>

        <span class="remove-btn" onclick="this.parentNode.remove();">Remove</span>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function addSkill() {
    const container = document.getElementById('skills');
    const html = `
    <div class="skill-box">
        <input type="text" name="skills[]" required>
        <span class="remove-btn" onclick="this.parentNode.remove();">Remove</span>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>
</head>

<body>

<header>
  <h1>HireHub</h1>
  <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
  <div class="form-box">

    <h2>Edit Profile</h2>

    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST">

        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>

        <label>Phone:</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>

        <label>Age:</label>
        <input type="number" name="age" value="<?php echo htmlspecialchars($profile['age']); ?>" required>

        <label>District:</label>
        <input type="text" name="district" value="<?php echo htmlspecialchars($profile['district']); ?>" required>

        <label>Town:</label>
        <input type="text" name="town" value="<?php echo htmlspecialchars($profile['town']); ?>" required>

        <label>Permanent Address:</label>
        <textarea name="permanent_address"><?php echo htmlspecialchars($profile['permanent_address']); ?></textarea>

        <label>Residential Address:</label>
        <textarea name="residential_address"><?php echo htmlspecialchars($profile['residential_address']); ?></textarea>

        <label>Institution:</label>
        <input type="text" name="institution" value="<?php echo htmlspecialchars($profile['institution']); ?>">

        <label>Current Status:</label>
        <input type="text" name="current_status" value="<?php echo htmlspecialchars($profile['current_status']); ?>">

        <label>Qualification:</label>
        <input type="text" name="qualification" value="<?php echo htmlspecialchars($profile['qualification']); ?>">

        <h3 style="color:#4f46e5; margin-top:25px;">Previous Experiences</h3>

        <div id="experiences">
            <?php while ($exp = $experiences->fetch_assoc()): ?>
            <div class="experience">
                <label>Company Name:</label>
                <input type="text" name="company_name[]" value="<?php echo htmlspecialchars($exp['company_name']); ?>" required>

                <label>Role:</label>
                <input type="text" name="role[]" value="<?php echo htmlspecialchars($exp['role']); ?>" required>

                <label>Start Date:</label>
                <input type="date" name="start_date[]" value="<?php echo htmlspecialchars($exp['start_date']); ?>">

                <label>End Date:</label>
                <input type="date" name="end_date[]" value="<?php echo htmlspecialchars($exp['end_date']); ?>">

                <label>Description:</label>
                <textarea name="description[]"><?php echo htmlspecialchars($exp['description']); ?></textarea>

                <span class="remove-btn" onclick="this.parentNode.remove();">Remove</span>
            </div>
            <?php endwhile; ?>
        </div>

        <button type="button" class="add-btn" onclick="addExperience()">➕ Add Experience</button>

        <h3 style="color:#4f46e5; margin-top:25px;">Skills</h3>

        <div id="skills">
            <?php foreach ($skills as $s): ?>
            <div class="skill-box">
                <input type="text" name="skills[]" value="<?php echo htmlspecialchars($s); ?>" required>
                <span class="remove-btn" onclick="this.parentNode.remove();">Remove</span>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="add-btn" onclick="addSkill()">➕ Add Skill</button>
        <br><br>

        <button type="submit">Update Profile</button>

    </form>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Empowering Students & Careers</p>
</footer>

</body>
</html>