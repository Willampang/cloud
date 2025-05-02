<?php
// File: get_admin_chat_list.php
// Return updated list of active chats for admin panel

session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    die("Not authorized");
}

// Include database connection
$conn = new mysqli("localhost", "root", "", "cloud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get active chat sessions with unread count
$chat_query = "SELECT cs.*, 
                IFNULL(u.first_name, cs.guest_name) AS name, 
                IFNULL(u.usergmail, cs.guest_email) AS email,
                (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.session_id AND sender_type = 'user' AND is_read = 0) AS unread
              FROM chat_sessions cs
              LEFT JOIN users u ON cs.user_id = u.id
              WHERE cs.status = 'active'
              ORDER BY cs.updated_at DESC";
$chats = $conn->query($chat_query);

// Output the HTML for the chat list
echo '<h3>Active Chats</h3>';

if($chats->num_rows > 0) {
    // Track unique emails to prevent duplicates in the list
    $unique_emails = array();
    while($chat = $chats->fetch_assoc()) {
        // Skip if we already displayed this email
        if(in_array($chat['email'], $unique_emails)) continue;
        $unique_emails[] = $chat['email'];
        
        $active_class = (isset($_GET['chat_id']) && $_GET['chat_id'] == $chat['session_id']) ? 'active' : '';
        ?>
        <div class="chat-item <?php echo $active_class; ?>" 
             onclick="window.location='admin_chat.php?chat_id=<?php echo $chat['session_id']; ?>'">
            <div class="chat-name"><?php echo htmlspecialchars($chat['name']); ?></div>
            <div class="chat-email"><?php echo htmlspecialchars($chat['email']); ?></div>
            <div class="chat-time">Started: <?php echo date('M j, g:i a', strtotime($chat['created_at'])); ?></div>
            <?php if($chat['unread'] > 0): ?>
                <div class="chat-badge"><?php echo $chat['unread']; ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
} else {
    echo '<div class="no-chats"><p>No active chats at the moment.</p></div>';
}

$conn->close();
?>