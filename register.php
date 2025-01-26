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

// Get form inputs
$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$mobile = $_POST['mobile'];
$address = $_POST['address'];
$occupation = $_POST['occupation'];
$age = intval($_POST['age']); // Ensure age is stored as an integer

// Handle Base64 photo input
if (!empty($_POST['photo'])) {
    $uploadDir = 'uploads/registered_faces/';

    // Ensure the upload directory exists
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo "Error: Failed to create upload directory.";
            exit;
        }
    }

    // Decode the Base64 image
    $base64String = $_POST['photo'];
    $imageData = explode(',', $base64String)[1] ?? $base64String; // Handle header
    $decodedImage = base64_decode($imageData);

    if ($decodedImage === false) {
        echo "Error: Invalid image data.";
        exit;
    }

    // Generate a unique filename
    $photoFileName = $username . '_' . time() . '.png';
    $photoFilePath = $uploadDir . $photoFileName;

    // Save the decoded image
    if (!file_put_contents($photoFilePath, $decodedImage)) {
        echo "Error: Failed to save the image.";
        exit;
    }

    // Save user details and image path to the database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, mobile, address, occupation, age, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $username, $email, $password, $mobile, $address, $occupation, $age, $photoFilePath);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error saving user data: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error: No photo data provided.";
}

$conn->close();
?>
