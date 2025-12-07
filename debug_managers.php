<?php
// Debug managers availability
require_once 'application/config/database.php';

$mysqli = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);

if ($mysqli->connect_error) {
    die('Connect Error: ' . $mysqli->connect_error);
}

echo "<h2>Debug: Manager Availability</h2>";

// Check all users with Coach role
echo "<h3>All Coach Users:</h3>";
$query = "SELECT id, username, first_name, last_name, role, team_id FROM users WHERE role = 'Coach'";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Team ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>" . ($row['team_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No Coach users found!</p>";
}

// Check coaches managing teams
echo "<h3>Coaches Managing Teams (created_by):</h3>";
$query = "SELECT DISTINCT u.id, u.first_name, u.last_name, t.team_name 
          FROM users u 
          INNER JOIN teams t ON u.id = t.created_by 
          WHERE u.role = 'Coach'";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Managing Team</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['team_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No coaches are currently managing teams</p>";
}

// Check coaches not part of any team
echo "<h3>Coaches Not Part of Any Team:</h3>";
$query = "SELECT id, first_name, last_name, team_id 
          FROM users 
          WHERE role = 'Coach' AND (team_id IS NULL OR team_id = 0)";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Team ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>" . ($row['team_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No unassigned coaches found</p>";
}

echo "<h3>API Test Links:</h3>";
echo "<p><a href='/github/insytes-web-app/index.php/Admin/TeamController/getAvailableManagers'>Test getAvailableManagers API</a></p>";

$mysqli->close();
?>