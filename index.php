<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>HireHub - Part-Time Jobs Made Easy</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: #f5f7fb; color: #333; }
header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
nav { display: flex; justify-content: center; gap: 30px; margin-top: 10px; }
nav a { color: #e0e7ff; text-decoration: none; font-weight: 500; }

.container { padding: 60px 8%; }

.hero { text-align: center; }
.hero h1 { font-size: 2.5rem; margin-bottom: 15px; }
.hero p { margin-bottom: 30px; color: #555; font-size: 1.1rem; }

.btn {
  background: #4f46e5;
  color: white;
  padding: 12px 25px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  margin: 10px;
  transition: 0.3s;
}
.btn:hover { background: #4338ca; }

.role-buttons { margin-top: 30px; }

.hidden { display: none; }

.option-box {
  margin-top: 30px;
}

.option-box h3 {
  margin-bottom: 15px;
  color: #4f46e5;
}

footer { text-align: center; padding: 20px; background: #e0e7ff; margin-top: 80px; }
</style>
</head>

<body>

<header>
<h1>HireHub</h1>
<p>Find Verified Part-Time Jobs Faster</p>
<nav>
<a href="#">Home</a>
</nav>
</header>

<div class="container">

<section class="hero">
<h1>Your Trusted Part-Time Job Platform</h1>
<p>Browse verified jobs, track applications, and connect securely with employers.</p>

<div class="role-buttons">
<button class="btn" onclick="showOptions('student')">Student</button>
<button class="btn" onclick="showOptions('employer')">Employer</button>
</div>

<div id="studentOptions" class="option-box hidden">
<h3>Student Portal</h3>
<button class="btn" onclick="window.location.href='student/login.php'">Login</button>
<button class="btn" onclick="window.location.href='auth/register_student.php'">Register</button>
</div>

<div id="employerOptions" class="option-box hidden">
<h3>Employer Portal</h3>
<button class="btn" onclick="window.location.href='employer/login.php'">Login</button>
<button class="btn" onclick="window.location.href='auth/register_employer.php'">Register</button>
</div>

</section>

</div>

<footer>
<p>© 2026 HireHub | Secure & Verified Hiring</p>
</footer>

<script>
function showOptions(role) {
    document.getElementById("studentOptions").classList.add("hidden");
    document.getElementById("employerOptions").classList.add("hidden");

    if (role === "student") {
        document.getElementById("studentOptions").classList.remove("hidden");
    } else {
        document.getElementById("employerOptions").classList.remove("hidden");
    }
}
</script>

</body>
</html>