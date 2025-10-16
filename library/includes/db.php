<?php
// =========================================
// Database Connection File
// =========================================

// Database credentials
$host = "localhost";       // usually "localhost"
$user = "root";            // your MySQL username (default: root)
$pass = "";                // your MySQL password (default: empty on XAMPP)
$dbname = "library_db";    // database name (must match your database.sql)

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// Optional: Set timezone (for timestamps)
date_default_timezone_set('Asia/Jakarta');
?>
