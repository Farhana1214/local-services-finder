<!DOCTYPE html>
<html>
<head>
    <title>Chatbot API Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h3 { color: #667eea; }
        input { padding: 8px; width: 300px; }
        button { padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .response { background: #f0f0f0; padding: 15px; margin-top: 10px; border-radius: 3px; white-space: pre-wrap; font-size: 12px; }
        .service { background: white; border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🤖 Chatbot API Test</h1>
    
    <div class="test-section">
        <h3>Test 1: Search for "plumber"</h3>
        <button onclick="testSearch('plumber')">Send: plumber</button>
        <div id="test1-response" class="response" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h3>Test 2: Search for "electrical"</h3>
        <button onclick="testSearch('electrical')">Send: electrical</button>
        <div id="test2-response" class="response" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h3>Test 3: Search for "AC repair"</h3>
        <button onclick="testSearch('AC repair')">Send: AC repair</button>
        <div id="test3-response" class="response" style="display:none;"></div>
    </div>

    <div class="test-section">
        <h3>Test 4: Custom Message</h3>
        <input type="text" id="customMessage" placeholder="Type a message..." value="cleaning"/>
        <button onclick="testCustom()">Send Message</button>
        <div id="test4-response" class="response" style="display:none;"></div>
    </div>

    <script>
        async function testSearch(message) {
            const testNum = message === 'plumber' ? 1 : message === 'electrical' ? 2 : message === 'AC repair' ? 3 : 4;
            const responseDiv = document.getElementById(`test${testNum}-response`);
            responseDiv.style.display = 'block';
            responseDiv.textContent = '⏳ Loading...';

            try {
                const response = await fetch('api/chatbot-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        user_city: 'Lahore',
                        user_location: null
                    })
                });

                const data = await response.json();
                
                let output = `API Response:\n${JSON.stringify(data, null, 2)}\n\n`;
                
                if (data.success) {
                    output += `✅ SUCCESS\n`;
                    output += `Reply: ${data.reply}\n`;
                    output += `Services Found: ${data.services ? data.services.length : 0}\n\n`;
                    
                    if (data.services && data.services.length > 0) {
                        output += `SERVICES:\n`;
                        data.services.forEach(s => {
                            output += `\n📌 ${s.business_name} (ID: ${s.service_id})\n`;
                            output += `   Service: ${s.service_name}\n`;
                            output += `   City: ${s.city}\n`;
                            output += `   Rating: ${s.avg_rating} ⭐ (${s.total_reviews} reviews)\n`;
                            output += `   Price: Rs. ${s.min_price}\n`;
                            output += `   Description: ${s.business_description}\n`;
                        });
                    } else {
                        output += `❌ NO SERVICES FOUND`;
                    }
                } else {
                    output += `❌ ERROR: ${data.message || 'Unknown error'}`;
                }
                
                responseDiv.textContent = output;
            } catch (error) {
                responseDiv.textContent = `❌ Network Error: ${error.message}`;
            }
        }

        function testCustom() {
            const msg = document.getElementById('customMessage').value;
            testSearch(msg);
        }
    </script>
</body>
</html>
