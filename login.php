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

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$photoData = $_POST['photo'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Username and password are required."]);
    exit;
}

// Validate username and password
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($hashedPassword);
$stmt->fetch();
$stmt->close();

if (!$hashedPassword) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}

if (!password_verify($password, $hashedPassword)) {
    echo json_encode(["status" => "error", "message" => "Invalid password."]);
    exit;
}

// If no photo is provided, return success after password validation
if (empty($photoData)) {
    echo json_encode(["status" => "success", "message" => "Password validated. Please proceed with face verification."]);
    exit;
}

// Decode Base64 image for login
if (strpos($photoData, ',') !== false) {
    $photoData = explode(',', $photoData)[1];
}

$decodedPhoto = base64_decode($photoData);
if ($decodedPhoto === false) {
    echo json_encode(["status" => "error", "message" => "Invalid image data."]);
    exit;
}

// Save the decoded image for comparison
$uploadDir = 'uploads/';
$loginPhotoPath = $uploadDir . 'current_login_photo.png';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!file_put_contents($loginPhotoPath, $decodedPhoto)) {
    echo json_encode(["status" => "error", "message" => "Failed to save login image."]);
    exit;
}

// Python script for face recognition
$registeredFacesDir = 'uploads/registered_faces/';
$command = escapeshellcmd("python M:/Xampp/htdocs/Form/face_recognition_script.py $loginPhotoPath $registeredFacesDir");
$output = shell_exec($command);

// Debugging: Log the Python script output
error_log("Python Script Output: " . $output);

if ($output === null) {
    echo json_encode(["status" => "error", "message" => "Face recognition script execution failed."]);
} elseif (strpos($output, "Match found") !== false) {
    echo json_encode(["status" => "success", "message" => "Login successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "No matching face found."]);
}

// Clean up temporary login photo
if (file_exists($loginPhotoPath)) {
    unlink($loginPhotoPath);
}

$conn->close();
?>