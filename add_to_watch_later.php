<?php
session_start();
require_once 'db_connect.php'; // Your database connection file

// Set response header to JSON
header('Content-Type: application/json');

// Function to log errors
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . "add_to_watch_later.php: " . $message . "\n", 3, "error.log");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logError("User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Determine if data is JSON or FormData
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// If JSON data is provided
if ($data && isset($data['title'])) {
    $title = trim($data['title']);
    $image = trim($data['image']);
    $video = trim($data['video']);
} else {
    // Otherwise, assume FormData via $_POST
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $image = isset($_POST['image']) ? trim($_POST['image']) : null;
    $video = isset($_POST['video']) ? trim($_POST['video']) : null;
}

// Log the received data for debugging
logError("Received data: user_id=$user_id, title=$title, image=$image, video=$video");

// Validate required fields
if (empty($title) || empty($image) || empty($video)) {
    logError("Missing required fields in request");
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Sanitize inputs
$user_id = filter_var($user_id, FILTER_VALIDATE_INT);
$title = filter_var($title, FILTER_SANITIZE_STRING);
$image = filter_var($image, FILTER_SANITIZE_URL);
$video = filter_var($video, FILTER_SANITIZE_URL);

if ($user_id === false || empty($title) || empty($image) || empty($video)) {
    logError("Invalid data after sanitization - user_id: $user_id, title: $title, image: $image, video: $video");
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

// Check if the item already exists in the user's Watch Later list
$sql_check = "SELECT id FROM watch_later WHERE user_id = ? AND title = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $user_id, $title);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    $stmt_check->close();
    logError("Item already exists in Watch Later for user_id: $user_id, title: $title");
    echo json_encode(['success' => false, 'message' => 'This item is already in your Watch Later list']);
    exit();
}
$stmt_check->close();

// Insert the item into the watch_later table
$sql = "INSERT INTO watch_later (user_id, title, image, video, added_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $user_id, $title, $image, $video);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Added to Watch Later']);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    logError("Database error: $error");
    echo json_encode(['success' => false, 'message' => 'Error adding to Watch Later']);
}
?>