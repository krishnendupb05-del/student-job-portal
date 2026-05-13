<?php
session_start();

// Unset all session variables
session_unset();

// Destroy session
session_destroy();

// Redirect to main home page
header("Location: ../index.php");
exit();
?>