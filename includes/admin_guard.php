<?php
// includes/admin_guard.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has the 'admin' role.
// The admin login page should be accessible without this guard.
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$is_logged_in) {
    // Not logged in, redirect to admin login
    header('Location: /admin/login.php');
    exit;
}

if (!$is_admin) {
    // Logged in, but not an admin. Redirect to the public homepage.
    header('Location: /index.php');
    exit;
}

?>
