<?php
session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect to main home page
header("Location: ../index.php");
exit();
?>