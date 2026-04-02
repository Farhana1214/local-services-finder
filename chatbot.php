<?php
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

// Check if user is logged in (if not, show limited functionality)
$user_id = $_SESSION['user_id'] ?? null;
$logged_in = isLoggedIn();

// Get user's city for location-based recommendations
$user_city = '';
if ($logged_in && $user_id) {
    $user_data = getSingleResult($conn, "SELECT city FROM users WHERE user_id = ?", 'i', [$user_id]);
    $user_city = $user_data['city'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Chatbot - Service Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .chat-container {
            width: 100%;
            max-width: 700px;
            height: 90vh;
            background: white;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header h3 {
            margin: 0;
            font-weight: 600;
        }

        .chat-header p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .avatar {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #f8f9fa;
        }

        .message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.user .message-content {
            background: #667eea;
            color: white;
            border-radius: 15px 15px 5px 15px;
        }

        .message.bot .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 15px 15px 15px 5px;
        }

        .message-content {
            max-width: 80%;
            padding: 1rem;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.3rem;
        }

        .input-area {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 0.5rem;
        }

        .input-group {
            flex: 1;
        }

        .input-group input {
            border-radius: 25px !important;
            border: 2px solid #e0e0e0;
            padding: 0.75rem 1.2rem;
            font-size: 0.95rem;
        }

        .input-group input:focus {
            border-color: #667eea;
            box-shadow: none;
        }

        .send-btn {
            background: #667eea;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: all 0.3s;
        }

        .send-btn:hover {
            background: #764ba2;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .service-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .service-card strong {
            color: #667eea;
        }

        .price-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .typing {
            display: flex;
            gap: 4px;
        }

        .typing span {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }

        .quick-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .quick-btn {
            background: #f0f0f0;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .quick-btn:hover {
            background: #667eea;
            color: white;
        }

        .login-prompt {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .modal-backdrop {
            display: none;
        }

        .location-pill {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .location-pill button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            opacity: 0.8;
        }

        .location-pill button:hover {
            opacity: 1;
        }

        .map-container {
            display: none;
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .map-container.show {
            display: block;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .location-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: #764ba2;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .location-btn:hover {
            background: #667eea;
        }

        .location-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="avatar">💬</div>
            <div>
                <h3>Smart Service Finder</h3>
                <p>Ask me anything! (Urdu & English supported)</p>
            </div>
        </div>

        <!-- Messages -->
        <div class="messages-area" id="messagesArea">
            <?php if (!$logged_in): ?>
                <div class="login-prompt">
                    <i class="bi bi-info-circle"></i> 
                    <a href="login.php">Login</a> to see personalized recommendations based on your location.
                </div>
            <?php endif; ?>
            
            <div class="message bot">
                <div class="message-content">
                    <strong>Welcome! 👋</strong><br>
                    I can help you find services! Try saying:<br>
                    • "Mujhe AC repair chahiye" (I need AC repair)<br>
                    • "Electrical services nearby" <br>
                    • "Plumbing price estimate"<br>
                    • "Best painters in my area"
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="input-area">
            <div style="width: 100%;">
                <div id="locationPill" style="display: none;" class="location-pill">
                    <i class="bi bi-geo-alt"></i>
                    <span id="locationText"></span>
                    <button onclick="clearLocation()" type="button">×</button>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="location-btn" id="locationBtn" title="Detect my location">
                        <i class="bi bi-geo-alt-fill"></i>
                    </button>
                    <div class="input-group" style="flex: 1;">
                        <input 
                            type="text" 
                            id="userInput" 
                            placeholder="Type your request in Urdu or English..." 
                            autocomplete="off"
                        >
                    </div>
                    <button class="send-btn" id="sendBtn">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const messagesArea = document.getElementById('messagesArea');
        const locationBtn = document.getElementById('locationBtn');
        const locationPill = document.getElementById('locationPill');
        const locationText = document.getElementById('locationText');
        
        let userLocation = null;
        let userCity = '<?php echo addslashes($user_city); ?>';
        let map = null;
        let markers = [];

        // Geolocation
        locationBtn.addEventListener('click', detectLocation);

        function detectLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            locationBtn.disabled = true;
            locationBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    userLocation = { lat, lng };

                    // For now, use the city name directly or ask user
                    userCity = 'Nearby Area';
                    updateLocationUI();
                    
                    addMessage('📍 Location detected! Now I can show services near you.', 'bot');
                    addMessage('What service do you need? (e.g., "electrical", "AC repair", "plumbing")', 'bot');
                    
                    locationBtn.disabled = false;
                    locationBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i>';
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    alert('Could not detect location. Please enable location access or type your city.');
                    locationBtn.disabled = false;
                    locationBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i>';
                }
            );
        }

        function updateLocationUI() {
            locationText.textContent = userCity;
            locationPill.style.display = 'flex';
        }

        function clearLocation() {
            userCity = '';
            userLocation = null;
            locationPill.style.display = 'none';
            addMessage('Location cleared. Will show services from all areas.', 'bot');
        }

        // Handle Enter key
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && userInput.value.trim()) {
                sendMessage();
            }
        });

        sendBtn.addEventListener('click', sendMessage);

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            userInput.value = '';
            sendBtn.disabled = true;

            // Show typing indicator
            showTypingIndicator();

            try {
                const response = await fetch('api/chatbot-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        user_city: userCity,
                        user_location: userLocation
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                // Remove typing indicator
                removeTypingIndicator();

                if (data.success) {
                    addMessage(data.reply, 'bot', data.services);
                } else {
                    addMessage('❌ ' + (data.message || 'Unable to process. Try asking: "AC repair", "plumbing", "electrical"'), 'bot');
                }
            } catch (error) {
                console.error('Chatbot Error:', error);
                removeTypingIndicator();
                
                // Check if it's a network error
                if (error instanceof TypeError) {
                    addMessage('⚠️ Network error. Make sure the API endpoint is accessible. Check console for details.', 'bot');
                } else {
                    addMessage('❌ Error: ' + error.message + '<br><small>Check browser console (F12) for details</small>', 'bot');
                }
            }

            sendBtn.disabled = false;
            userInput.focus();
        }

        function addMessage(content, sender, services = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = content;

            if (services && services.length > 0) {
                const servicesDiv = document.createElement('div');
                servicesDiv.className = 'quick-suggestions';
                servicesDiv.style.flexDirection = 'column';
                servicesDiv.style.gap = '0.5rem';
                
                services.forEach(service => {
                    const serviceCard = document.createElement('div');
                    serviceCard.className = 'service-card';
                    serviceCard.innerHTML = `
                        <strong>${service.business_name}</strong><br>
                        📍 ${service.city || 'Location TBA'}<br>
                        ⭐ ${parseFloat(service.avg_rating || 0).toFixed(1)} (${service.total_reviews || 0} reviews)<br>
                        💰 From <span class="price-badge">Rs. ${service.min_price || 'Contact'}</span><br>
                        <small>${service.business_description || 'Professional services available'}</small>
                        <button onclick="window.location.href='booking.php?service_id=${service.service_id}'" 
                                class="btn btn-sm btn-outline-primary mt-2" style="width: 100%;">
                            Book Now →
                        </button>
                    `;
                    servicesDiv.appendChild(serviceCard);
                });

                contentDiv.appendChild(servicesDiv);
            }

            messageDiv.appendChild(contentDiv);
            messagesArea.appendChild(messageDiv);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        function showTypingIndicator() {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot';
            messageDiv.id = 'typingIndicator';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';

            messageDiv.appendChild(contentDiv);
            messagesArea.appendChild(messageDiv);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }
    </script>
</body>
</html>
