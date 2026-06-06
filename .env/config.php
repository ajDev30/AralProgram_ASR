<?php
// Database configuration credentials
$host     = 'localhost';
$db_name  = 'aral_programDB'; // Replace with your actual database name
$username = 'andrew';        // Replace with your database username
$password = '1234';            // Replace with your database password
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // In production, change this to a generic message to keep database details secure
    die("Database connection failed: " . $e->getMessage());
}
?>
