<?php

/**
 * AI Chatbot Service
 * Phase 2: Core Development - Smart Chatbot
 * 
 * NLP-powered customer support and service assistance chatbot
 * Handles multi-turn conversations with context preservation
 */

class SmartChatbot {
    private $conn;
    private $openai_api_key;
    private $user_id;
    
    public function __construct($database_connection, $user_id) {
        $this->conn = $database_connection;
        $this->openai_api_key = getenv('OPENAI_API_KEY');
        $this->user_id = $user_id;
    }
    
    /**
     * Process user message and generate response
     * Maintains conversation context and intent recognition
     * 
     * @param string $message User message
     * @param int $conversation_id Optional conversation ID for context
     * @return array Bot response and relevant actions
     */
    public function chat($message, $conversation_id = null) {
        try {
            // Get conversation context if exists
            $context = $conversation_id ? 
                $this->getConversationContext($conversation_id) : 
                [];
            
            // Intent recognition
            $intent = $this->recognizeIntent($message);
            
            // Route to appropriate handler
            $response_data = match($intent) {
                'service_search' => $this->handleSearch($message),
                'booking_help' => $this->handleBookingHelp($intent, $context),
                'payment_issue' => $this->handlePaymentIssue($message),
                'complaint' => $this->escalateToSupport($message),
                'general_query' => $this->generateAIResponse($message, $context),
                default => $this->generateAIResponse($message, $context)
            };
            
            // Save conversation
            $conversation_id = $this->saveConversation(
                $message,
                $response_data['response'],
                $intent,
                $conversation_id
            );
            
            return [
                'success' => true,
                'response' => $response_data['response'],
                'intent' => $intent,
                'actions' => $response_data['actions'] ?? [],
                'conversation_id' => $conversation_id
            ];
            
        } catch (Exception $e) {
            error_log("Chatbot Error: " . $e->getMessage());
            return [
                'success' => false,
                'response' => 'Sorry, I encountered an error. Please try again.',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Recognize user intent from message
     * Uses NLP and keyword matching
     * 
     * @param string $message
     * @return string Intent type
     */
    private function recognizeIntent($message) {
        $message_lower = strtolower($message);
        
        // Service search keywords
        if (preg_match('/find|search|looking for|need|want|find me|show me/i', $message)) {
            return 'service_search';
        }
        
        // Booking help keywords
        if (preg_match('/book|booking|how to|help|guide|tutorial/i', $message)) {
            return 'booking_help';
        }
        
        // Payment issues
        if (preg_match('/pay|payment|charge|refund|money|price|cost/i', $message)) {
            return 'payment_issue';
        }
        
        // Complaints/Issues
        if (preg_match('/complain|issue|problem|broken|bad|poor|terrible/i', $message)) {
            return 'complaint';
        }
        
        // Default to general query
        return 'general_query';
    }
    
    /**
     * Handle service search requests
     * Extract service type and location from message
     * 
     * @param string $message
     * @return array Response data with search results
     */
    private function handleSearch($message) {
        try {
            // Use NLP to extract service type and location
            $extraction_prompt = "Extract the service type and location from: {$message}
            Return JSON: {'service': 'type of service', 'location': 'city/area'}";
            
            $extracted = $this->callGPT($extraction_prompt);
            $parsed = json_decode($extracted, true) ?? [];
            
            $service_type = $parsed['service'] ?? null;
            $location = $parsed['location'] ?? null;
            
            if (!$service_type) {
                return [
                    'response' => 'I can help you find a service! What type of service are you looking for? (e.g., plumber, electrician, cleaner)',
                    'actions' => []
                ];
            }
            
            // Search for matching services
            $search_engine = new AISearchEngine($this->conn);
            $results = $search_engine->searchWithRAG(
                $service_type . ($location ? " in $location" : ""),
                [],
                5
            );
            
            if (!$results['success']) {
                return [
                    'response' => "I couldn't find any services matching your criteria. Could you provide more details?",
                    'actions' => []
                ];
            }
            
            $services = $results['data']['services'];
            
            // Format response
            $response = "Great! I found " . count($services) . " matching services:\n\n";
            
            foreach (array_slice($services, 0, 3) as $s) {
                $response .= "• {$s['name']} by {$s['business_name']}\n";
                $response .= "  Rating: {$s['avg_rating']}/5 ⭐ | Price: Rs {$s['price']}\n";
                $response .= "  Location: {$s['city']}\n\n";
            }
            
            $response .= "Would you like to book one of these services?";
            
            return [
                'response' => $response,
                'actions' => [
                    ['type' => 'show_services', 'data' => array_slice($services, 0, 5)]
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Search Handler Error: " . $e->getMessage());
            return [
                'response' => 'I had trouble searching for services. Please try again.',
                'actions' => []
            ];
        }
    }
    
    /**
     * Handle booking help and guidance
     * 
     * @param string $intent
     * @param array $context
     * @return array
     */
    private function handleBookingHelp($intent, $context) {
        $help_text = "Here's how to book a service:\n\n";
        $help_text .= "1. **Search** - Use the search bar to find the service you need\n";
        $help_text .= "2. **View Details** - Check provider ratings, prices, and reviews\n";
        $help_text .= "3. **Compare** - Look at multiple options\n";
        $help_text .= "4. **Book** - Select date/time that works for you\n";
        $help_text .= "5. **Pay** - Choose your preferred payment method\n";
        $help_text .= "6. **Confirm** - Get booking confirmation via email/SMS\n\n";
        $help_text .= "Any questions about these steps?";
        
        return [
            'response' => $help_text,
            'actions' => [['type' => 'show_help_video', 'url' => '/videos/booking-help.mp4']]
        ];
    }
    
    /**
     * Handle payment-related queries
     * 
     * @param string $message
     * @return array
     */
    private function handlePaymentIssue($message) {
        $response = "I can help with payment issues. ";
        
        if (preg_match('/refund/i', $message)) {
            $response .= "For refund requests, I can escalate your issue to our support team. ";
        } else if (preg_match('/declined|failed|not working/i', $message)) {
            $response .= "If your payment isn't going through, try these steps:\n";
            $response .= "1. Check your card/payment method\n";
            $response .= "2. Ensure you have sufficient balance\n";
            $response .= "3. Try a different payment method\n";
            $response .= "4. Contact your bank if the issue persists\n\n";
        }
        
        $response .= "Would you like me to connect you with our support team?";
        
        return [
            'response' => $response,
            'actions' => [
                ['type' => 'offer_support', 'text' => 'Connect with support'],
                ['type' => 'payment_help', 'url' => '/help/payment-methods']
            ]
        ];
    }
    
    /**
     * Escalate to human support
     * Create support ticket and end chat
     * 
     * @param string $message Initial complaint
     * @return array
     */
    private function escalateToSupport($message) {
        $ticket_id = $this->createSupportTicket($message);
        
        return [
            'response' => "I've created a support ticket for you (ID: #{$ticket_id}). " .
                         "Our support team will contact you shortly. Thank you for your patience!",
            'actions' => [
                ['type' => 'create_ticket', 'ticket_id' => $ticket_id],
                ['type' => 'end_chat', 'reason' => 'escalated_to_support']
            ]
        ];
    }
    
    /**
     * Generate AI response using GPT
     * Uses conversation context for coherent responses
     * 
     * @param string $message
     * @param array $context
     * @return array
     */
    private function generateAIResponse($message, $context = []) {
        try {
            // Build context string
            $context_str = '';
            if (!empty($context)) {
                foreach (array_slice($context, -5) as $ctx) { // Last 5 messages
                    $context_str .= "User: {$ctx['user_message']}\nBot: {$ctx['bot_response']}\n\n";
                }
            }
            
            $system_prompt = "You are a helpful customer service chatbot for Service Finder Pakistan. " .
                           "You help users find services, book appointments, and resolve issues. " .
                           "Be professional, friendly, and concise. Format responses clearly.";
            
            $response = $this->callOpenAI([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $context_str . "User: {$message}"]
                ],
                'temperature' => 0.7,
                'max_tokens' => 300
            ]);
            
            return [
                'response' => $response,
                'actions' => []
            ];
            
        } catch (Exception $e) {
            error_log("AI Response Generation Error: " . $e->getMessage());
            return [
                'response' => "I'm here to help! What can I assist you with?",
                'actions' => []
            ];
        }
    }
    
    /**
     * Get conversation history for context
     * 
     * @param int $conversation_id
     * @return array Previous messages
     */
    private function getConversationContext($conversation_id) {
        $query = "
            SELECT user_message, bot_response, created_at
            FROM chatbot_conversations
            WHERE id = ? AND user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ";
        
        $results = getMultipleResults(
            $this->conn,
            $query,
            'ii',
            [$conversation_id, $this->user_id]
        );
        
        return $results ?? [];
    }
    
    /**
     * Save conversation to database
     * 
     * @param string $user_message
     * @param string $bot_response
     * @param string $intent
     * @param int $conversation_id
     * @return int Conversation ID
     */
    private function saveConversation($user_message, $bot_response, $intent, $conversation_id) {
        $query = "
            INSERT INTO chatbot_conversations 
            (user_id, conversation_id, user_message, bot_response, intent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        
        $result = executeQuery(
            $this->conn,
            $query,
            'iisss',
            [
                $this->user_id,
                $conversation_id ?? date('YmdHis'),
                $user_message,
                $bot_response,
                $intent
            ]
        );
        
        if ($result['success']) {
            return $conversation_id ?? date('YmdHis');
        }
        
        return null;
    }
    
    /**
     * Create support ticket for escalation
     * 
     * @param string $message
     * @return int Ticket ID
     */
    private function createSupportTicket($message) {
        $query = "
            INSERT INTO support_tickets 
            (user_id, issue_type, message, status, created_at)
            VALUES (?, 'chatbot_escalation', ?, 'open', NOW())
        ";
        
        $result = executeQuery(
            $this->conn,
            $query,
            'is',
            [$this->user_id, $message]
        );
        
        if ($result['success']) {
            return $this->conn->insert_id;
        }
        
        return null;
    }
    
    /**
     * Call OpenAI API
     * 
     * @param array $payload
     * @return string|null
     */
    private function callOpenAI($payload) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->openai_api_key,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $data = json_decode($response, true);
                return $data['choices'][0]['message']['content'] ?? null;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("OpenAI API Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Simpler GPT call
     * 
     * @param string $prompt
     * @return string|null
     */
    private function callGPT($prompt) {
        return $this->callOpenAI([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 100
        ]);
    }
}

?>
