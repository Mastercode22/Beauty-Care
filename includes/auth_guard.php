<?php
// includes/auth_guard.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in.
// If not, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    // The path to login.php. 
    // This assumes the guard is included from a file in the 'public' directory.
    // For a more robust solution in complex structures, a config constant for the base URL would be better.
    header('Location: /login.php');
    exit;
}
?>
