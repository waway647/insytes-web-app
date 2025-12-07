<?php
// Simple test to check if ProfileController is accessible
echo "Testing ProfileController access...<br>";

$controller_path = __DIR__ . '/application/controllers/Auth/ProfileController.php';
echo "Controller file exists: " . (file_exists($controller_path) ? "YES" : "NO") . "<br>";

if (file_exists($controller_path)) {
    require_once(__DIR__ . '/system/core/Controller.php');
    require_once($controller_path);
    
    echo "Controller class exists: " . (class_exists('ProfileController') ? "YES" : "NO") . "<br>";
    
    if (class_exists('ProfileController')) {
        echo "Controller looks good!<br>";
    }
}
?>