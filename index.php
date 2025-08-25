<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to login page
header('Location: auth/login.php');
exit();
?>

