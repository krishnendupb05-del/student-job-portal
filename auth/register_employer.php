<?php
session_start();
include_once __DIR__ . "/../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $shop_name  = mysqli_real_escape_string($conn, $_POST['company']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $raw_password = $_POST['password'];
    $state      = mysqli_real_escape_string($conn, $_POST['state']);
    $district   = mysqli_real_escape_string($conn, $_POST['district']);
    $town       = mysqli_real_escape_string($conn, $_POST['town']);

    /* 🔹 1️⃣ Validate Email Format */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";

    /* 🔹 2️⃣ Check if Email Already Exists */
    } else {

        $checkEmail = "SELECT user_id FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $checkEmail);

        if (mysqli_num_rows($result) > 0) {

            $message = "Email already registered. Please use another email.";

        } else {

            /* 🔹 3️⃣ Hash Password */
            $password = password_hash($raw_password, PASSWORD_DEFAULT);

            /* 🔹 4️⃣ Insert into USERS table */
            $userSql = "INSERT INTO users (email, password, role)
                        VALUES ('$email', '$password', 'employer')";

            if (mysqli_query($conn, $userSql)) {

                $user_id = mysqli_insert_id($conn);

                /* 🔹 5️⃣ Insert into EMPLOYERS table */
                $empSql = "INSERT INTO employers 
                          (user_id, shop_name, owner_name, phone, state, district, town, address, email)
                           VALUES 
                          ($user_id, '$shop_name', '$owner_name', '$phone', '$state', '$district', '$town', '$address', '$email')";

           if (mysqli_query($conn, $empSql)) {

                if (mysqli_query($conn, $empSql)) {

                    $emp_id = mysqli_insert_id($conn);

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['emp_id']  = $emp_id;
                    $_SESSION['role']    = 'employer';

                    header("Location: ../employer/dashboard.php");
                    exit;

                } else {
                    $message = "Employer insert error: " . mysqli_error($conn);
                }

            } else {
                $message = "User insert error: " . mysqli_error($conn);
            }
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
<title>HireHub - Employer Registration</title>

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

.container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px 20px;
}

.form-box {
  background: white;
  padding: 40px;
  width: 100%;
  max-width: 600px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.form-box h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #4f46e5;
}

.message {
  text-align: center;
  margin-bottom: 15px;
  color: red;
  font-weight: 500;
}

.input-group {
  margin-bottom: 18px;
}

.input-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}

.input-group input,
.input-group textarea,
.input-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  outline: none;
  font-size: 14px;
}

.input-group input:focus,
.input-group textarea:focus,
.input-group select:focus {
  border-color: #4f46e5;
}

button {
  width: 100%;
  padding: 12px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  margin-top: 10px;
  transition: 0.3s;
}

button:hover {
  background: #4338ca;
}

footer {
  text-align: center;
  padding: 20px;
  background: #e0e7ff;
  margin-top: 40px;
}

@media(max-width: 600px){
  .form-box {
    padding: 25px;
  }
}
</style>

<script>
/* Your townsByDistrict script stays EXACTLY same */
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
        townsByDistrict[district].forEach(town => {
            let opt = document.createElement("option");
            opt.value = town;
            opt.textContent = town;
            townSelect.appendChild(opt);
        });
    }
}
</script>

</head>

<body>

<header>
  <h1>HireHub</h1>
  <a href="../index.php">Back to Home</a>
</header>

<div class="container">
<div class="form-box">

<h2>Employer Registration</h2>

<?php if ($message): ?>
<div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST">

<div class="input-group">
<label>Company / Shop Name</label>
<input type="text" name="company" required>
</div>

<div class="input-group">
<label>Owner Name</label>
<input type="text" name="owner_name" required>
</div>

<div class="input-group">
<label>Phone</label>
<input type="text" name="phone" required>
</div>

<div class="input-group">
<label>Address</label>
<textarea name="address" rows="3" required></textarea>
</div>

<div class="input-group">
<label>Email</label>
<input type="email" name="email" required>
</div>

<div class="input-group">
<label>Password</label>
<input type="password" name="password" required>
</div>

<div class="input-group">
<label>State</label>
<input type="text" name="state" value="Kerala" readonly>
</div>

<div class="input-group">
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
</div>

<div class="input-group">
<label>Town</label>
<select name="town" id="town" required>
<option value="">Select Town</option>
</select>
</div>

<button type="submit">Register</button>

</form>

</div>
</div>

<footer>
<p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

</body>
</html>