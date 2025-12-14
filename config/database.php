<?php
// config/database.php

class Database {
    // Database credentials
    // Note: In a real production environment, these should be stored securely
    // and not hardcoded in the source code, e.g., using environment variables.
    private $host = "127.0.0.1";
    private $db_name = "beauty_care";
    private $username = "root";
    private $password = ""; // Default XAMPP password is empty
    public $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;
        $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("set names utf8");
        return $this->conn;
    }
}
?>