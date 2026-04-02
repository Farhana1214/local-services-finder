<?php

/**
 * Webhook Management System
 * Phase 5: Integration - Event-Driven Architecture
 * 
 * Send webhooks to third-party systems and handle incoming webhooks
 */

class WebhookManager {
    private $conn;
    private $max_retries = 3;
    private $retry_delay = 300; // 5 minutes
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Register webhook endpoint
     * 
     * @param int $user_id Business user ID
     * @param string $event_type Event to listen for
     * @param string $webhook_url Webhook endpoint URL
     * @return array Registration result
     */
    public function registerWebhook($user_id, $event_type, $webhook_url) {
        try {
            // Verify webhook URL is valid
            if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
                return ['success' => false, 'message' => 'Invalid webhook URL'];
            }
            
            // Test webhook with ping
            if (!$this->testWebhook($webhook_url)) {
                return ['success' => false, 'message' => 'Webhook URL is unreachable'];
            }
            
            $query = "
                INSERT INTO webhooks (user_id, event_type, webhook_url, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())
            ";
            
            $result = executeQuery(
                $this->conn,
                $query,
                'iss',
                [$user_id, $event_type, $webhook_url]
            );
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'webhook_id' => $this->conn->insert_id,
                    'message' => 'Webhook registered successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to register webhook'];
            
        } catch (Exception $e) {
            error_log("Webhook Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Webhook service error'];
        }
    }
    
    /**
     * Trigger webhook event
     * Send webhook to registered endpoints
     * 
     * @param string $event_type Event type
     * @param array $event_data Event payload
     * @param int $user_id User ID for filtering webhooks
     */
    public function triggerEvent($event_type, $event_data, $user_id = null) {
        try {
            // Get registered webhooks
            $query = "
                SELECT id, webhook_url, user_id FROM webhooks
                WHERE event_type = ? AND status = 'active'
            ";
            
            if ($user_id) {
                $query .= " AND (user_id = ? OR user_id IS NULL)";
                $webhooks = getMultipleResults($this->conn, $query, 'si', [$event_type, $user_id]);
            } else {
                $webhooks = getMultipleResults($this->conn, $query, 's', [$event_type]);
            }
            
            // Queue webhook deliveries
            foreach ($webhooks ?? [] as $webhook) {
                $this->queueWebhookDelivery($webhook['id'], $event_data);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Event Trigger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Queue webhook for delivery
     * 
     * @param int $webhook_id
     * @param array $payload
     */
    private function queueWebhookDelivery($webhook_id, $payload) {
        $query = "
            INSERT INTO webhook_events (webhook_id, payload, status, attempt_count, created_at)
            VALUES (?, ?, 'pending', 0, NOW())
        ";
        
        executeQuery(
            $this->conn,
            $query,
            'is',
            [$webhook_id, json_encode($payload)]
        );
    }
    
    /**
     * Process pending webhooks
     * Called by background job processor
     */
    public function processPendingWebhooks() {
        try {
            $query = "
                SELECT we.id, w.webhook_url, we.payload, we.attempt_count
                FROM webhook_events we
                JOIN webhooks w ON we.webhook_id = w.id
                WHERE we.status = 'pending' AND we.attempt_count < ?
                ORDER BY we.created_at ASC
                LIMIT 100
            ";
            
            $events = getMultipleResults(
                $this->conn,
                $query,
                'i',
                [$this->max_retries]
            );
            
            foreach ($events ?? [] as $event) {
                $this->deliverWebhook(
                    $event['id'],
                    $event['webhook_url'],
                    $event['payload'],
                    $event['attempt_count']
                );
            }
            
        } catch (Exception $e) {
            error_log("Webhook Processing Error: " . $e->getMessage());
        }
    }
    
    /**
     * Deliver webhook
     * 
     * @param int $event_id
     * @param string $webhook_url
     * @param string $payload JSON payload
     * @param int $attempt_count
     */
    private function deliverWebhook($event_id, $webhook_url, $payload, $attempt_count) {
        try {
            $response = $this->sendWebhookRequest($webhook_url, $payload);
            
            if ($response['success'] && $response['http_code'] >= 200 && 
                $response['http_code'] < 300) {
                // Success
                $this->updateWebhookStatus($event_id, 'delivered');
            } else {
                // Retry
                $this->updateWebhookStatus(
                    $event_id,
                    'pending',
                    $attempt_count + 1,
                    $response['error'] ?? 'HTTP ' . ($response['http_code'] ?? 'unknown')
                );
            }
            
        } catch (Exception $e) {
            error_log("Webhook Delivery Error: " . $e->getMessage());
            $this->updateWebhookStatus($event_id, 'pending', $attempt_count + 1, $e->getMessage());
        }
    }
    
    /**
     * Send webhook HTTP request
     * 
     * @param string $url
     * @param string $payload
     * @return array Response
     */
    private function sendWebhookRequest($url, $payload) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: ServiceFinder/1.0',
                'X-Webhook-Signature: ' . hash_hmac('sha256', $payload, getenv('WEBHOOK_SECRET'))
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            return [
                'success' => $http_code !== false,
                'http_code' => $http_code,
                'response' => $response,
                'error' => $error
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update webhook delivery status
     */
    private function updateWebhookStatus($event_id, $status, $attempts = null, $error_msg = null) {
        $query = "UPDATE webhook_events SET status = ?, attempt_count = COALESCE(?, attempt_count)";
        $params = [$status];
        $types = 's';
        
        if ($attempts !== null) {
            $query .= ", last_error = ?";
            $params[] = $error_msg ?? '';
            $types .= 's';
        }
        
        $query .= " WHERE id = ?";
        $params[] = $event_id;
        $types .= 'i';
        
        executeQuery($this->conn, $query, $types, $params);
    }
    
    /**
     * Handle incoming webhook
     * Verify and process webhooks from third-party services
     * 
     * @param array $payload Webhook payload
     * @param string $signature Webhook signature
     * @return array Processing result
     */
    public function handleIncomingWebhook($payload, $signature) {
        try {
            // Verify signature
            $payload_json = is_string($payload) ? $payload : json_encode($payload);
            $expected_signature = hash_hmac('sha256', $payload_json, getenv('WEBHOOK_SECRET'));
            
            if (!hash_equals($expected_signature, $signature)) {
                return ['success' => false, 'message' => 'Invalid signature'];
            }
            
            // Parse payload
            $data = is_string($payload) ? json_decode($payload, true) : $payload;
            
            // Route based on event type
            return match($data['event'] ?? null) {
                'payment.completed' => $this->handlePaymentWebhook($data),
                'sms.delivered' => $this->handleSMSWebhook($data),
                'email.bounced' => $this->handleEmailWebhook($data),
                default => ['success' => false, 'message' => 'Unknown event']
            };
            
        } catch (Exception $e) {
            error_log("Incoming Webhook Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Processing error'];
        }
    }
    
    /**
     * Handle payment webhook
     */
    private function handlePaymentWebhook($data) {
        // Update payment status in database
        $query = "UPDATE payments SET status = 'completed', external_id = ? WHERE id = ?";
        executeQuery(
            $this->conn,
            $query,
            'si',
            [$data['transaction_id'] ?? null, $data['payment_id'] ?? null]
        );
        
        return ['success' => true];
    }
    
    /**
     * Handle SMS delivery webhook
     */
    private function handleSMSWebhook($data) {
        // Update SMS status
        return ['success' => true];
    }
    
    /**
     * Handle email webhook
     */
    private function handleEmailWebhook($data) {
        // Handle bounces, complaints, etc.
        return ['success' => true];
    }
    
    /**
     * Test webhook connectivity
     */
    private function testWebhook($url) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $http_code !== 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

?>
