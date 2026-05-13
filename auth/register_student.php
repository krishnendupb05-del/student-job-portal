<?php
session_start();
include_once __DIR__ . "/../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name                = mysqli_real_escape_string($conn, $_POST['name']);
    $phone               = mysqli_real_escape_string($conn, $_POST['phone']);
    $age                 = intval($_POST['age']);
    $email               = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw        = $_POST['password'];
    $state               = mysqli_real_escape_string($conn, $_POST['state']);
    $district            = mysqli_real_escape_string($conn, $_POST['district']);
    $town                = mysqli_real_escape_string($conn, $_POST['town']);
    $permanent_address   = mysqli_real_escape_string($conn, $_POST['permanent_address']);
    $residential_address = mysqli_real_escape_string($conn, $_POST['residential_address']);
    $institution         = mysqli_real_escape_string($conn, $_POST['institution']);
    $current_status      = mysqli_real_escape_string($conn, $_POST['current_status']);
    $qualification       = mysqli_real_escape_string($conn, $_POST['qualification']);

    /* 1️⃣ Age Validation */
    if ($age < 18) {
        $message = "Only students aged 18 or above can register.";
    }

    /* 2️⃣ Email Format Validation */
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    }

    /* 3️⃣ Phone Validation */
    elseif (!preg_match("/^\d{10}$/", $phone)) {
        $message = "Enter a valid 10-digit phone number.";
    }

    else {

        /* 4️⃣ Check Duplicate Email in users table */
        $checkEmail = "SELECT user_id FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $checkEmail);

        if (mysqli_num_rows($result) > 0) {
            $message = "Email already registered. Please use another email.";
        }

        else {

            /* 5️⃣ Hash Password */
            $password = password_hash($password_raw, PASSWORD_DEFAULT);

            /* 6️⃣ Insert into USERS table */
            $userSql = "INSERT INTO users (email, password, role)
                        VALUES ('$email', '$password', 'student')";

            if (mysqli_query($conn, $userSql)) {

                $user_id = mysqli_insert_id($conn);

                /* 7️⃣ Insert into STUDENTS table */
                $studentSql = "INSERT INTO students
                              (user_id, name, phone, age, email, state, district, town,
                               permanent_address, residential_address, institution, current_status, qualification)
                               VALUES
                              ($user_id, '$name', '$phone', $age, '$email', '$state', '$district', '$town',
                               '$permanent_address', '$residential_address', '$institution', '$current_status', '$qualification')";

                if (mysqli_query($conn, $studentSql)) {

                    $student_id = mysqli_insert_id($conn);

                    /* 8️⃣ Create Session */
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['student_id'] = $student_id;
                    $_SESSION['role'] = 'student';

                    header("Location: ../student/dashboard.php");
                    exit;

                } else {
                    $message = "Student insert error: " . mysqli_error($conn);
                }

            } else {
                $message = "User insert error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub - Student Registration</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<script>
const townsByDistrict = {
"Thiruvananthapuram": ["Trivandrum","Neyyattinkara","Attingal","Varkala","Kazhakoottam","Nedumangad","Balaramapuram","Kovalam","Poovar","Vizhinjam"],
"Kollam": ["Kollam","Karunagappally","Punalur","Paravur","Chathannoor","Kottarakkara","Pathanapuram","Anchal","Sasthamkotta","Chavara"],
"Pathanamthitta": ["Pathanamthitta","Adoor","Thiruvalla","Ranni","Pandalam","Konni","Kozhencherry","Aranmula","Mallappally","Pullad"],
"Alappuzha": ["Alappuzha","Cherthala","Haripad","Kayamkulam","Mavelikkara","Ambalappuzha","Kuttanad","Aroor","Thuravoor","Mannar"],
"Kottayam": ["Kottayam","Pala","Changanassery","Vaikom","Ettumanoor","Pampady","Kanjirappally","Kuravilangad","Mundakayam","Erattupetta"],
"Idukki": ["Thodupuzha","Munnar","Kattappana","Adimali","Nedumkandam","Rajakkad","Kumily","Vandanmedu","Karimannoor","Devikulam"],
"Ernakulam": ["Kochi","Aluva","Perumbavoor","Angamaly","Kothamangalam","Muvattupuzha","Thrippunithura","Edappally","Vyttila","Fort Kochi"],
"Thrissur": ["Thrissur","Irinjalakuda","Chalakudy","Kodungallur","Guruvayur","Kunnamkulam","Wadakkanchery","Mala","Chavakkad","Ollur"],
"Palakkad": ["Palakkad","Ottapalam","Shornur","Mannarkkad","Cherpulassery","Alathur","Pattambi","Vadakkencherry","Chittur","Kollengode"],
"Malappuram": ["Malappuram","Manjeri","Perinthalmanna","Tirur","Kottakkal","Nilambur","Ponnani","Edappal","Tanur","Valanchery"],
"Kozhikode": ["Kozhikode","Koyilandy","Vadakara","Feroke","Ramanattukara","Mukkam","Balussery","Thamarassery","Payyoli","Kuttyadi"],
"Wayanad": ["Kalpetta","Sulthan Bathery","Mananthavady","Meenangadi","Ambalavayal","Pulpally","Panamaram","Thirunelli","Padinjarathara","Vythiri"],
"Kannur": ["Kannur","Taliparamba","Thalassery","Iritty","Payyannur","Mattannur","Kuthuparamba","Panoor","Dharmadam","Ancharakandi"],
"Kasaragod": ["Kasaragod","Kanhangad","Nileshwar","Cheruvathur","Manjeshwar","Uppala","Bovikanam","Periye","Bekal","Chittarikkal"]
};

function loadTowns() {
    let district = document.getElementById("district").value;
    let townSelect = document.getElementById("town");
    townSelect.innerHTML = "<option value=''>Select Town</option>";

    if (district in townsByDistrict) {
        townsByDistrict[district].forEach(function(town) {
            let option = document.createElement("option");
            option.value = town;
            option.textContent = town;
            townSelect.appendChild(option);
        });
    }
}
</script>

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
  max-width: 700px;
}

.card h2 {
  text-align: center;
  color: #4f46e5;
  margin-bottom: 25px;
}

/* Form */
form label {
  font-weight: 500;
  font-size: 14px;
}

form input,
form select,
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
  padding: 12px;
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

/* Message */
.message {
  color: #dc2626;
  text-align: center;
  margin-bottom: 15px;
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
  <a href="../index.php">Home</a>
</header>

<div class="container">
  <div class="card">

    <h2>Student Registration</h2>

    <?php if (!empty($message)) echo "<p class='message'><b>$message</b></p>"; ?>

    <form method="POST">

      <label>Name</label>
      <input type="text" name="name" required>

      <label>Phone</label>
      <input type="text" name="phone" pattern="\d{10}" title="Enter 10-digit phone number" required>

      <label>Age</label>
      <input type="number" name="age" min="18" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>State</label>
      <input type="text" name="state" value="Kerala" readonly>

      <label>District</label>
      <select name="district" id="district" onchange="loadTowns()" required>
        <option value="">Select District</option>
        <option>Thiruvananthapuram</option>
        <option>Kollam</option>
        <option>Pathanamthitta</option>
        <option>Alappuzha</option>
        <option>Kottayam</option>
        <option>Idukki</option>
        <option>Ernakulam</option>
        <option>Thrissur</option>
        <option>Palakkad</option>
        <option>Malappuram</option>
        <option>Kozhikode</option>
        <option>Wayanad</option>
        <option>Kannur</option>
        <option>Kasaragod</option>
      </select>

      <label>Town</label>
      <select name="town" id="town" required>
        <option value="">Select Town</option>
      </select>

      <label>Permanent Address</label>
      <textarea name="permanent_address" required></textarea>

      <label>Residential Address</label>
      <textarea name="residential_address" required></textarea>

      <label>Institution</label>
      <input type="text" name="institution" required>

      <label>Current Status</label>
      <select name="current_status" required>
        <option value="">Select Status</option>
        <option value="Student">Student</option>
        <option value="Unemployed">Unemployed</option>
        <option value="Other">Other</option>
      </select>

      <label>Qualification</label>
      <input type="text" name="qualification" required>

      <button type="submit">Register</button>

    </form>

  </div>
</div>

<footer>
  © 2026 HireHub. All rights reserved.
</footer>

</body>
</html>