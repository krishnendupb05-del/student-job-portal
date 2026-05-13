<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    /* 1️⃣ Authenticate from USERS table */
    $sql = "SELECT * FROM users WHERE email='$email' AND role='employer'";
    $result = mysqli_query($conn, $sql);

    if ($user = mysqli_fetch_assoc($result)) {

        if (password_verify($password, $user['password'])) {

            /* ✅ FIX: correct primary key name */
            $user_id = $user['user_id'];

            /* 2️⃣ Fetch employer details */
            $empSql = "SELECT * FROM employers WHERE user_id = $user_id";
            $empResult = mysqli_query($conn, $empSql);
            $employer = mysqli_fetch_assoc($empResult);

            if (!$employer) {
                die("Employer record not found");
            }

            /* 3️⃣ Set session */
            $_SESSION['user_id'] = $user_id;
            $_SESSION['emp_id'] = $employer['id'];
            $_SESSION['role'] = 'employer';

            header("Location: dashboard.php");
            exit();

        } else {
            echo "<script>alert('Incorrect password');</script>";
        }

    } else {
        echo "<script>alert('Email not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Employer Login</title>

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
  align-items: center;
  padding: 80px 20px;
}

/* Login Box */
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

.input-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  outline: none;
  font-size: 14px;
}

.input-group input:focus {
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
  <a href="../index.php">Back to Home</a>
</header>

<div class="container">
  <div class="form-box">
    <h2>Employer Login</h2>

    <form method="post">

      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>

      <button type="submit" name="login">Login</button>

    </form>

  </div>
</div>

<footer>
  <p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>