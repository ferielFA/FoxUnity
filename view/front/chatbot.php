<?php
require_once __DIR__ . '/../../controller/UserController.php';

$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* User Dropdown Menu Styles */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .username-display {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 8px;
        }

        .username-display:hover {
            background: rgba(255, 122, 0, 0.1);
        }

        .username-display img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff7a00;
        }

        .username-display span {
            color: #ff7a00;
            font-weight: 600;
            font-size: 16px;
        }

        .username-display i.fa-chevron-down {
            font-size: 12px;
            color: #ff7a00;
            transition: transform 0.3s ease;
        }

        .username-display i.fa-user-circle {
            font-size: 24px;
            color: #ff7a00;
        }

        .user-dropdown.active .username-display i.fa-chevron-down {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: rgba(20, 20, 20, 0.98);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 12px;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .user-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover {
            background: rgba(255, 122, 0, 0.1);
            border-left-color: #ff7a00;
        }

        .dropdown-item i {
            font-size: 16px;
            color: #ff7a00;
            width: 20px;
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 122, 0, 0.2);
            margin: 5px 0;
        }

        .dropdown-item.logout {
            color: #ff4444;
        }

        .dropdown-item.logout i {
            color: #ff4444;
        }

        .dropdown-item.logout:hover {
            background: rgba(255, 68, 68, 0.1);
            border-left-color: #ff4444;
        }

        /* Cart icon styling */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }

        .cart-icon i {
            color: #ff7a00;
            font-size: 18px;
        }

        .cart-count {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 700;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4);
        }

        /* Chatbot Container */
        .chatbot-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: calc(100vh - 300px);
        }

        .chatbot-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .chatbot-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 42px;
            color: #fff;
            margin-bottom: 15px;
        }

        .chatbot-header h1 span {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .chatbot-header p {
            color: #aaa;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Chat Interface */
        .chat-interface {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .chat-header {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-header-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        .chat-header-info h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            color: #fff;
            margin: 0;
        }

        .chat-header-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin: 3px 0 0 0;
        }

        .chat-status {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-size: 14px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            background: #00ff88;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Chat Messages Area */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(255, 122, 0, 0.5);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 122, 0, 0.7);
        }

        .message {
            display: flex;
            gap: 15px;
            animation: messageSlide 0.3s ease-out;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user-message {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
        }

        .message.bot-message .message-avatar {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
        }

        .message.user-message .message-avatar {
            background: rgba(255, 255, 255, 0.1);
            color: #ff7a00;
            border: 2px solid #ff7a00;
        }

        .message-content {
            flex: 1;
            max-width: 70%;
        }

        .message-bubble {
            padding: 15px 20px;
            border-radius: 15px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .message.bot-message .message-bubble {
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: #fff;
        }

        .message.user-message .message-bubble {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .message-time {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        /* Welcome Message */
        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: #aaa;
        }

        .welcome-message i {
            font-size: 60px;
            color: #ff7a00;
            margin-bottom: 20px;
        }

        .welcome-message h3 {
            font-family: 'Orbitron', sans-serif;
            color: #fff;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome-message p {
            font-size: 14px;
            line-height: 1.6;
        }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .suggestion-chip {
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: #ff7a00;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .suggestion-chip:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
            transform: translateY(-2px);
        }

        /* Typing Indicator */
        .typing-indicator {
            display: none;
            padding: 15px 20px;
            background: rgba(255, 122, 0, 0.1);
            border: 1px solid rgba(255, 122, 0, 0.3);
            border-radius: 15px;
            width: fit-content;
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-dots {
            display: flex;
            gap: 5px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            background: #ff7a00;
            border-radius: 50%;
            animation: typingDot 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingDot {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }

        /* Chat Input Area */
        .chat-input-area {
            padding: 20px 30px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 122, 0, 0.2);
        }

        .chat-input-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 25px;
            padding: 12px 20px;
            color: #fff;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .chat-input:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
        }

        .chat-input::placeholder {
            color: #666;
        }

        .send-button {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(255, 122, 0, 0.5);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        .clear-chat-button {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-chat-button:hover {
            background: rgba(255, 68, 68, 0.1);
            border-color: #ff4444;
            color: #ff4444;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chatbot-header h1 {
                font-size: 32px;
            }

            .chat-interface {
                height: 500px;
            }

            .message-content {
                max-width: 85%;
            }

            .chat-input-container {
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Animated bubbles -->
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>

        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="shop.php">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.php">News</a>
            <a href="reclamation.php">Support</a>
            <a href="contact_us.php">New Request</a>
            <a href="public_reclamations.php"><i class="fas fa-star"></i> Public Evaluations</a>
            <a href="about.php">About Us</a>
            <a href="chatbot.php" class="active"><i class="fas fa-robot"></i> AI Assistant</a>
        </nav>

        <div class="header-right">
            <div class="user-dropdown" id="userDropdown">
                <div class="username-display">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <?php if ($userImage): ?>
                            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                        <span>Guest</span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <div class="dropdown-menu">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="tradehis.php" class="dropdown-item">
                            <i class="fas fa-history"></i>
                            <span>Trade History</span>
                        </a>
                        <a href="events.php?view=history" class="dropdown-item">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Event History</span>
                        </a>
                        <?php
                        $userRole = strtolower($currentUser->getRole());
                        if ($userRole === 'admin' || $userRole === 'superadmin'):
                        ?>
                            <a href="../back/dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login/Register</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="main-section">
        <div class="chatbot-container">
            <!-- Chatbot Header -->
            <div class="chatbot-header">
                <h1>FoxUnity <span>AI Assistant</span></h1>
                <p>Ask me anything about our gaming charity platform!</p>
            </div>

            <!-- Chat Interface -->
            <div class="chat-interface">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="chat-header-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="chat-header-info">
                        <h3>FoxUnity Assistant</h3>
                        <p>Powered by DeepSeek AI</p>
                    </div>
                    <div class="chat-status">
                        <span class="status-indicator"></span>
                        <span>Online</span>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages" id="chatMessages">
                    <div class="welcome-message">
                        <i class="fas fa-robot"></i>
                        <h3>Welcome to FoxUnity AI Assistant!</h3>
                        <p>I'm here to help you navigate our gaming charity platform. Ask me about our shop, trading system, events, news, or how your contributions support charities worldwide.</p>
                        <div class="suggestion-chips">
                            <span class="suggestion-chip" onclick="sendSuggestion('What products are available in the shop?')">Shop Products</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('How does the trading system work?')">Trading System</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('Tell me about upcoming events')">Upcoming Events</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('How does the charity donation work?')">Charity Info</span>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="chat-input-area">
                    <div class="chat-input-container">
                        <button class="clear-chat-button" onclick="clearChat()" title="Clear conversation">
                            <i class="fas fa-trash"></i>
                        </button>
                        <input 
                            type="text" 
                            class="chat-input" 
                            id="chatInput" 
                            placeholder="Type your message here..."
                            onkeypress="handleKeyPress(event)"
                        >
                        <button class="send-button" id="sendButton" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link"
                    onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.php">Contact Support</a>
                <a href="#">FAQ</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>

    <script>
        // Conversation history for context
        let conversationHistory = [];

        // Dropdown Menu Toggle
        document.addEventListener('DOMContentLoaded', function () {
            const userDropdown = document.getElementById('userDropdown');

            if (userDropdown) {
                const usernameDisplay = userDropdown.querySelector('.username-display');

                usernameDisplay.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });

                document.addEventListener('click', function (e) {
                    if (!userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        userDropdown.classList.remove('active');
                    }
                });
            }

            // Update cart count
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }

            // Focus on input
            document.getElementById('chatInput').focus();
        });

        // Handle Enter key press
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        // Send suggestion
        function sendSuggestion(text) {
            document.getElementById('chatInput').value = text;
            sendMessage();
        }

        // Send message
        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();

            if (!message) return;

            // Disable input and button
            input.disabled = true;
            document.getElementById('sendButton').disabled = true;

            // Add user message to chat
            addMessage(message, 'user');

            // Clear input
            input.value = '';

            // Show typing indicator
            showTypingIndicator();

            try {
                // Add to conversation history
                conversationHistory.push({
                    role: 'user',
                    content: message
                });

                // Call API
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: conversationHistory
                    })
                });

                const data = await response.json();

                // Hide typing indicator
                hideTypingIndicator();

                if (data.success) {
                    // Add bot response
                    addMessage(data.response, 'bot');

                    // Add to conversation history
                    conversationHistory.push({
                        role: 'assistant',
                        content: data.response
                    });

                    // Limit conversation history to last 10 messages
                    if (conversationHistory.length > 10) {
                        conversationHistory = conversationHistory.slice(-10);
                    }
                } else {
                    addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                    console.error('Chatbot error:', data.error);
                }
            } catch (error) {
                hideTypingIndicator();
                addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
                console.error('Network error:', error);
            }

            // Re-enable input and button
            input.disabled = false;
            document.getElementById('sendButton').disabled = false;
            input.focus();
        }

        // Add message to chat
        function addMessage(text, type) {
            const messagesContainer = document.getElementById('chatMessages');
            const welcomeMessage = messagesContainer.querySelector('.welcome-message');

            // Remove welcome message if exists
            if (welcomeMessage) {
                welcomeMessage.remove();
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;

            const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            messageDiv.innerHTML = `
                <div class="message-avatar">
                    ${type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>'}
                </div>
                <div class="message-content">
                    <div class="message-bubble">${escapeHtml(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;

            messagesContainer.appendChild(messageDiv);

            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Show typing indicator
        function showTypingIndicator() {
            const messagesContainer = document.getElementById('chatMessages');

            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message';
            typingDiv.id = 'typingIndicator';

            typingDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="typing-indicator active">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;

            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Hide typing indicator
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Clear chat
        function clearChat() {
            if (confirm('Are you sure you want to clear the conversation?')) {
                const messagesContainer = document.getElementById('chatMessages');
                messagesContainer.innerHTML = `
                    <div class="welcome-message">
                        <i class="fas fa-robot"></i>
                        <h3>Welcome to FoxUnity AI Assistant!</h3>
                        <p>I'm here to help you navigate our gaming charity platform. Ask me about our shop, trading system, events, news, or how your contributions support charities worldwide.</p>
                        <div class="suggestion-chips">
                            <span class="suggestion-chip" onclick="sendSuggestion('What products are available in the shop?')">Shop Products</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('How does the trading system work?')">Trading System</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('Tell me about upcoming events')">Upcoming Events</span>
                            <span class="suggestion-chip" onclick="sendSuggestion('How does the charity donation work?')">Charity Info</span>
                        </div>
                    </div>
                `;
                conversationHistory = [];
            }
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
    </script>
</body>

</html>
