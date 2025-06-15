<?php
if (!isset($_GET['token'])) {
    echo "Token missing.";
    exit;
}
// Include the class file if it is in a separate file
require_once 'TokenizedFileManager.php';

// Configure your database connection details
$dbHost = 'localhost';
$dbName = 'lococloud';
$dbUser = 'loco';
$dbPass = '#1234567*()';

try {
    $fileManager = new TokenizedFileManager($dbHost, $dbName, $dbUser, $dbPass);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $token = $_GET["token"];
        $fileManager -> handleDownload($token);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
