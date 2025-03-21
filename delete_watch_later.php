<?php
session_start();
require_once 'db_connect.php'; // Your database connection file

// Set response header to JSON
header('Content-Type: application/json');

// Function to log errors
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . "delete_watch_later.php: " . $message . "\n", 3, "error.log");
}

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log the received data for debugging
logError("Received data: " . print_r($data, true));

// Validate required fields
if (!isset($data['id'])) {
    logError("Missing required field: id");
    echo json_encode(['success' => false, 'message' => 'Missing required field: id']);
    exit();
}

// Sanitize input
$watch_later_id = filter_var($data['id'], FILTER_VALIDATE_INT);

if ($watch_later_id === false) {
    logError("Invalid watch_later_id: " . $data['id']);
    echo json_encode(['success' => false, 'message' => 'Invalid Watch Later ID']);
    exit();
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    logError("User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify that the item belongs to the user
$sql_check = "SELECT user_id FROM watch_later WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $watch_later_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    $stmt_check->close();
    logError("Item not found for watch_later_id: $watch_later_id");
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}

$row = $result->fetch_assoc();
if ($row['user_id'] !== $user_id) {
    $stmt_check->close();
    logError("Unauthorized access attempt by user_id: $user_id for watch_later_id: $watch_later_id");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$stmt_check->close();

// Delete the item from the watch_later table
$sql = "DELETE FROM watch_later WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $watch_later_id, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Removed from Watch Later']);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    logError("Database error: $error");
    echo json_encode(['success' => false, 'message' => 'Error removing from Watch Later']);
}
?>