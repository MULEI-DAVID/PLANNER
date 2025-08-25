<?php
// Database Setup Script for C-Planner
// This script will create the database and all necessary tables

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up C-Planner Database...</h2>";
    
    // Read the schema file
    $schema = file_get_contents('database/schema.sql');
    
    // Split the schema into individual statements
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                // Ignore errors for CREATE DATABASE IF NOT EXISTS and USE statements
                if (strpos($statement, 'CREATE DATABASE IF NOT EXISTS') !== false || 
                    strpos($statement, 'USE') !== false) {
                    echo "<p style='color: blue;'>ℹ Skipped: " . substr($statement, 0, 50) . "...</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<h3 style='color: green;'>✅ Database setup completed successfully!</h3>";
    echo "<p><strong>Default login credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@cplanner.com</li>";
    echo "<li><strong>Password:</strong> password123</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to C-Planner</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Database Setup Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL connection settings in <code>config/database.php</code></p>";
    echo "<p>Make sure MySQL is running and the credentials are correct.</p>";
}
?>
