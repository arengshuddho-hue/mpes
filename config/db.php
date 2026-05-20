<?php
// config/db.php
// This file is responsible for establishing the connection to the MySQL database.

// Database configuration variables
$host = 'localhost';
$dbname = 'mpes_db';
$username = 'root'; // default xampp/mamp username
$password = ''; // default xampp/mamp password

try {
    // Attempt to create a new PDO instance for database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set PDO error mode to exception so errors throw exceptions instead of silent failures
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array for easier data handling
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Catch any connection errors and stop script execution, displaying the error message
    die("Database Connection failed: " . $e->getMessage());
}
?>
