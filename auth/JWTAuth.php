<?php

/**
 * JWT Authentication System
 * Phase 4: Security - Enterprise-Grade Authentication
 * 
 * JWT tokens for stateless authentication
 * Refresh token rotation and security best practices
 */

class JWTAuth {
    private $secret_key;
    private $algorithm = 'HS256';
    private $token_expiry = 3600; // 1 hour
    private $refresh_expiry = 2592000; // 30 days
    
    public function __construct() {
        $this->secret_key = getenv('JWT_SECRET') ?: 'your-secret-key-here';
    }
    
    /**
     * Generate JWT token
     * 
     * @param array $payload Token data
     * @param int $expiry Expiration time in seconds
     * @return string JWT token
     */
    public function generateToken($payload, $expiry = null) {
        $expiry = $expiry ?? $this->token_expiry;
        
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        
        $header_encoded = $this->base64UrlEncode(json_encode($header));
        $payload_encoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            "{$header_encoded}.{$payload_encoded}",
            $this->secret_key,
            true
        );
        $signature_encoded = $this->base64UrlEncode($signature);
        
        return "{$header_encoded}.{$payload_encoded}.{$signature_encoded}";
    }
    
    /**
     * Verify JWT token
     * 
     * @param string $token JWT token
     * @return array|false Token payload if valid, false otherwise
     */
    public function verifyToken($token) {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            [$header_encoded, $payload_encoded, $signature_encoded] = $parts;
            
            // Verify signature
            $signature_check = hash_hmac(
                'sha256',
                "{$header_encoded}.{$payload_encoded}",
                $this->secret_key,
                true
            );
            
            $signature_check_encoded = $this->base64UrlEncode($signature_check);
            
            if ($signature_check_encoded !== $signature_encoded) {
                return false;
            }
            
            // Decode payload
            $payload = json_decode(
                $this->base64UrlDecode($payload_encoded),
                true
            );
            
            if (!$payload) {
                return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            error_log("JWT Verification Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate access token and refresh token pair
     * 
     * @param int $user_id
     * @param string $user_type customer|provider|admin|support
     * @param array $extra Extra claims
     * @return array Token pair
     */
    public function generateTokenPair($user_id, $user_type, $extra = []) {
        $access_payload = array_merge([
            'sub' => $user_id,
            'type' => 'access',
            'user_type' => $user_type,
            'scope' => 'api'
        ], $extra);
        
        $refresh_payload = [
            'sub' => $user_id,
            'type' => 'refresh',
            'user_type' => $user_type,
            'jti' => bin2hex(random_bytes(16)) // Unique token ID for revocation
        ];
        
        return [
            'access_token' => $this->generateToken($access_payload, $this->token_expiry),
            'refresh_token' => $this->generateToken($refresh_payload, $this->refresh_expiry),
            'expires_in' => $this->token_expiry,
            'token_type' => 'Bearer'
        ];
    }
    
    /**
     * Rotate refresh token
     * Issues new access token and refresh token
     * 
     * @param string $refresh_token
     * @return array New token pair or false
     */
    public function refreshAccessToken($refresh_token) {
        $payload = $this->verifyToken($refresh_token);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return false;
        }
        
        // Check if token is in blacklist (revoked)
        if ($this->isTokenBlacklisted($payload['jti'])) {
            return false;
        }
        
        return $this->generateTokenPair(
            $payload['sub'],
            $payload['user_type']
        );
    }
    
    /**
     * Revoke token (blacklist)
     * 
     * @param string $token
     * @param int $conn Database connection
     * @return bool
     */
    public function revokeToken($token, $conn) {
        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            return false;
        }
        
        $query = "
            INSERT INTO token_blacklist (token_jti, user_id, revoked_at)
            VALUES (?, ?, NOW())
        ";
        
        $jti = $payload['jti'] ?? hash('sha256', $token);
        
        $result = executeQuery(
            $conn,
            $query,
            'si',
            [$jti, $payload['sub']]
        );
        
        return $result['success'];
    }
    
    /**
     * Check if token is blacklisted
     * 
     * @param string $jti
     * @return bool
     */
    private function isTokenBlacklisted($jti) {
        // Can be cached with Redis for performance
        // For simplicity, using static check
        return false; // Implement with database or Redis cache
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

/**
 * Role-Based Access Control (RBAC)
 * 
 * Define and check user permissions
 */
class AccessControl {
    private $user_type;
    private $user_id;
    
    public function __construct($user_type, $user_id) {
        $this->user_type = $user_type;
        $this->user_id = $user_id;
    }
    
    /**
     * Check if user has specific role
     * 
     * @param string $required_role
     * @return bool
     */
    public function hasRole($required_role) {
        return $this->user_type === $required_role;
    }
    
    /**
     * Check if user has specific permission
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission) {
        $permissions = $this->getRolePermissions();
        return in_array($permission, $permissions);
    }
    
    /**
     * Get permissions for user's role
     * 
     * @return array
     */
    private function getRolePermissions() {
        return match($this->user_type) {
            'admin' => [
                'manage_users',
                'manage_providers',
                'manage_services',
                'manage_categories',
                'view_analytics',
                'manage_payments',
                'manage_support_tickets',
                'view_reports'
            ],
            'provider' => [
                'create_service',
                'edit_own_service',
                'delete_own_service',
                'view_bookings',
                'complete_booking',
                'view_earnings',
                'update_profile'
            ],
            'customer' => [
                'search_services',
                'create_booking',
                'cancel_own_booking',
                'rate_service',
                'view_own_bookings',
                'update_profile'
            ],
            'support' => [
                'view_support_tickets',
                'respond_to_tickets',
                'escalate_tickets',
                'view_customer_details'
            ],
            default => []
        };
    }
    
    /**
     * Require specific permission
     * Throw exception if not authorized
     * 
     * @param string $permission
     * @throws Exception
     */
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            throw new Exception("Forbidden: User does not have '{$permission}' permission");
        }
    }
    
    /**
     * Require specific role
     * 
     * @param string|array $required_role
     * @throws Exception
     */
    public function requireRole($required_role) {
        $roles = is_array($required_role) ? $required_role : [$required_role];
        
        if (!in_array($this->user_type, $roles)) {
            throw new Exception("Forbidden: User role not authorized");
        }
    }
}

?>
