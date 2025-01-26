<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Error: Invalid request method.";
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_auth');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo "Error: Username is required.";
    exit;
}

// Fetch the user's photo path from the database
$stmt = $conn->prepare("SELECT photo FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($photoPath);
$stmt->fetch();
$stmt->close();

if (!$photoPath) {
    echo "Error: User not found.";
    exit;
}

// Delete the user from the database
$stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    // Attempt to delete the photo file
    if (file_exists($photoPath)) {
        unlink($photoPath); // Delete the photo file
    }
    echo "User deleted successfully!";
} else {
    echo "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
