<?php
try {
    require_once 'application/config/database.php';
    echo "Database config loaded.\n";
    
    $dsn = 'mysql:host=' . $db['default']['hostname'] . ';dbname=' . $db['default']['database'];
    $pdo = new PDO($dsn, $db['default']['username'], $db['default']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: " . $db['default']['database'] . "\n";
    
    // Check if logs table exists
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute(['logs']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Logs table EXISTS.\n";
        
        // Check table structure
        $structure = $pdo->query('DESCRIBE logs');
        echo "\nTable structure:\n";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
        // Check row count
        $count = $pdo->query('SELECT COUNT(*) FROM logs')->fetchColumn();
        echo "\nTotal logs: " . $count . "\n";
        
        // Show sample logs
        if ($count > 0) {
            echo "\nSample logs:\n";
            $logs = $pdo->query('SELECT * FROM logs ORDER BY created_at DESC LIMIT 3');
            while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $log['created_at'] . " | " . $log['category'] . " | " . $log['action'] . "\n";
            }
        }
    } else {
        echo "❌ Logs table does NOT exist - need to create it.\n";
        
        // Show existing tables
        echo "\nExisting tables:\n";
        $tables = $pdo->query('SHOW TABLES');
        while ($table = $tables->fetch(PDO::FETCH_NUM)) {
            echo "- " . $table[0] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>