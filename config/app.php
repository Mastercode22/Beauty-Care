<?php
// config/app.php

// Define the base URL of the application.
// This allows for flexible deployment (e.g., in a subfolder or on the root domain)
// without breaking asset paths or links.

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME']; // e.g., /Beauty&Care/public/index.php

// Find the directory path from the script name
// We want to go up one level from /public to the project root
$project_root_path = dirname(dirname($script_name));

// If the project is in the web root, the path might be just '\' or '/'. 
// We should handle this to avoid double slashes in the URL.
if ($project_root_path === '/' || $project_root_path === '\\') {
    $project_root_path = '';
}

define('BASE_URL', $protocol . '://' . $host . $project_root_path);
?>
