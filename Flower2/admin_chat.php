<?php
// File: admin_chat.php
session_start();
$title = "Chat Management - Graduation Shop";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include header file (with admin navigation)
include("includes/header.php");

// Include database connection
include("includes/db_connect.php");

// Get active chat sessions - prevent duplicates by joining with latest message
$chat_query = "SELECT cs.*, 
                  IFNULL(u.first_name, cs.guest_name) AS name, 
                  IFNULL(u.usergmail, cs.guest_email) AS email,
                  (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.session_id AND sender_type = 'user' AND is_read = 0) AS unread
               FROM chat_sessions cs
               LEFT JOIN users u ON cs.user_id = u.id
               WHERE cs.status = 'active'
               ORDER BY cs.updated_at DESC";
$chats = $conn->query($chat_query);

// Handle specific chat selection
$selected_chat = null;
$messages = null;

if(isset($_GET['chat_id'])) {
    $chat_id = intval($_GET['chat_id']);
    
    // Get chat info
    $chat_info_query = "SELECT cs.*, 
                           IFNULL(u.first_name, cs.guest_name) AS name, 
                           IFNULL(u.usergmail, cs.guest_email) AS email
                        FROM chat_sessions cs
                        LEFT JOIN users u ON cs.user_id = u.id
                        WHERE cs.session_id = ?";
    $stmt = $conn->prepare($chat_info_query);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $selected_chat = $stmt->get_result()->fetch_assoc();
    
    // Mark messages as read
    $mark_read_query = "UPDATE chat_messages SET is_read = 1 
                       WHERE session_id = ? AND sender_type = 'user' AND is_read = 0";
    $stmt = $conn->prepare($mark_read_query);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    
    // Get messages
    $messages_query = "SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $messages = $stmt->get_result();
}

// Handle message sending
if(isset($_POST['send_message']) && isset($_POST['chat_id'])) {
    $message = $conn->real_escape_string($_POST['message']);
    $chat_id = intval($_POST['chat_id']);
    $admin_id = $_SESSION['admin_id'];
    
    $send_query = "INSERT INTO chat_messages (session_id, sender_type, message, is_read) 
              VALUES (?, 'admin', ?, 1)";
    $stmt = $conn->prepare($send_query);
    $stmt->bind_param("is", $chat_id, $message);
    
    if($stmt->execute()) {
        // Update chat session timestamp
        $update_query = "UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        
        // Redirect to refresh page
        header("Location: admin_chat.php?chat_id=" . $chat_id);
        exit();
    }
}

// Handle closing chat
if(isset($_GET['close']) && isset($_GET['chat_id'])) {
    $chat_id = intval($_GET['chat_id']);
    
    $close_query = "UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE session_id = ?";
    $stmt = $conn->prepare($close_query);
    $stmt->bind_param("i", $chat_id);
    
    if($stmt->execute()) {
        // Add system message
        $system_message = "Chat closed by administrator.";
        $msg_query = "INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'system', ?)";
        $stmt = $conn->prepare($msg_query);
        $stmt->bind_param("is", $chat_id, $system_message);
        $stmt->execute();
        
        header("Location: admin_chat.php");
        exit();
    }
}
?>

