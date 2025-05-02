<?php
// Start or resume session
session_start();
include("includes/db_connect.php");

$is_logged_in = isset($_SESSION['first_name']) || 
                (isset($_SESSION['admin_id']) && $_SESSION['is_admin'] === true);
                
// Get the user ID from the session - FIXED: ensure this is a numeric ID from the users table
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
          
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 
            (isset($_SESSION['admin_email']) ? 'Admin' : '');
            
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Check if there's an active chat session for this user
$chat_session_id = isset($_SESSION['chat_session_id']) ? $_SESSION['chat_session_id'] : null;

// Handle new chat session creation - only for logged-in users
if (isset($_POST['start_chat']) && $is_logged_in) {
    $guest_name = $conn->real_escape_string($_POST['guest_name']);
    $guest_email = $conn->real_escape_string($_POST['guest_email']);
    
    // Check if the user already has an active chat session
    $check_sql = "SELECT session_id FROM chat_sessions WHERE 
                 (user_id = ? OR guest_email = ?) AND status = 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $user_id, $guest_email);
    $check_stmt->execute();
    $existing_session = $check_stmt->get_result();
    
    if ($existing_session->num_rows > 0) {
        // Use existing session
        $row = $existing_session->fetch_assoc();
        $chat_session_id = $row['session_id'];
        $_SESSION['chat_session_id'] = $chat_session_id;
    } else {
        // Create new chat session - FIXED: Check if user_id is valid
        if ($user_id !== null) {
            // User is logged in with a valid ID
            $sql = "INSERT INTO chat_sessions (user_id, guest_name, guest_email, status) 
                    VALUES (?, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $guest_name, $guest_email);
        } else {
            // No valid user ID - set user_id to NULL
            $sql = "INSERT INTO chat_sessions (user_id, guest_name, guest_email, status) 
                    VALUES (NULL, ?, ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $guest_name, $guest_email);
        }
        
        if ($stmt->execute()) {
            $chat_session_id = $conn->insert_id;
            $_SESSION['chat_session_id'] = $chat_session_id;
            
            // Add welcome message from admin
            $welcome_msg = "Welcome to our support chat! How can we help you today?";
            $sql = "INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, 'admin', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $chat_session_id, $welcome_msg);
            $stmt->execute();
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Handle message sending
if (isset($_POST['send_message']) && isset($_SESSION['chat_session_id']) && $is_logged_in) {
    $message = $conn->real_escape_string($_POST['message']);
    $session_id = $_SESSION['chat_session_id'];
    
    // Check if this is a repeated submission (prevent duplications)
    $prevent_duplicate = false;
    
    // Create a simple hash of the message to detect duplicates
    $message_hash = md5($message);
    
    // Check if we've seen this exact message in the last 5 seconds
    if (isset($_SESSION['last_message_hash']) && isset($_SESSION['last_message_time'])) {
        $time_diff = time() - $_SESSION['last_message_time'];
        if ($message_hash === $_SESSION['last_message_hash'] && $time_diff < 5) {
            $prevent_duplicate = true;
        }
    }
    
    if (!$prevent_duplicate) {
        // Store the hash and time of this message
        $_SESSION['last_message_hash'] = $message_hash;
        $_SESSION['last_message_time'] = time();
        
        $sql = "INSERT INTO chat_messages (session_id, sender_type, message, is_read) VALUES (?, 'user', ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $session_id, $message);
        $stmt->execute();
        $stmt->close();
        
        // Update the session's updated_at timestamp
        $update_sql = "UPDATE chat_sessions SET updated_at = NOW() WHERE session_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $session_id);
        $update_stmt->execute();
        $update_stmt->close();
        
    }
}

// Handle closing chat
if (isset($_POST['close_chat']) && isset($_SESSION['chat_session_id']) && $is_logged_in) {
    $session_id = $_SESSION['chat_session_id'];
    
    $sql = "UPDATE chat_sessions SET status = 'closed' WHERE session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $stmt->close();
    
    // Add system message
    $system_message = "Chat closed by user.";
    $msg_query = "INSERT INTO chat_messages (session_id, sender_type, message) VALUES (?, '', ?)";
    $stmt = $conn->prepare($msg_query);
    $stmt->bind_param("is", $session_id, $system_message);
    $stmt->execute();
    
    // Clear chat session
    unset($_SESSION['chat_session_id']);
    $chat_session_id = null;
    
    // Redirect to avoid resubmission
    header("Location: contact.php?closed=1");
    exit();
}

include("includes/header.php");
?>

<div class="chat-container">
    <div class="chat-header">
        <h2><?php echo $chat_session_id ? 'Live Support Chat' : 'Start a Chat with Support'; ?></h2>
    </div>
    
    <?php if (!$is_logged_in): ?>
        <!-- Message for non-logged in users -->
        <div class="login-required-message">
            <p>You must be logged in to chat with our support team.</p>
            <div class="login-buttons-container">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Register</a>
            </div>
        </div>
    <?php elseif (!$chat_session_id): ?>
        <!-- Chat initiation form - only shown to logged-in users -->
        <form class="chat-form" method="post" action="">
            <div class="form-group">
                <label for="guest_name">Your Name:</label>
                <input type="text" name="guest_name" id="guest_name" required 
                       value="<?php echo $user_name; ?>">
            </div>
            <div class="form-group">
                <label for="guest_email">Your Email:</label>
                <input type="email" name="guest_email" id="guest_email" required>
            </div>
            <button type="submit" name="start_chat" class="btn">Start Chat</button>
        </form>
<?php else: ?>
        <!-- Chat messages area -->
        <div class="chat-messages" id="chatMessages">
            <?php
            // Fetch all messages for this session
            $sql = "SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $chat_session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Initialize last_message_id_seen if not set
            if (!isset($_SESSION['last_message_id_seen'])) {
                $_SESSION['last_message_id_seen'] = 0;
            }
            
            while ($row = $result->fetch_assoc()) {
                $message_class = '';
                
                if ($row['sender_type'] == 'user') {
                    $message_class = 'user-message';
                } elseif ($row['sender_type'] == 'admin') {
                    $message_class = 'admin-message';
                } else {
                    $message_class = 'system-message';
                }
                
                $time = date('H:i', strtotime($row['created_at']));
                
                if ($message_class == 'system-message') {
                    echo "<div class='message system-message'>";
                    echo htmlspecialchars($row['message']);
                    echo "<span class='message-time'>{$time}</span>";
                    echo "</div>";
                } else {
                    echo "<div class='message-container clearfix'>";
                    echo "<div class='message {$message_class}'>";
                    echo htmlspecialchars($row['message']);
                    echo "<span class='message-time'>{$time}</span>";
                    echo "</div>";
                    echo "</div>";
                }
                
                // Update last_message_id_seen - FIXED: Use message_id, not id
                if ($row['message_id'] > $_SESSION['last_message_id_seen']) {
                    $_SESSION['last_message_id_seen'] = $row['message_id'];
                }
            }
            $stmt->close();
            ?>
        </div>
    
        <form method="post" action="" class="chat-input">
            <input type="text" name="message" placeholder="Type Your Message here......"required>
            <button type="submit" name="send_message">Send</button>
        </form>


        
        <form method="post" action="">
            <button type="submit" name="close_chat" class="btn close-chat">End Chat</button>
        </form>
        
        <script>
            // Define session ID constant for use in JavaScript
            const SESSION_ID = <?php echo json_encode($chat_session_id); ?>;
            
            // Scroll to bottom of chat
            function scrollToBottom() {
                const chatContainer = document.getElementById('chatMessages');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }
            
            // Function to check for new messages
            function checkForNewMessages() {
                const sessionId = SESSION_ID;
                if (!sessionId) return;
                
                fetch('get_messages.php?session_id=' + sessionId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.hasNewMessages) {
                            // Instead of reloading the page, append new messages
                            const chatContainer = document.getElementById('chatMessages');
                            data.messages.forEach(msg => {
                                // Append each new message
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = msg.html;
                                
                                // Append the actual elements
                                while (tempDiv.firstChild) {
                                    chatContainer.appendChild(tempDiv.firstChild);
                                }
                            });
                            
                            // Scroll to the bottom after adding new messages
                            scrollToBottom();
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for messages:', error);
                    });
            }
            
            // Prevent duplicate submissions on refresh/F5
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Add submit handler to prevent duplicate submissions
            document.getElementById('messageForm').addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton.disabled) {
                    e.preventDefault(); // Prevent double submission
                    return false;
                }
                
                // Disable the button temporarily
                submitButton.disabled = true;
                setTimeout(() => {
                    submitButton.disabled = false;
                }, 2000); // Re-enable after 2 seconds
            });
            
            // Run on page load
            window.onload = function() {
                scrollToBottom();
                
                // Set up auto-refresh
                if (document.getElementById('chatMessages')) {
                    setInterval(checkForNewMessages, 5000); // Check every 5 seconds
                }
            };
        </script>
    <?php endif; ?>
</div>

<?php 
include("includes/footer.php");
$conn->close();
?>