<?php

/**
 * Third-Party Integration Services
 * Phase 5: Integration & Ecosystem
 * 
 * Payment gateways, SMS, Email, Maps, etc.
 */

class PaymentGateway {
    private $stripe_api_key;
    private $jazzcash_api_key;
    
    public function __construct() {
        $this->stripe_api_key = getenv('STRIPE_API_KEY');
        $this->jazzcash_api_key = getenv('JAZZCASH_API_KEY');
    }
    
    /**
     * Process payment via Stripe
     * 
     * @param float $amount Amount to charge
     * @param string $currency Currency code (usd, pkr)
     * @param array $card_details Card information
     * @param string $description Payment description
     * @return array Payment result
     */
    public function processStripePayment($amount, $currency, $card_details, $description) {
        try {
            $payload = [
                'amount' => intval($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'card' => [
                    'number' => $card_details['number'],
                    'exp_month' => $card_details['exp_month'],
                    'exp_year' => $card_details['exp_year'],
                    'cvc' => $card_details['cvc']
                ],
                'description' => $description
            ];
            
            $response = $this->callStripeAPI('/v1/charges', 'POST', $payload);
            
            if ($response && $response['status'] === 'succeeded') {
                return [
                    'success' => true,
                    'transaction_id' => $response['id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'completed'
                ];
            }
            
            return [
                'success' => false,
                'message' => $response['error']['message'] ?? 'Payment failed'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment via JazzCash (Pakistani payment gateway)
     * 
     * @param float $amount
     * @param string $phone_number Customer phone
     * @param string $reference Booking reference
     * @return array Payment result
     */
    public function processJazzCashPayment($amount, $phone_number, $reference) {
        try {
            $timestamp = date('YmdHis');
            $expiration = date('YmdHis', strtotime('+10 minutes'));
            
            // JazzCash API payload
            $payload = [
                'pp_Amount' => $amount . '.00',
                'pp_BillReference' => $reference,
                'pp_Description' => 'Service Booking Payment',
                'pp_Language' => 'en',
                'pp_MerchantID' => getenv('JAZZCASH_MERCHANT_ID'),
                'pp_Password' => getenv('JAZZCASH_PASSWORD'),
                'pp_ReturnURL' => getenv('SITE_URL') . 'payment-callback.php',
                'pp_TxnRefNo' => 'TXN' . time(),
                'pp_TxnType' => 'MWALLET',
                'pp_Version' => '1.1',
                'pp_expiryDateTime' => $expiration,
                'pp_txnDateTime' => $timestamp
            ];
            
            // Generate security signature
            $string_to_hash = implode('&', [
                $payload['pp_Amount'],
                $payload['pp_BillReference'],
                $payload['pp_Description'],
                $payload['pp_Language'],
                $payload['pp_MerchantID'],
                $payload['pp_Password'],
                $payload['pp_ReturnURL'],
                $payload['pp_TxnRefNo'],
                $payload['pp_TxnType'],
                $payload['pp_Version'],
                $payload['pp_expiryDateTime'],
                $payload['pp_txnDateTime']
            ]);
            
            $payload['pp_SecureHash'] = hash_hmac(
                'sha256',
                $string_to_hash,
                getenv('JAZZCASH_SECURE_KEY')
            );
            
            $response = $this->callJazzCashAPI('/api/Payment/DoTransaction', $payload);
            
            if ($response && isset($response['pp_ResponseCode']) && 
                $response['pp_ResponseCode'] === '000') {
                return [
                    'success' => true,
                    'transaction_id' => $response['pp_TxnRefNo'],
                    'amount' => $amount,
                    'currency' => 'PKR',
                    'status' => 'pending', // Requires user confirmation
                    'redirect_url' => $response['pp_AuthCode']
                ];
            }
            
            return [
                'success' => false,
                'message' => $response['pp_ResponseMessage'] ?? 'Payment failed'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'JazzCash payment error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Call Stripe API
     */
    private function callStripeAPI($endpoint, $method, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->stripe_api_key . ':');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Call JazzCash API
     */
    private function callJazzCashAPI($endpoint, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getenv('JAZZCASH_API_URL') . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('JAZZCASH_API_KEY')
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

/**
 * Communication Services
 * SMS and Email integration
 */
class CommunicationService {
    private $twilio_sid;
    private $twilio_auth;
    private $sendgrid_api_key;
    
    public function __construct() {
        $this->twilio_sid = getenv('TWILIO_ACCOUNT_SID');
        $this->twilio_auth = getenv('TWILIO_AUTH_TOKEN');
        $this->sendgrid_api_key = getenv('SENDGRID_API_KEY');
    }
    
    /**
     * Send SMS via Twilio
     * 
     * @param string $phone_number Recipient phone
     * @param string $message Message to send
     * @return array Send result
     */
    public function sendSMS($phone_number, $message) {
        try {
            $twilio_phone = getenv('TWILIO_PHONE_NUMBER');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilio_sid}/Messages.json");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->twilio_sid . ':' . $this->twilio_auth);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'From' => $twilio_phone,
                'To' => $phone_number,
                'Body' => $message
            ]));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code >= 200 && $http_code < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message_id' => $data['sid'],
                    'status' => $data['status']
                ];
            }
            
            return ['success' => false, 'message' => 'SMS failed to send'];
            
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'SMS service error'];
        }
    }
    
    /**
     * Send Email via SendGrid
     * 
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $html_body HTML email body
     * @param array $attachments Optional attachments
     * @return array Send result
     */
    public function sendEmail($recipient, $subject, $html_body, $attachments = []) {
        try {
            $email_payload = [
                'personalizations' => [
                    [
                        'to' => [['email' => $recipient]],
                        'subject' => $subject
                    ]
                ],
                'from' => [
                    'email' => getenv('SENDER_EMAIL'),
                    'name' => getenv('SITE_NAME')
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $html_body
                    ]
                ]
            ];
            
            // Add attachments if provided
            if (!empty($attachments)) {
                $email_payload['attachments'] = $attachments;
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->sendgrid_api_key,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 202 || $http_code === 200) {
                return [
                    'success' => true,
                    'message' => 'Email queued successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Email send failed'];
            
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email service error'];
        }
    }
}

/**
 * Location & Maps Service
 * Google Maps integration
 */
class LocationService {
    private $google_api_key;
    
    public function __construct() {
        $this->google_api_key = getenv('GOOGLE_MAPS_API_KEY');
    }
    
    /**
     * Get distance between two locations
     * 
     * @param string $from_address Starting address
     * @param string $to_address Destination address
     * @return array Distance information
     */
    public function getDistance($from_address, $to_address) {
        try {
            $from_encoded = urlencode($from_address);
            $to_encoded = urlencode($to_address);
            
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json" .
                   "?origins={$from_encoded}" .
                   "&destinations={$to_encoded}" .
                   "&key={$this->google_api_key}";
            
            $response = json_decode(file_get_contents($url), true);
            
            if ($response['status'] === 'OK' && 
                isset($response['rows'][0]['elements'][0])) {
                
                $element = $response['rows'][0]['elements'][0];
                
                if ($element['status'] === 'OK') {
                    return [
                        'success' => true,
                        'distance_km' => $element['distance']['value'] / 1000,
                        'distance_text' => $element['distance']['text'],
                        'duration' => $element['duration']['text']
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Unable to calculate distance'];
            
        } catch (Exception $e) {
            error_log("Distance Calculation Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Location service error'];
        }
    }
}

?>
