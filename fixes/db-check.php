<?php
/**
 * Database Connection Diagnostic Script
 * Run this to check your database connection
 */

echo "<h1>Database Connection Diagnostic</h1>";

echo "<h2>1. Checking PHP MySQLi extension...</h2>";
if (extension_loaded('mysqli')) {
    echo "<p style='color:green'>✓ mysqli extension is loaded</p>";
} else {
    echo "<p style='color:red'>✗ mysqli extension is NOT loaded</p>";
}

echo "<h2>2. Checking XAMPP MySQL Service...</h2>";
// Check if we can connect to MySQL without database
$conn = @mysqli_connect('localhost', 'root', '');
if ($conn) {
    echo "<p style='color:green'>✓ Can connect to MySQL (no password)</p>";
    mysqli_close($conn);
} else {
    echo "<p style='color:red'>✗ Cannot connect to MySQL (no password)</p>";
    echo "<p>Error: " . mysqli_connect_error() . "</p>";
    
    // Try with empty password but different host
    echo "<h3>Trying alternative connections...</h3>";
    
    // Try 127.0.0.1 instead of localhost
    $conn = @mysqli_connect('127.0.0.1', 'root', '');
    if ($conn) {
        echo "<p style='color:green'>✓ Connected via 127.0.0.1</p>";
        mysqli_close($conn);
    } else {
        echo "<p style='color:red'>✗ Cannot connect via 127.0.0.1</p>";
    }
    
    // Try with password (common in some XAMPP setups)
    $conn = @mysqli_connect('localhost', 'root', 'root');
    if ($conn) {
        echo "<p style='color:green'>✓ Connected with password 'root'</p>";
        mysqli_close($conn);
    } else {
        echo "<p style='color:red'>✗ Cannot connect with password 'root'</p>";
    }
}

echo "<h2>3. Current Config Values...</h2>";
echo "<ul>";
echo "<li>DB_HOST: localhost</li>";
echo "<li>DB_USER: root</li>";
echo "<li>DB_PASS: (empty)</li>";
echo "<li>DB_NAME: attendance_system</li>";
echo "</ul>";

echo "<h2>4. Recommendations...</h2>";
echo "<ol>";
echo "<li>Make sure XAMPP MySQL service is running (check XAMPP Control Panel)</li>";
echo "<li>Try accessing phpMyAdmin at http://localhost/phpmyadmin</li>";
echo "<li>If you can access phpMyAdmin, check the users table to see what credentials work</li>";
echo "<li>Make sure the 'attendance_system' database exists</li>";
echo "</ol>";

echo "<h2>5. Quick Fix - Update config...</h2>";
echo "<p>If you find the correct MySQL credentials, update <code>config/db.php</code></p>";
?>

