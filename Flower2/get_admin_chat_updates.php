<?php
// File: get_admin_chat_updates.php
// Check for new messages in a specific chat session (for admin panel)

session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    die(json_encode(['error' => 'Not authorized']));
}

// Include database connection
$conn = new mysqli("localhost", "root", "", "cloud");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

if(!isset($_GET['chat_id'])) {
    die(json_encode(['error' => 'No chat ID provided']));
}

$chat_id = intval($_GET['chat_id']);

// Get the last message ID that the client has seen
$last_seen_id = isset($_SESSION['admin_last_seen_' . $chat_id]) ? intval($_SESSION['admin_last_seen_' . $chat_id]) : 0;

// Check for new messages in this chat
$query = "SELECT COUNT(*) as count FROM chat_messages 
          WHERE session_id = ? 
          AND message_id > ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $chat_id, $last_seen_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$newMessages = $row['count'] > 0;

// Get the latest message ID for this chat
if ($newMessages) {
    $query = "SELECT MAX(message_id) as max_id FROM chat_messages WHERE session_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $_SESSION['admin_last_seen_' . $chat_id] = $row['max_id'];
}

echo json_encode([
    'newMessages' => $newMessages
]);

$conn->close();
?>