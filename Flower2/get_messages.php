<?php
// File: get_messages.php
// Handles AJAX requests to fetch new messages

// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "cloud");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Check if session ID is provided
if (!isset($_GET['session_id'])) {
    die(json_encode(['error' => 'No session ID provided']));
}

$session_id = intval($_GET['session_id']);

// Get the last message ID that the client has seen
$last_seen_id = isset($_SESSION['last_message_id_seen']) ? intval($_SESSION['last_message_id_seen']) : 0;

// Get any new messages
$query = "SELECT * FROM chat_messages 
          WHERE session_id = ? 
          AND message_id > ? 
          ORDER BY created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $session_id, $last_seen_id);
$stmt->execute();
$result = $stmt->get_result();

$hasNewMessages = $result->num_rows > 0;
$messages = [];

// If there are new messages
if ($hasNewMessages) {
    while ($row = $result->fetch_assoc()) {
        // Determine message class
        $message_class = '';
        if ($row['sender_type'] == 'user') {
            $message_class = 'user-message';
        } elseif ($row['sender_type'] == 'admin') {
            $message_class = 'admin-message';
        } else {
            $message_class = 'system-message';
        }
        
        $time = date('H:i', strtotime($row['created_at']));
        
        // Prepare the HTML for this message
        $html = '';
        if ($message_class == 'system-message') {
            $html = "<div class='message system-message'>" .
                   htmlspecialchars($row['message']) .
                   "<span class='message-time'>{$time}</span>" .
                   "</div>";
        } else {
            $html = "<div class='message-container clearfix'>" .
                   "<div class='message {$message_class}'>" .
                   htmlspecialchars($row['message']) .
                   "<span class='message-time'>{$time}</span>" .
                   "</div>" .
                   "</div>";
        }
        
        $messages[] = [
            'id' => $row['message_id'],
            'html' => $html
        ];
        
        // Update the last seen message ID
        if ($row['message_id'] > $last_seen_id) {
            $last_seen_id = $row['message_id'];
            $_SESSION['last_message_id_seen'] = $last_seen_id;
        }
    }
}

// Mark user messages as read if user is admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $mark_read_query = "UPDATE chat_messages 
                        SET is_read = 1 
                        WHERE session_id = ? 
                        AND sender_type = 'user' 
                        AND is_read = 0";
    $mark_stmt = $conn->prepare($mark_read_query);
    $mark_stmt->bind_param("i", $session_id);
    $mark_stmt->execute();
    $mark_stmt->close();
}

// Return the result as JSON
echo json_encode([
    'hasNewMessages' => $hasNewMessages,
    'messages' => $messages
]);

$conn->close();
?>