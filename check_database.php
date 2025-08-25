<?php
// Database Verification Script for C-Planner
// This script checks if the database and tables are properly set up

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<h2 style='color: green;'>✅ Database Connection Successful!</h2>";
        
        // Check if tables exist
        $tables = ['users', 'tasks', 'events', 'finances', 'budgets', 'comments', 'attachments', 'task_history'];
        $existing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                $existing_tables[] = $table;
                echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            }
        }
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['admin@cplanner.com']);
        $user_count = $stmt->fetchColumn();
        
        if ($user_count > 0) {
            echo "<p style='color: green;'>✓ Admin user exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Admin user missing</p>";
        }
        
        echo "<h3>Database Status Summary:</h3>";
        echo "<p><strong>Tables found:</strong> " . count($existing_tables) . " / " . count($tables) . "</p>";
        echo "<p><strong>Admin user:</strong> " . ($user_count > 0 ? "Present" : "Missing") . "</p>";
        
        if (count($existing_tables) == count($tables) && $user_count > 0) {
            echo "<h3 style='color: green;'>🎉 Database is ready! You can now use C-Planner.</h3>";
            echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to C-Planner</a></p>";
        } else {
            echo "<h3 style='color: orange;'>⚠ Database setup incomplete. Please run setup_database.php first.</h3>";
            echo "<p><a href='setup_database.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Database Setup</a></p>";
        }
        
    } else {
        echo "<h2 style='color: red;'>❌ Database Connection Failed</h2>";
        echo "<p>Please check your database configuration.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Database Error</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>This usually means:</p>";
    echo "<ul>";
    echo "<li>MySQL is not running</li>";
    echo "<li>Database 'c_planner' doesn't exist</li>";
    echo "<li>Wrong database credentials</li>";
    echo "</ul>";
    echo "<p><a href='setup_database.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Database Setup</a></p>";
}
?>


