<?php
session_start();
include("../config/db.php");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Latitude and Longitude (if not set, default to 0)
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : 0;
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : 0;

    // Insert into users table
    $query1 = "INSERT INTO users (email, password, role) 
               VALUES ('$email', '$password', 'student')";
    if(mysqli_query($conn, $query1)){
        $user_id = mysqli_insert_id($conn);

        // Insert into students table
        $query2 = "INSERT INTO students (user_id, name, latitude, longitude) 
                   VALUES ($user_id, '$name', '$lat', '$lng')";
        if(mysqli_query($conn, $query2)){
            // Set session and redirect to student dashboard
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'student';
            header("Location: ../student/dashboard.php");
            exit();
        } else {
            echo "Error inserting into students table: " . mysqli_error($conn);
        }
    } else {
        echo "Error inserting into users table: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>
    <script>
        let autocomplete;
        function initAutocomplete() {
            const input = document.getElementById('address');
            autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setFields(['geometry']);

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if(place.geometry){
                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();
                }
            });
        }
        window.onload = initAutocomplete;
    </script>
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

    <label>Address:</label><br>
    <input type="text" name="address" id="address" placeholder="Enter your address" required><br><br>

    <!-- Hidden fields to store latitude & longitude -->
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">

    <button type="submit">Register</button>
</form>

</body>
</html>
