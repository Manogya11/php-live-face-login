<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_auth');
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

$usernameOrEmail = $_POST['usernameOrEmail'] ?? '';

if (empty($usernameOrEmail)) {
    echo json_encode(["status" => "error", "message" => "Please provide a username or email."]);
    exit;
}

// Check if the user exists
$stmt = $conn->prepare("SELECT email FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

if (!$email) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}

// Generate a temporary password (for simplicity)
$tempPassword = bin2hex(random_bytes(4)); // 8-character random password
$hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

// Update the password in the database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Your temporary password is: $tempPassword"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to reset password."]);
}

$stmt->close();
$conn->close();
?>
