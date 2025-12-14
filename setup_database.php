<?php
// setup_database.php

// In a real-world scenario, you'd have more robust error handling and configuration.
// This script is a straightforward utility for one-time database setup.

// Database credentials from your config/database.php
$host = "127.0.0.1";
$db_name = "beauty_care";
$username = "root";
$password = ""; // Default XAMPP password is empty

try {
    // 1. Connect to MySQL Server (without selecting the database initially)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Drop the database if it exists, and create a new one
    // This ensures a clean slate and that the script is idempotent (rerunnable).
    $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
    $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `$db_name`");
    echo "Database '$db_name' created successfully.\n";

    // 3. Read the SQL file content
    $sql = file_get_contents('database.sql');
    if ($sql === false) {
        throw new Exception("Could not read database.sql file.");
    }
    echo "Read contents of database.sql.\n";
    
    // 4. Execute the SQL commands from the file
    // PDO::exec can handle multiple statements separated by semicolons.
    $pdo->exec($sql);
    echo "Tables and data imported successfully.\n";

    echo "\nDatabase setup complete.\n";

} catch (PDOException $e) {
    // Provide a more specific error message if the connection fails.
    if ($e->getCode() === 2002) { // Cannot connect
        die("ERROR: Could not connect to MySQL server. Please ensure MySQL is running in XAMPP.\nDetails: " . $e->getMessage());
    }
    if ($e->getCode() === 1049) { // Unknown database
        die("ERROR: The database '$db_name' does not exist and could not be created.\nDetails: " . $e->getMessage());
    }
    die("DATABASE ERROR: " . $e->getMessage());

} catch (Exception $e) {
    die("GENERAL ERROR: " . $e->getMessage());
}
?>
