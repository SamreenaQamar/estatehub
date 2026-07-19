<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer class="footer">
  <div class="footer-container">

    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-col brand">
        <div class="logo">
          <i class="fas fa-home"></i>
          <span>EstateHub</span>
        </div>

        <p>Your trusted real estate partner in finding the best properties across Pakistan.</p>

        <div class="social">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="index.php" style="color:inherit;text-decoration:none;">Home</a></li>
          <li><a href="listings.php" style="color:inherit;text-decoration:none;">Listings</a></li>
          <li><a href="about.php" style="color:inherit;text-decoration:none;">About Us</a></li>
          <li><a href="contact.php" style="color:inherit;text-decoration:none;">Contact Us</a></li>
          <li><a href="faq.php" style="color:inherit;text-decoration:none;">FAQs</a></li>
        </ul>
      </div>


      <!-- Account -->
      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'seller') ? 'seller-dashboard.php' : 'buyer-dashboard.php'; ?>" style="color:inherit;text-decoration:none;">My Dashboard</a></li>
            <li><a href="wishlist.php" style="color:inherit;text-decoration:none;">Wishlist</a></li>
            <li><a href="messages.php" style="color:inherit;text-decoration:none;">Messages</a></li>
          <?php else: ?>
            <li><a href="login.php" style="color:inherit;text-decoration:none;">Login</a></li>
            <li><a href="signup.php" style="color:inherit;text-decoration:none;">Sign Up</a></li>
            <li><a href="login.php" style="color:inherit;text-decoration:none;">My Dashboard</a></li>
            <li><a href="wishlist.php" style="color:inherit;text-decoration:none;">Wishlist</a></li>
            <li><a href="login.php" style="color:inherit;text-decoration:none;">Messages</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Support -->
      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="contact.php" style="color:inherit;text-decoration:none;">Help Center</a></li>
          <li><a href="terms.php" style="color:inherit;text-decoration:none;">Terms & Conditions</a></li>
          <li><a href="privacy.php" style="color:inherit;text-decoration:none;">Privacy Policy</a></li>
          <li><a href="disclaimer.php" style="color:inherit;text-decoration:none;">Disclaimer</a></li>
        </ul>
      </div>

      <!-- Newsletter -->
      <div class="footer-col newsletter">
        <h4>Subscribe Newsletter</h4>
        <p>Subscribe to get updates about new properties and offers.</p>

        <div class="input-box">
          <input type="email" placeholder="Enter your email">
          <button><i class="fas fa-paper-plane"></i></button>
        </div>
      </div>

    </div>

    <!-- Bottom -->
    <div class="footer-bottom">
      <p>© <?php echo date('Y'); ?> EstateHub. All Rights Reserved.     ❤️Samreena Qamar❤️ </p>

      <div class="payments">
        <img src="https://cdn-icons-png.flaticon.com/512/196/196578.png" alt="Visa">
        <img src="https://cdn-icons-png.flaticon.com/512/196/196561.png" alt="Mastercard">
        <img src="https://cdn-icons-png.flaticon.com/512/196/196566.png" alt="PayPal">
      </div>
    </div>

  </div>
