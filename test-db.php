<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "✅ Database connected successfully!<br>";
    
    // Test query
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Users table accessible! Count: " . $row['count'] . "<br>";
    
    // Test all tables exist
    $tables = ['users', 'projects', 'tasks', 'kb_posts', 'kb_comments'];
    echo "<br>Checking tables:<br>";
    foreach ($tables as $table) {
        $query = "SELECT COUNT(*) as count FROM $table";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ $table: " . $row['count'] . " rows<br>";
    }
} else {
    echo "❌ Database connection failed!<br>";
}
?>
