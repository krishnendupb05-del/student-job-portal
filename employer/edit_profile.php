<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* 🔐 Employer authentication */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch employer data */
$stmt = $conn->prepare("SELECT * FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employer profile not found.");
}

$profile = $result->fetch_assoc();
$stmt->close();

/* Update profile */
if (isset($_POST['update'])) {

    $shop_name  = $_POST['shop_name'];
    $owner_name = $_POST['owner_name'];
    $phone      = $_POST['phone'];
    $district   = $_POST['district'];
    $town       = $_POST['town'];
    $address    = $_POST['address'];

    $update = $conn->prepare("
        UPDATE employers 
        SET shop_name=?, owner_name=?, phone=?, district=?, town=?, address=? 
        WHERE user_id=?
    ");

    $update->bind_param("ssssssi",
        $shop_name,
        $owner_name,
        $phone,
        $district,
        $town,
        $address,
        $user_id
    );

    if ($update->execute()) {
        header("Location: profile.php");
        exit();
    } else {
        $error = "Update failed.";
    }

    $update->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Edit Profile</title>

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
  display: flex;
  justify-content: center;
}

/* Card */
.card {
  background: white;
  padding: 35px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  width: 100%;
  max-width: 600px;
}

.card h2 {
  margin-bottom: 25px;
  color: #4f46e5;
  text-align: center;
}

/* Form */
form label {
  font-weight: 500;
  font-size: 14px;
}

form input,
form textarea {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  margin-bottom: 18px;
  border-radius: 6px;
  border: 1px solid #ddd;
  font-size: 14px;
}

form textarea {
  resize: vertical;
  min-height: 80px;
}

/* Button */
form button {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: none;
  background: #4f46e5;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: 0.3s;
}

form button:hover {
  background: #4338ca;
}

/* Error */
.error {
  color: #dc2626;
  margin-bottom: 15px;
  text-align: center;
}

/* Back link */
.back-link {
  display: block;
  text-align: center;
  margin-top: 20px;
  color: #4f46e5;
  text-decoration: none;
  font-weight: 500;
}

.back-link:hover {
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

<!-- Header -->
<header>
  <h1>HireHub</h1>
  <a href="profile.php">Back to Profile</a>
</header>

<!-- Container -->
<div class="container">
  <div class="card">

    <h2>Edit Employer Profile</h2>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">

      <label>Shop Name:</label>
      <input type="text" name="shop_name"
        value="<?php echo htmlspecialchars($profile['shop_name'] ?? ''); ?>" required>

      <label>Owner Name:</label>
      <input type="text" name="owner_name"
        value="<?php echo htmlspecialchars($profile['owner_name'] ?? ''); ?>" required>

      <label>Phone:</label>
      <input type="text" name="phone"
        value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">

      <label>District:</label>
      <input type="text" name="district"
        value="<?php echo htmlspecialchars($profile['district'] ?? ''); ?>">

      <label>Town:</label>
      <input type="text" name="town"
        value="<?php echo htmlspecialchars($profile['town'] ?? ''); ?>">

      <label>Address:</label>
      <textarea name="address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>

      <button type="submit" name="update">Update Profile</button>
    </form>

  </div>
</div>

<!-- Footer -->
<footer>
  © 2026 HireHub. All rights reserved.
</footer>

</body>
</html>