<link rel="stylesheet" href="css/admin.css">
<style>
    .chat-container {
        display: flex;
        height: calc(100vh - 150px);
        margin-locale_filter_matches: 20px;
        width:100%;
    }
    
    .chat-list {
        width: 30%;
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 15px;
        margin-right: 20px;
        overflow-y: auto;
    }
    
    .chat-item {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        position: relative;
    }
    
    .chat-item:hover, .chat-item.active {
        background-color: #ebebeb;
    }
    
    .chat-name {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .chat-email {
        color: #777;
        font-size: 0.9em;
        margin-bottom: 5px;
    }
    
    .chat-time {
        color: #999;
        font-size: 0.8em;
    }
    
    .chat-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background-color: #d6336c;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 0.8em;
    }
    
    .chat-window {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        background-color: #f8f8f8;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chat-header h3 {
        margin: 0;
    }
    
    .chat-header p {
        color: #777;
        margin: 5px 0 0 0;
    }
    
    .chat-messages {
        flex-grow: 1;
        padding: 15px;
        overflow-y: auto;
        background-color: #f9f9f9;
    }
    
    .message {
        margin-bottom: 15px;
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 70%;
        position: relative;
        clear: both;
    }
    
    .user-message {
        background-color: #e6e6e6;
        float: left;
    }
    
    .admin-message {
        background-color: #d6336c;
        color: white;
        float: right;
    }
    
    .system-message {
        background-color: #f8d7da;
        color: #721c24;
        text-align: center;
        width: 80%;
        margin: 15px auto;
        border-radius: 5px;
        padding: 8px;
        clear: both;
        float: none;
    }
    
    .message-time {
        font-size: 0.7em;
        color: #999;
        margin-top: 5px;
        display: block;
    }
    
    .admin-message .message-time {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .chat-input {
        padding: 15px;
        background-color: #f8f8f8;
        border-top: 1px solid #e0e0e0;
        display: flex;
    }
    
    .chat-input form {
        display: flex;
        width: 100%;
    }
    
    .chat-input input {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-right: 10px;
    }
    
    .chat-input button {
        background-color: #d6336c;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .chat-input button:hover {
        background-color: #b52b5c;
    }
    
    .no-chat-selected {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #777;
    }
    
    .no-chat-selected i {
        font-size: 3em;
        margin-bottom: 15px;
        color: #ccc;
    }
    
    .no-chats {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #777;
        text-align: center;
        padding: 0 20px;
    }
    
    .close-chat-btn {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
    }
    
    .close-chat-btn:hover {
        background-color: #5a6268;
    }
    
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
</style>

<div class="admin-content">
    <div class="admin-content-header">
        <h2><i class="fas fa-comments"></i> Chat Management</h2>
    </div>
    
    <div class="chat-container">
        <div class="chat-list">
            <h3>Active Chats</h3>
            <?php if($chats->num_rows > 0): ?>
                <?php 
                // Track unique emails to prevent duplicates in the list
                $unique_emails = array();
                while($chat = $chats->fetch_assoc()): 
                    // Skip if we already displayed this email
                    if(in_array($chat['email'], $unique_emails)) continue;
                    $unique_emails[] = $chat['email'];
                ?>
                    <div class="chat-item <?php echo (isset($_GET['chat_id']) && $_GET['chat_id'] == $chat['session_id']) ? 'active' : ''; ?>" 
                         onclick="window.location='admin_chat.php?chat_id=<?php echo $chat['session_id']; ?>'">
                        <div class="chat-name"><?php echo htmlspecialchars($chat['name']); ?></div>
                        <div class="chat-email"><?php echo htmlspecialchars($chat['email']); ?></div>
                        <div class="chat-time">Started: <?php echo date('M j, g:i a', strtotime($chat['created_at'])); ?></div>
                        <?php if($chat['unread'] > 0): ?>
                            <div class="chat-badge"><?php echo $chat['unread']; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-chats">
                    <p>No active chats at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-window">
            <?php if($selected_chat): ?>
                <div class="chat-header">
                    <div>
                        <h3><?php echo htmlspecialchars($selected_chat['name']); ?></h3>
                        <p><?php echo htmlspecialchars($selected_chat['email']); ?></p>
                    </div>
                    <a href="admin_chat.php?close=1&chat_id=<?php echo $selected_chat['session_id']; ?>" 
                       class="close-chat-btn" 
                       onclick="return confirm('Are you sure you want to close this chat?');">
                        Close Chat
                    </a>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if($messages && $messages->num_rows > 0): ?>
                        <?php while($message = $messages->fetch_assoc()): ?>
                            <?php if($message['sender_type'] == 'system'): ?>
                                <div class="message system-message">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                    <span class="message-time"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="message-container clearfix">
                                    <div class="message <?php echo ($message['sender_type'] == 'user') ? 'user-message' : 'admin-message'; ?>">
                                        <?php echo htmlspecialchars($message['message']); ?>
                                        <span class="message-time"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">No messages in this chat yet.</div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <form method="post" action="">
                        <input type="hidden" name="chat_id" value="<?php echo $selected_chat['session_id']; ?>">
                        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off" autofocus>
                        <button type="submit" name="send_message">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-chat-selected">
                    <i class="fas fa-comments"></i>
                    <p>Select a chat from the list to start responding</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto-scroll to bottom of chat messages
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
    
    // Auto-refresh for new messages (every 10 seconds)
    <?php if($selected_chat): ?>
    setInterval(function() {
        fetch('get_admin_chat_updates.php?chat_id=<?php echo $selected_chat['session_id']; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.newMessages) {
                    window.location.reload();
                }
            });
    }, 10000); // 10 seconds
    <?php endif; ?>
    
    // Auto-refresh chat list (every 30 seconds)
    setInterval(function() {
        fetch('get_admin_chat_list.php')
            .then(response => response.text())
            .then(data => {
                document.querySelector('.chat-list').innerHTML = data;
                
                // Re-add active class to current chat if any
                <?php if($selected_chat): ?>
                const currentChatItem = document.querySelector(`.chat-item[onclick*="chat_id=<?php echo $selected_chat['session_id']; ?>"]`);
                if (currentChatItem) {
                    currentChatItem.classList.add('active');
                }
                <?php endif; ?>
            });
    }, 30000); // 30 seconds
</script>

<?php 
$conn->close(); 
include("includes/footer.php"); 
?>