</footer>
<!-- Chatbot Widget -->
<style>
    .chatbot-widget {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        font-family: 'Inter', sans-serif;
    }
    
    /* Chat Button */
    .chatbot-toggle {
        width: 58px;
        height: 58px;
        background: linear-gradient(135deg, #0E7A4E, #0A5C3A);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(37,99,235,0.35);
        transition: all 0.3s ease;
        border: none;
        position: relative;
    }
    .chatbot-toggle:hover {
        transform: scale(1.08);
        box-shadow: 0 12px 32px #0E7A4E;
    }
    .chatbot-toggle svg {
        width: 26px;
        height: 26px;
        stroke: white;
    }
    .chatbot-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #0E7A4E;
        animation: pulse 2s infinite;
        opacity: 0;
    }
    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    
    /* Chat Window */
    .chatbot-window {
        position: absolute;
        bottom: 75px;
        right: 0;
        width: 380px;
        height: 520px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 10px 50px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #E5E7EB;
    }
    .chatbot-window.open {
        display: flex;
    }
    
    /* Chat Header */
    .chatbot-header {
        background: linear-gradient(135deg, #123524, #0A2318);
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: white;
    }
    .chatbot-avatar {
        width: 42px;
        height: 42px;
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .chatbot-avatar svg {
        width: 22px;
        height: 22px;
    }
    .chatbot-header-info h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }
    .chatbot-header-info span {
        font-size: 11px;
        opacity: 0.7;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .chatbot-header-info span::before {
        content: '';
        width: 7px;
        height: 7px;
        background: #10B981;
        border-radius: 50%;
        display: inline-block;
    }
    .chatbot-close {
        margin-left: auto;
        background: rgba(255,255,255,0.1);
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.2s;
    }
    .chatbot-close:hover {
        background: rgba(255,255,255,0.2);
    }
    .chatbot-close svg {
        width: 16px;
        height: 16px;
    }
    
    /* Chat Messages */
    .chatbot-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #F8FAFC;
    }
    .chatbot-messages::-webkit-scrollbar {
        width: 4px;
    }
    .chatbot-messages::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 4px;
    }
    
    .chat-message {
        display: flex;
        gap: 8px;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .chat-message.bot {
        align-items: flex-start;
    }
    .chat-message.user {
        flex-direction: row-reverse;
    }
    
    .msg-avatar {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 12px;
    }
    .chat-message.bot .msg-avatar {
        background: #E7F5EC;
        color: #0E7A4E;
    }
    .chat-message.user .msg-avatar {
        background: #0E7A4E;
        color: white;
    }
    
    .msg-bubble {
        max-width: 78%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 13px;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-line;
    }
    .chat-message.bot .msg-bubble {
        background: white;
        color: #374151;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .chat-message.user .msg-bubble {
        background: #0E7A4E;
        color: white;
        border-bottom-right-radius: 4px;
    }
    
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
    }
    .typing-indicator span {
        width: 7px;
        height: 7px;
        background: #94A3B8;
        border-radius: 50%;
        animation: bounce 1.4s infinite;
    }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-8px); }
    }
    
    /* Chat Input */
    .chatbot-input {
        padding: 14px 16px;
        background: white;
        border-top: 1px solid #E5E7EB;
        display: flex;
        gap: 10px;
    }
    .chatbot-input input {
        flex: 1;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: all 0.2s;
    }
    .chatbot-input input:focus {
        border-color: #0E7A4E;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
    }
    .chatbot-send {
        width: 42px;
        height: 42px;
        background: #0E7A4E;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .chatbot-send:hover {
        background: #0A5C3A;
    }
    .chatbot-send svg {
        width: 18px;
        height: 18px;
        stroke: white;
    }
    
    /* Suggestions */
    .chatbot-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 16px 12px;
        background: white;
    }
    .suggestion-chip {
        padding: 5px 12px;
        background: #E7F5EC;
        color: #0E7A4E;
        border-radius: 16px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .suggestion-chip:hover {
        background: #DBEAFE;
    }
    
    @media (max-width: 480px) {
        .chatbot-window {
            width: calc(100vw - 40px);
            right: -10px;
            height: 480px;
        }
    }
</style>

<div class="chatbot-widget">
    <!-- Chat Window -->
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <div class="chatbot-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
            </div>
            <div class="chatbot-header-info">
                <h3>EstateBot</h3>
                <span>Online</span>
            </div>
            <button class="chatbot-close" onclick="closeChatbot()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chat-message bot">
                <div class="msg-avatar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg>
                </div>
                <div class="msg-bubble">Hello! I'm EstateBot. How can I help you with your property search today?</div>
            </div>
        </div>
        
        <div class="chatbot-suggestions" id="chatbotSuggestions">
            <button class="suggestion-chip" onclick="sendSuggestion('How many properties are available?')">Available Properties</button>
            <button class="suggestion-chip" onclick="sendSuggestion('What cities do you cover?')">Cities</button>
            <button class="suggestion-chip" onclick="sendSuggestion('What are the prices?')">Price Range</button>
            <button class="suggestion-chip" onclick="sendSuggestion('How can I sell my property?')">How to Sell</button>
        </div>
        
        <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Type your message..." onkeypress="if(event.key==='Enter')sendMessage()">
            <button class="chatbot-send" onclick="sendMessage()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
    
    <!-- Toggle Button -->
    <button class="chatbot-toggle" id="chatbotToggle" onclick="openChatbot()">
        <span class="chatbot-pulse"></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
    </button>
</div>

<script>
function openChatbot() {
    document.getElementById('chatbotWindow').classList.add('open');
    document.getElementById('chatbotToggle').style.display = 'none';
    document.getElementById('chatbotInput').focus();
}

function closeChatbot() {
    document.getElementById('chatbotWindow').classList.remove('open');
    document.getElementById('chatbotToggle').style.display = 'flex';
}

function sendSuggestion(text) {
    document.getElementById('chatbotInput').value = text;
    sendMessage();
}

function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    if (!message) return;
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message bot';
    typingDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
    const messagesDiv = document.getElementById('chatbotMessages');
    messagesDiv.appendChild(typingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    // Send to server
    fetch('chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        // Remove typing indicator
        typingDiv.remove();
        // Add bot reply
        addMessage(data.reply, 'bot');
    })
    .catch(() => {
        typingDiv.remove();
        addMessage('Sorry, I\'m having trouble connecting. Please try again.', 'bot');
    });
}

function addMessage(text, type) {
    const messagesDiv = document.getElementById('chatbotMessages');
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message ' + type;
    
    const avatar = type === 'bot' 
        ? '<div class="msg-avatar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg></div>'
        : '<div class="msg-avatar">U</div>';
    
    msgDiv.innerHTML = avatar + '<div class="msg-bubble">' + text + '</div>';
    messagesDiv.appendChild(msgDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
</script>