<?php
// Simple test file to check manager API
require_once 'application/config/config.php';
require_once 'application/config/database.php';

// Initialize database connection
$db['default'] = array(
    'dsn'      => '',
    'hostname' => $db['default']['hostname'],
    'username' => $db['default']['username'], 
    'password' => $db['default']['password'],
    'database' => $db['default']['database'],
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

try {
    $mysqli = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
    
    if ($mysqli->connect_error) {
        die('Connect Error: ' . $mysqli->connect_error);
    }
    
    echo "<h2>Testing Manager Data</h2>";
    
    // Test 1: Check users table for potential managers
    echo "<h3>Available Users (Coach/Admin roles):</h3>";
    $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.role,
              CASE WHEN t.created_by IS NOT NULL THEN 'Assigned' ELSE 'Available' END as status
              FROM users u
              LEFT JOIN teams t ON u.id = t.created_by
              WHERE u.role IN ('Coach', 'Admin')
              ORDER BY status, u.first_name, u.last_name";
    
    $result = $mysqli->query($query);
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $name = trim($row['first_name'] . ' ' . $row['last_name']);
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$name}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Check current teams and their managers
    echo "<h3>Current Teams and Managers:</h3>";
    $query = "SELECT t.id, t.team_name, t.created_by,
              CONCAT(u.first_name, ' ', u.last_name) as manager_name,
              u.role
              FROM teams t
              LEFT JOIN users u ON t.created_by = u.id
              ORDER BY t.team_name";
    
    $result = $mysqli->query($query);
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Team ID</th><th>Team Name</th><th>Manager ID</th><th>Manager Name</th><th>Role</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['team_name']}</td>";
            echo "<td>{$row['created_by']}</td>";
            echo "<td>{$row['manager_name']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>API Test:</h3>";
    echo "<p>You can test the API endpoint at: <a href='/github/insytes-web-app/index.php/Admin/TeamController/getAvailableManagers' target='_blank'>/github/insytes-web-app/index.php/Admin/TeamController/getAvailableManagers</a></p>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>