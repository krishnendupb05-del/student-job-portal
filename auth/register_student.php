<?php
include("../config/db.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
</head>
<body>

    <h2>Student Registration</h2>

    <form method="POST" action="">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Register</button>
    </form>

</body>
</html>
<?php
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Insert into users table
    $query1 = "INSERT INTO users (email, password, role) 
               VALUES ('$email', '$password', 'student')";
    mysqli_query($conn, $query1);

    $user_id = mysqli_insert_id($conn);

    // Insert into students table
    $query2 = "INSERT INTO students (user_id, name) 
               VALUES ('$user_id', '$name')";
    mysqli_query($conn, $query2);

    echo "Student registered successfully";
}
?>